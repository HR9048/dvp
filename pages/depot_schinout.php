<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Session Expired please Login again.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'SECURITY') {
    // Allow access


    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['act_dep_time'])) {
        // Function to fetch data from API
        function fetchEmployeeData($pfNumber)
        {
            $url = 'http://localhost/data.php?EMP_PF_NUMBER=' . urlencode($pfNumber);
            $response = file_get_contents($url);
            if ($response === FALSE) {
                die('Error occurred while fetching data from API');
            }
            $data = json_decode($response, true);
            // Check if the data array is present and contains expected keys
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $employee) {
                    if ($employee['EMP_PF_NUMBER'] === $pfNumber) {
                        return $employee;
                    }
                }
            }
            return null;
        }

        // Escape input data
        $sch_no = mysqli_real_escape_string($db, $_POST['sch_no']);
        $vehicle_no = mysqli_real_escape_string($db, $_POST['vehicle_no']);
        $driver_token_no_1 = mysqli_real_escape_string($db, $_POST['driver_token_no_1']);
        $driver_token_no_2 = isset($_POST['driver_token_no_2']) && !empty($_POST['driver_token_no_2']) ? mysqli_real_escape_string($db, $_POST['driver_token_no_2']) : null;
        $conductor_token_no = isset($_POST['conductor_token_no']) && !empty($_POST['conductor_token_no']) ? mysqli_real_escape_string($db, $_POST['conductor_token_no']) : null;
        $act_dep_time = mysqli_real_escape_string($db, $_POST['act_dep_time']);
        $time_diff = mysqli_real_escape_string($db, $_POST['time_diff']);
        $reason_for_late_departure = isset($_POST['reason_for_late_departure']) && !empty($_POST['reason_for_late_departure']) ? mysqli_real_escape_string($db, $_POST['reason_for_late_departure']) : null;
        $reason_early_departure = isset($_POST['reason_early_departure']) && !empty($_POST['reason_early_departure']) ? mysqli_real_escape_string($db, $_POST['reason_early_departure']) : null;

        $division_id = $_SESSION['DIVISION_ID'];
        $depot_id = $_SESSION['DEPOT_ID'];

        // Check if schedule data already exists for today
        $today = date('Y-m-d');
        $checkQuery = "
        SELECT COUNT(*) as count
        FROM sch_veh_out
        WHERE sch_no = '$sch_no'
          AND division_id = '$division_id'
          AND depot_id = '$depot_id'
          AND departed_date = '$today'
    ";

        $checkResult = mysqli_query($db, $checkQuery);
        $checkData = mysqli_fetch_assoc($checkResult);

        if ($checkData['count'] > 0) {
            echo '<script>alert("Data for this schedule already exists for today.");</script>';
            echo '<script>window.location.href = "depot_schinout.php";</script>';
            exit;
        }

        // Fetch schedule details using sch_no
        $fetchScheduleDetails = "SELECT * FROM schedule_master WHERE sch_key_no = '$sch_no'  AND division_id = '$division_id' AND depot_id = '$depot_id'";
        $scheduleDetailsResult = mysqli_query($db, $fetchScheduleDetails) or die(mysqli_error($db));
        $scheduleDetails = mysqli_fetch_assoc($scheduleDetailsResult);

        $schedule_status = 1;

        // Fetch driver and conductor data from API
        $driver1Data = fetchEmployeeData($driver_token_no_1);
        $driver2Data = !is_null($driver_token_no_2) ? fetchEmployeeData($driver_token_no_2) : null;
        $conductorData = !is_null($conductor_token_no) ? fetchEmployeeData($conductor_token_no) : null;

        // Ensure the API response contains the expected keys for driver 1
        if (isset($driver1Data['EMP_PF_NUMBER'], $driver1Data['EMP_NAME'], $driver1Data['token_number'])) {
            $driver1pfno = $driver1Data['EMP_PF_NUMBER'];
            $driver1name = $driver1Data['EMP_NAME'];
            $driver1token = $driver1Data['token_number'];
        } else {
            die('Error: API response does not contain the expected keys for driver 1.');
        }

        // Check if the vehicle number is allotted
        $busAllottedStatus = ($vehicle_no == $scheduleDetails['bus_number_1'] || $vehicle_no == $scheduleDetails['bus_number_2']) ? 0 : 1;

        // Check if the driver tokens are allotted
        $driver1AllottedStatus = (
            $driver1Data['token_number'] == $scheduleDetails['driver_token_1'] ||
            $driver1Data['token_number'] == $scheduleDetails['driver_token_2'] ||
            $driver1Data['token_number'] == $scheduleDetails['driver_token_3'] ||
            $driver1Data['token_number'] == $scheduleDetails['half_releiver_token_1'] ||
            $driver1Data['token_number'] == $scheduleDetails['driver_token_4'] ||
            $driver1Data['token_number'] == $scheduleDetails['driver_token_5'] ||
            $driver1Data['token_number'] == $scheduleDetails['driver_token_6'] ||
            $driver1Data['token_number'] == $scheduleDetails['half_releiver_token_2']
        ) ? 0 : 1;

        $driver2AllottedStatus = is_null($driver2Data) ? null : (
            ($driver2Data['token_number'] == $scheduleDetails['driver_token_1'] ||
                $driver2Data['token_number'] == $scheduleDetails['driver_token_2'] ||
                $driver2Data['token_number'] == $scheduleDetails['driver_token_3'] ||
                $driver2Data['token_number'] == $scheduleDetails['half_releiver_token_1'] ||
                $driver2Data['token_number'] == $scheduleDetails['driver_token_4'] ||
                $driver2Data['token_number'] == $scheduleDetails['driver_token_5'] ||
                $driver2Data['token_number'] == $scheduleDetails['driver_token_6'] ||
                $driver2Data['token_number'] == $scheduleDetails['half_releiver_token_2']) ? 0 : 1
        );

        // Initialize conductorAllottedStatus
        $conductorAllottedStatus = null;
        $conductorpf = null;
        $conductorname = null;
        $conductortoken = null;

        if ($scheduleDetails['single_crew'] == 'yes') {
            $conductorAllottedStatus = null;
        } else {
            $conductorAllottedStatus = is_null($conductorData) ? null : (
                ($conductorData['token_number'] == $scheduleDetails['conductor_token_1'] ||
                    $conductorData['token_number'] == $scheduleDetails['conductor_token_2'] ||
                    $conductorData['token_number'] == $scheduleDetails['conductor_token_3']) ? 0 : 1
            );
            if ($conductorData) {
                $conductorpf = $conductorData['EMP_PF_NUMBER'];
                $conductorname = $conductorData['EMP_NAME'];
                $conductortoken = $conductorData['token_number'];
            }
        }

        // Set driver 2 details if present
        $driver2pfno = null;
        $driver2name = null;
        if ($driver2Data) {
            $driver2token = $driver2Data['token_number'];
            $driver2pfno = $driver2Data['EMP_PF_NUMBER'];
            $driver2name = $driver2Data['EMP_NAME'];
        }

        // Insert into schedules table
        $insertQuery = "INSERT INTO sch_veh_out (sch_no, vehicle_no, driver_token_no_1, driver_token_no_2, dep_time, dep_time_diff, reason_for_late_departure, reason_early_departure, bus_allotted_status, driver_1_allotted_status, driver_2_allotted_status, conductor_alloted_status, schedule_status, division_id, depot_id, driver_1_pf, driver_1_name, driver_2_pf, driver_2_name, conductor_token_no, conductor_pf_no, conductor_name) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($insertQuery);
        $stmt->bind_param("ssssssssiiiiiiisssssss", $sch_no, $vehicle_no, $driver1token, $driver2token, $act_dep_time, $time_diff, $reason_for_late_departure, $reason_early_departure, $busAllottedStatus, $driver1AllottedStatus, $driver2AllottedStatus, $conductorAllottedStatus, $schedule_status, $division_id, $depot_id, $driver1pfno, $driver1name, $driver2pfno, $driver2name, $conductortoken, $conductorpf, $conductorname);

        if ($stmt->execute()) {
            echo '<script>alert("The schedule has been successfully Departed.");</script>';
            echo '<script>window.location.href = "depot_schinout.php";</script>';
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['arr_time'])) {
        $id = $_POST['id'];
        $arrTime = $_POST['arr_time']; // Actual arrival time from POST data
        $schArrTime = $_POST['sch_arr_time'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        $scheduleNo = $_POST['sch_no_in'];
        $division_id1 = $_SESSION['DIVISION_ID'];
        $depot_id1 = $_SESSION['DEPOT_ID'];
        $status = '2';

        // Calculate the time difference
        $arrTimeObj = new DateTime($arrTime);
        $schArrTimeObj = new DateTime($schArrTime);
        $arrTimeDiffInMinutes = ($arrTimeObj->getTimestamp() - $schArrTimeObj->getTimestamp()) / 60;

        // Determine reason type
        $reasonForLateArr = null;
        $reasonForEarlyArr = null;
        if ($arrTimeDiffInMinutes > 60) {
            $reasonForEarlyArr = $reason;
        } elseif ($arrTimeDiffInMinutes < -30) {
            $reasonForLateArr = $reason;
        }

        date_default_timezone_set('Asia/Kolkata');
        $currentTime = date("Y-m-d H:i:s");
        $currentDate = date("Y-m-d");

        // Update the sch_veh_out table
        $updateQuery = "UPDATE sch_veh_out 
                    SET arr_time = ?, arr_date=?, act_arr_time = ?, arr_time_diff = ?, reason_for_late_arr = ?, reason_for_early_arr = ?, schedule_status = ?
                    WHERE id = ? AND sch_no = ? AND division_id = ? AND depot_id = ? AND schedule_status = '1'";

        $stmt = $db->prepare($updateQuery);

        if (!$stmt) {
            die("Error preparing statement: " . $db->error);
        }

        $stmt->bind_param("sssisssssss", $arrTime, $currentDate, $currentTime, $arrTimeDiffInMinutes, $reasonForLateArr, $reasonForEarlyArr, $status, $id, $scheduleNo, $division_id1, $depot_id1);

        if ($stmt->execute()) {
            echo '<script>alert("Schedule Status Updated successfully.");</script>';
            echo '<script>window.location.href = "depot_schinout.php";</script>';
        } else {
            echo "Error updating record: " . $stmt->error;
        }

        $stmt->close();
    }
    ?>



    <style>
        .select2-results__option[aria-disabled="true"] {
            background-color: #FFE800 !important;
        }
    </style>
    <h2 class="text-center">SECURITY MODULE</h2>
    <nav>
        <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
            <button class="nav-link active custom-size" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home"
                type="button" role="tab" aria-controls="nav-home" aria-selected="true">Vehicle Out</button>
            <button class="nav-link custom-size" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Vehicle In</button>
        </div>
    </nav>
    <div class="tab-content" id="nav-tabContent" style="width: 40%; min-width: 300px; margin: 0 auto; text-align: center;">
        <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
            <div class="container">
                <h2 class="mt-5">Depot: <?php echo $_SESSION['DEPOT']; ?></h2>
                <p style="color: red;">Schedule Vehicle Out Entry</p>
                <form method="POST" class="mt-4">
                    <div class="form-group">
                        <label for="sch_no">Schedule Key Number</label>
                        <select class="form-control select2" id="sch_no" name="sch_no" required style="width: 100%;">
                            <option value="">Select a Schedule Number</option>
                        </select>
                    </div>
                    <div id="scheduleDetails">
                        <!-- Fields will be populated here dynamically using JavaScript -->
                    </div>
                </form>
            </div>
        </div>
        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
            <div class="container">
                <h2 class="mt-5">Depot: <?php echo $_SESSION['DEPOT']; ?></h2>
                <p style="color:red;">Schedule Vehicle In entry</p>
                <form method="POST" class="mt-4">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="sch_no_in">Schedule Key Number</label>
                                <select class="form-control select2" id="sch_no_in" name="sch_no_in" required
                                    style="min-width: 100px;">
                                    <option value="">Select a Schedule Number</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="out_date">Schedule out Date</label>
                                <input class="form-control" type="date" id="out_date" name="out_date" required>
                            </div>
                        </div>
                    </div>

                    <div id="scheduleInDetails">
                        <!-- Fields will be populated here dynamically using JavaScript -->
                    </div>

                </form>
            </div>
        </div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        function fetchSchedule() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchSchedule' },
                success: function (response) {
                    var bodyBuilders = JSON.parse(response);
                    $.each(bodyBuilders, function (index, value) {
                        $('#sch_no').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }
        $(document).ready(function () {
            fetchSchedule();
        });
        $(document).ready(function () {
            $('#sch_no').select2({
                placeholder: 'Select a schedule Key number',
                allowClear: true
            });
            $('#sch_no').on('change', function () {
                var schNo = $(this).val();
                if (schNo) {
                    $.ajax({
                        type: 'POST',
                        url: 'fetch_schedule_details.php',
                        data: { sch_no: schNo },
                        success: function (response) {
                            var details = JSON.parse(response);
                            populateScheduleDetails(details);
                        }
                    });
                } else {
                    $('#scheduleDetails').empty();
                }
            });
            function populateScheduleDetails(details) {
                var scheduleDetailsDiv = document.getElementById('scheduleDetails');
                scheduleDetailsDiv.innerHTML = '';

                fetchBuses().then(buses => {
                    fetchAdditionalData().then(additionalData => {
                        if (details) {
                            var vehicleNoOptions = '<option value="">Select Vehicle No</option>';
                            if (details.bus_number_1) vehicleNoOptions += '<option value="' + details.bus_number_1 + '">' + details.bus_number_1 + ' (allotted)</option>';
                            if (details.bus_number_2) vehicleNoOptions += '<option value="' + details.bus_number_2 + '">' + details.bus_number_2 + ' (allotted)</option>';
                            if (details.additional_bus_number) vehicleNoOptions += '<option value="' + details.additional_bus_number + '">' + details.additional_bus_number + ' (Additional allotted)</option>';

                            buses = buses.filter(function (bus) {
                                return bus !== details.bus_number_1 && bus !== details.bus_number_2 && bus !== details.additional_bus_number;
                            });
                            buses.forEach(function (bus) {
                                vehicleNoOptions += `<option value="${bus}">${bus}</option>`;
                            });

                            var driverTokenOptions1 = '<option value="">Select Driver Token No 1</option>';
                            var driverTokenOptions2 = '<option value="">Select Driver Token No 2</option>';
                            var conductorTokenOptions = '<option value="">Select Conductor Token No</option>';

                            if (details.driver_token_1) driverTokenOptions1 += '<option value="' + details.driver_pf_1 + '">' + details.driver_token_1 + ' - ' + details.driver_name_1 + ' (allotted)</option>';
                            if (details.driver_token_2) driverTokenOptions1 += '<option value="' + details.driver_pf_2 + '">' + details.driver_token_2 + ' - ' + details.driver_name_2 + ' (allotted)</option>';
                            if (details.driver_token_3) driverTokenOptions1 += '<option value="' + details.driver_pf_3 + '">' + details.driver_token_3 + ' - ' + details.driver_name_3 + ' (allotted)</option>';
                            if (details.driver_token_4) driverTokenOptions1 += '<option value="' + details.driver_pf_4 + '">' + details.driver_token_4 + ' - ' + details.driver_name_4 + ' (allotted)</option>';
                            if (details.driver_token_5) driverTokenOptions1 += '<option value="' + details.driver_pf_5 + '">' + details.driver_token_5 + ' - ' + details.driver_name_5 + ' (allotted)</option>';
                            if (details.driver_token_6) driverTokenOptions1 += '<option value="' + details.driver_pf_6 + '">' + details.driver_token_6 + ' - ' + details.driver_name_6 + ' (allotted)</option>';
                            if (details.half_releiver_token_1) driverTokenOptions1 += '<option value="' + details.half_releiver_token_1 + '">' + details.half_releiver_token_1 + ' - ' + details.half_releiver_name_1 + ' (allotted off releiver)</option>';
                            if (details.half_releiver_token_2) driverTokenOptions1 += '<option value="' + details.half_releiver_token_2 + '">' + details.half_releiver_token_2 + ' - ' + details.half_releiver_name_2 + ' (allotted off releiver)</option>';

                            if (['2', '3', '4'].includes(details.service_type_id)) {
                                if (details.driver_token_1) driverTokenOptions2 += '<option value="' + details.driver_pf_1 + '">' + details.driver_token_1 + ' - ' + details.driver_name_1 + ' (allotted)</option>';
                                if (details.driver_token_2) driverTokenOptions2 += '<option value="' + details.driver_pf_2 + '">' + details.driver_token_2 + ' - ' + details.driver_name_2 + ' (allotted)</option>';
                                if (details.driver_token_3) driverTokenOptions2 += '<option value="' + details.driver_pf_3 + '">' + details.driver_token_3 + ' - ' + details.driver_name_3 + ' (allotted)</option>';
                                if (details.driver_token_4) driverTokenOptions2 += '<option value="' + details.driver_pf_4 + '">' + details.driver_token_4 + ' - ' + details.driver_name_4 + ' (allotted)</option>';
                                if (details.driver_token_5) driverTokenOptions2 += '<option value="' + details.driver_pf_5 + '">' + details.driver_token_5 + ' - ' + details.driver_name_5 + ' (allotted)</option>';
                                if (details.driver_token_6) driverTokenOptions2 += '<option value="' + details.driver_pf_6 + '">' + details.driver_token_6 + ' - ' + details.driver_name_6 + ' (allotted)</option>';
                                if (details.half_releiver_token_1) driverTokenOptions2 += '<option value="' + details.half_releiver_token_1 + '">' + details.half_releiver_token_1 + ' - ' + details.half_releiver_name_1 + ' (allotted off releiver)</option>';
                                if (details.half_releiver_token_2) driverTokenOptions2 += '<option value="' + details.half_releiver_token_2 + '">' + details.half_releiver_token_2 + ' - ' + details.half_releiver_name_2 + ' (allotted off releiver)</option>';
                            } else {
                                driverTokenOptions2 = ''; // If no valid tokens, clear the options
                            }

                            if (details.single_crew === 'no') {
                                if (details.conductor_token_1) conductorTokenOptions += '<option value="' + details.conductor_pf_1 + '">' + details.conductor_token_1 + ' - ' + details.conductor_name_1 + ' (allotted)</option>';
                                if (details.conductor_token_2) conductorTokenOptions += '<option value="' + details.conductor_pf_2 + '">' + details.conductor_token_2 + ' - ' + details.conductor_name_2 + ' (allotted)</option>';
                                if (details.conductor_token_3) conductorTokenOptions += '<option value="' + details.conductor_pf_3 + '">' + details.conductor_token_3 + ' - ' + details.conductor_name_3 + ' (allotted)</option>';
                            } else {
                                conductorTokenOptions = '';
                            }

                            // Separate filtering for drivers and conductors
                            var driverData = additionalData.filter(function (employee) {
                                return employee.EMP_DESGN_AT_APPOINTMENT === 'DRIVER' || employee.EMP_DESGN_AT_APPOINTMENT === 'DRIVER-CUM-CONDUCTOR' &&
                                    ![details.driver_token_1, details.driver_token_2, details.driver_token_3, details.driver_token_4, details.driver_token_5, details.driver_token_6, details.half_releiver_token_1, details.half_releiver_token_2].includes(employee.token_number);
                            });

                            driverData.forEach(function (driver) {
                                driverTokenOptions1 += `<option value="${driver.EMP_PF_NUMBER}">${driver.token_number} - ${driver.EMP_NAME}</option>`;
                                if (['2', '3', '4'].includes(details.service_type_id)) {
                                    driverTokenOptions2 += `<option value="${driver.EMP_PF_NUMBER}">${driver.token_number} - ${driver.EMP_NAME}</option>`;
                                } else {
                                    driverTokenOptions2 = ''; // If no valid tokens, clear the options
                                }
                            });

                            var conductorData = additionalData.filter(function (employee) {
                                return employee.EMP_DESGN_AT_APPOINTMENT === 'CONDUCTOR' || employee.EMP_DESGN_AT_APPOINTMENT === 'DRIVER-CUM-CONDUCTOR' &&
                                    ![details.conductor_token_1, details.conductor_token_2, details.conductor_token_3].includes(employee.token_number);
                            });

                            conductorData.forEach(function (conductor) {
                                conductorTokenOptions += `<option value="${conductor.EMP_PF_NUMBER}">${conductor.token_number} - ${conductor.EMP_NAME}</option>`;
                            });

                            var schDepTime = details.sch_dep_time || '';
                            scheduleDetailsDiv.innerHTML = `
                                                                <div class="form-group">
                                                                    <label for="vehicle_no">Vehicle No</label>
                                                                    <select class="form-control select2" id="vehicle_no" name="vehicle_no" required style="width: 100%;">
                                                                        ${vehicleNoOptions}
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="driver_token_no_1">Driver Token No 1</label>
                                                                    <select class="form-control select2" id="driver_token_no_1" name="driver_token_no_1" required style="width: 100%;">
                                                                        ${driverTokenOptions1}
                                                                    </select>
                                                                </div>
                                                                ${driverTokenOptions2 ? `
                                                                    <div class="form-group">
                                                                        <label for="driver_token_no_2">Driver Token No 2</label>
                                                                        <select class="form-control select2" id="driver_token_no_2" name="driver_token_no_2" style="width: 100%;">
                                                                            ${driverTokenOptions2}
                                                                        </select>
                                                                    </div>
                                                                ` : ''}
                                                                ${details.single_crew === 'no' ? `
                                                                <div class="form-group">
                                                                    <label for="conductor_token_no">Conductor Token No</label>
                                                                    <select class="form-control select2" id="conductor_token_no" name="conductor_token_no" style="width: 100%;">
                                                                        ${conductorTokenOptions}
                                                                    </select>
                                                                </div>
                                                            ` : ''}
                                                                <div class="row">
                                                                    <div class="col">
                                                                        <div class="form-group">
                                                                            <label for="sch_dep_time">Sch Departure time</label>
                                                                            <input type="time" class="form-control" id="sch_dep_time" name="sch_dep_time" value="${schDepTime}" required readonly>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col">
                                                                        <div class="form-group">
                                                                            <label for="act_dep_time">Act Departure time</label>
                                                                            <input type="time" class="form-control" id="act_dep_time" name="act_dep_time" value="" required>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group" style="display: none;">
                                                                    <label for="time_diff">Time Difference (minutes)</label>
                                                                    <input type="text" class="form-control" id="time_diff" name="time_diff" readonly>
                                                                </div>
                                                                <div class="form-group" style="display: none;">
                                                                    <label for="reason_for_late_departure">Reason for Late Departure:</label>
                                                                    <textarea class="form-control" id="reason_for_late_departure" name="reason_for_late_departure"></textarea>
                                                                </div>
                                                                <div class="form-group" style="display: none;">
                                                                    <label for="reason_early_departure">Reason for Early Departure:</label>
                                                                    <textarea class="form-control" id="reason_early_departure" name="reason_early_departure"></textarea>
                                                                </div>
                                                                <div class="form-group">
                                                                    <button type="submit" class="btn btn-primary">Submit</button>
                                                                </div>
                                                            `;

                            $('.select2').select2();
                            $(document).on('change', '#act_dep_time', function () {
                                calculateTimeDifference();
                            });

                            // updateCurrentTime();
                            // calculateTimeDifference();

                            $('#driver_token_no_1').on('change', function () {
                                updateDriverTokenOptions();
                            });

                            $('#driver_token_no_2').on('change', function () {
                                updateDriverTokenOptions();
                            });
                            $('#conductor_token_no').on('change', function () {
                                updateDriverTokenOptions();
                            });
                            updateDriverTokenOptions();

                        } else {
                            scheduleDetailsDiv.innerHTML = 'No details found for this schedule number.';
                        }
                    });
                });
            }

            function calculateTimeDifference() {
                var schDepTime = document.getElementById('sch_dep_time').value;
                var actDepTime = document.getElementById('act_dep_time').value;

                if (schDepTime && actDepTime) {
                    var schDepDate = new Date(`1970-01-01T${schDepTime}:00`);
                    var actDepDate = new Date(`1970-01-01T${actDepTime}:00`);

                    var timeDiff = (actDepDate - schDepDate) / 60000; // Convert milliseconds to minutes

                    document.getElementById('time_diff').value = timeDiff;
                    if (timeDiff > 30) {
                        document.getElementById('reason_for_late_departure').parentElement.style.display = 'block';
                        document.getElementById('reason_early_departure').parentElement.style.display = 'none';
                    } else if (timeDiff < -60) {
                        document.getElementById('reason_early_departure').parentElement.style.display = 'block';
                        document.getElementById('reason_for_late_departure').parentElement.style.display = 'none';
                    } else {
                        document.getElementById('reason_for_late_departure').parentElement.style.display = 'none';
                        document.getElementById('reason_early_departure').parentElement.style.display = 'none';
                    }
                }
            }
            function updateDriverTokenOptions() {
                var driverToken1 = $('#driver_token_no_1').val();
                var driverToken2 = $('#driver_token_no_2').val();
                var conductorTokenNo = $('#conductor_token_no').val();

                // Enable all options initially
                $('#driver_token_no_1 option, #driver_token_no_2 option, #conductor_token_no option').prop('disabled', false);

                // Check for duplicate selections and alert user
                if (driverToken1 && driverToken1 === driverToken2) {
                    $('#driver_token_no_2').val('').trigger('change.select2');
                    alert('Please select different token numbers for Driver Token No 1 and Driver Token No 2.');
                }
                if (driverToken1 && driverToken1 === conductorTokenNo) {
                    $('#conductor_token_no').val('').trigger('change.select2');
                    alert('Please select different token numbers for Driver Token No 1 and Conductor Token No.');
                }
                if (driverToken2 && driverToken2 === conductorTokenNo) {
                    $('#conductor_token_no').val('').trigger('change.select2');
                    alert('Please select different token numbers for Driver Token No 2 and Conductor Token No.');
                }

                // Disable selected options in the opposite select boxes
                if (driverToken1) {
                    $('#driver_token_no_2 option[value="' + driverToken1 + '"]').prop('disabled', true);
                    $('#conductor_token_no option[value="' + driverToken1 + '"]').prop('disabled', true);
                }
                if (driverToken2) {
                    $('#driver_token_no_1 option[value="' + driverToken2 + '"]').prop('disabled', true);
                    $('#conductor_token_no option[value="' + driverToken2 + '"]').prop('disabled', true);
                }
                if (conductorTokenNo) {
                    $('#driver_token_no_1 option[value="' + conductorTokenNo + '"]').prop('disabled', true);
                    $('#driver_token_no_2 option[value="' + conductorTokenNo + '"]').prop('disabled', true);
                }

                // Refresh Select2 elements
                $('#driver_token_no_1, #driver_token_no_2, #conductor_token_no').trigger('change.select2');
            }



            function fetchBuses() {
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'fetch_buses.php', true);
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            var busesData = JSON.parse(xhr.responseText);
                            resolve(busesData);
                        } else if (xhr.readyState === 4) {
                            reject('Error fetching buses data');
                        }
                    };
                    xhr.send();
                });
            }

            function fetchAdditionalData() {
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'http://localhost/data.php', true);
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            var data = JSON.parse(xhr.responseText).data;
                            var filteredData = data.filter(function (item) {
                                return item.Division === '<?php echo $_SESSION['KMPL_DIVISION']; ?>' && item.Depot === '<?php echo $_SESSION['KMPL_DEPOT']; ?>';
                            });
                            filteredData.sort(function (a, b) {
                                return a.token_number - b.token_number;
                            });
                            fetchVechSchOutData().then(function (vehSchOutData) {
                                filteredData = filteredData.filter(function (item) {
                                    return !vehSchOutData.some(function (vehItem) {
                                        return vehItem.driver_1_pf === item.EMP_PF_NUMBER || vehItem.driver_2_pf === item.EMP_PF_NUMBER || vehItem.conductor_pf_no === item.EMP_PF_NUMBER;
                                    });
                                });
                                resolve(filteredData);
                            }).catch(function (error) {
                                reject(error);
                            });
                        } else if (xhr.readyState === 4) {
                            reject('Error fetching additional data');
                        }
                    };
                    xhr.send();
                });
            }
            function fetchVechSchOutData() {
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'fetch_veh_sch_out.php', true);
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            var data = JSON.parse(xhr.responseText);
                            resolve(data);
                        } else if (xhr.readyState === 4) {
                            reject('Error fetching VEH_SCH_OUT data');
                        }
                    };
                    xhr.send();
                });
            }
        });
    </script>

    <!-- Schedule In script -->
    <script>
        function fetchScheduleIn() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchScheduleIn' },
                success: function (response) {
                    var bodyBuilders = JSON.parse(response);
                    $.each(bodyBuilders, function (index, value) {
                        $('#sch_no_in').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }
        $(document).ready(function () {
            fetchScheduleIn();
        });
        $(document).ready(function () {
            function fetchScheduleDetails() {
                var scheduleNo = $('#sch_no_in').val();
                var outDate = $('#out_date').val();

                if (scheduleNo && outDate) {
                    $.ajax({
                        url: 'fetch_schedulein_details.php',
                        type: 'POST',
                        data: { scheduleNo: scheduleNo, outDate: outDate },
                        success: function (response) {
                            $('#scheduleInDetails').html(response);
                        },
                        error: function (xhr, status, error) {
                            console.error('Error fetching schedule details:', error);
                        }
                    });
                }
            }

            $('#sch_no_in, #out_date').change(fetchScheduleDetails);
        });
        function handleArrivalTimeChange() {
            var serverCurrentTime = new Date(document.getElementById('server_current_time').value);
            var schArrTime = new Date('1970-01-01T' + document.getElementById('sch_arr_time').value + 'Z');

            var arrTime = new Date('1970-01-01T' + document.getElementById('arr_time').value);
            var diff = (schArrTime - arrTime) / (1000 * 60); // difference in minutes

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
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('arr_time').addEventListener('change', handleArrivalTimeChange);
        });

    </script>

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>