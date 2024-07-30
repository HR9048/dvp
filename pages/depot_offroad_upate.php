<?php
include '../includes/connection.php';
include 'session.php';
// Check if the form data is received via POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the data from the POST request
    $busNumber = $_POST['busNumber'];
    $make = $_POST['make'];
    $emissionNorms = $_POST['emissionNorms'];
    $offRoadLocation = $_POST['offRoadLocation'];
    $partsRequired = $_POST['partsRequired'];
    $remarks = $_POST['remarks'];

    // Get additional information from session variables 
    $username = $_SESSION['USERNAME'];
    $depot = $_SESSION['DEPOT_ID']; 
    $division = $_SESSION['DIVISION_ID'];
    
    // Get current date and time
    $submittedDateTime = date("Y-m-d H:i:s");

    // Set offRoadFromDate to today's date
    $offRoadFromDate = date("Y-m-d");

    // Set status to 'off_road'
    $status = 'off_road';

    // Convert the parts required string to an array
    $partsRequiredArray = explode(', ', $partsRequired);
    
    // Convert the array back to a comma-separated string
    $partsRequiredString = implode(', ', $partsRequiredArray);

    // Assuming you have a database table named 'off_road_data'
    $sql = "INSERT INTO off_road_data (bus_number, make, emission_norms, off_road_date, off_road_location, parts_required, remarks, username, division, depot, submission_datetime, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Prepare the SQL statement
    $stmt = $db->prepare($sql);

    // Bind the parameters
    $stmt->bind_param("ssssssssiiss", $busNumber, $make, $emissionNorms, $offRoadFromDate, $offRoadLocation, $partsRequiredString, $remarks, $username,  $division, $depot, $submittedDateTime, $status);

    // Execute the statement
    if ($stmt->execute()) {
        // Insertion successful
        echo "success";
    } else {
        // Insertion failed
        echo "error";
    }

    // Close the statement
    $stmt->close();
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

?>
