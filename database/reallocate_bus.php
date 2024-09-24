<?php
header('Content-Type: application/json'); // Ensure correct content type

include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();
if (isset($_POST['busNumber']) && isset($_POST['oldSchKeyNo'])) {
    $busNumber = $_POST['busNumber'];
    $oldSchKeyNo = $_POST['oldSchKeyNo'];
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];
    $date  = new DateTime("now", new DateTimeZone('Asia/Kolkata'));
    $formattedDate = $date->format('Y-m-d H:i:s'); // Format the date as per your needs

    // Set make and emission_norms to NULL for bus number in the old schedule
    $query = "UPDATE schedule_master 
SET bus_make_1 = NULL, bus_emission_norms_1 = NULL 
WHERE sch_key_no = ? AND bus_number_1 = ? AND division_id = ? AND depot_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ssii', $oldSchKeyNo, $busNumber, $division, $depot);
    $stmt->execute();

    // Set make and emission_norms to NULL for bus number in the old schedule
    $query = "UPDATE schedule_master 
SET bus_make_2 = NULL, bus_emission_norms_2 = NULL 
WHERE sch_key_no = ? AND bus_number_2 = ? AND division_id = ? AND depot_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ssii', $oldSchKeyNo, $busNumber, $division, $depot);
    $stmt->execute();


    // Remove bus number from old schedule
    $query = "UPDATE schedule_master SET bus_number_1 = NULL WHERE sch_key_no = ? AND bus_number_1 = ? AND division_id = ? AND depot_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ssii', $oldSchKeyNo, $busNumber, $division, $depot);
    $stmt->execute();

    // Allocate bus number to new schedule
    $query = "UPDATE schedule_master SET bus_number_2 = NULL WHERE sch_key_no = ? AND bus_number_2 = ? AND division_id = ? AND depot_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ssii', $oldSchKeyNo, $busNumber, $division, $depot);
    $result = $stmt->execute();
    // Allocate bus number to new schedule

    $query = "UPDATE bus_fix_data 
    SET to_date = ? 
    WHERE sch_key_no = ? AND bus_number = ? AND division_id = ? AND depot_id = ? AND additional='0'";
    $stmt = $db->prepare($query);
    $stmt->bind_param('sssii', $formattedDate, $oldSchKeyNo, $busNumber, $division, $depot);
    $result = $stmt->execute();

    // Send JSON response
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
}
?>