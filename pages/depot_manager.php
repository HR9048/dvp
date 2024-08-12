<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
?>
<div class="row show-grid">
    <!-- Customer ROW -->
    <div class="col-md-3">
        <!-- Customer record -->
        <div class="col-md-12 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Off Road</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $query = "SELECT 
                COUNT(DISTINCT bus_number) AS total_off_road_count
              FROM off_road_data
              WHERE status = 'off_road' AND division = '{$_SESSION['DIVISION_ID']}' AND depot = '{$_SESSION['DEPOT_ID']}'";

                                // Execute the query
                                $result = mysqli_query($db, $query) or die(mysqli_error($db));

                                // Fetch the count
                                $row = mysqli_fetch_array($result);

                                // Output the count
                                echo "$row[0]";
                                ?>
                                Record(s)
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier record -->
        <div class="col-md-12 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Employee</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php

                                // Prepare the SQL query to count the number of employees based on division and depot names
                                $query = "SELECT COUNT(*) FROM employee 
                INNER JOIN location ON employee.LOCATION_ID = location.LOCATION_ID 
                WHERE location.division = '{$_SESSION['DIVISION']}' AND location.depot = '{$_SESSION['DEPOT']}'";

                                // Execute the query
                                $result = mysqli_query($db, $query) or die(mysqli_error($db));

                                // Fetch the count
                                $row = mysqli_fetch_array($result);

                                // Output the count
                                echo "Number of employees: $row[0]";
                                ?>
                                Record(s)
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- Employee ROW -->
    <div class="col-md-3">
        <!-- Employee record -->
        <div class="col-md-12 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Today's DVP</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $current_date = date("Y-m-d");
                                // Prepare the SQL query to check if the current date is present in the database for the given session division
                                $query = "SELECT COUNT(*) FROM dvp_data WHERE date = '$current_date' AND division = '{$_SESSION['DIVISION_ID']}' and depot = '{$_SESSION['DEPOT_ID']}'";
                                $result = mysqli_query($db, $query) or die(mysqli_error($db));

                                // Fetch the count
                                $row = mysqli_fetch_array($result);

                                // Check if any record is found for the current date and session division
                                if ($row[0] > 0) {
                                    echo "Submitted";
                                } else {
                                    echo "Not Submitted";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- User record -->
        <div class="col-md-12 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">yesterday's KMPL</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $current_date = date("Y-m-d", strtotime("-1 day"));
                                // Prepare the SQL query to check if the current date is present in the database for the given session division
                                $query = "SELECT COUNT(*) FROM kmpl_data WHERE date = '$current_date' AND division = '{$_SESSION['DIVISION_ID']}' and depot = '{$_SESSION['DEPOT_ID']}'";
                                $result = mysqli_query($db, $query) or die(mysqli_error($db));

                                // Fetch the count
                                $row = mysqli_fetch_array($result);

                                // Check if any record is found for the current date and session division
                                if ($row[0] > 0) {
                                    echo "Submitted";
                                } else {
                                    echo "Not Submitted";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- PRODUCTS ROW -->
    <div class="col-md-3">
        <!-- Product record -->
        <div class="col-md-12 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">

                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Buses</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h6 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php
                                        $query = "SELECT COUNT(*) FROM bus_registration WHERE division_name = '{$_SESSION['DIVISION_ID']}' and depot_name = '{$_SESSION['DEPOT_ID']}'";
                                        $result = mysqli_query($db, $query);
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "$row[0]";
                                        }
                                        ?> Record(s)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-auto">
                            <i class="fas fa-bus fa-2x text-gray-300"></i>
                            <i class="fas fa-bus fa-2x text-gray-300"></i>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 mb-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Account</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php
                                // Assuming you have already established a connection to your database and stored it in $db variable
                                
                                // Make sure to sanitize and validate the session division and depot name inputs to prevent SQL injection
                                
                                // Example session division and depot name variables
                                $session_division = $_SESSION['DIVISION']; // Assuming you're getting this from a session variable
                                $session_depot = $_SESSION['DEPOT']; // Assuming you're getting this from a session variable
                                
                                // Prepare the SQL query to count registered accounts based on division and depot names
                                $query = "SELECT COUNT(*) FROM users 
                INNER JOIN employee ON users.PF_ID = employee.PF_ID 
                INNER JOIN location ON employee.LOCATION_ID = location.LOCATION_ID 
                WHERE users.TYPE_ID = 3 
                AND location.DIVISION = '$session_division' 
                AND location.DEPOT = '$session_depot'";

                                // Execute the query
                                $result = mysqli_query($db, $query) or die(mysqli_error($db));

                                // Fetch the count
                                $row = mysqli_fetch_array($result);

                                // Output the count
                                echo "Registered accounts: $row[0]";
                                ?>
                                Record(s)
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-3">
        <div class="card shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">

                    <div class="col-auto">
                        <i class="fa fa-th-list fa-fw"></i>
                    </div>

                    <div class="panel-heading"> Recent Off Road
                    </div>
                    <div class="row no-gutters align-items-center mt-1">
                        <div class="col-auto">
                            <div class="h6 mb-0 mr-0 text-gray-800">
                                <!-- /.panel-heading -->

                                <div class="panel-body">
                                    <div class="list-group">
                                        <?php
                                        // Prepare the SQL query to fetch data from the off_road_data table
                                        $query = "SELECT bus_number FROM off_road_data WHERE division = '{$_SESSION['DIVISION_ID']}' and depot = '{$_SESSION['DEPOT_ID']}' and status = 'off_road' ORDER BY submission_datetime DESC LIMIT 5";

                                        // Execute the query
                                        $result = mysqli_query($db, $query) or die(mysqli_error($db));

                                        // Output the fetched data
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<a href='#' class='list-group-item text-gray-800'><i class='fa fa-tasks fa-fw'></i> $row[0]</a>";
                                        }
                                        ?>

                                    </div>
                                    <!-- /.list-group -->
                                    <a href="depot_offroad.php" class="btn btn-default btn-block">View All Off
                                        Roads</a>
                                </div>
                                <!-- /.panel-body -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div>


    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>