<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();

// Get POST data from the AJAX request
$division_id = $_SESSION['DIVISION_ID'];
$depot_id = $_SESSION['DEPOT_ID'];
$pf_number = $_POST['pf_number'];
$token_number = $_POST['token_number'];
$old_schedule_no = $_POST['old_schedule'];


// Check if required data is received
if (empty($pf_number) || empty($token_number) || empty($old_schedule_no)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit;
}

// Prepare the SQL query to nullify matching PF numbers and related driver and conductor details
$sql = "UPDATE schedule_master 
        SET
            driver_token_1 = CASE WHEN driver_pf_1 = ? THEN NULL ELSE driver_token_1 END,
            driver_token_2 = CASE WHEN driver_pf_2 = ? THEN NULL ELSE driver_token_2 END,
            driver_token_3 = CASE WHEN driver_pf_3 = ? THEN NULL ELSE driver_token_3 END,
            driver_token_4 = CASE WHEN driver_pf_4 = ? THEN NULL ELSE driver_token_4 END,
            driver_token_5 = CASE WHEN driver_pf_5 = ? THEN NULL ELSE driver_token_5 END,
            driver_token_6 = CASE WHEN driver_pf_6 = ? THEN NULL ELSE driver_token_6 END,
            driver_name_1 = CASE WHEN driver_pf_1 = ? THEN NULL ELSE driver_name_1 END,
            driver_name_2 = CASE WHEN driver_pf_2 = ? THEN NULL ELSE driver_name_2 END,
            driver_name_3 = CASE WHEN driver_pf_3 = ? THEN NULL ELSE driver_name_3 END,
            driver_name_4 = CASE WHEN driver_pf_4 = ? THEN NULL ELSE driver_name_4 END,
            driver_name_5 = CASE WHEN driver_pf_5 = ? THEN NULL ELSE driver_name_5 END,
            driver_name_6 = CASE WHEN driver_pf_6 = ? THEN NULL ELSE driver_name_6 END,
            conductor_token_1 = CASE WHEN conductor_pf_1 = ? THEN NULL ELSE conductor_token_1 END,
            conductor_token_2 = CASE WHEN conductor_pf_2 = ? THEN NULL ELSE conductor_token_2 END,
            conductor_token_3 = CASE WHEN conductor_pf_3 = ? THEN NULL ELSE conductor_token_3 END,
            conductor_name_1 = CASE WHEN conductor_pf_1 = ? THEN NULL ELSE conductor_name_1 END,
            conductor_name_2 = CASE WHEN conductor_pf_2 = ? THEN NULL ELSE conductor_name_2 END,
            conductor_name_3 = CASE WHEN conductor_pf_3 = ? THEN NULL ELSE conductor_name_3 END,
            driver_pf_1 = CASE WHEN driver_pf_1 = ? THEN NULL ELSE driver_pf_1 END,
            driver_pf_2 = CASE WHEN driver_pf_2 = ? THEN NULL ELSE driver_pf_2 END,
            driver_pf_3 = CASE WHEN driver_pf_3 = ? THEN NULL ELSE driver_pf_3 END,
            driver_pf_4 = CASE WHEN driver_pf_4 = ? THEN NULL ELSE driver_pf_4 END,
            driver_pf_5 = CASE WHEN driver_pf_5 = ? THEN NULL ELSE driver_pf_5 END,
            driver_pf_6 = CASE WHEN driver_pf_6 = ? THEN NULL ELSE driver_pf_6 END,
            conductor_pf_1 = CASE WHEN conductor_pf_1 = ? THEN NULL ELSE conductor_pf_1 END,
            conductor_pf_2 = CASE WHEN conductor_pf_2 = ? THEN NULL ELSE conductor_pf_2 END,
            conductor_pf_3 = CASE WHEN conductor_pf_3 = ? THEN NULL ELSE conductor_pf_3 END
        WHERE sch_key_no = ?
        AND division_id = ?
        AND depot_id = ?";

// Prepare statement
$stmt = $db->prepare($sql);
if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement']);
    exit;
}

// Bind parameters
$stmt->bind_param(
    "ssssssssssssssssssssssssssssss", 
    $pf_number, $pf_number, $pf_number, $pf_number, 
    $pf_number, $pf_number, $pf_number, $pf_number, 
    $pf_number, $pf_number, $pf_number, $pf_number, 
    $pf_number, $pf_number, $pf_number, $pf_number, 
    $pf_number, $pf_number, $pf_number, $pf_number, 
    $pf_number, $pf_number, $pf_number, $pf_number, 
    $pf_number, $pf_number, $pf_number, 
    $old_schedule_no, $division_id, $depot_id
);
// Execute the statement
if ($stmt->execute()) {
    // Update crew_data_fix table
    $current_datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $formatted_datetime = $current_datetime->format('Y-m-d H:i:s');
    
    $updateCrewDataSql = "UPDATE crew_fix_data
                          SET to_date = ? 
                          WHERE sch_key_no = ? 
                          AND division_id = ? 
                          AND depot_id = ? 
                          and crew_pf =?
                          AND to_date IS NULL";
    
    $updateCrewStmt = $db->prepare($updateCrewDataSql);
    if ($updateCrewStmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare update statement for crew_data_fix']);
        exit;
    }
    
    $updateCrewStmt->bind_param('sssss', $formatted_datetime, $old_schedule_no, $division_id, $depot_id, $pf_number);
    
    if ($updateCrewStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Driver/DCC and Conductor reallocated successfully and crew_data_fix updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating crew_data_fix record: ' . $db->error]);
    }

    // Close the update statement for crew_data_fix
    $updateCrewStmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating schedule_master record: ' . $db->error]);
}

// Close the original statement and connection
$stmt->close();
$db->close();
?>