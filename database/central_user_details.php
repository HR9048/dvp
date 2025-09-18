<?php
define('API_KEY', '20170472417');

if (!isset($_GET['key']) || $_GET['key'] !== API_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pf_number = isset($_GET['pf_number']) ? $_GET['pf_number'] : '';

if (empty($pf_number)) {
    echo json_encode(['error' => 'PF Number is required']);
    exit;
}

// Example database query
include '../includes/connection.php';

$stmt = $db->prepare("SELECT u.`PF_ID` as pf_number, u.`USERNAME` as user_name, u.`PASSWORD` as password, j.JOB_TITLE as job_title FROM users u LEFT JOIN employee e on e.pf_number=u.PF_ID LEFT JOIN job j on j.JOB_ID= e.JOB_ID WHERE u.`PF_ID` = ?");
$stmt->bind_param('s', $pf_number);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['user' => $row]);
} else {
    echo json_encode(['user' => null]);
}
