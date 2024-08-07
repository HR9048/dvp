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
                JOIN `employee` e ON e.PF_ID = u.PF_ID
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

                // Store user data in session variables
                $_SESSION['MEMBER_ID'] = $found_user['ID'];
                $_SESSION['FIRST_NAME'] = $user_details['FIRST_NAME'];
                $_SESSION['LAST_NAME'] = $user_details['LAST_NAME'];
                $_SESSION['GENDER'] = $user_details['GENDER'];
                $_SESSION['EMAIL'] = $user_details['EMAIL'];
                $_SESSION['PHONE_NUMBER'] = $user_details['PHONE_NUMBER'];
                $_SESSION['JOB_TITLE'] = $user_details['JOB_TITLE'];
                $_SESSION['DIVISION'] = $user_details['DIVISION'];
                $_SESSION['DEPOT'] = $user_details['DEPOT'];
                $_SESSION['DIVISION_ID'] = $user_details['division_id'];
                $_SESSION['DEPOT_ID'] = $user_details['depot_id'];
                $_SESSION['KMPL_DIVISION'] = $user_details['kmpl_division'];
                $_SESSION['KMPL_DEPOT'] = $user_details['kmpl_depot'];
                $_SESSION['TYPE'] = $user_details['TYPE'];
                $_SESSION['USERNAME'] = $users;
                $_SESSION['PASSWORD'] = $upass;

                // Determine redirect URL
                $redirectUrl = determineRedirect($db, $_SESSION['TYPE'], $_SESSION['JOB_TITLE'], $_SESSION['MEMBER_ID']);
                
                // Send JSON response
                echo json_encode(array("status" => "success", "message" => $_SESSION['FIRST_NAME'] . " Welcome!", "redirect" => $redirectUrl));
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
                return "index.php";
            case 'RWY':
                return "rwy.php";
            case 'DIVISION':
                return "division.php";
            case 'DEPOT':
                switch ($jobTitle) {
                    case 'DM':
                        return "depot_manager.php";
                    case 'Mech':
                        return "depot_clerk.php";
                    case 'Bunk':
                        return "depot_kmpl.php";
                    case 'T_INSPECTOR':
                        return "depot_inspector_schedule_d.php";
                    case 'SECURITY':
                        return "depot_schinout.php";
                }
        }
    }

    // Default redirect if password fetch fails or doesn't match criteria
    return "index.php";
}

?>
