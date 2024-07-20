
  <!-- Page Wrapper -->
  <div id="wrapper">

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

          <a class="sidebar-brand d-flex align-items-center justify-content-center"
            style="text-decoration: none; font-size: 18px; font-weight: bold;" href="index.php">

            <div class="sidebar-brand-text mx-3">Home</div>
          </a>
          <a class="sidebar-brand d-flex align-items-center justify-content-center"
            style="text-decoration: none; font-size: 18px; font-weight: bold;" href="main_off_road.php">

            <div class="sidebar-brand-text mx-3">Off-road Position</div>
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
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 big">
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

              <!-- Dropdown menu -->
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