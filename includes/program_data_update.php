<?php
include 'connection.php';
include '../pages/session.php';
header('Content-Type: application/json');

// Read JSON input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'update_program_data') {

    if (!isset($data['bus_number']) || !isset($data['programs']) || !is_array($data['programs'])) {
        http_response_code(400);
        echo json_encode("Invalid data format");
        exit;
    }

    $bus_number = $data['bus_number'];
    $programs = $data['programs'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    foreach ($programs as $type => $km) {
        $km = intval($km);  // Ensure it's an integer

        if ($km <= 0) continue;  // Skip empty or invalid km

        $date = null;

        // Check if entry exists
        $check_sql = "SELECT id FROM program_data WHERE bus_number = ? AND program_type = ?";
        $stmt = $db->prepare($check_sql);
        if (!$stmt) {
            echo json_encode("Prepare failed: " . $db->error);
            exit;
        }
        $stmt->bind_param("ss", $bus_number, $type);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update existing
            $update_sql = "UPDATE program_data 
                           SET program_completed_km = ?, program_date = ?, updated_at = NOW() 
                           WHERE bus_number = ? AND program_type = ?";
            $update_stmt = $db->prepare($update_sql);
            if (!$update_stmt) {
                echo json_encode("Update Prepare failed: " . $db->error);
                exit;
            }
            $update_stmt->bind_param("isss", $km, $date, $bus_number, $type);
            $update_stmt->execute();
        } else {
            // Insert new
            $insert_sql = "INSERT INTO program_data 
                           (bus_number, program_type, program_completed_km, program_date, division_id, depot_id)
                           VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_sql);
            if (!$insert_stmt) {
                echo json_encode("Insert Prepare failed: " . $db->error);
                exit;
            }
            $insert_stmt->bind_param("ssisss", $bus_number, $type, $km, $date, $division_id, $depot_id);
            $insert_stmt->execute();
        }
    }

    echo json_encode("Updated successfully.");
    exit;
}

http_response_code(400);
echo json_encode("No valid action provided.");
exit;
