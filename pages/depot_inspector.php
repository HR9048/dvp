<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division = $_SESSION['KMPL_DIVISION'];
    $depot = $_SESSION['KMPL_DEPOT'];
    ?>
    <div class="row show-grid">

        <div class="col-md-4">
            <div class="col-md-12 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Driver Employees
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $apiUrl = 'http://localhost:8880/dvp/includes/data.php';

                                    // Initialize cURL session
                                    $ch = curl_init($apiUrl);

                                    // Set cURL options
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
                                    curl_setopt($ch, CURLOPT_HTTPGET, true);        // Use GET method
                                
                                    // Execute cURL request
                                    $response = curl_exec($ch);

                                    // Check for cURL errors
                                    if (curl_errno($ch)) {
                                        echo 'Request Error: ' . curl_error($ch);
                                        exit;
                                    }

                                    // Close cURL session
                                    curl_close($ch);

                                    // Decode JSON response
                                    $data = json_decode($response, true);

                                    // Filter the data based on division and depot
                                    $filteredData = array_filter($data['data'], function ($item) use ($division, $depot) {
                                        return $item['Division'] === $division && $item['Depot'] === $depot && $item['EMP_DESGN_AT_APPOINTMENT'] === 'DRIVER';
                                    });

                                    // Count the filtered records
                                    $totalOffRoadCount = count($filteredData);

                                    // Output the count
                                    echo 'Drivers : ' . $totalOffRoadCount;
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
            <div class="col-md-12 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Vehicles on Schedule
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $query = "SELECT COUNT(*) FROM sch_veh_out where schedule_status='1' and division_id=$division_id and depot_id=$depot_id";
                                    $result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    $row = mysqli_fetch_array($result);
                                    echo "Vehicle Count: $row[0]";
                                    ?>
                                    Record(s)
                                </div>
                            </div>
                            <div class="col-auto">
                            <i class="fa-regular fa-calendar-days fa-beat-fade fa-2xl"></i>                       </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-md-4">
            <div class="col-md-12 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Conductors Employees
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    $apiUrl = 'http://localhost:8880/dvp/includes/data.php';

                                    // Initialize cURL session
                                    $ch = curl_init($apiUrl);

                                    // Set cURL options
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
                                    curl_setopt($ch, CURLOPT_HTTPGET, true);        // Use GET method
                                
                                    // Execute cURL request
                                    $response = curl_exec($ch);

                                    // Check for cURL errors
                                    if (curl_errno($ch)) {
                                        echo 'Request Error: ' . curl_error($ch);
                                        exit;
                                    }

                                    // Close cURL session
                                    curl_close($ch);

                                    // Decode JSON response
                                    $data = json_decode($response, true);

                                    // Filter the data based on division and depot
                                    $filteredData = array_filter($data['data'], function ($item) use ($division, $depot) {
                                        return $item['Division'] === $division && $item['Depot'] === $depot && $item['EMP_DESGN_AT_APPOINTMENT'] === 'CONDUCTOR';
                                    });

                                    // Count the filtered records
                                    $totalOffRoadCount = count($filteredData);

                                    // Output the count
                                    echo 'Conductors : ' . $totalOffRoadCount;
                                    ?> Record(s)</a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-id-card fa-beat fa-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--<div class="col-md-12 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">yesterday's KMPL
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>-->
        </div>
        <div class="col-md-4">
            <div class="col-md-12 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">

                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">DCC Employees</div>
                                <div class="row no-gutters align-items-center">
                                    <div class="col-auto">
                                        <div class="h6 mb-0 mr-3 font-weight-bold text-gray-800">
                                            <?php
                                            $apiUrl = 'http://localhost:8880/dvp/includes/data.php';

                                            // Initialize cURL session
                                            $ch = curl_init($apiUrl);

                                            // Set cURL options
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
                                            curl_setopt($ch, CURLOPT_HTTPGET, true);        // Use GET method
                                        
                                            // Execute cURL request
                                            $response = curl_exec($ch);

                                            // Check for cURL errors
                                            if (curl_errno($ch)) {
                                                echo 'Request Error: ' . curl_error($ch);
                                                exit;
                                            }

                                            // Close cURL session
                                            curl_close($ch);

                                            // Decode JSON response
                                            $data = json_decode($response, true);

                                            // Filter the data based on division and depot
                                            $filteredData = array_filter($data['data'], function ($item) use ($division, $depot) {
                                                return $item['Division'] === $division && $item['Depot'] === $depot && $item['EMP_DESGN_AT_APPOINTMENT'] === 'DRIVER-CUM-CONDUCTOR';
                                            });

                                            // Count the filtered records
                                            $totalOffRoadCount = count($filteredData);

                                            // Output the count
                                            echo 'DCC : ' . $totalOffRoadCount;
                                            ?> Record(s)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-auto">
                                <i class="fa-solid fa-id-card fa-beat fa-2xl"></i>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
           <!-- <div class="col-md-12 mb-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Account</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    
                                    Record(s)
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>-->
        </div>
        <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>