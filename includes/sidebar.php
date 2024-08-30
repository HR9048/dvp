<?php
require('session.php');
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

  <title>KKRTC - DVP</title>
  <link rel="icon" href="../images/logo1.jpeg">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap core CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">

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
  <link rel="stylesheet" href="style.css">
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
  </style>
</head>
</head>

<body id="page-top">

    <div id="content" style="max-width: 100%; overflow-x: hidden;">
      <nav class="navbar navbar-expand-md fixed-top" style="background-color: #bfc9ca;">
        <div class="container-fluid ">
          <a class="navbar-brand" href="../includes/depot_verify.php" style="color: black; padding-right:0;">
            <img src="../images/kkrtclogo.png" width="50" height="40" alt="KKRTC" style="padding-right: 0;">
          </a>
          <!--<h4>KKRTC</h4>&nbsp;&nbsp;-->
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
            aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-duotone fa-solid fa-sliders"></i>
          </button>
          <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
              <li class="nav-item">
                <h4>
                  <a class="nav-link active" aria-current="page" href="../includes/depot_verify.php"
                    style="color: black; font-size: 18px;">Home</a>
                </h4>
              </li>
              <?php if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO' || $_SESSION['JOB_TITLE'] == 'CO_STORE') { ?>
                <li class="nav-item">
                  <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                      style="font-size: 15px;">
                      <b>Off-road</b>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <li><a class="dropdown-item" href="main_off_road.php">DEPOT DWS Off-Road</a></li>
                      <li><a class="dropdown-item" href="main_rwy_offroad.php">RWY Offroad</a></li>
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
                      <li><a class="dropdown-item" href="main_dvp_divisionwise.php">Division wise DVP</a></li>
                      <li><a class="dropdown-item" href="main_dvp.php">Depot wise DVP</a></li>
                    </ul>
                  </div>
                </li>
              <?php } ?>
              <?php if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') { ?>
                <li class="nav-item">
                  <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                      style="font-size: 15px;">
                      <b>KMPL</b>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <li><a class="dropdown-item" href="main_kmpl.php">Corporation KMPL</a></li>
                      <li><a class="dropdown-item" href="main_divisionwise_kmpl.php">Division wise KMPL</a></li>
                      <li><a class="dropdown-item" href="main_depotwise_kmpl.php">Depot wise KMPL</a></li>
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
                      <li><a class="dropdown-item" href="main_camera_defect.php">View camera/VLTS/PIS Defect</a></li>
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
                      <li><a class="dropdown-item" href="buses.php">View Bus</a></li>
                      <li><a class="dropdown-item" href="main_bus_transfer.php">Buse Transfer</a></li>
                      <li><a class="dropdown-item" href="main_rwy_alllocation.php">View RWY Buse Allocation</a></li>
                    </ul>
                  </div>
                </li>
                <li class="nav-item">
                  <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                      style="font-size: 15px;">
                      <b>Fixation Data</b>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <li><a class="dropdown-item" href="main_schedule_info.php">Route Crew/vehicle Fixation</a></li>
                    </ul>
                  </div>
                </li>
                <li class="nav-item">
                  <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                      style="font-size: 15px;">
                      <b>Report</b>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <li><a class="dropdown-item" href="report.php">Off-Road Report</a></li>
                    </ul>
                  </div>
                </li>
                <li class="nav-item">
                  <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" aria-expanded="false"
                      style="font-size: 15px;">
                      <b>Users</b>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <li><a class="dropdown-item" href="employee.php">Employees</a></li>
                      <li><a class="dropdown-item" href="user.php">User Accounts</a></li>
                    </ul>
                  </div>
                </li>
              <?php } ?>
            </ul>
            <ul class="navbar-nav ml-auto">
              <div class="topbar-divider d-none d-sm-block"></div>

              <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                  aria-haspopup="true" aria-expanded="false">
                  <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?php echo $_SESSION['FIRST_NAME'] . ' ' . $_SESSION['LAST_NAME']; ?>
                  </span>
                  <img class="img-profile rounded-circle profile-img" <?php
                  if ($_SESSION['GENDER'] == 'Male') {
                    echo 'src="../images/male.jpeg"';
                  } else {
                    echo 'src="../images/female2.jpeg"';
                  }
                  ?>>

                </a>

                <?php

                $query = 'SELECT ID, FIRST_NAME,LAST_NAME,USERNAME,PASSWORD, t.TYPE
                      FROM users u
                      JOIN employee e ON e.PF_ID=u.PF_ID
                      JOIN type t ON t.TYPE_ID=u.TYPE_ID';
                $result = mysqli_query($db, $query) or die(mysqli_error($db));

                while ($row = mysqli_fetch_assoc($result)) {
                  $a = $_SESSION['MEMBER_ID'];
                  $bbb = $_SESSION['TYPE'];
                }

                ?>

                <!-- Dropdown - User Information -->
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                  <button class="dropdown-item" onclick="on()">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                  </button>
                  <a class="dropdown-item" href="#" data-toggle="modal" data-target="#settingsModal"
                    data-href="settings.php?action=edit & id='<?php echo $a; ?>'">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Settings
                  </a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                  </a>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </div>
  <br><br><br>
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