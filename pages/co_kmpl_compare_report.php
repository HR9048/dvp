<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && ($_SESSION['JOB_TITLE'] == 'CME_CO')) {

    
        // Get date range (last 15 days excluding today)
        $dates = [];
        for ($i = 1; $i <= 15; $i++) {
            $dates[] = date('Y-m-d', strtotime("-$i day"));
        }
        $dates = array_reverse($dates); // show earliest date first
    
        // Build date list for SQL IN clause
        $dateList = "'" . implode("','", $dates) . "'";
    
        // Query to fetch manual and logsheet data joined on date, depot, and division
        $query = "SELECT 
                    l.kmpl_division AS division,
                    l.depot,
                    d.date,
    
                    d.total_km AS manual_km,
                    d.hsd AS manual_hsd,
                    d.kmpl AS manual_kmpl,
    
                    SUM(v.km_operated) AS logsheet_km,
                    SUM(v.hsd) AS logsheet_hsd,
                    ROUND(SUM(v.km_operated)/NULLIF(SUM(v.hsd),0), 2) AS logsheet_kmpl
    
                FROM location l
                LEFT JOIN kmpl_data d 
                    ON l.division_id = d.division AND l.depot_id = d.depot
                    AND d.date IN ($dateList)
                LEFT JOIN vehicle_kmpl v 
                    ON l.division_id = v.division_id AND l.depot_id = v.depot_id
                    AND v.date = d.date
                    AND v.deleted != 1
                WHERE d.date IS NOT NULL
                GROUP BY l.division, l.depot, d.date
                ORDER BY l.division_id, l.depot_id, d.date";
    
        $result = mysqli_query($db, $query);
    
        // Organize data by depot and date
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $division = $row['division'];
            $depot = $row['depot'];
            $date = $row['date'];
    
            $data[$depot]['division'] = $division;
            $data[$depot]['dates'][$date] = [
                'km' => [
                    'manual' => $row['manual_km'] ?? 0,
                    'logsheet' => $row['logsheet_km'] ?? 0,
                    'diff' => ($row['manual_km'] ?? 0) - ($row['logsheet_km'] ?? 0),
                ],
                'hsd' => [
                    'manual' => $row['manual_hsd'] ?? 0,
                    'logsheet' => $row['logsheet_hsd'] ?? 0,
                    'diff' => ($row['manual_hsd'] ?? 0) - ($row['logsheet_hsd'] ?? 0),
                ],
                'kmpl' => [
                    'manual' => $row['manual_kmpl'] ?? 0,
                    'logsheet' => $row['logsheet_kmpl'] ?? 0,
                    'diff' => round(($row['manual_kmpl'] ?? 0) - ($row['logsheet_kmpl'] ?? 0), 2),
                ]
            ];
        }
    
        // Start table
echo "<div class='container1'><h3 class='text-center'>Depot Wise KMPL Comparison Report</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>
<thead>
    <tr>
        <th rowspan='2'>Division</th>
        <th rowspan='2'>Depot</th>
        <th rowspan='2'>Metric</th>
        <th rowspan='2'>Content</th>";
foreach ($dates as $date) {
    echo "<th>" . date('d-M', strtotime($date)) . "</th>";
}
echo "</tr>
</thead>
<tbody>";

// Print data rows
foreach ($data as $depot => $info) {
    $division = $info['division'];
    $metrics = ['km' => 'KM', 'hsd' => 'HSD', 'kmpl' => 'KMPL'];

    $firstDepotRow = true;

    foreach ($metrics as $key => $label) {
        // Print 3 rows for each metric: Manual, Logsheet, Difference
        echo "<tr>";
        if ($firstDepotRow) {
            echo "<td rowspan='9'>$division</td>";
            echo "<td rowspan='9'>$depot</td>";
            $firstDepotRow = false;
        }

        echo "<td rowspan='3'>$label</td><td>Manual</td>";
        foreach ($dates as $date) {
            $val = $info['dates'][$date][$key] ?? ['manual' => '-', 'logsheet' => '-', 'diff' => '-'];
            echo "<td>{$val['manual']}</td>";
        }
        echo "</tr>";

        echo "<tr><td>Logsheet</td>";
        foreach ($dates as $date) {
            $val = $info['dates'][$date][$key] ?? ['manual' => '-', 'logsheet' => '-', 'diff' => '-'];
            echo "<td>{$val['logsheet']}</td>";
        }
        echo "</tr>";

        echo "<tr><td>Difference</td>";
        foreach ($dates as $date) {
            $val = $info['dates'][$date][$key] ?? ['manual' => '-', 'logsheet' => '-', 'diff' => '-'];
            echo "<td>{$val['diff']}</td>";
        }
        echo "</tr>";
    }
}

echo "</tbody></table></div>";

    

} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
    ?>
