<?php
require_once ('session.php');
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

  <!-- Custom styles for this template -->
  <link rel="stylesheet" href="style.css">

  <!-- Custom styles for this page -->
</head>

<body id="page-top">

  <!-- Page Wrapper -->
  <div id="wrapper">

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

          <a class="sidebar-brand d-flex align-items-center justify-content-center"
            style="text-decoration: none; font-size: 18px; font-weight: bold;" href="rwy.php">

            <div class="sidebar-brand-text mx-3">Home</div>
          </a>
          <a class="sidebar-brand d-flex align-items-center justify-content-center"
            style="text-decoration: none; font-size: 18px; font-weight: bold;" href="rwy_offroad.php">

            <div class="sidebar-brand-text mx-3">Vehicle Position</div>
          </a>

          <!-- Topbar Navbar -->
          <ul class="navbar-nav ml-auto">

            <!-- <li class="nav-item dropdown no-arrow">
              <a class="nav-link" href="dvp.php" role="button">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">DVP</span>
              </a>
            </li> -->

            <div class="topbar-divider d-none d-sm-block"></div>

            <!-- Nav Item - User Information -->
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

        </nav>
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