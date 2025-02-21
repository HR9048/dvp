<?php
include("../includes/connection.php");

// Set timezone to India/Kolkata
date_default_timezone_set("Asia/Kolkata");

// Get today's date and 30 days before
$today = date("Y-m-d");
$last_30_days = date("Y-m-d", strtotime("-30 days"));

$name = $_POST['name'];
$type = $_POST['type'];
$html = "";
if ($type === "Depot") {

    $stmt = $db->prepare("SELECT depot_id FROM location WHERE depot = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($depot_id);
    $stmt->fetch();
    $stmt->close();

    if (!$depot_id) {
        echo json_encode(["status" => "error", "message" => "Depot not found"]);
        exit;
    }

    // Fetch schedule count (SUM of sch_count)
    $stmt = $db->prepare("SELECT SUM(sch_count) FROM schedule_master WHERE depot_id = ? and status ='1'");
    $stmt->bind_param("i", $depot_id);
    $stmt->execute();
    $stmt->bind_result($schedule_count);
    $stmt->fetch();
    $stmt->close();

    // Fetch departure count (COUNT of rows)
    $stmt = $db->prepare("SELECT COUNT(*) FROM schedule_master WHERE depot_id = ? and status ='1'");
    $stmt->bind_param("i", $depot_id);
    $stmt->execute();
    $stmt->bind_result($departure_count);
    $stmt->fetch();
    $stmt->close();
} elseif ($type === "Division") {
    $stmt = $db->prepare("SELECT division_id FROM location WHERE kmpl_division = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($division_id);
    $stmt->fetch();
    $stmt->close();

    if (!$division_id) {
        echo json_encode(["status" => "error", "message" => "Division not found"]);
        exit;
    }

    // Fetch schedule count (SUM of sch_count)
    $stmt = $db->prepare("SELECT SUM(sch_count) FROM schedule_master WHERE division_id = ? and status ='1'");
    $stmt->bind_param("i", $division_id);
    $stmt->execute();
    $stmt->bind_result($schedule_count);
    $stmt->fetch();
    $stmt->close();

    // Fetch departure count (COUNT of rows)
    $stmt = $db->prepare("SELECT COUNT(*) FROM schedule_master WHERE division_id = ? and status ='1'");
    $stmt->bind_param("i", $division_id);
    $stmt->execute();
    $stmt->bind_result($departure_count);
    $stmt->fetch();
    $stmt->close();
}
if ($type === "Depot") {
    // Get depot_id from location
    $depotQuery = "SELECT depot_id FROM location WHERE depot = ?";
    $stmt = $db->prepare($depotQuery);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $depot_id = $row['depot_id'];

    // Fetch schedule details
    $query = "SELECT 
    sm.sch_dep_time, 
    sm.sch_key_no, 
    sm.sch_abbr, 
    st.name,
    sm.division_id, 
    sm.depot_id,
    COALESCE(SUM(CASE WHEN svo.dep_time_diff > 30 THEN 1 ELSE 0 END), 0) AS late,
    COALESCE(SUM(CASE WHEN svo.dep_time_diff <= 30 THEN 1 ELSE 0 END), 0) AS on_time,
    COALESCE(COUNT(svo.sch_no), 0) AS total_schedules
FROM schedule_master sm
LEFT JOIN sch_veh_out svo 
    ON sm.sch_key_no = svo.sch_no 
    AND sm.depot_id = svo.depot_id 
    AND svo.departed_date BETWEEN ? AND ?  -- Moved inside JOIN
LEFT JOIN service_class st 
    ON sm.service_type_id = st.id
WHERE sm.depot_id = ? 
AND sm.status = '1'
GROUP BY sm.sch_key_no
ORDER BY sm.sch_dep_time;";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssi", $last_30_days, $today,  $depot_id);
    $stmt->execute();
} elseif ($type === "Division") {
    // Get division_id from location
    $divisionQuery = "SELECT division_id FROM location WHERE kmpl_division = ?";
    $stmt = $db->prepare($divisionQuery);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $division_id = $row['division_id'];

    // Fetch schedules for all depots under this division
    $query = "SELECT 
    sm.sch_dep_time, 
    sm.sch_key_no, 
    sm.sch_abbr, 
    st.name,
    sm.division_id, 
    sm.depot_id,
    COALESCE(SUM(CASE WHEN svo.dep_time_diff > 30 THEN 1 ELSE 0 END), 0) AS late,
    COALESCE(SUM(CASE WHEN svo.dep_time_diff <= 30 THEN 1 ELSE 0 END), 0) AS on_time,
    COALESCE(COUNT(svo.sch_no), 0) AS total_schedules
FROM schedule_master sm
LEFT JOIN sch_veh_out svo 
    ON sm.sch_key_no = svo.sch_no 
    AND sm.depot_id = svo.depot_id 
    AND svo.departed_date BETWEEN ? AND ?
LEFT JOIN service_class st 
    ON sm.service_type_id = st.id
WHERE sm.division_id = ? 
AND sm.status = '1'
GROUP BY sm.sch_key_no, sm.depot_id  -- Now grouping by sch_key_no AND depot_id
ORDER BY sm.sch_dep_time;";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssi", $last_30_days, $today, $division_id);
    $stmt->execute();
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $html .= "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; border-collapse: collapse;'>
                <tr>
                    <th rowspan='2'>Sl. No</th>
                    <th rowspan='2'>Sch Key No</th>
                    <th rowspan='2'>Sch Dep Time</th>
                    <th rowspan='2'>discription</th>
                    <th rowspan='2'>Service Type</th>
                    <th colspan='2'>last 30 days</th>
                    <th rowspan='2' style='display:none'> division</th>
                    <th rowspan='2' style='display:none'>depot</th>
                </tr>
                <tr>
                <th>Late</th>
                    <th>On Time</th>
                    </tr>";
    $serial_number = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= "<tr>
                    <td>{$serial_number}</td>
                        <td onclick='fetchScheduleDetails(\"{$row['sch_key_no']}\", \"{$row['division_id']}\", \"{$row['depot_id']}\", \"{$row['sch_abbr']}\", \"{$row['name']}\", \"{$row['sch_dep_time']}\")'>
                        {$row['sch_key_no']}
                    </td>
                    <td>{$row['sch_dep_time']}</td>
                    <td>{$row['sch_abbr']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['late']}</td>
                    <td>{$row['on_time']}</td>
                    <td style='display:none'>{$row['division_id']}</td>
                    <td style='display:none'>{$row['depot_id']}</td>
                </tr>";
        $serial_number++;
    }
    $html .= "</table>";

    echo json_encode([
        "status" => "success",
        "html" => $html,
        "schedule_count" => $schedule_count ?: 0,
        "departure_count" => $departure_count ?: 0
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "No records found"]);
}
