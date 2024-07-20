<?php
include '../includes/connection.php'; // Your database connection file

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT 
                skm.*, 
                loc.division, 
                loc.depot, 
                sc.name AS service_class_name, 
                st.type AS service_type_name,
                skm.ID as ID
            FROM 
                schedule_master skm 
            JOIN 
                location loc 
                ON skm.division_id = loc.division_id 
                AND skm.depot_id = loc.depot_id
            LEFT JOIN 
                service_class sc 
                ON skm.service_class_id = sc.id
            LEFT JOIN 
                schedule_type st 
                ON skm.service_type_id = st.id
            WHERE 
                skm.ID = $id";

    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        $details = $result->fetch_assoc();
        echo json_encode($details);
    } else {
        echo json_encode(['error' => 'No details found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
