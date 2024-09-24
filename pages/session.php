<?php
// Start the session
session_start();

// Create a new function to check if the session variables are set
function logged_in() {
    return isset($_SESSION['MEMBER_ID']) && isset($_SESSION['DIVISION_ID']) && isset($_SESSION['DEPOT_ID']) && isset($_SESSION['JOB_TITLE']) && isset($_SESSION['TYPE']) && isset($_SESSION['DIVISION']) && isset($_SESSION['DEPOT']) && isset($_SESSION['KMPL_DIVISION']) && isset($_SESSION['KMPL_DEPOT']) && isset($_SESSION['USERNAME']);
}

// This function redirects to logout.php if session variables are not set, showing an alert first
function confirm_logged_in() {
    if (!logged_in()) {
?>
        <script type="text/javascript">
            // Show an alert message before redirecting
            alert('Session expired or missing necessary information. You will be logged out. Please Login Again.');
            window.location = "logout.php";
        </script>
<?php
    }
}
?>
