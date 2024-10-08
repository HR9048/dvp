<?php
include '../pages/session.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session has expired, please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}

header('Content-Type: application/json');
include '../includes/connection.php';
confirm_logged_in();

try {
    // Set the start and end dates
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');

    // Query to get depot-wise data including total off-road count
    $sql = "SELECT
                l.kmpl_division AS division,
                l.depot,
                DATE(od.date) AS date,
                SUM(od.ORDepot) AS or_depot_count,
                SUM(od.ORDWS) AS or_dws_count,
                SUM(od.Police) AS police_count,
                SUM(od.Dealer) AS dealer_count,
                SUM(od.ORDepot + od.ORDWS + od.Police + od.Dealer) AS total_offroad_count
            FROM dvp_data od
            INNER JOIN location l ON od.depot = l.depot_id AND od.division = l.division_id
            WHERE od.date BETWEEN ? AND ?
            GROUP BY l.kmpl_division, l.depot, DATE(od.date)
            ORDER BY DATE(od.date), l.division_id, l.depot_id";

    // Prepare the statement
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('Statement preparation failed: ' . $db->error);
    }

    // Bind the date parameters
    $stmt->bind_param('ss', $start_date, $end_date);
 
    // Execute the statement
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    // Fetch the result set and process the data
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'division' => $row['division'],
            'depot' => $row['depot'],
            'date' => $row['date'],
            'or_depot_count' => $row['or_depot_count'],
            'or_dws_count' => $row['or_dws_count'],
            'police_count' => $row['police_count'],
            'dealer_count' => $row['dealer_count'],
            'total_offroad_count' => $row['total_offroad_count']
        ];
    }

    // Output the result in JSON format
    echo json_encode($data);

    // Close the statement
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
