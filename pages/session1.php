<?php
// Increase session lifetime to 1 hour (3600 seconds)
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Start session
session_start();

// Function to check if session variables are set
function logged_in() {
    return isset($_SESSION['MEMBER_ID']) && isset($_SESSION['DIVISION_ID']) && isset($_SESSION['DEPOT_ID']) && 
           isset($_SESSION['JOB_TITLE']) && isset($_SESSION['TYPE']) && isset($_SESSION['DIVISION']) && 
           isset($_SESSION['DEPOT']) && isset($_SESSION['KMPL_DIVISION']) && isset($_SESSION['KMPL_DEPOT']) && 
           isset($_SESSION['USERNAME']);
}

// Return JSON response for session status
header('Content-Type: application/json');

if (logged_in()) {
    echo json_encode(['status' => 'active']);
} else {
    echo json_encode(['status' => 'expired']);
}
?>
