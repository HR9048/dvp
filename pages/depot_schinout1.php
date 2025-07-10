<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Session Expired please Login again.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'SECURITY') {
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    $today = date('Y-m-d');

    $vehicle_out_numbers = [];

    /*$query = "SELECT br.bus_number AS id, br.bus_number AS text
    FROM bus_registration br
    LEFT JOIN sch_veh_out svo 
        ON svo.vehicle_no = br.bus_number 
        AND svo.schedule_status = '1' 
        AND svo.depot_id = ? 
        AND svo.division_id = ?
    WHERE br.depot_name = ? 
      AND br.division_name = ? 
      AND br.deleted != '1' 
      AND br.scraped != '1'
      AND svo.vehicle_no IS NULL

    UNION

    SELECT vd.bus_number AS id, CONCAT(vd.bus_number, ' (deputed)') AS text
    FROM vehicle_deputation vd
    LEFT JOIN sch_veh_out svo2 
        ON svo2.vehicle_no = vd.bus_number 
        AND svo2.schedule_status = '1' 
        AND svo2.depot_id = ? 
        AND svo2.division_id = ?
    WHERE vd.t_depot_id = ? 
      AND vd.t_division_id = ? 
      AND vd.deleted != '1' 
      AND vd.status != '1' 
      AND vd.tr_date = ?
      AND svo2.vehicle_no IS NULL";*/
    $query = "SELECT DISTINCT 
    COALESCE(br.bus_number, vd.bus_number) AS id,
    CASE 
        WHEN vd.bus_number IS NOT NULL THEN CONCAT(vd.bus_number, ' (deputed)')
        ELSE br.bus_number 
    END AS text
FROM bus_registration br
LEFT JOIN vehicle_deputation vd
    ON vd.t_depot_id = br.depot_name
    AND vd.t_division_id =br.depot_name
    AND vd.tr_date = ?
    AND vd.deleted != '1'
    AND vd.status != '1'
    AND br.bus_number = vd.bus_number
WHERE br.depot_name = ? 
  AND br.division_name = ? ";


    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "sss",
        $today,
        $depot_id,
        $division_id
    );

    /*$query = "SELECT bus_number as id, bus_number as text from bus_registration WHERE depot_name = ? AND division_name = ? AND deleted != '1' AND scraped != '1'";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $depot_id, $division_id);*/
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $vehicle_out_numbers[] = [
            'id' => $row['id'],
            'text' => $row['text']
        ];
    }
    $stmt->close();


?>
    <style>
        .select2-results__option[aria-disabled="true"] {
            background-color: #FFE800 !important;
        }

        .nav-tabs .nav-link {
            background-color: rgb(134, 235, 124);
            /* Default background for tabs */
            border: none;
            /* Remove default borders */
            border-radius: 5px 5px 0 0;
            /* Rounded top corners for tabs */
            /* Add padding for spacing */
            transition: background-color 0.3s ease, color 0.3s ease;
            /* Smooth hover and active effect */
            color: #555;
            /* Default tab text color */
        }

        .nav-tabs .nav-link.active {
            background-color: rgb(44, 110, 5);
            /* Highlighted background for active tab */
            color: #fff;
            /* White text for the active tab */
            font-weight: bold;
            /* Bold text for emphasis */
        }

        .nav-tabs .nav-link:hover {
            background-color: rgb(44, 110, 5);
            /* Slight hover effect for non-active tabs */
            color: #333;
            /* Darker text on hover */
        }

        /* Tab content styles */
        .tab-content {
            background-color: rgb(255, 255, 255);
            /* Light background for content */
            border-radius: 0 0 5px 5px;
            /* Rounded bottom corners for content */
            padding: 5px;
            /* Add padding for spacing */
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            /* Subtle shadow for depth */
        }

        /* Default font size for small screens */
        .custom-size {
            font-size: 0.6rem;
            /* Adjust for smaller devices */
        }

        /* Medium screens (tablets) */
        @media (min-width: 768px) {
            .custom-size {
                font-size: 0.5rem;
                /* Slightly larger font size */
            }
        }

        /* Large screens (desktops) */
        @media (min-width: 1200px) {
            .custom-size {
                font-size: 1.5rem;
                /* Even larger font size */
            }
        }
    </style>
    <!-- Loader overlay -->
    <div id="loadingOverlay"
        style="
       position: fixed;
       top: 0;
       left: 0;
       width: 100vw;
       height: 100vh;
       background: rgba(255, 255, 255, 0.9);
       display: flex;
       justify-content: center;
       align-items: center;
       z-index: 1000;
       font-size: 1.5rem;
       color: #333;">
        ⏳ Loading...
    </div>

    <p style="text-align:right"><button class="btn btn-warning"><a href="depot_schedule_incomplete.php">ಅಪೂರ್ಣತೆಯ ಅನುಸೂಚಿ?</a></button></p>
    <div id="showloading">
        <nav>
            <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
                <button class="nav-link active custom-size" id="nav-home-tab" data-bs-toggle="tab"
                    data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home"
                    aria-selected="true">ವಾಹನ ಹೊರಗೆ</button>
                <button class="nav-link custom-size" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                    type="button" role="tab" aria-controls="nav-profile" aria-selected="false">ವಾಹನ ಒಳಗೆ</button>
            </div>
        </nav>
        <div>
            <div class="tab-content" id="nav-tabContent"
                style="width: 40%; min-width: 300px; margin: 0 auto; text-align: center;">
                <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                    <div class="container" style="padding:2px">
                        <h4>ಘಟಕ: <?php echo $_SESSION['DEPOT']; ?></h4>
                        <p style="color: red;">ವಾಹನ ನಿರ್ಗಮನ</p>
                        <form id="sch_out_form" method="POST" class="mt-4">
                            <div class="form-group">
                                <label for="veh_no_out">Vehicle No/ವಾಹನ ಸಂಖ್ಯೆ</label>
                                <select class="form-control " id="veh_no_out" name="veh_no_out" required style="width: 100%;">
                                    <option value="">ವಾಹನ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>
                                    <?php foreach ($vehicle_out_numbers as $vehicle): ?>
                                        <option value="<?= htmlspecialchars($vehicle['id']) ?>"><?= htmlspecialchars($vehicle['text']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display: none;" id="schedule_field_wrapper">
                                <label for="schedule_no_out">Schedule No/ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆ</label>
                                <select name="schedule_no_out" id="schedule_no_out" class="form-control" required style="width: 100%;">
                                    <option value="">Select Schedule</option>
                                </select>
                            </div>
                            <div id="scheduleoutdetailsview"></div>
                        </form>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                    <div class="container" style="padding:2px">
                        <h4>ಘಟಕ: <?php echo $_SESSION['DEPOT']; ?></h4>
                        <p style="color:red;">ವಾಹನ ಆಗಮನ</p>
                        <form id="sch_in_form" method="POST" class="mt-4">
                            <div class="form-group">
                                <label for="veh_no_in">Vehicle No/ವಾಹನ ಸಂಖ್ಯೆ</label>
                                <select class="form-control select2" id="veh_no_in" name="veh_no_in" required style="width: 100%;">
                                    <option value="">ವಾಹನ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>
                                    <?php
                                    $query = "SELECT vehicle_no FROM sch_veh_out WHERE schedule_status = '1' AND depot_id = ? AND division_id = ?";
                                    $stmt = $db->prepare($query);
                                    $stmt->bind_param("ss", $depot_id, $division_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['vehicle_no']) . "'>" . htmlspecialchars($row['vehicle_no']) . "</option>";
                                    }
                                    $stmt->close();
                                    ?>
                                </select>
                            </div>
                            <div id="scheduleindetailsview">
                                <!-- Fields will be populated here dynamically using JavaScript -->
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#veh_no_out').select2({
                placeholder: 'ವಾಹನ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });

            $('#veh_no_in').select2({
                placeholder: 'ವಾಹನ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });
            $('#schedule_no_out').select2({
                placeholder: 'ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });
        });


        $('#veh_no_out').change(function() {
            var veh_no_out = $(this).val();
            $('#scheduleoutdetailsview').html("");
            if (veh_no_out) {
                $.ajax({
                    type: "POST",
                    url: "../includes/backend_data.php",
                    dataType: "json",
                    data: {
                        action: 'vehicle_out_fetch_schedules',
                        veh_no_out: veh_no_out
                    },
                    success: function(response) {
                        if (response.status === 'error') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Vehicle Already on Operation',
                                text: response.message,
                                confirmButtonColor: '#d33'
                            }).then(() => {
                                $('#veh_no_out').val(''); // Clear selected value
                                $('#schedule_no_out').html('<option value="">Select Schedule</option>');
                                $('#schedule_field_wrapper').hide();
                            });
                        } else if (response.status === 'success') {
                            $('#schedule_no_out').html(response.options);
                            $('#schedule_field_wrapper').show();
                        }
                    }
                });
            } else {
                $('#schedule_no_out').html('<option value="">Select Schedule</option>');
                $('#schedule_field_wrapper').hide();
            }
        });




        $('#schedule_no_out').change(function() {
            var scheduleKey = $(this).val();
            var busnumber =   $('#veh_no_out').val();
            $('#scheduleoutdetailsview').html("");
            if (scheduleKey) {
                $.ajax({
                    type: "POST",
                    url: "../includes/backend_data.php",
                    data: {
                        action: 'fetch_schedule_details',
                        schedule_key_no: scheduleKey,
                        busnumber: busnumber
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);

                            if (data.status && data.status === 'error') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops!',
                                    text: data.message,
                                    confirmButtonColor: '#d33',
                                }).then(() => {
                                    // Reset the schedule select
                                    $('#schedule_no_out').val('');
                                });
                                return;
                            }
                            // If it's NOT an error, proceed
                            scheduledetailsforselectedschedule(data);

                        } catch (e) {
                            console.error("Invalid JSON received", e);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                    }
                });
            } else {
                $('#scheduleoutdetailsview').html(""); // clear details if nothing selected
            }
        });

        function scheduledetailsforselectedschedule(data) {
            fetchAdditionalData().then(additionalData => {

                // Separate filtering for drivers and conductors
                var driverData = additionalData.filter(function(employee) {
                    return ![
                        data.driver_pf_1, data.driver_pf_2, data.driver_pf_3,
                        data.driver_pf_4, data.driver_pf_5, data.driver_pf_6,
                        data.offreliverdriver_pf_1, data.offreliverdriver_pf_2
                    ].includes(employee.EMP_PF_NUMBER);
                });

                var conductorData = additionalData.filter(function(employee) {
                    return ![
                        data.conductor_pf_1, data.conductor_pf_2, data.conductor_pf_3,
                        data.offreliverconductor_pf_1
                    ].includes(employee.EMP_PF_NUMBER);
                });

                // Now start building HTML after filtering is done
                var detailsHtml = '';

                // DRIVER TOKEN 1
                var driverOptions1 = '';
                if (data.driver_pf_1) driverOptions1 += `<option value="${data.driver_pf_1}">${data.driver_token_1} -${data.driver_name_1}(Allotted)</option>`;
                if (data.driver_pf_2) driverOptions1 += `<option value="${data.driver_pf_2}">${data.driver_token_2} -${data.driver_name_2}(Allotted)</option>`;
                if (data.driver_pf_3) driverOptions1 += `<option value="${data.driver_pf_3}">${data.driver_token_3} -${data.driver_name_3}(Allotted)</option>`;
                if (data.driver_pf_4) driverOptions1 += `<option value="${data.driver_pf_4}">${data.driver_token_4} -${data.driver_name_4}(Allotted)</option>`;
                if (data.driver_pf_5) driverOptions1 += `<option value="${data.driver_pf_5}">${data.driver_token_5} -${data.driver_name_5}(Allotted)</option>`;
                if (data.driver_pf_6) driverOptions1 += `<option value="${data.driver_pf_6}">${data.driver_token_6} -${data.driver_name_6}(Allotted)</option>`;
                if (data.offreliverdriver_pf_1) driverOptions1 += `<option value="${data.offreliverdriver_pf_1}">${data.offreliverdriver_token_1} -${data.offreliverdriver_name_1}(Allotted Off-Reliver)</option>`;
                if (data.offreliverdriver_pf_2) driverOptions1 += `<option value="${data.offreliverdriver_pf_2}">${data.offreliverdriver_token_2} -${data.offreliverdriver_name_2}(Allotted Off-Reliver)</option>`;

                // Append additional drivers
                driverData.forEach(function(driver) {
                    driverOptions1 += `<option value="${driver.EMP_PF_NUMBER}">${driver.token_number} - ${driver.EMP_NAME}</option>`;
                });

                if (driverOptions1) {
                    detailsHtml += `
                <div class="form-group">
                    <label for="driver_token_no_1">Driver Token No 1/ಚಾಲಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ 1</label>
                    <select class="form-control" id="driver_token_no_1" required>
                        <option value="">-- Select Driver Token No 1 --</option>
                        ${driverOptions1}
                    </select>
                </div>`;
                }

                // DRIVER TOKEN 2 (only for service_type_id 4)
                var driverOptions2 = '';
                if (data.driver_pf_1) driverOptions2 += `<option value="${data.driver_pf_1}">${data.driver_token_1} -${data.driver_name_1}(Allotted)</option>`;
                if (data.driver_pf_2) driverOptions2 += `<option value="${data.driver_pf_2}">${data.driver_token_2} -${data.driver_name_2}(Allotted)</option>`;
                if (data.driver_pf_3) driverOptions2 += `<option value="${data.driver_pf_3}">${data.driver_token_3} -${data.driver_name_3}(Allotted)</option>`;
                if (data.driver_pf_4) driverOptions2 += `<option value="${data.driver_pf_4}">${data.driver_token_4} -${data.driver_name_4}(Allotted)</option>`;
                if (data.driver_pf_5) driverOptions2 += `<option value="${data.driver_pf_5}">${data.driver_token_5} -${data.driver_name_5}(Allotted)</option>`;
                if (data.driver_pf_6) driverOptions2 += `<option value="${data.driver_pf_6}">${data.driver_token_6} -${data.driver_name_6}(Allotted)</option>`;
                if (data.offreliverdriver_pf_1) driverOptions2 += `<option value="${data.offreliverdriver_pf_1}">${data.offreliverdriver_token_1} -${data.offreliverdriver_name_1}(Allotted Off-Reliver)</option>`;
                if (data.offreliverdriver_pf_2) driverOptions2 += `<option value="${data.offreliverdriver_pf_2}">${data.offreliverdriver_token_2} -${data.offreliverdriver_name_2}(Allotted Off-Reliver)</option>`;

                driverData.forEach(function(driver) {
                    driverOptions2 += `<option value="${driver.EMP_PF_NUMBER}">${driver.token_number} - ${driver.EMP_NAME}</option>`;
                });

                if (data.service_type_id == '4' && driverOptions2) {
                    detailsHtml += `
                <div class="form-group">
                    <label for="driver_token_no_2">Driver Token No 2/ಚಾಲಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ 2</label>
                    <select class="form-control" id="driver_token_no_2">
                        <option value="">-- Select Driver Token No 2 --</option>
                        ${driverOptions2}
                    </select>
                </div>`;
                }

                // CONDUCTOR
                var conductorOptions = '';
                if (data.conductor_pf_1) conductorOptions += `<option value="${data.conductor_pf_1}">${data.conductor_token_1} -${data.conductor_name_1}(Allotted)</option>`;
                if (data.conductor_pf_2) conductorOptions += `<option value="${data.conductor_pf_2}">${data.conductor_token_2} -${data.conductor_name_2}(Allotted)</option>`;
                if (data.conductor_pf_3) conductorOptions += `<option value="${data.conductor_pf_3}">${data.conductor_token_3} -${data.conductor_name_3}(Allotted)</option>`;
                if (data.offreliverconductor_pf_1) conductorOptions += `<option value="${data.offreliverconductor_pf_1}">${data.offreliverconductor_token_1} -${data.offreliverconductor_name_1}(Allotted Off-Reliver)</option>`;

                conductorData.forEach(function(conductor) {
                    conductorOptions += `<option value="${conductor.EMP_PF_NUMBER}">${conductor.token_number} - ${conductor.EMP_NAME}</option>`;
                });

                if (data.single_crew == 'no' && conductorOptions) {
                    detailsHtml += `
                <div class="form-group">
                    <label for="conductor_token_no">Conductor Token No/ನಿರ್ವಾಹಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ</label>
                    <select class="form-control" id="conductor_token_no">
                        <option value="">-- Select Conductor Token No --</option>
                        ${conductorOptions}
                    </select>
                </div>`;
                }

                detailsHtml += `<div class="row">`;
                detailsHtml += `<div class="col">`;
                detailsHtml += `<div class="form-group">`;
                detailsHtml += `<label for="sch_dep_time">Sch Departure time/ ಅನುಸೂಚಿ ನಿರ್ಗಮನ ಸಮಯ</label>`;
                detailsHtml += `<input type="time" class="form-control" id="sch_dep_time" name="sch_dep_time" value="${data.sch_dep_time}" required readonly>`;
                detailsHtml += `</div>`;
                detailsHtml += `</div>`;
                detailsHtml += `<div class="col">`;
                detailsHtml += `<div class="form-group">`;
                detailsHtml += `<label for="act_dep_time">Act Departure time/ನಿಗದಿತ ನಿರ್ಗಮನ ಸಮಯ</label>`;
                detailsHtml += `<input type="time" class="form-control" id="act_dep_time" name="act_dep_time" value="" required>`;
                detailsHtml += `</div>`;
                detailsHtml += `</div>`;
                detailsHtml += `</div>`;
                detailsHtml += `<div class="form-group" style="display: none;">`;
                detailsHtml += `<label for="time_diff">Time Difference (minutes)</label>`;
                detailsHtml += `<input type="text" class="form-control" id="time_diff" name="time_diff" readonly>`;
                detailsHtml += `</div>`;
                detailsHtml += `<div class="form-group" style="display: none;">`;
                detailsHtml += `<label for="reason_for_late_departure">Reason for Late Departure/ತಡವಾಗಿ ನಿರ್ಗಮನಕ್ಕೆ ಕಾರಣ:</label>`;
                detailsHtml += `<textarea class="form-control" id="reason_for_late_departure" name="reason_for_late_departure"></textarea>`;
                detailsHtml += `</div>`;
                detailsHtml += `<div class="form-group" style="display: none;">`;
                detailsHtml += `<label for="reason_early_departure">Reason for Early Departure/ಮುಂಚಿತ ನಿರ್ಗಮನಕ್ಕೆ ಕಾರಣ:</label>`;
                detailsHtml += `<textarea class="form-control" id="reason_early_departure" name="reason_early_departure"></textarea>`;
                detailsHtml += `</div>`;
                detailsHtml += `<div class="form-group">`;
                detailsHtml += `<button type="submit" id="submitBtnvehicleout" class="btn btn-primary">Submit</button>`;
                detailsHtml += `</div>`;
                // Finally update DOM after everything ready
                $('#scheduleoutdetailsview').html(detailsHtml);

                // Apply select2 after DOM updated
                $('#driver_token_no_1').select2({
                    placeholder: 'ಚಾಲಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ 1 ಆಯ್ಕೆಮಾಡಿ',
                    allowClear: true
                });

                $('#driver_token_no_2').select2({
                    placeholder: 'ಚಾಲಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ 2 ಆಯ್ಕೆಮಾಡಿ',
                    allowClear: true
                });

                $('#conductor_token_no').select2({
                    placeholder: 'ನಿರ್ವಾಹಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ ಆಯ್ಕೆಮಾಡಿ',
                    allowClear: true
                });

                $(document).on('change', '#act_dep_time', function() {
                    var actDepTime = document.getElementById('act_dep_time').value;
                    var currentTime = new Date().toTimeString().slice(0, 5); // Get current time in HH:MM format

                    if (actDepTime > currentTime) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Time!',
                            text: 'ನಿಗದಿತ ನಿರ್ಗಮನ ಸಮಯ ಪ್ರಸ್ತುತ ಸಮಯಕ್ಕಿಂತ ಹೆಚ್ಚು ಇರಬಾರದು!',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            document.getElementById('act_dep_time').value = '';
                        });
                    } else {
                        calculateTimeDifference();
                    }
                });

                $('#driver_token_no_1').on('change', function() {
                    updateDriverTokenOptions();
                });

                $('#driver_token_no_2').on('change', function() {
                    updateDriverTokenOptions();
                });
                $('#conductor_token_no').on('change', function() {
                    updateDriverTokenOptions();
                });
                updateDriverTokenOptions();

            });
        }


        function fetchAdditionalData() {
            return new Promise(function(resolve, reject) {
                var division = '<?php echo $_SESSION['KMPL_DIVISION']; ?>';
                var depot = '<?php echo $_SESSION['KMPL_DEPOT']; ?>';

                var dataApiUrl = '../includes/data.php?division=' + encodeURIComponent(division) + '&depot=' + encodeURIComponent(depot);
                var empApiUrl = '../database/private_emp_api.php?division=' + encodeURIComponent(division) + '&depot=' + encodeURIComponent(depot);
                var depApiUrl = '../database/deputation_crew_api.php?division=' + encodeURIComponent(division) + '&depot=' + encodeURIComponent(depot);

                function fetchApiData(url) {
                    return new Promise(function(resolve, reject) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', url, true);
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                if (xhr.status === 200) {
                                    try {
                                        var response = JSON.parse(xhr.responseText);
                                        if (!response.data || response.data.length === 0) {
                                            resolve([]);
                                        } else {
                                            resolve(response.data);
                                        }
                                    } catch (e) {
                                        reject('Error parsing response from ' + url + ': ' + e.message);
                                    }
                                } else {
                                    reject('Error fetching data from ' + url);
                                }
                            }
                        };
                        xhr.send();
                    });
                }

                Promise.all([fetchApiData(dataApiUrl), fetchApiData(empApiUrl), fetchApiData(depApiUrl)])
                    .then(function(responses) {
                        var dataResp = responses[0];
                        var empResp = responses[1];
                        var depResp = responses[2];


                        let combinedData = [].concat(dataResp, empResp, depResp);

                        // Normalize token_number and Division/Depot casing if needed
                        combinedData.forEach(function(item) {
                            item.token_number = parseInt(item.token_number) || 0;
                            item.Division = (item.Division || '').toString().trim();
                            item.Depot = (item.Depot || '').toString().trim();
                        });


                        fetchVechSchOutData().then(function(vehSchOutData) {
                            combinedData = combinedData.filter(function(item) {
                                return !vehSchOutData.some(function(vehItem) {
                                    return vehItem.driver_1_pf === item.EMP_PF_NUMBER ||
                                        vehItem.driver_2_pf === item.EMP_PF_NUMBER ||
                                        vehItem.conductor_pf_no === item.EMP_PF_NUMBER;
                                });
                            });


                            fetchdepCrewData().then(function(depCrewData) {
                                combinedData = combinedData.filter(function(item) {
                                    return !depCrewData.some(function(depItem) {
                                        return depItem.DEP_EMP_PF_NUMBER === item.EMP_PF_NUMBER;
                                    });
                                });


                                // Final filter by division and depot (optional if API already filters)
                                combinedData = combinedData.filter(function(item) {
                                    return item.Division === division && item.Depot === depot;
                                });

                                // Final sort by token_number
                                combinedData.sort(function(a, b) {
                                    return a.token_number - b.token_number;
                                });


                                resolve(combinedData);
                            }).catch(function(error) {
                                reject('Error filtering DEPUTATION_CREW data: ' + error);
                            });
                        }).catch(function(error) {
                            reject('Error filtering VEH_SCH_OUT data: ' + error);
                        });
                    })
                    .catch(function(error) {
                        reject(error);
                    });
            });
        }

        function fetchVechSchOutData() {
            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'fetch_veh_sch_out.php', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            resolve(data);
                        } catch (e) {
                            reject('Error parsing VEH_SCH_OUT data: ' + e.message);
                        }
                    } else if (xhr.readyState === 4) {
                        reject('Error fetching VEH_SCH_OUT data');
                    }
                };
                xhr.send();
            });
        }

        function fetchdepCrewData() {
            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'fetch_deputation_data.php', true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            resolve(data);
                        } catch (e) {
                            reject('Error parsing deputation data: ' + e.message);
                        }
                    } else if (xhr.readyState === 4) {
                        reject('Error fetching deputation data');
                    }
                };
                xhr.send();
            });
        }

        function calculateTimeDifference() {
            var schDepTime = document.getElementById('sch_dep_time').value;
            var actDepTime = document.getElementById('act_dep_time').value;

            if (schDepTime && actDepTime) {
                var schParts = schDepTime.split(':');
                var actParts = actDepTime.split(':');

                var schMinutes = parseInt(schParts[0]) * 60 + parseInt(schParts[1]);
                var actMinutes = parseInt(actParts[0]) * 60 + parseInt(actParts[1]);

                // Handle midnight crossing
                var timeDiff = actMinutes - schMinutes;
                if (timeDiff < -1080) {
                    // assume next day if difference is more than 18 hours negative
                    timeDiff += 1440; // add 24 hours
                } else if (timeDiff > 1080) {
                    // assume previous day if difference is more than 18 hours positive
                    timeDiff -= 1440;
                }

                document.getElementById('time_diff').value = timeDiff;

                // Your existing condition logic
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
            $('#driver_token_no_1 option, #driver_token_no_2 option, #conductor_token_no option').prop('disabled',
                false);

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

        $(document).ready(function() {
            $('#sch_out_form').on('submit', function(e) {
                e.preventDefault();

                var submitBtn = document.getElementById('submitBtnvehicleout');
                submitBtn.disabled = true;
                var originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = "Submitting...";

                var formData = $(this).serialize() + '&action=vehicle_out_submit';

                $.ajax({
                    type: 'POST',
                    url: '../includes/backend_data.php',
                    dataType: 'json',
                    data: formData,
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'A Network error occurred: ' + (xhr.responseText || error),
                            confirmButtonText: 'OK'
                        });
                    },
                    complete: function() {
                        // This runs on both success and error
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                });
            });

        });
    </script>
    <script>
        // This script assumes the page has finished making the AJAX request or
        // that this block is rendered after your $vehicle_out_numbers are available.

        document.addEventListener("DOMContentLoaded", function() {
            // Wait until the page finishes loading
            const loader = document.getElementById("loadingOverlay");
            const mainContent = document.getElementById("showloading");

            // If data is available (PHP already rendered the select), show it
            if (mainContent) {
                loader.style.display = "none"; // Hide loader
                mainContent.style.display = "block"; // Show main content
            }
        });
    </script>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>