<?php
// Include the necessary files
include '../includes/connection.php'; // Include the file where the database connection is established
include 'session.php'; // Include the file for session management

// Create an associative array to store response data
$response = array();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $date = $_POST['date'];
    $schedules = $_POST['schdules'];
    $vehicles = $_POST['vehicles'];
    $spare = $_POST['spare'];
    $spareP = $_POST['spareP'];
    $docking = $_POST['docking'];
    $wup = $_POST['wup'];
    $ORDepot = $_POST['ORDepot'];
    $ORDWS = $_POST['ORDWS'];
    $ORRWY = $_POST['ORRWY'];
    $CC = $_POST['CC'];
    $loan = $_POST['loan'];
    $Police = $_POST['Police'];
    $Dealer = $_POST['Dealer'];
    $notdepot = $_POST['notdepot'];
    $ORTotal = $_POST['ORTotal'];
    $available = $_POST['available'];
    $ES = $_POST['E/S'];
    // Retrieve other form data similarly
// Retrieve username and designation from session data
    if (!isset($_SESSION['USERNAME']) || !isset($_SESSION['DIVISION_ID']) || !isset($_SESSION['DEPOT_ID'])) {
        // If session variables are not set, logout the user
        session_destroy();
        header("Location: logout.php");
        exit;
    }
    // Retrieve username and designation from session data
    $username = $_SESSION['USERNAME'];
    $designation = $_SESSION['JOB_TITLE'];
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];

    // Get current date and time
    date_default_timezone_set('Asia/Kolkata'); // Set the timezone to Indian Standard Time (IST)
    $submissionDateTime = date('Y-m-d H:i:s'); // Get the current date and time in Bangalore (IST)    

    // Check if the date already exists
    $query = mysqli_query($db, "SELECT * FROM dvp_data WHERE date='$date' && division='$division' && depot='$depot'");
    $count = mysqli_num_rows($query);
    if ($count > 0) {
        // Return a message indicating that the date DVP has already been submitted
        $response['status'] = 'error';
        $response['message'] = 'Selected date DVP already submitted. Please check the Print DVP page.';
    } else {
        // Validate form data
        if (
            empty($date) ||
            empty($schedules) ||
            empty($vehicles) ||
            !isset($spare) ||
            !isset($spareP) ||
            !isset($docking) ||
            !isset($wup) ||
            !isset($ORDepot) ||
            !isset($ORDWS) ||
            !isset($ORRWY) ||
            !isset($CC) ||
            !isset($loan) ||
            !isset($Police) ||
            !isset($Dealer) ||
            !isset($notdepot) ||
            !isset($ORTotal) ||
            !isset($available) ||
            !isset($ES)
        ) {
            // Return an error message if required fields are empty
            $response['status'] = 'error';
            $response['message'] = 'Please fill all required fields.';
        } else {
            // Prepared statement to prevent SQL injection
            $stmt = $db->prepare("INSERT INTO dvp_data (date, schedules, vehicles, spare, spareP, docking, wup, ORDepot, ORDWS, ORRWY, CC, Police, Dealer, notdepot, ORTotal, available, ES, division, depot, username, designation, submission_datetime, loan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            // Bind parameters
            $stmt->bind_param("siiidiiiiiiiiiiiiiisssi", $date, $schedules, $vehicles, $spare, $spareP, $docking, $wup, $ORDepot, $ORDWS, $ORRWY, $CC, $Police, $Dealer, $notdepot, $ORTotal, $available, $ES, $division, $depot, $username, $designation, $submissionDateTime, $loan);

            // Execute prepared statement
            if ($stmt->execute()) {
                // Return a success message
                $response['status'] = 'success';
                $response['message'] = 'Data inserted successfully!';
            } else {
                // Return an error message if execution fails
                $response['status'] = 'error';
                $response['message'] = 'Error: ' . $stmt->error;
            }

            // Close statement
            $stmt->close();
        }
    }
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

// Return JSON response
echo json_encode($response);
?>