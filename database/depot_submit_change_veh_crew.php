<?php
include '../pages/session.php';
// Check if the request is authorized
if (!isset($_SESSION['USERNAME'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Include database connection
require '../INCLUDES/connection.php'; // Replace with your actual DB connection script

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data with null coalescing to handle missing inputs
    $sch_out_id = $_POST['id'] ?? null;
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $arr_time = $_POST['arr_time'] ?? null;
    $reason = $_POST['reason'] ?? null;
    $other_reason = $_POST['otherreason'] ?? null;

    $change_vehicle = isset($_POST['change_vehicle']) ? 1 : 0; // 1 if checked, 0 if not
    $change_driver = isset($_POST['change_driver']) ? 1 : 0;
    $change_driver2 = isset($_POST['change_driver2']) ? 1 : 0;
    $change_conductor = isset($_POST['change_conductor']) ? 1 : 0;
    // Present data
    $present_vehicle_no = $_POST['vehicle_no'] ?? null;
    $present_driver_1_pf_no = $_POST['driver_1_pf'] ?? null;

    $present_driver_2_pf_no = $_POST['driver_2_pf'] ?? null;

    $present_conductor_pf_no = $_POST['conductor_pf_no'] ?? null;

    // Changed data
    $changed_vehicle_no = $_POST['bus_select'] ?? null;
    $changed_driver_1_pf_no = $_POST['driver_1_select'] ?? null;

    $changed_driver_2_pf_no = $_POST['driver_2_select'] ?? null;

    $changed_conductor_pf_no = $_POST['conductorselect'] ?? null;



$missing_fields = [];

if (!$sch_out_id) {
    $missing_fields[] = 'Schedule Out ID';
}
if (!$division_id) {
    $missing_fields[] = 'Division ID';
}
if (!$depot_id) {
    $missing_fields[] = 'Depot ID';
}
if (!$arr_time) {
    $missing_fields[] = 'Arrival Time';
}
if (!$reason) {
    $missing_fields[] = 'Reason';
}

if (!empty($missing_fields)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'The following fields are missing: ' . implode(', ', $missing_fields)
    ]);
    exit;
}


    // If "Other" reason is selected, ensure other_reason is provided
    if ($reason == 'Others' && !$other_reason) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide details for other reason.']);
        exit;
    }

    // Final reason handling
    $final_reason = $reason === 'Others' ? $other_reason : $reason;

    // Prepare the SQL query
    $query = "
    INSERT INTO sch_change_data (
        sch_out_id, division_id, depot_id, 
        present_vehicle_no, changed_vehicle_no, 
        present_driver_1_name, present_driver_1_token_no, present_driver_1_pf_no,
        changed_driver_1_name, changed_driver_1_token_no, changed_driver_1_pf_no,
        present_driver_2_name, present_driver_2_token_no, present_driver_2_pf_no,
        changed_driver_2_name, changed_driver_2_token_no, changed_driver_2_pf_no,
        present_conductor_name, present_conductor_token_no, present_conductor_pf_no,
        changed_conductor_name, changed_conductor_token_no, changed_conductor_pf_no,
        change_reason, changed_time
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

// Prepare and bind the statement
if ($stmt = $db->prepare($query)) {
    $stmt->bind_param(
        'sssssssssssssssssssssssss',
        $sch_out_id, $division_id, $depot_id,
        $present_vehicle_no, $changed_vehicle_no,
        $present_driver_1_name, $present_driver_1_token_no, $present_driver_1_pf_no,
        $changed_driver_1_name, $changed_driver_1_token_no, $changed_driver_1_pf_no,
        $present_driver_2_name, $present_driver_2_token_no, $present_driver_2_pf_no,
        $changed_driver_2_name, $changed_driver_2_token_no, $changed_driver_2_pf_no,
        $present_conductor_name, $present_conductor_token_no, $present_conductor_pf_no,
        $changed_conductor_name, $changed_conductor_token_no, $changed_conductor_pf_no,
        $final_reason, $arr_time
    );

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to insert data: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the query: ' . $db->error]);
}

$db->close();
} else {
echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}