<?php
include '../includes/connection.php';
include '../pages/session.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is experied please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}
if (isset($_POST['schedule_no'])) {
    $schedule_no = mysqli_real_escape_string($db, $_POST['schedule_no']);
    $query = "SELECT * FROM schedule_key_master WHERE sch_key_no = '$schedule_no'";
    $result = mysqli_query($db, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode($row);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
