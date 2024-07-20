<?php
include '../includes/connection.php';
include 'session.php';

// Initialize response array
$response = array();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if username is set in session
    if (!isset($_SESSION['USERNAME'])) {
        // If username is not set, redirect to logout.php with an alert message
    echo "<script>alert('Username is not set. Please log in again.'); window.location = 'logout.php';</script>";
    exit; // Stop script execution
    }

    // Get form data
    $busNumber = $_POST['busNumberInput'];
    $division = $_POST['divisionIDInput'];
    $depot = $_POST['depotIDInput'];
    $make = $_POST['makeInput'];
    $emissionNorms = $_POST['emissionNormsInput'];
    $receivedDate = $_POST['receivedDate'];
    $workReason = $_POST['workReason'];

    // Check if received date is empty
    if (empty($receivedDate)) {
        // Set error response
        $response['success'] = false;
        $response['message'] = "Received date is empty.";
    } else {
        // Prepare and execute the SQL query to insert data into the database
        $query = "INSERT INTO rwy_offroad (division, depot, bus_number, make, emission_norms, received_date, work_reason, status, username) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssssssss", $division, $depot, $busNumber, $make, $emissionNorms, $receivedDate, $workReason, $status, $username);

        $status = "off_road"; // Set status value
        $username = $_SESSION['USERNAME'];

        // Execute the statement
        if ($stmt->execute()) {
            // Set success response
            $response['success'] = true;
            $response['message'] = "Data inserted successfully.";
        } else {
            // Set error response
            $response['success'] = false;
            $response['message'] = "Error: " . $db->error;
        }

        // Close statement
        $stmt->close();
    }

    // Send response as JSON
    echo json_encode($response);
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}


?>
