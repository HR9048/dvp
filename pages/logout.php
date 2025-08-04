<?php
require('../includes/connection.php');
session_start();
date_default_timezone_set('Asia/Kolkata');

// 1. Update logout time instead of deleting the session
if (isset($_COOKIE['dvp_session_token'])) {
    $token = $_COOKIE['dvp_session_token'];

    $stmt = $db->prepare("UPDATE sessions SET logged_out_at = NOW() WHERE session_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    // 2. Expire the session_token cookie
    setcookie("dvp_session_token", "", time() - 7200, "/", "", false, true);
}

// 3. Unset all session variables
$_SESSION = [];

// 4. Destroy session (optional)
session_destroy();
?>

<!-- 5. Redirect to login -->
<script type="text/javascript">
    window.location = "login.php";
</script>
