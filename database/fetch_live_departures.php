<?php
include '../includes/connection.php';
date_default_timezone_set('Asia/Kolkata'); // Set the timezone

// Get the selected date from the AJAX request
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$current_date = date('Y-m-d'); // Today's date
$current_date1 = date('d-m-Y', strtotime($selected_date)); // Format for display

// Determine the time condition based on the selected date
if ($selected_date == $current_date) {
    $current_time = date('H:i:s'); // Use current time for today's data
    $current_time1 = date('H:i');
} else {
    $current_time = "23:59:59"; // Set to 11:59 PM for past dates
    $current_time1 = "23:59";
}

// Run each query separately and fetch results
$total_schedules = [];
$actual_schedules = [];
$late_departures = [];

// Query 1: Total Schedules
$query1 = "SELECT l.kmpl_division, l.depot, COUNT(sm.sch_key_no) AS total_schedules 
           FROM location l
           LEFT JOIN schedule_master sm 
           ON l.division_id = sm.division_id 
           AND l.depot_id = sm.depot_id 
           AND sm.sch_dep_time <= '$current_time' 
           WHERE l.division_id NOT IN ('0', '10') 
           AND l.depot != 'DIVISION' 
           AND sm.status ='1'
           GROUP BY l.division_id, l.depot_id;";
$result1 = mysqli_query($db, $query1);
if (!$result1) {
    die("Query 1 failed: " . mysqli_error($db));
}
while ($row = mysqli_fetch_assoc($result1)) {
    $total_schedules[$row['kmpl_division']][$row['depot']] = $row['total_schedules'];
}

// Query 2: Actual Schedules
$query2 = "SELECT 
    l.kmpl_division, 
    l.depot, 
    COALESCE(COUNT(svo.sch_no), 0) AS actual_schedules 
FROM location l
LEFT JOIN sch_veh_out svo 
    ON l.division_id = svo.division_id 
    AND l.depot_id = svo.depot_id 
    AND DATE(svo.departed_date) = '$selected_date'
LEFT JOIN schedule_master sm
    ON svo.sch_no = sm.sch_key_no
    AND svo.division_id = sm.division_id
    AND svo.depot_id = sm.depot_id
WHERE 
    l.division_id NOT IN ('0', '10') 
    AND l.depot != 'DIVISION'
    AND sm.sch_dep_time <= '$current_time'
GROUP BY 
    l.division_id, l.depot_id;";
$result2 = mysqli_query($db, $query2);
if (!$result2) {
    die("Query 2 failed: " . mysqli_error($db));
}
while ($row = mysqli_fetch_assoc($result2)) {
    $actual_schedules[$row['kmpl_division']][$row['depot']] = $row['actual_schedules'];
}

// Query 2.1: Actual Schedules
$query21 = "SELECT 
    l.kmpl_division, 
    l.depot, 
    COALESCE(COUNT(svo.sch_no), 0) AS actual_schedules 
FROM location l
LEFT JOIN sch_veh_out svo 
    ON l.division_id = svo.division_id 
    AND l.depot_id = svo.depot_id 
    AND DATE(svo.departed_date) = '$selected_date'
LEFT JOIN schedule_master sm
    ON svo.sch_no = sm.sch_key_no
    AND svo.division_id = sm.division_id
    AND svo.depot_id = sm.depot_id
    AND sm.sch_dep_time <= '$current_time'
WHERE 
    l.division_id NOT IN ('0', '10') 
    AND l.depot != 'DIVISION'
    AND sm.sch_dep_time <= '$current_time'
GROUP BY 
    l.division_id, l.depot_id;";
$result21 = mysqli_query($db, $query21);
if (!$result21) {
    die("Query 2 failed: " . mysqli_error($db));
}
while ($row = mysqli_fetch_assoc($result21)) {
    $actual_schedules1[$row['kmpl_division']][$row['depot']] = $row['actual_schedules'];
}

// Query 3: Late Departures
$query3 = "SELECT l.kmpl_division, l.depot, COALESCE(COUNT(DISTINCT svo.sch_no), 0) AS late_departures 
           FROM location l
           LEFT JOIN sch_veh_out svo 
           ON l.division_id = svo.division_id 
           AND l.depot_id = svo.depot_id 
           AND DATE(svo.departed_date) = '$selected_date' 
           AND svo.dep_time_diff > 30
           WHERE l.division_id NOT IN ('0', '10') 
           AND l.depot != 'DIVISION'
           GROUP BY l.division_id, l.depot_id;";
$result3 = mysqli_query($db, $query3);
if (!$result3) {
    die("Query 3 failed: " . mysqli_error($db));
}
while ($row = mysqli_fetch_assoc($result3)) {
    $late_departures[$row['kmpl_division']][$row['depot']] = $row['late_departures'];
}

// Merge results into a final array
$report = [];
foreach ($total_schedules as $division => $depots) {
    foreach ($depots as $depot => $total) {
        $actual = isset($actual_schedules[$division][$depot]) ? $actual_schedules[$division][$depot] : 0;
        $actual1 = isset($actual_schedules1[$division][$depot]) ? $actual_schedules1[$division][$depot] : 0;
        $late = isset($late_departures[$division][$depot]) ? $late_departures[$division][$depot] : 0;
        $difference = $total - $actual1;

        $report[] = [
            'division' => $division,
            'depot' => $depot,
            'total_schedules' => $total,
            'actual_schedules' => $actual,
            'difference' => $difference,
            'late_departures' => $late
        ];
    }
}

// Convert to JSON for AJAX response
echo json_encode(['time' => $current_date1, 'time1' => $current_time1, 'data' => $report]);
?>
