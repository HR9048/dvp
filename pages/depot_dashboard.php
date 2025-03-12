<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') { ?>
    <div class="row show-grid">

        <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech') { ?>
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
                                    <i class="fa-solid fa-tools fa-beat fa-2x text-gray-300"></i>
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
                                    <i class="fa-solid fa-bus fa-beat fa-2x text-gray-300"></i>
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
                                    <i class="fa-solid fa-bus fa-beat fa-2x text-gray-300"></i>
                                    <i class="fa-solid fa-bus fa-beat fa-2x text-gray-300"></i>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM') { ?>
                <div class="col-md-3">
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
                                        <i class="fa-solid fa-tachometer-alt fa-beat fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php }
        if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') {
            $division_id = $_SESSION['DIVISION_ID'];
            $depot_id = $_SESSION['DEPOT_ID'];
            $division = $_SESSION['KMPL_DIVISION'];
            $depot = $_SESSION['KMPL_DEPOT']; ?>

            <div class="col-md-3">
                <div class="col-md-12 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-0">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Driver
                                        Employees
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        <?php
                                        $apiUrl = 'http://localhost:8880/dvp/includes/data.php';

                                        $apiUrl .= '?division=' . urlencode($division) . '&depot=' . urlencode($depot);

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

                                        // Filter the data based on division and depot (already filtered from the API)
                                        $filteredData = array_filter($data['data'], function ($item) {
                                            return $item['EMP_DESGN_AT_APPOINTMENT'] === 'DRIVER';
                                        });

                                        // Count the filtered records
                                        $totalOffRoadCount = count($filteredData);

                                        // Output the count
                                        echo 'Drivers: ' . $totalOffRoadCount;
                                        ?> Record(s)

                                        <?php
                                        $session_division = $_SESSION['DIVISION_ID']; // Assuming you're getting this from a session variable
                                        $session_depot = $_SESSION['DEPOT_ID']; // Assuming you're getting this from a session variable
                                
                                        // Prepare the SQL query to count registered accounts based on division and depot names
                                        $query2 = "SELECT COUNT(*)
                                        FROM private_employee
                                        INNER JOIN location ON private_employee.division_id = location.division_id and private_employee.depot_id = location.depot_id
                                        WHERE private_employee.status = '1' and EMP_DESGN_AT_APPOINTMENT='DRIVER'
                                        AND location.division_id = '$session_division' 
                                        AND location.depot_id = '$session_depot'";

                                        // Execute the query
                                        $result2 = mysqli_query($db, $query2) or die(mysqli_error($db));

                                        // Fetch the count
                                        $row2 = mysqli_fetch_array($result2);

                                        // Output the count
                                        echo "Private Drivers: $row2[0]";
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
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-0">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Conductors
                                        Employees
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        <?php

                                        // Append division and depot as query parameters to the URL
                                        $apiUrl = 'http://localhost:8880/dvp/includes/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);

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

                                        // Filter the data for conductors only
                                        $filteredData = array_filter($data['data'], function ($item) {
                                            return $item['EMP_DESGN_AT_APPOINTMENT'] === 'CONDUCTOR';
                                        });

                                        // Count the filtered records
                                        $totalOffRoadCount = count($filteredData);

                                        // Output the count
                                        echo 'Conductors: ' . $totalOffRoadCount;
                                        ?>Record(s)
                                        <?php
                                        $session_division = $_SESSION['DIVISION_ID']; // Assuming you're getting this from a session variable
                                        $session_depot = $_SESSION['DEPOT_ID']; // Assuming you're getting this from a session variable
                                
                                        // Prepare the SQL query to count registered accounts based on division and depot names
                                        $query2 = "SELECT COUNT(*)
                                        FROM private_employee
                                        INNER JOIN location ON private_employee.division_id = location.division_id and private_employee.depot_id = location.depot_id
                                        WHERE private_employee.status = '1' and EMP_DESGN_AT_APPOINTMENT='CONDUCTOR'
                                        AND location.division_id = '$session_division' 
                                        AND location.depot_id = '$session_depot'";

                                        // Execute the query
                                        $result2 = mysqli_query($db, $query2) or die(mysqli_error($db));

                                        // Fetch the count
                                        $row2 = mysqli_fetch_array($result2);

                                        // Output the count
                                        echo "Private Conductor: $row2[0]";
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
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">

                                <div class="col mr-0">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">DCC Employees
                                    </div>
                                    <div class="row no-gutters align-items-center">
                                        <div class="col-auto">
                                            <div class="h6 mb-0 mr-3 font-weight-bold text-gray-800">
                                                <?php
                                                $apiUrl = 'http://localhost:8880/dvp/includes/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);

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

                                                // Filter the data for DRIVER-CUM-CONDUCTOR only
                                                $filteredData = array_filter($data['data'], function ($item) {
                                                    return $item['EMP_DESGN_AT_APPOINTMENT'] === 'DRIVER-CUM-CONDUCTOR';
                                                });

                                                // Count the filtered records
                                                $totalOffRoadCount = count($filteredData);

                                                // Output the count
                                                echo 'DCC: ' . $totalOffRoadCount;
                                                ?>
                                                Record(s)
                                                <?php
                                                $session_division = $_SESSION['DIVISION_ID']; // Assuming you're getting this from a session variable
                                                $session_depot = $_SESSION['DEPOT_ID']; // Assuming you're getting this from a session variable
                                        
                                                // Prepare the SQL query to count registered accounts based on division and depot names
                                                $query2 = "SELECT COUNT(*)
                                        FROM private_employee
                                        INNER JOIN location ON private_employee.division_id = location.division_id and private_employee.depot_id = location.depot_id
                                        WHERE private_employee.status = '1' and EMP_DESGN_AT_APPOINTMENT='DRIVER-CUM-CONDUCTOR'
                                        AND location.division_id = '$session_division' 
                                        AND location.depot_id = '$session_depot'";
                                                // Execute the query
                                                $result2 = mysqli_query($db, $query2) or die(mysqli_error($db));

                                                // Fetch the count
                                                $row2 = mysqli_fetch_array($result2);

                                                // Output the count
                                                echo "Private DCC: $row2[0]";
                                                ?>
                                                Record(s)
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

            </div>
            <div class="col-md-3">
                <div class="col-md-12 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-0">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Vehicles on
                                        Schedule
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
                                    <i class="fa-regular fa-calendar-days fa-beat-fade fa-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <!--<div class="col-lg-8 mb-4">
        <div class="container">
            <h1>KMPL Data by Division</h1>
            <canvas id="dvpChart"></canvas>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        fetch('../database/depot_kmpl.php')
            .then(response => response.json())
            .then(data => {
                const labels = [...new Set(data.map(item => item.date))]; // Unique dates
                const depots = [...new Set(data.map(item => item.depot))]; // Unique depots

                const datasets = depots.map(depot => {
                    return {
                        label: depot,
                        data: labels.map(date => {
                            const entry = data.find(item => item.depot === depot && item.date === date);
                            return entry ? {
                                x: date, // X-axis is date
                                y: entry.ORDepot_count + entry.ORDWS_count + entry.Police_count + entry.Dealer_count, // Y-axis is total count
                                details: entry // Store full data in each point for tooltip access
                            } : { x: date, y: 0, details: { ORDepot_count: 0, ORDWS_count: 0, Police_count: 0, Dealer_count: 0 } };
                        }),
                        borderColor: getRandomColor(),
                        fill: false,
                        tension: 0.1,
                    };
                });

                const ctx = document.getElementById('dvpChart').getContext('2d');
                console.log("Datasets: ", datasets); // Check dataset values
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels, // X-axis (dates)
                        datasets: datasets, // Y-axis data
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false,
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const data = context.raw.details;
                                        return [
                                            `Total Off-road: ${data.total_offroad}`,
                                            `Depot Off-road: ${data.ORDepot_count}`,
                                            `DWS Off-road: ${data.ORDWS_count}`,
                                            `Held at Police: ${data.Police_count}`,
                                            `Held at Dealer: ${data.Dealer_count}`
                                        ];
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                min: 0,
                                max: 10000, // Set max for testing
                                title: {
                                    display: true,
                                    text: 'Total Counts'
                                }
                            }
                        }

                    }
                });

                function getRandomColor() {
                    const letters = '0123456789ABCDEF';
                    let color = '#';
                    for (let i = 0; i < 6; i++) {
                        color += letters[Math.floor(Math.random() * 16)];
                    }
                    return color;
                }
            });

    </script>-->
    <?php
    if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') {
    // Query for Deputation Requests (status = 1)
    $sql_deputation = "SELECT * FROM `crew_deputation` 
                       WHERE `status` = '1' AND `t_depot_id` = '" . $_SESSION['DEPOT_ID'] . "' 
                       GROUP BY `token_number`";
    $result_deputation = mysqli_query($db, $sql_deputation);
    $count_deputation = mysqli_num_rows($result_deputation);

    // Query for Deputed Vehicles Returned (status = 3)
    $sql_returned = "SELECT * FROM `crew_deputation` 
                     WHERE `status` = '3' AND `f_depot_id` = '" . $_SESSION['DEPOT_ID'] . "' 
                     GROUP BY `token_number`";
    $result_returned = mysqli_query($db, $sql_returned);
    $count_returned = mysqli_num_rows($result_returned);

    // Total count for notification
    $total_count = $count_deputation + $count_returned;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech') {
    // Query for Deputation Requests (status = 1)
    $sql_deputation = "SELECT * FROM `vehicle_deputation` 
                       WHERE `status` = '1' AND `t_depot_id` = '" . $_SESSION['DEPOT_ID'] . "' 
                       GROUP BY `bus_number`";
    $result_deputation = mysqli_query($db, $sql_deputation);
    $count_deputation = mysqli_num_rows($result_deputation);

    // Query for Deputed Vehicles Returned (status = 3)
    $sql_returned = "SELECT * FROM `vehicle_deputation` 
                     WHERE `status` = '3' AND `f_depot_id` = '" . $_SESSION['DEPOT_ID'] . "' 
                     GROUP BY `bus_number`";
    $result_returned = mysqli_query($db, $sql_returned);
    $count_returned = mysqli_num_rows($result_returned);

    // Total count for notification
    $total_count = $count_deputation + $count_returned;
}
?>
<!-- Notification Popup -->

<?php if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') {
     if ($total_count > 0) { ?>
    <div id="notificationPopup" class="notification-popup">
        <i class="fas fa-bell"></i>
        <span>You have <b><?php echo $total_count; ?></b> new notifications</span>
        <span class="close-btn">&times;</span>
    </div>
<?php } } ?>

<script>
    $(document).ready(function () {
        var popup = $("#notificationPopup");

        if (popup.length > 0) {
            // Slide in and show popup
            setTimeout(function () {
                popup.css({
                    "right": "20px",
                    "animation": "slideIn 0.7s ease-out"
                });
            }, 500);

            // Auto-hide after 5 seconds
            setTimeout(function () {
                popup.fadeOut(1000);
            }, 5000);
        }

        // Close on click
        $(".close-btn").click(function () {
            popup.fadeOut(500);
        });
    });
</script>

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>