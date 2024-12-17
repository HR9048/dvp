<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
$division_id = $_SESSION['DIVISION_ID'];

if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'DC') { 
?>
<div class="container1">
    <h2 style="text-align: center;">Defective Buses Report</h2>
    <table>
        <thead>
            <tr>
                <th>Division</th>
                <th>Depot</th>
                <th>BS-6 Vehicles Held</th>
                <th>Camera Defect</th>
                <th>PIS Defect</th>
                <th>Camera & PIS Defect</th>
                <th>VLTS Disconnected</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Array to store rows grouped by division
            // Array to store rows grouped by division
            $data = [];
            $corporationTotal = [
                'BS-6 Vehicles Held' => 0,
                'Camera Defect' => 0,
                'PIS Defect' => 0,
                'Both Defect' => 0,
                'VLTS Disconnected' => 0 // Ensure this key is initialized
            ];

            // Fetch Leyland BS-6 buses data
            $queryLeylandBuses = "SELECT l.division, l.depot, COUNT(DISTINCT br.bus_number) AS num_leyland_buses
                      FROM bus_registration br
                      JOIN location l ON br.division_name = l.division_id AND br.depot_name = l.depot_id
                      WHERE br.make = 'Leyland' AND br.emission_norms = 'BS-6' AND l.division_id = '$division_id'
                      GROUP BY l.division, l.depot
                      ORDER BY l.division_id, l.depot_id";

            $resultLeylandBuses = mysqli_query($db, $queryLeylandBuses) or die(mysqli_error($db));

            // Store Leyland BS-6 buses data grouped by division
            while ($row = mysqli_fetch_assoc($resultLeylandBuses)) {
                $division = $row['division'];
                $depot = $row['depot'];
                $data[$division][$depot]['BS-6 Vehicles Held'] = $row['num_leyland_buses'];
                $corporationTotal['BS-6 Vehicles Held'] += $row['num_leyland_buses'];
            }

            // Fetch defective buses data
            $queryDefectiveBuses = "SELECT l.division, l.depot, dt.defect_name, COUNT(DISTINCT dcd.bus_number) AS num_defects
                        FROM depot_camera_defect dcd
                        JOIN depot_camera_defect_type dt ON dcd.defect_type_id = dt.id
                        JOIN location l ON dcd.division_id = l.division_id AND dcd.depot_id = l.depot_id AND dcd.status = 1
                        WHERE l.division_id = '$division_id'
                        GROUP BY dt.defect_name, l.division, l.depot
                        ORDER BY l.division_id, l.depot_id";

            $resultDefectiveBuses = mysqli_query($db, $queryDefectiveBuses) or die(mysqli_error($db));

            // Store defective buses data grouped by division
            while ($row = mysqli_fetch_assoc($resultDefectiveBuses)) {
                $division = $row['division'];
                $depot = $row['depot'];
                $defectName = $row['defect_name'];
                $count = $row['num_defects'];

                if (!isset($data[$division][$depot])) {
                    $data[$division][$depot] = [];
                }

                $columnHeading =  $row['defect_name'];
                $data[$division][$depot][$columnHeading] = $count;

                // Safely update the corporation total
                if (!isset($corporationTotal[$columnHeading])) {
                    $corporationTotal[$columnHeading] = 0;
                }
                $corporationTotal[$columnHeading] += $count;
            }

            // Function to match defect names with column headings
            function getMatchingColumnHeading($defectName) {
                // Example column headings
                $columnHeadings = [
                    'camera_defect' => 'Camera Defect Details',
                    'status' => 'Defect Status',
                    'division' => 'Division Name',
                    'date_reported' => 'Reported Date'
                ];
            
                // Check if the parameter exists in the columnHeadings array
                if (array_key_exists($defectName, $columnHeadings)) {
                    return $columnHeadings[$defectName];
                } else {
                    return 'Unknown Column'; // Default fallback
                }
            }

            // Output data row by row
            foreach ($data as $division => $depots) {
                $divisionTotalBS6 = 0;
                $divisionTotalCamera = 0;
                $divisionTotalPIS = 0;
                $divisionTotalBoth = 0;
                $divisionTotalvlts = 0;

                foreach ($depots as $depot => $counts) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($division) . "</td>";
                    echo "<td>" . htmlspecialchars($depot) . "</td>";

                    $BS6 = isset($counts['BS-6 Vehicles Held']) ? $counts['BS-6 Vehicles Held'] : 0;
                    $Camera = isset($counts['Camera/DVR system not working']) ? $counts['Camera/DVR system not working'] : 0;
                    $PIS = isset($counts['Public Information System not working']) ? $counts['Public Information System not working'] : 0;
                    $Both = isset($counts['Both Camera and PIS not working']) ? $counts['Both Camera and PIS not working'] : 0;
                    $vlts = isset($counts['VLTS Status Showing as Disconnected']) ? $counts['VLTS Status Showing as Disconnected'] : 0;

                    echo "<td>" . htmlspecialchars($BS6) . "</td>";
                    echo "<td>" . htmlspecialchars($Camera) . "</td>";
                    echo "<td>" . htmlspecialchars($PIS) . "</td>";
                    echo "<td>" . htmlspecialchars($Both) . "</td>";
                    echo "<td>" . htmlspecialchars($vlts) . "</td>";
                    echo "</tr>";

                    $divisionTotalBS6 += $BS6;
                    $divisionTotalCamera += $Camera;
                    $divisionTotalPIS += $PIS;
                    $divisionTotalBoth += $Both;
                    $divisionTotalvlts += $vlts;
                }

                echo "<tr style='font-weight: bold;'>";
                echo "<td colspan='2'>Total " . htmlspecialchars($division) . "</td>";
                echo "<td>" . htmlspecialchars($divisionTotalBS6) . "</td>";
                echo "<td>" . htmlspecialchars($divisionTotalCamera) . "</td>";
                echo "<td>" . htmlspecialchars($divisionTotalPIS) . "</td>";
                echo "<td>" . htmlspecialchars($divisionTotalBoth) . "</td>";
                echo "<td>" . htmlspecialchars($divisionTotalvlts) . "</td>";
                echo "</tr>";
            }

           

            ?>
        </tbody>
    </table>
    <br>

    <h2 style="text-align: center;">Defect Records</h2>
    <table>
        <thead>
            <tr>
                <th>Sl No.</th>
                <th>Division</th>
                <th>Depot</th>
                <th>Bus Number</th>
                <th>Date of Commissioning</th>
                <th>Make</th>
                <th>Emission Norms</th>
                <th>Defect Type</th>
                <th>Defect Notice Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch data from database
            $query = "SELECT l.division, l.depot, dcd.bus_number, dcd.doc, dcd.make, dcd.emission_norms, dt.defect_name, dcd.defect_notice_date
              FROM depot_camera_defect dcd
              JOIN depot_camera_defect_type dt ON dcd.defect_type_id = dt.id
              JOIN location l ON dcd.division_id = l.division_id AND dcd.depot_id = l.depot_id AND dcd.status = 1
              WHERE l.division_id = '$division_id'
              ORDER BY l.division_id, l.depot_id, dcd.defect_type_id ASC";

            $result = mysqli_query($db, $query) or die(mysqli_error($db));

            // Initialize serial numbers
            $overallSerialNo = 1;
            $prevDivision = "";

            while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $overallSerialNo++; ?></td>
                    <?php if ($row['division'] !== $prevDivision): ?>
                    <?php endif; ?>
                    <?php $prevDivision = $row['division']; ?>
                    <td><?php echo htmlspecialchars($row['division']); ?></td>
                    <td><?php echo htmlspecialchars($row['depot']); ?></td>
                    <td><?php echo htmlspecialchars($row['bus_number']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['doc'])); ?></td>
                    <td><?php echo htmlspecialchars($row['make']); ?></td>
                    <td><?php echo htmlspecialchars($row['emission_norms']); ?></td>
                    <td><?php echo htmlspecialchars($row['defect_name']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['defect_notice_date'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <!-- Download Excel button -->
    <button class="btn btn-success" id="downloadExcel">Download Excel</button>
    <!-- Download Text button -->
    <button class="btn btn-danger" id="downloadText">Download Text</button>
</div>


<!-- Include xlsx.full.min.js library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>

<script>
    document.getElementById('downloadExcel').addEventListener('click', function () {
        // Generate a workbook from the first table in the container
        var table1 = document.querySelector('.container1 table:nth-of-type(1)');
        var table2 = document.querySelector('.container1 table:nth-of-type(2)');

        // Convert HTML tables to sheets
        var wb = XLSX.utils.book_new();
        var ws1 = XLSX.utils.table_to_sheet(table1);
        var ws2 = XLSX.utils.table_to_sheet(table2);

        // Append sheets to workbook
        XLSX.utils.book_append_sheet(wb, ws1, "Defective Buses Report");
        XLSX.utils.book_append_sheet(wb, ws2, "Defect Records");

        // Generate file name with today's date
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); // Month is 0-based
        var yyyy = today.getFullYear();
        var fileName = `Defective_Buses_Report_${dd}-${mm}-${yyyy}.xlsx`;

        // Save workbook as Excel
        XLSX.writeFile(wb, fileName);
    });

    document.getElementById('downloadText').addEventListener('click', function () {
        var textContent = "";

        // Select both tables
        var tables = document.querySelectorAll('.container1 table');

        // Process tables and rows into plain text
        tables.forEach(function (table, index) {
            textContent += `Table ${index + 1}:\n`;

            table.querySelectorAll('tr').forEach(function (row) {
                var rowData = [];
                row.querySelectorAll('th, td').forEach(function (cell) {
                    rowData.push(cell.innerText.trim());
                });
                textContent += rowData.join('\t') + '\n';
            });

            textContent += '\n';
        });

        // Generate file name
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        var fileName = `Defective_Buses_Report_${dd}-${mm}-${yyyy}.txt`;

        // Create and download the text file
        var blob = new Blob([textContent], { type: "text/plain;charset=utf-8" });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.click();
    });
</script>

<?php
} else {
  echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
  exit;
}
include '../includes/footer.php';
?>