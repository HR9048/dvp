<?php
require_once('session.php');
confirm_logged_in();
if (in_array($_SESSION['DEPOT_ID'], ['1', '8', '12', '13', '14', '15'])) {
    $programstart_date = '2025-07-31';
    $formated_programstart_date = date('d-m-Y', strtotime($programstart_date));
    $reportstart_date = '2025-08-01';
    $formated_reportstart_date = date('d-m-Y', strtotime($reportstart_date));
} elseif (in_array($_SESSION['DEPOT_ID'], ['111'])) {
    $programstart_date = '2025-08-31';
    $formated_programstart_date = date('d-m-Y', strtotime($programstart_date));
    $reportstart_date = '2025-09-01';
    $formated_reportstart_date = date('d-m-Y', strtotime($reportstart_date));
}
date_default_timezone_set('Asia/Kolkata');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <meta name="description" content="">
    <meta name="author" content="">

    <title>KKRTC-DVP</title>
    <link rel="icon" href="../images/logo1.jpeg">
    <!-- Include jQuery library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Include Bootstrap JavaScript -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Bootstrap core CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
        integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

    <!-- Custom fonts for this template -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Bootstrap CSS -->

    <!-- Bootstrap Datepicker CSS -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

    <!-- Custom styles for this template -->
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js">
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom styles for this page -->
    <style>
        /* ===========================
   NAVBAR DROPDOWN & SUBMENU
   (Full style, improved chevron)
   =========================== */

        /* ===== Base dropdown behavior ===== */
        /* Show first-level dropdown on hover */
        /* ===========================
   DROPDOWN & SUBMENU
   =========================== */

        /* Show first-level dropdown on hover */
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content" style="max-width: 100%; overflow-x: hidden;">
                <div class="fixed-top">
                    <div
                        style="display: flex; align-items: center; justify-content: center; background-color: #3c97bf; padding: 0px 0px;">
                        <div style="margin-right: 10px; margin-left: auto;padding-left:20px">
                            <img src="../images/kkrtclogo.png" alt="alternatetext" width="45" height="45">
                        </div>
                        <div class="navcenter" style="text-align: center; flex: 1;">
                            <h6 style="color: white; margin: 0;"><b>ಕಲ್ಯಾಣ ಕರ್ನಾಟಕ ರಸ್ತೆ ಸಾರಿಗೆ ನಿಗಮ</b></h6>
                            <p style="color: white; margin: 0;">ದೈನಂದಿನ ವಾಹನದ ಸ್ದಿತಿಗತಿ</p>
                        </div>
                    </div>
                    <nav class="navbar navbar-expand-md"
                        style="background-color: #bfc9ca;padding:0px 0px;margin: bottom 0px;">

                        <div class="container-fluid">
                            <button class="navbar-toggler" type="button" data-toggle="collapse"
                                data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false"
                                aria-label="Toggle navigation">
                                <i class="fa-duotone fa-solid fa-sliders"></i> </button>
                            <div class="collapse navbar-collapse" id="navbarCollapse">
                                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                                    <li class="nav-item">
                                        <h4>
                                            <a class="nav-link active" aria-current="page"
                                                href="../includes/depot_verify.php"
                                                style="color: black; font-size: 18px;">Home</a>
                                        </h4>
                                    </li>
                                    <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM') { ?>
                                        <li class="nav-item">
                                            <div class="dropdown">
                                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1"
                                                    aria-expanded="false" style="font-size: 15px;">
                                                    <b>Off-road</b>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="depot_offroad.php">Add Off-Road</a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="depot_offroad_print.php">Print
                                                            Offroad</a></li>
                                                    <!--<li><a class="dropdown-item" href="depot_add_bd.php">Add BreakDown</a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="depot_view_bd.php">View/print
                                                            BreakDown</a></li>-->
                                                </ul>
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <div class="dropdown">
                                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1"
                                                    aria-expanded="false" style="font-size: 15px;">
                                                    <b>DVP</b>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="dvp.php">Add DVP</a></li>
                                                    <li><a class="dropdown-item" href="dvp_print.php">Print DVP</a></li>
                                                </ul>
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <div class="dropdown">
                                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1"
                                                    aria-expanded="false" style="font-size: 15px;">
                                                    <b>Route Vehicles</b>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="depot_ramp.php">Route Ramp
                                                            Section</a></li>
                                                    <li><a class="dropdown-item" href="depot_ramp_attend.php">Ramp Defect
                                                            Attend</a></li>
                                                </ul>
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <div class="dropdown">
                                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1"
                                                    aria-expanded="false" style="font-size: 15px;">
                                                    <b>Route Vehicles</b>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="depot_ramp.php">Route Ramp Section</a></li>
                                                    <li class="dropdown-submenu">
                                                        <a class="dropdown-item dropdown-toggle" href="#">Ramp Defect</a>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="depot_ramp_attend.php">Ramp Defect Attend</a></li>
                                                            <li><a class="dropdown-item" href="depot_ramp_report.php">Ramp Defect Report</a></li>
                                                        </ul>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>

                                    <?php } ?>
                                </ul>
                                <ul class="navbar-nav ml-auto mb-2 mb-md-0">
                                    <li class="nav-item">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1"
                                                aria-expanded="false" style="font-size: 15px;">
                                                <b
                                                    class="mr-2 d-none d-lg-inline text-gray-600 small"><b><?php echo $_SESSION['FIRST_NAME'] . ' ' . $_SESSION['LAST_NAME']; ?></b></b>
                                                <img class="img-profile rounded-circle profile-img" <?php if ($_SESSION['GENDER'] == 'Male') {
                                                                                                        echo 'src="../images/male.jpeg"';
                                                                                                    } else {
                                                                                                        echo 'src="../images/female2.jpeg"';
                                                                                                    } ?>>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                <li><button class="dropdown-item" onclick="on()"><i
                                                            class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile</button>
                                                </li>
                                                <li><a class="dropdown-item" href="#" data-toggle="modal"
                                                        data-target="#settingsModal"
                                                        data-href="settings.php?action=edit & id='<?php echo $a; ?>'"><i
                                                            class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>Settings</a>
                                                </li>
                                                <li><a class="dropdown-item" href="#" data-toggle="modal"
                                                        data-target="#logoutModal"><i
                                                            class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                                        Logout</a></li>
                                            </ul>
                                        </div>
                                    </li>


                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
                <br><br><br><br><br>
                <!-- End of Topbar -->
                <script>
                    $(document).ready(function() {
                        // Close dropdown when clicking outside
                        $(document).click(function(e) {
                            var target = e.target;
                            if (!$(target).is('.dropdown-toggle') && !$(target).parents().is(
                                    '.dropdown-menu')) {
                                $('.dropdown-menu').removeClass('show');
                            }
                        });

                        // Open dropdown when clicking dropdown toggle
                        $('.dropdown-toggle').click(function() {
                            var dropdownMenu = $(this).next('.dropdown-menu');
                            $('.dropdown-menu').not(dropdownMenu).removeClass('show');
                            dropdownMenu.toggleClass('show');
                        });
                    });
                </script>
                <!-- Begin Page Content -->
                <div class="container-fluid">