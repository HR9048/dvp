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
        <form id="busForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="bus_number" class="form-label">Select Bus Number</label>
                    <select class="form-control" id="bus_number" name="bus_number" required>
                        <option value="">-- Select Bus Number --</option>
                        <?php
                        if ($_SESSION['TYPE'] == 'DEPOT'){
                        $q = mysqli_query($db, "SELECT bus_number 
FROM (
    -- From bus_registration
    SELECT bus_number 
    FROM bus_registration 
    WHERE division_name = '$division_id' 
      AND depot_name = '$depot_id'
    
    UNION

    -- From bus_transfer_data
    SELECT bus_number 
    FROM bus_transfer_data 
    WHERE order_date > '2025-03-31' 
      AND division = '$division_id' 
      AND from_depot = '$depot_id'
) AS all_buses
WHERE NOT EXISTS (
    SELECT 1 
    FROM bus_inventory 
    WHERE bus_inventory.bus_number = all_buses.bus_number 
      AND inventory_date = '2025-03-31' 
      AND deleted != 1
);");
                        }elseif($_SESSION['TYPE'] == 'DIVISION'){
                            $q = mysqli_query($db, "SELECT bus_number FROM bus_registration WHERE division_name = '$division_id'  AND NOT EXISTS (SELECT 1 FROM bus_inventory WHERE bus_inventory.bus_number = bus_registration.bus_number AND inventory_date = '2025-03-31' and deleted !=1);");
                        }elseif($_SESSION['TYPE'] == 'RWY'){
                            $q = mysqli_query($db, "SELECT bus_number FROM bus_registration WHERE NOT EXISTS (SELECT 1 FROM bus_inventory WHERE bus_inventory.bus_number = bus_registration.bus_number AND inventory_date = '2025-03-31' and deleted !=1);");
                        }
                        while ($row = mysqli_fetch_assoc($q)) {
                            echo '<option value="' . $row['bus_number'] . '">' . $row['bus_number'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="chassis_number" class="form-label">Chassis Number</label>
                    <input type="text" id="chassis_number" name="chassis_number" class="form-control" readonly required>
                </div>
                <div class="col-md-6">
                    <label for="make" class="form-label">Make</label>
                    <input type="text" id="make" name="make" class="form-control" readonly required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="emission_norms" class="form-label">Emission Norms</label>
                    <input type="text" id="emission_norms" name="emission_norms" class="form-control" readonly required>
                </div>
                <div class="col-md-6">
                    <label for="bus_category" class="form-label">Bus Category</label>
                    <input type="text" id="bus_category" name="bus_category" class="form-control" readonly required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="bus_body_builder" class="form-label">Bus Body Builder</label>
                    <input type="text" id="bus_body_builder" name="bus_body_builder" class="form-control" readonly required>
                </div>
                <div class="col-md-6">
                    <label for="seating_capacity" class="form-label">Seating Capacity</label>
                    <input type="text" id="seating_capacity" name="seating_capacity" class="form-control" readonly required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date_of_commission" class="form-label">Date of Commission</label>
                    <input type="date" id="date_of_commission" name="date_of_commission" class="form-control" readonly required>
                </div>
                <div class="col-md-6">
                    <label for="wheel_base" class="form-label">Wheel Base</label>
                    <input type="text" id="wheel_base" name="wheel_base" class="form-control" readonly required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date_of_fc" class="form-label">FC date</label>
                    <input type="date" id="date_of_fc" name="date_of_fc" class="form-control" min="2020-03-31" max="2030-03-31" required>
                </div>
                <div class="col-md-6">
                    <label for="bus_progressive_km" class="form-label">Bus Progresive KM (As on 31-03-2025)</label>
                    <input type="number" id="bus_progressive_km" name="bus_progressive_km" class="form-control" required>
                </div>
            </div>
            <input type="hidden" id="bus_type" name="bus_type">
            <!-- Engine Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="engine_no" class="form-label">Engine No</label>
                    <select class="form-control" id="engine_no" name="engine_no" required>
                        <option value="">-- Select Engine Number --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="engine_no_progressive_km" class="form-label">Engine Progressive KM (As on 31-03-2025)</label>
                    <input type="number" id="engine_no_progressive_km" name="engine_no_progressive_km" class="form-control" required>
                </div>
            </div>
            <!-- FPP/HPP Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fiphpp_no" class="form-label">FIP/HPP No</label>
                    <select class="form-control" id="fiphpp_no" name="fiphpp_no" required>
                        <option value="">-- Select FIP/HPP Number --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="fiphpp_no_progressive_km" class="form-label">FIP/FPP Progressive KM (As on 31-03-2025)</label>
                    <input type="number" id="fiphpp_no_progressive_km" name="fiphpp_no_progressive_km" class="form-control" required>
                </div>
            </div>
            <!-- Gear Box Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="gearbox_no" class="form-label">Gear Box No</label>
                    <select class="form-control" id="gearbox_no" name="gearbox_no" required>
                        <option value="">-- Select Gear Box Number --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="gearbox_no_progressive_km" class="form-label">Gear Box Progressive KM (As on 31-03-2025)</label>
                    <input type="number" id="gearbox_no_progressive_km" name="gearbox_no_progressive_km" class="form-control" required>
                </div>
            </div>
            <!-- Starter Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="starter_no" class="form-label">Starter No</label>
                    <select class="form-control" id="starter_no" name="starter_no" required>
                        <option value="">-- Select Starter Number --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="starter_no_progressive_km" class="form-label">Starter Progressive KM (As on 31-03-2025)</label>
                    <input type="number" id="starter_no_progressive_km" name="starter_no_progressive_km" class="form-control" required>
                </div>
            </div>

            <!-- Alternator Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="alternator_no" class="form-label">Alternator No</label>
                    <select class="form-control" id="alternator_no" name="alternator_no" required>
                        <option value="">-- Select Alternator Number --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="alternator_no_progressive_km" class="form-label">Alternator Progressive KM (As on 31-03-2025)</label>
                    <input type="number" id="alternator_no_progressive_km" name="alternator_no_progressive_km" class="form-control" required>
                </div>
            </div>

            <!-- Rear axel Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="rear_axel_no" class="form-label">Rear Axel No</label>
                    <select class="form-control" id="rear_axel_no" name="rear_axel_no" required>
                        <option value="">-- Select Rear Axel Number --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="rear_axel_no_progressive_km" class="form-label">Rear Axel Progressive KM (As on 31-03-2025)</label>
                    <input type="number" id="rear_axel_no_progressive_km" name="rear_axel_no_progressive_km" class="form-control" required>
                </div>
            </div>

            <!-- Battery 1 Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="battery_1_no" class="form-label">Battery 1 No</label>
                    <select class="form-control" id="battery_1_no" name="battery_1_no" required onchange="initBatterySelection()">
                        <option value="">-- Select Battery 1 Number --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="battery_1_no_progressive_km" class="form-label">Battery 1 Progressive KM (As on 31-03-2025)</label>
                    <input type="number" id="battery_1_no_progressive_km" name="battery_1_no_progressive_km" class="form-control" required>
                </div>
            </div>
            <!-- Battery 2 Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="battery_2_no" class="form-label">Battery 2 No</label>
                    <select class="form-control" id="battery_2_no" name="battery_2_no" required onchange="initBatterySelection()">
                        <option value="">-- Select Battery 2 Number --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="battery_2_no_progressive_km" class="form-label">Battery 2 Progressive KM (As on 31-03-2025)</label>
                    <input type="number" id="battery_2_no_progressive_km" name="battery_2_no_progressive_km" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="speed_governor" class="form-label">Speed Governor</label>
                    <select class="form-control" id="speed_governor" name="speed_governor" required onchange="toggleSpeedGovernorFields()">
                        <option value="">-- Select Speed Governor Condition --</option>
                        <option value="FITTED">FITTED</option>
                        <option value="NOT FITTED">NOT FITTED</option>
                    </select>
                </div>

                <div class="col-md-6" id="speed_governor_model_div" style="display: none;">
                    <label for="speed_governor_model" class="form-label">Speed Governor Model</label>
                    <select class="form-control" id="speed_governor_model" name="speed_governor_model">
                        <option value="">-- Select Speed Governor Model --</option>
                        <option value="ACTIA">ACTIA</option>
                        <option value="PRICOL">PRICOL</option>
                        <option value="ROSMERTA">ROSMERTA</option>
                        <option value="ECU">ECU</option>
                    </select>
                </div>

                <div class="col-md-6" id="speed_governor_serial_div" style="display: none;">
                    <label for="speed_governor_serial_no" class="form-label">Speed Governor Serial No</label>
                    <input type="text" id="speed_governor_serial_no" name="speed_governor_serial_no" class="form-control" oninput="validateAndFormatInput(this)">
                </div>

                <div class="col-md-6" id="ac_unit_container" style="display: none;">
                    <label for="ac_unit" class="form-label">AC Unit</label>
                    <select class="form-control" id="ac_unit" name="ac_unit">
                        <option value="">-- Select AC Unit Condition --</option>
                        <option value="WORKING">WORKING</option>
                        <option value="NOT WORKING">NOT WORKING</option>
                    </select>
                </div>

                <div class="col-md-6" id="ac_model_container" style="display: none;">
                    <label for="ac_model" class="form-label">AC Model</label>
                    <select class="form-control" id="ac_model" name="ac_model">
                        <option value="">-- Select AC Model --</option>
                        <option value="CARRIER">CARRIER</option>
                        <option value="HIGER">HIGER</option>
                        <option value="SPHAROS">SPHAROS</option>
                        <option value="EBERSPAECHER">EBERSAECHER</option>
                        <option value="JTAC">JTAC</option>
                    </select>
                </div>
                <!-- LED Destination Board Present (Initially Hidden) -->
                <div class="col-md-6" id="led_board_section" style="display: none;">
                    <label for="led_board" class="form-label">LED Destination Board Present</label>
                    <select class="form-control" id="led_board" name="led_board">
                        <option value="">-- Select LED Destination Board Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <!-- All LED Board Fields (Initially Hidden) -->
                <div class="col-md-6 led-fields" id="led_board_make_section" style="display: none;">
                    <label for="led_board_make" class="form-label">LED Board Make</label>
                    <select class="form-control" id="led_board_make" name="led_board_make">
                        <option value="">-- Select LED Board Make --</option>
                        <option value="AUTOMATERS">AUTOMATERS</option>
                        <option value="POWER ELECTRONICS">POWER ELECTRONICS</option>
                        <option value="HANNOVER">HANNOVER</option>
                        <option value="ACCOLADE ELE">ACCOLADE ELE</option>
                        <option value="ARGEE">ARGEE</option>
                    </select>
                </div>

                <div class="col-md-6 led-fields" id="led_board_front_section" style="display: none;">
                    <label for="led_board_front" class="form-label">LED Destination Front</label>
                    <select class="form-control" id="led_board_front" name="led_board_front">
                        <option value="">-- Select Board Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6 led-fields dult-only" id="led_board_front_inside_section" style="display: none;">
                    <label for="led_board_front_inside" class="form-label">LED Destination Front Inside</label>
                    <select class="form-control" id="led_board_front_inside" name="led_board_front_inside">
                        <option value="">-- Select Board Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6 led-fields" id="led_board_rear_section" style="display: none;">
                    <label for="led_board_rear" class="form-label">LED Destination Rear</label>
                    <select class="form-control" id="led_board_rear" name="led_board_rear">
                        <option value="">-- Select Board Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6 led-fields dult-only" id="led_board_lhs_outside_section" style="display: none;">
                    <label for="led_board_lhs_outside" class="form-label">LED Destination LHS Outside</label>
                    <select class="form-control" id="led_board_lhs_outside" name="led_board_lhs_outside">
                        <option value="">-- Select Board Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>
                <!-- Camera Fields (Initially Hidden) -->
                <div class="col-md-6 camera-fields" id="camera_f_saloon_section" style="display: none;">
                    <label for="camera_f_saloon" class="form-label">Camera Present Front Saloon</label>
                    <select class="form-control" id="camera_f_saloon" name="camera_f_saloon">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6 camera-fields" id="camera_f_outside_section" style="display: none;">
                    <label for="camera_f_outside" class="form-label">Camera Present Front Outside</label>
                    <select class="form-control" id="camera_f_outside" name="camera_f_outside">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6 camera-fields" id="camera_r_saloon_section" style="display: none;">
                    <label for="camera_r_saloon" class="form-label">Camera Present Rear Saloon</label>
                    <select class="form-control" id="camera_r_saloon" name="camera_r_saloon">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6 camera-fields" id="camera_r_outside_section" style="display: none;">
                    <label for="camera_r_outside" class="form-label">Camera Present Rear Outside</label>
                    <select class="form-control" id="camera_r_outside" name="camera_r_outside">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6 camera-fields" id="camera_monitor_section" style="display: none;">
                    <label for="camera_monitor" class="form-label">Camera Monitor Present</label>
                    <select class="form-control" id="camera_monitor" name="camera_monitor">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6 camera-fields" id="camera_storage_unit_section" style="display: none;">
                    <label for="camera_storage_unit" class="form-label">Camera DVR/Storage Unit Present</label>
                    <select class="form-control" id="camera_storage_unit" name="camera_storage_unit">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <!-- PIS Mike with Amplifier (Initially Hidden) -->
                <div class="col-md-6 pis-fields" id="pis_mike_amplefier_section" style="display: none;">
                    <label for="pis_mike_amplefier" class="form-label">Mike with Amplifier</label>
                    <select class="form-control" id="pis_mike_amplefier" name="pis_mike_amplefier">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <!-- VLTS Unit Present (Initially Hidden) -->
                <div class="col-md-6" id="vlts_unit_present_section" style="display: none;">
                    <label for="vlts_unit_present" class="form-label">VLTS Unit Present</label>
                    <select class="form-control" id="vlts_unit_present" name="vlts_unit_present">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <!-- VLTS Unit Make (Initially Hidden) -->
                <div class="col-md-6" id="vlts_unit_make_section" style="display: none;">
                    <label for="vlts_unit_make" class="form-label">VLTS Unit Make</label>
                    <select class="form-control" id="vlts_unit_make" name="vlts_unit_make">
                        <option value="">-- Select Status --</option>
                        <option value="AUTOMATERS">AUTOMATERS</option>
                        <option value="POWER ELECTRONICS">POWER ELECTRONICS</option>
                        <option value="HANNOVER">HANNOVER</option>
                        <option value="ACCOLADE ELE">ACCOLADE ELE</option>
                        <option value="ARGEE">ARGEE</option>
                    </select>
                </div>

                <!-- FDAS/FDSS Present (Initially Hidden) -->
                <div class="col-md-6" id="fdas_fdss_section" style="display: none;">
                    <label for="fdas_fdss_present" class="form-label">FDAS/FDSS Present</label>
                    <select class="form-control" id="fdas_fdss_present" name="fdas_fdss_present">
                        <option value="">-- Select Status --</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="fire_extinguisher_nos" class="form-label">Fire Extinguisher Nos</label>
                    <select class="form-control" id="fire_extinguisher_nos" name="fire_extinguisher_nos" required>
                        <option value="">-- Select Status --</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="NIL">NIL</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="fire_extinguisher_total_kg" class="form-label">Fire Extinguisher Total in Kgs</label>
                    <input type="number" class="form-control" id="fire_extinguisher_total_kg" name="fire_extinguisher_total_kg" required min="0" step="any">
                    </div>

                <div class="col-md-6">
                    <label for="first_aid_box_status" class="form-label">First Aid Box</label>
                    <select class="form-control" id="first_aid_box_status" name="first_aid_box_status" required>
                        <option value="">-- Select Status --</option>
                        <option value="FITTED WITH KIT">FITTED WITH KIT</option>
                        <option value="FITTED WITHOUT KIT">FITTED WITHOUT KIT</option>
                        <option value="NOT FITTED">NOT FITTED</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="scrap_status" id="scrap_status" value="0">

            <div class="row mb-3">
                <div class="col-md-12 text-center">
                    <button type="button" class="btn btn-primary" onclick="submitbusinventory()">Submit</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#bus_number').select2({
                placeholder: "-- Select Bus Number --",
                allowClear: true,
                width: '100%'
            });
            $('#fiphpp_no').select2({
                allowClear: true,
                width: '100%'
            });
            $('#engine_no').select2({
                allowClear: true,
                width: '100%'
            });
            $('#gearbox_no').select2({
                allowClear: true,
                width: '100%'
            });
            $('#starter_no').select2({
                allowClear: true,
                width: '100%'
            });
            $('#alternator_no').select2({
                allowClear: true,
                width: '100%'
            });
            $('#rear_axel_no').select2({
                allowClear: true,
                width: '100%'
            });
            $('#battery_1_no').select2({
                allowClear: true,
                width: '100%'
            });
            $('#battery_2_no').select2({
                allowClear: true,
                width: '100%'
            });
            loadStartter();
            loadAlternator();
            loadReaeAxel();
            loadBattery();
            initBatterySelection();
        });
        

        $('#bus_number').on('change', function() {
            var bus_number = $(this).val();
            if (bus_number !== '') {
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: {
                        action: 'get_bus_details',
                        bus_number: bus_number
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#chassis_number').val(response.data.chassis_number);
                            $('#make').val(response.data.make);
                            $('#emission_norms').val(response.data.emission_norms);
                            $('#bus_category').val(response.data.bus_category);
                            $('#bus_body_builder').val(response.data.bus_body_builder);
                            $('#seating_capacity').val(response.data.seating_capacity);
                            $('#date_of_commission').val(response.data.date_of_commission);
                            $('#wheel_base').val(response.data.wheel_base);
                            $('#bus_type').val(response.data.bus_type);
                            toggleACFields();
                            updateLedBoardFields();
                            updateCameraFields();
                            updatePISFields();
                            updateVLTSFields();
                            updateFDASField();
                            loadEngines(response.data.make, response.data.emission_norms);
                            loadFipHpp(response.data.make, response.data.emission_norms);
                            loadGearBox(response.data.make, response.data.emission_norms);
                        }
                    }
                });
            } else {
                // Reset fields if no bus is selected
                $('#busForm input').val('');
            }
        });

        function loadEngines(make, norms) {
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "get_engine_details",
                    make: make,
                    norms: norms
                },
                success: function(data) {
                    $("#engine_no").html(data);
                }
            });
        }

        function loadFipHpp(make, norms) {
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "get_fiphpp_details",
                    make: make,
                    norms: norms
                },
                success: function(data) {
                    $("#fiphpp_no").html(data);
                }
            });
        }

        function loadGearBox(make, norms) {
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "get_gearbox_details",
                    make: make,
                    norms: norms
                },
                success: function(data) {
                    $("#gearbox_no").html(data);
                }
            });
        }

        function loadStartter() {
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "get_starter_details",
                },
                success: function(data) {
                    $("#starter_no").html(data);
                }
            });
        }

        function loadAlternator() {
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "get_alternator_details",
                },
                success: function(data) {
                    $("#alternator_no").html(data);
                }
            });
        }

        function loadReaeAxel() {
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "get_rearaxel_details",
                },
                success: function(data) {
                    $("#rear_axel_no").html(data);
                }
            });
        }

        function loadBattery() {
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "get_battery_details",
                },
                success: function(data) {
                    $("#battery_1_no").html(data);
                    $("#battery_2_no").html(data);
                }
            });
        }

        function validateAndFormatInput(inputField) {
            let value = inputField.value;

            // Allow only letters and numbers, remove spaces and special characters
            value = value.replace(/[^a-zA-Z0-9]/g, '');

            // Convert letters to uppercase
            inputField.value = value.toUpperCase();
        }

        function toggleSpeedGovernorFields() {
            let speedGovernor = document.getElementById("speed_governor").value;
            let modelDiv = document.getElementById("speed_governor_model_div");
            let serialDiv = document.getElementById("speed_governor_serial_div");
            let modelField = document.getElementById("speed_governor_model");
            let serialField = document.getElementById("speed_governor_serial_no");

            if (speedGovernor === "FITTED") {
                modelDiv.style.display = "block";
                serialDiv.style.display = "block";
                modelField.setAttribute("required", "required");
                serialField.setAttribute("required", "required");
            } else {
                modelDiv.style.display = "none";
                serialDiv.style.display = "none";
                modelField.removeAttribute("required");
                serialField.removeAttribute("required");
                modelField.value = "";
                serialField.value = "";
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

            // Reset all fields
            $('.led-fields').hide().find('select').prop('required', false).val('');
            $('#led_board_section').hide().find('select').val('');

            if (category === "Jn-NURM Midi City" || category === "Branded DULT City") {
                $('#led_board_section').show(); // Show LED Board Present field
            }

            $('#led_board').change(function() {
                if ($(this).val() === "YES") {
                    $('.led-fields').show().find('select').prop('required', true);

                    // If bus category is NOT "DULT City", hide Front Inside and LHS Outside
                    if (category !== "Branded DULT City") {
                        $('.dult-only').hide().find('select').prop('required', false).val('');
                    }
                } else {
                    $('.led-fields').hide().find('select').prop('required', false).val('');
                }
            });
        }
        $('#bus_number').on('change', function() {
            $('#led_board').val('').trigger('change');
        });

        function updateCameraFields() {
            var emissionNorms = $('#emission_norms').val();
            var wheelBase = $('#wheel_base').val();

            // Check condition for showing/hiding fields
            if (emissionNorms === "BS-6" || wheelBase === "193 Midi") {
                $('.camera-fields').show().find('select').prop('required', true);
            } else {
                $('.camera-fields').hide().find('select').prop('required', false).val('');
            }
        }

        function updatePISFields() {
            var emissionNorms = $('#emission_norms').val();
            var wheelBase = $('#wheel_base').val();

            // Show PIS fields only if emission norms is BS-4, BS-6, or wheel_base is 193 Midi
            if (emissionNorms === "BS-4" || emissionNorms === "BS-6" || wheelBase === "193 Midi") {
                $('.pis-fields').show().find('select').prop('required', true);
            } else {
                $('.pis-fields').hide().find('select').prop('required', false).val('');
            }
        }

        function updateVLTSFields() {
            var emissionNorms = $('#emission_norms').val();
            var vltsPresent = $('#vlts_unit_present').val();

            // Show 'VLTS Unit Present' if emission_norms is BS-6
            if (emissionNorms === "BS-6") {
                $('#vlts_unit_present_section').show();
                $('#vlts_unit_present').prop('required', true);
            } else {
                $('#vlts_unit_present_section').hide();
                $('#vlts_unit_present').prop('required', false).val('');
                $('#vlts_unit_make_section').hide(); // Hide VLTS Make when BS-6 is not selected
                $('#vlts_unit_make').prop('required', false).val('');
            }

            // Show 'VLTS Unit Make' only if 'VLTS Unit Present' is YES
            if (vltsPresent === "YES") {
                $('#vlts_unit_make_section').show();
                $('#vlts_unit_make').prop('required', true);
            } else {
                $('#vlts_unit_make_section').hide();
                $('#vlts_unit_make').prop('required', false).val('');
            }
        }
        $('#vlts_unit_present').on('change', updateVLTSFields);

        function updateFDASField() {
            var emissionNorms = $('#emission_norms').val();

            // Show the field if emission norms is BS-4 or BS-6
            if (emissionNorms === "BS-4" || emissionNorms === "BS-6") {
                $('#fdas_fdss_section').show();
                $('#fdas_fdss_present').prop('required', true);
            } else {
                $('#fdas_fdss_section').hide();
                $('#fdas_fdss_present').prop('required', false).val('');
            }
        }

        function fetchProgressiveKM(partType, partId) {
            if (partId !== "") {
                $.ajax({
                    url: "../includes/backend_data.php", // PHP file to process the request
                    type: "POST",
                    data: {
                        action: "getProgressiveKMofParts",
                        part_type: partType,
                        part_id: partId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === "success") {
                            $("#" + partType + "_progressive_km").val(response.progressive_km);
                        } else {
                            $("#" + partType + "_progressive_km").val("");
                            alert("No data found for the selected part.");
                        }
                    },
                    error: function() {
                        alert("Error fetching data. Please try again.");
                    }
                });
            } else {
                $("#" + partType + "_progressive_km").val("");
            }
        }

        // Apply event listener for all part number dropdowns
        $("#engine_no, #fiphpp_no, #gearbox_no, #starter_no, #alternator_no, #rear_axel_no, #battery_1_no, #battery_2_no").on("change", function() {
            let partType = $(this).attr("id"); // Get the ID of the select element
            let partId = $(this).val(); // Get the selected part ID
            fetchProgressiveKM(partType, partId);
        });


        //on click submit of for valide the form data and call the ajax request with a action
        function submitbusinventory() {
            var submitBtn = $('.btn[onclick="submitbusinventory()"]');
            submitBtn.prop('disabled', true).html('Submitting...'); // Disable + change text
            var requiredFields = [];
            $('#busForm input[required], #busForm select[required]').each(function() {
                if ($(this).val() === "") {
                    var label = $("label[for='" + $(this).attr("id") + "']").text();
                    requiredFields.push(label);
                }
            });
            if (requiredFields.length > 0) {
                submitBtn.prop('disabled', false).html('Submit'); // Re-enable + reset text
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Fields',
                    html: "Please fill in the following fields:<br>" + requiredFields.join("<br>"),
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            // Check if the date is in the future
            var dateOfRC = new Date($('#date_of_fc').val());
            //current date = 2025-03-31
            var currentDate = new Date('2030-03-31');
            var pastDate = new Date('2020-03-31');
            //var currentDate = new Date();
            if (dateOfRC > currentDate) {
                submitBtn.prop('disabled', false).html('Submit'); // Re-enable + reset text
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date',
                    text: 'Date of RC cannot be greater then 5 years.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            if (dateOfRC < pastDate) {
                submitBtn.prop('disabled', false).html('Submit'); // Re-enable + reset text
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date',
                    text: 'Date of RC cannot be older than 5 years.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            // Check if the progressive KM is greater than 0
            var progressiveKMs = [
                'engine_no_progressive_km',
                'fiphpp_no_progressive_km',
                'gearbox_no_progressive_km',
                'starter_no_progressive_km',
                'alternator_no_progressive_km',
                'rear_axel_no_progressive_km',
                'battery_1_no_progressive_km',
                'battery_2_no_progressive_km'
            ];
            for (var i = 0; i < progressiveKMs.length; i++) {
                var kmValue = $('#' + progressiveKMs[i]).val();
                if (kmValue <= 0) {
                    submitBtn.prop('disabled', false).html('Submit'); // Re-enable + reset text
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid KM Value',
                        text: 'Progressive KM must be greater than 0.',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }
            }
            var form = document.getElementById("busForm");

            var formData = new FormData(form);
            formData.append("action", "submit_bus_inventory");
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json", // important!
                success: function(response) {
                    if (response.status === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            confirmButtonColor: '#3085d6'
                            //after conformation reload the page
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    } else {
                        submitBtn.prop('disabled', false).html('Submit'); // Re-enable + reset text
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    submitBtn.prop('disabled', false).html('Submit'); // Re-enable + reset text
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong. Please try again later.',
                        footer: 'Details: ' + error,
                        confirmButtonColor: '#3085d6'
                    });
                }
            });

        }

        function initBatterySelection() {
            const battery1 = document.getElementById("battery_1_no");
            const battery2 = document.getElementById("battery_2_no");

            if (!battery1 || !battery2) return; // Safety check

            function handleBatteryChange() {
                const selected1 = battery1.value;
                const selected2 = battery2.value;

                // Enable all options first
                Array.from(battery1.options).forEach(opt => opt.disabled = false);
                Array.from(battery2.options).forEach(opt => opt.disabled = false);

                // Disable selected value in the opposite dropdown
                if (selected1) {
                    const opt2 = battery2.querySelector(`option[value="${selected1}"]`);
                    if (opt2) opt2.disabled = true;
                }

                if (selected2) {
                    const opt1 = battery1.querySelector(`option[value="${selected2}"]`);
                    if (opt1) opt1.disabled = true;
                }

                // Prevent same selection
                if (selected1 && selected1 === selected2) {
                    alert("Battery 1 and Battery 2 cannot be the same.");
                    // Reset the one that was changed most recently
                    if (document.activeElement === battery1) {
                        battery1.value = "";
                    } else {
                        battery2.value = "";
                    }
                    handleBatteryChange(); // Re-run to update disabled options
                }
            }

            battery1.addEventListener("change", handleBatteryChange);
            battery2.addEventListener("change", handleBatteryChange);

            // Run once to handle pre-selected or default states
            handleBatteryChange();
        }
    </script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>