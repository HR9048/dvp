<?php
include '../includes/connection.php';
include '../pages/session.php';

$depot_id = $_SESSION['DEPOT_ID'];
// Assuming you have a database connection already established.
$sql = "SELECT 
    l.kmpl_depot AS depot, 
    DATE(d.date) as date, 
    d.ORDepot as ORDepot_count, 
    d.ORDWS as ORDWS_count, 
    d.Police as Police_count, 
    d.Dealer as Dealer_count, 
    (d.ORDepot + d.ORDWS + d.Police + d.Dealer) as total_offroad
FROM dvp_data d
INNER JOIN location l ON d.depot = l.depot_id
WHERE d.date >= NOW() - INTERVAL 30 DAY AND d.depot = $depot_id
GROUP BY l.depot, DATE(d.date)
ORDER BY DATE(d.date)
";

$result = $db->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>