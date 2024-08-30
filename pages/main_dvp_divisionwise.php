<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO' ||$_SESSION['JOB_TITLE'] == 'CO_STORE' ) {
?>
<style>
    table {
        border-collapse: collapse;
        width: 100%;
        overflow-x: auto;
        table-layout: auto;
        /* Adjust column width dynamically */
    }

    th,
    td {
        border: 2px solid black;
        text-align: left;
        padding: 8px;
        white-space: nowrap;
        /* Prevent text wrapping */
    }

    th {
        background-color: #f2f2f2;
    }

    .container1 {
        overflow-x: auto;
        /* Add horizontal scroll for small screens */
    }
</style>
<button class="btn btn-primary"><a href="main_dvp.php" style="color: white;">Depot wise DVP -></a></button><br><br>

<nav class="navbar bg-body-tertiary">
    <div class="container-fluid">
        <form class="d-flex" action="" method="POST" style="width: 40%; text-align: left;">
            <input type="date" id="selected_date" name="selected_date" max="<?php echo date('Y-m-d'); ?>"
                class="form-control me-2">
            <button class="btn btn-outline-success" style="width: 40%;" type="submit">Show Data</button>
        </form>
    </div>
</nav>

<br>

<?php $formatted_date = date('d/m/Y', strtotime($_POST['selected_date'])); ?>
<div class="container1">
    <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br><br>
    <h2 style="display: inline-block; width: 33%; text-align:left; padding-left: 100px;">CENTRAL OFFICE</h2>
    <h2 style="display: inline-block; width: 33%;text-align:center;">KALABURAGI</h2>
    <h2 style="display: inline-block; width: 33%; text-align:right;">
        <?php echo $formatted_date; ?>
    </h2>

    <?php
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve selected date
        $selectedDate = $_POST['selected_date'];
    }

    // Retrieve data from the database based on session variables and selected date
    $sql = "SELECT * FROM dvp_data WHERE  date = '$selectedDate'";
    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        // Output data in multiple columns
    
        // Fetch division names
        $divisions = array();
        while ($row = $result->fetch_assoc()) {
            if (!in_array($row['division'], $divisions)) {
                $divisions[] = $row['division'];
            }
        }

        // Define custom column headings
        $customColumnsFirst = array(
            'schedules' => 'Number of Schedules',
            'vehicles' => 'Number Of Vehicles(Including RWY)',
            'spare' => 'Number of Spare Vehicles(Including RWY)',
        );

        $customColumnsSecond = array(
            'sparepercentage' => 'Percentage of Spare Vehicles(Excluding RWY)',
        );

        $customColumnsThird = array(
            'docking' => 'Vehicles stopped for Docking',
            'ORDepot' => 'Vehicles Off Road at Depot',
            'ORDWS' => 'Vehicles Off Road at DWS',
            'ORRWY' => 'Vehicles Off Road at RWY',
            'CC' => 'Vehicles Withdrawn for CC',
            'loan' => 'Vehicles loan given to other depot/training center',
            'wup' => 'Vehicles Withdrawn for Fair',
            'Police' => 'Vehicles at Police Station',
            'notdepot' => 'Vehicles Not Arrived to Depot',
            'Dealer' => 'Vehicles Held at Dealer Point',
            'ORTotal' => '<span style="font-weight:bold;">Total Vehicles not Available for Operation</span>',
            'available' => '<span style="font-weight:bold;">Total Vehicles Available for Operation</span>',
            'ES' => '<span style="font-weight:bold;">Vehicles Excess/Shortage</span>',
        );
        ?>


        <?php
        // Define the preferred order of division names
        $preferredDivisionsOrder = array(
            "1" => "KLB-1",
            "2" => "KLB-2",
            "3" => "YDR",
            "4" => "BDR",
            "5" => "RCH",
            "6" => "KPL",
            "7" => "BLR",
            "8" => "HSP",
            "9" => "VJP"
        );
        $preferredDivisionsOrder1 = array(
            "1",
            "2",
            "3",
            "4",
            "5",
            "6",
            "7",
            "8",
            "9"
        );


        // Fetch division names
        $divisionNames = array_values($preferredDivisionsOrder);
        $divisions = $preferredDivisionsOrder1;

        echo "<table>";
        echo "<tr><th>Particulars</th>";
        foreach ($divisionNames as $name) {
            echo "<th style='text-align: center;'>$name</th>";
        }
        echo "<th style='text-align: center;'>Total</th>";
        echo "</tr>";

        // Output data for the first set of custom columns
        foreach ($customColumnsFirst as $column => $heading) {
            echo "<tr>";
            echo "<td>$heading</td>";

            // Initialize total for each row
            $total = 0;

            // Output data for each division
            foreach ($divisions as $division) {
                // Initialize total for each division
                $divisionTotal = 0;

                // Output data for each depot under the division
                $result->data_seek(0); // Reset result pointer
                while ($row = $result->fetch_assoc()) {
                    if ($row['division'] === $division) {
                        // Calculate the total for the current division and custom column
                        $divisionTotal += $row[$column];
                    }
                }

                // Output the total for the current division and custom column
                echo "<td style='" . ($column === 'ORTotal' || $column === 'available' || $column === 'ES' ? 'font-weight:bold;text-align:right;' : 'text-align:right;') . "'>$divisionTotal</td>";

                // Add division total to the overall total
                $total += $divisionTotal;
            }

            // Output the total for the custom column across all divisions
            echo "<td style='font-weight:bold;text-align:right;'>$total</td>";

            echo "</tr>";
        }

        // Output data for the second set of custom columns (spare percentage)
        foreach ($customColumnsSecond as $column => $heading) {
            echo "<tr>";
            echo "<td>$heading</td>";

            // Initialize total for each row
            $total = 0;

            // Output data for each division
            foreach ($divisions as $division) {
                // Get the number of schedules and number of spare vehicles for the division
                $schedules = 0;
                $spare = 0;
                $ORRWY = 0;

                $result->data_seek(0); // Reset result pointer
                while ($row = $result->fetch_assoc()) {
                    if ($row['division'] === $division) {
                        $schedules += $row['schedules'];
                        $spare += $row['spare'];
                        $ORRWY += $row['ORRWY'];
                    }
                }

                // Calculate the "Number of Spare Percentage"
                $sparePercentage = ($schedules != 0) ? round((($spare - $ORRWY) * 100) / $schedules, 2) : 0;

                // Output the "Number of Spare Percentage"
                echo "<td style='text-align:right;'>$sparePercentage%</td>";

                // Add the spare percentage to the total only if it's not the total spare percentage column
                if ($column !== 'sparepercentage') {
                    $total += $sparePercentage;
                }
            }

            // Output the total spare percentage for all divisions
            if ($column === 'sparepercentage') {
                $totalSpare = 0;
                $totalSchedules = 0;
                $totalORRWY = 0;

                // Calculate the total spare vehicles and schedules for all divisions
                foreach ($divisions as $division) {
                    // Get the number of schedules and number of spare vehicles for the division
                    $schedules = 0;
                    $spare = 0;
                    $ORRWY = 0;

                    $result->data_seek(0); // Reset result pointer
                    while ($row = $result->fetch_assoc()) {
                        if ($row['division'] === $division) {
                            $schedules += $row['schedules'];
                            $spare += $row['spare'];
                            $ORRWY += $row['ORRWY'];
                        }
                    }

                    // Add division's spare and schedules to the total
                    $totalSpare += $spare;
                    $totalSchedules += $schedules;
                    $totalORRWY += $ORRWY;
                }

                // Calculate the total spare percentage
                $totalSparePercentage = ($totalSchedules != 0) ? round((($totalSpare - $totalORRWY) * 100) / $totalSchedules, 2) : 0;

                // Output the total spare percentage for all divisions
                echo "<td style='font-weight:bold;text-align:right;'>$totalSparePercentage%</td>";
            } else {
                echo "<td style='font-weight:bold;text-align:right;'>$total</td>";
            }


            echo "</tr>";
        }

        // Output data for the third set of custom columns
        foreach ($customColumnsThird as $column => $heading) {
            echo "<tr>";
            echo "<td>$heading</td>";

            // Initialize total for each row
            $total = 0;

            // Output data for each division
            foreach ($divisions as $division) {
                // Initialize total for each division
                $divisionTotal = 0;

                // Output data for each depot under the division
                $result->data_seek(0); // Reset result pointer
                while ($row = $result->fetch_assoc()) {
                    if ($row['division'] === $division) {
                        // Calculate the total for the current division and custom column
                        $divisionTotal += $row[$column];
                    }
                }

                // Output the total for the current division and custom column
                echo "<td style='" . ($column === 'ORTotal' || $column === 'available' || $column === 'ES' ? 'font-weight:bold;text-align:right;' : 'text-align:right;') . "'>$divisionTotal</td>";

                // Add division total to the overall total
                $total += $divisionTotal;
            }

            // Output the total for the custom column across all divisions
            echo "<td style='font-weight:bold;text-align:right;'>$total</td>";

            echo "</tr>";
        }

        echo "</table>";

    }
    $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($selectedDate)));

    // Query to fetch yesterday's data of all divisions and their cumulative total
    $query = "SELECT 
    location.division,
    SUM(CASE WHEN date = '$yesterday' THEN total_km ELSE 0 END) AS total_total_km,
    SUM(CASE WHEN date = '$yesterday' THEN hsd ELSE 0 END) AS total_hsd,
    ROUND(SUM(CASE WHEN date = '$yesterday' THEN total_km ELSE 0 END) / SUM(CASE WHEN date = '$yesterday' THEN hsd ELSE 0 END), 2) AS total_kmpl,
    SUM(CASE WHEN date = '$yesterday' THEN total_km ELSE 0 END) AS daily_total_km,
    SUM(CASE WHEN date = '$yesterday' THEN hsd ELSE 0 END) AS daily_total_hsd,
    ROUND(SUM(CASE WHEN date = '$yesterday' THEN total_km ELSE 0 END) / SUM(CASE WHEN date = '$yesterday' THEN hsd ELSE 0 END), 2) AS daily_total_kmpl,
    SUM(CASE WHEN date BETWEEN DATE_FORMAT('$yesterday', '%Y-%m-01') AND '$yesterday' THEN total_km ELSE 0 END) AS cumulative_total_km,
    SUM(CASE WHEN date BETWEEN DATE_FORMAT('$yesterday', '%Y-%m-01') AND '$yesterday' THEN hsd ELSE 0 END) AS cumulative_total_hsd,
    ROUND(SUM(CASE WHEN date BETWEEN DATE_FORMAT('$yesterday', '%Y-%m-01') AND '$yesterday' THEN total_km ELSE 0 END) / SUM(CASE WHEN date BETWEEN DATE_FORMAT('$yesterday', '%Y-%m-01') AND '$yesterday' THEN hsd ELSE 0 END), 2) AS cumulative_total_kmpl
FROM 
    kmpl_data
INNER JOIN (
    SELECT DISTINCT division_id, division FROM location
) AS location ON kmpl_data.division = location.division_id
WHERE 
    date BETWEEN DATE_FORMAT('$yesterday', '%Y-%m-01') AND '$yesterday'
GROUP BY 
    location.division
ORDER BY location.division_id;";

    $result = $db->query($query);


    // Check if there are any rows returned
    if ($result->num_rows > 0) {
        ?><br>
        <h2 style="text-align:center;">DIVISION WISE KMPL AS ON <?php echo date('d-m-Y', strtotime($yesterday)); ?></h2>
        <table border="1">
            <thead>
                <tr>
                    <th rowspan="2" style="text-align:center;">Division</th>
                    <th colspan="3" style="text-align:center;">Daily KMPL</th>
                    <th colspan="3" style="text-align:center;">Cumulative KMPL</th>
                </tr>
                <tr>
                    <th>Total KM</th>
                    <th>HSD</th>
                    <th>KMPL</th>
                    <th>Total KM</th>
                    <th>HSD</th>
                    <th>KMPL</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalDailyKm = 0;
                $totalDailyHsd = 0;
                $totalCumulativeKm = 0;
                $totalCumulativeHsd = 0;

                // Output data for each division
                while ($row = $result->fetch_assoc()) {
                    // Accumulate total daily and cumulative KM and HSD
                    $totalDailyKm += $row['daily_total_km'];
                    $totalDailyHsd += $row['daily_total_hsd'];
                    $totalCumulativeKm += $row['cumulative_total_km'];
                    $totalCumulativeHsd += $row['cumulative_total_hsd'];
                    ?>
                    <tr>
                        <td><?php echo $row['division']; ?></td>
                        <td><?php echo $row['daily_total_km']; ?></td>
                        <td><?php echo $row['daily_total_hsd']; ?></td>
                        <td>
                            <?php
                            // Check if HSD is not zero to avoid division by zero error
                            if ($row['daily_total_hsd'] != 0) {
                                echo number_format($row['daily_total_km'] / $row['daily_total_hsd'], 2);
                            } else {
                                echo "0"; // or any other message indicating division by zero
                            }
                            ?>
                        </td>
                        <td><?php echo $row['cumulative_total_km']; ?></td>
                        <td><?php echo $row['cumulative_total_hsd']; ?></td>
                        <td>
                            <?php
                            // Check if HSD is not zero to avoid division by zero error
                            if ($row['cumulative_total_hsd'] != 0) {
                                echo number_format($row['cumulative_total_km'] / $row['cumulative_total_hsd'], 2);
                            } else {
                                echo "0"; // or any other message indicating division by zero
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }

                // Calculate total daily and cumulative KMPL for the corporation
                if ($totalDailyHsd != 0) {
                    $corporateDailyKmpl = number_format($totalDailyKm / $totalDailyHsd, 2);
                } else {
                    $corporateDailyKmpl = "0"; // or any other message indicating division by zero
                }

                if ($totalCumulativeHsd != 0) {
                    $corporateCumulativeKmpl = number_format($totalCumulativeKm / $totalCumulativeHsd, 2);
                } else {
                    $corporateCumulativeKmpl = "0"; // or any other message indicating division by zero
                }
                ?>
                <tr>
                    <td><strong>Corporation</strong></td>
                    <td><strong><?php echo $totalDailyKm; ?></strong></td>
                    <td><strong><?php echo $totalDailyHsd; ?></strong></td>
                    <td><strong><?php echo $corporateDailyKmpl; ?></strong></td>
                    <td><strong><?php echo $totalCumulativeKm; ?></strong></td>
                    <td><strong><?php echo $totalCumulativeHsd; ?></strong></td>
                    <td><strong><?php echo $corporateCumulativeKmpl; ?></strong></td>
                </tr>
            </tbody>

        </table>


        <?php

    } else {?><br><br><br><br><?php
        echo "<h3>Please select the date.</h3>";
    }


    // Fetch data from the BUS_REGISTRATION table
    $query = "SELECT * FROM BUS_REGISTRATION";
    $result = $db->query($query);

    // Define the order of divisions
    $divisionOrder = array(
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9'
    );
    $divisionOrder1 = array(
        "1" => "KLB-1",
        "2" => "KLB-2",
        "3" => "YDR",
        "4" => "BDR",
        "5" => "RCH",
        "6" => "KPL",
        "7" => "BLR",
        "8" => "HSP",
        "9" => "VJP"
    );

    // Initialize an array to store data organized by division and subcategory
    $organizedData = array();
    $particulars = array();
    $totalDivision = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $division = $row['division_name'];
            $subCategory = $row['bus_sub_category'];

            // Rearrange the division names based on preference
            if ($division == 'KALABURAGI') {
                $division = 'KALABURAGI-1';
            } elseif ($division == 'KALABURGI') {
                $division = 'KALABURAGI-2';
            }

            // If the division doesn't exist in the organized data array, initialize it
            if (!isset($organizedData[$division])) {
                $organizedData[$division] = array();
            }

            // If the subcategory doesn't exist in the division array, initialize it
            if (!isset($organizedData[$division][$subCategory])) {
                $organizedData[$division][$subCategory] = 0;
            }

            // Increment the count for the particular subcategory in the division
            $organizedData[$division][$subCategory]++;

            // Add subcategory to particulars array if not already present
            if (!in_array($subCategory, $particulars)) {
                $particulars[] = $subCategory;
            }

            // Increment the count for the particular subcategory in the totalDivision array
            if (!isset($totalDivision[$subCategory])) {
                $totalDivision[$subCategory] = 0;
            }
            $totalDivision[$subCategory]++;
        }
    }

    // Add a 'Total' column to the particulars array
    $particulars[] = 'Total';

    // Close the database connection
    ?><br>
    <h2 style="text-align:center;">TYPES OF VEHICLES AS ON <?php echo date('d-m-Y'); ?></h2>
    <table>
        <thead>
            <tr>
                <th>Particulars</th>
                <?php
                // Output table headings based on division names
                foreach ($divisionOrder1 as $division) {
                    echo "<th>$division</th>";
                }
                ?>
                <th><strong>Total</strong></th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Output table rows based on subcategory counts
            foreach ($particulars as $particular) {
                if ($particular != 'Total') {
                    echo "<tr>";
                    echo "<td>$particular</td>";
                    $total = 0;
                    foreach ($divisionOrder as $division) {
                        $count = isset($organizedData[$division][$particular]) ? $organizedData[$division][$particular] : 0;
                        $total += $count;
                        echo "<td>$count</td>";
                    }
                    echo "<td><strong>$total</strong></td>";
                    echo "</tr>";
                }
            }

            // Output row for Corporation in bold
            echo "<tr><td><strong>Corporation</strong></td>";
            $corporationTotal = 0;
            foreach ($divisionOrder as $division) {
                $divisionTotal = 0;
                foreach ($particulars as $particular) {
                    $divisionTotal += isset($organizedData[$division][$particular]) ? $organizedData[$division][$particular] : 0;
                }
                $corporationTotal += $divisionTotal;
                echo "<td><strong>$divisionTotal</strong></td>";
            }
            echo "<td><strong>$corporationTotal</strong></td></tr>";
            ?>
        </tbody>
    </table>
    <?php
    // Preferred order of divisions
    $preferredDivisions = array(
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9'
    );
    $preferredDivisions1 = array(
        "1" => "KLB-1",
        "2" => "KLB-2",
        "3" => "YDR",
        "4" => "BDR",
        "5" => "RCH",
        "6" => "KPL",
        "7" => "BLR",
        "8" => "HSP",
        "9" => "VJP"
    );

    // Fetch data from the BUS_REGISTRATION table
    $query = "SELECT * FROM BUS_REGISTRATION";
    $result = $db->query($query);

    // Initialize an array to store data organized by make, emission norms, and divisions
    $organizedData = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $division = $row['division_name'];
            $make = $row['make'];
            $emissionNorms = $row['emission_norms'];

            // If the make doesn't exist in the organized data array, initialize it
            if (!isset($organizedData[$make])) {
                $organizedData[$make] = array();
            }

            // If the emission norms don't exist in the make array, initialize it
            if (!isset($organizedData[$make][$emissionNorms])) {
                $organizedData[$make][$emissionNorms] = array_fill_keys($preferredDivisions, 0);
            }

            // Increment the count for the particular make and emission norms in the division
            $organizedData[$make][$emissionNorms][$division]++;
        }
    }

    ?><br>
    <!-- Display the data in a table -->
    <h2 style="text-align:center;">MAKE AND EMISSION NORMS WISE VEHICLES AS ON <?php echo date('d-m-Y'); ?></h2>
    <table border="1">
        <thead>
            <tr>
                <th colspan="2" style="text-align:center;">Particulars</th>
                <?php
                // Output table headings based on division names
                foreach ($preferredDivisions1 as $division) {
                    echo "<th>$division</th>";
                }
                ?>
                <th><strong>Total</strong></th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Output table rows based on make, emission norms, and division counts
            foreach ($organizedData as $make => $makeData) {
                // Sort the emission norms array in ascending order
                ksort($makeData);
                foreach ($makeData as $emissionNorms => $divisionCounts) {
                    echo "<tr>";
                    echo "<td>$make</td>";
                    echo "<td>$emissionNorms</td>";
                    $total = 0;
                    foreach ($divisionCounts as $division => $count) {
                        $total += $count;
                        echo "<td>$count</td>";
                    }
                    echo "<td><strong>$total</strong></td>";
                    echo "</tr>";
                }
                // Output dynamic row to calculate total for each make
                echo "<tr>";
                echo "<td colspan='2'><strong>$make Total</strong></td>";
                foreach ($preferredDivisions as $division) {
                    $divisionTotal = array_sum(array_column($organizedData[$make], $division)); // Calculate division total for the make
                    echo "<td><strong>$divisionTotal</strong></td>";
                }
                $makeTotal = array_sum(array_map('array_sum', $makeData)); // Calculate total for the make
                echo "<td><strong>$makeTotal</strong></td>"; // Output the total for the Total column
                echo "</tr>";
            }

            // Output the total row for each division
            echo "<tr>";
            echo "<td colspan='2'><strong>Corporation</strong></td>";
            $totalTotal = 0; // Initialize total for the Total column
            foreach ($preferredDivisions as $division) {
                $divisionTotal = 0;
                foreach ($organizedData as $makeData) {
                    foreach ($makeData as $divisionCounts) {
                        $divisionTotal += $divisionCounts[$division];
                    }
                }
                echo "<td><strong>$divisionTotal</strong></td>";
                $totalTotal += $divisionTotal; // Add division total to totalTotal
            }
            echo "<td><strong>$totalTotal</strong></td>"; // Output the total for the Total column
            echo "</tr>";
            ?>
        </tbody>
    </table>
    <?php
    // Close the database connection
    $db->close();
    ?>
    <br>
    <br><br>
    <h4 style="display: inline-block; width: 24%; text-align:center;">JTO</h4>
    <h4 style="display: inline-block; width: 24%; text-align:center;">DME</h4>
    <h4 style="display: inline-block; width: 24%;text-align:center;">DY CME</h4>
    <h4 style="display: inline-block; width: 24%; text-align:right; padding-right:200px">CME</h4>

</div>

<!-- Print button -->
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <!-- Download Excel button -->
    <button class="btn btn-success" id="downloadExcel">Download Excel</button>
    <!-- Download Text button -->
    <button class="btn btn-danger" id="downloadText">Download Text</button>
    <a href="main_dvp_divisionwise_pdf.php?selected_date=<?php echo $_POST['selected_date']; ?>"
        class="btn btn-success">Download PDF</a>

</div>
<!-- Include xlsx.full.min.js library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
<?php
// Assuming $selectedDate is already defined somewhere
$formattedDate = date('d/m/Y', strtotime($selectedDate));
?>
<script>
    document.getElementById('downloadExcel').addEventListener('click', function () {

        // Get container1 HTML content
        var htmlContent = document.querySelector('.container1').outerHTML;

        // Convert HTML to workbook
        var workbook = XLSX.utils.table_to_book(document.querySelector('.container1'));

        // Save workbook as Excel file with today's date and "DVP" appended to the file name
        XLSX.writeFile(workbook, '<?php echo $formattedDate; ?>_DVP.xlsx');
    });

    document.getElementById('downloadText').addEventListener('click', function () {

        // Get container1 text content
        var textContent = document.querySelector('.container1').innerText;

        // Create a Blob with the text content
        var blob = new Blob([textContent], { type: 'text/plain' });

        // Create a link element to trigger the download
        var link = document.createElement('a');
        link.download = '<?php echo $formattedDate; ?>_DVP.txt'; // Set the file name
        link.href = window.URL.createObjectURL(blob);

        // Append the link to the body and trigger the download
        document.body.appendChild(link);
        link.click();

        // Cleanup
        document.body.removeChild(link);
    });
</script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>
