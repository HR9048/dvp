<?php
include '../includes/connection.php';
error_reporting(0);
ini_set('display_errors', 0);

if (isset($_POST['date'])) {
    $selectedDate = $_POST['date'];
    $year = date('Y', strtotime($selectedDate));
    $month = date('m', strtotime($selectedDate));

    // Fetch all divisions and depots from the location table
    $sqlLocation = "SELECT division_id, depot_id, division, depot FROM location";
    $resultLocation = $db->query($sqlLocation);
    $locationData = [];
    while ($row = $resultLocation->fetch_assoc()) {
        $locationData[$row['division_id']][$row['depot_id']] = [
            'division' => $row['division'],
            'depot' => $row['depot']
        ];
    }

    // Daily Data (Only for selected date)
    $sqlDaily = "SELECT 
                    division_id,
                    depot_id, 
                    SUM(km_operated) AS total_gross_km, 
                    SUM(hsd) AS total_hsd, 
                    (SUM(km_operated) / NULLIF(SUM(hsd), 0)) AS kmpl
                 FROM vehicle_kmpl
                 WHERE date = ?
                 GROUP BY division_id, depot_id";

    $stmtDaily = $db->prepare($sqlDaily);
    $stmtDaily->bind_param("s", $selectedDate);
    $stmtDaily->execute();
    $resultDaily = $stmtDaily->get_result();

    // Cumulative Data (From 1st of the month to selected date)
    $sqlCumulative = "SELECT 
                        division_id,
                        depot_id, 
                        SUM(km_operated) AS total_gross_km, 
                        SUM(hsd) AS total_hsd, 
                        (SUM(km_operated) / NULLIF(SUM(hsd), 0)) AS kmpl
                     FROM vehicle_kmpl
                     WHERE date BETWEEN ? AND ?
                     GROUP BY division_id, depot_id";

    $startDate = "$year-$month-01";
    $stmtCumulative = $db->prepare($sqlCumulative);
    $stmtCumulative->bind_param("ss", $startDate, $selectedDate);
    $stmtCumulative->execute();
    $resultCumulative = $stmtCumulative->get_result();
    $formattedDate = date('d/m/Y', strtotime($selectedDate));
    // Generate Report Table
    $tableHtml = '<h2 class="text-center">Kalyana Karnataka Road Transport Corporation (KKRTC)</h2><br>';
    $tableHtml .= '<h2 style="display: inline-block; width: 30%; text-align:left;">Central Office</h2>
            <h2 style="display: inline-block; width: 30%; text-align:center;">KALABURAGI</h2>
            <h2 style="display: inline-block; width: 30%; text-align:right;">' . $formattedDate . '</h2>';
                
    $tableHtml .= '<table class="table table-bordered">
        <thead>
            <tr>
                <th rowspan="2">Sl No</th> <!-- Serial Number -->
                <th rowspan="2">Division</th>
                <th rowspan="2">Depot</th>
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
        <tbody>';

    $divisionTotals = []; // To accumulate division totals
    $corporationTotals = [ // To accumulate corporation totals
        'gross_km_daily' => 0, 'hsd_daily' => 0, 'kmpl_daily' => 0,
        'gross_km_cumulative' => 0, 'hsd_cumulative' => 0, 'kmpl_cumulative' => 0
    ];
    $previousDivision = null;
    $serialNumber = 1; // Initialize serial number

    while ($rowDaily = $resultDaily->fetch_assoc()) {
        $division_id = $rowDaily['division_id'];
        $depot_id = $rowDaily['depot_id'];
        $division_name = $locationData[$division_id][$depot_id]['division'] ?? 'N/A';
        $depot_name = $locationData[$division_id][$depot_id]['depot'] ?? 'N/A';

        $gross_km_daily = $rowDaily['total_gross_km'];
        $hsd_daily = $rowDaily['total_hsd'];
        $kmpl_daily = number_format($rowDaily['kmpl'], 2);

        // Find cumulative data for the same depot
        $cumulativeData = null;
        $resultCumulative->data_seek(0); // Reset result set pointer
        while ($rowCumulative = $resultCumulative->fetch_assoc()) {
            if ($rowCumulative['division_id'] == $division_id && $rowCumulative['depot_id'] == $depot_id) {
                $cumulativeData = $rowCumulative;
                break;
            }
        }

        $gross_km_cumulative = $cumulativeData['total_gross_km'] ?? 0;
        $hsd_cumulative = $cumulativeData['total_hsd'] ?? 0;
        $kmpl_cumulative = isset($cumulativeData['kmpl']) ? number_format($cumulativeData['kmpl'], 2) : '0.00';

        // Add to division total
        if ($previousDivision != $division_id && $previousDivision !== null) {
            // Show division total for the previous division
            $kmpl_daily = number_format($divisionTotals[$previousDivision]['gross_km_daily'] / max($divisionTotals[$previousDivision]['hsd_daily'], 1), 2);
            $kmpl_cumulative = number_format($divisionTotals[$previousDivision]['gross_km_cumulative'] / max($divisionTotals[$previousDivision]['hsd_cumulative'], 1), 2);
            $tableHtml .= "<tr style='font-weight:bold; background:#f0f0f0;'>
                <td colspan='3'>{$locationData[$previousDivision][array_key_first($locationData[$previousDivision])]['division']} - TOTAL</td>
                <td>{$divisionTotals[$previousDivision]['gross_km_daily']}</td>
                <td>{$divisionTotals[$previousDivision]['hsd_daily']}</td>
                <td>$kmpl_daily</td>
                <td>{$divisionTotals[$previousDivision]['gross_km_cumulative']}</td>
                <td>{$divisionTotals[$previousDivision]['hsd_cumulative']}</td>
                <td>$kmpl_cumulative</td>
            </tr>";
            // Reset division total accumulator
            $divisionTotals[$previousDivision] = [
                'gross_km_daily' => 0, 'hsd_daily' => 0, 'kmpl_daily' => 0,
                'gross_km_cumulative' => 0, 'hsd_cumulative' => 0, 'kmpl_cumulative' => 0
            ];
        }

        // Add data for current division and depot
        $divisionTotals[$division_id]['gross_km_daily'] += $gross_km_daily;
        $divisionTotals[$division_id]['hsd_daily'] += $hsd_daily;
        $divisionTotals[$division_id]['gross_km_cumulative'] += $gross_km_cumulative;
        $divisionTotals[$division_id]['hsd_cumulative'] += $hsd_cumulative;

        // Accumulate corporation totals
        $corporationTotals['gross_km_daily'] += $gross_km_daily;
        $corporationTotals['hsd_daily'] += $hsd_daily;
        $corporationTotals['gross_km_cumulative'] += $gross_km_cumulative;
        $corporationTotals['hsd_cumulative'] += $hsd_cumulative;

        // Print data row for depot
        $tableHtml .= "<tr>
            <td>$serialNumber</td> <!-- Serial Number -->
            <td>$division_name</td>
            <td>$depot_name</td>
            <td>$gross_km_daily</td>
            <td>$hsd_daily</td>
            <td>$kmpl_daily</td>
            <td>$gross_km_cumulative</td>
            <td>$hsd_cumulative</td>
            <td>$kmpl_cumulative</td>
        </tr>";

        $previousDivision = $division_id;
        $serialNumber++; // Increment serial number
    }

    // After loop ends, print the last division's total
    if ($previousDivision !== null) {
        $kmpl_daily = number_format($divisionTotals[$previousDivision]['gross_km_daily'] / max($divisionTotals[$previousDivision]['hsd_daily'], 1), 2);
        $kmpl_cumulative = number_format($divisionTotals[$previousDivision]['gross_km_cumulative'] / max($divisionTotals[$previousDivision]['hsd_cumulative'], 1), 2);
        $tableHtml .= "<tr style='font-weight:bold; background:#f0f0f0;'>
            <td colspan='3'>{$locationData[$previousDivision][array_key_first($locationData[$previousDivision])]['division']} - TOTAL</td>
            <td>{$divisionTotals[$previousDivision]['gross_km_daily']}</td>
            <td>{$divisionTotals[$previousDivision]['hsd_daily']}</td>
            <td>$kmpl_daily</td>
            <td>{$divisionTotals[$previousDivision]['gross_km_cumulative']}</td>
            <td>{$divisionTotals[$previousDivision]['hsd_cumulative']}</td>
            <td>$kmpl_cumulative</td>
        </tr>";
    }

    // Add Corporation Total row
    $kmpl_daily = number_format($corporationTotals['gross_km_daily'] / max($corporationTotals['hsd_daily'], 1), 2);
    $kmpl_cumulative = number_format($corporationTotals['gross_km_cumulative'] / max($corporationTotals['hsd_cumulative'], 1), 2);
    $tableHtml .= "<tr style='font-weight:bold; background:#e0e0e0;'>
        <td colspan='3'>Corporation Total</td>
        <td>{$corporationTotals['gross_km_daily']}</td>
        <td>{$corporationTotals['hsd_daily']}</td>
        <td>$kmpl_daily</td>
        <td>{$corporationTotals['gross_km_cumulative']}</td>
        <td>{$corporationTotals['hsd_cumulative']}</td>
        <td>$kmpl_cumulative</td>
    </tr>";

    $tableHtml .= '</tbody></table>';

    echo $tableHtml;
}
?>
