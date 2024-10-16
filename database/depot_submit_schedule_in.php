<?php
include '../includes/connection.php';
include '../pages/session.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['arr_time'])) {
    $id = $_POST['id'];
    $arrTime = $_POST['arr_time']; // Actual arrival time from POST data
    $schArrTime = $_POST['sch_arr_time'];
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    $scheduleNo = $_POST['sch_no_in'];
    $division_id1 = $_SESSION['DIVISION_ID'];
    $depot_id1 = $_SESSION['DEPOT_ID'];
    $status = '2';
    $today = date('Y-m-d');
    $checkQuery = "
SELECT COUNT(*) as count
FROM sch_veh_out
WHERE sch_no = '$scheduleNo'
AND division_id = '$division_id1'
AND depot_id = '$depot_id1'
AND arr_date = '$today'
AND schedule_status = '2'
";
    $checkResult = mysqli_query($db, $checkQuery);
    $checkData = mysqli_fetch_assoc($checkResult);

    if ($checkData['count'] > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'The schedule already Arrived.'
        ]);
        exit;
    }
    // Calculate the time difference
    $arrTimeObj = new DateTime($arrTime);
    $schArrTimeObj = new DateTime($schArrTime);
    $arrTimeDiffInMinutes = ($arrTimeObj->getTimestamp() - $schArrTimeObj->getTimestamp()) / 60;

    // Determine reason type
    $reasonForLateArr = null;
    $reasonForEarlyArr = null;
    if ($arrTimeDiffInMinutes > 60) {
        $reasonForEarlyArr = $reason;
    } elseif ($arrTimeDiffInMinutes < -30) {
        $reasonForLateArr = $reason;
    }

    date_default_timezone_set('Asia/Kolkata');
    $currentTime = date("Y-m-d H:i:s");
    $currentDate = date("Y-m-d");

    // Update the sch_veh_out table
    $updateQuery = "UPDATE sch_veh_out 
                    SET arr_time = ?, arr_date=?, act_arr_time = ?, arr_time_diff = ?, reason_for_late_arr = ?, reason_for_early_arr = ?, schedule_status = ?
                    WHERE id = ? AND sch_no = ? AND division_id = ? AND depot_id = ? AND schedule_status = '1'";

    $stmt = $db->prepare($updateQuery);

    if (!$stmt) {
        die("Error preparing statement: " . $db->error);
    }

    $stmt->bind_param("sssisssssss", $arrTime, $currentDate, $currentTime, $arrTimeDiffInMinutes, $reasonForLateArr, $reasonForEarlyArr, $status, $id, $scheduleNo, $division_id1, $depot_id1);

    if ($stmt->execute()) {
        // Return JSON success message
        echo json_encode([
            'status' => 'success',
            'message' => 'The schedule has been successfully Arrived.'
        ]);
    } else {
        // Return JSON error message
        echo json_encode([
            'status' => 'error',
            'message' => 'Error occurred: ' . $stmt->error
        ]);
    }

    $stmt->close();
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: ../pages/login.php");
    exit;
}
?>