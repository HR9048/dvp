<?php
include '../includes/connection.php';  // Include DB connection

$id = $_GET['id'];
$query = "SELECT * FROM schedule_master WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
echo json_encode($data);
?>
