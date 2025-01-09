<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is expired, please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}

$division_id = $_SESSION['DIVISION_ID'];
$depot_id = $_SESSION['DEPOT_ID'];
date_default_timezone_set('Asia/Kolkata');

// Default to today's date if no date is passed in the query string
$todays_date = date("Y-m-d");

header('Content-Type: application/json');

try {
    // Escape variables to prevent SQL injection
    $division_id = $db->real_escape_string($division_id);
    $depot_id = $db->real_escape_string($depot_id);

    // Fetch data from crew_deputation table
    $sql = "SELECT EMP_PF_NUMBER AS DEP_EMP_PF_NUMBER 
            FROM crew_deputation 
            WHERE f_division_id = '$division_id' 
              AND f_depot_id = '$depot_id' 
              AND tr_date = '$todays_date' 
              AND deleted = '0' 
              AND status IN ('2', '3')";

    $result = $db->query($sql);

    if (!$result) {
        throw new Exception("Database Error [{$db->errno}] {$db->error}");
    }

    $vehSchOutData = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehSchOutData[] = $row;
        }
    }

    $db->close();

    // Return data as JSON
    echo json_encode($vehSchOutData);

} catch (Exception $e) {
    // Send error response
    echo json_encode(array('error' => $e->getMessage()));
}
?>
