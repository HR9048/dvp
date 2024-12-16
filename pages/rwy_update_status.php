<?php
include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
// Check if USERNAME session variable is set
if (!isset($_SESSION['USERNAME'])) {
    ?>
    <script>
        alert("Username not found. Please login again.");
        window.location = "logout.php";
    </script>
    <?php
    exit(); // Stop execution if username is not set
}
if (empty($_POST)) {
    // If accessed directly without POST data, redirect to login.php
    header("Location: login.php");
    exit;
}
// Get form data
$busNumber = $_POST['busNumberInput'];
$division = $_POST['divisionIDInput'];
$depot = $_POST['depotIDInput'];
$make = $_POST['makeInput'];
$emissionNorms = $_POST['emissionNormsInput'];
$receivedDate = date('Y-m-d', strtotime($_POST['receiveDateInput'])); // Convert received date to correct format
$workReason = $_POST['workreasonInput'];
$workStatus = $_POST['workStatus'];
$remarks = $_POST['remarks'];
$id = $_POST['id'];

// Check if work status or remarks is empty
if (empty($workStatus) || empty($remarks)) {
    echo "error";
    exit(); // Exit if any field is empty
}

// Calculate no_of_days
$offRoadDate = new DateTime($receivedDate);
$today = date('Y-m-d H:i:s', strtotime('+5 hours 30 minutes')); // Get current date and time in IST
$daysOffRoad = $offRoadDate->diff(new DateTime($today))->days;

// Prepare and execute SQL statement to check if the provided ID exists
$sql_check_id = "SELECT COUNT(*) AS count FROM rwy_offroad WHERE id = ?";
$stmt_check_id = mysqli_prepare($db, $sql_check_id);
mysqli_stmt_bind_param($stmt_check_id, "i", $id);
mysqli_stmt_execute($stmt_check_id);
$result_check_id = mysqli_stmt_get_result($stmt_check_id);
$row_count = mysqli_fetch_assoc($result_check_id)['count'];
mysqli_stmt_close($stmt_check_id);

if ($row_count > 0) {
    // Check if work status and remarks are NULL
    $sql_check_status_remarks = "SELECT COUNT(*) AS count FROM rwy_offroad WHERE id = ? AND (work_status IS NULL OR remarks IS NULL)";
    $stmt_check_status_remarks = mysqli_prepare($db, $sql_check_status_remarks);
    mysqli_stmt_bind_param($stmt_check_status_remarks, "i", $id);
    mysqli_stmt_execute($stmt_check_status_remarks);
    $result_check_status_remarks = mysqli_stmt_get_result($stmt_check_status_remarks);
    $row_count_status_remarks = mysqli_fetch_assoc($result_check_status_remarks)['count'];
    mysqli_stmt_close($stmt_check_status_remarks);

    if ($row_count_status_remarks > 0) {
        // Update existing record
        $sql_update = "UPDATE rwy_offroad SET work_status = ?, remarks = ?, last_updated_datetime = CONVERT_TZ(NOW(), @@session.time_zone, '+05:30') WHERE id = ?";
        $stmt_update = mysqli_prepare($db, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "sss", $workStatus, $remarks,  $id);
        mysqli_stmt_execute($stmt_update);

        if (mysqli_stmt_affected_rows($stmt_update) > 0) {
            // Data updated successfully
            echo "success";
        } else {
            // Error occurred
            echo "error";
        }

        mysqli_stmt_close($stmt_update);
    } else {
        $today = date('Y-m-d H:i:s', strtotime('+5 hours 30 minutes')); // Get current date and time in IST
        $sql_insert = "INSERT INTO rwy_offroad (division, depot, bus_number, make, emission_norms, received_date, work_reason, work_status, status, remarks, username, last_updated_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'off_road', ?, ?, CONVERT_TZ(NOW(), @@session.time_zone, '+05:30'))";
        $stmt_insert = mysqli_prepare($db, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "ssssssssss", $division, $depot, $busNumber, $make, $emissionNorms, $today, $workReason, $workStatus, $remarks, $_SESSION['USERNAME']);
        mysqli_stmt_execute($stmt_insert);

        if (mysqli_stmt_affected_rows($stmt_insert) > 0) {
            // Update no_of_days for previous id
            $sql_update_days = "UPDATE rwy_offroad SET no_of_days = DATEDIFF(CURDATE(), received_date) WHERE id = ?";
            $stmt_update_days = mysqli_prepare($db, $sql_update_days);
            mysqli_stmt_bind_param($stmt_update_days, "i", $id);
            mysqli_stmt_execute($stmt_update_days);
            mysqli_stmt_close($stmt_update_days);

            // Data inserted successfully
            echo "success";
        } else {
            // Error occurred
            echo "error";
        }

        mysqli_stmt_close($stmt_insert);
    }
} else {
    echo "error"; // ID not found
}

// Close connection
mysqli_close($db);
?>
