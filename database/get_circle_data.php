<?php
include '../pages/session.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}
header('Content-Type: application/json');

// Include the database connection file
include '../includes/connection.php';

try {
    // Query to get off-road data by division and off-road location
    $sql = "SELECT
    l.kmpl_division AS division,
    l.depot,
    od.off_road_location,
    COUNT(od.bus_number) AS off_road_count
FROM (
    SELECT
        bus_number,
        MAX(id) AS max_id
    FROM off_road_data
    WHERE status = 'off_road'
    GROUP BY bus_number
) AS max_ids
INNER JOIN off_road_data od
    ON max_ids.bus_number = od.bus_number
    AND max_ids.max_id = od.id
INNER JOIN location l 
    ON od.depot = l.depot_id 
    AND od.division = l.division_id
GROUP BY l.kmpl_division, l.depot, od.off_road_location
ORDER BY l.division_id, l.depot_id, od.off_road_location
    ";

    $result = $db->query($sql);

    if (!$result) {
        throw new Exception($db->error);
    }

    $data = [];
    $divisionTotals = [];

    // Collect data for division totals and depot details
    while ($row = $result->fetch_assoc()) {
        $division = $row['division'];
        $depot = $row['depot'];
        $location = $row['off_road_location'];
        $count = $row['off_road_count'];

        // Store depot-wise data for tooltips
        $data[] = $row;

        // Aggregate totals by division and location
        if (!isset($divisionTotals[$division])) {
            $divisionTotals[$division] = [];
        }
        if (!isset($divisionTotals[$division][$location])) {
            $divisionTotals[$division][$location] = 0;
        }
        $divisionTotals[$division][$location] += $count;
    }

    // Prepare final data format for the chart
    $chartData = [];
    foreach ($divisionTotals as $division => $locations) {
        $totalCount = array_sum($locations);
        $chartData[] = [
            'division' => $division,
            'total_count' => $totalCount,
            'locations' => $locations
        ];
    }

    // Output the data
    echo json_encode([
        'chartData' => $chartData,
        'depotData' => $data
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
