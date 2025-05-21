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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul id="sidebar" class="navbar-nav sidebar sidebar-dark accordion">
            <!-- Sidebar content -->
            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="rwy.php">
                <img src="../images/logo1.jpeg" alt="" style="width: 30%; height: auto;">
            </a>


            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item">
                <a class="nav-link1" href="rwy.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span></a>
            </li>
            <!-- Divider -->
            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Tables
            </div>
            <!-- Tables Buttons -->

            <li class="nav-item">
                <a class="nav-link1" href="rwy_division_receive.php">
                    <i class="fa-solid fa-bus"></i>
                    <span>Sent from Division</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link1" href="rwy_offroad.php">
                    <i class="fas fa-tools"></i>
                    <span>RWY Off-Road</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link1" href="rwy_all_offroad.php">
                    <i class="fas fa-tools"></i>
                    <span>Corporation Off Road</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link1" href="depot_inventory_parts.php">
                    <i class="fas fa-bus"></i>
                    <span>Add Assemblies</span></a>
            </li>

             <li class="nav-item">
                <a class="nav-link1" href="depot_inventory_parts_view.php">
                    <i class="fas fa-fw fa-archive"></i>
                    <span>View Assemblies</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link1" href="depot_bus_inventory_add.php">
                    <i class="fas fa-fw fa-bus"></i>
                    <span>Add vehicle Inventory</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link1" href="co_inventory_details_view.php">
                    <i class="fas fa-fw fa-retweet"></i>
                    <span>View vehicle Inventory</span></a>
            </li>

            <!--<li class="nav-item">
                <a class="nav-link1" href="depot_report.php">
                    <i class="fas fa-fw fa-cogs"></i>
                    <span>Reports</span></a>
            </li> -->

            <!-- <li class="nav-item">
        <a class="nav-link1" href="#">
          <i class="fas fa-fw fa-users"></i>
          <span>Accounts</span></a>
      </li> -->
            <!-- Add this button at the end of the sidebar -->


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
        </ul>

        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Topbar -->
            <?php include_once 'rwy_top.php'; ?>