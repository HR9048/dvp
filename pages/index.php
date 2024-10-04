<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO' || $_SESSION['JOB_TITLE'] == 'CO_STORE') {
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
                                    $query = "SELECT COUNT(*) FROM sch_veh_out where schedule_status='1'";
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
                                    $query = "SELECT COUNT(depot) FROM dvp_data WHERE date = '$current_date'";
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
                                    $query = "SELECT COUNT(depot) FROM kmpl_data WHERE date = '$current_date' ";
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
                                <i class="fa-solid fa-bus fa-beat fa-2xl"></i>
                                <i class="fa-solid fa-bus fa-beat fa-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--<div class="col-md-3">
            <div class="col-md-12 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Account</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    //$session_division = $_SESSION['DIVISION'];
                                    //$query = "SELECT COUNT(*) FROM users 
                                    //INNER JOIN employee ON users.PF_ID = employee.PF_ID 
                                    //INNER JOIN location ON employee.LOCATION_ID = location.LOCATION_ID 
                                    //WHERE users.TYPE_ID IN (1,2,3,4)";
                                    //$result = mysqli_query($db, $query) or die(mysqli_error($db));
                                    //$row = mysqli_fetch_array($result);
                                    // echo "Registered accounts: $row[0]";
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
        </div>-->




        <div class="col-md-3">
            <div class="col-md-12 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-0">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Driver Employees
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    // Query to get all division and depot combinations
                                    $query = "SELECT kmpl_division, kmpl_depot FROM location";
                                    $result = mysqli_query($db, $query);

                                    if (!$result) {
                                        die("Error fetching division and depot data: " . mysqli_error($db));
                                    }

                                    // Prepare to store all the combined data
                                    $allData = [];

                                    // Array to hold cURL handles
                                    $curlHandles = [];
                                    $multiCurl = curl_multi_init(); // Initialize multi-cURL handle
                                
                                    // Loop through each division and depot, and prepare the cURL requests
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $division = $row['kmpl_division'];
                                        $depot = $row['kmpl_depot'];

                                        // Prepare API URL with division and depot
                                        $apiUrl = 'http://localhost/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);

                                        // Initialize individual cURL session
                                        $ch = curl_init($apiUrl);

                                        // Set cURL options
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
                                        curl_setopt($ch, CURLOPT_HTTPGET, true);        // Use GET method
                                
                                        // Add the handle to the multi-cURL handle
                                        curl_multi_add_handle($multiCurl, $ch);

                                        // Store the cURL handle to reference it later
                                        $curlHandles[] = $ch;
                                    }

                                    // Execute all cURL requests in parallel
                                    $running = null;
                                    do {
                                        curl_multi_exec($multiCurl, $running);
                                        curl_multi_select($multiCurl);
                                    } while ($running > 0);

                                    // Collect the responses and merge the data
                                    foreach ($curlHandles as $ch) {
                                        $response = curl_multi_getcontent($ch); // Get the content from each handle
                                
                                        // Decode JSON response
                                        $data = json_decode($response, true);

                                        // Check if data exists
                                        if (isset($data['data']) && is_array($data['data'])) {
                                            // Merge the current API response data into the $allData array
                                            $allData = array_merge($allData, $data['data']);
                                        }

                                        // Remove the handle from the multi-cURL handler and close it
                                        curl_multi_remove_handle($multiCurl, $ch);
                                        curl_close($ch);
                                    }

                                    // Close the multi-cURL handle
                                    curl_multi_close($multiCurl);

                                    // Now you have all the data combined in $allData
                                    if (empty($allData)) {
                                        echo 'No data available.';
                                    } else {
                                        // Filter and count the 'DRIVER' employees
                                        $filteredData = array_filter($allData, function ($item) {
                                            return $item['EMP_DESGN_AT_APPOINTMENT'] === 'DRIVER';
                                        });

                                        // Count the filtered records
                                        $totalDriverCount = count($filteredData);

                                        // Filter and count the 'CONDUCTOR' employees
                                        $filteredData1 = array_filter($allData, function ($item) {
                                            return $item['EMP_DESGN_AT_APPOINTMENT'] === 'CONDUCTOR';
                                        });

                                        // Count the filtered records
                                        $totalConductorCount = count($filteredData1);
                                        $filteredData2 = array_filter($allData, function ($item) {
                                            return $item['EMP_DESGN_AT_APPOINTMENT'] === 'DRIVER-CUM-CONDUCTOR';
                                        });

                                        // Count the filtered records
                                        $totalDCCCount = count($filteredData2);

                                        // Output the driver count
                                        echo 'Drivers: ' . $totalDriverCount . ' Record(s)';
                                    }
                                    ?>
                                    <?php
                                    $session_division = $_SESSION['DIVISION_ID']; // Session-based division
                                    $session_depot = $_SESSION['DEPOT_ID']; // Session-based depot
                                
                                    // SQL query to count only private drivers for the current division and depot
                                    $query2 = "SELECT COUNT(*) 
                               FROM private_employee
                               INNER JOIN location ON private_employee.division_id = location.division_id 
                                                   AND private_employee.depot_id = location.depot_id
                               WHERE private_employee.status = '1' 
                                 AND EMP_DESGN_AT_APPOINTMENT = 'DRIVER'";

                                    // Execute the query
                                    $result2 = mysqli_query($db, $query2) or die(mysqli_error($db));

                                    // Fetch the count from the result
                                    $row2 = mysqli_fetch_array($result2);

                                    // Output the count of private drivers
                                    echo "Private Drivers: $row2[0]";
                                    ?> Record(s)
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
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Conductor Employees
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    // Output the conductor count
                                    echo 'Conductors: ' . $totalConductorCount . ' Record(s)';
                                    ?>
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
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">DCC Employees</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    <?php
                                    // Output the conductor count
                                    echo 'DCC: ' . $totalDCCCount . ' Record(s)';
                                    ?>
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
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-id-card fa-beat fa-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <h5 class="card-title">Off Road Data by Division</h5>
                    <canvas id="circleChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-4">
            <div class="container">
                <h1>KMPL Data by Division</h1>
                <canvas id="kmplChart"></canvas>
            </div>
        </div>
        <div class="col-lg-12 mb-4">
            <div class="container">
                <h1>Off-Road Data by Division</h1>
                <canvas id="offroadChart"></canvas>
            </div>
        </div>
        <!--<div class="col-lg-6 mb-4">
            <div class="container">
                <h1>Schedules Vehicles on Route</h1>
                <canvas id="scheduleChart"></canvas>
            </div>
        </div>-->


        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <!-- Moment.js and its adapter -->
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
        <script>
            fetch('../database/get_depot_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }

                    const divisions = data.divisions;
                    const depots = data.depots;
                    const rwyData = data.rwy;
                    const overallRWYCount = data.rwyOverallCount;

                    // Extract data for chart
                    const divisionLabels = divisions.map(division => division.division);
                    const divisionCounts = divisions.map(division => division.off_road_count);

                    // Add RWY data to chart
                    const rwyLabel = 'RWY Off-Road';
                    const rwyCounts = [overallRWYCount]; // Single value for RWY

                    // Combine division and RWY data
                    const labels = [...divisionLabels, rwyLabel];
                    const dataCounts = [...divisionCounts, ...rwyCounts];
                    const backgroundColors = ['#FF6384', '#36A2EB', '#FFCE56', '#58d68d', '#145a32', '#abb2b9', '#6e2c00', '#884ea0', '#dc7633', '#17202a'];

                    const ctx = document.getElementById('circleChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: dataCounts,
                                backgroundColor: backgroundColors,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            plugins: {
                                tooltips: {
                                    callbacks: {
                                        label: function (tooltipItem, data) {
                                            const division = data.labels[tooltipItem.index];
                                            const divisionData = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];

                                            // Get depot data for this division and date
                                            const depotData = getDepotDataForDateAndDivision(selectedDate, division);
                                            if (!depotData || depotData.length === 0) {
                                                return `${division}: No data available`;
                                            }

                                            // Construct the tooltip content
                                            let tooltipContent = `${division} Total: ${divisionData.total_offroad_count}\n\nDepot-wise Breakdown:\n`;

                                            depotData.forEach(depot => {
                                                tooltipContent += `${depot.depot}: ${depot.total_offroad_count}\n`;  // Append each depot on a new line
                                            });

                                            return tooltipContent;
                                        }
                                    }
                                },
                                datalabels: {
                                    display: false // Remove data labels
                                }
                            },
                            elements: {
                                center: {
                                    text: dataCounts.reduce((total, count) => total + count, 0),
                                    color: '#FF6384',
                                    fontStyle: 'Arial',
                                    sidePadding: 20
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching data:', error));
        </script>
        <script>
            let divisionData = {};

            const ctx = document.getElementById('offroadChart').getContext('2d');
            const offroadChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Offroad Counts'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Dates'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const division = context.dataset.label;
                                    const date = context.label;
                                    const depotData = getDepotDataForDateAndDivision(date, division);

                                    let label = `${division}: Total Offroad Count: ${context.raw}\n\n`;
                                    if (depotData && depotData.length > 0) {
                                        label += 'Depot-wise Counts:\n';
                                        depotData.forEach(depotInfo => {
                                            label += ` - ${depotInfo.depot}: ${depotInfo.count || 0}\n`;
                                        });

                                    } else {
                                        label += 'No depot-wise data available';
                                    }

                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            // Function to fetch data from the server
            async function fetchData() {
                try {
                    const response = await fetch('../database/get_circle_data.php');
                    const data = await response.json();

                    const dates = new Set();
                    divisionData = {};

                    // Process the data row by row
                    data.forEach(row => {
                        const date = row.date;
                        const division = row.division;
                        const depot = row.depot;
                        const depotCount = parseInt(row.total_offroad_count) || 0; // Ensure the count is treated as a number

                        dates.add(date);

                        // Initialize data structures if not already present
                        if (!divisionData[division]) {
                            divisionData[division] = {
                                data: {},
                                depotData: {}
                            };
                        }

                        // Initialize the date-specific data for the division
                        if (!divisionData[division].data[date]) {
                            divisionData[division].data[date] = 0; // Initialize division total for the date
                        }

                        // Sum depot counts for the division total on this date, avoiding repetition
                        divisionData[division].data[date] += depotCount;

                        if (!divisionData[division].depotData[date]) {
                            divisionData[division].depotData[date] = [];
                        }

                        // Store depot data for tooltips
                        divisionData[division].depotData[date].push({
                            depot: depot,
                            count: depotCount
                        });
                    });

                    // Convert the date set to a sorted array for labels
                    const labels = Array.from(dates).sort();
                    offroadChart.data.labels = labels;

                    // Populate chart datasets with the processed division data
                    for (const division in divisionData) {
                        const dataPoints = labels.map(label => divisionData[division].data[label] || 0);
                        offroadChart.data.datasets.push({
                            label: division,
                            data: dataPoints,
                            borderColor: getRandomColor(),
                            fill: false,
                            pointRadius: 5
                        });
                    }

                    offroadChart.update();
                } catch (error) {
                    console.error('Error fetching data:', error);
                }
            }
            // Function to format the date as "Mon, DD" (e.g., "Jan, 01")
            function formatDateLabel(dateStr) {
                const date = new Date(dateStr);
                const options = { month: 'short', day: 'numeric' };
                return date.toLocaleDateString('en-US', options); // "Jan, 1"
            }

            // Function to convert the formatted date label back to the original date format
            function parseDateLabel(label) {
                const [month, day] = label.split(', '); // Splitting the formatted label
                const fullDate = new Date(`${month} ${day}, ${new Date().getFullYear()}`);
                return fullDate.toISOString().split('T')[0]; // Return the date in YYYY-MM-DD format
            }
            // Function to get depot data for a specific date and division
            function getDepotDataForDateAndDivision(date, division) {
                if (divisionData[division] && divisionData[division].depotData[date]) {
                    return divisionData[division].depotData[date];
                }
                return [];
            }

            // Function to generate random color for each division's line
            function getRandomColor() {
                const letters = '0123456789ABCDEF';
                let color = '#';
                for (let i = 0; i < 9; i++) {
                    color += letters[Math.floor(Math.random() * 16)];
                }
                return color;
            }

            // Fetch data when the page loads
            fetchData();
        </script>
        <script>
            fetch('../database/chart_kmpl.php') // Replace with your actual PHP script path
                .then(response => response.json())
                .then(data => {
                    const labels = [...new Set(data.map(item => item.date))];

                    const datasets = data.reduce((acc, item) => {
                        const existingDivision = acc.find(ds => ds.label === item.division);
                        const depotData = {
                            x: item.date,
                            y: item.avg_kmpl_division,
                            depots: item.depots,
                            total_km_depots: item.total_km_depots,
                            total_hsd_depots: item.total_hsd_depots,
                            avg_kmpl_depots: item.avg_kmpl_depots
                        };

                        if (existingDivision) {
                            existingDivision.data.push(depotData);
                        } else {
                            acc.push({
                                label: item.division,
                                data: [depotData],
                                borderColor: getRandomColor(),
                                fill: false
                            });
                        }

                        return acc;
                    }, []);

                    const chartData = {
                        labels: labels,
                        datasets: datasets
                    };

                    new Chart(document.getElementById('kmplChart'), {
                        type: 'line',
                        data: chartData,
                        options: {
                            scales: {
                                x: {
                                    type: 'time',
                                    time: {
                                        unit: 'day',
                                        tooltipFormat: 'MMM,DD-YYYY' // Display only the date in tooltip
                                    },
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: 'KMPL'
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function (tooltipItem) {
                                            const { depots, avg_kmpl_depots } = tooltipItem.raw;
                                            const depotsArray = depots.split(', ');
                                            const avgKmplArray = avg_kmpl_depots.split(', ');

                                            // Create array for tooltip lines
                                            let tooltipLines = [`${tooltipItem.dataset.label}: ${tooltipItem.parsed.y} KMPL`];
                                            tooltipLines.push('Depot-wise Data:');

                                            // Append each depot's information to tooltipLines
                                            depotsArray.forEach((depot, index) => {
                                                tooltipLines.push(`${depot}: ${avgKmplArray[index]} KMPL`);
                                            });

                                            // Return the array of lines, Chart.js will handle the line breaks
                                            return tooltipLines;
                                        },
                                        title: function (tooltipItem) {
                                            // Show only the date in the tooltip title
                                            return tooltipItem[0].label.split(' ')[0]; // Remove time part
                                        }
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching data:', error));

            function getRandomColor() {
                return `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.7)`;
            }
        </script>
        <!--<script>
            document.addEventListener('DOMContentLoaded', function () {
                fetch('../database/chart_schedule.php')
                    .then(response => response.json())
                    .then(data => {
                        const divisionCounts = data.divisionCounts;
                        const depotCounts = data.depotCounts;

                        const labels = Object.keys(divisionCounts);
                        const dataCounts = labels.map(division => divisionCounts[division]);

                        const chartData = {
                            labels: labels,
                            datasets: [{
                                label: 'Schedules Count by Division',
                                data: dataCounts,
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        };

                        const ctx = document.getElementById('scheduleChart').getContext('2d');
                        const scheduleChart = new Chart(ctx, {
                            type: 'line',
                            data: chartData,
                            options: {
                                responsive: true,
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function (tooltipItem) {
                                                const division = tooltipItem.label;
                                                const depotData = depotCounts[division] || {};
                                                let depotLabels = '';

                                                for (const depot in depotData) {
                                                    depotLabels += `${depot}: ${depotData[depot]} \n`;
                                                }

                                                return `Count: ${tooltipItem.raw}\n${depotLabels}`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    })
                    .catch(error => console.error('Error fetching data:', error));
            });
        </script>-->
        <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>