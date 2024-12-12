<?php
include '../pages/session.php';
include '../includes/connection.php';
confirm_logged_in();
// Set the time zone to India/Kolkata
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduleNo = $_POST['scheduleNo'];
    $outDate = $_POST['outDate'];
    $formType = $_POST['formType']; // Identify which form sent the request

    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];
    $lmsdivision = $_SESSION['KMPL_DIVISION'];
    $lmsdepot = $_SESSION['KMPL_DEPOT'];
    // Query to get the vehicles list
    $vehicleQuery = "SELECT bus_number FROM bus_registration WHERE division_name = ? AND depot_name = ?";
    $stmt = $db->prepare($vehicleQuery);
    $stmt->bind_param("ss", $division, $depot);
    $stmt->execute();
    $stmt->bind_result($vehicleNumber);

    // Prepare an array to hold the available vehicles
    $vehicleOptions = [];
    while ($stmt->fetch()) {
        $vehicleOptions[] = $vehicleNumber;
    }
    $stmt->close();

    $query = "SELECT svo.id, svo.driver_token_no_1, svo.driver_token_no_2, svo.conductor_token_no, 
                     svo.driver_1_name, svo.driver_2_name, svo.conductor_name,
                     svo.driver_1_pf, svo.driver_2_pf, svo.conductor_pf_no,
                     sm.sch_arr_time, sm.sch_count, svo.departed_date, svo.dep_time,svo.vehicle_no
              FROM sch_veh_out svo
              JOIN schedule_master sm ON svo.sch_no = sm.sch_key_no AND svo.division_id = sm.division_id AND svo.depot_id = sm.depot_id
              WHERE svo.sch_no = ? AND svo.departed_date = ? AND svo.division_id = ? AND svo.depot_id = ? AND svo.schedule_status='1'";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssss", $scheduleNo, $outDate, $division, $depot);
    $stmt->execute();
    $stmt->bind_result($id, $driverToken1, $driverToken2, $conductorToken, $driverName1, $driverName2, $conductorName, $driver_1_pf, $driver_2_pf, $conductor_pf_no, $schArrTime, $schCount, $departedDate, $departedTime, $vehicle_no);

    if ($stmt->fetch()) {
        echo '<input class="form-control" type="hidden" id="id" name="id" value="' . htmlspecialchars($id) . '" readonly>';
        echo '<input class="form-control" type="hidden" id="driver_1_pf" name="driver_1_pf" value="' . htmlspecialchars($driver_1_pf) . '" readonly>';
        echo '<input class="form-control" type="hidden" id="driver_2_pf" name="driver_2_pf" value="' . htmlspecialchars($driver_2_pf) . '" readonly>';
        echo '<input class="form-control" type="hidden" id="conductor_pf_no" name="conductor_pf_no" value="' . htmlspecialchars($conductor_pf_no) . '" readonly>';
        echo '<input class="form-control" type="hidden" id="vehicle_no" name="vehicle_no" value="' . htmlspecialchars($vehicle_no) . '" readonly>';

        // Checkbox row
        echo '<div class="row mb-3">';
        if ($formType == 'bus') {
            echo '<div style="display: none;"><div class="col-md-3"><input type="checkbox" id="change_vehicle" name="change_vehicle" checked> Change Vehicle</div></div>';
        } else {
            echo '<div style="display: none;"><div class="col-md-3"><input type="checkbox" id="change_vehicle" name="change_vehicle"> Change Vehicle</div></div>';
        }
        if ($formType == 'crew') {
            echo '<div class="col-md-3"><input type="checkbox" id="change_driver" name="change_driver" onclick="toggleSection(\'driver_section\')"> Change Driver</div>';
            if (!empty($driverToken2)) {
                echo '<div class="col-md-3"><input type="checkbox" id="change_driver2" name="change_driver2" onclick="toggleSection(\'driver2_section\')"> Change Driver 2</div>';
            }
            if (!empty($conductorToken)) {
                echo '<div class="col-md-3"><input type="checkbox" id="change_conductor" name="change_conductor" onclick="toggleSection(\'conductor_section\')"> Change Conductor</div>';
            }
        }
        echo '</div>';
        if ($formType == 'bus') {
            echo '<div id="vehicle_section">';
        } else {
            echo '<div id="vehicle_section" style="display: none;" >';
        }
        echo '<div class="col-md-6 d-flex justify-content-between align-items-center" style="max-width: 100%;">';
        echo '<div>';
        echo '<label>Present Vehicle</label>';
        echo '<input type="text" class="form-control" readonly value="' . htmlspecialchars($vehicle_no) . '" >';
        echo '</div>';
        echo '<div>';
        echo '<label>Change Vehicle</label><br>';
        echo '<select class="form-control" id="bus_select" name="bus_select" style="min-width: 50%;">';
        echo '<option value="">Select Vehicle no</option>';
        foreach ($vehicleOptions as $vehicleOption) {
            echo '<option value="' . htmlspecialchars($vehicleOption) . '">' . htmlspecialchars($vehicleOption) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Driver 1 change section
        echo '<div id="driver_section" style="display: none;" >';
        echo '<div class="col-md-6 d-flex justify-content-between align-items-center" style="max-width: 100%;">';
        echo '<div>';
        echo '<label>Present Driver 1</label>';
        echo '<input type="text" class="form-control" readonly value="' . htmlspecialchars($driverToken1) . ' (' . htmlspecialchars($driverName1) . ')">';
        echo '</div>';
        echo '<div>';
        echo '<label>Change Driver 1</label><br>';
        echo '<select class="form-control" id="driver_1_select" name="driver_1_select" style="min-width:50%"><option value="">Select Driver</option></select>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Driver 2 change section
        if (!empty($driverToken2)) {
            echo '<div id="driver2_section" style="display: none;" >';
            echo '<div class="col-md-6 d-flex justify-content-between align-items-center" style="max-width: 100%;">';
            echo '<div>';
            echo '<label>Present Driver 2</label><br>';
            echo '<input type="text" class="form-control" readonly value="' . htmlspecialchars($driverToken2) . ' (' . htmlspecialchars($driverName2) . ')">';
            echo '</div>';
            echo '<div>';
            echo '<label>Change Driver 2</label><br>';
            echo '<select class="form-control" id="driver_2_select" name="driver_2_select" style="min-width:50%"><option value="">Select Driver</option></select>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }

        // Conductor change section
        if (!empty($conductorToken)) {
            echo '<div id="conductor_section" style="display: none;">';
            echo '<div class="col-md-6 d-flex justify-content-between align-items-center" style="max-width: 100%;">';
            echo '<div>';
            echo '<label>Present Conductor</label>';
            echo '<input type="text" class="form-control" readonly value="' . htmlspecialchars($conductorToken) . ' (' . htmlspecialchars($conductorName) . ')">';
            echo '</div>';
            echo '<div>';
            echo '<label>Change Conductor</label>';
            echo '<select class="form-control" id="conductorselect" name="conductorselect" style="min-width:50%"><option value="">Select Conductor</option></select>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }

        // Arrival Time and Submit Section
        echo '<div class="row mt-3">';
        echo '<div class="col-md-6 col-sm-12">';
        echo '<div class="form-group">';
        echo '<label for="arr_time">Change Time/ಬದಲಾವಣೆ ಸಮಯ</label>';
        echo '<input class="form-control" type="time" id="arr_time" name="arr_time" required>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-6 col-sm-12">';
        echo '<div class="form-group">';
        echo '<label for="reason">Reason/ಕಾರಣ</label>';
        if ($formType == 'bus') {
            echo '<select class="form-control" id="reason" name="reason" onchange="handleReasonChange()" required><option value="">Select Reason</option><option value="Vehicle Problem">Vehicle Problem</option><option value="Vehicle Exchange">Vehicle Exchange</option><option value="Others">Others</option></select>';
        } else {
            echo '<select class="form-control" id="reason" name="reason" onchange="handleReasonChange()" required><option value="">Select Reason</option><option value="Crew Health Problem">Crew Health Problem</option><option value="Crew Exchahge">Crew Exchahge</option><option value="ETM Problem">ETM Problem</option><option value="Others">Others</option></select>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>'; // Close row
        echo '<div class="row mt-12" id="otherreason_row" style="display: none;">'; // Initially hidden
        echo '<div class="col">';
        echo '<div class="form-group">';
        echo '<label for="otherreason">Give Reason</label>';
        echo '<textarea class="form-control" id="otherreason" name="otherreason" rows="2"></textarea>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group mt-3">';
        echo '<button type="submit" class="btn btn-primary">Submit</button>';
        echo '</div>';
    } else {
        echo '<p>No details found for this schedule.</p>';
    }

    $stmt->close();
} else {
    header("Location: ../pages/login.php");
    exit;
}
$db->close();
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script>
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        const selectField = section.querySelector('select'); // Get the select field inside the section
        if (section.style.display === 'none') {
            section.style.display = 'block'; // Show section
            selectField.setAttribute('required', true); // Make the field required
        } else {
            section.style.display = 'none'; // Hide section
            selectField.removeAttribute('required'); // Remove the required attribute
        }
    }

    function handleReasonChange() {
        const reasonSelect = document.getElementById('reason');
        const otherReasonRow = document.getElementById('otherreason_row');
        const otherReasonInput = document.getElementById('otherreason');

        if (reasonSelect.value === 'Others') {
            // Show the "Give Reason" input field and make it required
            otherReasonRow.style.display = 'block';
            otherReasonInput.setAttribute('required', 'required');
        } else {
            // Hide the "Give Reason" input field and remove required attribute
            otherReasonRow.style.display = 'none';
            otherReasonInput.removeAttribute('required');
        }
    }

    $(document).ready(function () {
        // Initialize select2 for all the relevant select elements
        $('#bus_select, #driver_1_select, #driver_2_select, #conductorselect').select2({
            placeholder: "Select an option",  // Add placeholder text
            allowClear: true  // Allow clearing the selection
        });

        const division = "<?php echo $lmsdivision; ?>";
        const depot = "<?php echo $lmsdepot; ?>";

        let allDrivers = [];
        let allConductors = [];

        // Fetch driver data from API1 (drivers)
        $.get("http://192.168.1.32:50/data.php", { division: division, depot: depot }, function (response) {
            allDrivers = response.data;  // Store drivers in a variable
            populateSelectOptions();
        });

        // Fetch conductor data from API2 (conductors)
        $.get("http://192.168.1.32/transfer/dvp/database/private_emp_api.php", { division: division, depot: depot }, function (response) {
            allConductors = response.data;  // Store conductors in a variable
            populateSelectOptions();
        });

        // Function to populate all selects with combined data once both responses are received
        function populateSelectOptions() {
            if (allDrivers.length === 0 || allConductors.length === 0) return; // Wait until both data are fetched

            // Combine drivers and conductors into one array
            const combinedOptions = [...allDrivers, ...allConductors];

            // Populate driver_1_select, driver_2_select, and conductorselect
            combinedOptions.forEach(option => {
                const text = `${option.token_number} (${option.EMP_NAME})`;  // Display PF Number and Employee Name
                const id = option.EMP_PF_NUMBER;  // Use PF number as the value (id)

                // Populate both driver_1_select and driver_2_select
                $('#driver_1_select').append(new Option(text, id));
                $('#driver_2_select').append(new Option(text, id));
                // Populate conductorselect
                $('#conductorselect').append(new Option(text, id));
            });
        }

        // Event listeners to update other selects when an option is selected
        $('#driver_1_select').on('change', function () {
            const selectedDriver1 = $(this).val();
            $('#driver_2_select option').each(function () {
                if ($(this).val() === selectedDriver1) {
                    $(this).prop('disabled', true);  // Disable the same driver in the second select
                } else {
                    $(this).prop('disabled', false);  // Enable other options
                }
            });
        });

        $('#driver_2_select').on('change', function () {
            const selectedDriver2 = $(this).val();
            $('#driver_1_select option').each(function () {
                if ($(this).val() === selectedDriver2) {
                    $(this).prop('disabled', true);  // Disable the same driver in the first select
                } else {
                    $(this).prop('disabled', false);  // Enable other options
                }
            });
        });

        $('#conductorselect').on('change', function () {
            const selectedConductor = $(this).val();
            $('#driver_1_select option').each(function () {
                if ($(this).val() === selectedConductor) {
                    $(this).prop('disabled', true);  // Disable the same conductor in the driver select
                } else {
                    $(this).prop('disabled', false);  // Enable other options
                }
            });

            $('#driver_2_select option').each(function () {
                if ($(this).val() === selectedConductor) {
                    $(this).prop('disabled', true);  // Disable the same conductor in the driver select
                } else {
                    $(this).prop('disabled', false);  // Enable other options
                }
            });
        });
    });


</script>