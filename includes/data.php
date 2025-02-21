<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header("Content-Type:application/json");

$servername = "localhost"; 
$username = "root"; 
$password = "kkrtcsystem";
$port = 33306; // MySQL custom port
$database = "lms2021"; 
// Create connection using the custom MySQL port
$db = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Check if 'division' and 'depot' values are set
if (isset($_GET['division']) && isset($_GET['depot'])) {
    $division = $_GET['division'];
    $depot = $_GET['depot'];

    // Prepare the SQL statement
    $sql = "SELECT * from employee where `Division` = ? AND `Depot` = ?";
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('ss', $division, $depot);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $stdArr["data"] = array();
            while ($row = $result->fetch_assoc()) {
                array_push($stdArr["data"], $row);
            }
            echo json_encode($stdArr);
        } else {
            echo json_encode(["message" => "No data found"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["message" => "Failed to prepare the statement"]);
    }
} else {
    // Instead of redirecting, return a JSON response
    echo json_encode(["message" => "Authentication required. Please log in."]);
}

// Close the database connection
$db->close();
?>