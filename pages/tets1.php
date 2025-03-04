<?php
// Connect to the Database
$servername = "localhost";
$username = "root";
$password = "kkrtcsystem";
$dbname = "kkrtcdvp_data";
$port = "33306";
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Date range
$start_date = '2025-02-01';
$end_date = '2025-02-24';

// Query to get total counts and fixation data
$sql = "SELECT 
            COUNT(*) AS total_count,
            SUM(bus_allotted_status) AS total_bus_fix,
            SUM(driver_1_allotted_status) AS total_driver_fix
        FROM sch_veh_out
        WHERE departed_date BETWEEN '$start_date' AND '$end_date'";

$result = $conn->query($sql);
$total_data = $result->fetch_assoc();

$total_count = $total_data['total_count'];
$total_bus_fix = $total_data['total_bus_fix'];
$total_driver_fix = $total_data['total_driver_fix'];

// Calculate the fixation percentage
$fixation_percentage = 0;
if ($total_count > 0) {
    $fixation_percentage = (($total_bus_fix + $total_driver_fix) / (2 * $total_count)) * 100;
}

// Query to get depot-wise data
$sql_depot = "SELECT
                s.depot_id,
                l.depot as depot_name,
                l.division as division_name,
                COUNT(*) AS depot_count,
                SUM(s.bus_allotted_status) AS depot_bus_fix,
                SUM(s.driver_1_allotted_status) AS depot_driver_fix
              FROM sch_veh_out s
              JOIN location l ON s.depot_id = l.depot_id
              WHERE s.departed_date BETWEEN '$start_date' AND '$end_date'
              GROUP BY s.depot_id, l.depot, l.division";

$depot_result = $conn->query($sql_depot);
$depot_data = [];
$division_totals = []; // This will hold the division totals

while ($row = $depot_result->fetch_assoc()) {
    $depot_count = $row['depot_count'];
    $depot_bus_fix = $row['depot_bus_fix'];
    $depot_driver_fix = $row['depot_driver_fix'];

    // Calculate fixed and changed buses and drivers for depot
    $depot_bus_fixed = $depot_count - $depot_bus_fix;  // 0 means fixed
    $depot_driver_fixed = $depot_count - $depot_driver_fix;  // 0 means fixed

    // Calculate depot fixation percentage for bus and driver separately
    $depot_bus_fixation_percentage = 0;
    $depot_driver_fixation_percentage = 0;
    if ($depot_count > 0) {
        $depot_bus_fixation_percentage = ($depot_bus_fix / $depot_count) * 100;
        $depot_driver_fixation_percentage = ($depot_driver_fix / $depot_count) * 100;
    }

    $depot_data[] = [
        'depot_name' => $row['depot_name'],
        'division_name' => $row['division_name'],
        'depot_count' => $depot_count,
        'depot_bus_fix' => $depot_bus_fix,
        'depot_driver_fix' => $depot_driver_fix,
        'depot_bus_fixed' => $depot_bus_fixed,
        'depot_driver_fixed' => $depot_driver_fixed,
        'depot_bus_fixation_percentage' => $depot_bus_fixation_percentage,
        'depot_driver_fixation_percentage' => $depot_driver_fixation_percentage
    ];

    // Calculate division totals
    if (!isset($division_totals[$row['division_name']])) {
        $division_totals[$row['division_name']] = [
            'division_count' => 0,
            'division_bus_fix' => 0,
            'division_driver_fix' => 0,
            'division_bus_fixed' => 0,
            'division_driver_fixed' => 0
        ];
    }

    $division_totals[$row['division_name']]['division_count'] += $depot_count;
    $division_totals[$row['division_name']]['division_bus_fix'] += $depot_bus_fix;
    $division_totals[$row['division_name']]['division_driver_fix'] += $depot_driver_fix;
    $division_totals[$row['division_name']]['division_bus_fixed'] += $depot_bus_fixed;
    $division_totals[$row['division_name']]['division_driver_fixed'] += $depot_driver_fixed;
}

// Calculate division fixation percentages
foreach ($division_totals as $division_name => &$data) {
    if ($data['division_count'] > 0) {
        $data['division_bus_fixation_percentage'] = ($data['division_bus_fix'] / $data['division_count']) * 100;
        $data['division_driver_fixation_percentage'] = ($data['division_driver_fix'] / $data['division_count']) * 100;
    }
}

// If export to Excel is requested
if (isset($_GET['export_excel'])) {
    // Set the header to force download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="data_export.xlsx"');
    header('Cache-Control: max-age=0');

    // Start creating the Excel file
    echo "Depot Name\tDivision Name\tTotal Count\tBus Fixed\tBus Changed\tDriver Fixed\tDriver Changed\tBus Fixation Percentage\tDriver Fixation Percentage\n";

    foreach ($depot_data as $depot) {
        echo $depot['depot_name'] . "\t" . $depot['division_name'] . "\t" . $depot['depot_count'] . "\t" . $depot['depot_bus_fixed'] . "\t" . $depot['depot_bus_fix'] . "\t" . $depot['depot_driver_fixed'] . "\t" . $depot['depot_driver_fix'] . "\t" . number_format($depot['depot_bus_fixation_percentage'], 2) . "\t" . number_format($depot['depot_driver_fixation_percentage'], 2) . "\n";
    }

    echo "\nDivision Total\n";
    echo "Division Name\tTotal Count\tBus Fixed\tDriver Fixed\tBus Fixation Percentage\tDriver Fixation Percentage\n";
    foreach ($division_totals as $division => $data) {
        echo $division . "\t" . $data['division_count'] . "\t" . $data['division_bus_fixed'] . "\t" . $data['division_driver_fixed'] . "\t" . number_format($data['division_bus_fixation_percentage'], 2) . "\t" . number_format($data['division_driver_fixation_percentage'], 2) . "\n";
    }

    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Extraction & Fixation Percentage</title>
    <style>
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 10px;
            text-align: center;
        }

        h1 {
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>Data for February 2025</h1>

    <h2>Total Fixation Percentage</h2>
    <p>Total count: <?php echo $total_count; ?></p>
    <p>Overall Fixation Percentage: <?php echo number_format($fixation_percentage, 2); ?>%</p>
    <div class="container">
        <h2>Depot-wise Fixation Status</h2>
        <table>
            <tr>
                <th>Depot Name</th>
                <th>Division Name</th>
                <th>Total Count</th>
                <th>Bus Fixed</th>
                <th>Bus Changed</th>
                <th>Driver Fixed</th>
                <th>Driver Changed</th>
                <th>Bus Fixation Percentage</th>
                <th>Driver Fixation Percentage</th>
            </tr>
            <?php foreach ($depot_data as $depot): ?>
                <tr>
                    <td><?php echo $depot['depot_name']; ?></td>
                    <td><?php echo $depot['division_name']; ?></td>
                    <td><?php echo $depot['depot_count']; ?></td>
                    <td><?php echo $depot['depot_bus_fixed']; ?></td>
                    <td><?php echo $depot['depot_bus_fix']; ?></td>
                    <td><?php echo $depot['depot_driver_fixed']; ?></td>
                    <td><?php echo $depot['depot_driver_fix']; ?></td>
                    <td><?php echo number_format($depot['depot_bus_fixation_percentage'], 2); ?>%</td>
                    <td><?php echo number_format($depot['depot_driver_fixation_percentage'], 2); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2>Division-wise Total Fixation Status</h2>
        <table>
            <tr>
                <th>Division Name</th>
                <th>Total Count</th>
                <th>Bus Fixed</th>
                <th>Driver Fixed</th>
                <th>Bus Fixation Percentage</th>
                <th>Driver Fixation Percentage</th>
            </tr>
            <?php foreach ($division_totals as $division => $data): ?>
                <tr>
                    <td><?php echo $division; ?></td>
                    <td><?php echo $data['division_count']; ?></td>
                    <td><?php echo $data['division_bus_fixed']; ?></td>
                    <td><?php echo $data['division_driver_fixed']; ?></td>
                    <td><?php echo number_format($data['division_bus_fixation_percentage'], 2); ?>%</td>
                    <td><?php echo number_format($data['division_driver_fixation_percentage'], 2); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <button class="btn btn-success" id="downloadExcel">Download Excel</button>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <?php
        // Assuming $selectedDate is already defined somewhere
        $formattedDate = date('d/m/Y');
        ?>
    <script>
            document.getElementById('downloadExcel').addEventListener('click', function () {
                // Get container1 HTML content
                var htmlContent = document.querySelector('.container').outerHTML;

                // Convert HTML to workbook
                var workbook = XLSX.utils.table_to_book(document.querySelector('.container'));

                // Save workbook as Excel file with the PHP formatted date and "KMPL" appended to the file name
                XLSX.writeFile(workbook, '<?php echo $formattedDate; ?>report.xlsx');
            });
</script>

</body>

</html>