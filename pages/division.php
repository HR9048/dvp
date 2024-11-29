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
                                    ?><a href="division_depot_dvp.php"> Depot Submitted</a>
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
                                    ?><a href="division_depot_dvp.php"> Depot Submitted</a>
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
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Driver Employees
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800" id="driver-count">
                                    <span class="loading">Drivers: <i class="fa-solid fa-spinner fa-spin fa-lg"></i></span>
                                </div>
                                <?php
                                $session_division = $_SESSION['DIVISION_ID']; // Session-based division
                                $session_depot = $_SESSION['DEPOT_ID']; // Session-based depot
                            
                                // SQL query to count only private drivers for the current division and depot
                                $query2 = "SELECT COUNT(*) 
                               FROM private_employee
                               WHERE private_employee.status = '1' 
                                 AND EMP_DESGN_AT_APPOINTMENT = 'DRIVER' and division_id = '{$_SESSION['DIVISION_ID']}'";

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
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Conductor Employees
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800" id="conductor-count">
                                    <span class="loading">Conductors: <i
                                            class="fa-solid fa-spinner fa-spin fa-lg"></i></span>
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
                               WHERE private_employee.status = '1' 
                                 AND EMP_DESGN_AT_APPOINTMENT = 'CONDUCTOR'  and division_id = '{$_SESSION['DIVISION_ID']}'";

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
                               WHERE private_employee.status = '1' 
                                 AND EMP_DESGN_AT_APPOINTMENT = 'DRIVER-CUM-CONDUCTOR'  and division_id = '{$_SESSION['DIVISION_ID']}'";

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
    </div>
    <script>
        $(document).ready(function () {
            $.ajax({
                url: '../database/division_fetch_employee_count.php',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    $('#driver-count').html('Drivers: ' + data.drivers + ' Record(s)');
                    $('#conductor-count').html('Conductors: ' + data.conductors + ' Record(s)');
                    $('#dcc-count').html('DCC: ' + data.dcc + ' Record(s)');
                },
                error: function () {
                    $('#driver-count').html('Error loading data.');
                    $('#conductor-count').html('Error loading data.');
                    $('#dcc-count').html('Error loading data.');
                }
            });
        });
    </script>
    <!--<div class="col-lg-6 mb-4">
        <div class="container">
            <h3>KMPL Data of Division: <?php echo $_SESSION['DIVISION'] ?></h3>
            <canvas id="divisionkmplChart"></canvas>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    fetchKMPLComparisonData();

    function fetchKMPLComparisonData() {
        fetch("../database/division_kmpl_chart.php") // This file should fetch data for the specific month
            .then(response => response.json())
            .then(data => {
                const dates = [];
                const depots = new Set();
                const kmplData = {};  // Data structure to hold KMPL values for each depot and date

                // Organize data by date and depot
                data.forEach(entry => {
                    // Convert the database date format (yyyy-mm-dd) into a Date object
                    const dateObj = new Date(entry.date);
                    // Format the date as "dd, mmm yyyy" (e.g., 01, Jan 2024)
                    const formattedDate = dateObj.toLocaleDateString("en-GB", {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });

                    if (!dates.includes(formattedDate)) {
                        dates.push(formattedDate); // Add formatted date to x-axis labels
                    }
                    depots.add(entry.depot_name); // Add depot to the set

                    // Initialize kmplData if not already
                    if (!kmplData[entry.depot_name]) kmplData[entry.depot_name] = {};
                    kmplData[entry.depot_name][formattedDate] = entry.kmpl;
                });

                // Prepare datasets for each depot (one line per depot)
                const datasets = Array.from(depots).map(depot => {
                    return {
                        label: depot,
                        data: dates.map(date => kmplData[depot][date] || 0), // KMPL value per date
                        borderColor: getRandomColor(), // Random color for each line
                        borderWidth: 2,
                        fill: false, // No fill for lines, change to true if you want filled lines
                        tension: 0.4 // Smooth the line
                    };
                });

                // Generate the Chart
                const ctx = document.getElementById('divisionkmplChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line', // Line chart for comparison
                    data: {
                        labels: dates, // Set dates as x-axis labels
                        datasets: datasets // Set datasets for each depot
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const depot = context.dataset.label;
                                        const date = context.label;
                                        const kmpl = kmplData[depot][date];
                                        return `${depot} - Date: ${date}\nKMPL: ${kmpl}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                },
                                ticks: {
                                    autoSkip: true,
                                    maxTicksLimit: 20,
                                    callback: function(value) {
                                        // Return formatted date as "dd, mmm yyyy"
                                        return value;
                                    }
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'KMPL'
                                },
                                ticks: {
                                    beginAtZero: false, // Let the chart auto-scale
                                    callback: function(value) {
                                        return value.toFixed(1); // Display only one decimal place
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 2000, // Animation duration in ms
                            easing: 'easeOutQuart', // Easing function (smooth animation)
                            delay: function (context) {
                                return context.index * 50; // Stagger the animation for each line
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching KMPL data:', error));
    }

    // Function to generate random colors for each depot's line
    function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }
});
</script>


    -->

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>