<?php
// Include the database connection file
include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
// Check if the form data is received via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if any form field is empty or 0
    if (empty($_POST['bus_number']) || empty($_POST['make']) || empty($_POST['norms']) || empty($_POST['from_depot_id']) || empty($_POST['to_depot']) || empty($_POST['transfer_order_no']) || empty($_POST['order_date']) || empty($_POST['from_division_id']) || empty($_POST['division'])) {
        // If any field is empty, show alert message and exit
        echo "<script>alert('Please fill in all the required fields.'); window.location.href='division_bus_transfer.php';</script>";
        exit();
    }

    // Store form data in variables
    $vehicle_no = $_POST['bus_number'];
    $make = $_POST['make'];
    $norms = $_POST['norms'];
    $from_depot = $_POST['from_depot_id'];
    $to_depot = $_POST['to_depot'];
    $transfer_order_no = $_POST['transfer_order_no'];
    $order_date = $_POST['order_date'];
    $from_division = $_POST['from_division_id'];
    $to_division = $_POST['division'];

    // Validate session variables
    if (!isset($_SESSION['USERNAME']) || !isset($_SESSION['DIVISION_ID'])) {
        // If session variables are not set or empty, redirect to login page
        echo "<script>alert('Session variables not set. Please login.'); window.location.href='logout.php';</script>";
        exit();
    }

    // Get session username and division
    $username = $_SESSION['USERNAME'];

    // Update the bus_registration table
    $update_query = "UPDATE bus_registration SET depot_name = ?, division_name=? WHERE bus_number = ?";
    $update_stmt = mysqli_prepare($db, $update_query);
    mysqli_stmt_bind_param($update_stmt, "sss", $to_depot,$to_division, $vehicle_no);
    $update_result = mysqli_stmt_execute($update_stmt);

    // Check if update operation was successful
    // Check if update operation was successful
    if ($update_result) {
        // Get the current date and time in India Kolkata timezone
        date_default_timezone_set('Asia/Kolkata');
        $submitted_datetime = date('Y-m-d H:i:s');

        // Insert data into bus_transfer_data table
        $insert_query = "INSERT INTO bus_transfer_data (bus_number, make, norms, from_depot, to_depot, transfer_order_no, order_date, submitted_datetime, username, division,to_division) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($db, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "sssssssssss", $vehicle_no, $make, $norms, $from_depot, $to_depot, $transfer_order_no, $order_date, $submitted_datetime, $username, $from_division, $to_division);
        $insert_result = mysqli_stmt_execute($insert_stmt);

        // Check if insert operation was successful
        if ($insert_result) {
            // Return success response
            echo json_encode(array("status" => "success"));
            exit();
        } else {
            // Return error message if insert operation failed
            echo json_encode(array("status" => "error", "message" => "Failed to Update data ."));
            exit();
        }
    } else {
        // Return error message if update operation failed
        echo json_encode(array("status" => "error", "message" => "Failed to update data."));
        exit();
    }

}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}
?>