<?php
// Include your database connection file
include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
// Function to update bus status to "On Road" based on ID
if (empty($_POST)) {
    // If accessed directly without POST data, redirect to login.php
    header("Location: login.php");
    exit;
}
function updateBusStatus($id)
{
    global $db; // Access the global database connection
    date_default_timezone_set('Asia/Kolkata'); // Set the timezone to Indian Standard Time (IST)
    $submissionDateTime = date('Y-m-d H:i:s'); // Get the current date and time in Bangalore (IST)
    // Prepare and execute the SQL query to update the status

    // from the id find the bus number and check if the bus is off-road
    $findBusQuery = "SELECT bus_number, off_road_location FROM off_road_data WHERE id = $id";
    $findBusResult = mysqli_query($db, $findBusQuery);
    if (!$findBusResult || mysqli_num_rows($findBusResult) == 0) {
        return 'error: bus not found';
    }
    $busData = mysqli_fetch_assoc($findBusResult);
    $busNumber = $busData['bus_number'];
    $offRoadLocation = $busData['off_road_location'];

    if ($offRoadLocation == 'RWY') {

        //before update check if the bus number is present in rwy_offroad and status is 'off_road' then return error
        $checkQuery = "SELECT * FROM rwy_offroad WHERE bus_number = '$busNumber' AND status = 'off_road'";
        $checkResult = mysqli_query($db, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            return 'error: Bus is Held at RWY not allowed to update status Please wait for the bus to be released from RWY';
        }
    }

    $query = "UPDATE off_road_data
          SET 
              status = 'on_road', 
              no_of_days_offroad = DATEDIFF(CURDATE(), off_road_date),
              on_road_date= '$submissionDateTime'
          WHERE 
              id = $id";

    $result = mysqli_query($db, $query);

    // Check if the update was successful
    if ($result) {
        return 'success';
    } else {
        // Return the MySQL error message for debugging
        return 'Error: Network error occurred while updating the status. ';
    }
}

// Check if the ID is provided in the POST request
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    echo updateBusStatus($id); // Call the function and echo the result
} else {
    // If ID is not provided, return an error message
    echo 'error';
}

// Close the database connection
mysqli_close($db);
