<?php
// Include TCPDF library and database connection
require_once('../includes/tcpdf/tcpdf.php');
require_once('../includes/connection.php');
include 'session.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO' || $_SESSION['JOB_TITLE'] == 'CO_STORE') {
    // Check if selected_date is set
    if (isset($_GET['selected_date'])) {
        // Initialize selected date and format it
        $selectedDate = $_GET['selected_date'];
        $formatted_date = date('d/m/Y', strtotime($selectedDate));

        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Name');
        $pdf->SetTitle('DVP Report');
        $pdf->SetSubject('DVP Report');
        $pdf->SetKeywords('DVP, Report, KKRTC');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 8);

        // Retrieve data from the database based on session variables and selected date
        $sql = "SELECT * FROM dvp_data WHERE date = '$selectedDate' ORDER BY division";
        $result = $db->query($sql);

        if ($result->num_rows > 0) {
            // Output data in multiple columns

            // Fetch division names and order them by division_id
            $sqlDivisions = "SELECT distinct(division_id), kmpl_division as division FROM location ORDER BY division_id";
            $resultDivisions = $db->query($sqlDivisions);

            if ($resultDivisions->num_rows > 0) {
                // Fetch and store division names and IDs in arrays
                $divisionNames = array();
                while ($row = $resultDivisions->fetch_assoc()) {
                    $divisionNames[$row['division_id']] = $row['division'];
                }

                // Define custom column headings
                $customColumnsFirst = array(
                    'schedules' => 'Number of Schedules',
                    'vehicles' => 'Number Of Vehicles (Including RWY)',
                    'spare' => 'Number of Spare Vehicles (Including RWY)',
                );

                $customColumnsSecond = array(
                    'sparepercentage' => 'Percentage of Spare Vehicles (Excluding RWY)',
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

                // Initialize preferred order of division names based on division_id
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

                // Generate ordered array of division names based on preferred order
                $divisionNamesOrdered = array();
                foreach ($preferredDivisionsOrder as $divisionId => $divisionName) {
                    if (isset($divisionNames[$divisionId])) {
                        $divisionNamesOrdered[$divisionId] = $divisionNames[$divisionId];
                    }
                }

                // Start building HTML for PDF content
                $html = '<div class="container1">';
                $html .= '<h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br><br><br>';
                $html .= '<table style="width: 100%; margin-top: 50px;">';
                $html .= '<tr>';
                $html .= '<td style="text-align: left;"><b>CENTRAL OFFICE</b></td>';
                $html .= '<td style="text-align: center;"><b>KALABUGAGI</b></td>';
                $html .= '<td style="text-align: right;"><b>' . date('d/m/Y', strtotime($selectedDate)) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table><br><br>';

                // Start table
                $html .= '<table border="1" cellpadding="4">';
                $html .= '<tr><th style="width: 180px;"><b>Particulars</b></th>';

                // Output headers based on ordered division names
                foreach ($divisionNamesOrdered as $divisionId => $divisionName) {
                    $html .= '<th style="text-align: right; width:34px"><b>' . $divisionName . '</b></th>';
                }
                $html .= '<th style="text-align: center;"><b>Total</b></th>';
                $html .= '</tr>';

                // Function to calculate total for each column
                function calculateTotal($result, $column, $divisions)
                {
                    $total = 0;
                    foreach ($divisions as $division) {
                        $divisionTotal = 0;
                        $result->data_seek(0); // Reset result pointer
                        while ($row = $result->fetch_assoc()) {
                            if ($row['division'] == $division) {
                                $divisionTotal += $row[$column];
                            }
                        }
                        $total += $divisionTotal;
                    }
                    return $total;
                }

                // Calculate totals for spare percentage globally
                $totalSchedules = calculateTotal($result, 'schedules', array_keys($divisionNamesOrdered));
                $totalSpare = calculateTotal($result, 'spare', array_keys($divisionNamesOrdered));
                $totalORRWY = calculateTotal($result, 'ORRWY', array_keys($divisionNamesOrdered));

                // Compute spare percentage globally
                $sparePercentage = ($totalSchedules != 0) ? round((($totalSpare - $totalORRWY) * 100) / $totalSchedules, 2) : 0;

                // Output data for the first set of custom columns
                foreach ($customColumnsFirst as $column => $heading) {
                    $html .= '<tr>';
                    $html .= '<td>' . $heading . '</td>';

                    // Initialize total for each row
                    $total = 0;

                    // Output data for each division
                    foreach ($divisionNamesOrdered as $divisionId => $divisionName) {
                        $divisionTotal = calculateTotal($result, $column, [$divisionId]);
                        $html .= '<td style="text-align:right;">' . $divisionTotal . '</td>';
                        $total += $divisionTotal;
                    }

                    // Output the total for the custom column across all divisions
                    $html .= '<td style="font-weight:bold;text-align:right;">' . $total . '</td>';
                    $html .= '</tr>';
                }

                // Output data for the second set of custom columns (spare percentage)
                $html .= '<tr>';
                $html .= '<td>' . $customColumnsSecond['sparepercentage'] . '</td>';

                // Output spare percentage for each division
                foreach ($divisionNamesOrdered as $divisionId => $divisionName) {
                    $schedules = calculateTotal($result, 'schedules', [$divisionId]);
                    $spare = calculateTotal($result, 'spare', [$divisionId]);
                    $ORRWY = calculateTotal($result, 'ORRWY', [$divisionId]);

                    // Calculate the "Number of Spare Percentage" for each division
                    $sparePercentageDivision = ($schedules != 0) ? round((($spare - $ORRWY) * 100) / $schedules, 2) : 0;

                    // Output the "Number of Spare Percentage" for each division
                    $html .= '<td style="text-align:right;">' . $sparePercentageDivision . '%</td>';
                }

                // Calculate and output the total spare percentage for all divisions
                $html .= '<td style="font-weight:bold;text-align:right;">' . $sparePercentage . '%</td>';
                $html .= '</tr>';

                // Output data for the third set of custom columns
                foreach ($customColumnsThird as $column => $heading) {
                    $html .= '<tr>';
                    $html .= '<td>' . $heading . '</td>';

                    // Initialize total for each row
                    $total = 0;

                    // Output data for each division
                    foreach ($divisionNamesOrdered as $divisionId => $divisionName) {
                        $divisionTotal = calculateTotal($result, $column, [$divisionId]);
                        $html .= '<td style="' . ($column === 'ORTotal' || $column === 'available' || $column === 'ES' ? 'font-weight:bold;text-align:right;' : 'text-align:right;') . '">' . $divisionTotal . '</td>';
                        $total += $divisionTotal;
                    }

                    // Output the total for the custom column across all divisions
                    $html .= '<td style="font-weight:bold;text-align:right;">' . $total . '</td>';
                    $html .= '</tr>';
                }

                // Close table and container
                $html .= '</table>';
                $html .= '</div>';

                // Output HTML content to PDF
                $pdf->writeHTML($html, true, false, true, false, '');

            } else {
                // Handle case where no divisions found
                $pdf->writeHTML('<p>No divisions found.</p>', true, false, true, false, '');
            }

        } else {
            // Handle case where no data found
            $pdf->writeHTML('<p>No data available for the selected date.</p>', true, false, true, false, '');
        }
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($selectedDate)));

        // Query to fetch yesterday's data of all divisions and their cumulative total
        $query = "SELECT 
    location.kmpl_division,
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
    SELECT DISTINCT division_id, division, kmpl_division FROM location
) AS location ON kmpl_data.division = location.division_id
WHERE 
    date BETWEEN DATE_FORMAT('$yesterday', '%Y-%m-01') AND '$yesterday'
GROUP BY 
    location.kmpl_division
ORDER BY location.division_id;";

        $result = $db->query($query);

        $html = "<h2 style=\"text-align:center;\">DIVISION WISE KMPL AS ON " . date('d-m-Y', strtotime($yesterday)) . "</h2>";
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead>
            <tr>
                <th rowspan="2" style="text-align:center;"><b>Division</b></th>
                <th colspan="3" style="text-align:center;"><b>Daily KMPL</b></th>
                <th colspan="3" style="text-align:center;"><b>Cumulative KMPL</b></th>
            </tr>
            <tr>
                <th><b>Total KM</b></th>
                <th><b>HSD</b></th>
                <th><b>KMPL</b></th>
                <th><b>Total KM</b></th>
                <th><b>HSD</b></th>
                <th><b>KMPL</b></th>
            </tr>
          </thead>';
        $html .= '<tbody>';

        if ($result->num_rows > 0) {
            $totalDailyKm = 0;
            $totalDailyHsd = 0;
            $totalCumulativeKm = 0;
            $totalCumulativeHsd = 0;

            while ($row = $result->fetch_assoc()) {
                $totalDailyKm += $row['daily_total_km'];
                $totalDailyHsd += $row['daily_total_hsd'];
                $totalCumulativeKm += $row['cumulative_total_km'];
                $totalCumulativeHsd += $row['cumulative_total_hsd'];

                $html .= '<tr>
                    <td>' . $row['kmpl_division'] . '</td>
                    <td>' . $row['daily_total_km'] . '</td>
                    <td>' . $row['daily_total_hsd'] . '</td>
                    <td>' . ($row['daily_total_hsd'] != 0 ? number_format($row['daily_total_km'] / $row['daily_total_hsd'], 2) : "0") . '</td>
                    <td>' . $row['cumulative_total_km'] . '</td>
                    <td>' . $row['cumulative_total_hsd'] . '</td>
                    <td>' . ($row['cumulative_total_hsd'] != 0 ? number_format($row['cumulative_total_km'] / $row['cumulative_total_hsd'], 2) : "0") . '</td>
                  </tr>';
            }

            $corporateDailyKmpl = ($totalDailyHsd != 0) ? number_format($totalDailyKm / $totalDailyHsd, 2) : "0";
            $corporateCumulativeKmpl = ($totalCumulativeHsd != 0) ? number_format($totalCumulativeKm / $totalCumulativeHsd, 2) : "0";

            $html .= '<tr>
                <td><strong>Corporation</strong></td>
                <td><strong>' . $totalDailyKm . '</strong></td>
                <td><strong>' . $totalDailyHsd . '</strong></td>
                <td><strong>' . $corporateDailyKmpl . '</strong></td>
                <td><strong>' . $totalCumulativeKm . '</strong></td>
                <td><strong>' . $totalCumulativeHsd . '</strong></td>
                <td><strong>' . $corporateCumulativeKmpl . '</strong></td>
              </tr>';
        } else {
            $html .= '<tr><td colspan="7" style="text-align:center;">No data available for yesterday.</td></tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Retrieve data for the second table
        $query2 = "SELECT * FROM BUS_REGISTRATION";
        $result2 = $db->query($query2);

        // Check if result2 has rows
        if ($result2 && $result2->num_rows > 0) {
            // Start building the HTML content
            $html = "<h2 style=\"text-align:center;\">TYPES OF VEHICLES AS ON " . date('d-m-Y') . "</h2>";
            $html .= "<table border=\"1\" cellpadding=\"4\" cellspacing=\"0\" style=\"border-collapse: collapse; width: 100%; margin-top: 10px;\">";
            $html .= "<thead><tr><th style=\"width: 170px;  border: 1px solid black; padding: 5px;\"><b>Particulars</b></th>";

            // Define the order of divisions for the second table
            $divisionOrder = array('1' => 'KLB1', '2' => 'KLB2', '3' => 'YDG', '4' => 'BDR', '5' => 'RCH', '6' => 'KPL', '7' => 'BLR', '8' => 'HSP', '9' => 'VJP');
            foreach ($divisionOrder as $division) {
                $html .= "<th style=\"width: 35px;  border: 1px solid black;\"><b>$division</b></th>";
            }
            $html .= "<th style=\"width: 50px;  border: 1px solid black;\"><b>Total</b></th></tr></thead><tbody>";

            // Initialize arrays for the second table
            $organizedData = array();
            $particulars = array();
            $totalDivision = array();

            // Fetch data and prepare HTML content for the second table
            while ($row = $result2->fetch_assoc()) {
                $division = $row['division_name'];
                $subCategory = $row['bus_sub_category'];

                // Adjust division name to match the provided names
                switch ($division) {
                    case '1':
                        $division = 'KLB1';
                        break;
                    case '2':
                        $division = 'KLB2';
                        break;
                    case '3':
                        $division = 'YDG';
                        break;
                    case '4':
                        $division = 'BDR';
                        break;
                    case '5':
                        $division = 'RCH';
                        break;
                    case '6':
                        $division = 'KPL';
                        break;
                    case '7':
                        $division = 'BLR';
                        break;
                    case '8':
                        $division = 'HSP';
                        break;
                    case '9':
                        $division = 'VJP';
                        break;
                    default:
                        // Handle unexpected division names
                        break;
                }

                if (!isset($organizedData[$division])) {
                    $organizedData[$division] = array();
                }
                if (!isset($organizedData[$division][$subCategory])) {
                    $organizedData[$division][$subCategory] = 0;
                }
                $organizedData[$division][$subCategory]++;
                if (!in_array($subCategory, $particulars)) {
                    $particulars[] = $subCategory;
                }
                if (!isset($totalDivision[$subCategory])) {
                    $totalDivision[$subCategory] = 0;
                }
                $totalDivision[$subCategory]++;
            }
            $particulars[] = 'Total';

            foreach ($particulars as $particular) {
                if ($particular != 'Total') {
                    $html .= "<tr style=\"width:100px;\"><td style=\"width:170px;\">$particular</td>";
                    $total = 0;
                    foreach ($divisionOrder as $division) {
                        $count = isset($organizedData[$division][$particular]) ? $organizedData[$division][$particular] : 0;
                        $total += $count;
                        $html .= "<td style=\"width: 35px; border: 1px solid black;\">$count</td>";
                    }
                    $html .= "<td style=\"width: 50px; border: 1px solid black;\"><b>$total</b></td></tr>";
                }
            }
            $html .= "<tr><td style=\"min-width: 170px; border: 1px solid black;\"><strong>Corporation</strong></td>";
            $corporationTotal = 0;
            foreach ($divisionOrder as $division) {
                $divisionTotal = 0;
                foreach ($particulars as $particular) {
                    $divisionTotal += isset($organizedData[$division][$particular]) ? $organizedData[$division][$particular] : 0;
                }
                $corporationTotal += $divisionTotal;
                $html .= "<td style=\"width: 35px; border: 1px solid black;\"><b>$divisionTotal</b></td>";
            }
            $html .= "<td style=\"width: 50px; border: 1px solid black;\"><b>$corporationTotal</b></td></tr></tbody></table>";

            // Output the HTML content
            $pdf->writeHTML($html, true, false, true, false, '');
        }

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

        // Start output buffering to capture PDF content
        ob_start();

        // Output the PDF content
        ?>
        <br>
        <!-- Display the data in a table -->
        <h2 style="text-align:center;">MAKE AND EMISSION NORMS WISE VEHICLES AS ON <?php echo date('d-m-Y'); ?></h2>
        <table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-top: 10px;">
            <thead>
                <tr style="text-align:center;">
                    <th colspan="2"><b>Particulars</b></th>
                    <?php
                    // Output table headings based on division names
                    foreach ($preferredDivisions1 as $division) {
                        echo "<th><b>$division</b></th>";
                    }
                    ?>
                    <th style="text-align:center;"><strong>Total</strong></th>
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
                        echo "<td style=\"text-align:center;\">$make</td>";
                        echo "<td style=\"text-align:center;\">$emissionNorms</td>";
                        $total = 0;
                        foreach ($divisionCounts as $division => $count) {
                            $total += $count;
                            echo "<td style=\"text-align:center;\">$count</td>";
                        }
                        echo "<td style=\"text-align:center;\"><strong>$total</strong></td>";
                        echo "</tr>";
                    }
                    // Output dynamic row to calculate total for each make
                    echo "<tr>";
                    echo '<td colspan="2" style="text-align:center;"><strong>' . htmlspecialchars($make) . ' Total</strong></td>';
                    foreach ($preferredDivisions as $division) {
                        $divisionTotal = array_sum(array_column($organizedData[$make], $division)); // Calculate division total for the make
                        echo "<td style=\"text-align:center;\"><strong>$divisionTotal</strong></td>";
                    }
                    $makeTotal = array_sum(array_map('array_sum', $makeData)); // Calculate total for the make
                    echo "<td style=\"text-align:center;\"><strong>$makeTotal</strong></td>"; // Output the total for the Total column
                    echo "</tr>";
                }

                // Output the total row for each division
                echo "<tr>";
                echo '<td colspan="2" style="text-align:center;"><strong>Corporation</strong></td>';
                $totalTotal = 0; // Initialize total for the Total column
                foreach ($preferredDivisions as $division) {
                    $divisionTotal = 0;
                    foreach ($organizedData as $makeData) {
                        foreach ($makeData as $divisionCounts) {
                            $divisionTotal += $divisionCounts[$division];
                        }
                    }
                    echo "<td style=\"text-align:center;\"><strong>$divisionTotal</strong></td>";
                    $totalTotal += $divisionTotal; // Add division total to totalTotal
                }
                echo "<td style=\"text-align:center;\"><strong>$totalTotal</strong></td>"; // Output the total for the Total column
                echo "</tr>";
                ?>
            </tbody>
        </table>
        <?php

        // Get current buffer contents and delete current output buffer
        $html = ob_get_clean();

        // Set PDF margins
        $pdf->SetMargins(10, 10, 10);

        // Set font
        $pdf->SetFont('helvetica', '', 10);


        // Add the final table at the end of the PDF
        $html .= '<br><br><br><br>';
        $html .= '<table style="width: 90%;">';
        $html .= '<tr>';
        $html .= '<td style="text-align: left;"><b>JTO</b></td>';
        $html .= '<td style="text-align: center;"><b>DME</b></td>';
        $html .= '<td style="text-align: center;"><b>Dy-CME</b></td>';
        $html .= '<td style="text-align: right;"><b>CME</b></td>';
        $html .= '</tr>';
        $html .= '</table>';

        // Output the final table to the PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Generate formatted date for file name
        $formattedFileName = date('d_m_Y', strtotime($selectedDate));
        $fileName = $formattedFileName . '_divisionwise_dvp.pdf';

        // Close and output PDF document
        $pdf->Output($fileName, 'D');
        exit;
    } else {
        // Redirect to login.php if accessed directly without POST data
        header("Location: dvp_print.php");
    }
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
?>