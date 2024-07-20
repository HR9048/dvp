<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

$query = 'SELECT ID, t.TYPE FROM users u JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DEPOT') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Depot Page");
            window.location = "../includes/depot_verify.php";
        </script>
    <?php } elseif ($Aa == 'DIVISION') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
        </script>
    <?php } elseif ($Aa == 'RWY') {
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to RWY Page");
            window.location = "rwy.php";
        </script>
    <?php } 
}
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
              WHERE status = 'off_road'";

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
                ";
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
                                $query = "SELECT COUNT(depot) FROM dvp_data WHERE date = '$current_date'";
                                $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                $row = mysqli_fetch_array($result);
                                echo "$row[0]";
                                ?><a href="depot_dvp_submision.php"> Depot Submitted</a>
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
                                $query = "SELECT COUNT(depot) FROM kmpl_data WHERE date = '$current_date' ";
                                $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                $row = mysqli_fetch_array($result);
                                echo "$row[0]";
                                ?><a href="depot_dvp_submision.php"> Depot Submitted</a>
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
                                        $query = "SELECT COUNT(*) FROM bus_registration ";
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
                WHERE users.TYPE_ID IN (1,2,3,4)";
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
                                        $query = "SELECT bus_number FROM off_road_data WHERE status = 'off_road' ORDER BY submission_datetime DESC LIMIT 5";
                                        $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<a href='#' class='list-group-item text-gray-800'><i class='fa fa-tasks fa-fw'></i> $row[0]</a>";
                                        }
                                        ?>
                                    </div>
                                    <a href="main_off_road.php" class="btn btn-default btn-block">View All Off
                                        Roads</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>