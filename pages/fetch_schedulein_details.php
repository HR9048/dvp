
<?php
include 'session.php'; // Include your session management
include '../includes/connection.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduleNo = $_POST['scheduleNo'];
    $outDate = $_POST['outDate'];

    // Assuming that division and depot are stored in the session
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];

    // Query to get driver and conductor token numbers and names
    $query = "SELECT driver_token_no_1, driver_token_no_2, conductor_token_no, 
                     driver_1_name, driver_2_name, conductor_name 
              FROM sch_veh_out 
              WHERE sch_no = ? AND departed_date = ? AND division_id = ? AND depot_id = ? AND schedule_status='1'";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssss", $scheduleNo, $outDate, $division, $depot);
    $stmt->execute();
    $stmt->bind_result($driverToken1, $driverToken2, $conductorToken, $driverName1, $driverName2, $conductorName);

    if ($stmt->fetch()) {
        // Output the driver and conductor details
        echo '<div class="row">';
        echo '<div class="col-md-6 col-sm-12">';
        echo '<div class="form-group">';
        echo '<label for="driver1">Driver 1</label>';
        echo '<input class="form-control" type="text" id="driver1" name="driver1" value="' . htmlspecialchars($driverToken1) . ' (' . htmlspecialchars($driverName1) . ')" readonly>';
        echo '</div>';
        echo '</div>';

        if (!empty($driverToken2)) {
            echo '<div class="col-md-6 col-sm-12">';
            echo '<div class="form-group">';
            echo '<label for="driver2">Driver 2</label>';
            echo '<input class="form-control" type="text" id="driver2" name="driver2" value="' . htmlspecialchars($driverToken2) . ' (' . htmlspecialchars($driverName2) . ')" readonly>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>'; // Close row

        echo '<div class="row">';
        if (!empty($conductorToken)) {
            echo '<div class="col-md-6 col-sm-12">';
            echo '<div class="form-group">';
            echo '<label for="conductor">Conductor</label>';
            echo '<input class="form-control" type="text" id="conductor" name="conductor" value="' . htmlspecialchars($conductorToken) . ' (' . htmlspecialchars($conductorName) . ')" readonly>';
            echo '</div>';
            echo '</div>';
        }

        echo '<div class="col-md-6 col-sm-12">';
        echo '<div class="form-group">';
        echo '<label for="arr_time">Arrival Time</label>';
        echo '<input class="form-control" type="time" id="arr_time" name="arr_time" required>';
        echo '</div>';
        echo '</div>';
        echo '</div>'; // Close row
    } else {
        echo '<p>No details found for this schedule.</p>';
    }

    $stmt->close();
}
$db->close();
?>