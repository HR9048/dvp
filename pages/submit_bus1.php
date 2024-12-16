<?php
// Include the database connection file
include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $divisionName = $_POST['division'];
    $depotName = $_POST['depot'];
    $busNumber = $_POST['bus_number'];
    $make = $_POST['make'];
    $emissionNorms = $_POST['emission_norms'];
    $doc = $_POST['doc'];
    $wheelBase = $_POST['wheel_base'];
    $chassisNumber = $_POST['chassis_number'];
    $busCategory = $_POST['bus_category'];
    $busSubCategory = $_POST['bus_sub_category'];
    $seatingCapacity = $_POST['seating_capacity'];
    $busBodyBuilder = $_POST['bus_body_builder'];

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
            // Bus number already exists, show alert message
            echo '<script>alert("Bus number ' . $busNumber . ' already exists."); window.location.href = "register1.php";</script>';
        } else {
            // Prepare and execute SQL INSERT query
            $sql = "INSERT INTO bus_registration (bus_number, division_name, depot_name, make, emission_norms, doc, wheel_base, chassis_number, bus_category, bus_sub_category, seating_capacity, bus_body_builder, username, submit_datetime) 
                    VALUES ('$busNumber', '$divisionName', '$depotName', '$make', '$emissionNorms', '$doc', '$wheelBase', '$chassisNumber', '$busCategory', '$busSubCategory', '$seatingCapacity', '$busBodyBuilder', '$username', '$submitDateTime')";

            try {
                // Attempt to execute the query
                $db->query($sql);
                echo '<script>alert("Bus data added successfully"); window.location.href = "register1.php";</script>';
            } catch (mysqli_sql_exception $e) {
                // Handle other database errors
                echo '<script>alert("Error: ' . $e->getMessage() . '");window.location.href = "register1.php";</script>';
            }
        }
    } else {
        // Alert message if any field is empty
        echo '<script>alert("Please fill in all fields.");</script>';
    }

    // Close the database connection
    $db->close();
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

?>
