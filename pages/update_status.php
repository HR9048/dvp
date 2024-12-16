<?php
// Include your database connection file
include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
// Function to update bus status to "On Road" based on ID
if (empty($_POST)) {
    // If accessed directly without POST data, redirect to login.php
    header("Location: login.php");
    exit;
}
function updateBusStatus($id)
{
    global $db; // Access the global database connection
    date_default_timezone_set('Asia/Kolkata'); // Set the timezone to Indian Standard Time (IST)
    $submissionDateTime = date('Y-m-d H:i:s'); // Get the current date and time in Bangalore (IST)
    // Prepare and execute the SQL query to update the status
    $query = "UPDATE off_road_data
          SET 
              status = 'on_road', 
              no_of_days_offroad = DATEDIFF(CURDATE(), off_road_date),
              on_road_date= '$submissionDateTime'
          WHERE 
              id = $id";

    $result = mysqli_query($db, $query);

    // Check if the update was successful
    if ($result) {
        return 'success';
    } else {
        // Return the MySQL error message for debugging
        return 'Error: ' . mysqli_error($db);
    }
}

// Check if the ID is provided in the POST request
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    echo updateBusStatus($id); // Call the function and echo the result
} else {
    // If ID is not provided, return an error message
    echo 'error';
}

// Close the database connection
mysqli_close($db);
?>
