<?php
include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {



  // Set the parameters and execute the statement
  $pfId = $_POST['empid']; // Assuming 'empid' is the name of the input field containing PF_ID
  $username = $_POST['username'];
  $password = $_POST['password'];
  $typeId = $_POST['type_id']; // Assuming 'type_id' is the name of the hidden input field containing TYPE_ID
  // Prepare and bind the INSERT statement
  $stmt = $db->prepare("INSERT INTO users (PF_ID, USERNAME, PASSWORD, TYPE_ID) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isss", $pfId, $username, $password, $typeId);
  if ($stmt->execute()) { ?>

    <script type="text/javascript">window.location = "user.php";</script>
    <?PHP
  } else {
    echo "Error: " . $stmt->error;
  }

  // Close the statement
  $stmt->close();
} else {
  // Redirect to login.php if accessed directly without POST data
  header("Location: login.php");
  exit;
}


?>