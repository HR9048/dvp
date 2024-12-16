<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();

// Get the pf_number from the AJAX request
if (isset($_GET['pf_number'])) {
    $pfNumber = $_GET['pf_number'];

    // Query to check if the PF number exists in any of the schedule_master fields
    $sql = "SELECT sch_key_no , sch_count 
            FROM schedule_master 
            WHERE driver_pf_1 = ? 
               OR driver_pf_2 = ? 
               OR driver_pf_3 = ? 
               OR driver_pf_4 = ? 
               OR driver_pf_5 = ? 
               OR driver_pf_6 = ? 
               OR conductor_pf_1 = ? 
               OR conductor_pf_2 = ? 
               OR conductor_pf_3 = ? 
               OR offreliverdriver_pf_1 = ? 
               OR offreliverdriver_pf_2 = ? 
               OR offreliverconductor_pf_1 = ?";

    // Prepare and execute the statement
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ssssssssssss', $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber, $pfNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the result and count total schedule counts
    if ($result->num_rows > 0) {
        $data = [];
        $totalScheduleCount = 0;

        // Loop through the results and calculate total schedule counts
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
            $totalScheduleCount += $row['sch_count']; // Add the sch_count value to the total
        }

        // Check if the total schedule count is greater than 6
        if ($totalScheduleCount > 5) {
            // Send the data if total schedule count exceeds 6
            echo json_encode(['schedule' => $data]);
        } else {
            // Return a message indicating the total count is not sufficient
            echo json_encode(['message' => 'Total schedule count is less than or equal to 6']);
        }
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
