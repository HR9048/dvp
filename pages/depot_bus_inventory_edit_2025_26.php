<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'WM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>
    <div class="container mt-4" style="width:80%;">
        <h4>Bus Details Form</h4>
        <form id='updateInventoryForm'>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="bus_number" class="form-label">Select Bus Number</label>
                    <select class="form-control" id="bus_number" name="bus_number" required>
                        <option value="">-- Select Bus Number --</option>
                        <?php
                        if ($_SESSION['TYPE'] == 'DEPOT') {
                            $q = mysqli_query($db, "SELECT bus_number 
FROM (
    -- From bus_registration_2025_26
    SELECT bus_number 
    FROM bus_registration_2025_26 
    WHERE division_name = '$division_id' 
      AND depot_name = '$depot_id'
) AS all_buses
WHERE EXISTS (
    SELECT 1 
    FROM `bus_inventory_2025_26` 
    WHERE `bus_inventory_2025_26`.bus_number = all_buses.bus_number 
      AND inventory_date = '2026-03-31' 
      AND deleted != 1
);");
                        } elseif ($_SESSION['TYPE'] == 'DIVISION') {
                            $q = mysqli_query($db, "SELECT bus_number FROM bus_registration_2025_26 WHERE division_name = '$division_id'  AND EXISTS (SELECT 1 FROM `bus_inventory_2025_26` WHERE `bus_inventory_2025_26`.bus_number = bus_registration_2025_26.bus_number AND inventory_date = '2025-03-31' and deleted !=1);");
                        } elseif ($_SESSION['TYPE'] == 'RWY') {
                            $q = mysqli_query($db, "SELECT bus_number FROM bus_registration_2025_26 WHERE EXISTS (SELECT 1 FROM `bus_inventory_2025_26` WHERE `bus_inventory_2025_26`.bus_number = bus_registration_2025_26.bus_number AND inventory_date = '2025-03-31' and deleted !=1);");
                        }
                        while ($row = mysqli_fetch_assoc($q)) {
                            echo '<option value="' . $row['bus_number'] . '">' . $row['bus_number'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div id="fetcheddata"></div>
        </form>
    </div>

    <script>
        $(document).ready(function() {

            // Initialize Select2
            $('#bus_number').select2({
                placeholder: "-- Select Bus Number --",
                allowClear: true,
                width: '100%'
            });

            $(document).ready(function() {

                // Change Event
                $('#bus_number').on('change', function() {

                    let busNumber = $(this).val();

                    // Clear old data
                    $('#fetcheddata').html('');

                    if (!busNumber) return;

                    $.ajax({
                        url: '../includes/backend_data.php',
                        type: 'POST',
                        data: {
                            bus_number: busNumber,
                            action: 'fetch_bus_inventory_details_for_edit'
                        },
                        dataType: 'json',

                        beforeSend: function() {
                            $('#fetcheddata').html(
                                '<div class="text-center p-3">Loading...</div>'
                            );
                        },

                        success: function(response) {

                            if (response.status === 'success') {

                                // Load HTML
                                $('#fetcheddata').html(response.html);

                                // Select2 for dropdowns
                                $('#fetcheddata select.select2').select2({
                                    width: '100%',
                                    placeholder: "Search assembly...",
                                    allowClear: true
                                });

                                // Run field toggle functions after HTML inserted
                                if (typeof toggleSpeedGovernorFields === "function") {
                                    toggleSpeedGovernorFields();
                                }

                                if (typeof toggleACFields === "function") {
                                    toggleACFields();
                                }
                                if (typeof updateLedBoardFields === "function") {
                                    updateLedBoardFields();
                                }
                                if (typeof updateCameraFields === "function") {
                                    updateCameraFields();
                                }
                                if (typeof updatePISFields === "function") {
                                    updatePISFields();
                                }
                                if (typeof updateVLTSFields === "function") {
                                    updateVLTSFields();
                                }
                                if (typeof updateFDASField === "function") {
                                    updateFDASField();
                                }

                            } else {

                                $('#fetcheddata').html(
                                    '<div class="alert alert-danger">' +
                                    response.message +
                                    '</div>'
                                );
                            }
                        },

                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", xhr.responseText);

                            $('#fetcheddata').html(
                                '<div class="alert alert-danger">Error fetching data</div>'
                            );
                        }
                    });

                });

            });

        });
        // submit event for dynamically generated form
        $(document).on("submit", "#updateInventoryForm", function(e) {
            e.preventDefault();

            let form = $(this);

            // 🔥 CONFIRMATION POPUP
            Swal.fire({
                title: "Are you sure?",
                text: "This update is allowed only once. After submission, no further changes will be permitted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Submit",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33"
            }).then((result) => {

                if (!result.isConfirmed) return; // ❌ stop if cancelled

                let formData = form.serialize() + '&action=update_bus_inventory_2025_26';

                // 🔥 Debug log
                let formArray = form.serializeArray();


                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',

                    beforeSend: function() {
                        $('#update_assembly_list')
                            .prop('disabled', true)
                            .text('Updating...');
                    },

                    success: function(response) {

                        $('#update_assembly_list')
                            .prop('disabled', false)
                            .text('Update');

                        if (response.status === 'success') {

                            Swal.fire("Success", response.message, "success")
                                .then(() => {
                                    location.reload();
                                });

                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },

                    error: function(xhr, status, error) {

                        $('#update_assembly_list')
                            .prop('disabled', false)
                            .text('Update');

                        Swal.fire("Error", "AJAX error: " + error, "error");
                    }
                });

            });

        });

        function toggleSpeedGovernorFields() {
            let speedGovernor = document.getElementById("speed_governor")?.value;
            let modelDiv = document.getElementById("speed_governor_model_div");
            let serialDiv = document.getElementById("speed_governor_serial_div");
            let modelField = document.getElementById("speed_governor_model");
            let serialField = document.getElementById("speed_governor_serial_no");

            if (!modelDiv) return;

            if (speedGovernor === "FITTED") {
                modelDiv.style.display = "block";
                serialDiv.style.display = "block";
                modelField.required = true;
                serialField.required = true;
            } else {
                modelDiv.style.display = "none";
                serialDiv.style.display = "none";
                modelField.required = false;
                serialField.required = false;
            }
        }

        function toggleACFields() {
            var busType = document.getElementById("bus_type").value;
            var acUnitContainer = document.getElementById("ac_unit_container");
            var acModelContainer = document.getElementById("ac_model_container");
            var acUnit = document.getElementById("ac_unit");
            var acModel = document.getElementById("ac_model");

            if (busType.toUpperCase() === "AC") {
                acUnitContainer.style.display = "block";
                acModelContainer.style.display = "block";
                acUnit.setAttribute("required", "required");
                acModel.setAttribute("required", "required");
            } else {
                acUnitContainer.style.display = "none";
                acModelContainer.style.display = "none";
                acUnit.removeAttribute("required");
                acModel.removeAttribute("required");
                acUnit.value = "";
                acModel.value = "";
            }
        }

        function updateLedBoardFields() {

            var category = $('#bus_category').val();

            // Hide all child LED fields first
            $('.led-fields').hide().find('select').prop('required', false);

            // Show LED Board Present only for valid categories
            if (
                category === "Jn-NURM Midi City" ||
                category === "Branded DULT City" ||
                category === "Double Door City"
            ) {

                $('#led_board_section').show();
                $('#led_board').prop('required', true); // ✅ required

            } else {

                $('#led_board_section').hide();
                $('#led_board').prop('required', false);

                $('.led-fields').hide().find('select').prop('required', false);
                return;
            }

            // Remove duplicate binding
            $('#led_board').off('change').on('change', function() {

                if ($(this).val() === "YES") {

                    $('.led-fields')
                        .show()
                        .find('select')
                        .prop('required', true);

                    // Hide special fields for non-DULT buses
                    if (
                        category !== "Branded DULT City" &&
                        category !== "Double Door City"
                    ) {
                        $('.dult-only')
                            .hide()
                            .find('select')
                            .prop('required', false);
                    }

                } else {

                    $('.led-fields')
                        .hide()
                        .find('select')
                        .prop('required', false);
                }
            });

            // Trigger existing value
            $('#led_board').trigger('change');
        }

        function updateCameraFields() {
            let emissionNorms = ($('#emission_norms').val() || '').trim();
            let wheelBase = ($('#wheel_base').val() || '').trim();

            if (emissionNorms === "BS-6" || wheelBase === "193 Midi") {
                $('.camera-fields')
                    .show()
                    .find('select')
                    .prop('required', true);
            } else {
                $('.camera-fields')
                    .hide()
                    .find('select')
                    .prop('required', false);
            }
        }


        function updatePISFields() {
            let emissionNorms = ($('#emission_norms').val() || '').trim();
            let wheelBase = ($('#wheel_base').val() || '').trim();

            if (
                emissionNorms === "BS-4" ||
                emissionNorms === "BS-6" ||
                wheelBase === "193 Midi"
            ) {
                $('.pis-fields')
                    .show()
                    .find('select')
                    .prop('required', true);
            } else {
                $('.pis-fields')
                    .hide()
                    .find('select')
                    .prop('required', false);
            }
        }


        function updateVLTSFields() {
            let emissionNorms = ($('#emission_norms').val() || '').trim();
            let vltsPresent = ($('#vlts_unit_present').val() || '').trim();

            if (emissionNorms === "BS-6") {
                $('#vlts_unit_present_section').show();
                $('#vlts_unit_present').prop('required', true);
            } else {
                $('#vlts_unit_present_section').hide();
                $('#vlts_unit_present').prop('required', false);

                $('#vlts_unit_make_section').hide();
                $('#vlts_unit_make').prop('required', false);
                return;
            }

            if (vltsPresent === "YES") {
                $('#vlts_unit_make_section').show();
                $('#vlts_unit_make').prop('required', true);
            } else {
                $('#vlts_unit_make_section').hide();
                $('#vlts_unit_make').prop('required', false);
            }
        }

        // Prevent duplicate binding
        $(document)
            .off('change', '#vlts_unit_present')
            .on('change', '#vlts_unit_present', updateVLTSFields);


        function updateFDASField() {
            let emissionNorms = ($('#emission_norms').val() || '').trim();

            if (emissionNorms === "BS-4" || emissionNorms === "BS-6") {
                $('#fdas_fdss_section').show();
                $('#fdas_fdss_present').prop('required', true);
            } else {
                $('#fdas_fdss_section').hide();
                $('#fdas_fdss_present').prop('required', false);
            }
        }
        // Submit event for dynamically generated form
        $(document).on("submit", "#updateInventoryForm", function(e) {
            e.preventDefault();

            let form = $(this);

            /* =====================================================
               VALIDATE ONLY VISIBLE REQUIRED FIELDS BEFORE CONFIRM
            ===================================================== */
            let isValid = true;
            let firstInvalid = null;

            form.find(':input').each(function() {
                let field = $(this);

                // Skip hidden fields / disabled fields / hidden sections
                if (!field.is(':visible') || field.is(':disabled')) {
                    return true;
                }

                // Check required only
                if (field.prop('required')) {
                    let value = $.trim(field.val());

                    if (value === '' || value === null) {
                        isValid = false;
                        firstInvalid = field;
                        field.addClass('is-invalid');
                    } else {
                        field.removeClass('is-invalid');
                    }
                }
            });

            if (!isValid) {
                Swal.fire({
                    icon: "warning",
                    title: "Missing Required Fields",
                    text: "Please fill all visible required fields before submitting."
                });

                if (firstInvalid) {
                    firstInvalid.focus();
                }

                return;
            }

            /* =====================================================
               CONFIRMATION POPUP
            ===================================================== */
            Swal.fire({
                title: "Are you sure?",
                text: "This update is allowed only once. After submission, no further changes will be permitted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Submit",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33"
            }).then((result) => {

                if (!result.isConfirmed) return;

                let formData = form.serialize() + '&action=update_bus_inventory_2025_26';

                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',

                    beforeSend: function() {
                        $('#update_assembly_list')
                            .prop('disabled', true)
                            .text('Updating...');
                    },

                    success: function(response) {

                        $('#update_assembly_list')
                            .prop('disabled', false)
                            .text('Update');

                        if (response.status === 'success') {

                            Swal.fire("Success", response.message, "success")
                                .then(() => {
                                    location.reload();
                                });

                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },

                    error: function(xhr, status, error) {

                        $('#update_assembly_list')
                            .prop('disabled', false)
                            .text('Update');

                        Swal.fire("Error", "AJAX error: " + error, "error");
                    }
                });

            });
        });
    </script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>