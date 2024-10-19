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
    if (empty($_POST['sch_in_id'])) {
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
    $in_id = $_POST['sch_in_id'];
    $ramp_defect = $_POST['ramp_defect'];
    $ramp_remark = $_POST['ramp_remark'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    $bunk_status= '3';
    // Prepare and execute the update query for sch_veh_in
    $sql_update_in = "UPDATE sch_veh_in 
                      SET ramp_defect = ?, 
                          ramp_remark = ? 
                      WHERE id = ? AND depot_id = ? AND division_id = ?";

    if ($stmt_in = $db->prepare($sql_update_in)) {
        $stmt_in->bind_param('ssiii', $ramp_defect, $ramp_remark, $in_id, $depot_id, $division_id);

        if ($stmt_in->execute()) {
            // Prepare and execute the update query for sch_veh_out
            $schedule_status = ($ramp_defect == '1') ? 0 : 4;

            $sql_update_out = "UPDATE sch_veh_out 
                               SET schedule_status = ? 
                               WHERE id = ? and schedule_status=? and depot_id=? and division_id=?";

            if ($stmt_out = $db->prepare($sql_update_out)) {
                $stmt_out->bind_param('iiiii', $schedule_status, $out_id, $bunk_status, $depot_id,$division_id);

                if ($stmt_out->execute()) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Route data already updated please refresh the page']);
                }

                $stmt_out->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the sch_veh_out statement.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update the sch_veh_in record.']);
        }

        $stmt_in->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the sch_veh_in statement.']);
    }
    $db->close();
}else {
    header('Location: ../pages/login.php');
    exit;
}
?>
