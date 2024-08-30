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

        <div class="col-md-4">
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
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
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
        <div class="col-md-4">
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
        <div class="col-lg-6 mb-4">
            <div class="container">
                <h1>Off-Road Data by Division</h1>
                <canvas id="lineChart"></canvas>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="container">
                <h1>Schedules Vehicles on Route</h1>
                <canvas id="scheduleChart"></canvas>
            </div>
        </div>


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
                                tooltip: {
                                    callbacks: {
                                        title: function (context) {
                                            return context[0].label;
                                        },
                                        label: function (context) {
                                            const label = context.label || '';
                                            const value = context.raw || '';
                                            return `${label}: ${value}`;
                                        },
                                        footer: function (context) {
                                            const division = context[0].label || '';
                                            const isRWY = division === 'RWY Off-Road';
                                            if (isRWY) {
                                                // Show division-wise RWY counts in the footer
                                                return rwyData.map(d => `${d.division}: ${d.off_road_count}`);
                                            } else {
                                                const depotData = depots.filter(d => d.division === division);
                                                if (depotData.length) {
                                                    // Create footer content with line breaks for depots
                                                    return ['Depots:', ...depotData.map(d => `${d.depot}: ${d.off_road_count}`)];
                                                }
                                            }
                                            return [];
                                        }
                                    },
                                    // Enable HTML rendering
                                    enabled: true
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
            fetch('../database/get_circle_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }
                    console.log(data); // Log the fetched data for debugging

                    const chartData = data.chartData;
                    const depotData = data.depotData;

                    // Extract unique divisions and locations
                    const divisions = [...new Set(chartData.map(item => item.division))];
                    const locations = [...new Set(depotData.map(item => item.off_road_location))];

                    // Prepare dataset for each location
                    const datasets = locations.map(location => {
                        return {
                            label: location,
                            data: chartData.map(item => item.locations[location] || 0),
                            fill: false,
                            borderColor: getRandomColor(),
                            tension: 0.1
                        };
                    });

                    // Prepare data for the chart
                    const ctx = document.getElementById('lineChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: divisions,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        title: function (tooltipItem) {
                                            const division = tooltipItem[0].label;
                                            const location = tooltipItem[0].dataset.label;

                                            // Calculate total for this off-road location in the current division
                                            const totalForLocation = chartData
                                                .find(item => item.division === division)
                                                ?.locations[location] || 0;

                                            return `${division} - Off-road: ${location} (Total: ${totalForLocation})`;
                                        },
                                        label: function (tooltipItem) {
                                            const division = tooltipItem.label;
                                            const location = tooltipItem.dataset.label;

                                            // Filter depot data for the current division and location
                                            const depots = depotData
                                                .filter(item => item.division === division && item.off_road_location === location)
                                                .map(item => `${item.depot}: ${item.off_road_count}`);

                                            // Combine depot details with new lines
                                            return ['' + location, ...depots];
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Division'
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Off-Road Count'
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching data:', error));

            function getRandomColor() {
                const letters = '0123456789ABCDEF';
                let color = '#';
                for (let i = 0; i < 6; i++) {
                    color += letters[Math.floor(Math.random() * 16)];
                }
                return color;
            }
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
        <script>
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
        </script>
        <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>