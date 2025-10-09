<?php
include 'connection.php';
include '../pages/session.php';
header('Content-Type: application/json');

// Read JSON input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'update_program_data') {

    if (empty($data['bus_number']) || empty($data['programs']) || !is_array($data['programs'])) {
        http_response_code(400);
        echo json_encode("Invalid data format");
        exit;
    }

    $bus_number = $data['bus_number'];
    $programs = $data['programs'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    foreach ($programs as $program) {
        $program_type = $program['program_type'] ?? null;
        $km = isset($program['km']) ? intval($program['km']) : 0;
        $id = $program['id'] ?? null;

        if (empty($program_type) || $km <= 0) continue;

        // Update existing record if ID is present
        if (!empty($id)) {
            $update_sql = "UPDATE program_data 
                           SET program_completed_km = ?
                           WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            if (!$update_stmt) {
                echo json_encode("Update prepare failed: " . $db->error);
                exit;
            }
            $update_stmt->bind_param("ii", $km, $id);
            $update_stmt->execute();
        } else {
            //some times id may miss for that check if record exists for bus and program type with program date null only
            $check_sql = "SELECT id FROM program_data 
                          WHERE bus_number = ? AND program_type = ? AND program_date IS NULL order by id ASC LIMIT 1";
            $check_stmt = $db->prepare($check_sql);
            if (!$check_stmt) {
                echo json_encode("Check prepare failed: " . $db->error);
                exit;
            }
            $check_stmt->bind_param("ss", $bus_number, $program_type);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $check_stmt->bind_result($id);
                $check_stmt->fetch();
            }
            $check_stmt->close();
            if (!empty($id)) {
                // If record found, update it
                $update_sql = "UPDATE program_data 
                               SET program_completed_km = ?
                               WHERE id = ?";
                $update_stmt = $db->prepare($update_sql);
                if (!$update_stmt) {
                    echo json_encode("Update prepare failed: " . $db->error);
                    exit;
                }
                $update_stmt->bind_param("ii", $km, $id);
                $update_stmt->execute();
                continue; // Move to next program
            } else {
                // Insert new record if no ID found
                $insert_sql = "INSERT INTO program_data 
                           (bus_number, program_type, program_completed_km, division_id, depot_id)
                           VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_sql);
                if (!$insert_stmt) {
                    echo json_encode("Insert prepare failed: " . $db->error);
                    exit;
                }
                $insert_stmt->bind_param("sssss", $bus_number, $program_type, $km, $division_id, $depot_id);
                $insert_stmt->execute();
            }
        }
    }

    echo json_encode("Program data updated successfully.");
    exit;
}

http_response_code(400);
echo json_encode("No valid action provided.");
exit;
