<?php
include '../includes/connection.php';  // Include your database connection file
include '../pages/session.php';
// Define the division based on the session
$division = $_SESSION['DIVISION_ID'];

// Get the date range (last 30 days up to today)
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-60 days'));

// Prepare the SQL query
$query = "
    SELECT 
        kd.date, 
        kd.total_km, 
        kd.hsd, 
        kd.kmpl, 
        l.depot AS depot_name
    FROM 
        kmpl_data kd
    JOIN 
        location l ON kd.division = l.division_id AND kd.depot = l.depot_id
    WHERE 
        kd.division = ? 
        AND kd.date BETWEEN ? AND ?
    ORDER BY 
        kd.date ASC
";

$stmt = $db->prepare($query);
$stmt->bind_param("sss", $division, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$stmt->close();
$db->close();

echo json_encode($data);
?>
