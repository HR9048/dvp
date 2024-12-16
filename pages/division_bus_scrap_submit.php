<?php
// Start session to access session variables
include 'session.php';

// Check if username is set in session, if not redirect to logout.php
if (!isset($_SESSION['USERNAME'])) {
    header("Location: logout.php");
    exit;
}
if (empty($_POST)) {
    // If accessed directly without POST data, redirect to login.php
    header("Location: login.php");
    exit;
}
// Include the common database connection file
include '../includes/connection.php';

// Check if all required form fields are present and not empty
$form_fields = ['bus_number', 'make', 'emission_norms', 'depotID', 'divisionID', 'transfer_order_no', 'order_date'];
foreach ($form_fields as $field) {
    if ($field === 'order_date' && empty($_POST[$field])) {
        $response = array("status" => "error", "message" => "Order date is invalid. Please select a valid date.");
        echo json_encode($response);
        exit;
    } elseif (!isset($_POST[$field]) || empty($_POST[$field])) {
        $response = array("status" => "error", "message" => "Form details missing. Please fill once again. Field: $field");
        echo json_encode($response);
        exit;
    }
}

// Check if hidden fields are present in the form
$hidden_fields = ['doc', 'wheel_base', 'chassis_number', 'bus_category', 'bus_sub_category', 'seating_capacity', 'bus_body_builder', 'bus_username', 'bus_submit_datetime'];
foreach ($hidden_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $response = array("status" => "error", "message" => "Form hidden details missing. Please fill once again. Field: $field");
        echo json_encode($response);
        exit;
    }
}

// Set variables with form data and session data
$bus_number = $_POST['bus_number'];
$make = $_POST['make'];
$emission_norms = $_POST['emission_norms'];
$depot = $_POST['depotID'];
$division = $_POST['divisionID'];
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$wheel_base = isset($_POST['wheel_base']) ? $_POST['wheel_base'] : "";
$chassis_number = isset($_POST['chassis_number']) ? $_POST['chassis_number'] : "";
$bus_category = isset($_POST['bus_category']) ? $_POST['bus_category'] : "";
$bus_sub_category = isset($_POST['bus_sub_category']) ? $_POST['bus_sub_category'] : "";
$seating_capacity = isset($_POST['seating_capacity']) ? $_POST['seating_capacity'] : "";
$bus_body_builder = isset($_POST['bus_body_builder']) ? $_POST['bus_body_builder'] : "";
$bus_username = $_POST['bus_username'];
$bus_submitdatetime = $_POST['bus_submit_datetime'];
$transfer_order_no = $_POST['transfer_order_no'];
// Format the order date to 'YYYY-MM-DD' format
$order_date = date('Y-m-d', strtotime($_POST['order_date']));
$username = $_SESSION['USERNAME']; // Get username from session variable
// Set the timezone to India/Kolkata
date_default_timezone_set('Asia/Kolkata');

// Get the current server time


// Prepare and bind SQL statement for inserting into bus_scrap_data table
$insert_sql = "INSERT INTO bus_scrap_data (bus_number, make, emission_norms, depot, division, doc, wheel_base, chassis_number, bus_category, bus_sub_category, seating_capacity, bus_body_builder, bus_username, bus_submitdatetime, transfer_order_no, order_date, username) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$insert_stmt = $db->prepare($insert_sql);

// Check if the statement was prepared successfully
if ($insert_stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($db->error));
}

// Bind parameters
$insert_stmt->bind_param(
    "sssssssssssssssss", 
    $bus_number, 
    $make, 
    $emission_norms, 
    $depot, 
    $division, 
    $doc, 
    $wheel_base, 
    $chassis_number, 
    $bus_category, 
    $bus_sub_category, 
    $seating_capacity, 
    $bus_body_builder, 
    $bus_username, 
    $bus_submitdatetime, // Ensure this is in 'YYYY-MM-DD' format
    $transfer_order_no, 
    $order_date, // Ensure this is in 'YYYY-MM-DD' format
    $username
);

// Execute the insert SQL statement
if ($insert_stmt->execute()) {
    // If insert is successful, prepare and bind SQL statement for deleting from bus_registration table
    $delete_sql = "DELETE FROM bus_registration WHERE bus_number = ?";
    $delete_stmt = $db->prepare($delete_sql);
    $delete_stmt->bind_param("s", $bus_number);

    // Execute the delete SQL statement
    if ($delete_stmt->execute()) {
        $response = array("status" => "success", "message" => "New record inserted successfully and old record deleted from bus_registration");
        echo json_encode($response);
    } else {
        $response = array("status" => "error", "message" => "Error deleting old record: " . $delete_stmt->error);
        echo json_encode($response);
    }
} else {
    $response = array("status" => "error", "message" => "Error inserting new record: " . $insert_stmt->error);
    echo json_encode($response);
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Close statements and connection
$insert_stmt->close();
$delete_stmt->close();
$db->close();
?>