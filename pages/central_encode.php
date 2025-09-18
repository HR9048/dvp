<?php
$receivedKey = $_GET['key'] ?? '';
$loginStatus = $_GET['login_status'] ?? '';

if ($loginStatus !== 'success' || empty($receivedKey)) {
    exit('Access Denied');
}

$decodedKey = base64_decode($receivedKey);
list($secretKey, $pf_number) = explode('|', $decodedKey);

if ($secretKey !== '20170472417') {
    exit('Invalid key');
}

require('../includes/connection.php');
require('session.php');


$pf_number = trim($pf_number);

$stmt = $db->prepare("
    SELECT u.ID, e.FIRST_NAME, e.LAST_NAME, e.GENDER, e.EMAIL, e.PHONE_NUMBER, j.JOB_TITLE, 
           l.DIVISION, l.DEPOT, l.division_id, l.depot_id, l.kmpl_division, l.kmpl_depot, 
           t.TYPE, u.PASSWORD
    FROM `users` u
    JOIN `employee` e ON e.pf_number = u.PF_ID
    JOIN `location` l ON e.LOCATION_ID = l.LOCATION_ID
    JOIN `job` j ON e.JOB_ID = j.JOB_ID
    JOIN `type` t ON t.TYPE_ID = u.TYPE_ID
    WHERE u.PF_ID = ?
");

$stmt->bind_param('s', $pf_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user_details = $result->fetch_assoc();

    $session_token = bin2hex(random_bytes(32));
    date_default_timezone_set('Asia/Kolkata');
    $expires_at = date('Y-m-d H:i:s', time() + 7200);

    $stmt = $db->prepare("INSERT INTO sessions (session_token, user_id, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $session_token, $user_details['ID'], $expires_at);
    $stmt->execute();

    setcookie("dvp_session_token", $session_token, time() + 7200, "/", "", false, true);

    $redirectUrl = determineRedirect($db, $user_details['TYPE'], $user_details['JOB_TITLE'], $user_details['ID']);

    // Perform direct redirect
    header("Location: http://117.251.26.11:8880/dvp_test/pages/$redirectUrl");
    exit();
} else {
    echo "Error fetching user details.";
    exit();
}

function determineRedirect($db, $type, $jobTitle, $member_id)
{
    $stmt = $db->prepare("SELECT PASSWORD FROM users WHERE ID = ?");
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_password = $result->fetch_assoc()['PASSWORD'];

        if (ctype_digit($user_password) || ctype_alpha($user_password) || !preg_match('/[^\w]/', $user_password)) {
            return "update_password.php";
        }

        switch ($type) {
            case 'HEAD-OFFICE':
                return "index.php";
            case 'RWY':
                return "rwy.php";
            case 'DIVISION':
                return "division.php";
            case 'DEPOT':
                switch ($jobTitle) {
                    case 'DM':
                    case 'Mech':
                    case 'T_INSPECTOR':
                        return "depot_dashboard.php";
                    case 'Bunk':
                        return "depot_kmpl.php";
                    case 'SECURITY':
                        return "depot_schinout.php";
                }
        }
    }

    return "index.php";
}
