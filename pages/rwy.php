<?php
include '../includes/connection.php';
include '../includes/rwy_sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'RWY' && $_SESSION['JOB_TITLE'] == 'WM') {
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
        
    </div>

    </div>
    <div class="col-md-3">


    </div>
    </div>


    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>