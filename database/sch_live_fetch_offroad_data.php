<?php
include("../includes/connection.php"); // Ensure database connection is included
date_default_timezone_set('Asia/Kolkata');
$today_date = date('Y-m-d');
$sql = "SELECT 
    l.division_id, 
    l.division AS `DIVISION`,
    d.depot_name,
    COALESCE(l.depot, CONCAT('Depot ', d.depot_name)) AS `DEPOT`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'Authorized Dealer' THEN o.bus_number END) AS `DEALER_COUNT`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'Police Station' THEN o.bus_number END) AS `POLICE_COUNT`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'DWS' THEN o.bus_number END) AS `DWS`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'Depot' AND o.parts_required != 'Work under Progress' THEN o.bus_number END) AS `DEPOT_COUNT`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'Depot' AND o.parts_required = 'Work under Progress' THEN o.bus_number END) AS `WUP`,
    COUNT(DISTINCT CASE WHEN o.off_road_location != 'RWY' and o.off_road_location != 'Police Station' THEN o.bus_number END) AS `G_TOTAL`,
    (SELECT COUNT(*) FROM bus_registration br WHERE br.depot_name = l.depot_id AND br.division_name = l.division_id) AS `TOTAL_BUS`
FROM 
    (SELECT depot_name FROM bus_registration GROUP BY depot_name) AS d
LEFT JOIN location l ON l.depot_id = d.depot_name
LEFT JOIN off_road_data o 
  ON o.id IN (
        SELECT MAX(id) 
        FROM off_road_data 
        WHERE status = 'off_road' 
        GROUP BY bus_number
     )
 AND o.depot = d.depot_name
LEFT JOIN dvp_data dp on dp.depot = l.depot_id and dp.date = '$today_date'
AND o.id IN (SELECT MAX(id) FROM off_road_data WHERE status = 'off_road' GROUP BY bus_number, depot)
GROUP BY l.division_id, d.depot_name, l.depot
ORDER BY l.division_id, l.depot_id";

$result = mysqli_query($db, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($db));
}

// Initialize totals
$division_totals = [];
$corporation_totals = [
    'DEPOT_COUNT' => 0,
    'WUP' => 0,
    'DWS' => 0,
    'DEALER_COUNT' => 0,
    'POLICE_COUNT' => 0,
    'G_TOTAL' => 0,
    'TOTAL_BUS' => 0
];

echo '<p style="text-align:center;"><b>Depot-wise Off-Road Summary</b></p>';
echo '<table border="1" cellspacing="0" cellpadding="5" style="width:100%; border-collapse: collapse;">
    <tr>
        <th rowspan="3">Sl. No</th>
        <th rowspan="3">Depot</th>
        <th rowspan="3">Buses</th>
        <th colspan="4">Off-Road</th>
        <th rowspan="3"><b>Total</b></th>
        <th rowspan="3">Off-Road %</th>
    </tr>
    <tr>
        <th colspan="2">@ Depot</th>
        <th rowspan="2">@ DWS</th>
        <th rowspan="2">@ Dealer</th>
    </tr>
    <tr>
        <th>Depot</th>
        <th>WUP</th>
    </tr>';

$serial_number = 0;
$prev_division = null;
while ($row = mysqli_fetch_assoc($result)) {
    $division = $row['DIVISION'];
    if (!isset($division_totals[$division])) {
        $division_totals[$division] = [
            'DEPOT_COUNT' => 0,
            'WUP' => 0,
            'DWS' => 0,
            'DEALER_COUNT' => 0,
            'POLICE_COUNT' => 0,
            'G_TOTAL' => 0,
            'TOTAL_BUS' => 0
        ];
    }

    $off_road_percentage = $row['TOTAL_BUS'] > 0 ? round(($row['G_TOTAL'] * 100) / $row['TOTAL_BUS'], 1) : 0;

    foreach (['DEPOT_COUNT', 'WUP', 'DWS', 'DEALER_COUNT', 'POLICE_COUNT', 'G_TOTAL', 'TOTAL_BUS'] as $key) {
        $division_totals[$division][$key] += $row[$key];
        $corporation_totals[$key] += $row[$key];
    }

    if ($prev_division !== null && $prev_division !== $division) {
        $div_off_road_percentage = $division_totals[$prev_division]['TOTAL_BUS'] > 0 ?
            round(($division_totals[$prev_division]['G_TOTAL'] * 100) / $division_totals[$prev_division]['TOTAL_BUS'], 1) : 0;
        $div_total_off_road = $division_totals[$prev_division]['G_TOTAL'];
        echo "<tr style='background-color: #d4edda; font-weight: bold;'>
            <td colspan='2' onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"All\")'>{$prev_division} Total</td>
            <td>{$division_totals[$prev_division]['TOTAL_BUS']}</td>
            <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordepot\")'>{$division_totals[$prev_division]['DEPOT_COUNT']}</td>
            <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordepotwup\")'>{$division_totals[$prev_division]['WUP']}</td>
            <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordws\")'>{$division_totals[$prev_division]['DWS']}</td>
            <td>{$division_totals[$prev_division]['DEALER_COUNT']}</td>
            <td><b>{$div_total_off_road}</b></td>
            <td>{$div_off_road_percentage}%</td>
        </tr>";
    }
    $depot_total_off_road = $row['G_TOTAL'];
    $serial_number++;
    echo "<tr>
        <td>{$serial_number}</td>
        <td onclick='fetchoffroadDetails(\"{$row['division_id']}\", \"{$row['depot_name']}\", \"Depot\", \"All\")'>{$row['DEPOT']}</td>
        <td>{$row['TOTAL_BUS']}</td>
        <td>{$row['DEPOT_COUNT']}</td>
        <td>{$row['WUP']}</td>
        <td>{$row['DWS']}</td>
        <td>{$row['DEALER_COUNT']}</td>
        <td><b>{$depot_total_off_road}</b></td>
        <td>{$off_road_percentage}%</td>
    </tr>";

    $prev_division = $division;
}

if ($prev_division !== null) {
    $div_off_road_percentage = $division_totals[$prev_division]['TOTAL_BUS'] > 0 ?
        round(($division_totals[$prev_division]['G_TOTAL'] * 100) / $division_totals[$prev_division]['TOTAL_BUS'], 1) : 0;
    //the total offroad row for the last division should be WUP + G_TOTAL
    $div_total_off_road = $division_totals[$prev_division]['G_TOTAL'];

    echo "<tr style='background-color: #d4edda; font-weight: bold;'>
        <td colspan='2' onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"All\")'>{$prev_division} Total</td>
        <td>{$division_totals[$prev_division]['TOTAL_BUS']}</td>
        <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordepot\")'>{$division_totals[$prev_division]['DEPOT_COUNT']}</td>
        <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordepotwup\")'>{$division_totals[$prev_division]['WUP']}</td>
        <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordws\")'>{$division_totals[$prev_division]['DWS']}</td>
        <td>{$division_totals[$prev_division]['DEALER_COUNT']}</td>
        <td><b>{$div_total_off_road}</b></td>
        <td>{$div_off_road_percentage}%</td> 
    </tr>";
}

$corp_off_road_percentage = $corporation_totals['TOTAL_BUS'] > 0 ?
    round(($corporation_totals['G_TOTAL'] * 100) / $corporation_totals['TOTAL_BUS'], 1) : 0;
    $div_total_off_road_corpo = $corporation_totals['G_TOTAL'];

echo "<tr style='background-color: #f8d7da; font-weight: bold;'>
    <td colspan='2' onclick='fetchoffroadDetails(\"Corporation\", \"Corporation\", \"Corporation\", \"All\")'>Corporation Total</td>
    <td>{$corporation_totals['TOTAL_BUS']}</td>
    <td onclick='fetchoffroadDetails(\"Corporation\", \"Corporation\", \"Corporation\", \"ordepot\")'>{$corporation_totals['DEPOT_COUNT']}</td>
    <td onclick='fetchoffroadDetails(\"Corporation\", \"Corporation\", \"Corporation\", \"ordepotwup\")'>{$corporation_totals['WUP']}</td>
    <td onclick='fetchoffroadDetails(\"Corporation\", \"Corporation\", \"Corporation\", \"ordws\")'>{$corporation_totals['DWS']}</td>
    <td>{$corporation_totals['DEALER_COUNT']}</td>
    <td><b>{$div_total_off_road_corpo}</b></td>
    <td>{$corp_off_road_percentage}%</td>
</tr>";

echo '</table>';

$sql = "SELECT 
    l.division_id, 
    l.division AS `DIVISION`,
    d.depot_name,
    COALESCE(l.depot, CONCAT('Depot ', d.depot_name)) AS `DEPOT`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'Authorized Dealer' THEN o.bus_number END) AS `DEALER_COUNT`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'Police Station' THEN o.bus_number END) AS `POLICE_COUNT`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'DWS' THEN o.bus_number END) AS `DWS`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'Depot' AND o.parts_required != 'Work under Progress' THEN o.bus_number END) AS `DEPOT_COUNT`,
    COUNT(DISTINCT CASE WHEN o.off_road_location = 'Depot' AND o.parts_required = 'Work under Progress' THEN o.bus_number END) AS `WUP`,
    COUNT(DISTINCT CASE WHEN o.off_road_location != 'RWY' and o.off_road_location != 'Police Station' THEN o.bus_number END) AS `G_TOTAL`,
    (SELECT COUNT(*) FROM bus_registration br WHERE br.depot_name = l.depot_id AND br.division_name = l.division_id) AS `TOTAL_BUS`
FROM 
    (SELECT depot_name FROM bus_registration GROUP BY depot_name) AS d
LEFT JOIN location l ON l.depot_id = d.depot_name
LEFT JOIN off_road_data o 
  ON o.id IN (
        SELECT MAX(id) 
        FROM off_road_data 
        WHERE status = 'off_road' 
        GROUP BY bus_number
     )
 AND o.depot = d.depot_name
LEFT JOIN dvp_data dp on dp.depot = l.depot_id and dp.date = '$today_date'
AND o.id IN (SELECT MAX(id) FROM off_road_data WHERE status = 'off_road' GROUP BY bus_number, depot)
GROUP BY l.division_id, d.depot_name, l.depot
ORDER BY l.division_id, l.depot_id";

$result = mysqli_query($db, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($db));
}

// Initialize totals
$division_totals = [];
$corporation_totals = [
    'DEPOT_COUNT' => 0,
    'WUP' => 0,
    'DWS' => 0,
    'DEALER_COUNT' => 0,
    'POLICE_COUNT' => 0,
    'G_TOTAL' => 0,
    'TOTAL_BUS' => 0
];

echo '<br><p style="text-align:center;"><b>Division-wise Off-Road Summary</b></p>';
echo '<table border="1" cellspacing="0" cellpadding="5" style="width:100%; border-collapse: collapse;">
    <tr>
        <th rowspan="3">Division</th>
        <th rowspan="3">Buses</th>
        <th colspan="4">Off-Road</th>
        <th rowspan="3"><b>Total</b></th>
        <th rowspan="3">Off-Road %</th>
    </tr>
    <tr>
        <th colspan="2">@ Depot</th>
        <th rowspan="2">@ DWS</th>
        <th rowspan="2">@ Dealer</th>
    </tr>
    <tr>
        <th>Depot</th>
        <th>WUP</th>
    </tr>';

$serial_number = 0;
$prev_division = null;
while ($row = mysqli_fetch_assoc($result)) {
    $division = $row['DIVISION'];
    if (!isset($division_totals[$division])) {
        $division_totals[$division] = [
            'DEPOT_COUNT' => 0,
            'WUP' => 0,
            'DWS' => 0,
            'DEALER_COUNT' => 0,
            'POLICE_COUNT' => 0,
            'G_TOTAL' => 0,
            'TOTAL_BUS' => 0
        ];
    }

    $off_road_percentage = $row['TOTAL_BUS'] > 0 ? round(($row['G_TOTAL'] * 100) / $row['TOTAL_BUS'], 1) : 0;

    foreach (['DEPOT_COUNT', 'WUP', 'DWS', 'DEALER_COUNT', 'POLICE_COUNT', 'G_TOTAL', 'TOTAL_BUS'] as $key) {
        $division_totals[$division][$key] += $row[$key];
        $corporation_totals[$key] += $row[$key];
    }

    if ($prev_division !== null && $prev_division !== $division) {
        $div_off_road_percentage = $division_totals[$prev_division]['TOTAL_BUS'] > 0 ?
            round(($division_totals[$prev_division]['G_TOTAL'] * 100) / $division_totals[$prev_division]['TOTAL_BUS'], 1) : 0;
        $div_total_off_road = $division_totals[$prev_division]['G_TOTAL'];
        echo "<tr style='background-color: #d4edda; font-weight: bold;'>
            <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"All\")'>{$prev_division}</td>
            <td>{$division_totals[$prev_division]['TOTAL_BUS']}</td>
            <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordepot\")'>{$division_totals[$prev_division]['DEPOT_COUNT']}</td>
            <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordepotwup\")'>{$division_totals[$prev_division]['WUP']}</td>
            <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordws\")'>{$division_totals[$prev_division]['DWS']}</td>
            <td>{$division_totals[$prev_division]['DEALER_COUNT']}</td>
            <td><b>{$div_total_off_road}</b></td>
            <td>{$div_off_road_percentage}%</td>
        </tr>";
    }
    $depot_total_off_road = $row['G_TOTAL'];
    $serial_number++;
    
    $prev_division = $division;
}

if ($prev_division !== null) {
    $div_off_road_percentage = $division_totals[$prev_division]['TOTAL_BUS'] > 0 ?
        round(($division_totals[$prev_division]['G_TOTAL'] * 100) / $division_totals[$prev_division]['TOTAL_BUS'], 1) : 0;
    //the total offroad row for the last division should be WUP + G_TOTAL
    $div_total_off_road = $division_totals[$prev_division]['G_TOTAL'];

    echo "<tr style='background-color: #d4edda; font-weight: bold;'>
        <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"All\")'>{$prev_division}</td>
        <td>{$division_totals[$prev_division]['TOTAL_BUS']}</td>
        <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordepot\")'>{$division_totals[$prev_division]['DEPOT_COUNT']}</td>
        <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordepotwup\")'>{$division_totals[$prev_division]['WUP']}</td>
        <td onclick='fetchoffroadDetails(\"{$prev_division}\", \"{$prev_division}\", \"Division\", \"ordws\")'>{$division_totals[$prev_division]['DWS']}</td>
        <td>{$division_totals[$prev_division]['DEALER_COUNT']}</td>
        <td><b>{$div_total_off_road}</b></td>
        <td>{$div_off_road_percentage}%</td> 
    </tr>";
}

$corp_off_road_percentage = $corporation_totals['TOTAL_BUS'] > 0 ?
    round(($corporation_totals['G_TOTAL'] * 100) / $corporation_totals['TOTAL_BUS'], 1) : 0;
    $div_total_off_road_corpo = $corporation_totals['G_TOTAL'];

echo "<tr style='background-color: #f8d7da; font-weight: bold;'>
    <td onclick='fetchoffroadDetails(\"Corporation\", \"Corporation\", \"Corporation\", \"All\")'>Corporation Total</td>
    <td>{$corporation_totals['TOTAL_BUS']}</td>
    <td onclick='fetchoffroadDetails(\"Corporation\", \"Corporation\", \"Corporation\", \"ordepot\")'>{$corporation_totals['DEPOT_COUNT']}</td>
    <td onclick='fetchoffroadDetails(\"Corporation\", \"Corporation\", \"Corporation\", \"ordepotwup\")'>{$corporation_totals['WUP']}</td>
    <td onclick='fetchoffroadDetails(\"Corporation\", \"Corporation\", \"Corporation\", \"ordws\")'>{$corporation_totals['DWS']}</td>
    <td>{$corporation_totals['DEALER_COUNT']}</td>
    <td><b>{$div_total_off_road_corpo}</b></td>
    <td>{$corp_off_road_percentage}%</td>
</tr>";

echo '</table>';