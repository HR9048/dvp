<?php
require('../includes/connection.php');
date_default_timezone_set('Asia/Kolkata');

// Extend PHP session lifetime
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

session_start();

// 1. Clear any existing session variables
$_SESSION = [];

// 2. Check if session_token cookie exists
if (isset($_COOKIE['dvp_session_token'])) {
    $token = $_COOKIE['dvp_session_token'];

    // 3. Validate token in DB
    $stmt = $db->prepare("
        SELECT s.user_id, s.expires_at, s.logged_out_at,
               u.USERNAME, u.PASSWORD, t.TYPE, 
               e.FIRST_NAME, e.LAST_NAME, e.GENDER, e.EMAIL, e.PHONE_NUMBER, 
               j.JOB_TITLE, l.DIVISION, l.DEPOT, l.division_id, l.depot_id, 
               l.kmpl_division, l.kmpl_depot
        FROM sessions s
        JOIN users u ON s.user_id = u.ID
        JOIN employee e ON e.PF_ID = u.PF_ID
        JOIN job j ON e.JOB_ID = j.JOB_ID
        JOIN location l ON e.LOCATION_ID = l.LOCATION_ID
        JOIN type t ON u.TYPE_ID = t.TYPE_ID
        WHERE s.session_token = ? AND s.expires_at > NOW() AND s.logged_out_at IS NULL
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 4. Set session variables
        $_SESSION['MEMBER_ID']      = $user['user_id'];
        $_SESSION['FIRST_NAME']     = $user['FIRST_NAME'];
        $_SESSION['LAST_NAME']      = $user['LAST_NAME'];
        $_SESSION['GENDER']         = $user['GENDER'];
        $_SESSION['EMAIL']          = $user['EMAIL'];
        $_SESSION['PHONE_NUMBER']   = $user['PHONE_NUMBER'];
        $_SESSION['JOB_TITLE']      = $user['JOB_TITLE'];
        $_SESSION['DIVISION']       = $user['DIVISION'];
        $_SESSION['DEPOT']          = $user['DEPOT'];
        $_SESSION['TYPE']           = $user['TYPE'];
        $_SESSION['USERNAME']       = $user['USERNAME'];
        $_SESSION['DIVISION_ID']    = $user['division_id'];
        $_SESSION['DEPOT_ID']       = $user['depot_id'];
        $_SESSION['KMPL_DIVISION']  = $user['kmpl_division'];
        $_SESSION['KMPL_DEPOT']     = $user['kmpl_depot'];

        // ✅ Session is valid
        echo json_encode(['status' => 'active']);
        exit;
    }
}

// ❌ Session expired or invalid
echo json_encode(['status' => 'expired']);
exit;
