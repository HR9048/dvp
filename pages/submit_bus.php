<?php
// Include the database connection file
include '../includes/connection.php';
include 'session.php';

// Initialize response array
$response = array();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $divisionName = $_POST['divisionid'];
    $depotName = $_POST['depot'];
    $busNumber = $_POST['bus_number'];
    $make = $_POST['make'];
    $emissionNorms = $_POST['emissionNorms'];
    $doc = $_POST['doc'];
    $wheelBase = $_POST['wheelBase'];
    $chassisNumber = $_POST['chassisNumber'];
    $busCategory = $_POST['busCategory'];
    $busSubCategory = $_POST['busSubCategory'];
    $seatingCapacity = $_POST['seatingCapacity'];
    $busBodyBuilder = $_POST['busBodyBuilder'];

    // Check if all fields are entered
    if (!empty($divisionName) && !empty($depotName) && !empty($busNumber) && !empty($make) && !empty($emissionNorms) && !empty($doc) && !empty($wheelBase) && !empty($chassisNumber) && !empty($busCategory) && !empty($busSubCategory) && !empty($seatingCapacity) && !empty($busBodyBuilder)) {
        
        // Retrieve username from session
        $username = $_SESSION['USERNAME'];
        
        // Get current date and time in India/Kolkata timezone
        date_default_timezone_set('Asia/Kolkata');
        $submitDateTime = date('Y-m-d H:i:s');

        // Check if bus number already exists
        $check_sql = "SELECT * FROM bus_registration WHERE bus_number = '$busNumber'";
        $result = $db->query($check_sql);

        if ($result->num_rows > 0) {
            // Bus number already exists, set error message
            $response['error'] = true;
            $response['message'] = "Bus number $busNumber already exists.";
        } else {
            // Prepare and execute SQL INSERT query for bus_registration table
            $sql = "INSERT INTO bus_registration (bus_number, division_name, depot_name, make, emission_norms, doc, wheel_base, chassis_number, bus_category, bus_sub_category, seating_capacity, bus_body_builder, username, submit_datetime) 
                    VALUES ('$busNumber', '$divisionName', '$depotName', '$make', '$emissionNorms', '$doc', '$wheelBase', '$chassisNumber', '$busCategory', '$busSubCategory', '$seatingCapacity', '$busBodyBuilder', '$username', '$submitDateTime')";

            if ($db->query($sql)) {
                // Update rwy_bus_allocation table
                $update_sql = "UPDATE rwy_bus_allocation 
                               SET bus_number = '$busNumber', depot = '$depotName', doc = '$doc', division_receive_date = '$submitDateTime'
                               WHERE chassis_number = '$chassisNumber'";
                $db->query($update_sql);
                
                // Set success message
                $response['error'] = false;
                $response['message'] = "Bus data added successfully.";
            } else {
                // Set error message for database query failure
                $response['error'] = true;
                $response['message'] = "Error: " . $db->error;
            }
        }
    } else {
        // Set error message if any field is empty
        $response['error'] = true;
        $response['message'] = "Please fill in all fields.";
    }

    // Close the database connection
    $db->close();
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}
// Send JSON response
?>
