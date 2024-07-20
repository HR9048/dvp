<?php
include '../includes/connection.php';
include '../includes/rwy_sidebar.php';

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
    <?php } elseif ($Aa == 'HEAD-OFFICE') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Central office Page");
            window.location = "division.php";
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
              FROM rwy_offroad
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


    </div>
    <div class="col-md-3">
        <!-- <div class="col-md-12 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Vehicle Allocation</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $query = "SELECT 
                COUNT(*) as total_off_road_count
              FROM rwy_bus_allocation";

                                // Execute the query
                                $result = mysqli_query($db, $query) or die(mysqli_error($db));

                                // Fetch the count
                                $row = mysqli_fetch_array($result);

                                // Output the count
                                echo "$row[0]";
                                ?>
                                vehicle allowcated
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>-->
            </div> 
        </div>

    </div>
    <div class="col-md-3">


    </div>
</div>


<?php include '../includes/footer.php'; ?>