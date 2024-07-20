<?php
include '../includes/connection.php';

// Check if the form data has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect the updated bus details from the POST data
    $oldBusNumber = mysqli_real_escape_string($db, $_POST['busNumber']);
    $busNumber = mysqli_real_escape_string($db, $_POST['newBusNumber']); // New bus number if it's edited
    $division = mysqli_real_escape_string($db, $_POST['division']);
    $depot = mysqli_real_escape_string($db, $_POST['depot']);
    $make = mysqli_real_escape_string($db, $_POST['make']);
    $emissionNorms = mysqli_real_escape_string($db, $_POST['emissionNorms']);
    $doc = mysqli_real_escape_string($db, $_POST['doc']);
    $wheelBase = mysqli_real_escape_string($db, $_POST['wheelBase']);
    $chassisNumber = mysqli_real_escape_string($db, $_POST['chassisNumber']);
    $busCategory = mysqli_real_escape_string($db, $_POST['busCategory']);
    $busSubCategory = mysqli_real_escape_string($db, $_POST['busSubCategory']);
    $seatingCapacity = mysqli_real_escape_string($db, $_POST['seatingCapacity']);
    $busBodyBuilder = mysqli_real_escape_string($db, $_POST['busBodyBuilder']);

    // SQL query to update the bus details in the database
    $sql = "UPDATE bus_registration SET 
            bus_number='$busNumber',
            make = '$make',
            emission_norms = '$emissionNorms',
            doc = '$doc',
            wheel_base = '$wheelBase',
            chassis_number = '$chassisNumber',
            bus_category = '$busCategory',
            bus_sub_category = '$busSubCategory',
            seating_capacity = '$seatingCapacity',
            bus_body_builder = '$busBodyBuilder'
            WHERE bus_number = '$oldBusNumber'";

    // Execute the SQL query
    if (mysqli_query($db, $sql)) {
        echo "Bus details updated successfully.";
    } else {
        echo "Error updating bus details: " . mysqli_error($db);
    }
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

// Close the database connection
mysqli_close($db);
?>
