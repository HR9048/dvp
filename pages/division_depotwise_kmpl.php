<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/division_sidebar.php';
include_once 'session.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'DC') {
    // Allow access
    ?>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .container,
            .container * {
                visibility: visible;
            }

            .container {
                width: 100%;
                text-align: right;
                position: absolute;
                top: 0;
                right: 0;
                /* Align container to the right */
            }


        }
    </style>

    <form class="form-inline mb-3" method="GET">
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

    <?php
    // Fetch distinct depot names for the current division
    $division = $_SESSION['DIVISION_ID'];
    $query = "SELECT DISTINCT l.depot AS depotName, l.depot_id AS depot_ID
          FROM kmpl_data kd
          INNER JOIN location l ON kd.depot = l.depot_id
          WHERE kd.division = '$division' order by l.depot_id";
    $result = mysqli_query($db, $query);
    $depotName = $row['depotName'];

    echo $depotName;

    // Start the container
    echo "<div class='container'>";
    ?>
    <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br>
    <div style="display: flex; justify-content: space-between;">
        <h2 style="text-align:left; padding: 2%; margin: 0;">DIVISION:<?php echo $_SESSION['DIVISION']; ?>
        </h2>
        <h2 style="text-align:center; padding: 2%; margin: 0;">Depot wise KMPL</h2>
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
                    echo date('F Y');
                }
            } else {
                echo date('F Y');
            }
            ?>

        </h2>
    </div>
    <?php

    // Loop through each depot
    while ($row = mysqli_fetch_assoc($result)) {

        $depotName = $row['depotName'];
        $depotID = $row['depot_ID'];
        ?>
        <table>
            <br>
            <h4 style='text-align:center;'><?php echo $depotName; ?> Depot KMPL</h4>

            <thead>
                <tr>
                    <th rowspan="2">Date</th>
                    <th COLspan="3" style="text-align:center;">DAILY KMPL</th>
                    <th COLspan="3" style="text-align:center;">CUMULATIVE KMPL</th>

                </tr>
                <Tr>
                    <th>Gross KM</th>
                    <th>HSD</th>
                    <th>KMPL</th>
                    <th>Gross KM</th>
                    <th>HSD</th>
                    <th>KMPL</th>
                </Tr>
            </thead>
            <tbody>
                <?php
                // Fetch data for daily entry
                if (isset($_GET['year']) && isset($_GET['month'])) {
                    $selected_year = $_GET['year'];
                    $selected_month = $_GET['month'];

                    $sqlDaily = "SELECT * FROM kmpl_data WHERE division = '$division' AND depot = '$depotID' AND YEAR(date) = $selected_year AND MONTH(date) = $selected_month ORDER BY date ASC";
                } else {
                    $sqlDaily = "SELECT * FROM kmpl_data WHERE division = '$division' AND depot = '$depotID' AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) ORDER BY date ASC";
                }
                $resultDaily = mysqli_query($db, $sqlDaily);
                $cumulativeData = array('total_km' => 0, 'hsd' => 0);

                if (mysqli_num_rows($resultDaily) > 0) {
                    while ($rowDaily = mysqli_fetch_assoc($resultDaily)) {
                        echo "<tr>";
                        echo "<td>" . date('d/m/Y', strtotime($rowDaily['date'])) . "</td>";
                        echo "<td>{$rowDaily['total_km']}</td>";
                        echo "<td>{$rowDaily['hsd']}</td>";
                        echo "<td>{$rowDaily['kmpl']}</td>";

                        // Calculate cumulative data
                        $cumulativeData['total_km'] += $rowDaily['total_km'];
                        $cumulativeData['hsd'] += $rowDaily['hsd'];

                        // Calculate KMPL for cumulative data
                        $cumulativeKmpl = ($cumulativeData['hsd'] != 0) ? round($cumulativeData['total_km'] / $cumulativeData['hsd'], 2) : 0;

                        // Output cumulative data
                        echo "<td>{$cumulativeData['total_km']}</td>";
                        echo "<td>{$cumulativeData['hsd']}</td>";
                        echo "<td>{$cumulativeKmpl}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No data available</td></tr>";
                }

                echo "</tbody>";
                echo "</table>";

    }
    ?>
            <br><br>
            <div style="display: flex; justify-content: space-between;">
                <h2 style="text-align:left; padding: 2%; margin: 0;">JA</h2>
                <h2 style="text-align:center; padding: 2%; margin: 0;">DWS</h2>
                <h2 style="text-align:right; padding: 2%; margin: 0;">DME</h2>
            </div>

            <?php
            // End the container
            echo "</div>";
            ?>


            <div class="text-center mt-3">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
            <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>
        <script>
            $(document).ready(function () {
                // Remove month and year parameters from the URL when the page loads
                window.history.replaceState({}, document.title, window.location.pathname);
            });

            // Your existing JavaScript code
        </script>