<?php
header('Content-Type: application/json');
include '../includes/connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "insertupdatesinglevehiclekmpl") {
    try {
        $id = $_POST['id'] ?? null;
        $vc = $_POST['vc'] ?? null;
        $cc = $_POST['cc'] ?? null;
        $bus_number = $_POST['bus_number'] ?? '';
        $route_number = $_POST['route_number'] ?? '';
        $driver_token_1 = $_POST['driver_token_1'] ?? '';
        $driver_token_2 = $_POST['driver_token_2'] ?? null;
        $logsheet_no = $_POST['logsheet_no'] ?? '';
        $km_operated = $_POST['km_operated'] ?? '';
        $hsd = $_POST['hsd'] ?? '';
        $kmpl = $_POST['kmpl'] ?? '';
        $thump_status = $_POST['thump_status'] ?? '';
        $logsheet_defects = $_POST['logsheet_defects'] ?? '';
        $reportdate = $_POST['reportDate'] ?? '';
        $division_id = $_POST['division_id'] ?? '';
        $depot_id = $_POST['depot_id'] ?? '';

        if (empty($bus_number) || empty($route_number) || empty($driver_token_1) || empty($logsheet_no) || empty($km_operated) || empty($hsd) || empty($kmpl)) {
            echo json_encode(["status" => "error", "message" => "Required fields are missing"]);
            exit;
        }

        if (!empty($id)) {
            $stmt = $db->prepare("UPDATE vehicle_kmpl SET route_no=?, driver_1_pf=?, driver_2_pf=?, logsheet_no=?, km_operated=?, hsd=?, kmpl=?, thumps_id=?, remarks=?, v_change=?, c_change=? WHERE id=? AND bus_number=? AND `date`=? AND division_id=? AND depot_id=?");
            $stmt->bind_param("ssssssssssssssss", $route_number, $driver_token_1, $driver_token_2, $logsheet_no, $km_operated, $hsd, $kmpl, $thump_status, $logsheet_defects, $vc, $cc, $id, $bus_number, $reportdate, $division_id, $depot_id);
        } else {
            // If no ID is provided, insert a new record
            //before inserting, check if the record already exists check logsheet no for the report data is exisist then update else insert
            $checkStmt = $db->prepare("SELECT id FROM vehicle_kmpl WHERE logsheet_no=? AND `date`=? AND division_id=? AND depot_id=? and deleted != '1' LIMIT 1");
            $checkStmt->bind_param("ssss", $logsheet_no, $reportdate, $division_id, $depot_id);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                // Record exists, update it
                // fetch the id of the existing record
                $checkStmt->bind_result($existing_id);
                $checkStmt->fetch();
                $checkStmt->close();

                // Update the existing record

                $stmt = $db->prepare("UPDATE vehicle_kmpl SET route_no=?, driver_1_pf=?, driver_2_pf=?, logsheet_no=?, km_operated=?, hsd=?, kmpl=?, thumps_id=?, remarks=?, v_change=?, c_change=? WHERE id=?");
                $stmt->bind_param("ssssssssssss", $route_number, $driver_token_1, $driver_token_2, $logsheet_no, $km_operated, $hsd, $kmpl, $thump_status, $logsheet_defects, $vc, $cc, $existing_id);
            } else {
                // Record does not exist, insert a new one
                $stmt = $db->prepare("INSERT INTO vehicle_kmpl (bus_number, route_no, driver_1_pf, driver_2_pf, logsheet_no, km_operated, hsd, kmpl, thumps_id, remarks, date, division_id, depot_id, v_change, c_change) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssssss", $bus_number, $route_number, $driver_token_1, $driver_token_2, $logsheet_no, $km_operated, $hsd, $kmpl, $thump_status, $logsheet_defects, $reportdate, $division_id, $depot_id, $vc, $cc);
            }
        }

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => !empty($id) ? "Record updated successfully" : "Record inserted successfully",
                "id" => $db->insert_id ?: $id,
                "vc" => $vc,
                "cc" => $cc
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Network Error"]);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Exception: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}
