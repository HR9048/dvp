<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();

if (isset($_POST['busNumber'])) {
    $busNumber = $_POST['busNumber'];

    // Query to get the service_type_id and count of the same bus number occurrences
    $query = "SELECT sm.sch_key_no, sm.service_type_id, 
                     (SELECT COUNT(*) FROM schedule_master WHERE (bus_number_1 = ? OR bus_number_2 = ?) AND service_type_id IN (1,2, 3, 4)) AS sameBusCount
              FROM schedule_master sm
              WHERE bus_number_1 = ? OR bus_number_2 = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ssss', $busNumber, $busNumber, $busNumber, $busNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $sameBusCount = $row['sameBusCount'];
        $serviceTypeId = $row['service_type_id'];

        // If service type is 2, 3, or 4, check the count of bus number occurrences
        if (($serviceTypeId == 2 || $serviceTypeId == 3 || $serviceTypeId == 4)) {
            if ($sameBusCount >= 2) {
                // Allow if there are 2 or more occurrences
                echo json_encode(['exists' => true, 'sch_key_no' => $row['sch_key_no']]);
            } else {
                // If there's only 1 occurrence, do not allow
                echo json_encode(['exists' => false]);
            }
        } elseif (($serviceTypeId == 1)) {
            if ($sameBusCount >= 1) {
                echo json_encode(['exists' => true, 'sch_key_no' => $row['sch_key_no']]);
            } else {
                // For other service types, only allow if no existing bus number
                echo json_encode(['exists' => false]);
            }
        }
    } else {
        echo json_encode(['exists' => false]);
    }
}
?>