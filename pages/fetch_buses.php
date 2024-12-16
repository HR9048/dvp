<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is experied please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}
$depot_id = $_SESSION['DEPOT_ID'];
$division_id = $_SESSION['DIVISION_ID'];

$query = "SELECT br.bus_number
FROM bus_registration br
LEFT JOIN sch_veh_out vso ON br.bus_number = vso.vehicle_no AND vso.schedule_status IN (1)
LEFT JOIN off_road_data or_status ON br.bus_number = or_status.bus_number AND or_status.status = 'off_road'
WHERE br.depot_name = $depot_id
  AND br.division_name = $division_id
  AND vso.vehicle_no IS NULL
  AND or_status.bus_number IS NULL";
$result = mysqli_query($db, $query) or die(mysqli_error($db));

$buses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $buses[] = $row['bus_number'];
}

echo json_encode($buses);

//LEFT JOIN sch_veh_out vso ON br.bus_number = vso.vehicle_no AND vso.schedule_status IN (1,2,3,4,6,7,8) -->

?>
