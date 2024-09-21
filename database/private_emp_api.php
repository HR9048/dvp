<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header("Content-Type:application/json");
include '../includes/connection.php';
if (isset($_GET['division']) && isset(($_GET['depot']))) {
    // Prepare the SQL statement with placeholders
    $sql = "SELECT * FROM private_employee WHERE status = '1' AND Division = ? AND Depot = ?";

    // Prepare the statement
    if ($stmt = $db->prepare($sql)) {
        // Bind the parameters to the statement (assuming both `division` and `depot` are strings)
        $division = $_GET['division']; // Or use $_POST, depending on how you're passing the data
        $depot = $_GET['depot'];
        $stmt->bind_param('ss', $division, $depot); // 'ss' denotes two string
        // Execute the statement
        $stmt->execute();
        // Get the result
        $result = $stmt->get_result();
        // Check if there are results and fetch them as an associative array
        if ($result && $result->num_rows > 0) {
            $stdArr["data"] = array();
            while ($row = $result->fetch_assoc()) {
                array_push($stdArr["data"], $row);
            }
            // Output data in JSON format
            echo json_encode($stdArr);
        } else {
            echo json_encode(["message" => "No data found"]);
        }
        // Close the statement
        $stmt->close();
    } else {
        echo json_encode(["message" => "Failed to prepare the statement"]);
    }
} else {
    header('Location: ../pages/logout.php');
    exit;
}
// Close the database connection
$db->close();
?>