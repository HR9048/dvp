<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/connection.php'; // Include your database connection file
include '../pages/session.php';
confirm_logged_in();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check for missing POST variables
    $missingFields = [];

    if (empty($_POST['sch_out_id'])) {
        $missingFields[] = 'please refresh the page and try again';
    }
    if (empty($_POST['ramp_defect'])) {
        $missingFields[] = 'ramp defect';
    }
    if ($_POST['ramp_defect'] != '1' && empty($_POST['ramp_remark'])) {
        $missingFields[] = 'ramp remark (required for ramp_defect other than none)';
    }

    if (!empty($missingFields)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing data: ' . implode(', ', $missingFields)]);
        exit;
    }

    // Assign the POST variables to local variables
    $out_id = $_POST['sch_out_id'];


    // Prepare the SQL query
    $sql12 = "SELECT schedule_status FROM sch_veh_out WHERE id = ?";

    // Prepare the statement
    $stmt12 = $db->prepare($sql12);

    // Bind the parameter
    $stmt12->bind_param("i", $out_id);

    // Execute the statement
    $stmt12->execute();

    // Bind the result to the $schedule_status variable
    $stmt12->bind_result($schedule_status_out);

    // Fetch the result
    $stmt12->fetch();

    // Close the statement
    $stmt12->close();


    $ramp_defect = $_POST['ramp_defect'];
    $ramp_remark = $_POST['ramp_remark'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    if ($schedule_status_out == '3') {
        $bunk_status = '3';
    } elseif ($schedule_status_out == '7') {
        $bunk_status = '7';
    }
    // Prepare and execute the update query for sch_veh_out
    if ($schedule_status_out == '3') {
        $schedule_status = ($ramp_defect == '1') ? 0 : 4;
    } elseif ($schedule_status_out == '7') {
        $schedule_status = ($ramp_defect == '1') ? 9 : 8;
    }
    $sql_update_out = "UPDATE sch_veh_out 
                               SET schedule_status = ? , ramp_defect = ?, ramp_remark = ?
                               WHERE id = ? and schedule_status=? and depot_id=? and division_id=?";

    if ($stmt_out = $db->prepare($sql_update_out)) {
        $stmt_out->bind_param('issiiii', $schedule_status, $ramp_defect, $ramp_remark, $out_id, $bunk_status, $depot_id, $division_id);

        if ($stmt_out->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Route data already updated please refresh the page']);
        }

        $stmt_out->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the sch_veh_out statement.']);
    }


    $db->close();
} else {
    header('Location: ../pages/login.php');
    exit;
}
