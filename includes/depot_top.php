<?php
require_once('session.php');
confirm_logged_in();
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
  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <!-- Custom styles for this page -->
  <style>
    .dropdown:hover .dropdown-menu {
      display: block;
      margin-top: 0;
    }

    .dropdown-toggle {
      font-size: 12px;
    }

    .nav-link {
      font-size: 18px;
    }

    .dropdown-menu {
      font-size: 15px;
    }

    nav.navbar {
      padding: 0 !important;
    }

    /* Remove padding from the navbar items (links and list items) */
    nav.navbar ul {
      padding: 0;
      margin: 0;
    }

    nav.navbar ul li {
      padding: 0;
      margin: 0;
      list-style: none;
      /* optional, to remove bullets if it's a list */
    }

    nav.navbar ul li a {
      padding: 0;
      margin: 0;
      display: inline-block;
      /* To ensure links behave like inline elements */

    }

    nav.navbar ul li .dropdown-menu li {
      padding: 0;
      margin: 0;
      list-style: none;
      /* Optional: remove bullets */
    }

    nav.navbar ul li .dropdown-menu li a {
      padding: 10px 20px;
      /* Add padding around the links for better spacing */
      display: block;
      /* Ensure the anchor tags take up full width */
      width: 100%;
      /* Ensure the anchor fills the width of the dropdown */
    }

    /* Media query for mobile view */
    @media (max-width: 600px) {
      .navcenter h5 {
        font-size: 0.8em;
        /* Adjust font size for mobile */
      }

      .navcenter h6 {
        font-size: 0.55em;
        /* Adjust font size for mobile */
        word-wrap: break-word;
        /* Ensure long words wrap */
      }

    }
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
              <img src="../images/kkrtclogo.png" alt="alternatetext" width="45" height="45" >
            </div>
            <div class="navcenter" style="text-align: center; flex: 1;">
              <h6 style="color: white; margin: 0;"><b>ಕಲ್ಯಾಣ ಕರ್ನಾಟಕ ರಸ್ತೆ ಸಾರಿಗೆ ನಿಗಮ</b></h6>
              <p style="color: white; margin: 0;">ದೈನಂದಿನ ವಾಹನದ ಸ್ದಿತಿಗತಿ | Daily Vehicle Position</p>
            </div>
          </div>
          <nav class="navbar navbar-expand-md" style="background-color: #bfc9ca;">

            <div class="container-fluid">
              <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fa-duotone fa-solid fa-sliders"></i> </button>
              <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                  <li class="nav-item">
                    <h4>
                      <a class="nav-link active" aria-current="page" href="../includes/depot_verify.php"
                        style="color: black; font-size: 18px;">Home</a>
                    </h4>
                  </li>
                  <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM') { ?>
                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b>Off-road</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                          <li><a class="dropdown-item" href="depot_offroad.php">Add Off-Road</a></li>
                          <li><a class="dropdown-item" href="depot_offroad_print.php">Print Offroad</a></li>
                        </ul>
                      </div>
                    </li>
                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
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
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b>Route Vehicles</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                          <li><a class="dropdown-item" href="depot_ramp.php">Route Ramp Section</a></li>
                          <li><a class="dropdown-item" href="depot_ramp_attend.php">Ramp Defect Attend</a></li>
                        </ul>
                      </div>
                    </li>
                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b>Buses</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                          <li><a class="dropdown-item" href="register1.php">Add Bus</a></li>
                          <li><a class="dropdown-item" href="depot_busses.php">View Buses</a></li>
                        </ul>
                      </div>
                    </li>
                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b> Break Down</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                          <li><a class="dropdown-item" href="depot_bd.php">Add BD</a></li>
                        </ul>
                      </div>
                    </li>
                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b>Defect records</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                          <li><a class="dropdown-item" href="depot_camera_defect.php">Add/view camera/VLTS/PIS Defect</a>
                          </li>
                        </ul>
                      </div>
                    </li>
                  <?php } ?>
                  <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Bunk' || $_SESSION['JOB_TITLE'] == 'DM') { ?>
                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b>KMPL</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                          <li><a class="dropdown-item" href="depot_kmpl.php">Add Depot KMPL</a></li>
                        </ul>
                      </div>
                    </li>
                  <?php } ?>
                  <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') { ?>

                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b>Schedule Master</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                          <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') { ?>
                            <li><a class="dropdown-item" href="depot_inspector_schedule_d.php">Update Schedule</a></li>
                            <li><a class="dropdown-item" href="depot_add_schedule.php">Add Schedule</a></li>
                            <li><a class="dropdown-item" href="depot_scheduel_actinact.php">Active/Inactive Schedule</a>
                            </li>
                          <?php } ?>
                          <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech') { ?>
                            <li><a class="dropdown-item" href="depot_schedule_b.php">Update Schedule</a></li>
                          <?php } ?>
                          <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM') { ?>
                            <li><a class="dropdown-item" href="depot_inspector_schedule_d.php">Update Schedule(crew)</a>
                            </li>
                            <li><a class="dropdown-item" href="depot_schedule_b.php">Update Schedule(Bus)</a></li>
                            <li><a class="dropdown-item" href="depot_add_schedule.php">Add Schedule</a></li>
                            <li><a class="dropdown-item" href="depot_scheduel_actinact.php">Active/Inactive Schedule</a>
                            </li>
                          <?php } ?>
                        </ul>
                      </div>
                    </li>
                  <?php }
                  if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') { ?>

                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b>Private Employee</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">

                          <li><a class="dropdown-item" href="depot_addp_emp.php">Add Private Employee</a></li>
                          <li><a class="dropdown-item" href="depot_manp_emp.php">Manage Private Employee</a></li>
                          <!-- <li><a class="dropdown-item" href="depot_relp_emp.php">Releve Private Employee</a></li> -->
                        </ul>
                      </div>
                    </li>
                  <?php } ?>
                  <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') { ?>

                    <li class="nav-item">
                      <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                          style="font-size: 15px;">
                          <b>Report</b>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                          <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM') { ?>

                            <li><a class="dropdown-item" href="depot_offroad_fromto.php">Off-Road From to To date</a></li>
                            <li><a class="dropdown-item" href="depot_report.php">Off-Road One day report</a></li>
                          <?php } ?>
                          <li><a class="dropdown-item" href="depot_crew_report_d.php">Route Daily Crew report</a></li>
                          <li><a class="dropdown-item" href="depot_crew_report_m.php">Route Monthly Crew report</a></li>
                        </ul>
                      </div>
                    </li>
                  <?php }
                  ?>
                </ul>
                <ul class="navbar-nav ml-auto">
                  <div class="topbar-divider d-none d-sm-block"></div>
                  <li class="nav-item">
                    <div class="dropdown">
                      <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                        style="font-size: 15px;">
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
                              class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile</button></li>
                        <li><a class="dropdown-item" href="#" data-toggle="modal" data-target="#settingsModal"
                            data-href="settings.php?action=edit & id='<?php echo $a; ?>'"><i
                              class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>Settings</a></li>
                        <li><a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal"><i
                              class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</a></li>
                      </ul>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </nav>
        </div>
        <br><br><br><br>
        <!-- End of Topbar -->
        <script>
          $(document).ready(function () {
            // Close dropdown when clicking outside
            $(document).click(function (e) {
              var target = e.target;
              if (!$(target).is('.dropdown-toggle') && !$(target).parents().is('.dropdown-menu')) {
                $('.dropdown-menu').removeClass('show');
              }
            });

            // Open dropdown when clicking dropdown toggle
            $('.dropdown-toggle').click(function () {
              var dropdownMenu = $(this).next('.dropdown-menu');
              $('.dropdown-menu').not(dropdownMenu).removeClass('show');
              dropdownMenu.toggleClass('show');
            });
          });
        </script>
        <!-- Begin Page Content -->
        <div class="container-fluid">