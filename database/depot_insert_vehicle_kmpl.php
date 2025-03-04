<?php
include '../includes/connection.php';
include '../pages/session.php';

// Decode the JSON data from the request
$requestData = json_decode(file_get_contents('php://input'), true);

if (isset($requestData['action']) && $requestData['action'] === 'insertvehiclekmpldata') {
    $data = $requestData['data'];
    $reportDate = $requestData["date"] ?? null;
    $username = $_SESSION['USERNAME'] ?? '';

    date_default_timezone_set('Asia/Kolkata'); // Set the time zone to India (Kolkata)
    $submitted_datetime = date('Y-m-d H:i:s'); // Get the current date and time in YYYY-MM-DD HH:MM:SS format

    if (empty($reportDate) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $reportDate)) {
        echo json_encode(["success" => false, "message" => "Invalid or missing report date."]);
        exit;
    }

    if (!empty($data)) {
        $insertSuccess = true;
        $errorMessage = "";

        foreach ($data as $index => $row) {
            if ($index === count($data) - 1) {
                // Handle the last row separately (Totals Row)
                $total_km = $row['total_km_operated'] ?? 0;
                $total_hsd = $row['total_hsd'] ?? 0;
                $total_kmpl = ($total_hsd > 0) ? round($total_km / $total_hsd, 2) : 0;
                $division_id = $row['division_id'];
                $depot_id = $row['depot_id'];

                $checkQuery = "SELECT COUNT(*) as count FROM kmpl_data WHERE division = ? AND depot = ? AND date = ?";
                $stmt = $db->prepare($checkQuery);
                $stmt->bind_param("iis", $division_id, $depot_id, $reportDate);
                $stmt->execute();
                $result = $stmt->get_result();
                $rowExists = $result->fetch_assoc()['count'] > 0;

                if ($rowExists) {
                    // Update existing total row
                    $updateQuery = "UPDATE kmpl_data SET total_km = ?, hsd = ?, kmpl = ? WHERE division = ? AND depot = ? AND date = ?";
                    $stmt = $db->prepare($updateQuery);
                    $stmt->bind_param("dddiis", $total_km, $total_hsd, $total_kmpl, $division_id, $depot_id, $reportDate);
                    if (!$stmt->execute()) {
                        $insertSuccess = false;
                        $errorMessage = "Failed to update totals row.";
                    }
                } else {
                    // Insert new total row
                    $insertQuery = "INSERT INTO kmpl_data (division, depot, date, total_km, hsd, kmpl, username, submitted_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insertQuery);
                    $stmt->bind_param("iisdssss", $division_id, $depot_id, $reportDate, $total_km, $total_hsd, $total_kmpl, $username, $submitted_datetime);
                    if (!$stmt->execute()) {
                        $insertSuccess = false;
                        $errorMessage = "Failed to insert totals row.";
                    }
                }
            } else {
                // Handle all other rows (Update if ID exists, Insert if not)
                $id = $row['id'] ?? null;

                if (!empty($id)) {
                    // Update existing row
                    $vc = $row['vc'] ?? 0;
                    $cc = $row['cc'] ?? 0;
                    $km_operated = $row['km_operated'] ?? 0;
                    $hsd = $row['hsd'] ?? 0;
                    $kmpl = ($hsd > 0) ? round($km_operated / $hsd, 2) : 0; // Calculate KMPL with 2 decimal places

                    $updateQuery = "UPDATE vehicle_kmpl SET bus_number = ?, route_no = ?, driver_1_pf = ?, driver_2_pf = ?, logsheet_no = ?, km_operated = ?, hsd = ?, kmpl = ?, thumps_id = ?, remarks = ?, division_id = ?, depot_id = ?, date = ?, v_change = ?, c_change = ? WHERE id = ?";
                    $stmt = $db->prepare($updateQuery);
                    $stmt->bind_param(
                        "ssssssssssiisiii",
                        $row['bus_number'],
                        $row['route_no'],
                        $row['driver_token1'],
                        $row['driver_token2'],
                        $row['logsheet_no'],
                        $row['km_operated'],
                        $row['hsd'],
                        $kmpl,
                        $row['thump_status'],
                        $row['remarks'],
                        $row['division_id'],
                        $row['depot_id'],
                        $reportDate,
                        $vc,  // Now a variable
                        $cc,
                        $id
                    );

                    if (!$stmt->execute()) {
                        $insertSuccess = false;
                        $errorMessage = "Failed to update row with ID " . $id;
                    }
                } elseif ($id == null) {
                    // Insert new row
                    $vc = $row['vc'] ?? 0;
                    $cc = $row['cc'] ?? 0;
                    $insertQuery = "INSERT INTO vehicle_kmpl (bus_number, route_no, driver_1_pf, driver_2_pf, logsheet_no, km_operated, hsd, kmpl, thumps_id, remarks, division_id, depot_id, date, v_change, c_change, created_by) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insertQuery);
                    $stmt->bind_param(
                        "sssssssssssssiis",
                        $row['bus_number'],
                        $row['route_no'],
                        $row['driver_token1'],
                        $row['driver_token2'],
                        $row['logsheet_no'],
                        $row['km_operated'],
                        $row['hsd'],
                        $row['kmpl'],
                        $row['thump_status'],
                        $row['remarks'],
                        $row['division_id'],
                        $row['depot_id'],
                        $reportDate,
                        $vc,
                        $cc,
                        $username
                    );

                    if (!$stmt->execute()) {
                        $insertSuccess = false;
                        $errorMessage = "Failed to insert new row for bus " . $row['bus_number'] . " Error: " . $stmt->error;
                    }
                }
            }
        }

        if ($insertSuccess) {
            echo json_encode(["success" => true, "message" => "KMPL Data added/updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => $errorMessage]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "No data received."]);
    }
}
