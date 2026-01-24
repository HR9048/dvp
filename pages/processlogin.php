<?php
require('../includes/connection.php');
require('session.php');

if (logged_in()) {
    // If user is already logged in, redirect to the appropriate page
    header("Location: " . determineRedirect($db, $_SESSION['TYPE'], $_SESSION['JOB_TITLE'], $_SESSION['MEMBER_ID']));
    exit();
}

// Start the session
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user'], $_POST['password'])) {
    $users = trim($_POST['user']); // Retrieve username
    $upass = trim($_POST['password']); // Retrieve password

    // Check if password is missing
    if ($upass == '') {
        echo json_encode(array("status" => "error", "message" => "Password is missing!"));
        exit(); // Stop further execution
    } else {
        // Prepare SQL statement to check username and password
        $stmt = $db->prepare("
            SELECT ID 
            FROM `users` 
            WHERE BINARY `USERNAME` = ? AND BINARY `PASSWORD` = ?
        ");

        $stmt->bind_param('ss', $users, $upass); // Bind parameters
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Username and password match found, fetch additional details
            $found_user = $result->fetch_assoc();

            // Prepare SQL statement to fetch additional user details
            $stmt = $db->prepare("
                SELECT e.FIRST_NAME, e.LAST_NAME, e.GENDER, e.EMAIL, e.PHONE_NUMBER, j.JOB_TITLE, 
                       l.DIVISION, l.DEPOT, l.division_id, l.depot_id, l.kmpl_division, l.kmpl_depot, 
                       t.TYPE, u.PASSWORD
                FROM `users` u
                JOIN `employee` e ON e.pf_number = u.PF_ID
                JOIN `location` l ON e.LOCATION_ID = l.LOCATION_ID
                JOIN `job` j ON e.JOB_ID = j.JOB_ID
                JOIN `type` t ON t.TYPE_ID = u.TYPE_ID
                WHERE BINARY u.USERNAME = ? AND BINARY u.PASSWORD = ?
            ");

            $stmt->bind_param('ss', $users, $upass); // Bind parameters
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user_details = $result->fetch_assoc();

                $session_token = bin2hex(random_bytes(32));
                date_default_timezone_set('Asia/Kolkata');
                $expires_at = date('Y-m-d H:i:s', time() + 7200); // 1 hour

                // Store session in DB
                $stmt = $db->prepare("INSERT INTO sessions (session_token, user_id, expires_at) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $session_token, $found_user['ID'], $expires_at);
                $stmt->execute();

                // Set cookie
                setcookie("dvp_session_token", $session_token, time() + 7200, "/", "", false, true);
                // Determine redirect URL


                 $redirectUrl = determineRedirect($db, $user_details['TYPE'], $user_details['JOB_TITLE'], $found_user['ID']);

                // Send JSON response
                echo json_encode(array("status" => "success", "message" => $user_details['FIRST_NAME'] . " Welcome!", "redirect" => $redirectUrl));
                exit();
            } else {
                echo json_encode(array("status" => "error", "message" => "Error fetching user details."));
                exit(); // Stop further execution
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "Invalid Username or Password! Please try again."));
            exit(); // Stop further execution
        }
    }
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

// Function to determine redirect URL based on user type, job title, and password
function determineRedirect($db, $type, $jobTitle, $member_id)
{
    // Fetch the user's password from the database
    $stmt = $db->prepare("SELECT PASSWORD FROM users WHERE ID = ?");
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_password = $result->fetch_assoc()['PASSWORD'];

        // Check if the password is only numeric, only alphabetic, or lacks special characters
        if (ctype_digit($user_password) || ctype_alpha($user_password) || !preg_match('/[^\w]/', $user_password)) {
            return "update_password.php";
        }

        // Determine redirect URL based on user type and job title
        switch ($type) {
            case 'HEAD-OFFICE':
                switch ($member_id) {
                    case '1':
                        return "admin_select.php";
                    default:
                        return "index.php";
                }
            case 'RWY':
                return "rwy.php";
            case 'DIVISION':
                return "division.php";
            case 'DEPOT':
                switch ($jobTitle) {
                    case 'DM':
                        return "depot_dashboard.php";
                    case 'Mech':
                        return "depot_dashboard.php";
                    case 'Bunk':
                        return "depot_kmpl.php";
                    case 'T_INSPECTOR':
                        return "depot_dashboard.php";
                    case 'SECURITY':
                        return "depot_schinout.php";
                }
        }
    }

    // Default redirect if password fetch fails or doesn't match criteria
    return "index.php";
}
