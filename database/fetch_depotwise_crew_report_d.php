<?php
include '../includes/connection.php';
include '../pages/session.php';
$data = json_decode(file_get_contents('php://input'), true);
$date = $data['date'];
$division_id = $data['division']; // Make sure session variables are in capital letters
$depot_id = $data['depot'];
if (!$date) {
    echo json_encode(['html' => 'Invalid date selected.']);
    exit;
}

// Get division and depot from session


// Format the date to check against the `inact_from` and `inact_to` fields
$selected_date = date('Y-m-d', strtotime($date)); // 'YYYY-MM-DD' format

// Step 1: Query to fetch schedules from `schedule_master` where `sch_actinact` is not inactive on the selected date
$query = "SELECT 
    sm.sch_key_no AS schedule_key_no,
    sm.sch_abbr AS description,
    sm.sch_dep_time,
    sm.sch_arr_time
FROM 
    schedule_master sm
LEFT JOIN 
    sch_actinact sai ON sm.sch_key_no = sai.sch_key_no 
    AND sai.division_id = sm.division_id 
    AND sai.depot_id = sm.depot_id
WHERE 
    sm.division_id = ? 
    AND sm.depot_id = ? 
    AND (
        (sai.inact_from IS NULL 
        OR NOT (
            (DATE(sai.inact_from) < ? OR 
            (DATE(sai.inact_from) = ? AND TIME(sai.inact_from) >= '11:00:00'))
        AND
        (DATE(sai.inact_to) > ? OR 
         (DATE(sai.inact_to) = ? AND TIME(sai.inact_to) <= '16:00:00'))
        ))
    )
ORDER BY 
    sm.sch_key_no";

$stmt = $db->prepare($query);
$stmt->bind_param('iissss', $division_id, $depot_id, $selected_date, $selected_date, $selected_date, $selected_date);
$stmt->execute();
$result = $stmt->get_result();

// Initialize an array to store schedule keys (IDs) for filtering later
$schedule_keys = [];
while ($row = $result->fetch_assoc()) {
    $schedule_keys[] = $row['schedule_key_no'];
}

// Step 2: Query to fetch schedule details from `schedule_master` and `sch_veh_out`
// If no schedules found, show all schedules (departure not yet happened)
if (empty($schedule_keys)) {
    // Fetch all schedules if departure not happened
    $schedule_keys_str = "SELECT sm.sch_key_no FROM schedule_master sm WHERE sm.division_id = ? AND sm.depot_id = ?";
} else {
    $schedule_keys_str = implode("','", $schedule_keys);
}


// Query to fetch bus details and check for cancellations
$bus_query = "SELECT 
        bfd.sch_key_no, 
        IF(sc.sch_key_no IS NOT NULL, 'Canceled', GROUP_CONCAT(bfd.bus_number)) AS buses
    FROM 
        bus_fix_data bfd
    LEFT JOIN 
        schedule_cancel sc
        ON bfd.sch_key_no = sc.sch_key_no 
        AND bfd.division_id = sc.division_id 
        AND bfd.depot_id = sc.depot_id 
        AND sc.cancel_date = ?
    WHERE 
        bfd.sch_key_no IN (" . implode(',', array_fill(0, count($schedule_keys), '?')) . ") 
        AND bfd.division_id = ? 
        AND bfd.depot_id = ? 
        AND ('$selected_date' BETWEEN DATE(bfd.from_date) AND IFNULL(DATE(bfd.to_date), '$selected_date'))
    GROUP BY bfd.sch_key_no";

// Prepare the query
$stmt = $db->prepare($bus_query);

// Bind parameters (selected_date + schedule_keys + division_id + depot_id)
$params = array_merge([$selected_date], $schedule_keys, [$division_id, $depot_id]);
$stmt->bind_param(
    str_repeat('s', 1) . str_repeat('i', count($schedule_keys)) . 'ii',
    ...$params
);

// Execute the query and fetch results
$stmt->execute();
$bus_result = $stmt->get_result();
$buses = [];

// Map the results
while ($row = $bus_result->fetch_assoc()) {
    $buses[$row['sch_key_no']] = $row['buses']; // "Canceled" or bus numbers
}




$location = "SELECT division, depot FROM location WHERE division_id = ? AND depot_id = ?";
$ssstt = $db->prepare($location);
$ssstt->bind_param('ii', $division_id, $depot_id);
$ssstt->execute();
$locat = $ssstt->get_result();

if ($locat->num_rows > 0) {
    $row = $locat->fetch_assoc();
    $division_name = $row['division'];
    $depot_name = $row['depot'];
} 
$crew_query = "
    SELECT 
        cfd.sch_key_no,
        CASE 
            WHEN sc.sch_key_no IS NOT NULL THEN 'Canceled'
            ELSE GROUP_CONCAT(CASE 
                WHEN cfd.designation IN ('driver', 'Driver-cum-Conductor') THEN cfd.crew_token 
                ELSE NULL 
            END)
        END AS driver_tokens,
        CASE 
            WHEN sc.sch_key_no IS NOT NULL THEN 'Canceled'
            ELSE GROUP_CONCAT(CASE 
                WHEN cfd.designation = 'conductor' THEN cfd.crew_token 
                ELSE NULL 
            END)
        END AS conductor_tokens
    FROM 
        crew_fix_data cfd
    LEFT JOIN 
        schedule_cancel sc
        ON cfd.sch_key_no = sc.sch_key_no
        AND cfd.division_id = sc.division_id
        AND cfd.depot_id = sc.depot_id
        AND sc.cancel_date = ?
    WHERE 
        cfd.sch_key_no IN ('$schedule_keys_str') 
        AND cfd.division_id = ? 
        AND cfd.depot_id = ? 
        AND ('$selected_date' BETWEEN DATE(cfd.from_date) AND IFNULL(DATE(cfd.to_date), '$selected_date'))
    GROUP BY cfd.sch_key_no";

// Execute the query to fetch the crew token data
$stmt = $db->prepare($crew_query);

// Bind parameters (cancel_date, division_id, depot_id)
$stmt->bind_param('sii', $selected_date, $division_id, $depot_id);
$stmt->execute();

$crew_result = $stmt->get_result();
$crews = [];
while ($row = $crew_result->fetch_assoc()) {
    $crews[$row['sch_key_no']] = [
        'driver_tokens' => $row['driver_tokens'], // Contains driver token numbers or "Canceled"
        'conductor_tokens' => $row['conductor_tokens'] // Contains conductor token numbers or "Canceled"
    ];
}


// Query to filter schedules and get vehicle/crew details from `schedule_master` and `sch_veh_out`
$query = "SELECT 
    sm.sch_key_no AS schedule_key_no,
    sm.sch_abbr AS description,
    sm.sch_dep_time,
    CASE 
        WHEN scc.sch_key_no IS NOT NULL THEN 'Canceled'
        ELSE svo.dep_time 
    END AS dep_time,  -- Show 'Canceled' if cancellation found
    sm.sch_arr_time,
    CASE 
        WHEN scc.sch_key_no IS NOT NULL THEN 'Canceled'
        ELSE svo.arr_time 
    END AS arr_time,  -- Show 'Canceled' if cancellation found
    svo.dep_time_diff,
    svo.arr_time_diff,
    sc.name AS service_class,  -- Fetching service class name
    IFNULL(svo.vehicle_no, 'NA') AS buses,
    IFNULL(svo.driver_token_no_1, 'NA') AS driver_1,
    IFNULL(svo.driver_token_no_2, 'NA') AS driver_2,
    IFNULL(svo.conductor_token_no, 'NA') AS conductor,
    IFNULL(svo.bus_allotted_status, 'SNO') AS bus_status,
    IFNULL(svo.driver_1_allotted_status, 'SNO') AS driver_1_status,
    IFNULL(svo.driver_2_allotted_status, 'SNO') AS driver_2_status,
    IFNULL(svo.conductor_alloted_status, 'SNO') AS conductor_status
FROM 
    schedule_master sm
LEFT JOIN 
    sch_veh_out svo 
    ON svo.sch_no = sm.sch_key_no 
    AND svo.division_id = ? 
    AND svo.depot_id = ? 
    AND svo.departed_date = ?
LEFT JOIN 
    service_class sc 
    ON sm.service_class_id = sc.id  -- Joining with service_class table
LEFT JOIN 
    schedule_cancel scc 
    ON sm.sch_key_no = scc.sch_key_no 
    AND scc.division_id = sm.division_id 
    AND scc.depot_id = sm.depot_id
    AND scc.cancel_date = ? -- Joining with schedule_cancel for cancellations
WHERE 
    sm.division_id = ? 
    AND sm.depot_id = ? 
    AND sm.sch_key_no IN ('$schedule_keys_str')
ORDER BY 
    sm.sch_dep_time";

// Prepare and bind parameters
$stmt = $db->prepare($query);
$stmt->bind_param('ssssss', $division_id, $depot_id, $selected_date, $selected_date, $division_id, $depot_id);
$stmt->execute();
$result = $stmt->get_result();


$html = '<table border="1" style="width: 100%; border-collapse: collapse;">';
$html .= '<br><br>';
$html .= '<h2 style="text-align: center;">'. $division_name .' '. $depot_name .' Schedule Operation on Date: ' . date('d-m-Y', strtotime($date)) . '</h2><br>';
$html .= '<p style="color: red;">Note * : (SNO = Schedule not Operated), (SNA = Schedule not Arrived), (NA = Not Alloted), (N/A = Not Applicable)</p>';
$html .= '<tr><th>Sl No</th><th>Schedule Key No</th><th>Description</th><th>Sch Dep Time</th><th>Dep Time</th><th>Sch Arr Time</th><th>Arr Time</th><th>Service Class</th><th>Buses</th><th>Drivers</th><th>Conductors</th><th>Bus Status</th><th>Driver 1 Status</th><th>Driver 2 Status</th><th>Conductor Status</th></tr>';

$sl_no = 1;
while ($row = $result->fetch_assoc()) {
    // Handle Arrive Time and Depature Time coloring based on diff
    $arr_time = $row['arr_time'];
    $arr_time_diff = $row['arr_time_diff'];
    $dep_time = $row['dep_time'];
    $dep_time_diff = $row['dep_time_diff'];

    // Format Arr Time based on arr_time_diff
    if ($dep_time == null && $arr_time == null) {
        $arr_time_display = 'SNO';
    } elseif ($dep_time != null && $arr_time === null) {
        $arr_time_display = 'SNA';
    } else {
        if ($arr_time == 'Canceled'){
            $arr_time_display ='<span style="color: rgb(0, 0, 0);">' . $arr_time . '</span>';
        } elseif($arr_time_diff > 30) {
            $arr_time_display = '<span style="color: green;">' . $arr_time . '</span>';
        } elseif ($arr_time_diff < -30) {
            $arr_time_display = '<span style="color: red;">' . $arr_time . '</span>';
        } else {
            $arr_time_display = $arr_time;
        }
    }

    // Format Dep Time based on dep_time_diff
    if ($dep_time == null) {
        $dep_time_display = 'SNO';
    } elseif($dep_time == 'Canceled'){
        $dep_time_display = '<span style="color:rgb(0, 0, 0);">' . $dep_time . '</span>';
    } elseif ($dep_time_diff > 60) {
        $dep_time_display = '<span style="color: red;">' . $dep_time . '</span>';
    } elseif ($dep_time_diff < -30) {
        $dep_time_display = '<span style="color: green;">' . $dep_time . '</span>';
    } else {
        $dep_time_display = $dep_time;
    }

    // Handle Driver/Conductor Status and Formatting
    $bus_status_display = ($row['bus_status'] == 0) ? '<i class="fa-solid fa-square-check fa-xl" style="color: #198104;"></i>' : ($row['bus_status'] == 1 ? $row['buses'] : 'N/A');
    $driver_1_status_display = ($row['driver_1_status'] == 0) ? '<i class="fa-solid fa-square-check fa-xl" style="color: #198104;"></i>' : ($row['driver_1_status'] == 1 ? $row['driver_1'] : 'N/A');
    $driver_2_status_display = ($row['driver_2_status'] == 0) ? '<i class="fa-solid fa-square-check fa-xl" style="color: #198104;"></i>' : ($row['driver_2_status'] == 1 ? $row['driver_2'] : 'N/A');
    $conductor_status_display = ($row['conductor_status'] == 0) ? '<i class="fa-solid fa-square-check fa-xl" style="color: #198104;"></i>' : ($row['conductor_status'] == 1 ? $row['conductor'] : 'N/A');

    // Start row display
    $html .= '<tr>';
    $html .= '<td>' . $sl_no++ . '</td>';
    $html .= '<td>' . $row['schedule_key_no'] . '</td>';
    $html .= '<td>' . $row['description'] . '</td>';
    $html .= '<td>' . $row['sch_dep_time'] . '</td>';
    $html .= '<td>' . $dep_time_display . '</td>';
    $html .= '<td>' . $row['sch_arr_time'] . '</td>';
    $html .= '<td>' . $arr_time_display . '</td>';
    $html .= '<td>' . $row['service_class'] . '</td>';
    // Handle Bus details (show in same column with line breaks)
    $buses_details = isset($buses[$row['schedule_key_no']]) ? $buses[$row['schedule_key_no']] : 'NA';
    $bus_array = explode(',', $buses_details);
    $buses_display = implode('<br>', $bus_array);  // Join with line breaks
    $html .= '<td>' . $buses_display . '</td>';

    // Handle Driver details (show in same column with line breaks)
    $driver_tokens = isset($crews[$row['schedule_key_no']]['driver_tokens']) ? $crews[$row['schedule_key_no']]['driver_tokens'] : 'NA';
    $driver_array = explode(',', $driver_tokens);
    $drivers_display = implode('<br>', $driver_array);  // Join with line breaks
    $html .= '<td>' . $drivers_display . '</td>';

    // Handle Conductor details (show in same column with line breaks)
    $conductor_tokens = isset($crews[$row['schedule_key_no']]['conductor_tokens']) ? $crews[$row['schedule_key_no']]['conductor_tokens'] : 'NA';
    $conductor_array = explode(',', $conductor_tokens);
    $conductors_display = implode('<br>', $conductor_array);  // Join with line breaks
    $html .= '<td>' . $conductors_display . '</td>';
    $html .= '<td>' . displayStatus($row['bus_status'], $row['buses']) . '</td>';
    $html .= '<td>' . displayStatus($row['driver_1_status'], $row['driver_1']) . '</td>';
    $html .= '<td>' . displayStatus($row['driver_2_status'], $row['driver_2']) . '</td>';
    $html .= '<td>' . displayStatus($row['conductor_status'], $row['conductor']) . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';
function displayStatus($status, $data) {
    switch ($status) {
        case '0':
            return '<i class="fa-solid fa-square-check fa-xl" style="color: #198104;"></i>'; // Green checkmark
        case '1':
            return htmlspecialchars($data); // Return the actual data (driver, conductor, or vehicle number)
        default:
            return '<span>N/A</span>'; // Not available
    }
}
// Return the generated table
echo json_encode(['html' => $html]);
?>
