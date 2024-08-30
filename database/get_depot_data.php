<?php


// Include the database connection file
include '../includes/connection.php';
include '../pages/session.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}
header('Content-Type: application/json');

try {
    // Query to get division-wise off-road data
    $sql = "SELECT 
        l.KMPL_division AS division,
        COUNT(od.bus_number) AS off_road_count
    FROM off_road_data od
    INNER JOIN location l 
        ON od.depot = l.depot_id 
        AND od.division = l.division_id
    WHERE od.status = 'off_road' 
        AND od.off_road_location != 'RWY'
        AND od.id IN (
            SELECT MAX(id)
            FROM off_road_data
            WHERE status = 'off_road'
            GROUP BY bus_number, division
        )
    GROUP BY l.division_id
    ORDER BY l.division_id";

    $result = $db->query($sql);
    $divisions = $result->fetch_all(MYSQLI_ASSOC);

    // Query to get depot details within each division
    $sqlDepots = "SELECT 
        l.KMPL_division AS division,
        l.depot,
        COUNT(DISTINCT od.bus_number) AS off_road_count
    FROM off_road_data od
    INNER JOIN location l 
        ON od.depot = l.depot_id 
        AND od.division = l.division_id
    WHERE od.status = 'off_road' 
        AND od.off_road_location != 'RWY'
        AND od.id IN (
            SELECT MAX(id)
            FROM off_road_data
            WHERE status = 'off_road'
            GROUP BY bus_number, depot, division
        )
    GROUP BY l.division_id, l.depot_id
    ORDER BY l.division_id, l.depot_id";

    $resultDepots = $db->query($sqlDepots);
    $depots = $resultDepots->fetch_all(MYSQLI_ASSOC);

    // Query to get RWY off-road data, aggregated by division
    $sqlRWY = "SELECT 
        l.KMPL_division AS division,
        COUNT(DISTINCT od.bus_number) AS off_road_count
    FROM off_road_data od
    INNER JOIN location l 
        ON od.depot = l.depot_id 
        AND od.division = l.division_id
    WHERE od.status = 'off_road' 
        AND od.off_road_location = 'RWY'
        AND od.id IN (
            SELECT MAX(id)
            FROM off_road_data
            WHERE status = 'off_road'
            GROUP BY bus_number, division
        )
    GROUP BY l.division_id
    ORDER BY l.division_id";

    $resultRWY = $db->query($sqlRWY);
    $rwyData = $resultRWY->fetch_all(MYSQLI_ASSOC);

    // Aggregate RWY data for overall count
    $overallRWYCount = array_sum(array_column($rwyData, 'off_road_count'));

    echo json_encode([
        'divisions' => $divisions,
        'depots' => $depots,
        'rwy' => $rwyData,
        'rwyOverallCount' => $overallRWYCount
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
