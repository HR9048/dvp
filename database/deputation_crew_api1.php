<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header("Content-Type:application/json");
include '../includes/connection.php';
include '../pages/session.php';

if (isset($_GET['division']) && isset($_GET['depot'])) {
    date_default_timezone_set('Asia/Kolkata');

    // Default to today's date if no date is passed in the query string
    $todays_date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");

    // Prepare the SQL statement with placeholders
    $sql = "SELECT `EMP_PF_NUMBER`, `EMP_NAME` AS `EMP_NAME`, `token_number`, `EMP_DESGN_AT_APPOINTMENT`, `t_Division` as `Division`, `t_Depot` as `Depot` 
            FROM `crew_deputation` 
            WHERE `tr_date` = ? 
            AND `t_Division` = ? 
            AND `t_Depot` = ? 
            AND deleted = '0' 
            AND status = '2'";

    // Prepare the statement
    if ($stmt = $db->prepare($sql)) {
        // Bind the parameters to the statement
        $division = $_GET['division'];
        $depot = $_GET['depot'];

        $stmt->bind_param('sss', $todays_date, $division, $depot); // 'sss' denotes three strings
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
