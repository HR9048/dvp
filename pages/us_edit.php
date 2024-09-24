<?php
include '../includes/connection.php'; // Include your database configuration file
include 'session.php';
confirm_logged_in();
if (isset($_GET['id'])) {
  $id = $_GET['id'];

  $query = "SELECT ID, e.FIRST_NAME, e.LAST_NAME, e.GENDER, USERNAME, PASSWORD, e.EMAIL, PHONE_NUMBER, j.JOB_TITLE, t.TYPE, l.DIVISION, l.DEPOT
    FROM users u
    join employee e on u.PF_ID = e.PF_ID
    join job j on e.JOB_ID=j.JOB_ID
    join location l on e.LOCATION_ID=l.LOCATION_ID
    join type t on u.TYPE_ID=t.TYPE_ID
    WHERE ID = $id";

  $result = mysqli_query($db, $query);
  if (!$result) {
    die("Error: " . mysqli_error($db));
  }

  $row = mysqli_fetch_assoc($result);

  // Echo the data in JSON format
  echo json_encode($row);
}else {
  // Redirect to login.php if accessed directly without POST data
  header("Location: login.php");
  exit;
}
?>