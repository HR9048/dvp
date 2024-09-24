<?php
include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
// Check if form data is present
if (
    isset($_POST['busNumberInput']) &&
    isset($_POST['divisionID']) &&
    isset($_POST['depotID']) &&
    isset($_POST['makeInput']) &&
    isset($_POST['emissionNormsInput']) &&
    isset($_POST['workReason']) &&
    isset($_POST['remarks'])
) {
    // Get form data
    $busNumber = $_POST['busNumberInput'];
    $depot = $_POST['depotID'];
    $make = $_POST['makeInput'];
    $emissionNorms = $_POST['emissionNormsInput'];
    $partsRequired = $_POST['workReason'];
    $remarks = $_POST['remarks'];
    $division = $_POST['divisionID'];
    // Check if any field is empty
    if (empty($busNumber) || empty($depot) || empty($make) || empty($emissionNorms) || empty($partsRequired) || empty($division) || empty($remarks)) {
        echo "Error: Please fill out all fields.";
    } else {
        // Insert data into the database
        $username = $_SESSION['USERNAME'];

        $submission_datetime = date('Y-m-d H:i:s', strtotime('+5 hours 30 minutes')); // Kolkata time
        $offRoadFromDate = date('Y-m-d', strtotime('+5 hours 30 minutes')); // Kolkata time
        $status = 'off_road';
        $dws_last_update = date('Y-m-d H:i:s', strtotime('+5 hours 30 minutes')); // Kolkata time
        $offRoadLocation = 'RWY';
        // Prepare and execute the SQL statement
        $stmt = $db->prepare("INSERT INTO off_road_data (bus_number, make, emission_norms, off_road_date, off_road_location, parts_required, remarks, username, division, depot, submission_datetime, status, dws_remark, dws_last_update) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssss", $busNumber, $make, $emissionNorms, $offRoadFromDate, $offRoadLocation, $partsRequired, $remarks, $username, $division, $depot, $submission_datetime, $status, $remarks, $dws_last_update);
        if ($stmt->execute()) {
            echo "Success: Data inserted successfully.";
        } else {
            echo "Error: Failed to insert data.";
        }
        $stmt->close();
    }
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

?>