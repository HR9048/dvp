<?php
include '../includes/connection.php';
date_default_timezone_set('Asia/Kolkata'); // Set the timezone

$id = mysqli_real_escape_string($db, $_POST['id']); // Prevent SQL injection
$type = mysqli_real_escape_string($db, $_POST['type']);
$location = mysqli_real_escape_string($db, $_POST['location']);

$today = date('Y-m-d'); // Get today's date
$curr_time = date('H:i:s');

$data = [];

// Fetch depot_id or division_id
if ($location == "Division") {
    $queryDiv = "SELECT division_id FROM location WHERE kmpl_division = '$id' LIMIT 1";
    $resultDiv = mysqli_query($db, $queryDiv);
    $rowDiv = mysqli_fetch_assoc($resultDiv);
    
    if (!$rowDiv) {
        echo json_encode([]); // Return empty if no matching division
        exit;
    }
    
    $division_id = $rowDiv['division_id'];
    $condition = "division_id = '$division_id'";
    $condition2 = "division_id = '$division_id'";
    $condition1 = "sm.division_id = '$division_id'";

} else {
    $queryDepot = "SELECT depot_id, division_id FROM location WHERE depot = '$id' LIMIT 1";
    $resultDepot = mysqli_query($db, $queryDepot);
    $rowDepot = mysqli_fetch_assoc($resultDepot);

    if (!$rowDepot) {
        echo json_encode([]); // Return empty if no matching depot
        exit;
    }

    $depot_id = $rowDepot['depot_id'];
    $division_id = $rowDepot['division_id'];
    $condition = "depot_id = '$depot_id'";
    $condition2 = "depot_id = '$depot_id' and division_id = '$division_id'";
    $condition1 = "sm.depot_id = '$depot_id'";
}

if ($type === 'difference') {
    // Step 1: Fetch all operated schedule numbers with depot_id from sch_veh_out for today
    $operatedSchedules = [];
    $queryOperated = "SELECT sch_no, depot_id FROM sch_veh_out WHERE departed_date = '$today' AND ($condition2)";
    $resultOperated = mysqli_query($db, $queryOperated);
    
    while ($row = mysqli_fetch_assoc($resultOperated)) {
        $operatedSchedules[] = $row['sch_no'] . '-' . $row['depot_id']; // Store as combined key
    }

    // Step 2: Fetch all schedule details from schedule_master and filter out operated schedules
    $querySchedules = "SELECT 
    sm.sch_key_no, 
    sm.sch_abbr, 
    s.name,  -- Fetching the service name instead of ID
    sm.sch_dep_time, 
    sm.depot_id
FROM schedule_master sm
LEFT JOIN service_class s ON sm.service_class_id = s.id  -- Joining with the service table
WHERE 
    sm.status = 1
    AND $condition2
    AND sm.sch_dep_time <= '$curr_time'
ORDER BY 
    sm.sch_dep_time ASC;
";

    $resultSchedules = mysqli_query($db, $querySchedules);

    while ($row = mysqli_fetch_assoc($resultSchedules)) {
        $scheduleKey = $row['sch_key_no'] . '-' . $row['depot_id']; // Combine sch_no and depot_id
        if (!in_array($scheduleKey, $operatedSchedules)) {
            $data[] = $row; // Add only schedules not found in sch_veh_out
        }
    }
}
 elseif ($type === 'late') {
    // Fetch schedules with dep_time_diff > 30
    $queryLate = "SELECT 
    sm.sch_key_no, 
    sm.sch_abbr, 
    s.name,  -- Fetching the service name instead of ID
    sm.sch_dep_time,
    svo.dep_time AS act_dep_time, 
    svo.dep_time_diff AS late_by,
    sm.division_id,
    sm.depot_id
FROM schedule_master sm
JOIN sch_veh_out svo 
    ON sm.sch_key_no = svo.sch_no 
    AND sm.depot_id = svo.depot_id  -- Ensuring same depot
JOIN service_class s 
    ON sm.service_class_id = s.id  -- Joining with service table
WHERE 
    svo.departed_date = '$today' 
    AND svo.dep_time_diff > 30  -- Late departures (more than 30 minutes)
    AND $condition1
ORDER BY 
    sm.sch_dep_time ASC;
";

    $resultLate = mysqli_query($db, $queryLate);

    while ($row = mysqli_fetch_assoc($resultLate)) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>
