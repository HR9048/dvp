<?php
include '../includes/connection.php';
date_default_timezone_set('Asia/Kolkata'); // Set the timezone

$sch_no = $_POST['sch_no'];
$division_id = $_POST['division_id'];
$depot_id = $_POST['depot_id'];
$today = date('Y-m-d');
$last_30_days = date('Y-m-d', strtotime('-30 days'));

$query = "
    SELECT 
        departed_date AS date, 
        dep_time, 
        dep_time_diff AS late_by, 
        driver_1_allotted_status AS driver_fixed, 
        bus_allotted_status AS vehicle_fixed
    FROM sch_veh_out 
    WHERE sch_no = '$sch_no' 
    AND division_id = '$division_id' 
    AND depot_id = '$depot_id' 
    AND departed_date BETWEEN '$last_30_days' AND '$today'
    ORDER BY departed_date DESC
";

$result = mysqli_query($db, $query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(['success' => count($data) > 0, 'data' => $data]);
?>
