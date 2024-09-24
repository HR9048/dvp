<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();
if (isset($_POST['busNumber'])) {
    $busNumber = $_POST['busNumber'];

    $query = "SELECT sch_key_no FROM schedule_master WHERE bus_number_1 = ? OR bus_number_2 = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ss', $busNumber, $busNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['exists' => true, 'sch_key_no' => $row['sch_key_no']]);
    } else {
        echo json_encode(['exists' => false]);
    }
}
?>
