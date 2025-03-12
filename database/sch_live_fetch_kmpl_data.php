<?php
include '../includes/connection.php'; // Include database connection

$receivedDate = $_POST['date'];

$selectedDate = date('Y-m-d', strtotime($receivedDate . ' -1 day'));
$formateddate = date('d-m-Y', strtotime($selectedDate));
$firstDateOfMonth = date("Y-m-01", strtotime($selectedDate));

// Static Target KMPL values for depots
$targetKmplValues = [
    1 => 5.05,
    2 => 5.09,
    3 => 5.16,
    4 => 5.38,
    5 => 5.28,
    6 => 5.51,
    7 => 5.45,
    8 => 5.45,
    9 => 5.33,
    10 => 5.25,
    11 => 5.38,
    12 => 5.19,
    13 => 5.23,
    14 => 5.23,
    15 => 5.26,
    16 => 5.15,
    17 => 5.25,
    18 => 5.25,
    19 => 5.17,
    20 => 5.22,
    21 => 5.17,
    22 => 5.01,
    23 => 5.13,
    24 => 5.18,
    25 => 5.10,
    26 => 5.07,
    27 => 5.32,
    28 => 5.25,
    29 => 5.29,
    30 => 5.00,
    31 => 5.11,
    32 => 5.51,
    33 => 5.15,
    34 => 5.35,
    35 => 4.97,
    36 => 5.29,
    37 => 5.25,
    38 => 5.26,
    39 => 5.20,
    40 => 5.24,
    41 => 5.31,
    42 => 5.39,
    43 => 5.23,
    44 => 5.44,
    45 => 5.25,
    46 => 5.33,
    47 => 5.26,
    48 => 5.50,
    49 => 5.22,
    50 => 5.29,
    51 => 5.28,
    52 => 5.26,
    53 => 5.42
];
$targetDivisionKmplValues = [
    "KLB1" => 5.20,
    "KLB2" => 5.38,
    "YDG" => 5.22,
    "BDR" => 5.20,
    "RCH" => 5.17,
    "BLR" => 5.11,
    "KPL" => 5.25,
    "HSP" => 5.32,
    "VJP" => 5.30
];
$query = "SELECT 
    l.kmpl_division AS division_name, 
    l.depot AS depot_name,
    l.depot_id,
    l.division_id,
    -- Daily Data
    SUM(CASE WHEN k.date = '$selectedDate' THEN k.total_km ELSE 0 END) AS daily_total_km,
    SUM(CASE WHEN k.date = '$selectedDate' THEN k.hsd ELSE 0 END) AS daily_hsd,

    -- Cumulative Data
    SUM(CASE WHEN k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate' THEN k.total_km ELSE 0 END) AS total_total_km,
    SUM(CASE WHEN k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate' THEN k.hsd ELSE 0 END) AS total_hsd

FROM kmpl_data k
JOIN location l ON k.depot = l.depot_id
WHERE k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate'
GROUP BY l.division, l.depot, l.depot_id
ORDER BY l.division_id, l.depot_id";

$result = mysqli_query($db, $query);

$data = [];
$division_totals = [];
$corporation_total = [
    "daily_total_km" => 0,
    "daily_hsd" => 0,
    "total_total_km" => 0,
    "total_hsd" => 0
];

while ($row = mysqli_fetch_assoc($result)) {
    $division_name = $row['division_name'];
    $depot_id = $row['depot_id'];

    // Calculate KMPL (Avoid Division by Zero)
    $row['daily_kmpl'] = ($row['daily_hsd'] > 0) ? ($row['daily_total_km'] / $row['daily_hsd']) : 0;
    $row['total_kmpl'] = ($row['total_hsd'] > 0) ? ($row['total_total_km'] / $row['total_hsd']) : 0;

    // Get Target KMPL
    $row['target_kmpl'] = isset($targetKmplValues[$depot_id]) ? $targetKmplValues[$depot_id] : 'NA';
    $row['targetdivision_kmpl'] = isset($targetDivisionKmplValues[$division_name]) ? $targetDivisionKmplValues[$division_name] : 'NA';

    // Calculate Difference (Target KMPL - Cumulative KMPL)
    $row['diff_kmpl'] = ($row['target_kmpl'] !== 'NA') ? ($row['total_kmpl'] - $row['target_kmpl']) : 'NA';

    // Store data by division
    $data[$division_name][] = $row;

    // Calculate division-wise totals
    if (!isset($division_totals[$division_name])) {
        $division_totals[$division_name] = [
            "daily_total_km" => 0,
            "daily_hsd" => 0,
            "total_total_km" => 0,
            "total_hsd" => 0
        ];
    }

    foreach (["daily_total_km", "daily_hsd", "total_total_km", "total_hsd"] as $key) {
        $division_totals[$division_name][$key] += $row[$key];
        $corporation_total[$key] += $row[$key];
    }
}
?>
<style>
    @media (max-width: 768px) {

        th,
        td {
            font-size: 7px !important;
        }
    }

    /* Highlight Division & Overall Total Rows */
    .division-total {
        font-weight: bold;
        background-color: #dff0d8;
    }

    .overall-total {
        font-weight: bold;
        background-color: #f7c6c7;
    }
</style>

<?php
echo '<h2 style="text-align:center;">Depot-wise KMPL data Date: ' . $formateddate . '</h2>';
echo "<table class='table table-bordered'>";
echo "<thead>
    <tr>
        <th rowspan='2' style='width: 5%;'>Sl. No.</th>
        <th rowspan='2' style='width: 12%;'>Depot</th>
        <th colspan='3' style='width: 32%;'>Daily KMPL</th>
        <th colspan='3' style='width: 37%;'>Cumulative KMPL</th>
        <th rowspan='2' style='width: 7%;'>Target KMPL</th>
        <th rowspan='2' style='width: 7%;'>Diff</th>
    </tr>
    <tr>
        <th style='width: 14%;'>Total KM</th>
        <th style='width: 11%;'>HSD</th>
        <th style='width: 7%;'>KMPL</th>
        <th style='width: 17%;'>Total KM</th>
        <th style='width: 13%;'>HSD</th>
        <th style='width: 7%;'>KMPL</th>
    </tr>
</thead>

      <tbody>";

$serialNo = 1;

foreach ($data as $division => $depots) {
    // Division Totals Calculation
    $divDailyKMPL = ($division_totals[$division]['daily_hsd'] > 0) ? ($division_totals[$division]['daily_total_km'] / $division_totals[$division]['daily_hsd']) : 0;
    $divTotalKMPL = ($division_totals[$division]['total_hsd'] > 0) ? ($division_totals[$division]['total_total_km'] / $division_totals[$division]['total_hsd']) : 0;

    $targetDivisionKmpl = isset($targetDivisionKmplValues[$division]) ? $targetDivisionKmplValues[$division] : 'NA';
    $divDIFFTotalKMPL = ($targetDivisionKmpl !== 'NA') ? (number_format($divTotalKMPL, 2) - $targetDivisionKmpl ) : 'NA';


    foreach ($depots as $row) {
        echo "<tr>
                <td>{$serialNo}</td>
                <td onclick='fetchvehiclekmplDetails(\"{$row['depot_id']}\", \"Depot\" , \"{$selectedDate}\")'>{$row['depot_name']}</td>
                <td>{$row['daily_total_km']}</td>
                <td>{$row['daily_hsd']}</td>
                <td>" . number_format($row['daily_kmpl'], 2) . "</td>
                <td>{$row['total_total_km']}</td>
                <td>{$row['total_hsd']}</td>
                <td>" . number_format($row['total_kmpl'], 2) . "</td>
                <td>{$row['target_kmpl']}</td>
                <td>" . (is_numeric($row['diff_kmpl']) ? number_format($row['diff_kmpl'], 2) : 'NA') . "</td>
              </tr>";

        $serialNo++;
    }
    // Display Division Total Row
    echo "<tr class='division-total'>
            <td colspan='2' onclick='fetchvehiclekmplDetails(\"{$row['division_id']}\", \"Division\" , \"{$selectedDate}\")'>$division Total</td>
            <td>{$division_totals[$division]['daily_total_km']}</td>
            <td>{$division_totals[$division]['daily_hsd']}</td>
            <td>" . number_format($divDailyKMPL, 2) . "</td>
            <td>{$division_totals[$division]['total_total_km']}</td>
            <td>{$division_totals[$division]['total_hsd']}</td>
            <td>" . number_format($divTotalKMPL, 2) . "</td>
            <td>{$targetDivisionKmpl}</td>
            <td>" . number_format($divDIFFTotalKMPL, 2) . "</td>
          </tr>";
}

// Corporation Total Row
$corpKmplCumulative = ($corporation_total['total_hsd'] > 0) ? ($corporation_total['total_total_km'] / $corporation_total['total_hsd']) : 0;
$corpKmpl = ($corporation_total['daily_total_km'] > 0) ? ($corporation_total['daily_total_km'] / $corporation_total['daily_hsd']) : 0;

echo "<tr class='overall-total'>
        <td colspan='2'>Corporation Total</td>
        <td>{$corporation_total['daily_total_km']}</td>
        <td>{$corporation_total['daily_hsd']}</td>
        <td>" . number_format($corpKmpl, 2) . "</td>
        <td>{$corporation_total['total_total_km']}</td>
        <td>{$corporation_total['total_hsd']}</td>
        <td>" . number_format($corpKmplCumulative, 2) . "</td>
        <td>-</td>
        <td>-</td>
      </tr>";

echo "</tbody></table>";

mysqli_close($db);
