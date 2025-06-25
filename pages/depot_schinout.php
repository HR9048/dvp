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


?>


    <style>
        /* Tab navigation styles
.nav-tabs {
    border-bottom: 12px solid #ddd;
}
*/
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


    <style>
        .select2-results__option[aria-disabled="true"] {
            background-color: #FFE800 !important;
        }
    </style>
    <p style="text-align:right"><button class="btn btn-warning"><a href="depot_schedule_incomplete.php">ಅಪೂರ್ಣತೆಯ ಅನುಸೂಚಿ
                ?</a></button></p>
    <nav>
        <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
            <button class="nav-link active custom-size" id="nav-home-tab" data-bs-toggle="tab"
                data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home"
                aria-selected="true">ಅನುಸೂಚಿ ಹೊರಗೆ</button>
            <button class="nav-link custom-size" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                type="button" role="tab" aria-controls="nav-profile" aria-selected="false">ಅನುಸೂಚಿ ಒಳಗೆ</button>
            <button class="nav-link custom-size" id="nav-exchange-tab" data-bs-toggle="tab"
                data-bs-target="#nav-exchange" type="button" role="tab" aria-controls="nav-exchange"
                aria-selected="false">ಬದಲಾವಣೆ</button>
        </div>
    </nav>
    <div>
        <div class="tab-content" id="nav-tabContent"
            style="width: 40%; min-width: 300px; margin: 0 auto; text-align: center;">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                <div class="container" style="padding:2px">
                    <h4>ಘಟಕ: <?php echo $_SESSION['DEPOT']; ?></h4>
                    <p style="color: red;">ಅನುಸೂಚಿಗಳ ನಿರ್ಗಮನ</p>
                    <form id="sch_out_form" method="POST" class="mt-4">
                        <div class="form-group">
                            <label for="sch_no">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆ</label>
                            <select class="form-control select2" id="sch_no" name="sch_no" required style="width: 100%;">
                                <option value="">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>
                            </select>
                        </div>
                        <div id="scheduleDetails">
                            <!-- Fields will be populated here dynamically using JavaScript -->
                        </div>
                    </form>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                <div class="container" style="padding:2px">
                    <h4>ಘಟಕ: <?php echo $_SESSION['DEPOT']; ?></h4>
                    <p style="color:red;">ಅನುಸೂಚಿಗಳ ಆಗಮನ</p>
                    <form id="sch_in_form" method="POST" class="mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        <label for="sch_no_in">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆ</label>
                                        <select class="form-control select2" id="sch_no_in" name="sch_no_in" required
                                            style="min-width: 100px;">
                                            <option value="">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        <label for="out_date">ಅನುಸೂಚಿಗಳ ನಿರ್ಗಮನದ ದಿನಾಂಕ</label>
                                        <input class="form-control" type="date" id="out_date" name="out_date" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="scheduleInDetails">
                            <!-- Fields will be populated here dynamically using JavaScript -->
                        </div>

                    </form>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-exchange" role="tabpanel" aria-labelledby="nav-exchange-tab">
                <div class="container" style="padding:2px">
                    <h4>ಘಟಕ: <?php echo $_SESSION['DEPOT']; ?></h4>
                    <nav>
                        <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
                            <button class="nav-link active custom-size" id="nav-bus-tab" data-bs-toggle="tab"
                                data-bs-target="#nav-bus" type="button" role="tab" aria-controls="nav-bus"
                                aria-selected="false">ವಾಹನ ಬದಲಾವಣೆ</button>
                            <button class="nav-link custom-size" id="nav-crew-tab" data-bs-toggle="tab"
                                data-bs-target="#nav-crew" type="button" role="tab" aria-controls="nav-profile"
                                aria-selected="false">ಸಿಬ್ಬಂದಿ ಬದಲಾವಣೆ</button>
                        </div>
                    </nav>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="nav-bus" role="tabpanel" aria-labelledby="nav-bus-tab">
                            <p style="color: red;">ವಾಹನ ಬದಲಾವಣೆ</p>
                            <form id="sch_change_bus_form" method="POST" class="mt-4">
                                <input class="form-control" type="hidden" id="formtype" value="bus" name="formtype">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="sch_no_change_bus">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆ</label>
                                            <select class="form-control select2" id="sch_no_change_bus"
                                                name="sch_no_change_bus" required style="min-width: 100px;">
                                                <option value="">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="out_date_change_bus">ಅನುಸೂಚಿಗಳ ನಿರ್ಗಮನದ ದಿನಾಂಕ</label>
                                            <input class="form-control" type="date" id="out_date_change_bus"
                                                name="out_date_change_bus" required>
                                        </div>
                                    </div>
                                </div>
                                <div id="scheduleChangeDetailsBus">
                                    <!-- Fields will be populated dynamically using JavaScript -->
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="nav-crew" role="tabpanel" aria-labelledby="nav-crew-tab">
                            <p style="color: red;">ಸಿಬ್ಬಂದಿ ಬದಲಾವಣೆ</p>
                            <form id="sch_change_crew_form" method="POST" class="mt-4">
                                <input class="form-control" type="hidden" id="formtype_crew" value="crew"
                                    name="formtype_crew">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="sch_no_change_crew">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆ</label>
                                            <select class="form-control select2" id="sch_no_change_crew"
                                                name="sch_no_change_crew" required style="min-width: 100px;">
                                                <option value="">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="out_date_change_crew">ಅನುಸೂಚಿಗಳ ನಿರ್ಗಮನದ ದಿನಾಂಕ</label>
                                            <input class="form-control" type="date" id="out_date_change_crew"
                                                name="out_date_change_crew" required>
                                        </div>
                                    </div>
                                </div>
                                <div id="scheduleChangeDetailsCrew">
                                    <!-- Fields will be populated dynamically using JavaScript -->
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
                data: {
                    action: 'fetchSchedule'
                },
                success: function(response) {
                    var bodyBuilders = JSON.parse(response);
                    $.each(bodyBuilders, function(index, value) {
                        $('#sch_no').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }

        $(document).ready(function() {
            fetchSchedule();
        });


        $(document).ready(function() {
            $('#sch_no').select2({
                placeholder: 'ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });
            $('#sch_no_in').select2({
                placeholder: 'ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });
            $('#sch_no_change_bus').select2({
                placeholder: 'ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });
            $('#sch_no_change_crew').select2({
                placeholder: 'ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });

            $('#sch_no').on('change', function() {
                var schNo = $(this).val();
                if (schNo) {
                    $.ajax({
                        type: 'POST',
                        url: 'fetch_schedule_details.php',
                        data: {
                            sch_no: schNo
                        },
                        success: function(response) {
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
                            if (details.bus_number_1) vehicleNoOptions += '<option value="' + details
                                .bus_number_1 + '">' + details.bus_number_1 + ' (allotted)</option>';
                            if (details.bus_number_2) vehicleNoOptions += '<option value="' + details
                                .bus_number_2 + '">' + details.bus_number_2 + ' (allotted)</option>';
                            if (details.additional_bus_number) vehicleNoOptions += '<option value="' +
                                details.additional_bus_number + '">' + details.additional_bus_number +
                                ' (Additional allotted)</option>';

                            buses = buses.filter(function(bus) {
                                return bus !== details.bus_number_1 && bus !== details
                                    .bus_number_2 && bus !== details.additional_bus_number;
                            });
                            buses.forEach(function(bus) {
                                vehicleNoOptions += `<option value="${bus.id}">${bus.text}</option>`;
                            });

                            var driverTokenOptions1 =
                                '<option value="">Select Driver Token No 1</option>';
                            var driverTokenOptions2 =
                                '<option value="">Select Driver Token No 2</option>';
                            var conductorTokenOptions =
                                '<option value="">Select Conductor Token No</option>';

                            if (details.driver_token_1) driverTokenOptions1 += '<option value="' +
                                details.driver_pf_1 + '">' + details.driver_token_1 + ' - ' + details
                                .driver_name_1 + ' (allotted)</option>';
                            if (details.driver_token_2) driverTokenOptions1 += '<option value="' +
                                details.driver_pf_2 + '">' + details.driver_token_2 + ' - ' + details
                                .driver_name_2 + ' (allotted)</option>';
                            if (details.driver_token_3) driverTokenOptions1 += '<option value="' +
                                details.driver_pf_3 + '">' + details.driver_token_3 + ' - ' + details
                                .driver_name_3 + ' (allotted)</option>';
                            if (details.driver_token_4) driverTokenOptions1 += '<option value="' +
                                details.driver_pf_4 + '">' + details.driver_token_4 + ' - ' + details
                                .driver_name_4 + ' (allotted)</option>';
                            if (details.driver_token_5) driverTokenOptions1 += '<option value="' +
                                details.driver_pf_5 + '">' + details.driver_token_5 + ' - ' + details
                                .driver_name_5 + ' (allotted)</option>';
                            if (details.driver_token_6) driverTokenOptions1 += '<option value="' +
                                details.driver_pf_6 + '">' + details.driver_token_6 + ' - ' + details
                                .driver_name_6 + ' (allotted)</option>';
                            if (details.offreliverdriver_token_1) driverTokenOptions1 +=
                                '<option value="' + details.offreliverdriver_pf_1 + '">' + details
                                .offreliverdriver_token_1 + ' - ' + details.offreliverdriver_name_1 +
                                ' (allotted off releiver)</option>';
                            if (details.offreliverdriver_token_2) driverTokenOptions1 +=
                                '<option value="' + details.offreliverdriver_pf_2 + '">' + details
                                .offreliverdriver_token_2 + ' - ' + details.offreliverdriver_name_2 +
                                ' (allotted off releiver)</option>';

                            if (['4'].includes(details.service_type_id)) {
                                if (details.driver_token_1) driverTokenOptions2 += '<option value="' +
                                    details.driver_pf_1 + '">' + details.driver_token_1 + ' - ' +
                                    details.driver_name_1 + ' (allotted)</option>';
                                if (details.driver_token_2) driverTokenOptions2 += '<option value="' +
                                    details.driver_pf_2 + '">' + details.driver_token_2 + ' - ' +
                                    details.driver_name_2 + ' (allotted)</option>';
                                if (details.driver_token_3) driverTokenOptions2 += '<option value="' +
                                    details.driver_pf_3 + '">' + details.driver_token_3 + ' - ' +
                                    details.driver_name_3 + ' (allotted)</option>';
                                if (details.driver_token_4) driverTokenOptions2 += '<option value="' +
                                    details.driver_pf_4 + '">' + details.driver_token_4 + ' - ' +
                                    details.driver_name_4 + ' (allotted)</option>';
                                if (details.driver_token_5) driverTokenOptions2 += '<option value="' +
                                    details.driver_pf_5 + '">' + details.driver_token_5 + ' - ' +
                                    details.driver_name_5 + ' (allotted)</option>';
                                if (details.driver_token_6) driverTokenOptions2 += '<option value="' +
                                    details.driver_pf_6 + '">' + details.driver_token_6 + ' - ' +
                                    details.driver_name_6 + ' (allotted)</option>';
                                if (details.offreliverdriver_token_1) driverTokenOptions2 +=
                                    '<option value="' + details.offreliverdriver_pf_1 + '">' + details
                                    .offreliverdriver_token_1 + ' - ' + details.offreliverdriver_name_1 +
                                    ' (allotted off releiver)</option>';
                                if (details.offreliverdriver_token_2) driverTokenOptions2 +=
                                    '<option value="' + details.offreliverdriver_pf_2 + '">' + details
                                    .offreliverdriver_token_2 + ' - ' + details.offreliverdriver_name_2 +
                                    ' (allotted off releiver)</option>';
                            } else {
                                driverTokenOptions2 = ''; // If no valid tokens, clear the options
                            }

                            if (details.single_crew === 'no') {
                                if (details.conductor_token_1) conductorTokenOptions +=
                                    '<option value="' + details.conductor_pf_1 + '">' + details
                                    .conductor_token_1 + ' - ' + details.conductor_name_1 +
                                    ' (allotted)</option>';
                                if (details.conductor_token_2) conductorTokenOptions +=
                                    '<option value="' + details.conductor_pf_2 + '">' + details
                                    .conductor_token_2 + ' - ' + details.conductor_name_2 +
                                    ' (allotted)</option>';
                                if (details.conductor_token_3) conductorTokenOptions +=
                                    '<option value="' + details.conductor_pf_3 + '">' + details
                                    .conductor_token_3 + ' - ' + details.conductor_name_3 +
                                    ' (allotted)</option>';
                                if (details.offreliverconductor_token_1) conductorTokenOptions +=
                                    '<option value="' + details.offreliverconductor_pf_1 + '">' + details
                                    .offreliverconductor_token_1 + ' - ' + details.offreliverconductor_name_1 +
                                    ' (allotted off releiver)</option>';
                            } else {
                                conductorTokenOptions = '';
                            }

                            // Separate filtering for drivers and conductors
                            var driverData = additionalData.filter(function(employee) {
                                return ![details.driver_pf_1, details.driver_pf_2, details
                                    .driver_pf_3, details.driver_pf_4, details
                                    .driver_pf_5, details.driver_pf_6, details
                                    .offreliverdriver_pf_1, details.offreliverdriver_pf_2
                                ].includes(employee.EMP_PF_NUMBER);
                            });

                            driverData.forEach(function(driver) {
                                driverTokenOptions1 +=
                                    `<option value="${driver.EMP_PF_NUMBER}">${driver.token_number} - ${driver.EMP_NAME}</option>`;
                                if (['4'].includes(details.service_type_id)) {
                                    driverTokenOptions2 +=
                                        `<option value="${driver.EMP_PF_NUMBER}">${driver.token_number} - ${driver.EMP_NAME}</option>`;
                                } else {
                                    driverTokenOptions2 =
                                        ''; // If no valid tokens, clear the options
                                }
                            });

                            var conductorData = additionalData.filter(function(employee) {
                                return ![details.conductor_pf_1, details.conductor_pf_2,
                                    details.conductor_pf_3, details.offreliverconductor_pf_1
                                ].includes(employee.EMP_PF_NUMBER);
                            });

                            conductorData.forEach(function(conductor) {
                                conductorTokenOptions +=
                                    `<option value="${conductor.EMP_PF_NUMBER}">${conductor.token_number} - ${conductor.EMP_NAME}</option>`;
                            });

                            var schDepTime = details.sch_dep_time || '';
                            scheduleDetailsDiv.innerHTML = `
                                                                                    <div class="form-group">
                                                                                        <label for="vehicle_no">Vehicle No/ವಾಹನ ಸಂಖ್ಯೆ</label>
                                                                                        <select class="form-control select2" id="vehicle_no" name="vehicle_no" required style="width: 100%;">
                                                                                            ${vehicleNoOptions}
                                                                                        </select>
                                                                                    </div>
                                                                                    <div class="form-group">
                                                                                        <label for="driver_token_no_1">Driver Token No 1/ಚಾಲಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ 1</label>
                                                                                        <select class="form-control select2" id="driver_token_no_1" name="driver_token_no_1" required style="width: 100%;">
                                                                                            ${driverTokenOptions1}
                                                                                        </select>
                                                                                    </div>
                                                                                    ${driverTokenOptions2 ? `
                                                                                        <div class="form-group">
                                                                                            <label for="driver_token_no_2">Driver Token No 2/ಚಾಲಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ 2</label>
                                                                                            <select class="form-control select2" id="driver_token_no_2" name="driver_token_no_2" style="width: 100%;">
                                                                                                ${driverTokenOptions2}
                                                                                            </select>
                                                                                        </div>
                                                                                    ` : ''}
                                                                                    ${details.single_crew === 'no' ? `
                                                                                    <div class="form-group">
                                                                                        <label for="conductor_token_no">Conductor Token No/ನಿರ್ವಾಹಕ ಟೋಕನ್‌ ಸಂಖ್ಯೆ </label>
                                                                                        <select class="form-control select2" id="conductor_token_no" name="conductor_token_no" style="width: 100%;">
                                                                                            ${conductorTokenOptions}
                                                                                        </select>
                                                                                    </div>
                                                                                ` : ''}
                                                                                    <div class="row">
                                                                                        <div class="col">
                                                                                            <div class="form-group">
                                                                                                <label for="sch_dep_time">Sch Departure time/ ಅನುಸೂಚಿ ನಿರ್ಗಮನ ಸಮಯ</label>
                                                                                                <input type="time" class="form-control" id="sch_dep_time" name="sch_dep_time" value="${schDepTime}" required readonly>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col">
                                                                                            <div class="form-group">
                                                                                                <label for="act_dep_time">Act Departure time/ನಿಗದಿತ ನಿರ್ಗಮನ ಸಮಯ</label>
                                                                                                <input type="time" class="form-control" id="act_dep_time" name="act_dep_time" value="" required>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="form-group" style="display: none;">
                                                                                        <label for="time_diff">Time Difference (minutes)</label>
                                                                                        <input type="text" class="form-control" id="time_diff" name="time_diff" readonly>
                                                                                    </div>
                                                                                    <div class="form-group" style="display: none;">
                                                                                        <label for="reason_for_late_departure">Reason for Late Departure/ತಡವಾಗಿ ನಿರ್ಗಮನಕ್ಕೆ ಕಾರಣ:</label>
                                                                                        <textarea class="form-control" id="reason_for_late_departure" name="reason_for_late_departure"></textarea>
                                                                                    </div>
                                                                                    <div class="form-group" style="display: none;">
                                                                                        <label for="reason_early_departure">Reason for Early Departure/ಮುಂಚಿತ ನಿರ್ಗಮನಕ್ಕೆ ಕಾರಣ:</label>
                                                                                        <textarea class="form-control" id="reason_early_departure" name="reason_early_departure"></textarea>
                                                                                    </div>
                                                                                    <div class="form-group">
                                                                                        <button type="submit" id="submitBtnvehicleout" class="btn btn-primary">Submit</button>
                                                                                    </div>
                                                                                `;

                            $('.select2').select2();
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

                            // updateCurrentTime();
                            // calculateTimeDifference();

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


            function fetchBuses() {
                return new Promise(function(resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'fetch_buses.php', true);
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            try {
                                var busesData = JSON.parse(xhr.responseText);
                                resolve(busesData); // Resolve the promise with the buses data
                            } catch (e) {
                                reject('Error parsing response: ' + e.message); // Reject if there's a parsing error
                            }
                        } else if (xhr.readyState === 4) {
                            reject('Error fetching buses data'); // Reject if there's an issue with the request
                        }
                    };
                    xhr.send();
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

        });
        $(document).ready(function() {
            $('#sch_out_form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                document.getElementById('submitBtnvehicleout').addEventListener('click', function() {
                    document.getElementById('submitBtnvehicleout').disabled = true;
                });
                // Serialize form data
                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: '../database/depot_submit_schedule_out.php', // URL of the PHP script
                    data: formData,
                    dataType: 'json', // Expect a JSON response
                    success: function(response) {
                        if (response.status === 'success') {
                            // Display SweetAlert on success
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload the page after the alert is closed
                                window.location.reload();
                            });
                        } else {
                            // Display SweetAlert on error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Display detailed error message from the server
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An Network error occurred Please refresh page and try once again: ' + (xhr.responseText || error),
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

        });
    </script>
    <!-- Schedule In script -->
    <script>
        function fetchScheduleIn() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: {
                    action: 'fetchScheduleIn'
                },
                success: function(response) {
                    var bodyBuilders = JSON.parse(response);
                    $.each(bodyBuilders, function(index, value) {
                        $('#sch_no_in').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }
        $(document).ready(function() {
            fetchScheduleIn();
        });
        $(document).ready(function() {
            function fetchScheduleDetails() {
                var scheduleNo = $('#sch_no_in').val();
                var outDate = $('#out_date').val();

                if (scheduleNo && outDate) {
                    $.ajax({
                        url: 'fetch_schedulein_details.php',
                        type: 'POST',
                        data: {
                            scheduleNo: scheduleNo,
                            outDate: outDate
                        },
                        success: function(response) {
                            $('#scheduleInDetails').html(response);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching schedule details:', error);
                        }
                    });
                }
            }

            $('#sch_no_in, #out_date').change(fetchScheduleDetails);
        });
        $(document).ready(function() {
            $('#sch_in_form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                document.getElementById('submitBtnvehiclein').addEventListener('click', function() {
                    document.getElementById('submitBtnvehiclein').disabled = true;
                });
                // Serialize form data
                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: '../database/depot_submit_schedule_in.php', // URL of the PHP script
                    data: formData,
                    dataType: 'json', // Expect a JSON response
                    success: function(response) {
                        if (response.status === 'success') {
                            // Display SweetAlert on success
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload the page after the alert is closed
                                window.location.reload();
                            });
                        } else {
                            // Display SweetAlert on error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle the AJAX request error
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An Network error occurred Please refresh page and try once again: ' + error,
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Fetch schedule options for both forms
            function fetchScheduleOptions() {
                $.ajax({
                    url: '../includes/data_fetch.php',
                    type: 'GET',
                    data: {
                        action: 'fetchScheduleIn'
                    },
                    success: function(response) {
                        var options = JSON.parse(response);
                        $('#sch_no_change_bus, #sch_no_change_crew').empty().append(
                            '<option value="">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>');
                        $.each(options, function(index, value) {
                            $('#sch_no_change_bus, #sch_no_change_crew').append('<option value="' +
                                value + '">' + value + '</option>');
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching schedule options:', error);
                    }
                });
            }

            // Fetch schedule details for bus change
            function fetchBusScheduleDetails() {
                var scheduleNo = $('#sch_no_change_bus').val();
                var outDate = $('#out_date_change_bus').val();
                const formType = $('#formtype').val();
                if (scheduleNo && outDate) {
                    $.ajax({
                        url: '../database/fetch_schedule_change_details.php',
                        type: 'POST',
                        data: {
                            scheduleNo: scheduleNo,
                            outDate: outDate,
                            formType: formType
                        },
                        success: function(response) {
                            $('#scheduleChangeDetailsBus').html(response);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching bus schedule details:', error);
                        }
                    });
                }
            }

            // Fetch schedule details for crew change
            function fetchCrewScheduleDetails() {
                var scheduleNo = $('#sch_no_change_crew').val();
                var outDate = $('#out_date_change_crew').val();
                const formType = $('#formtype_crew').val();
                if (scheduleNo && outDate) {
                    $.ajax({
                        url: '../database/fetch_schedule_change_details.php',
                        type: 'POST',
                        data: {
                            scheduleNo: scheduleNo,
                            outDate: outDate,
                            formType: formType
                        },
                        success: function(response) {
                            $('#scheduleChangeDetailsCrew').html(response);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching crew schedule details:', error);
                        }
                    });
                }
            }

            // Initialize data and attach event listeners
            fetchScheduleOptions();

            $('#sch_no_change_bus, #out_date_change_bus').change(fetchBusScheduleDetails);
            $('#sch_no_change_crew, #out_date_change_crew').change(fetchCrewScheduleDetails);
        });


        $(document).ready(function() {
            $('#sch_change_bus_form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                document.getElementById('submitBtnchange').addEventListener('click', function() {
                    document.getElementById('submitBtnchange').disabled = true;
                });
                // Check if any checkbox is selected
                const isAtLeastOneSelected = $('#change_vehicle').is(':checked') ||
                    $('#change_driver').is(':checked') ||
                    ($('#change_driver2').length && $('#change_driver2').is(':checked')) ||
                    ($('#change_conductor').length && $('#change_conductor').is(':checked'));

                // If no checkbox is selected, show SweetAlert
                if (!isAtLeastOneSelected) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please select at least one field to change before submitting.',
                        confirmButtonText: 'OK'
                    });
                    return; // Prevent form submission
                }

                // Serialize form data if at least one checkbox is selected
                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: '../database/depot_submit_change_veh_crew.php', // URL of the PHP script
                    data: formData,
                    dataType: 'json', // Expect a JSON response
                    success: function(response) {
                        if (response.status === 'success') {
                            // Display SweetAlert on success
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload the page after the alert is closed
                                window.location.reload();
                            });
                        } else {
                            // Display SweetAlert on error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle the AJAX request error
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An Network error occurred Please refresh page and try once again:' + error,
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
        $(document).ready(function() {
            $('#sch_change_crew_form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                document.getElementById('submitBtnchange').addEventListener('click', function() {
                    document.getElementById('submitBtnchange').disabled = true;
                });
                // Check if any checkbox is selected
                const isAtLeastOneSelected = $('#change_vehicle').is(':checked') ||
                    $('#change_driver').is(':checked') ||
                    ($('#change_driver2').length && $('#change_driver2').is(':checked')) ||
                    ($('#change_conductor').length && $('#change_conductor').is(':checked'));

                // If no checkbox is selected, show SweetAlert
                if (!isAtLeastOneSelected) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please select at least one field to change before submitting.',
                        confirmButtonText: 'OK'
                    });
                    return; // Prevent form submission
                }

                // Serialize form data if at least one checkbox is selected
                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: '../database/depot_submit_change_veh_crew.php', // URL of the PHP script
                    data: formData,
                    dataType: 'json', // Expect a JSON response
                    success: function(response) {
                        if (response.status === 'success') {
                            // Display SweetAlert on success
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload the page after the alert is closed
                                window.location.reload();
                            });
                        } else {
                            // Display SweetAlert on error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle the AJAX request error
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An Network error occurred Please refresh page and try once again: ' + error,
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
    </script>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>