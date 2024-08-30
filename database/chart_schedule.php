<?php
header('Content-Type: application/json');

require '../includes/connection.php';  // Ensure this file has the $db object

$query = " SELECT 
        l.kmpl_division AS division_name,
        l.depot AS depot_name,
        COUNT(s.sch_no) AS count
    FROM sch_veh_out s
    INNER JOIN location l ON s.depot_id = l.depot_id AND s.division_id = l.division_id
    WHERE s.schedule_status = 1
    GROUP BY l.kmpl_division, l.depot
    ORDER BY l.division_id,l.depot_id";

$result = $db->query($query);

if (!$result) {
    echo json_encode(['error' => $db->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$divisionCounts = [];
$depotCounts = [];

foreach ($data as $row) {
    $divisionName = $row['division_name'];
    $depotName = $row['depot_name'];
    $count = $row['count'];

    if (!isset($divisionCounts[$divisionName])) {
        $divisionCounts[$divisionName] = 0;
        $depotCounts[$divisionName] = [];
    }

    $divisionCounts[$divisionName] += $count;
    $depotCounts[$divisionName][$depotName] = $count;
}

$response = [
    'divisionCounts' => $divisionCounts,
    'depotCounts' => $depotCounts,
];

echo json_encode($response);
