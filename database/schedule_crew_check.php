<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();
// Get the pf_number from the AJAX request
if (isset($_GET['pf_number'])) {
    $pfNumber = $_GET['pf_number'];

    // Query to check if the PF number exists in any of the schedule_master fields
    $sql = "SELECT sch_key_no 
            FROM schedule_master 
            WHERE driver_pf_1 = ? 
               OR driver_pf_2 = ? 
               OR driver_pf_3 = ? 
               OR driver_pf_4 = ? 
               OR driver_pf_5 = ? 
               OR driver_pf_6 = ? 
               OR conductor_pf_1 = ? 
               OR conductor_pf_2 = ? 
               OR conductor_pf_3 = ?";

    // Prepare and execute the statement
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sssssssss', $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the result
    if ($result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // Return the schedule details if found
        echo json_encode(['schedule' => $data]);
    } else {
        // Return empty array if no match is found
        echo json_encode(['schedule' => []]);
    }

    // Close the statement
    $stmt->close();
}

// Close the database connection
$db->close();
?>
