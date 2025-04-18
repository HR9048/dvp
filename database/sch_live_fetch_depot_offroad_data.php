<?php
include '../includes/connection.php';  // Include database connection

if (isset($_POST['id'], $_POST['name'], $_POST['type'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    if ($type == "Division") {
        $query_division = "SELECT division_id FROM location WHERE division = ?";
        $stmt_division = $db->prepare($query_division);
        $stmt_division->bind_param("s", $id);
        $stmt_division->execute();
        $result_division = $stmt_division->get_result();

        if ($result_division->num_rows > 0) {
            $row_division = $result_division->fetch_assoc();
            $division_id = $row_division['division_id'];
        } else {
            echo json_encode(["status" => "error", "message" => "Division not found"]);
            exit;
        }
        $stmt_division->close();
    }
    // Determine the condition based on type
    if ($type == "Depot") {
        $query = "SELECT 
    o.bus_number, 
    o.make, 
    o.emission_norms, 
    o.depot, 
    l.depot AS depot_name, 
    MAX(o.off_road_location) AS off_road_location, 
    MIN(o.off_road_date) AS off_road_date,
    DATEDIFF(CURDATE(), MIN(o.off_road_date)) AS no_of_days_offroad
FROM off_road_data o
INNER JOIN location l ON o.depot = l.depot_id
WHERE o.depot = ? 
    AND o.status = 'off_road' 
    AND NOT EXISTS (
        SELECT 1 
        FROM off_road_data sub 
        WHERE sub.bus_number = o.bus_number 
        GROUP BY sub.bus_number 
        HAVING MAX(sub.id) AND MAX(sub.off_road_location) = 'RWY'
    )
GROUP BY o.bus_number, o.make, o.emission_norms, o.depot, l.depot
ORDER BY o.off_road_date ASC;";
    } elseif ($type == "Division") {
        $query = "SELECT 
    o.bus_number, 
    o.make, 
    o.emission_norms, 
    o.depot, 
    l.depot AS depot_name, 
    MAX(o.off_road_location) AS off_road_location, 
    MIN(o.off_road_date) AS off_road_date,
    DATEDIFF(CURDATE(), MIN(o.off_road_date)) AS no_of_days_offroad
FROM off_road_data o
INNER JOIN location l ON o.depot = l.depot_id
WHERE o.division = ? 
    AND o.status = 'off_road' 
    AND NOT EXISTS (
        SELECT 1 
        FROM off_road_data sub 
        WHERE sub.bus_number = o.bus_number 
        GROUP BY sub.bus_number 
        HAVING MAX(sub.id) AND MAX(sub.off_road_location) = 'RWY'
    )
GROUP BY o.bus_number, o.make, o.emission_norms, o.depot, l.depot
ORDER BY o.depot,o.off_road_date ASC";
    } elseif ($type == "Corporation") {
        $query = "SELECT 
    o.bus_number, 
    o.make, 
    m.make_abbr,
    o.emission_norms, 
    o.depot, 
    l.depot AS depot_name, 
    MAX(o.off_road_location) AS off_road_location, 
    orl.location_abbr,
    MIN(o.off_road_date) AS off_road_date,
    DATEDIFF(CURDATE(), MIN(o.off_road_date)) AS no_of_days_offroad
FROM off_road_data o
INNER JOIN location l ON o.depot = l.depot_id
LEFT JOIN makes m ON o.make = m.make
LEFT JOIN off_road_location orl ON o.off_road_location = orl.location_name
WHERE o.status = 'off_road'
    AND o.off_road_date < DATE_SUB(CURDATE(), INTERVAL 5 DAY) 
    AND NOT EXISTS (
        SELECT 1 
        FROM off_road_data sub 
        WHERE sub.bus_number = o.bus_number 
        GROUP BY sub.bus_number 
        HAVING MAX(sub.id) AND MAX(sub.off_road_location) = 'RWY'
    )
GROUP BY o.bus_number, o.make, m.make_abbr, o.emission_norms, o.depot, l.depot, orl.location_abbr
ORDER BY l.depot_id, o.off_road_date ASC;

";
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid type"]);
        exit;
    }

    if ($type == "Depot" || $type == "Division") {
        
        // Assign correct parameter value
        $id1 = ($type == "Depot") ? $name : $division_id;

        // Prepare and execute query
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $id1);
        $stmt->execute();
        $result = $stmt->get_result();
    } elseif ($type == "Corporation") {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid type"]);
        exit;
    }
    // Generate the table if data is found
    if ($result->num_rows > 0) {
        if ($type == "Depot" || $type == "Division") {

            $html = "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; border-collapse: collapse;'>
                    <tr>
                        <th>Sl. No</th>
                        <th style='width:15%;'>Depot</th>
                        <th style='width:18%;'>Vehicle No.</th>
                        <th style='width:12%;'>Make</th>
                        <th>BS</th>
                        <th>Days</th>
                        <th> @ </th>
                        <th style='width:18%;'>Offroad Date</th>
                    </tr>";
            $serial_number = 1;
            while ($row = $result->fetch_assoc()) {
                $formatted_date = date('d-m-Y', strtotime($row['off_road_date'])); // Format the date
                $html .= "<tr>
                        <td>{$serial_number}</td>
                        <td>{$row['depot_name']}</td>
                        <td>{$row['bus_number']}</td>
                        <td>{$row['make']}</td>
                        <td>{$row['emission_norms']}</td>
                        <td>{$row['no_of_days_offroad']}</td>
                        <td>{$row['off_road_location']}</td>
                        <td>{$formatted_date}</td>
                      </tr>";
                $serial_number++;
            }
            $html .= "</table>";
        } elseif ($type == "Corporation") {
            $html = "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; border-collapse: collapse;'>
        <tr>
            <th>Sl. No</th>
            <th style='width:15%;'>Depot</th>
            <th style='width:18%;'>Vehicle No.</th>
            <th style='width:12%;'>Make</th>
            <th>BS</th>
            <th>Days</th>
            <th> @ </th>
        </tr>";
            $serial_number = 1;
            while ($row = $result->fetch_assoc()) {
                $formatted_date = date('d-m-Y', strtotime($row['off_road_date'])); // Format the date
                $html .= "<tr>
            <td>{$serial_number}</td>
            <td>{$row['depot_name']}</td>
            <td>{$row['bus_number']}</td>
            <td>{$row['make_abbr']}</td>
            <td>{$row['emission_norms']}</td>
            <td>{$row['no_of_days_offroad']}</td>
            <td>{$row['location_abbr']}</td>
          </tr>";
                $serial_number++;
            }
        }
        echo json_encode(["status" => "success", "html" => $html]);
    } else {
        echo json_encode(["status" => "error", "message" => "No records found"]);
    }

    // Close the statement and connection
    $stmt->close();
    $db->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}
