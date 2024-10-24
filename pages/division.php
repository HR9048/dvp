<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME') {
    // Allow access
    ?>
 <div class="row show-grid">
        <div class="col-md-3">
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
              WHERE status = 'off_road' AND division = '{$_SESSION['DIVISION_ID']}'";

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
                                <i class="fa-solid fa-tools fa-beat fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="col-md-12 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Vehicles on Schedule
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $query = "SELECT COUNT(*) FROM sch_veh_out where schedule_status='1' AND division_id = '{$_SESSION['DIVISION_ID']}'";
                                    $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    $row = mysqli_fetch_array($result);
                                    echo "Vehicle Count: $row[0]";
                                    ?>
                                    Record(s)
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-id-card fa-beat fa-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="col-md-12 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Today's DVP</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $current_date = date("Y-m-d");
                                    $query = "SELECT COUNT(depot) FROM dvp_data WHERE date = '$current_date' AND division = '{$_SESSION['DIVISION_ID']}'";
                                    $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    $row = mysqli_fetch_array($result);
                                    echo "$row[0]";
                                    ?><a href="depot_dvp_submision.php"> Depot Submitted</a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-bus fa-beat fa-2xl"></i>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="col-md-12 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">yesterday's KMPL
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $current_date = date("Y-m-d", strtotime("-1 day"));
                                    $query = "SELECT COUNT(depot) FROM kmpl_data WHERE date = '$current_date' AND division = '{$_SESSION['DIVISION_ID']}'";
                                    $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    $row = mysqli_fetch_array($result);
                                    echo "$row[0]";
                                    ?><a href="depot_dvp_submision.php"> Depot Submitted</a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-tachometer-alt fa-beat fa-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="col-md-12 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Buses</div>
                                <div class="row no-gutters align-items-center">
                                    <div class="col-auto">
                                        <div class="h6 mb-0 mr-3 font-weight-bold text-gray-800">
                                            <?php
                                            $query = "SELECT COUNT(*) FROM bus_registration where division_name = '{$_SESSION['DIVISION_ID']}'";
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
                                <i class="fa-solid fa-bus fa-beat fa-2xl"></i>
                                <i class="fa-solid fa-bus fa-beat fa-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
        <div class="col-md-12 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Driver Employees</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="driver-count">
                                <span class="loading">Drivers: <i class="fa-solid fa-spinner fa-spin fa-lg"></i></span>
                            </div>
                            <?php
                            $session_division = $_SESSION['DIVISION_ID']; // Session-based division
                                    $session_depot = $_SESSION['DEPOT_ID']; // Session-based depot
                                
                                    // SQL query to count only private drivers for the current division and depot
                                    $query2 = "SELECT COUNT(*) 
                               FROM private_employee
                               INNER JOIN location ON private_employee.division_id = location.division_id 
                                                   AND private_employee.depot_id = location.depot_id
                               WHERE private_employee.status = '1' 
                                 AND EMP_DESGN_AT_APPOINTMENT = 'DRIVER' ";

                                    // Execute the query
                                    $result2 = mysqli_query($db, $query2) or die(mysqli_error($db));

                                    // Fetch the count from the result
                                    $row2 = mysqli_fetch_array($result2);

                                    // Output the count of private drivers
                                    echo "Private Drivers: $row2[0]";
                                    ?> Record(s)
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-id-card fa-beat fa-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="col-md-12 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Conductor Employees</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="conductor-count">
                                <span class="loading">Conductors: <i class="fa-solid fa-spinner fa-spin fa-lg"></i></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-id-card fa-beat fa-2xl"></i>
                        </div>
                                   
                                    <?php
                                    $session_division = $_SESSION['DIVISION_ID']; // Session-based division
                                    $session_depot = $_SESSION['DEPOT_ID']; // Session-based depot
                                
                                    // SQL query to count only private drivers for the current division and depot
                                    $query2 = "SELECT COUNT(*) 
                               FROM private_employee
                               INNER JOIN location ON private_employee.division_id = location.division_id 
                                                   AND private_employee.depot_id = location.depot_id
                               WHERE private_employee.status = '1' 
                                 AND EMP_DESGN_AT_APPOINTMENT = 'CONDUCTOR'";

                                    // Execute the query
                                    $result2 = mysqli_query($db, $query2) or die(mysqli_error($db));

                                    // Fetch the count from the result
                                    $row2 = mysqli_fetch_array($result2);

                                    // Output the count of private drivers
                                    echo "Private Conductor: $row2[0]";
                                    ?> Record(s)
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="col-md-12 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">DCC Employees</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="dcc-count">
                                <span class="loading">DCC: <i class="fa-solid fa-spinner fa-spin fa-lg"></i></span>
                            </div>
                                    <?php
                                    $session_division = $_SESSION['DIVISION_ID']; // Session-based division
                                    $session_depot = $_SESSION['DEPOT_ID']; // Session-based depot
                                
                                    // SQL query to count only private drivers for the current division and depot
                                    $query2 = "SELECT COUNT(*) 
                               FROM private_employee
                               INNER JOIN location ON private_employee.division_id = location.division_id 
                                                   AND private_employee.depot_id = location.depot_id
                               WHERE private_employee.status = '1' 
                                 AND EMP_DESGN_AT_APPOINTMENT = 'DRIVER-CUM-CONDUCTOR'";

                                    // Execute the query
                                    $result2 = mysqli_query($db, $query2) or die(mysqli_error($db));

                                    // Fetch the count from the result
                                    $row2 = mysqli_fetch_array($result2);

                                    // Output the count of private drivers
                                    echo "Private DCC: $row2[0]";
                                    ?> Record(s)
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-id-card fa-beat fa-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $.ajax({
                url: '../database/fetch_employee_count.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#driver-count').html('Drivers: ' + data.drivers + ' Record(s)');
                    $('#conductor-count').html('Conductors: ' + data.conductors + ' Record(s)');
                    $('#dcc-count').html('DCC: ' + data.dcc + ' Record(s)');
                },
                error: function() {
                    $('#driver-count').html('Error loading data.');
                    $('#conductor-count').html('Error loading data.');
                    $('#dcc-count').html('Error loading data.');
                }
            });
        });
    </script>





    <div>
    <div class="row show-grid">
        <div class="col-md-3">
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
              WHERE status = 'off_road' AND division = '{$_SESSION['DIVISION_ID']}'";

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
            <div class="col-md-12 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Employee</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $query = "SELECT COUNT(*) FROM employee 
                INNER JOIN location ON employee.LOCATION_ID = location.LOCATION_ID 
                WHERE location.division = '{$_SESSION['DIVISION']}'";
                                    $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    $row = mysqli_fetch_array($result);
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
        <div class="col-md-3">
            <div class="col-md-12 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Today's DVP</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $current_date = date("Y-m-d");
                                    $query = "SELECT COUNT(DISTINCT depot) FROM dvp_data WHERE date = '$current_date' AND division = '{$_SESSION['DIVISION_ID']}'";
                                    $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    $row = mysqli_fetch_array($result);
                                    echo "$row[0]";
                                    ?> <a href="division_depot_dvp.php"> Depot Submitted</a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-bus fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">yesterday's KMPL
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $current_date = date("Y-m-d", strtotime("-1 day"));
                                    $query = "SELECT COUNT(DISTINCT depot) FROM kmpl_data WHERE date = '$current_date' AND division = '{$_SESSION['DIVISION_ID']}'";
                                    $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    $row = mysqli_fetch_array($result);
                                    echo "$row[0]";
                                    ?> <a href="division_depot_dvp.php"> Depot Submitted</a>
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
        <div class="col-md-3">
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
                                            $query = "SELECT COUNT(*) FROM bus_registration WHERE division_name = '{$_SESSION['DIVISION_ID']}'";
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
                                    $session_division = $_SESSION['DIVISION'];
                                    $query = "SELECT COUNT(*) FROM users 
                INNER JOIN employee ON users.PF_ID = employee.PF_ID 
                INNER JOIN location ON employee.LOCATION_ID = location.LOCATION_ID 
                WHERE users.TYPE_ID IN (2, 3) 
                AND location.DIVISION = '$session_division'";
                                    $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    $row = mysqli_fetch_array($result);
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
                                    <div class="panel-body">
                                        <div class="list-group">
                                            <?php
                                            $query = "SELECT bus_number FROM off_road_data WHERE division = '{$_SESSION['DIVISION_ID']}' and status = 'off_road' ORDER BY submission_datetime DESC LIMIT 5";
                                            $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "<a href='#' class='list-group-item text-gray-800'><i class='fa fa-tasks fa-fw'></i> $row[0]</a>";
                                            }
                                            ?>
                                        </div>
                                        <a href="division_ORP.php" class="btn btn-default btn-block">View All Off
                                            Roads</a>
                                    </div>
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