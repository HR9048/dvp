<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check user type and redirect if necessary
$query = 'SELECT ID, t.TYPE FROM users u JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];
    if ($Aa == 'DIVISION') {
        echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Division Page'); window.location = 'division.php';</script>";
    } elseif ($Aa == 'HEAD-OFFICE') {
        echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Head Office Page'); window.location = 'index.php';</script>";
    } elseif ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Bunk') {
        echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Mech Page'); window.location = '../includes/depot_verify.php';</script>";
    }
}

?>
<style>
    .select2-results__option[aria-disabled="true"] {
        background-color: #FFE800 !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="container" style="width:40%; min-width:300px">
    <h2 class="mt-5">Depot: <?php echo $_SESSION['DEPOT']; ?></h2>
    <form action="submit_late_arrival.php" method="POST" class="mt-4">
        <div class="form-group">
            <label for="sch_no">Schedule Numner</label>
            <input type="text" class="form-control" id="sch_no" name="sch_no" required
                oninput="this.value = this.value.toUpperCase()">
        </div>
        <div id="scheduleDetails">
            <!-- Fields will be populated here dynamically using JavaScript -->
        </div>

    </form>
</div>

<script>
    $(document).ready(function () {
        document.getElementById('sch_no').addEventListener('input', function () {
            var schNo = this.value;
            if (schNo) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'fetch_schedule_details.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        populateScheduleDetails(response);
                    }
                };
                xhr.send('sch_no=' + schNo);
            }
        });

        function populateScheduleDetails(details) {
            var scheduleDetailsDiv = document.getElementById('scheduleDetails');
            scheduleDetailsDiv.innerHTML = '';

            fetchBuses().then(buses => {
                fetchAdditionalData().then(additionalData => {
                    if (details) {
                        // Existing code to populate vehicleNoOptions and driverTokenOptions...

                        var vehicleNoOptions = '<option value="">Select Vehicle No</option>';
                        if (details.bus_number_1) vehicleNoOptions += '<option value="' + details.bus_number_1 + '">' + details.bus_number_1 + ' (allotted)</option>';
                        if (details.bus_number_2) vehicleNoOptions += '<option value="' + details.bus_number_2 + '">' + details.bus_number_2 + ' (allotted)</option>';

                        buses = buses.filter(function (bus) {
                            return bus !== details.bus_number_1 && bus !== details.bus_number_2;
                        });
                        buses.forEach(function (bus) {
                            vehicleNoOptions += `<option value="${bus}">${bus}</option>`;
                        });

                        var driverTokenOptions1 = '<option value="">Select Driver Token No 1</option>';
                        var driverTokenOptions2 = '<option value="">Select Driver Token No 2</option>';

                        if (details.driver_token_1_1) driverTokenOptions1 += '<option value="' + details.driver_token_1_1 + '">' + details.driver_token_1_1 + ' - ' + details.driver_name_1_1 + ' (allotted)</option>';
                        if (details.driver_token_1_2) driverTokenOptions1 += '<option value="' + details.driver_token_1_2 + '">' + details.driver_token_1_2 + ' - ' + details.driver_name_1_2 + ' (allotted)</option>';
                        if (details.driver_token_1_3) driverTokenOptions1 += '<option value="' + details.driver_token_1_3 + '">' + details.driver_token_1_3 + ' - ' + details.driver_name_1_3 + ' (allotted)</option>';
                        if (details.driver_token_2_1) driverTokenOptions1 += '<option value="' + details.driver_token_2_1 + '">' + details.driver_token_2_1 + ' - ' + details.driver_name_2_1 + ' (allotted)</option>';
                        if (details.driver_token_2_2) driverTokenOptions1 += '<option value="' + details.driver_token_2_2 + '">' + details.driver_token_2_2 + ' - ' + details.driver_name_2_2 + ' (allotted)</option>';
                        if (details.driver_token_2_3) driverTokenOptions1 += '<option value="' + details.driver_token_2_3 + '">' + details.driver_token_2_3 + ' - ' + details.driver_name_2_3 + ' (allotted)</option>';
                        if (details.half_releiver_token_1) driverTokenOptions1 += '<option value="' + details.half_releiver_token_1 + '">' + details.half_releiver_token_1 + ' - ' + details.half_releiver_name_1 + ' (allotted off releiver)</option>';
                        if (details.half_releiver_token_2) driverTokenOptions1 += '<option value="' + details.half_releiver_token_2 + '">' + details.half_releiver_token_2 + ' - ' + details.half_releiver_name_2 + ' (allotted off releiver)</option>';

                        if (details.driver_token_1_2 || details.driver_token_1_3 || details.driver_token_2_2 || details.driver_token_2_3) {
                            if (details.driver_token_1_1) driverTokenOptions2 += '<option value="' + details.driver_token_1_1 + '">' + details.driver_token_1_1 + ' - ' + details.driver_name_1_1 + ' (allotted)</option>';
                            if (details.driver_token_1_2) driverTokenOptions2 += '<option value="' + details.driver_token_1_2 + '">' + details.driver_token_1_2 + ' - ' + details.driver_name_1_2 + ' (allotted)</option>';
                            if (details.driver_token_1_3) driverTokenOptions2 += '<option value="' + details.driver_token_1_3 + '">' + details.driver_token_1_3 + ' - ' + details.driver_name_1_3 + ' (allotted)</option>';
                            if (details.driver_token_2_1) driverTokenOptions2 += '<option value="' + details.driver_token_2_1 + '">' + details.driver_token_2_1 + ' - ' + details.driver_name_2_1 + ' (allotted)</option>';
                            if (details.driver_token_2_2) driverTokenOptions2 += '<option value="' + details.driver_token_2_2 + '">' + details.driver_token_2_2 + ' - ' + details.driver_name_2_2 + ' (allotted)</option>';
                            if (details.driver_token_2_3) driverTokenOptions2 += '<option value="' + details.driver_token_2_3 + '">' + details.driver_token_2_3 + ' - ' + details.driver_name_2_3 + ' (allotted)</option>';
                            if (details.half_releiver_token_1) driverTokenOptions2 += '<option value="' + details.half_releiver_token_1 + '">' + details.half_releiver_token_1 + ' - ' + details.half_releiver_name_1 + ' (allotted off releiver)</option>';
                            if (details.half_releiver_token_2) driverTokenOptions2 += '<option value="' + details.half_releiver_token_2 + '">' + details.half_releiver_token_2 + ' - ' + details.half_releiver_name_2 + ' (allotted off releiver)</option>';
                        } else {
                            driverTokenOptions2 = ''; // If no valid tokens, clear the options
                        }

                        additionalData = additionalData.filter(function (driver) {
                            return driver.token_number !== details.driver_token_1_1 && driver.token_number !== details.driver_token_1_2 &&
                                driver.token_number !== details.driver_token_1_3 && driver.token_number !== details.driver_token_2_1 &&
                                driver.token_number !== details.driver_token_2_2 && driver.token_number !== details.driver_token_2_3 &&
                                driver.token_number !== details.half_releiver_token_1 && driver.token_number !== details.half_releiver_token_2;
                        });
                        additionalData.forEach(function (driver) {
                            driverTokenOptions1 += `<option value="${driver.token_number}">${driver.token_number} - ${driver.EMP_NAME}</option>`;
                            if (details.driver_token_1_2 || details.driver_token_1_3 || details.driver_token_2_2 || details.driver_token_2_3) {
                                driverTokenOptions2 += `<option value="${driver.token_number}">${driver.token_number} - ${driver.EMP_NAME}</option>`;
                            } else {
                                driverTokenOptions2 = ''; // If no valid tokens, clear the options
                            }
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
                                <input type="time" class="form-control" id="act_dep_time" name="act_dep_time" value="" required >
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
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
                        function updateDriverTokenOptions() {
                            var driverToken1 = $('#driver_token_no_1').val();
                            var driverToken2 = $('#driver_token_no_2').val();

                            // Enable all options initially
                            $('#driver_token_no_1 option, #driver_token_no_2 option').prop('disabled', false);

                            // Disable and highlight selected option in the opposite select box
                            if (driverToken1 && driverToken1 === driverToken2) {
                                $('#driver_token_no_2').val('').trigger('change.select2');
                                alert('Please select different token numbers for Driver Token No 1 and Driver Token No 2.');
                            }

                            $('#driver_token_no_1 option[value="' + driverToken2 + '"]').prop('disabled', true);
                            $('#driver_token_no_2 option[value="' + driverToken1 + '"]').prop('disabled', true);

                            // Refresh Select2 elements
                            $('#driver_token_no_1, #driver_token_no_2').trigger('change.select2');
                        }
                        
                        $('#driver_token_no_1').on('change', function () {
                            updateDriverTokenOptions();
                        });
                        updateCurrentTime();

                        $('#driver_token_no_2').on('change', function () {
                            updateDriverTokenOptions();
                        });

                        updateDriverTokenOptions();
                    } else {
                        scheduleDetailsDiv.innerHTML = 'No details found for this schedule number.';
                    }
                });
            });
        }


        function updateCurrentTime() {
            var actDepTimeField = document.getElementById('act_dep_time1');
            if (actDepTimeField) {
                setInterval(function () {
                    var now = new Date();
                    var hours = now.getHours();
                    var minutes = now.getMinutes();
                    var seconds = now.getSeconds();

                    hours = hours < 10 ? '0' + hours : hours;
                    minutes = minutes < 10 ? '0' + minutes : minutes;
                    seconds = seconds < 10 ? '0' + seconds : seconds;

                    var currentTime = hours + ':' + minutes;
                    actDepTimeField.value = currentTime;
                }, 1000);
            }
            calculateTimeDifference();
        }
        function calculateTimeDifference() {
            const scheduleStartTime = document.getElementById('sch_dep_time').value;
            const actualDepartureTime = document.getElementById('act_dep_time').value;

            if (scheduleStartTime && actualDepartureTime) {
                const startTime = new Date(`1970-01-01T${scheduleStartTime}Z`);
                const departureTime = new Date(`1970-01-01T${actualDepartureTime}Z`);

                const timeDifference = (departureTime - startTime) / (1000 * 60); // Difference in minutes
                document.getElementById('time_diff').value = timeDifference;

                if (timeDifference > 15) {
                    document.getElementById('reason_for_late_departure').parentElement.style.display = 'block';
                    document.getElementById('reason_early_departure').parentElement.style.display = 'none';
                } else if (timeDifference < -15) {
                    document.getElementById('reason_early_departure').parentElement.style.display = 'block';
                    document.getElementById('reason_for_late_departure').parentElement.style.display = 'none';
                } else {
                    document.getElementById('reason_for_late_departure').parentElement.style.display = 'none';
                    document.getElementById('reason_early_departure').parentElement.style.display = 'none';
                }
            }
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
                        resolve(filteredData);
                    } else if (xhr.readyState === 4) {
                        reject('Error fetching additional data');
                    }
                };
                xhr.send();
            });
        }



    });

</script>
<?php include '../includes/footer.php'; ?>