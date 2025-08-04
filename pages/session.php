<?php
require('../includes/connection.php');
date_default_timezone_set('Asia/Kolkata');

// Start native PHP session
session_start();

// Clear all existing $_SESSION variables
$_SESSION = [];

// Load session from token cookie
if (isset($_COOKIE['dvp_session_token'])) {
    $token = $_COOKIE['dvp_session_token'];

    $stmt = $db->prepare("
        SELECT s.user_id, u.USERNAME, u.PASSWORD, t.TYPE, j.JOB_TITLE,
               e.FIRST_NAME, e.LAST_NAME, e.GENDER, e.EMAIL, e.PHONE_NUMBER,
               l.DIVISION, l.DEPOT, l.division_id, l.depot_id, l.kmpl_division, l.kmpl_depot
        FROM sessions s
        JOIN users u ON s.user_id = u.ID
        JOIN employee e ON u.PF_ID = e.PF_ID
        JOIN job j ON e.JOB_ID = j.JOB_ID
        JOIN location l ON e.LOCATION_ID = l.LOCATION_ID
        JOIN type t ON t.TYPE_ID = u.TYPE_ID
        WHERE s.session_token = ? AND s.expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ✅ Set session variables
        $_SESSION['MEMBER_ID']      = $user['user_id'];
        $_SESSION['USERNAME']       = $user['USERNAME'];
        $_SESSION['TYPE']           = $user['TYPE'];
        $_SESSION['JOB_TITLE']      = $user['JOB_TITLE'];
        $_SESSION['FIRST_NAME']     = $user['FIRST_NAME'];
        $_SESSION['LAST_NAME']      = $user['LAST_NAME'];
        $_SESSION['EMAIL']          = $user['EMAIL'];
        $_SESSION['PHONE_NUMBER']   = $user['PHONE_NUMBER'];
        $_SESSION['GENDER']         = $user['GENDER'];
        $_SESSION['DIVISION']       = $user['DIVISION'];
        $_SESSION['DEPOT']          = $user['DEPOT'];
        $_SESSION['DIVISION_ID']    = $user['division_id'];
        $_SESSION['DEPOT_ID']       = $user['depot_id'];
        $_SESSION['KMPL_DIVISION']  = $user['kmpl_division'];
        $_SESSION['KMPL_DEPOT']     = $user['kmpl_depot'];
    }
}

// Check if required session values exist
function logged_in() {
    return isset($_SESSION['MEMBER_ID']) && isset($_SESSION['USERNAME'])  && isset($_SESSION['TYPE'])  && isset($_SESSION['JOB_TITLE'])  && isset($_SESSION['FIRST_NAME'])  && isset($_SESSION['LAST_NAME'])  && isset($_SESSION['DIVISION'])  && isset($_SESSION['DEPOT'])  && isset($_SESSION['DIVISION_ID'])  && isset($_SESSION['DEPOT_ID'])  && isset($_SESSION['KMPL_DIVISION'])  && isset($_SESSION['KMPL_DEPOT']);
}

// Enforce login
function confirm_logged_in() {
    if (!logged_in()) {
        echo "<script>
                alert('Session expired or missing necessary information. You will be logged out. Please login again.');
                window.location = 'logout.php';
              </script>";
        exit();
    }
}

