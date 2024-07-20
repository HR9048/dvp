<?php
include '../includes/connection.php';

$PF = $_POST['PF_NO'];
$fname = $_POST['firstname'];
$lname = $_POST['lastname'];
$gen = $_POST['gender'];
$email = $_POST['email'];
$phone = $_POST['phonenumber'];
$jobb = $_POST['designation'];
$prov = $_POST['division'];
$cit = $_POST['depot'];
if (empty($_POST)) {
    // If accessed directly without POST data, redirect to login.php
    header("Location: login.php");
    exit;
}
// Fetch the JOB_ID from the job table based on the provided designation
$query_job = "SELECT JOB_ID FROM job WHERE JOB_TITLE = '$jobb'";
$result_job = mysqli_query($db, $query_job);
if ($result_job && mysqli_num_rows($result_job) > 0) {
    $row_job = mysqli_fetch_assoc($result_job);
    $job_id = $row_job['JOB_ID'];
} else {
    // Handle the case where the provided designation does not exist in the job table
    die("Error: Job with title '$jobb' not found.");
}

// Fetch the LOCATION_ID from the location table based on the provided division and depot
$query_location = "SELECT LOCATION_ID FROM location WHERE DIVISION = '$prov' AND depot = '$cit'";
$result_location = mysqli_query($db, $query_location);
if ($result_location && mysqli_num_rows($result_location) > 0) {
    $row_location = mysqli_fetch_assoc($result_location);
    $location_id = $row_location['LOCATION_ID'];
} else {
    // Handle the case where the provided division and depot combination does not exist in the location table
    die("Error: Location with division '$prov' and depot '$cit' not found.");
}

// Insert data into the employee table
$query_insert_employee = "INSERT INTO employee (PF_ID, FIRST_NAME, LAST_NAME, GENDER, EMAIL, PHONE_NUMBER, JOB_ID, LOCATION_ID)
                           VALUES (NULL, '$fname', '$lname', '$gen', '$email', '$phone', '$job_id', '$location_id')";
$result_insert_employee = mysqli_query($db, $query_insert_employee);
if (!$result_insert_employee) {
    // Handle the case where the insertion failed
    die("Error: " . mysqli_error($db));
}

// Redirect to employee.php after successful insertion
header('Location: employee.php');
exit;
?>
