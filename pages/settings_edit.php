<?php
include('../includes/connection.php');
require_once('session.php');
if (empty($_POST)) {
  // If accessed directly without POST data, redirect to login.php
  header("Location: login.php");
  exit;
}
$zz = $_POST['id'];
$a = $_POST['firstname'];
$b = $_POST['lastname'];
$c = $_POST['gender'];
$d = $_POST['username'];
$e = $_POST['password'];
$f = $_POST['email'];
$g = $_POST['phone'];
$h = $_POST['role'];
$i = $_POST['DIVISION'];
$j = $_POST['DEPOT'];
$j = $_POST['type'];


$query = 'UPDATE users u 
	 						join employee e on e.PF_ID=u.PF_ID
	 						join location l on l.LOCATION_ID=e.LOCATION_ID
	 						set e.FIRST_NAME="' . $a . '", e.LAST_NAME="' . $b . '", GENDER="' . $c . '", PASSWORD = ("' . $e . '"),  EMAIL="' . $f . '", PHONE_NUMBER ="' . $g . '" WHERE
					ID ="' . $zz . '"';
$result = mysqli_query($db, $query) or die(mysqli_error($db));


?>
<?php

$sql = 'SELECT ID
                          FROM users';
$result2 = mysqli_query($db, $sql) or die(mysqli_error($db));

if ($result2) {
  // Redirect to processlogin.php after successful update
  echo '<script type="text/javascript">
            alert("You\'ve updated your account successfully.");
            window.location = "processlogin.php";
          </script>';
} else {
  // Handle errors
  echo '<script type="text/javascript">
            alert("Failed to update your account. Please try again.");
            window.location = "processlogin.php";
          </script>';
}

?>