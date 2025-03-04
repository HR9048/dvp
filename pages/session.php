<?php
// Increase session lifetime to 1 hour (3600 seconds)
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Start session
session_start();

// Extend session expiration on user activity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    session_unset();
    session_destroy();
    echo "<script>
            alert('Session expired due to inactivity. Please log in again.');
            window.location = 'logout.php';
          </script>";
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time(); // Reset session timer

// Function to check if session variables are set
function logged_in() {
    return isset($_SESSION['MEMBER_ID']) && isset($_SESSION['DIVISION_ID']) && isset($_SESSION['DEPOT_ID']) && 
           isset($_SESSION['JOB_TITLE']) && isset($_SESSION['TYPE']) && isset($_SESSION['DIVISION']) && 
           isset($_SESSION['DEPOT']) && isset($_SESSION['KMPL_DIVISION']) && isset($_SESSION['KMPL_DEPOT']) && 
           isset($_SESSION['USERNAME']);
}

// Function to confirm login
function confirm_logged_in() {
    if (!logged_in()) {
        echo "<script>
                alert('Session expired or missing necessary information. You will be logged out. Please Login Again.');
                window.location = 'logout.php';
              </script>";
        exit();
    }
}
?>
