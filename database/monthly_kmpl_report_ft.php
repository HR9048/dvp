<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/connection.php'; // Database connection

header('Content-Type: application/json');

// Get JSON request data
$data = json_decode(file_get_contents("php://input"), true);

$from = $data['from'] ?? null;
$to = $data['to'] ?? null;
$division = $data['division'] ?? null;
$depot = $data['depot'] ?? null;
$sch_no = $data['sch_no'] ?? null;
$bus_number = $data['bus_number'] ?? null;
$driver_token = $data['driver_token'] ?? null;

// Validate required fields
if (strtolower($sch_no) === 'all' || strtolower($bus_number) === 'all' || strtolower($driver_token) === 'all') {

    // Fetch Division & Depot Names
    $locationQuery = "SELECT division, depot FROM location WHERE division_id = '$division' AND depot_id = '$depot'";
    $locationResult = mysqli_query($db, $locationQuery);
    $locationData = mysqli_fetch_assoc($locationResult);
    $divisionName = $locationData['division'] ?? 'Unknown';
    $depotName = $locationData['depot'] ?? 'Unknown';


    if (!$from || !$to || !$division || !$depot) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }

    $fromFormatted = date("d-m-Y", strtotime($from));
    $toFormatted = date("d-m-Y", strtotime($to));

    $whereClauses = ["date BETWEEN '$from' AND '$to'", "division_id = '$division'", "depot_id = '$depot'"];
    $filterText = "";

    if (!empty($sch_no) && strtolower($sch_no) !== 'all') {
        $whereClauses[] = "route_no = '$sch_no'";
        $filterText = "Route No: $sch_no";
    }
    if (!empty($bus_number) && strtolower($bus_number) !== 'all') {
        $whereClauses[] = "bus_number = '$bus_number'";
        $filterText = "Bus Number: $bus_number";
    }
    if (!empty($driver_token) && strtolower($driver_token) !== 'all') {
        $whereClauses[] = "(driver_1_pf = '$driver_token' OR driver_2_pf = '$driver_token')";
        $filterText = "Driver PF: $driver_token";
    }

    $whereClause = implode(" AND ", $whereClauses);

    // Get Data
    $query = "SELECT * FROM vehicle_kmpl WHERE $whereClause and deleted !='1' ORDER BY date, route_no, bus_number";
    $result = mysqli_query($db, $query);

    if (!$result) {
        echo json_encode(['error' => 'DB Error: ' . mysqli_error($db)]);
        exit;
    }

    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Setup date headers
    $period = new DatePeriod(
        new DateTime($from),
        new DateInterval('P1D'),
        (new DateTime($to))->modify('+1 day')
    );
    $dateHeaders = [];
    foreach ($period as $date) {
        $dateHeaders[] = $date->format('Y-m-d');
    }

    // Determine grouping
    if (!empty($sch_no) && strtolower($sch_no) === 'all') {
        $groupKey = 'route_no';
        $groupLabel = 'Schedule No';
    } elseif (!empty($bus_number) && strtolower($bus_number) === 'all') {
        $groupKey = 'bus_number';
        $groupLabel = 'Bus Number';
    } elseif (!empty($driver_token) && strtolower($driver_token) === 'all') {
        $groupKey = 'driver_pf';
        $groupLabel = 'Driver PF';
    } else {
        echo json_encode(['error' => 'Invalid filter selection']);
        exit;
    }

    // Group data
    $groupedData = [];

    foreach ($data as $row) {
        $recordDate = $row['date'];
        $km = $row['km_operated'];
        $hsd = $row['hsd'];

        if ($groupKey === 'driver_pf') {
            $drivers = [];
            if (!empty($row['driver_1_pf'])) $drivers[] = $row['driver_1_pf'];
            if (!empty($row['driver_2_pf'])) $drivers[] = $row['driver_2_pf'];

            $numDrivers = count($drivers);
            if ($numDrivers == 0) continue;

            $perKm = $km / $numDrivers;
            $perHsd = $hsd / $numDrivers;

            foreach ($drivers as $driver) {
                $groupedData[$driver][$recordDate]['km'] = ($groupedData[$driver][$recordDate]['km'] ?? 0) + $perKm;
                $groupedData[$driver][$recordDate]['hsd'] = ($groupedData[$driver][$recordDate]['hsd'] ?? 0) + $perHsd;
            }
        } else {
            $key = $row[$groupKey];
            $groupedData[$key][$recordDate]['km'] = ($groupedData[$key][$recordDate]['km'] ?? 0) + $km;
            $groupedData[$key][$recordDate]['hsd'] = ($groupedData[$key][$recordDate]['hsd'] ?? 0) + $hsd;
        }
    }

    // Generate HTML table
    $isDriverPFView = !empty($driver_token) && strtolower($driver_token) === 'all';

    // Helper to fetch token & name by PF
    function getDriverDetails($pf)
    {
        $url = "http://localhost:8880/dvp_test/database/combined_api_data.php?pf_no=" . urlencode($pf);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        curl_close($ch);
        if ($resp) {
            $json = json_decode($resp, true);
            if (!empty($json['data'][0])) {
                return $json['data'][0];
            }
        }
        return null;
    }

    // Start table
    $html = "<div style='text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 10px;'>
            KMPL Report <br> 
            Division: $divisionName | Depot: $depotName <br>
            From Date: $fromFormatted To Date: $toFormatted <br>
            <span style='font-size: 16px; color: blue;'>$filterText</span>
         </div><table border='1' style='border-collapse: collapse; width: 100%; text-align: center;'>";
    $html .= "<thead><tr style='text-align:center;'><th rowspan='2'>Sl. No</th><th rowspan='2'>" . ($isDriverPFView ? "Token No (Name)" : $groupLabel) . "</th><th colspan='3'>Cumulative</th>";

    foreach ($dateHeaders as $d) {
        $html .= "<th colspan='3'>" . date("d-m-Y", strtotime($d)) . "</th>";
    }
    $html .= "</tr>";

    // Subheaders
    $html .= "<tr style='text-align:center;'><th>KM</th><th>HSD</th><th>KMPL</th>";
    foreach ($dateHeaders as $_) {
        $html .= "<th>KM</th><th>HSD</th><th>KMPL</th>";
    }

    $html .= "</tr></thead><tbody>";

    // Fill rows
    $sl = 1;
    foreach ($groupedData as $key => $dates) {
        $totalKm = 0;
        $totalHsd = 0;

        // Get name/token if driver PF
        $label = $key;
        if ($isDriverPFView) {
            $details = getDriverDetails($key);
            if ($details) {
                $label = $details['token_number'] . " (" . $details['EMP_NAME'] . ")";
            }
        }

        // Cumulative data
        foreach ($dateHeaders as $d) {
            $totalKm += $dates[$d]['km'] ?? 0;
            $totalHsd += $dates[$d]['hsd'] ?? 0;
        }
        $cumulativeKmpl = $totalHsd > 0 ? number_format($totalKm / $totalHsd, 2) : "0.00";

        $html .= "<tr><td>$sl</td><td>$label</td>
              <td><strong>" . round($totalKm, 2) . "</strong></td>
              <td><strong>" . round($totalHsd, 2) . "</strong></td>
              <td><strong>$cumulativeKmpl</strong></td>";

        // Per-date data if PF view
        foreach ($dateHeaders as $d) {
            $km = round($dates[$d]['km'] ?? 0, 2);
            $hsd = round($dates[$d]['hsd'] ?? 0, 2);
            $kmpl = $hsd > 0 ? number_format($km / $hsd, 2) : "0.00";
            $html .= "<td>$km</td><td>$hsd</td><td>$kmpl</td>";
        }


        $html .= "</tr>";
        $sl++;
    }

    // Overall total row
    $overallTotals = [];
    $overallTotalKm = 0;
    $overallTotalHsd = 0;

    // Accumulate per-date and cumulative totals
    foreach ($groupedData as $dates) {
        foreach ($dateHeaders as $d) {
            $km = $dates[$d]['km'] ?? 0;
            $hsd = $dates[$d]['hsd'] ?? 0;

            $overallTotals[$d]['km'] = ($overallTotals[$d]['km'] ?? 0) + $km;
            $overallTotals[$d]['hsd'] = ($overallTotals[$d]['hsd'] ?? 0) + $hsd;

            $overallTotalKm += $km;
            $overallTotalHsd += $hsd;
        }
    }

    $overallKmpl = $overallTotalHsd > 0 ? number_format($overallTotalKm / $overallTotalHsd, 2) : "0.00";

    $html .= "<tr style='font-weight: bold; background-color: #f0f0f0;'>
            <td colspan='2'>Overall Total</td>
            <td>" . round($overallTotalKm, 2) . "</td>
            <td>" . round($overallTotalHsd, 2) . "</td>
            <td>$overallKmpl</td>";

    foreach ($dateHeaders as $d) {
        $km = round($overallTotals[$d]['km'] ?? 0, 2);
        $hsd = round($overallTotals[$d]['hsd'] ?? 0, 2);
        $kmpl = $hsd > 0 ? number_format($km / $hsd, 2) : "0.00";
        $html .= "<td>$km</td><td>$hsd</td><td>$kmpl</td>";
    }

    $html .= "</tr></tbody></table>";
} elseif (strtolower($sch_no) !== 'all' || strtolower($bus_number) !== 'all' || strtolower($driver_token) !== 'all') {
    if (!$from || !$to || !$division || !$depot) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }

    // Convert dates to dd-mm-yyyy format
    $fromFormatted = date("d-m-Y", strtotime($from));
    $toFormatted = date("d-m-Y", strtotime($to));

    if (empty($sch_no)) {
        $whereClauses = ["date BETWEEN '$from' AND '$to'"];
    } elseif (empty($bus_number)) {
        $whereClauses = ["date BETWEEN '$from' AND '$to'", "division_id = '$division'", "depot_id = '$depot'"];
    }
    $filterText = "";
    if (!empty($sch_no)) {
        $whereClauses[] = "route_no = '$sch_no'";
        $filterText = "Route No: $sch_no";
    }
    if (!empty($bus_number)) {
        $whereClauses[] = "bus_number = '$bus_number'";
        $filterText = "Bus Number: $bus_number";
    }
    if (!empty($driver_token)) {
        $whereClauses[] = "(driver_1_pf = '$driver_token' OR driver_2_pf = '$driver_token')";
        $filterText = "Driver PF: $driver_token";
    }

    $whereClause = implode(" AND ", $whereClauses);

    // Fetch Division & Depot Names
    $locationQuery = "SELECT division, depot FROM location WHERE division_id = '$division' AND depot_id = '$depot'";
    $locationResult = mysqli_query($db, $locationQuery);
    $locationData = mysqli_fetch_assoc($locationResult);
    $divisionName = $locationData['division'] ?? 'Unknown';
    $depotName = $locationData['depot'] ?? 'Unknown';

    // Fetch Data
    $query = "SELECT * FROM vehicle_kmpl WHERE deleted !=1 and $whereClause ORDER BY route_no, bus_number, driver_1_pf, driver_2_pf";
    $result = mysqli_query($db, $query);

    if (!$result) {
        echo json_encode(['error' => 'Database error: ' . mysqli_error($db)]);
        exit;
    }

    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Initialize grouped data
    $groupedByVehicle = [];
    $groupedByDriver = [];
    $groupedBySchedule = [];

    foreach ($data as $row) {
        $km_operated = $row['km_operated'];
        $hsd = $row['hsd'];

        // Group by Vehicle
        if (!isset($groupedByVehicle[$row['bus_number']])) {
            $groupedByVehicle[$row['bus_number']] = [
                'bus_number' => $row['bus_number'],
                'km_operated' => 0,
                'hsd' => 0
            ];
        }
        $groupedByVehicle[$row['bus_number']]['km_operated'] += $km_operated;
        $groupedByVehicle[$row['bus_number']]['hsd'] += $hsd;

        // Group by Driver PF
        $drivers = [];
        if (!empty($row['driver_1_pf'])) $drivers[] = $row['driver_1_pf'];
        if (!empty($row['driver_2_pf'])) $drivers[] = $row['driver_2_pf'];

        $numDrivers = count($drivers);
        foreach ($drivers as $driver_pf) {
            if (!isset($groupedByDriver[$driver_pf])) {
                $groupedByDriver[$driver_pf] = [
                    'driver_pf' => $driver_pf,
                    'km_operated' => 0,
                    'hsd' => 0
                ];
            }
            $groupedByDriver[$driver_pf]['km_operated'] += $km_operated / $numDrivers;
            $groupedByDriver[$driver_pf]['hsd'] += $hsd / $numDrivers;
        }

        // Group by Schedule
        if (!isset($groupedBySchedule[$row['route_no']])) {
            $groupedBySchedule[$row['route_no']] = [
                'route_no' => $row['route_no'],
                'km_operated' => 0,
                'hsd' => 0
            ];
        }
        $groupedBySchedule[$row['route_no']]['km_operated'] += $km_operated;
        $groupedBySchedule[$row['route_no']]['hsd'] += $hsd;
    }

    // Function to calculate KMPL with 2 decimal places
    function calculateKmpl($km_operated, $hsd)
    {
        return ($hsd != 0) ? number_format($km_operated / $hsd, 2) : "0.00";
    }

    // Generate Report Header
    $html = "<div style='text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 10px;'>
            KMPL Report <br> 
            Division: $divisionName | Depot: $depotName <br>
            From Date: $fromFormatted To Date: $toFormatted <br>
            <span style='font-size: 16px; color: blue;'>$filterText</span>
         </div>";

    $html .= "<div style='display: flex; justify-content: center; gap: 30px;'>";

    // Function to generate tables
    function generateTable($title, $data, $columns)
    {
        $html = "<div style='text-align: center;'>
                <h4>$title</h4>
                <table border='1' style='border-collapse: collapse; width: 100%; text-align: center;'>
                <tr>
                    <th>SL No</th>";

        // Add table headers
        foreach ($columns as $column) {
            $html .= "<th>$column</th>";
        }
        $html .= "</tr>";

        $totalKm = 0;
        $totalHsd = 0;
        $count = 1;

        foreach ($data as $row) {
            $totalKm += $row['km_operated'];
            $totalHsd += $row['hsd'];
            $html .= "<tr><td>" . $count++ . "</td>";
            foreach ($row as $value) {
                $html .= "<td>$value</td>";
            }
            $html .= "<td>" . calculateKmpl($row['km_operated'], $row['hsd']) . "</td></tr>";
        }

        // Add total row
        $html .= "<tr><td colspan='2'><strong>Total</strong></td>
              <td><strong>$totalKm</strong></td>
              <td><strong>$totalHsd</strong></td>
              <td><strong>" . calculateKmpl($totalKm, $totalHsd) . "</strong></td></tr>";

        $html .= "</table></div>";
        return $html;
    }

    // Generate reports based on filters
    if (!empty($sch_no)) {
        $html .= generateTable("Vehicle Wise Report", $groupedByVehicle, ["Bus Number", "KM Operated", "HSD", "KMPL"]);
        $html .= generateTable("Driver PF Wise Report", $groupedByDriver, ["Driver PF", "KM Operated", "HSD", "KMPL"]);
    }

    if (!empty($bus_number)) {
        $html .= generateTable("Schedule Wise Report", $groupedBySchedule, ["Schedule No", "KM Operated", "HSD", "KMPL"]);
        $html .= generateTable("Driver PF Wise Report", $groupedByDriver, ["Driver PF", "KM Operated", "HSD", "KMPL"]);
    }

    if (!empty($driver_token)) {
        $html .= generateTable("Schedule Wise Report", $groupedBySchedule, ["Schedule No", "KM Operated", "HSD", "KMPL"]);
        $html .= generateTable("Vehicle Wise Report", $groupedByVehicle, ["Bus Number", "KM Operated", "HSD", "KMPL"]);
    }

    $html .= "</div>";
}

// Return JSON response
echo json_encode(['html' => $html]);
