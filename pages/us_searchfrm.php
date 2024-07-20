<?php
// Include connection file and other necessary files
include '../includes/connection.php';

// Check if 'id' parameter is set in the URL
if(isset($_GET['id'])) {
    // Assuming $_GET['id'] is the user ID
    $userID = $_GET['id'];

    // Prepare the query to fetch user details based on the user ID
    $query = 'SELECT ID, FIRST_NAME, LAST_NAME, GENDER, USERNAME, PASSWORD, EMAIL, PHONE_NUMBER, JOB_TITLE, TYPE, DIVISION, DEPOT
                FROM users u
                JOIN employee e ON u.PF_ID = e.PF_ID
                JOIN job j ON e.JOB_ID = j.JOB_ID
                JOIN location l ON e.LOCATION_ID = l.LOCATION_ID
                JOIN type t ON u.TYPE_ID = t.TYPE_ID
                WHERE ID = ?';

    // Prepare statement
    $stmt = mysqli_prepare($db, $query);

    // Bind parameters
    mysqli_stmt_bind_param($stmt, "i", $userID);

    // Execute statement
    mysqli_stmt_execute($stmt);

    // Get result
    $result = mysqli_stmt_get_result($stmt);

    // Check if there are rows
    if(mysqli_num_rows($result) > 0) {
        // Fetch the user data
        $row = mysqli_fetch_assoc($result);

        // Construct HTML to display user details
        echo '<div class="row">';
        echo '<div class="col-sm-3"><strong>Full Name:</strong></div><div class="col-sm-9">' . $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'] . '</div>';
        echo '<div class="col-sm-3"><strong>Gender:</strong></div><div class="col-sm-9">' . $row['GENDER'] . '</div>';
        echo '<div class="col-sm-3"><strong>Username:</strong></div><div class="col-sm-9">' . $row['USERNAME'] . '</div>';
        echo '<div class="col-sm-3"><strong>Email:</strong></div><div class="col-sm-9">' . $row['EMAIL'] . '</div>';
        echo '<div class="col-sm-3"><strong>Contact #:</strong></div><div class="col-sm-9">' . $row['PHONE_NUMBER'] . '</div>';
        echo '<div class="col-sm-3"><strong>Role:</strong></div><div class="col-sm-9">' . $row['JOB_TITLE'] . '</div>';
        echo '<div class="col-sm-3"><strong>Division:</strong></div><div class="col-sm-9">' . $row['DIVISION'] . '</div>';
        echo '<div class="col-sm-3"><strong>Depot:</strong></div><div class="col-sm-9">' . $row['DEPOT'] . '</div>';
        echo '<div class="col-sm-3"><strong>Account Type:</strong></div><div class="col-sm-9">' . $row['TYPE'] . '</div>';
        echo '</div>';
    } else {
        echo "User not found.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
} else {
  // Redirect to login.php if accessed directly without POST data
  header("Location: login.php");
  exit;
}
// Close database connection
mysqli_close($db);
?>
