<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'DC') {
    // Allow access
    ?>
    <style>
        #dataEntryModal {
            display: none;
        }
    </style>
    <button><a href="division_depotwise_kmpl.php">Depot wise KMPL -></a></button>
    <br><br><br>
    <form class="form-inline mb-3">
        <div class="form-group mr-2">
            <?php
            $currentDate = new DateTime();
            $currentYear = $currentDate->format("Y");
            $currentMonth = $currentDate->format("m");
            $startYear = 2024;
            $startMonth = 4;

            // Generate year range
            $year_range = range($startYear, $currentYear);
            ?>

            <label for="year" class="mr-2">Select Year:</label>
            <select class="form-control" name="year" id="year" onchange="this.form.submit()">
                <option value=''>Select Year</option>
                <?php
                foreach ($year_range as $year_val) {
                    $selected_year = (isset($_GET['year']) && $year_val == $_GET['year']) ? 'selected' : '';
                    echo '<option ' . $selected_year . ' value ="' . $year_val . '">' . $year_val . '</option>';
                }
                ?>
            </select>
        </div>
        <?php if (isset($_GET['year'])): // Check if a year is selected ?>
            <div class="form-group mr-2">
                <label for="month" class="mr-2">Select Month:</label>
                <select class="form-control" name="month" id="month" onchange="this.form.submit()">
                    <option value=''>Select Month</option>
                    <?php
                    $month_range = array();
                    $selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
                    $selected_month = isset($_GET['month']) ? $_GET['month'] : date('n');

                    // Calculate start and end month based on selected year
                    $start = ($selected_year == $startYear) ? $startMonth : 1;
                    $end = ($selected_year == $currentYear) ? $currentMonth : 12;

                    for ($i = $start; $i <= $end; $i++) {
                        $month_range[$i] = date("F", mktime(0, 0, 0, $i, 1));
                    }

                    foreach ($month_range as $month_number => $month_name) {
                        $selected = ($selected_month == $month_number) ? 'selected' : '';
                        echo '<option ' . $selected . ' value ="' . $month_number . '">' . $month_name . '</option>';
                    }
                    ?>
                </select>
            </div>
        <?php endif; ?>
    </form>



    <div class="container-fluid mt-5">
        <div class="container1">
            <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br>
            <div style="display: flex; justify-content: space-between;">
                <h2 style="text-align:left; padding: 2%; margin: 0;">DIVISION:<?php echo $_SESSION['DIVISION']; ?>
                </h2>
                <h2 style="text-align:center; padding: 2%; margin: 0;">KMPL</h2>
                <h2 style="text-align:right; padding: 2%; margin: 0;">
                    <?php
                    if (isset($_GET['year']) && isset($_GET['month'])) {
                        $selected_year = $_GET['year'];
                        $selected_month = $_GET['month'];
                        // Check if month is a valid integer
                        if (ctype_digit($selected_month) && $selected_month >= 1 && $selected_month <= 12) {
                            $selected_month_name = date('F', mktime(0, 0, 0, (int) $selected_month, 1));
                            echo $selected_month_name . ' ' . $selected_year;
                        } else {
                            // Handle invalid month
                            echo "Invalid month";
                        }
                    } else {
                        echo date('F Y');
                    }
                    ?>
                </h2>
            </div>

            <?php
            // Check if both year and month are selected
            if (isset($_GET['year']) && isset($_GET['month'])) {
                $selected_year = $_GET['year'];
                $selected_month = $_GET['month'];

                // Get the start and end dates of the selected month
                $start_date = date('Y-m-01', strtotime("$selected_year-$selected_month-01"));
                $end_date = date('Y-m-t', strtotime("$selected_year-$selected_month-01"));

                // Fetch data for the selected year and month
                $sql = "SELECT 
                DATE(date) AS date,
                SUM(total_km) AS total_total_km,
                SUM(hsd) AS total_hsd
            FROM 
                kmpl_data
            WHERE 
                DATE(date) BETWEEN '$start_date' AND '$end_date'
                AND division = '{$_SESSION['DIVISION_ID']}'
            GROUP BY 
                DATE(date)
            ORDER BY 
                DATE(date) ASC";
            } else {
                // Fetch data for the current month
                $current_month_start = date('Y-m-01');
                $current_month_end = date('Y-m-t');

                $sql = "SELECT 
                DATE(date) AS date,
                SUM(total_km) AS total_total_km,
                SUM(hsd) AS total_hsd
            FROM 
                kmpl_data
            WHERE 
                DATE(date) BETWEEN '$current_month_start' AND '$current_month_end'
                AND division = '{$_SESSION['DIVISION_ID']}'
            GROUP BY 
                DATE(date)
            ORDER BY 
                DATE(date) ASC";
            }

            $result = mysqli_query($db, $sql);

            // Check if there are rows returned
            if (mysqli_num_rows($result) > 0) {
                ?>
                <!-- Display the table -->
                <div class="row">
                    <div class="col-lg-12">
                        <!-- Table to display data -->
                        <table>
                            <thead>
                                <tr>
                                    <th rowspan="2">Date</th>
                                    <th colspan="3" style="text-align:center;">DAILY KMPL</th>
                                    <th colspan="3" style="text-align:center;">CUMULATIVE KMPL</th>
                                </tr>
                                <tr>
                                    <th>Gross KM</th>
                                    <th>HSD</th>
                                    <th>KMPL</th>
                                    <th>Gross KM</th>
                                    <th>HSD</th>
                                    <th>KMPL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Initialize cumulative sum variables
                                $cumulative_total_km_sum = 0;
                                $cumulative_hsd_sum = 0;

                                // Output data for each day
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y', strtotime($row['date'])) . "</td>";
                                    echo "<td>" . $row['total_total_km'] . "</td>";
                                    echo "<td>" . $row['total_hsd'] . "</td>";
                                    echo "<td>";
                                    if ($row['total_hsd'] != 0) {
                                        echo number_format(($row['total_total_km'] / $row['total_hsd']), 2);
                                    } else {
                                        echo "0"; // or any appropriate message
                                    }
                                    echo "</td>"; // Daily KMPL
                        
                                    // Update cumulative sums
                                    $cumulative_total_km_sum += $row['total_total_km'];
                                    $cumulative_hsd_sum += $row['total_hsd'];

                                    // Output cumulative values
                                    echo "<td>" . $cumulative_total_km_sum . "</td>";
                                    echo "<td>" . $cumulative_hsd_sum . "</td>";
                                    echo "<td>" . number_format(($cumulative_total_km_sum / $cumulative_hsd_sum), 2) . "</td>"; // Cumulative KMPL
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
            } else {
                echo "<p>No data available.</p>";
            }
            ?>
            <br><br>
            <div style="display: flex; justify-content: space-between;">
                <h2 style="text-align:left; padding: 2%; margin: 0;">JA</h2>
                <h2 style="text-align:center; padding: 2%; margin: 0;">CM/AWS</h2>
                <h2 style="text-align:right; padding: 2%; margin: 0;">DM</h2>
            </div>
        </div>



    </div>

    <!-- Print button -->
    <div class="text-center mt-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>





    <script>
        $(document).ready(function () {
            // Remove month and year parameters from the URL when the page loads
            window.history.replaceState({}, document.title, window.location.pathname);
        });

        // Your existing JavaScript code
    </script>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>