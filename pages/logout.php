<?php

session_start();

// 2. Unset all the session variables
unset($_SESSION['MEMBER_ID']);
unset($_SESSION['FIRST_NAME']);
unset($_SESSION['LAST_NAME']);
unset($_SESSION['GENDER']);
unset($_SESSION['EMAIL']);
unset($_SESSION['PHONE_NUMBER']);
unset($_SESSION['JOB_TITLE']);
unset($_SESSION['DIVISION']);
unset($_SESSION['DEPOT']);
unset($_SESSION['TYPE']);
unset($_SESSION['USERNAME']);
unset($_SESSION['DIVISION_ID']);
unset($_SESSION['DEPOT_ID']);
unset($_SESSION['DIVISION_KMPL']);
unset($_SESSION['DEPOT_KMPL']);

?>
<script type="text/javascript">
    window.location = "login.php";
</script>