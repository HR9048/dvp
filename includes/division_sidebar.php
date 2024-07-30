<?php
require ('session.php');
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

  <!-- Bootstrap core CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Bootstrap JavaScript -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<link
  href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
  rel="stylesheet">

  <!-- Bootstrap core CSS -->
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom fonts for this template -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">


<!-- jQuery -->
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JavaScript -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.6/js/jquery.dataTables.js"></script>
<link rel="stylesheet" href="style.css">

</head>

<body id="page-top">

  <!-- Page Wrapper -->
  <div id="wrapper">

    <!-- Sidebar -->
    <ul id="sidebar" class="navbar-nav sidebar sidebar-dark accordion">
      <!-- Sidebar content -->
      <!-- Sidebar - Brand -->
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="division.php">
        <img src="../images/logo1.jpeg" alt="" style="width: 30%; height: auto;">
      </a>
      <!-- Divider -->
      <hr class="sidebar-divider my-0">

      <!-- Nav Item - Dashboard -->
      <li class="nav-item">
        <a class="nav-link1" href="division.php">
          <i class="fas fa-fw fa-home"></i>
          <span>Home</span></a>
      </li>
      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        Tables
      </div>
      <!-- Tables Buttons -->
      <li class="nav-item">
        <a class="nav-link1" href="division_ORP.php">
          <i class="fas fa-tools"></i>
          <span>Off Road position</span></a>
      </li>

      <li class="nav-item">
        <a class="nav-link1" href="division_dvp.php">
        <i class="fas fa-fw fa-bus"></i>
          <span>Print DVP</span></a>
      </li>

      <li class="nav-item">
        <a class="nav-link1" href="division_offroad_print.php">
          <i class="fas fa-tools"></i>
          <span>Print Offroad</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link1" href="division_camera_defect.php">
          <i class="fas fa-fw fa-cogs"></i>
          <span>Camera/PIS Defect</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link1" href="division_buses.php">
          <i class="fas fa-fw fa-bus"></i>
          <span>Buses</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link1" href="division_schedule_info.php">
        <i class="fas fa-calendar-alt"></i>
        <span>Schedules</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link1" href="division_kmpl.php">
          <i class="fas fa-tachometer-alt"></i>
          <span>KMPL</span></a>
      </li>

      <li class="nav-item">
        <a class="nav-link1" href="division_employee.php">
          <i class="fas fa-users"></i>
          <span>Employees</span></a>
      </li>

      

      <li class="nav-item">
        <a class="nav-link1" href="division_accounts.php">
          <i class="fas fa-fw fa-users"></i>
          <span>Accounts</span></a>
      </li>
      <!-- Add this button at the end of the sidebar -->
      <li class="nav-item">
        <a class="nav-link1" href="division_report.php">
          <i class="fas fa-fw fa-archive"></i>
          <span>Reports</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider d-none d-md-block">


      <li class="nav-item" style="margin-top: auto; margin-bottom: 10px;">
        <div id="sidebarCollapse" class="btn btn-dark d-flex justify-content-center align-items-center">
          <i class="fas fa-angle-left"></i>
        </div>
      </li>


      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const sidebar = document.getElementById('sidebar');
          const sidebarCollapse = document.getElementById('sidebarCollapse');

          sidebarCollapse.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');

            if (sidebar.classList.contains('collapsed')) {
              sidebar.style.width = '7%'; // Adjust as needed
            } else {
              sidebar.style.width = ''; // Reset to default width
            }

            // Toggle the rotate-icon class to rotate the icon
            sidebarCollapse.querySelector('.fas').classList.toggle('rotate-icon');
          });
        });

      </script>


      <!-- Sidebar Toggler (Sidebar) -->
      <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
      </div>
    </ul>

    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Topbar -->
      <?php include_once 'division_top_side.php'; ?>