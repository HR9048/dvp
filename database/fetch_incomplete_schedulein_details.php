<?php
include '../pages/session.php';
include '../includes/connection.php';
confirm_logged_in();
// Set the time zone to India/Kolkata
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduleNo = $_POST['scheduleNo'];
    $outDate = $_POST['outDate'];

    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];

    $query = "SELECT svo.id, svo.driver_token_no_1, svo.driver_token_no_2, svo.conductor_token_no, 
                     svo.driver_1_name, svo.driver_2_name, svo.conductor_name,
                     sm.sch_arr_time, sm.sch_count, svo.departed_date, svo.dep_time
              FROM sch_veh_out svo
              JOIN schedule_master sm ON svo.sch_no = sm.sch_key_no AND svo.division_id = sm.division_id AND svo.depot_id = sm.depot_id
              WHERE svo.sch_no = ? AND svo.departed_date = ? AND svo.division_id = ? AND svo.depot_id = ? AND svo.schedule_status='1'";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssss", $scheduleNo, $outDate, $division, $depot);
    $stmt->execute();
    $stmt->bind_result($id, $driverToken1, $driverToken2, $conductorToken, $driverName1, $driverName2, $conductorName, $schArrTime, $schCount, $departedDate, $departedTime);

    if ($stmt->fetch()) {
        // Fetch server's current date and time in India/Kolkata timezone
        $serverCurrentDateTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $serverCurrentDateTimeFormatted = $serverCurrentDateTime->format('Y-m-d H:i:s');

        // Check the time constraints based on sch_count
        $departedDateTime = new DateTime("$departedDate $departedTime", new DateTimeZone('Asia/Kolkata'));
        $interval = $departedDateTime->diff($serverCurrentDateTime);
        $hoursDifference = $interval->days * 24 + $interval->h + $interval->i / 60;

        echo '<input class="form-control" type="hidden" id="id" name="id" value="' . htmlspecialchars($id) . '" readonly>';

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

        // Hidden field to store scheduled arrival time
        echo '<input type="hidden" id="sch_arr_time" name="sch_arr_time" value="' . htmlspecialchars($schArrTime) . '">';
        echo '<input type="hidden" id="server_current_time" name="server_current_time" value="' . htmlspecialchars($serverCurrentDateTimeFormatted) . '">';
        echo '<input type="hidden" id="time_difference" name="time_difference">';

        echo '<div class="form-group" id="reason_field">';
        echo '<label for="reason">Reason</label>';
        echo '<select class="form-control" id="reason" name="reason" onchange="handleReasonChange()">';
        echo '<option value="" disabled selected>Select a reason</option>';
        echo '<option value="breakdown">Break Down</option>';
        echo '<option value="mechanical_issue">Mechanical Parts Issue</option>';
        echo '<option value="fuel_issue">Fuel Issue</option>';
        echo '<option value="crew_health">Crew Health Issue</option>';
        echo '<option value="other">Other</option>';
        echo '</select>';
        echo '</div>';

        // Remarks text field (hidden by default)
        echo '<div class="form-group" id="remarks_field" style="display:none;">';
        echo '<label for="remarks">Remarks</label>';
        echo '<input class="form-control" type="text" id="remarks" name="remarks" placeholder="Enter remarks for Other reason">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<button type="submit" class="btn btn-primary">Submit</button>';
        echo '</div>';
    } else {
        echo '<p>No details found for this schedule.</p>';
    }

    $stmt->close();
} else {
    header("Location: login.php");
    exit;
}
$db->close();
?>
<script>
    function handleReasonChange() {
        var reasonSelect = document.getElementById('reason');
        var remarksField = document.getElementById('remarks_field');
        var remarksInput = document.getElementById('remarks');

        if (reasonSelect.value === 'other') {
            remarksField.style.display = 'block'; // Show remarks field
            remarksInput.required = true;         // Make it required
        } else {
            remarksField.style.display = 'none';  // Hide remarks field
            remarksInput.required = false;        // Make it not required
            remarksInput.value = '';              // Clear the value if hidden
        }
    }
</script>