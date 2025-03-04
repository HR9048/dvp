<?php
include '../includes/connection.php';
include '../pages/session.php';

// Decode the JSON data from the request
$requestData = json_decode(file_get_contents('php://input'), true);

if (isset($requestData['action']) && $requestData['action'] === 'insertvehiclekmpldata') {
    $data = $requestData['data'];
    $reportDate = $requestData["date"] ?? null;

    if (empty($reportDate) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $reportDate)) {
        echo json_encode(["success" => false, "message" => "Invalid or missing report date."]);
        exit;
    }

    if (!empty($data)) {
        foreach ($data as $index => $row) {
            if ($index === count($data) - 1) {
                // Last row (Totals row) - Insert/Update into kmpl_data
                $total_km = $row['total_km_operated'] ?? null;
                $total_hsd = $row['total_hsd'] ?? null;
                $total_kmpl = ($total_hsd > 0) ? $total_km / $total_hsd : 0;

                // Check if record exists
                $checkQuery = "SELECT COUNT(*) as count FROM kmpl_data WHERE division = ? AND depot = ? AND date = ?";
                $stmt = $db->prepare($checkQuery);
                $stmt->bind_param("iis", $division_id, $depot_id, $reportDate);
                $stmt->execute();
                $result = $stmt->get_result();
                $rowExists = $result->fetch_assoc()['count'] > 0;

                if ($rowExists) {
                    // Update existing record
                    //$updateQuery = "UPDATE kmpl_data SET total_km = ?, hsd = ?, kmpl = ? WHERE division = ? AND depot = ? AND date = ?";
                    //$stmt = $db->prepare($updateQuery);
                    //$stmt->bind_param("dddiis", $total_km, $total_hsd, $total_kmpl, $division_id, $depot_id, $reportDate);
                } else {
                    // Insert new record
                    //$insertQuery = "INSERT INTO kmpl_data (total_km, hsd, kmpl, division, depot, date) VALUES (?, ?, ?, ?, ?, ?)";
                    //$stmt = $db->prepare($insertQuery);
                    //$stmt->bind_param("dddiis", $total_km, $total_hsd, $total_kmpl, $division_id, $depot_id, $reportDate);
                }
            } else {
                // Normal row processing for vehicle_kmpl
                $bus_number = $row['bus_number'] ?? null;
                $route_no = $row['route_no'] ?? null;
                $driver_1_pf = $row['driver_token1'] ?? null;
                $driver_2_pf = $row['driver_token2'] ?? null;
                $logsheet_no = $row['logsheet_no'] ?? null;
                $km_operated = $row['km_operated'] ?? null;
                $hsd = $row['hsd'] ?? null;
                $kmpl = ($hsd > 0) ? $km_operated / $hsd : 0;
                $thump_status = $row['thump_status'] ?? null;
                $remarks = $row['remarks'] ?? null;
                $division_id = $row['division_id'];
                $depot_id = $row['depot_id'];
                

                $checkQuery = "SELECT COUNT(*) as count FROM vehicle_kmpl WHERE bus_number = ? AND division_id = ? AND depot_id = ? AND date = ?";
                $stmt = $db->prepare($checkQuery);
                $stmt->bind_param("siis", $bus_number, $division_id, $depot_id, $reportDate);
                $stmt->execute();
                $result = $stmt->get_result();
                $rowExists = $result->fetch_assoc()['count'] > 0;

                if ($rowExists) {
                    $updateQuery = "UPDATE vehicle_kmpl SET route_no = ?, driver_1_pf = ?, driver_2_pf = ?, logsheet_no = ?, km_operated = ?, hsd = ?, kmpl = ?, thumps_id = ?,  remarks = ?, division_id = ?, depot_id = ? WHERE bus_number = ?  AND date = ?";
                    $stmt = $db->prepare($updateQuery);
                    $stmt->bind_param("ssssssssssiis", $route_no, $driver_1_pf, $driver_2_pf, $logsheet_no, $km_operated, $hsd, $kmpl, $thump_status,  $remarks, $division_id, $depot_id, $bus_number, $reportDate);
                } else {
                    $insertQuery = "INSERT INTO vehicle_kmpl (bus_number, route_no, driver_1_pf, driver_2_pf, logsheet_no, km_operated, hsd, kmpl, thumps_id, remarks, division_id, depot_id, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insertQuery);
                    $stmt->bind_param("ssssssssssiis", $bus_number, $route_no, $driver_1_pf, $driver_2_pf, $logsheet_no, $km_operated, $hsd, $kmpl, $thump_status, $remarks, $division_id, $depot_id, $reportDate);
                }
            }
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Error inserting/updating data: ' . $stmt->error]);
                exit;
            }
        }
        echo json_encode(['success' => true, 'message' => 'Data processed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data to process.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>
