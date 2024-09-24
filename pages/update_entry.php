<?php
// update_entry.php

include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
$response = array(); // Initialize response array

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $entryId = $_POST['entryId'];
    $dwsRemark = $_POST['dwsRemark'];

    // Get current date and time in Kolkata timezone
    date_default_timezone_set('Asia/Kolkata');
    $currentDateTime = date('Y-m-d H:i:s');

    // Prepare and bind parameters
    $stmt = $db->prepare("UPDATE off_road_data SET dws_remark = ?, dws_last_update = ? WHERE id = ?");
    $stmt->bind_param("ssi", $dwsRemark, $currentDateTime, $entryId);

    // Execute the update statement
    if ($stmt->execute()) {
        // Set success message in response array
        $response['status'] = 'success';
        $response['message'] = 'Record updated successfully';
    } else {
        // Set error message in response array
        $response['status'] = 'error';
        $response['message'] = 'Error updating record: ' . $stmt->error;
    }

    // Close statement and database connection
    $stmt->close();
    $db->close();
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}


// Output response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
