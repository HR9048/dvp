<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is experied please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}
$division_id = $_SESSION['DIVISION_ID'];
$depot_id = $_SESSION['DEPOT_ID'];

header('Content-Type: application/json');

try {
    // Fetch data from VEH_SCH_OUT table
    $sql = "SELECT driver_1_pf, driver_2_pf, conductor_pf_no FROM sch_veh_out WHERE schedule_status in (1,2) AND division_id = $division_id and depot_id= $depot_id";
    $result = $db->query($sql);

    if (!$result) {
        throw new Exception("Database Error [{$db->errno}] {$db->error}");
    }

    $vehSchOutData = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehSchOutData[] = $row;
        }
    }

    $db->close();

    // Return data as JSON
    echo json_encode($vehSchOutData);

} catch (Exception $e) {
    // Send error response
    echo json_encode(array('error' => $e->getMessage()));
}
?>