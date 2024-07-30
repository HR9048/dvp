<?php
include '../includes/connection.php';
include 'session.php';

// Set the default timezone to Kolkata, India
date_default_timezone_set('Asia/Kolkata');

// Check if the session username is set
if (!isset($_SESSION['USERNAME'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired or user not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $division = $_POST['division'];
    $make = $_POST['make'];
    $emission_norms = $_POST['emission_norms'];
    $bus_body_builder = $_POST['bus_body_builder'];
    $wheel_base = $_POST['wheel_base'];
    $chassis_number = $_POST['chassis_number'];
    $bus_category = $_POST['bus_category'];
    $bus_sub_category = $_POST['bus_sub_category'];
    $seating_capacity = $_POST['seating_capacity'];
    $username = $_SESSION['USERNAME'];
    $submitted_date_time = date('Y-m-d H:i:s');

    // Check if all required fields are set
    if (empty($division) || empty($make) || empty($emission_norms) || empty($bus_body_builder) ||
        empty($wheel_base) || empty($chassis_number) || empty($bus_category) || empty($bus_sub_category) || empty($seating_capacity)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Prepare the SQL statement
    $query = "INSERT INTO rwy_bus_allocation (division, make, emission_norms, bus_body_builder, wheel_base, chassis_number, bus_category, bus_sub_category, seating_capacity, username, submit_datetime)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($db, $query)) {
        // Bind the parameters to the statement
        mysqli_stmt_bind_param($stmt, 'sssssssssss', $division, $make, $emission_norms, $bus_body_builder, $wheel_base, $chassis_number, $bus_category, $bus_sub_category, $seating_capacity, $username, $submitted_date_time);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'Bus registration successful.']);
        } else {
            if (mysqli_errno($db) == 1062) { // Duplicate entry error code
                echo json_encode(['status' => 'error', 'message' => 'Duplicate entry: Chassis number must be unique.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($stmt)]);
            }
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . mysqli_error($db)]);
    }
}
?>
