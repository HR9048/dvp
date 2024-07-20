<?php
// Include database connection
include '../includes/connection.php';
include 'session.php';

if (!isset($_SESSION['USERNAME'], $_SESSION['DIVISION_ID'], $_SESSION['DEPOT_ID'])) {
    session_destroy();
    header("Location: logout.php");
    exit;
}
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $date = $_POST['bd_date'];
    $bus_number = $_POST['bus_number'];
    $make = $_POST['make'];
    $model = $_POST['emission_norms'];
    $route_number = $_POST['route_number'];
    $bd_location = $_POST['bd_location'];
    $reason = $_POST['reason'];
    $remark = $_POST['remark'];

    // Set timezone to Kolkata (Indian Standard Time)
    date_default_timezone_set('Asia/Kolkata');

    // Get current date and time in Kolkata timezone
    $submitted_date = date("Y-m-d H:i:s");
    $username = $_SESSION['USERNAME']; // Assuming this session variable is set elsewhere
    $division = $_SESSION['DIVISION_ID']; // Assuming this session variable is set elsewhere
    $depot = $_SESSION['DEPOT_ID']; // Assuming this session variable is set elsewhere

    // Prepare SQL statement to insert data
    $sql = "INSERT INTO bd_data (date, bus_number, make, model, route_number, bd_location, reason, remark, username, division, depot, submitted_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare and bind parameters to prevent SQL injection
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssssssssssss", $date, $bus_number, $make, $model, $route_number, $bd_location, $reason, $remark, $username, $division, $depot, $submitted_date);

    // Execute prepared statement
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'New record created successfully!';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error: ' . $stmt->error;
    }

    // Close statement and database connection
    $stmt->close();
    $db->close();

    // Return JSON response
    echo json_encode($response);
} else {
    // Redirect if accessed directly without POST data
    header("Location: login.php");
    exit;
}
?>