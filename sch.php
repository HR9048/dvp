<?php
// Database connection
$servername = "localhost"; 
$username = "root"; 
$password = "kkrtcsystem";
$port = 33306; // MySQL custom port
$database = "kkrtcdvp_data"; 
// Create connection using the custom MySQL port
$db = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
} 

// Set date range
$last_30_days = date('Y-m-d', strtotime('-31 days'));
$yesterday_date = date('Y-m-d', strtotime('-1 day'));
$today_date = date('Y-m-d'); // Today's date

// Query to fetch data
$query = "SELECT 
    sm.sch_dep_time, 
    sm.sch_key_no, 
    sm.sch_abbr, 
    st.name AS type,
    sm.division_id, 
    sm.depot_id,
    l.depot AS depot_name,
    COALESCE(SUM(CASE WHEN svo.dep_time_diff > 30 THEN 1 ELSE 0 END), 0) AS late,
    COALESCE(SUM(CASE WHEN svo.dep_time_diff <= 30 THEN 1 ELSE 0 END), 0) AS on_time,
    COALESCE(COUNT(svo.sch_no), 0) AS total_schedules
FROM schedule_master sm
LEFT JOIN sch_veh_out svo 
    ON sm.sch_key_no = svo.sch_no 
    AND sm.depot_id = svo.depot_id 
    AND svo.departed_date BETWEEN ? AND ?
LEFT JOIN service_class st ON sm.service_class_id = st.id
LEFT JOIN location l
    ON sm.depot_id = l.depot_id
WHERE sm.status = '1'
GROUP BY sm.sch_key_no, sm.depot_id
HAVING late > 7 
ORDER BY l.depot_id, sm.sch_dep_time";

$stmt = $db->prepare($query);
$stmt->bind_param("ss", $last_30_days, $yesterday_date);
$stmt->execute();
$result = $stmt->get_result();

// Insert fetched data into schedule_report
$insertQuery = "INSERT INTO schedule_report 
    (sch_dep_time, sch_key_no, sch_abbr, type, division_id, depot_id, depot_name, late, on_time, total_schedules, report_date) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insertStmt = $db->prepare($insertQuery);

while ($row = $result->fetch_assoc()) {
    $insertStmt->bind_param(
        "ssssiisiiis", 
        $row['sch_dep_time'], 
        $row['sch_key_no'], 
        $row['sch_abbr'], 
        $row['type'], 
        $row['division_id'], 
        $row['depot_id'], 
        $row['depot_name'], 
        $row['late'], 
        $row['on_time'], 
        $row['total_schedules'], 
        $today_date
    );
    $insertStmt->execute();
}

// Close connections
$stmt->close();
$insertStmt->close();
$db->close();

echo "Data inserted successfully.";
?>
