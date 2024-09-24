<?php
include '../includes/connection.php'; // Replace with your actual DB connection file
include '../pages/session.php';
confirm_logged_in();
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is experied please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}
$sql = "SELECT 
    l.division_id,
    l.kmpl_division as division,
    GROUP_CONCAT(l.depot ORDER BY l.depot_id SEPARATOR ', ') AS depots,
    GROUP_CONCAT(ROUND(subquery.total_km_depot, 2) ORDER BY l.depot_id SEPARATOR ', ') AS total_km_depots,
    GROUP_CONCAT(ROUND(subquery.total_hsd_depot, 2) ORDER BY l.depot_id SEPARATOR ', ') AS total_hsd_depots,
    GROUP_CONCAT(ROUND(subquery.avg_kmpl_depot, 2) ORDER BY l.depot_id SEPARATOR ', ') AS avg_kmpl_depots,
    ROUND(SUM(subquery.total_km_depot), 2) AS total_km_division,
    ROUND(SUM(subquery.total_hsd_depot), 2) AS total_hsd_division,
    ROUND(SUM(subquery.total_km_depot) / SUM(subquery.total_hsd_depot), 2) AS avg_kmpl_division, -- Calculate division KMPL here
    kd.date
FROM 
    kmpl_data kd
INNER JOIN 
    location l ON kd.depot = l.depot_id AND kd.division = l.division_id
INNER JOIN (
    SELECT 
        kd.depot,
        ROUND(SUM(kd.total_km), 2) AS total_km_depot,
        ROUND(SUM(kd.hsd), 2) AS total_hsd_depot,
        ROUND(SUM(kd.total_km)/ SUM(kd.hsd), 2) AS avg_kmpl_depot,
        kd.date
    FROM 
        kmpl_data kd
    GROUP BY 
        kd.depot, kd.date
) subquery ON subquery.depot = l.depot_id AND subquery.date = kd.date
WHERE 
    kd.date >= CURDATE() - INTERVAL 30 DAY AND kd.date <= CURDATE()
GROUP BY 
    l.division_id, kd.date
ORDER BY 
    l.division_id, kd.date";

$result = $db->query($sql);

$data = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>