<?php
// Include the database connection file
include '../includes/connection.php';
include 'session.php';

// Check if the session variables are set
if (!isset($_SESSION['DIVISION_ID']) || !isset($_SESSION['DEPOT_ID'])) {
    // Display alert message and redirect to logout page
    echo '<script>alert("Session expired. Please login again."); window.location.href = "logout.php";</script>';
    exit; // Stop further execution
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $hsd = isset($_POST['hsd']) ? floatval($_POST['hsd']) : null;
    $totalKM = isset($_POST['totalKM']) ? floatval($_POST['totalKM']) : null;

    // Validate form data
    if (empty($date) || is_null($hsd) || is_null($totalKM) || $hsd == 0 || $totalKM == 0) {
        // Display alert message if any field is empty, null, or equal to 0
        echo '<script>alert("Date, HSD, and Total KM are required and should not be null or zero."); window.history.back();</script>';
        exit; // Stop further execution
    }

    // Check if the date already exists in the database
    $check_query = "SELECT * FROM kmpl_data WHERE date = '$date' AND division = '{$_SESSION['DIVISION_ID']}' AND depot = '{$_SESSION['DEPOT_ID']}'";
    $check_result = mysqli_query($db, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Date already exists in the database
        echo 'date_exists';
    } else {
        // Date does not exist, proceed with insertion
        $kmpl = isset($_POST['kmpl']) ? floatval($_POST['kmpl']) : null;
        $username = $_SESSION['USERNAME']; // Adjust this according to your session variable name
        $division = $_SESSION['DIVISION_ID']; // Adjust this according to your session variable name
        $depot = $_SESSION['DEPOT_ID']; // Adjust this according to your session variable name
        date_default_timezone_set('Asia/Kolkata'); // Set timezone to India/Kolkata
        $submitted_datetime = date("Y-m-d H:i:s"); // Get current date and time in India/Kolkata timezone

        // Prepare the SQL statement
        $stmt = $db->prepare("INSERT INTO kmpl_data (date, total_km, hsd, kmpl, username, division, depot, submitted_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind parameters
        $stmt->bind_param("sdddssss", $date, $totalKM, $hsd, $kmpl, $username, $division, $depot, $submitted_datetime);

        // Execute the statement
        if ($stmt->execute()) {
            // Data inserted successfully
            echo 'success';
        } else {
            // Failed to insert data
            echo 'failure';
        }

        // Close the statement and database connection
        $stmt->close();
        $db->close();
    }
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}
?>
