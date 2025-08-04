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

            .container1,
            .container1 * {
                visibility: visible;
            }

            .container1 {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            /* Print styles for the table, th and td for font size and set padding 1 px only */
            table {
                font-size: 18px;
            }

            th,
            td {
                padding: 2px;
                font-size: 18px;
            }
        }
    </style>



    <form action="" method="POST" class="form-inline">
        <label for="selected_date" class="mr-2">Select Date:</label>
        <input type="date" id="selected_date" name="selected_date" max="<?php echo date('Y-m-d'); ?>"
            class="form-control mr-2">
        <button class="btn btn-primary" type="submit">Show Data</button>
    </form><br>
    <?php $formatted_date = date('d/m/Y', strtotime($_POST['selected_date'])); ?>


    <?php
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve selected date
        $selectedDate = $_POST['selected_date'];
    }


    // Fetch data from the database based on session variables and selected date
    $sql = "SELECT d.*, l.depot AS depotName
            FROM dvp_data d
            INNER JOIN location l ON d.depot = l.depot_id
            WHERE d.division = '{$_SESSION['DIVISION_ID']}' AND d.date = '$selectedDate' order by l.depot_id";

    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        // Output data in multiple columns
        echo '<div class="container1"><h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br>
        <div style="display: flex; justify-content: space-between;">
            <h2 style="text-align:left; padding: 2%; margin: 0;">DIVISION: ' . $_SESSION['DIVISION'] . '</h2>
            <h2 style="text-align:center; padding: 2%; margin: 0;">DVP</h2>
            <h2 style="text-align:right; padding: 2%; margin: 0;">
                ' . $formatted_date . '
            </h2>
        </div>';
        echo "<table>";

        // Calculate total schedules and total spare vehicles
        $totalSchedules = 0;
        $totalSpare = 0;

        // Initialize array to store depot names
        $depotNames = array();

        // Fetch depot names and store them in an array
        while ($row = $result->fetch_assoc()) {
            $depotName = $row['depotName'];
            $depotNames[$depotName] = $depotName;
            // Update total schedules and total spare vehicles
            $totalSchedules += $row['schedules'];
            $totalSpare += $row['spare'];
            $totalORRWY += $row['ORRWY'];
        }

        // Output table headers
        echo "<tr><th style='text-align: left;'>Particulars</th>";
        foreach ($depotNames as $depotName) {
            echo "<th>$depotName</th>";
        }
        echo "<th>Total</th>"; // Add Total column header
        echo "</tr>";

        // Define custom column headings
        $customHeadings = array(
            'schedules' => 'Number of Schedules',
            'vehicles' => 'Number Of Vehicles(Excluding RWY)',
            'spare' => 'Number of Spare Vehicles(Excluding RWY)',
            'ORRWY' => 'Vehicles Off Road at RWY',
            'spareP' => 'Percentage of Spare Vehicles(Excluding RWY)',
            'docking' => 'Vehicles stopped for Docking',
            'ORDepot' => 'Vehicles Off Road at Depot',
            'ORDWS' => 'Vehicles Off Road at DWS',
            'CC' => 'Vehicles Withdrawn for CC',
            'wup1' => 'Vehicles Work Under Progress at Depot',
            'loan' => 'Vehicles loan given to other Depot',
            'wup' => 'Vehicles Withdrawn for Fair',
            'Police' => 'Vehicles at Police Station',
            'notdepot' => 'Vehicles Not Arrived to Depot',
            'Dealer' => 'Vehicles Held at Dealer Point',
            'ORTotal' => '<span style="font-weight:bold;">Total Vehicles not Available for Operation</span>',
            'available' => '<span style="font-weight:bold;">Total Vehicles available for Operation</span>',
            'ES' => '<span style="font-weight:bold;">Vehicles Excess/Shortage</span>',
            // Add more custom headings as needed
        );

        // Output data of each row
        foreach ($customHeadings as $column => $heading) {
            echo "<tr>";
            echo "<td>$heading</td>";

            // Initialize total for each row
            $total = 0;

            // Output data for each depot and calculate total
            $result->data_seek(0); // Reset result pointer
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $key => $value) {
                    if ($key === $column) {
                        // Check if the column is 'ORTotal' or 'available' and apply inline style for bold
                        $cellStyle = ($column === 'ORTotal' || $column === 'available' || $column === 'ES') ? 'font-weight:bold;text-align:right;' : 'text-align:right;';
                        echo "<td style='$cellStyle'>$value</td>";
                        $total += $value; // Add value to total
                    }
                }
            }

            // If column is 'spareP', calculate and output the percentage with two decimal places
            if ($column === 'spareP') {
                $percentage = ($totalSchedules > 0) ? number_format((($totalSpare - $totalORRWY) * 100 / $totalSchedules), 2) : 0;
                echo "<td style='font-weight:bold;text-align:right;'>$percentage%</td>";
            } else {
                // Output total for the row
                echo "<td style='font-weight:bold;text-align:right;'>$total</td>";
            }

            echo "</tr>";
        }

        echo "</table>";

        // Display the kmpl details from kmpl_data table of all depots under the division
        // kmpl date should be -1 day of selected date
        $kmpldate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
        $formatedkmpldate = date('d/m/Y', strtotime($kmpldate));
        $kmplSql = "SELECT k.*, l.depot AS depot_name FROM kmpl_data k LEFT JOIN location l ON k.depot = l.depot_id WHERE k.division = '{$_SESSION['DIVISION_ID']}' AND k.date = '$kmpldate' order by l.depot_id";
        $kmplResult = $db->query($kmplSql);
        if ($kmplResult && $kmplResult->num_rows > 0) {

            // Aggregate rows depot-wise (in case there are multiple entries per depot)
            $byDepot = [];
            while ($r = $kmplResult->fetch_assoc()) {
                // Try common column names for depot, km and diesel
                $depot  = $r['depot_name'] ?? $r['depot_id'] ?? $r['depot_code'] ?? 'N/A';
                $km     = (float)($r['total_km'] ?? $r['km'] ?? 0);
                $diesel = (float)($r['hsd'] ?? $r['diesel'] ?? 0);

                if (!isset($byDepot[$depot])) {
                    $byDepot[$depot] = ['km' => 0.0, 'diesel' => 0.0];
                }
                $byDepot[$depot]['km']     += $km;
                $byDepot[$depot]['hsd'] += $diesel;
            }

            // Sort by depot name/code for a stable display (optional)

            // Build table
            $kmplHtml  = '<br><br><table style="width:100%; border-collapse:collapse; border:2px solid black; margin-top:20px;">';
            $kmplHtml .= '<tr><th colspan="4" style="text-align:center; border:1px solid black;"><b>KMPL Details As on ' . htmlspecialchars($formatedkmpldate) . '</b></th></tr>';
            $kmplHtml .= '<tr>'
                . '<th style="border:1px solid black; padding:6px; text-align:left;">Depot</th>'
                . '<th style="border:1px solid black; padding:6px; text-align:right;">Total KM</th>'
                . '<th style="border:1px solid black; padding:6px; text-align:right;">Total Diesel</th>'
                . '<th style="border:1px solid black; padding:6px; text-align:right;">KMPL</th>'
                . '</tr>';

            $grandKm = 0.0;
            $grandDiesel = 0.0;

            foreach ($byDepot as $depot => $vals) {
                $km = $vals['km'];
                $diesel = $vals['hsd'];
                $kmpl = ($diesel > 0) ? ($km / $diesel) : 0;

                $grandKm += $km;
                $grandDiesel += $diesel;

                $kmplHtml .= '<tr>'
                    . '<td style="border:1px solid black; padding:6px;">' . htmlspecialchars((string)$depot) . '</td>'
                    . '<td style="border:1px solid black; padding:6px; text-align:right;">' . $km . '</td>'
                    . '<td style="border:1px solid black; padding:6px; text-align:right;">' . $diesel . '</td>'
                    . '<td style="border:1px solid black; padding:6px; text-align:right;">' . number_format($kmpl, 2) . '</td>'
                    . '</tr>';
            }

            // Grand total row
            $grandKmpl = ($grandDiesel > 0) ? ($grandKm / $grandDiesel) : 0;
            $kmplHtml .= '<tr>'
                . '<th style="border:1px solid black; padding:6px; text-align:right;">Total</th>'
                . '<th style="border:1px solid black; padding:6px; text-align:right;">' . $grandKm . '</th>'
                . '<th style="border:1px solid black; padding:6px; text-align:right;">' . $grandDiesel . '</th>'
                . '<th style="border:1px solid black; padding:6px; text-align:right;">' . number_format($grandKmpl, 2) . '</th>'
                . '</tr>';

            $kmplHtml .= '</table>';

            echo $kmplHtml;
        } else {
            echo '<br><br><p style="text-align:center;">No KMPL data available for the selected date.</p>';
        }


        echo '<br><br><br><br><br>
        <div style="display: flex; justify-content: space-between;">
            <h2 style="text-align:left; padding: 2%; margin: 0;">JA</h2>
            <h2 style="text-align:center; padding: 2%; margin: 0;">DWS</h2>
            <h2 style="text-align:right; padding: 2%; margin: 0;">DME</h2>
        </div>
        
        </div>

    <!-- Print button -->
    <div class="text-center mt-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>';
    }

    $db->close();
    ?>


<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>