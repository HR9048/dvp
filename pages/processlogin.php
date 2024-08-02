<?php
require ('../includes/connection.php');
require ('session.php');
if (logged_in()) {
    // If user is already logged in, redirect to appropriate page
    header("Location: " . determineRedirect($_SESSION['TYPE'], $_SESSION['JOB_TITLE']));
    exit();
}

// Start the session

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user'], $_POST['password'])) { // Check if it's a POST request and if username and password are set
    $users = trim($_POST['user']); // Retrieve username
    $upass = trim($_POST['password']); // Retrieve password

    // Check if password is missing
    if ($upass == '') {
        echo json_encode(array("status" => "error", "message" => "Password is missing!"));
        exit(); // Stop further execution
    } else {
        $sql = "SELECT ID, e.FIRST_NAME, e.LAST_NAME, e.GENDER, e.EMAIL, e.PHONE_NUMBER, j.JOB_TITLE, l.DIVISION, l.DEPOT,l.division_id, l.depot_id,l.kmpl_division,l.kmpl_depot, t.TYPE
        FROM  `users` u
        JOIN `employee` e ON e.PF_ID=u.PF_ID
        JOIN `location` l ON e.LOCATION_ID=l.LOCATION_ID
        JOIN `job` j ON e.JOB_ID=j.JOB_ID
        JOIN `type` t ON t.TYPE_ID=u.TYPE_ID
        WHERE BINARY `USERNAME` ='" . $users . "' AND BINARY `PASSWORD` =  '" . $upass . "'";
        $result = $db->query($sql);

        if ($result && $result->num_rows > 0) {
            $found_user = mysqli_fetch_array($result);
            // Store user data in session variables
            $_SESSION['MEMBER_ID'] = $found_user['ID'];
            $_SESSION['FIRST_NAME'] = $found_user['FIRST_NAME'];
            $_SESSION['LAST_NAME'] = $found_user['LAST_NAME'];
            $_SESSION['GENDER'] = $found_user['GENDER'];
            $_SESSION['EMAIL'] = $found_user['EMAIL'];
            $_SESSION['PHONE_NUMBER'] = $found_user['PHONE_NUMBER'];
            $_SESSION['JOB_TITLE'] = $found_user['JOB_TITLE'];
            $_SESSION['DIVISION'] = $found_user['DIVISION'];
            $_SESSION['DEPOT'] = $found_user['DEPOT'];
            $_SESSION['DIVISION_ID'] = $found_user['division_id'];
            $_SESSION['DEPOT_ID'] = $found_user['depot_id'];
            $_SESSION['KMPL_DIVISION'] = $found_user['kmpl_division'];
            $_SESSION['KMPL_DEPOT'] = $found_user['kmpl_depot'];
            $_SESSION['TYPE'] = $found_user['TYPE'];
            $_SESSION['USERNAME'] = $users;

            // Send JSON response
            echo json_encode(array("status" => "success", "message" => $_SESSION['FIRST_NAME'] . " Welcome!", "redirect" => determineRedirect($_SESSION['TYPE'], $_SESSION['JOB_TITLE'])));
            exit();
        } else {
            echo json_encode(array("status" => "error", "message" => "Invalid Username or Password! Please try again."));
            exit(); // Stop further execution
        }
    }
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}


// Function to determine redirect URL based on user type and job title
function determineRedirect($type, $jobTitle)
{
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
                case 'INSPECTOR':
                    return "depot_kmpl.php";
                case 'SECURITY':
                    return "depot_schinout.php";
            }
    }
}

?>
