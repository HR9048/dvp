<?php
include '../includes/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $PF = trim($_POST['PF_NO']);
    $fname = trim($_POST['firstname']);
    $lname = trim($_POST['lastname']);
    $gen = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phonenumber']);
    $jobb = trim($_POST['designation']);
    $prov = trim($_POST['division']);
    $cit = trim($_POST['depot']);

    // Validate input
    if ( empty($fname) || empty($lname) || empty($gen) || 
        empty($email) || empty($phone) || empty($jobb) || 
        empty($prov) || empty($cit)) {
        die("All fields are required.");
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Validate phone number (exactly 10 digits)
    if (!preg_match('/^\d{10}$/', $phone)) {
        die("Invalid phone number. Please enter exactly 10 digits.");
    }

    // Fetch the JOB_ID from the job table based on the provided designation
    $query_job = "SELECT JOB_ID FROM job WHERE JOB_TITLE = ?";
    $stmt_job = mysqli_prepare($db, $query_job);
    mysqli_stmt_bind_param($stmt_job, 's', $jobb);
    mysqli_stmt_execute($stmt_job);
    $result_job = mysqli_stmt_get_result($stmt_job);

    if ($result_job && mysqli_num_rows($result_job) > 0) {
        $row_job = mysqli_fetch_assoc($result_job);
        $job_id = $row_job['JOB_ID'];
    } else {
        die("Error: Job with title '$jobb' not found.");
    }

    // Fetch the LOCATION_ID from the location table based on the provided division and depot
    $query_location = "SELECT LOCATION_ID FROM location WHERE division_id = ? AND depot_id = ?";
    $stmt_location = mysqli_prepare($db, $query_location);
    mysqli_stmt_bind_param($stmt_location, 'ss', $prov, $cit);
    mysqli_stmt_execute($stmt_location);
    $result_location = mysqli_stmt_get_result($stmt_location);

    if ($result_location && mysqli_num_rows($result_location) > 0) {
        $row_location = mysqli_fetch_assoc($result_location);
        $location_id = $row_location['LOCATION_ID'];
    } else {
        die("Error: Location with division '$prov' and depot '$cit' not found.");
    }

    // Insert data into the employee table
    $query_insert_employee = "INSERT INTO employee (PF_ID, FIRST_NAME, LAST_NAME, GENDER, EMAIL, PHONE_NUMBER, JOB_ID, LOCATION_ID)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_employee = mysqli_prepare($db, $query_insert_employee);
    mysqli_stmt_bind_param($stmt_insert_employee, 'issssssi', $PF, $fname, $lname, $gen, $email, $phone, $job_id, $location_id);
    $result_insert_employee = mysqli_stmt_execute($stmt_insert_employee);

    if (!$result_insert_employee) {
        die("Error: " . mysqli_error($db));
    }

    // Redirect to employee.php after successful insertion
    header('Location: employee.php');
    exit;
} else {
    // If accessed directly without POST data, redirect to login.php
    header("Location: login.php");
    exit;
}
?>
