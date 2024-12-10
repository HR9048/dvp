<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['USERNAME'])) {
    // Redirect to login page if not logged in
    header('Location: ../pages/login.php');
    exit();
}

include '../includes/connection.php';  // Include DB connection

// Get the form data
$id = $_POST['row_id'];
$sch_key_no = $_POST['sch_key_no'];
$sch_abbr = $_POST['sch_abbr'];
$sch_km = $_POST['sch_km'];
$sch_dep_time = $_POST['sch_dep_time'];
$sch_arr_time = $_POST['sch_arr_time'];
$service_class_id = $_POST['service_class_id'];
$service_type_id = $_POST['service_type_id'];

// Determine schedule count based on service type id
if ($service_type_id == 1 || $service_type_id == 2) {
    $sch_count = 1;
} elseif ($service_type_id == 3 || $service_type_id == 4) {
    $sch_count = 2;
} else {
    $sch_count = 0;
}

// Determine number of buses based on service type id
if ($service_type_id == 1 || $service_type_id == 2) {
    $number_of_buses = 1;
} elseif ($service_type_id == 3 || $service_type_id == 4) {
    $number_of_buses = 2;
} else {
    $number_of_buses = 0;
}

// Update the schedule data
$query = "UPDATE schedule_master SET 
    sch_key_no = ?, sch_abbr = ?, sch_km = ?, sch_dep_time = ?, 
    sch_arr_time = ?, sch_count = ?, number_of_buses = ?, 
    service_class_id = ?, service_type_id = ? 
WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("sssssiiiii", $sch_key_no, $sch_abbr, $sch_km, $sch_dep_time, 
    $sch_arr_time, $sch_count, $number_of_buses, $service_class_id, $service_type_id, $id);
$stmt->execute();

// Check if the update was successful
if ($stmt->affected_rows > 0) {
    echo "Success";
} else {
    echo "Error: No changes made.";
}

$stmt->close();
$db->close();
?>
