<?php
include '../includes/connection.php';

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
