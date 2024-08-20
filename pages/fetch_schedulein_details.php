<?php
include 'session.php';
include '../includes/connection.php';

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
        if ($schCount == 1 && $hoursDifference > 36) {
            echo '<script>alert("The selected schedule is a 1-day schedule and the time difference is greater than 1 day 12 hours. Please contact higher authority to make the schedule in."); window.location.href="depot_schinout.php";</script>';
            exit;
        } elseif ($schCount == 2 && $serverCurrentDateTime->format('Y-m-d') == $departedDateTime->format('Y-m-d')) {
            echo '<script>alert("The route has 2 schedule counts. The departure and arrival should not be allowed on the same date."); window.location.href="depot_schinout.php";</script>';
            exit;
        } elseif ($schCount == 2 && $hoursDifference > 84) {
            echo '<script>alert("The selected schedule is a 2-day schedule and the time difference is greater than 3 days 12 hours. Please contact higher authority to make the schedule in."); window.location.href="depot_schinout.php";</script>';
            exit;
        }
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

        echo '<div class="form-group" id="reason_field" style="display:none;">';
        echo '<label for="reason">Reason</label>';
        echo '<input class="form-control" type="text" id="reason" name="reason" placeholder="Enter reason">';
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
document.addEventListener('DOMContentLoaded', function() {
    var serverCurrentTime = new Date(document.getElementById('server_current_time').value);
    var schArrTime = new Date('1970-01-01T' + document.getElementById('sch_arr_time').value + 'Z');

    document.getElementById('arr_time').addEventListener('change', function() {
        var arrTime = new Date('1970-01-01T' + this.value + 'Z');
        var diff = (arrTime - schArrTime) / (1000 * 60); // difference in minutes

        var reasonField = document.getElementById('reason_field');
        var reasonInput = document.getElementById('reason');

        if (diff > 30) {
            reasonField.style.display = 'block';
            reasonInput.setAttribute('placeholder', 'Enter reason for early arrival');
            reasonInput.setAttribute('required', 'required');
        } else if (diff < -30) {
            reasonField.style.display = 'block';
            reasonInput.setAttribute('placeholder', 'Enter reason for late arrival');
            reasonInput.setAttribute('required', 'required');
        } else {
            reasonField.style.display = 'none';
            reasonInput.removeAttribute('required');
        }
    });
});
</script>
