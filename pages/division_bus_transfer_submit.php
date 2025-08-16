<?php
// Include the database connection file
include '../includes/connection.php';
include 'session.php';
date_default_timezone_set('Asia/Kolkata');

// Check if the form data is received via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if any form field is empty or 0
    if (empty($_POST['bus_number']) || empty($_POST['make']) || empty($_POST['norms']) || empty($_POST['from_depot_id']) || empty($_POST['to_depot']) || empty($_POST['transfer_order_no']) || empty($_POST['order_date'])) {
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

    // Validate session variables
    if (!isset($_SESSION['USERNAME']) || !isset($_SESSION['DIVISION_ID'])) {
        // If session variables are not set or empty, redirect to login page
        echo "<script>alert('Session variables not set. Please login.'); window.location.href='logout.php';</script>";
        exit();
    }

    // Get session username and division
    $username = $_SESSION['USERNAME'];
    $division = $_SESSION['DIVISION_ID'];


    // Null bus_number_1 details if match
    $update1 = "
        UPDATE schedule_master
        SET bus_number_1 = NULL,
            bus_make_1 = NULL,
            bus_emission_norms_1 = NULL
        WHERE bus_number_1 = ?
    ";
    $stmt1 = mysqli_prepare($db, $update1);
    mysqli_stmt_bind_param($stmt1, "s", $vehicle_no);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    // Null bus_number_2 details if match
    $update2 = "
        UPDATE schedule_master
        SET bus_number_2 = NULL,
            bus_make_2 = NULL,
            bus_emission_norms_2 = NULL
        WHERE bus_number_2 = ?
    ";
    $stmt2 = mysqli_prepare($db, $update2);
    mysqli_stmt_bind_param($stmt2, "s", $vehicle_no);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    // Null additional_bus_number details if match
    $update3 = "
        UPDATE schedule_master
        SET additional_bus_number = NULL,
            additional_bus_make = NULL,
            additional_bus_emission_norms = NULL
        WHERE additional_bus_number = ?
    ";
    $stmt3 = mysqli_prepare($db, $update3);
    mysqli_stmt_bind_param($stmt3, "s", $vehicle_no);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_close($stmt3);

    //update bus_fix_data table set to_date = current datetime where bus number matches and to_date is null
    $update_fix_data = "
        UPDATE bus_fix_data
        SET to_date = NOW()
        WHERE bus_number = ? AND to_date IS NULL
    ";
    $stmt_fix_data = mysqli_prepare($db, $update_fix_data);
    mysqli_stmt_bind_param($stmt_fix_data, "s", $vehicle_no);
    mysqli_stmt_execute($stmt_fix_data);
    mysqli_stmt_close($stmt_fix_data);

    // Update the bus_registration table
    $update_query = "UPDATE bus_registration SET depot_name = ? WHERE bus_number = ?";
    $update_stmt = mysqli_prepare($db, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ss", $to_depot, $vehicle_no);
    $update_result = mysqli_stmt_execute($update_stmt);

    // Check if update operation was successful
    // Check if update operation was successful
    if ($update_result) {
        // Get the current date and time in India Kolkata timezone
        date_default_timezone_set('Asia/Kolkata');
        $submitted_datetime = date('Y-m-d H:i:s');

        // Insert data into bus_transfer_data table
        $insert_query = "INSERT INTO bus_transfer_data (bus_number, make, norms, from_depot, to_depot, transfer_order_no, order_date, submitted_datetime, username, division) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($db, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "ssssssssss", $vehicle_no, $make, $norms, $from_depot, $to_depot, $transfer_order_no, $order_date, $submitted_datetime, $username, $division);
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
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}
