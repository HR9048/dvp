<?php
include 'connection.php';
include '../pages/session.php';

if ($_SESSION['TYPE'] == 'DEPOT'){
    // Check the job title of the user
    if ($_SESSION['JOB_TITLE'] == 'DM'){
        ?>
        <script type="text/javascript">
            // Redirect to depot_manager.php if the job title is Depot Manager
            window.location = "../pages/depot_dashboard.php";
        </script>
        <?php
    } elseif ($_SESSION['JOB_TITLE'] == 'Mech') {
        ?>
        <script type="text/javascript">
            // Redirect to depot_clerk.php if the job title is Clerk
            window.location = "../pages/depot_dashboard.php";
        </script>
        <?php
    }
    elseif ($_SESSION['JOB_TITLE'] == 'Bunk') {
        ?>
        <script type="text/javascript">
            // Redirect to depot_clerk.php if the job title is Clerk
            window.location = "../pages/depot_kmpl.php";
        </script>
        <?php
    } elseif ($_SESSION['JOB_TITLE'] == 'T_INSPECTOR') {
        ?>
        <script type="text/javascript">
            // Redirect to depot_clerk.php if the job title is Clerk
            window.location = "../pages/depot_inspector.php";
        </script>
        <?php
    } elseif ($_SESSION['JOB_TITLE'] == 'SECURITY') {
        ?>
        <script type="text/javascript">
            // Redirect to depot_clerk.php if the job title is Clerk
            window.location = "../pages/depot_schinout.php";
        </script>
        <?php
    }
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: ../pages/login.php");
    exit;
}
?>
