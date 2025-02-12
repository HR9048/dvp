<?php
include '../includes/connection.php';
error_reporting(0);
ini_set('display_errors', 0);
include '../pages/session.php';

if (isset($_POST['date'])) {
    $selectedDate = $_POST['date'];
    $year = date('Y', strtotime($selectedDate));
    $month = date('m', strtotime($selectedDate));
    $division_id = $_SESSION['DIVISION_ID'];
    $DIVISION_NAME = $_SESSION['DIVISION'];
    // Fetch all depots for the given division from the location table
    $sqlLocation = "SELECT depot_id, division, depot FROM location WHERE division_id = ?";
    $stmtLocation = $db->prepare($sqlLocation);
    $stmtLocation->bind_param("i", $division_id);
    $stmtLocation->execute();
    $resultLocation = $stmtLocation->get_result();
    $locationData = [];
    while ($row = $resultLocation->fetch_assoc()) {
        $locationData[$row['depot_id']] = [
            'division' => $row['division'],
            'depot' => $row['depot']
        ];
    }

    // Daily Data (Only for selected date)
    $sqlDaily = "SELECT 
                    depot_id, 
                    SUM(km_operated) AS total_gross_km, 
                    SUM(hsd) AS total_hsd, 
                    (SUM(km_operated) / NULLIF(SUM(hsd), 0)) AS kmpl
                 FROM vehicle_kmpl
                 WHERE date = ? AND division_id = ?
                 GROUP BY depot_id";

    $stmtDaily = $db->prepare($sqlDaily);
    $stmtDaily->bind_param("si", $selectedDate, $division_id);
    $stmtDaily->execute();
    $resultDaily = $stmtDaily->get_result();

    // Cumulative Data (From 1st of the month to selected date)
    $sqlCumulative = "SELECT 
                        depot_id, 
                        SUM(km_operated) AS total_gross_km, 
                        SUM(hsd) AS total_hsd, 
                        (SUM(km_operated) / NULLIF(SUM(hsd), 0)) AS kmpl
                     FROM vehicle_kmpl
                     WHERE date BETWEEN ? AND ? AND division_id = ?
                     GROUP BY depot_id";

    $startDate = "$year-$month-01";
    $stmtCumulative = $db->prepare($sqlCumulative);
    $stmtCumulative->bind_param("ssi", $startDate, $selectedDate, $division_id);
    $stmtCumulative->execute();
    $resultCumulative = $stmtCumulative->get_result();
    $formattedDate = date('d/m/Y', strtotime($selectedDate));
// Generate Report Table
$tableHtml = '<h2 class="text-center">Kalyana Karnataka Road Transport Corporation (KKRTC)</h2><br>';
$tableHtml .= '<h2 style="display: inline-block; width: 30%; text-align:left;">' . $DIVISION_NAME . '</h2>
        <h2 style="display: inline-block; width: 30%; text-align:center;">DIVISION</h2>
        <h2 style="display: inline-block; width: 30%; text-align:right;">' . $formattedDate . '</h2>';

$tableHtml .= '<table class="table table-bordered">
    <thead>
        <tr>
            <th rowspan="2">Sl No</th>
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

$corporationTotals = [
    'gross_km_daily' => 0, 'hsd_daily' => 0,
    'gross_km_cumulative' => 0, 'hsd_cumulative' => 0
];
$serialNumber = 1;

while ($rowDaily = $resultDaily->fetch_assoc()) {
    $depot_id = $rowDaily['depot_id'];
    $depot_name = $locationData[$depot_id]['depot'] ?? 'N/A';

    $gross_km_daily = $rowDaily['total_gross_km'];
    $hsd_daily = $rowDaily['total_hsd'];
    $kmpl_daily = number_format($rowDaily['kmpl'], 2);

    // Find cumulative data for the same depot
    $cumulativeData = null;
    $resultCumulative->data_seek(0);
    while ($rowCumulative = $resultCumulative->fetch_assoc()) {
        if ($rowCumulative['depot_id'] == $depot_id) {
            $cumulativeData = $rowCumulative;
            break;
        }
    }

    $gross_km_cumulative = $cumulativeData['total_gross_km'] ?? 0;
    $hsd_cumulative = $cumulativeData['total_hsd'] ?? 0;
    $kmpl_cumulative = isset($cumulativeData['kmpl']) ? number_format($cumulativeData['kmpl'], 2) : '0.00';

    // Accumulate corporation totals
    $corporationTotals['gross_km_daily'] += $gross_km_daily;
    $corporationTotals['hsd_daily'] += $hsd_daily;
    $corporationTotals['gross_km_cumulative'] += $gross_km_cumulative;
    $corporationTotals['hsd_cumulative'] += $hsd_cumulative;

    // Print data row for depot
    $tableHtml .= "<tr>
        <td>$serialNumber</td>
        <td>$depot_name</td>
        <td>$gross_km_daily</td>
        <td>$hsd_daily</td>
        <td>$kmpl_daily</td>
        <td>$gross_km_cumulative</td>
        <td>$hsd_cumulative</td>
        <td>$kmpl_cumulative</td>
    </tr>";

    $serialNumber++;
}

// Add Corporation Total row
$kmpl_daily = number_format($corporationTotals['gross_km_daily'] / max($corporationTotals['hsd_daily'], 1), 2);
$kmpl_cumulative = number_format($corporationTotals['gross_km_cumulative'] / max($corporationTotals['hsd_cumulative'], 1), 2);
$tableHtml .= "<tr style='font-weight:bold; background:#e0e0e0;'>
    <td colspan='2'>Division Total</td>
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