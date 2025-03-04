<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();

// Get the input data from the AJAX request
$data = json_decode(file_get_contents('php://input'), true);

// Get the necessary parameters from the input
$month = $data['month'];
$monthName = date("F", mktime(0, 0, 0, $month, 1)); // Convert month number to name
$year = $data['year'];
$depot_id = $data['depot_id'];
$division_id = $data['division_id'];
$make = $data['make'];
$emission_norms = $data['emission_norms'];
$sch_no = $data['sch_no'];
$bus_number = $data['bus_number'];
$driver_pf = $data['driver_token'];

$depot_name = null; // Default value

$sql = "SELECT depot FROM location WHERE depot_id = ? and depot_id != '0'";
$stmt = $db->prepare($sql);
$stmt->bind_param("i",  $depot_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $depot_name = $row['depot']; // Fetch the depot name
}

$Division_name = null; // Default value

$sql = "SELECT division FROM location WHERE division_id = ? and division_id != '0'";
$stmt = $db->prepare($sql);
$stmt->bind_param("i",  $division_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $Division_name = $row['division']; // Fetch the depot name
}

$stmt->close();
// Calculate the start and end dates for the selected month
$startDate = "$year-$month-01";
$endDate = date("Y-m-t", strtotime($startDate));

$whereClauses = "WHERE v.date BETWEEN '$startDate' AND '$endDate' and deleted !='1'";

if ($depot_id) $whereClauses .= " AND v.depot_id = '$depot_id'";
if ($division_id) $whereClauses .= " AND v.division_id = '$division_id'";
if ($make) $whereClauses .= " AND bm.make = '$make'";
if ($emission_norms) $whereClauses .= " AND bm.emission_norms = '$emission_norms'";
if ($sch_no) $whereClauses .= " AND v.route_no = '$sch_no'";
if ($bus_number) $whereClauses .= " AND v.bus_number = '$bus_number'";
if ($driver_pf) $whereClauses .= " AND v.driver_1_pf = '$driver_pf' or driver_2_pf = '$driver_pf'";

// **Join `bus_master` only if needed**
$busJoin = "";
if ($bus_number) {
    $busJoin = "JOIN bus_registration bm ON v.bus_number = bm.bus_number";
} else {
    $busJoin = "LEFT JOIN bus_registration bm ON v.bus_number = bm.bus_number";
}

// Fetch main table data (Daily & Cumulative KMPL)
$query = "
SELECT 
    v.date, 
    SUM(v.km_operated) AS km_operated, 
    SUM(v.hsd) AS hsd, 
    ROUND(SUM(v.km_operated) / NULLIF(SUM(v.hsd), 0), 2) AS kmpl
FROM 
    vehicle_kmpl v
$busJoin
$whereClauses
GROUP BY 
    v.date
ORDER BY 
    v.date ASC;
";

$result = $db->query($query);
$data = [];
$detailsData = []; // Store modal data for all dates

while ($row = $result->fetch_assoc()) {
    $formattedDate = date("d-m-Y", strtotime($row['date']));
    $row['date'] = $formattedDate;
    $data[] = $row;
}

// Fetch detailed data for the modal (All Bus Details)
$detailsQuery = "
SELECT v.date, v.bus_number, v.driver_1_pf, v.km_operated, v.hsd, 
       ROUND(v.km_operated / NULLIF(v.hsd, 0), 2) AS kmpl
FROM vehicle_kmpl v
$busJoin
$whereClauses
ORDER BY v.date ASC;
";

$detailsResult = $db->query($detailsQuery);
while ($row = $detailsResult->fetch_assoc()) {
    $formattedDate = date("d-m-Y", strtotime($row['date']));
    $detailsData[$formattedDate][] = $row;
}

// Initialize cumulative variables
$cumulative_km = 0;
$cumulative_hsd = 0;
$cumulative_kmpl = 0;

// Generate HTML for the main table
$tableHtml = '<h4>Report for Year:' . $year . ' Month: ' . $monthName . ' ' . $Division_name . ' ' . $depot_name . ' ' . $make . ' ' . $emission_norms . ' ' . $sch_no . ' ' . $bus_number . '  ' . $driver_pf . '</h4><table id="kmplReportTable" class="table table-bordered">
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
    <tbody>';

foreach ($data as &$entry) {
    // Update cumulative values
    $cumulative_km += $entry['km_operated'];
    $cumulative_hsd += $entry['hsd'];

    // Calculate cumulative KMPL (prevent division by zero)
    $cumulative_kmpl = ($cumulative_hsd > 0) ? round($cumulative_km / $cumulative_hsd, 2) : 0;

    // Append cumulative values to the entry
    $entry['cumulative_km'] = $cumulative_km;
    $entry['cumulative_hsd'] = $cumulative_hsd;
    $entry['cumulative_kmpl'] = $cumulative_kmpl;

    // Add row to table with Date as a Button
    $tableHtml .= '<tr>
        <td>' . $entry['date'] . '</td>
        <td>' . $entry['km_operated'] . '</td>
        <td>' . $entry['hsd'] . '</td>
        <td>' . $entry['kmpl'] . '</td>
        <td>' . $entry['cumulative_km'] . '</td>
        <td>' . $entry['cumulative_hsd'] . '</td>
        <td>' . $entry['cumulative_kmpl'] . '</td>
    </tr>';
}

$tableHtml .= '</tbody></table>';


// Send the full HTML and detailed data as part of the response
echo json_encode([
    'html' => $tableHtml,
    'details' => $detailsData // Sending detailed data for all dates
]);
