<?php
// Include your database connection file
include_once '../includes/connection.php';
include_once 'session.php';

// Check if the request is made via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if 'busNumber' parameter is set
    if (isset($_POST['busNumber'])) {
        $busNumber = $_POST['busNumber'];

        // Query to fetch data based on bus number
        $query = "SELECT br.*, l1.division, l1.division_id AS division_id, l2.depot AS depot_name, l2.depot_id AS depot_id
                  FROM bus_registration br
                  INNER JOIN location l1 ON br.division_name = l1.division_id
                  INNER JOIN location l2 ON br.depot_name = l2.depot_id
                  WHERE bus_number = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $busNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch data as associative array
            $row = $result->fetch_assoc();

            // Check if division and depot names match session variables
            if ($_SESSION['DIVISION_ID'] != $row['division_name']) {
                // Return specific error message for division and depot mismatch
                http_response_code(403); // Forbidden
                echo json_encode(array('error' => "The bus does not belong to division: {$_SESSION['DIVISION']} "));

            } else {
                // Query to check if bus is already marked as off-road
                $offRoadQuery = "SELECT status FROM off_road_data WHERE bus_number = ? ORDER BY id DESC LIMIT 1";
                $stmt = $db->prepare($offRoadQuery);
                $stmt->bind_param("s", $busNumber);
                $stmt->execute();
                $offRoadResult = $stmt->get_result();

                if ($offRoadResult->num_rows > 0) {
                    $offRoadRow = $offRoadResult->fetch_assoc();
                    if ($offRoadRow['status'] == 'off_road') {
                        // Return specific error message if bus is already marked as off-road
                        http_response_code(403); // Forbidden
                        echo json_encode(array('error' => 'The bus is marked as off-road.'));
                        exit; // Terminate further execution
                    }
                }

                // Include division name, division ID, depot name, and depot ID in the response
                $row['divisionID'] = $row['division_id'];
                $row['division_name'] = $row['division'];
                $row['depotID'] = $row['depot_id'];
                $row['depotNAME'] = $row['depot_name'];
                
                // Remove the redundant fields
                unset($row['division']);
                unset($row['depot']);

                // Return the fetched data as JSON response
                header('Content-Type: application/json');
                echo json_encode($row);
            }
        } else {
            http_response_code(404); // Not Found
            echo json_encode(array('error' => 'Bus data not found'));
        }
    } else {
        // Handle error if 'busNumber' parameter is missing
        http_response_code(400); // Bad Request
        echo json_encode(array('error' => 'Bus number parameter is missing'));
    }
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}
?>
