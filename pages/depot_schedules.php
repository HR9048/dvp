<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/depot_top.php';

$query = 'SELECT ID, t.TYPE
            FROM users u
            JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DIVISION') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to Division Page');
                window.location = 'division.php';
              </script>";
    } elseif ($Aa == 'HEAD-OFFICE') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to Head Office Page');
                window.location = 'index.php';
              </script>";
    } elseif ($_SESSION['TYPE'] == 'DEPOT') {
        if ($_SESSION['JOB_TITLE'] == 'Mech') {
            echo "<script type='text/javascript'>
                    alert('Restricted Page! You will be redirected to Mech Page');
                    window.location = '../includes/depot_verify.php';
                  </script>";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input data
    $sch_key_no = mysqli_real_escape_string($db, $_POST['sch_key_no']);
    $sch_abbr = mysqli_real_escape_string($db, $_POST['sch_abbr']);
    $sch_km = mysqli_real_escape_string($db, $_POST['sch_km']);
    $sch_dep_time = mysqli_real_escape_string($db, $_POST['sch_dep_time']);
    $sch_arr_time = mysqli_real_escape_string($db, $_POST['sch_arr_time']);
    $sch_count = mysqli_real_escape_string($db, $_POST['sch_count']);
    $service_class_id = mysqli_real_escape_string($db, $_POST['service_class_id']);
    $service_type_id = mysqli_real_escape_string($db, $_POST['service_type_id']);
    $number_of_buses = mysqli_real_escape_string($db, $_POST['number_of_buses']);

    $bus_number_1 = mysqli_real_escape_string($db, $_POST['bus_number_1']);
    $bus_make_1 = mysqli_real_escape_string($db, $_POST['make1']);
    $bus_emission_norms_1 = mysqli_real_escape_string($db, $_POST['emission_norms1']);

    $bus_number_2 = isset($_POST['bus_number_2']) ? mysqli_real_escape_string($db, $_POST['bus_number_2']) : null;
    $bus_make_2 = isset($_POST['make2']) ? mysqli_real_escape_string($db, $_POST['make2']) : null;
    $bus_emission_norms_2 = isset($_POST['emission_norms2']) ? mysqli_real_escape_string($db, $_POST['emission_norms2']) : null;
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $username3 = $_SESSION['USERNAME'];

    // Prepare driver details
    $driver_details = [];
    for ($i = 1; $i <= 2; $i++) {
        $number_of_drivers = mysqli_real_escape_string($db, $_POST["number_of_drivers_bus_$i"]);
        for ($j = 1; $j <= $number_of_drivers; $j++) {
            $driver_token = mysqli_real_escape_string($db, $_POST["driver_token_{$i}_{$j}"]);
            $driver_pf = mysqli_real_escape_string($db, $_POST["pf_no_d{$i}_{$j}"]);
            $driver_name = mysqli_real_escape_string($db, $_POST["driver_{$i}_{$j}_name"]);
            $driver_details["driver_token_{$i}_{$j}"] = $driver_token;
            $driver_details["driver_pf_{$i}_{$j}"] = $driver_pf;
            $driver_details["driver_name_{$i}_{$j}"] = $driver_name;
        }
    }

    // Add half reliever data to driver details
    $half_releiver_driver_1_token = mysqli_real_escape_string($db, $_POST["half_releiver_driver_1_token"]);
    $half_releiver_driver_1_pf = mysqli_real_escape_string($db, $_POST["half_releiver_driver_1_pf"]);
    $half_releiver_driver_1_name = mysqli_real_escape_string($db, $_POST["half_releiver_driver_1_name"]);

    $half_releiver_driver_2_token = isset($_POST['half_releiver_driver_2_token']) ? mysqli_real_escape_string($db, $_POST['half_releiver_driver_2_token']) : null;
    $half_releiver_driver_2_pf = isset($_POST['half_releiver_driver_2_pf']) ? mysqli_real_escape_string($db, $_POST['half_releiver_driver_2_pf']) : null;
    $half_releiver_driver_2_name = isset($_POST['half_releiver_driver_2_name']) ? mysqli_real_escape_string($db, $_POST['half_releiver_driver_2_name']) : null;

    $driver_details["half_releiver_token_1"] = $half_releiver_driver_1_token;
    $driver_details["half_releiver_pf_1"] = $half_releiver_driver_1_pf;
    $driver_details["half_releiver_name_1"] = $half_releiver_driver_1_name;

    $driver_details["half_releiver_token_2"] = $half_releiver_driver_2_token;
    $driver_details["half_releiver_pf_2"] = $half_releiver_driver_2_pf;
    $driver_details["half_releiver_name_2"] = $half_releiver_driver_2_name;

    // Check for duplicate schedule key number within the same division and depot
    $stmt = mysqli_prepare($db, "SELECT * FROM schedule_master WHERE sch_key_no = ? AND division_id = ? AND depot_id = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $sch_key_no, $division_id, $depot_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "<script>alert('Schedule key number is already registered. Please select another schedule key number.'); window.history.back();</script>";
        exit;
    }
    mysqli_stmt_close($stmt);

    // Check for duplicate bus numbers
    $bus_numbers = array_filter([$bus_number_1, $bus_number_2]);
    $bus_number_placeholders = implode(',', array_fill(0, count($bus_numbers), '?'));

    $stmt = mysqli_prepare($db, "SELECT * FROM schedule_master WHERE bus_number_1 IN ($bus_number_placeholders) OR bus_number_2 IN ($bus_number_placeholders)");
    $merged_bus_number = array_merge($bus_numbers, $bus_numbers);
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($bus_numbers) * 2), ...$merged_bus_number);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "<script>alert('Duplicate bus number found. Please check the bus numbers.'); window.location.href = 'depot_schedules.php';</script>";
        exit;
    }
    mysqli_stmt_close($stmt);

    // Check for duplicate PF numbers
    $pf_numbers = [];
    foreach ($driver_details as $key => $value) {
        if (strpos($key, 'driver_pf_') !== false && strpos($key, 'half_releiver_pf') === false) {
            $pf_numbers[] = $value;
        }
    }
    $pf_number_placeholders = implode(',', array_fill(0, count($pf_numbers), '?'));

    $stmt = mysqli_prepare($db, "SELECT * FROM schedule_master WHERE driver_pf_1_1 IN ($pf_number_placeholders)
                              OR driver_pf_1_2 IN ($pf_number_placeholders)
                              OR driver_pf_1_3 IN ($pf_number_placeholders)
                              OR driver_pf_2_1 IN ($pf_number_placeholders)
                              OR driver_pf_2_2 IN ($pf_number_placeholders)
                              OR driver_pf_2_3 IN ($pf_number_placeholders)
                              OR half_releiver_pf_1 IN ($pf_number_placeholders)
                              OR half_releiver_pf_2 IN ($pf_number_placeholders)");
    $merged_pf_numbers = array_merge($pf_numbers, $pf_numbers, $pf_numbers, $pf_numbers, $pf_numbers, $pf_numbers, $pf_numbers, $pf_numbers);
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($pf_numbers) * 8), ...$merged_pf_numbers);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "<script>alert('Duplicate PF number found. Please check the PF numbers.'); window.location.href = 'depot_schedules.php';</script>";
        exit;
    }
    mysqli_stmt_close($stmt);
    $half_releiver_pfs = [$half_releiver_driver_1_pf, $half_releiver_driver_2_pf];
    foreach ($half_releiver_pfs as $pf) {
        if ($pf !== null) {
            $stmt = mysqli_prepare($db, "SELECT SUM(service_type_id) AS total_service_days FROM schedule_master WHERE half_releiver_pf_1 = ? OR half_releiver_pf_2 = ?");
            mysqli_stmt_bind_param($stmt, "ss", $pf, $pf);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $total_service_days);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            if ($total_service_days >= 6) {
                echo "<script>alert('Half reliever quota max of 6 days exceeded for PF number $pf. Please select another half reliever.'); window.location.href = 'depot_schedules.php';</script>";
                exit;
            }
        }
    }
    // Check for half reliever quota
    $half_releiver_pf_numbers = array_filter([$half_releiver_driver_1_pf, $half_releiver_driver_2_pf]);

    foreach ($half_releiver_pf_numbers as $pf_number) {
        if ($pf_number !== null) {
            $stmt = mysqli_prepare($db, "SELECT SUM(service_class_id) as total_service_class_id FROM schedule_master WHERE half_releiver_pf_1 = ? OR half_releiver_pf_2 = ?");
            mysqli_stmt_bind_param($stmt, 'ss', $pf_number, $pf_number);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $total_service_class_id);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            // Add the current form's service class ID
            $total_service_class_id += $service_class_id;

            if ($total_service_class_id > 6) {
                echo "<script>alert('The selected service class and the selected half reliever PF number (" . htmlspecialchars($pf_number) . ") exceeding the max of 6 days allocation. Please select another service class or other PF number.'); window.location.href = 'depot_schedules.php';</script>";
                exit;
            }
        }
    }


    // Prepare and bind parameters for insertion
    $stmt = mysqli_prepare($db, "INSERT INTO schedule_master (
        division_id,depot_id,username,
        sch_key_no, sch_abbr, sch_km, sch_dep_time, sch_arr_time, sch_count, 
        service_class_id, service_type_id, number_of_buses, 
        bus_number_1, bus_make_1, bus_emission_norms_1, 
        bus_number_2, bus_make_2, bus_emission_norms_2,
        driver_token_1_1, driver_pf_1_1, driver_name_1_1,
        driver_token_1_2, driver_pf_1_2, driver_name_1_2,
        driver_token_1_3, driver_pf_1_3, driver_name_1_3,
        half_releiver_token_1, half_releiver_pf_1, half_releiver_name_1,
        driver_token_2_1, driver_pf_2_1, driver_name_2_1,
        driver_token_2_2, driver_pf_2_2, driver_name_2_2,
        driver_token_2_3, driver_pf_2_3, driver_name_2_3,
        half_releiver_token_2, half_releiver_pf_2, half_releiver_name_2
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )");

    mysqli_stmt_bind_param(
        $stmt,
        "iisssssssiiissssssssssssssssssssssssssssss",
        $division_id,
        $depot_id,
        $username3,
        $sch_key_no,
        $sch_abbr,
        $sch_km,
        $sch_dep_time,
        $sch_arr_time,
        $sch_count,
        $service_class_id,
        $service_type_id,
        $number_of_buses,
        $bus_number_1,
        $bus_make_1,
        $bus_emission_norms_1,
        $bus_number_2,
        $bus_make_2,
        $bus_emission_norms_2,
        $driver_details["driver_token_1_1"],
        $driver_details["driver_pf_1_1"],
        $driver_details["driver_name_1_1"],
        $driver_details["driver_token_1_2"],
        $driver_details["driver_pf_1_2"],
        $driver_details["driver_name_1_2"],
        $driver_details["driver_token_1_3"],
        $driver_details["driver_pf_1_3"],
        $driver_details["driver_name_1_3"],
        $driver_details["half_releiver_token_1"],
        $driver_details["half_releiver_pf_1"],
        $driver_details["half_releiver_name_1"],
        $driver_details["driver_token_2_1"],
        $driver_details["driver_pf_2_1"],
        $driver_details["driver_name_2_1"],
        $driver_details["driver_token_2_2"],
        $driver_details["driver_pf_2_2"],
        $driver_details["driver_name_2_2"],
        $driver_details["driver_token_2_3"],
        $driver_details["driver_pf_2_3"],
        $driver_details["driver_name_2_3"],
        $driver_details["half_releiver_token_2"],
        $driver_details["half_releiver_pf_2"],
        $driver_details["half_releiver_name_2"]
    );

    // Execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('New schedule added successfully'); window.location.href = 'depot_schedules.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($db) . "'); window.location.href = 'depot_schedules.php';</script>";
    }
    // Close statement and database connection
    mysqli_stmt_close($stmt);
}
$division = $_SESSION['KMPL_DIVISION'];
$depot = $_SESSION['KMPL_DEPOT'];
?>
<style>
    .modal-dialog-custom {
        max-width: 80%;
    }
</style>


<div class="container mt-5">
    <!-- Button to open the modal -->
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addScheduleModal">
        Add Schedule
    </button>

    <!-- Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">Add Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form inside the modal -->
                    <form id="add-schedule-form" method="post">
                        <div class="container">
                            <input type="hidden" id="schedule_id" name="schedule_id">
                            <!-- Hidden field for schedule ID -->
                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="sch_key_no">Schedule Key No:</label>
                                        <input type="text" id="sch_key_no" name="sch_key_no" class="form-control"
                                            required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="sch_abbr">Schedule Abbreviation:</label>
                                        <input type="text" id="sch_abbr" name="sch_abbr" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="sch_km">Schedule KM:</label>
                                        <input type="number" step="0.01" id="sch_km" name="sch_km" class="form-control"
                                            required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="sch_dep_time">Schedule Departure Time:</label>
                                        <input type="time" id="sch_dep_time" name="sch_dep_time" class="form-control"
                                            required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="sch_arr_time">Schedule Arrival Time:</label>
                                        <input type="time" id="sch_arr_time" name="sch_arr_time" class="form-control"
                                            required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="sch_count">Schedule Count:</label>
                                        <input type="number" id="sch_count" name="sch_count" class="form-control"
                                            required min="1" max="3" oninput="updateScheduleFields()">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="service_class_id">Service Class:</label>
                                        <select id="service_class_id" name="service_class_id" class="form-control"
                                            required>
                                            <!-- Options for Service Class ID -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="service_type_id">Service Type:</label>
                                        <select id="service_type_id" name="service_type_id" class="form-control"
                                            required>
                                            <!-- Options for Service Type ID -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="number_of_buses">Number of Buses:</label>
                                        <input type="number" id="number_of_buses" name="number_of_buses"
                                            class="form-control" min="1" max="2" required oninput="updateBusFields()">
                                    </div>
                                </div>
                            </div>
                            <div id="bus-fields"></div>
                            <div id="driver-fields"></div> <!-- Placeholder for driver fields -->
                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="half_releiver">Number of half reliver driver:</label>
                                        <input type="number" id="half_releiver" name="half_releiver"
                                            class="form-control" min="1" max="2" required
                                            oninput="updateHalfReleiverFields()">
                                    </div>
                                </div>
                            </div>
                            <div id="half-releiver-fields"></div>
                            <!-- Confirmation Checkbox -->
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="confirm-fields" required>
                                    <label class="form-check-label" for="confirm-fields">All the fields are
                                        correct</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-custom btn-block">Add Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>




    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script>
        var searchedToken = ''; // Global variable to store the searched token
        function updateScheduleFields() {
            var scheduleCount = parseInt(document.getElementById('sch_count').value);
            // Ensure the number of drivers does not exceed the allowed limits
            if (scheduleCount > 2) {
                alert('Only 2 schedules are allowed for a single route.');
                document.getElementById('sch_count').value = '';
            }
        }
        function updateDriverFields() {
            var numberOfBuses = parseInt(document.getElementById('number_of_buses').value);
            var scheduleCount = parseInt(document.getElementById('sch_count').value);
            var driverFields = document.getElementById('driver-fields');
            driverFields.innerHTML = ''; // Clear previous driver fields

            var totalDrivers = 0;
            var maxDriversPerBus = (numberOfBuses === 1) ? 2 : 3;

            for (var i = 1; i <= numberOfBuses; i++) {
                var numberOfDrivers = parseInt(document.getElementById('number_of_drivers_bus_' + i).value) || 0;

                // Ensure the number of drivers does not exceed the allowed limits
                if (numberOfBuses === 1 && numberOfDrivers > 2) {
                    alert('Only 2 drivers are allowed for a single bus.');
                    document.getElementById('number_of_drivers_bus_' + i).value = '';
                    continue;
                } else if (numberOfBuses === 2 && numberOfDrivers > 2 && scheduleCount == 1) {
                    alert('Only 2 drivers are allowed for a single bus.');
                    document.getElementById('number_of_drivers_bus_' + i).value = '';
                    continue;
                } else if (numberOfBuses === 2 && numberOfDrivers > 2 && scheduleCount == 2) {
                    alert('Only 2 drivers are allowed for a single bus.');
                    document.getElementById('number_of_drivers_bus_' + i).value = '';
                    continue;
                } else if (numberOfDrivers > 2) {
                    alert('Only 2 drivers are allowed for a single bus.');
                    document.getElementById('number_of_drivers_bus_' + i).value = '';
                    continue;
                } else if (numberOfBuses >= 2 && scheduleCount >= 2) {
                    totalDrivers += numberOfDrivers;
                    if (totalDrivers > 4) {
                        alert('The total number of drivers cannot exceed 4.');
                        document.getElementById('number_of_drivers_bus_' + i).value = '';
                        totalDrivers -= numberOfDrivers;
                        continue;
                    }
                }


                for (var j = 1; j <= numberOfDrivers; j++) {
                    var driverFieldHTML = `
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="driver_token_${i}_${j}">Driver Token Bus ${i} Driver ${j}:</label>
                        <input type="number" id="driver_token_${i}_${j}" name="driver_token_${i}_${j}" class="form-control" required>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="pf_no_d${i}_${j}">Driver PF Bus ${i} Driver ${j}:</label>
                        <input type="text" id="pf_no_d${i}_${j}" name="pf_no_d${i}_${j}" class="form-control" readonly>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="driver_${i}_${j}_name">Driver Bus ${i} Driver ${j} Name:</label>
                        <input type="text" id="driver_${i}_${j}_name" name="driver_${i}_${j}_name" class="form-control" readonly>
                    </div>
                </div>
            </div>`;
                    driverFields.insertAdjacentHTML('beforeend', driverFieldHTML);
                }
            }

            // Reattach event listeners for the new fields
            reattachEventListeners();
        }


        function updateBusFields() {
            var numberOfBuses = document.getElementById('number_of_buses').value;

            // Check if the number of buses exceeds the limit
            if (numberOfBuses > 2) {
                alert('Only 2 buses are allowed for a route.');
                document.getElementById('number_of_buses').value = ''; // Set the value back to 2
                return; // Exit the function
            }

            var busFields = document.getElementById('bus-fields');
            busFields.innerHTML = ''; // Clear previous bus fields

            for (var i = 1; i <= numberOfBuses; i++) {
                var busFieldHTML = `
        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label for="bus_number_${i}">Bus Number ${i}:</label>
                    <input type="text" id="bus_number_${i}" name="bus_number_${i}" class="form-control" required oninput="this.value = this.value.toUpperCase()" onChange="searchBus(${i})">
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="make${i}">Bus Make ${i}:</label>
                    <input type="text" id="make${i}" name="make${i}" class="form-control" readonly>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="emission_norms${i}">Bus Norm ${i}:</label>
                    <input type="text" id="emission_norms${i}" name="emission_norms${i}" class="form-control" readonly>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="number_of_drivers_bus_${i}">Number of Drivers for Bus ${i}:</label>
                    <input type="number" id="number_of_drivers_bus_${i}" name="number_of_drivers_bus_${i}" class="form-control" min="1" max="6" required oninput="updateDriverFields()">
                </div>
            </div>
        </div>`;
                busFields.insertAdjacentHTML('beforeend', busFieldHTML);
            }
            updateDriverFields(); // Ensure driver fields are updated based on the new bus fields
        }
        function updateHalfReleiverFields() {
            var halfReleiverCount = parseInt(document.getElementById('half_releiver').value);
            if (halfReleiverCount > 2) {
                alert('Only 2 half releiver divers are allowed for a single route.');
                document.getElementById('half_releiver').value = ''; // Set the value back to 2
                return; // Exit the function
            }
            var busFields = document.getElementById('bus-fields');
            busFields.innerHTML = ''; // Clear previous bus fields
            var halfReleiverFields = document.getElementById('half-releiver-fields');
            halfReleiverFields.innerHTML = ''; // Clear previous half releiver fields

            for (var i = 1; i <= halfReleiverCount; i++) {
                var halfReleiverFieldHTML = `
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="half_releiver_driver_${i}_token">Half Releiver Driver ${i} Token:</label>
                        <input type="number" id="half_releiver_driver_${i}_token" name="half_releiver_driver_${i}_token" class="form-control" required>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="half_releiver_driver_${i}_pf">Half Releiver Driver ${i} PF:</label>
                        <input type="text" id="half_releiver_driver_${i}_pf" name="half_releiver_driver_${i}_pf" class="form-control" readonly>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="half_releiver_driver_${i}_name">Half Releiver Driver ${i} Name:</label>
                        <input type="text" id="half_releiver_driver_${i}_name" name="half_releiver_driver_${i}_name" class="form-control" readonly>
                    </div>
                </div>
            </div>`;
                halfReleiverFields.insertAdjacentHTML('beforeend', halfReleiverFieldHTML);
            }
            reattachEventListeners(); // Ensure new fields have event listeners
        }

        document.getElementById('half_releiver').addEventListener('input', updateHalfReleiverFields);

        function searchHalfReleiverDriver(driverIndex) {
            var tokenInput = document.getElementById(`half_releiver_driver_${driverIndex}_token`);
            if (tokenInput) {
                var tokenValue = tokenInput.value;
                fetchDriverDetails(tokenValue, `half_releiver_driver_${driverIndex}_name`, `half_releiver_driver_${driverIndex}_pf`, `half_releiver_driver_${driverIndex}_token`);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var driverTokens = document.querySelectorAll('input[id^="half_releiver_driver_"]');
            driverTokens.forEach(function (tokenElement) {
                tokenElement.addEventListener('change', function (event) {
                    var tokenInput = event.target;
                    if (tokenInput) {
                        var tokenNumber = tokenInput.value;
                        var idParts = tokenInput.id.split('_');
                        var driverIndex = idParts[3];
                        if (checkDuplicateTokens()) {
                        } else {
                            fetchDriverDetails(tokenNumber, `half_releiver_driver_${driverIndex}_name`, `half_releiver_driver_${driverIndex}_pf`, `half_releiver_driver_${driverIndex}_token`);
                        }
                    }
                });
            });

            // Add event listeners to all other form fields to uncheck the confirmation checkbox if any field is changed
            var formFields = document.querySelectorAll('#add-schedule-form input');
            formFields.forEach(function (field) {
                field.addEventListener('change', function (event) {
                    // Check if the event target is not the confirmation checkbox
                    if (event.target.id !== 'confirm-fields') {
                        document.getElementById('confirm-fields').checked = false; // Uncheck the confirmation checkbox
                    }
                });
            });
        });



        // Function to search for bus
        function searchBus(index) {
            var busNumber = $('#bus_number_' + index).val();

            // Check if the bus count is more than one
            var numberOfBuses = document.getElementById('number_of_buses').value;
            if (numberOfBuses > 1) {
                // If bus count is more than one, compare bus number with the other bus number
                var otherBusNumberIndex = (index === 1) ? 2 : 1;
                var otherBusNumber = $('#bus_number_' + otherBusNumberIndex).val();
                if (busNumber === otherBusNumber) {
                    // Found a duplicate, show alert and clear the input field
                    alert('Duplicate entry for bus number.');
                    $('#bus_number_' + index).val('');
                    $('#make' + index).val('');
                    $('#emission_norms' + index).val('');
                    return; // Exit the function to prevent further execution
                }
            }

            $.ajax({
                url: 'dvp_bus_search1.php',
                type: 'POST',
                data: { busNumber: busNumber },
                dataType: 'json', // Specify the expected data type as JSON
                success: function (response) {
                    if (response.make !== undefined && response.make !== null) {
                        // Populate form fields with fetched data
                        $('#make' + index).val(response.make);
                        $('#bus_number_' + index).val(busNumber);
                        $('#emission_norms' + index).val(response.emission_norms);
                    } else {
                        // Clear the make and bus number fields if bus number not found
                        $('#make' + index).val('');
                        $('#bus_number_' + index).val('');
                        $('#emission_norms' + index).val('');
                    }
                },
                error: function (xhr, status, error) {
                    // Display error message
                    if (xhr.status === 403) {
                        alert(xhr.responseJSON.error);
                    } else {
                        alert('Error: Bus not Registered in KKRTC.');
                    }
                    // Clear the make and bus number fields if search failed
                    $('#make' + index).val('');
                    $('#bus_number_' + index).val('');
                    $('#emission_norms' + index).val('');
                }
            });
        }
        function checkDuplicateTokens(newTokenElement) {
            var tokens = {};
            var driverTokens = document.querySelectorAll('input[id^="driver_token_"]');
            var halfReleiverTokens = document.querySelectorAll('input[id^="half_releiver_driver_"]');

            // Collect driver tokens
            driverTokens.forEach(function (tokenElement) {
                if (tokenElement.value) {
                    if (tokens[tokenElement.value]) {
                        tokens[tokenElement.value].push(tokenElement.id);
                    } else {
                        tokens[tokenElement.value] = [tokenElement.id];
                    }
                }
            });

            // Collect half releiver tokens
            halfReleiverTokens.forEach(function (tokenElement) {
                if (tokenElement.value) {
                    if (tokens[tokenElement.value]) {
                        tokens[tokenElement.value].push(tokenElement.id);
                    } else {
                        tokens[tokenElement.value] = [tokenElement.id];
                    }
                }
            });

            // Check for duplicates and if new token is duplicate
            var isDuplicate = false;
            var duplicateTokenId = null;
            Object.keys(tokens).forEach(function (token) {
                if (tokens[token].length > 1) {
                    if (tokens[token].includes(newTokenElement.id)) {
                        isDuplicate = true;
                        duplicateTokenId = newTokenElement.id;
                    }
                }
            });

            if (isDuplicate) {
                alert('Driver tokens cannot be the same. Please enter different token numbers.');
                clearDuplicateToken(duplicateTokenId);
                return true;
            }

            return false;
        }

        function clearDuplicateToken(tokenElementId) {
            var tokenElement = document.getElementById(tokenElementId);
            var idParts = tokenElement.id.split('_');
            var isDriverToken = idParts[0] === 'driver';
            var busIndex = idParts[2];
            var driverIndex = idParts[3];

            if (isDriverToken) {
                document.getElementById(`driver_token_${busIndex}_${driverIndex}`).value = '';
                document.getElementById(`driver_${busIndex}_${driverIndex}_name`).value = '';
                document.getElementById(`pf_no_d${busIndex}_${driverIndex}`).value = '';
            } else {
                var halfRelIndex = idParts[3];
                document.getElementById(`half_releiver_driver_${halfRelIndex}_token`).value = '';
                document.getElementById(`half_releiver_driver_${halfRelIndex}_name`).value = '';
                document.getElementById(`half_releiver_driver_${halfRelIndex}_pf`).value = '';
            }
        }

        function reattachEventListeners() {
            var driverTokens = document.querySelectorAll('input[id^="driver_token_"]');
            var halfReleiverTokens = document.querySelectorAll('input[id^="half_releiver_driver_"]');

            driverTokens.forEach(function (tokenElement) {
                tokenElement.addEventListener('change', function (event) {
                    var tokenNumber = event.target.value;
                    var idParts = event.target.id.split('_');
                    var busIndex = idParts[2];
                    var driverIndex = idParts[3];
                    if (tokenNumber) {
                        if (checkDuplicateTokens(event.target)) {
                            return;
                        } else {
                            fetchDriverDetails(tokenNumber, `driver_${busIndex}_${driverIndex}_name`, `pf_no_d${busIndex}_${driverIndex}`, `driver_token_${busIndex}_${driverIndex}`);
                        }
                    }
                });
            });

            halfReleiverTokens.forEach(function (tokenElement) {
                tokenElement.addEventListener('change', function (event) {
                    var tokenNumber = event.target.value;
                    var idParts = event.target.id.split('_');
                    var driverIndex = idParts[3];
                    if (tokenNumber) {
                        if (checkDuplicateTokens(event.target)) {
                            return;
                        } else {
                            fetchDriverDetails(tokenNumber, `half_releiver_driver_${driverIndex}_name`, `half_releiver_driver_${driverIndex}_pf`, `half_releiver_driver_${driverIndex}_token`);
                        }
                    }
                });
            });

            // Add event listeners to all other form fields to uncheck the confirmation checkbox if any field is changed
            var formFields = document.querySelectorAll('#add-schedule-form input');
            formFields.forEach(function (field) {
                field.addEventListener('change', function (event) {
                    if (event.target.id !== 'confirm-fields') {
                        document.getElementById('confirm-fields').checked = false;
                    }
                });
            });
        }

        function fetchDriverDetails(tokenNumber, nameElementId, pfElementId, tokenElementId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'http://localhost/data.php?token=' + tokenNumber, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var response = JSON.parse(this.responseText);
                    var data = response.data;

                    // Check if the data is null or empty
                    if (!data || data.length === 0) {
                        alert('The token number employee is not registered in KKRTC.');
                        clearFields(nameElementId, pfElementId, tokenElementId);
                        return;
                    }

                    // Filter the data for the correct division and depot
                    var matchingDrivers = data.filter(driver => {
                        var driverDivision = driver.Division.trim();
                        var driverDepot = driver.Depot.trim();
                        return driverDivision === sessionDivision && driverDepot === sessionDepot;
                    });

                    // Check if there are any matching drivers for the session division and depot
                    if (matchingDrivers.length > 0) {
                        var driver = matchingDrivers.find(driver => driver.token_number === tokenNumber);
                        if (driver) {
                            document.getElementById(nameElementId).value = driver.EMP_NAME || '';
                            document.getElementById(pfElementId).value = driver.EMP_PF_NUMBER || '';
                        } else {
                            alert(`The employee does not belong to the ${sessionDivision} division and ${sessionDepot} depot.`);
                            clearFields(nameElementId, pfElementId, tokenElementId);
                        }
                    } else {
                        alert('The employee does not belong to the session division and depot.');
                        clearFields(nameElementId, pfElementId, tokenElementId);
                    }
                } else {
                    alert('An error occurred while fetching the driver details.');
                    clearFields(nameElementId, pfElementId, tokenElementId);
                }
            };
            xhr.onerror = function () {
                alert('A network error occurred while fetching the driver details.');
                clearFields(nameElementId, pfElementId, tokenElementId);
            };
            xhr.send();
        }

        function clearFields(nameElementId, pfElementId, tokenElementId) {
            document.getElementById(nameElementId).value = '';
            document.getElementById(pfElementId).value = '';
            document.getElementById(tokenElementId).value = ''; // Nullify the token
        }

        // Initialize event listeners for the initial state
        document.addEventListener('DOMContentLoaded', function () {
            reattachEventListeners();
            ScheduleType();
            ServiceClass();
        });

        function ServiceClass() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'ServiceClass' },
                success: function (response) {
                    var service = JSON.parse(response);

                    // Clear existing options
                    $('#service_class_id').empty();

                    // Add default "Select" option
                    $('#service_class_id').append('<option value="">Select</option>');

                    // Add fetched options
                    $.each(service, function (index, value) {
                        $('#service_class_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        }

        function ScheduleType() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'ScheduleType' },
                success: function (response) {
                    var service = JSON.parse(response);

                    // Clear existing options
                    $('#service_type_id').empty();

                    // Add default "Select" option
                    $('#service_type_id').append('<option value="">Select</option>');

                    // Add fetched options
                    $.each(service, function (index, value) {
                        $('#service_type_id').append('<option value="' + value.id + '">' + value.type + '</option>');
                    });
                }
            });
        }

        var sessionDivision = "<?php echo strtoupper($division); ?>".trim();
        var sessionDepot = "<?php echo strtoupper($depot); ?>".trim();
    </script>

    <br><br>
    <style>
        .modal-body {
            max-height: 500px;
            overflow-y: auto;
        }
        .modal-lg {
            max-width: 70%;
        }
        .hide {
            display: none;
        }
    </style>
    <table id="dataTable">
        <thead>
            <tr>
                <th class="hide">ID</th>
                <th>Depot</th>
                <th>Sch No</th>
                <th>Sch Abbr</th>
                <th>Sch KM</th>
                <th>Sch Dep Time</th>
                <th>Sch Arr Time</th>
                <th>Service Class</th>
                <th>Service Type</th>
                <th>Sch Count</th>
                <th>Bus 1 Number</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT 
            skm.*, 
            loc.division, 
            loc.depot, 
            sc.name AS service_class_name, 
            st.type AS service_type_name,
            skm.ID as ID
        FROM 
            schedule_master skm 
        JOIN 
            location loc 
            ON skm.division_id = loc.division_id 
            AND skm.depot_id = loc.depot_id
        LEFT JOIN 
            service_class sc 
            ON skm.service_class_id = sc.id
        LEFT JOIN 
            schedule_type st 
            ON skm.service_type_id = st.id
        WHERE 
            skm.division_id = '" . $_SESSION['DIVISION_ID'] . "' 
            AND skm.depot_id = '" . $_SESSION['DEPOT_ID'] . "'";

            $result = $db->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<tr data-id="' . $row['ID'] . '">
                        <td class="hide">' . $row['ID'] . '</td>
                        <td>' . $row['depot'] . '</td>
                        <td>' . $row['sch_key_no'] . '</td>
                        <td>' . $row['sch_abbr'] . '</td>
                        <td>' . $row['sch_km'] . '</td>
                        <td>' . $row['sch_dep_time'] . '</td>
                        <td>' . $row['sch_arr_time'] . '</td>
                        <td>' . $row['service_class_name'] . '</td>
                        <td>' . $row['service_type_name'] . '</td>
                        <td>' . $row['sch_count'] . '</td>
                        <td>' . $row['bus_number_1'] . '</td>
                        <td><button class="btn btn-primary view-details">Details</button></td>
                    </tr>';
                }
            } else {
                echo '<tr><td colspan="13">No results found</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Schedule Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Details will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        // Close modal when clicking the close button
        $('#detailsModal .close').on('click', function () {
            $('#detailsModal').modal('hide');
        });

        // Close modal when clicking outside the modal
        $('#detailsModal').on('click', function (event) {
            if ($(event.target).is('#detailsModal')) {
                $('#detailsModal').modal('hide');
            }
        });
    });
    $(document).ready(function () {
        // Close modal when clicking the close button
        $('#detailsModal .modal-footer .btn-secondary').on('click', function () {
            $('#detailsModal').modal('hide');
        });
    });
    // JavaScript code provided earlier goes here
    $(document).ready(function () {
        $('.view-details').on('click', function () {
            var scheduleId = $(this).closest('tr').data('id');

            $.ajax({
                url: 'get_schedule_details.php',
                type: 'GET',
                data: { id: scheduleId },
                success: function (response) {
                    var details = JSON.parse(response);
                    var detailsHtml = '<table class="table table-bordered table-striped"><tbody>';

                    const excludedFields = ['id', 'division_id', 'depot_id', 'submitted_datetime', 'username'];
                    const fieldOrder = [
                        'sch_key_no', 'sch_abbr', 'sch_km', 'sch_dep_time', 'sch_arr_time', 'sch_count',
                        'service_class_name', 'service_type_name', 'number_of_buses',
                        'bus_number_1', 'bus_make_1', 'bus_emission_norms_1',
                        'driver_token_1_1', 'driver_pf_1_1', 'driver_name_1_1',
                        'driver_token_1_2', 'driver_pf_1_2', 'driver_name_1_2',
                        'driver_token_1_3', 'driver_pf_1_3', 'driver_name_1_3',
                        'bus_number_2', 'bus_make_2', 'bus_emission_norms_2',
                        'driver_token_2_1', 'driver_pf_2_1', 'driver_name_2_1',
                        'driver_token_2_2', 'driver_pf_2_2', 'driver_name_2_2',
                        'driver_token_2_3', 'driver_pf_2_3', 'driver_name_2_3',
                        'half_releiver_token_1', 'half_releiver_pf_1', 'half_releiver_name_1',
                        'half_releiver_token_2', 'half_releiver_pf_2', 'half_releiver_name_2'
                    ];
                    const fieldNames = {
                        'sch_key_no': 'Schedule Key Number',
                        'sch_abbr': 'Schedule Abbreviation',
                        'sch_km': 'Schedule KM',
                        'sch_dep_time': 'Departure Time',
                        'sch_arr_time': 'Arrival Time',
                        'sch_count': 'Schedule Count',
                        'service_class_name': 'Service Class',
                        'service_type_name': 'Service Type',
                        'number_of_buses': 'Number of Buses',
                        'bus_number_1': 'Bus 1 Number',
                        'bus_make_1': 'Bus 1 Make',
                        'bus_emission_norms_1': 'Bus 1 Emission Norms',
                        'driver_token_1_1': 'Bus 1 Driver 1 Token',
                        'driver_pf_1_1': 'Bus 1 Driver 1 PF',
                        'driver_name_1_1': 'Bus 1 Driver 1 Name',
                        'driver_token_1_2': 'Bus 1 Driver 2 Token',
                        'driver_pf_1_2': 'Bus 1 Driver 2 PF',
                        'driver_name_1_2': 'Bus 1 Driver 2 Name',
                        'driver_token_1_3': 'Bus 1 Driver 3 Token',
                        'driver_pf_1_3': 'Bus 1 Driver 3 PF',
                        'driver_name_1_3': 'Bus 1 Driver 3 Name',
                        'bus_number_2': 'Bus 2 Number',
                        'bus_make_2': 'Bus 2 Make',
                        'bus_emission_norms_2': 'Bus 2 Emission Norms',
                        'driver_token_2_1': 'Bus 2 Driver 1 Token',
                        'driver_pf_2_1': 'Bus 2 Driver 1 PF',
                        'driver_name_2_1': 'Bus 2 Driver 1 Name',
                        'driver_token_2_2': 'Bus 2 Driver 2 Token',
                        'driver_pf_2_2': 'Bus 2 Driver 2 PF',
                        'driver_name_2_2': 'Bus 2 Driver 2 Name',
                        'driver_token_2_3': 'Bus 2 Driver 3 Token',
                        'driver_pf_2_3': 'Bus 2 Driver 3 PF',
                        'driver_name_2_3': 'Bus 2 Driver 3 Name',
                        'half_releiver_token_1': 'Half Reliever 1 Token',
                        'half_releiver_pf_1': 'Half Reliever 1 PF',
                        'half_releiver_name_1': 'Half Reliever 1 Name',
                        'half_releiver_token_2': 'Half Reliever 2 Token',
                        'half_releiver_pf_2': 'Half Reliever 2 PF',
                        'half_releiver_name_2': 'Half Reliever 2 Name'
                    };

                    let count = 0;
                    detailsHtml += '<tr>';
                    fieldOrder.forEach(function (key) {
                        if (details[key] && !excludedFields.includes(key)) {
                            if (count === 3) {
                                detailsHtml += '</tr><tr>';
                                count = 0;
                            }
                            var displayName = fieldNames[key] || key.replace(/_/g, ' ').toUpperCase();
                            detailsHtml += '<td><strong>' + displayName + ':</strong> ' + details[key] + '</td>';
                            count++;
                        }
                    });
                    detailsHtml += '</tr></tbody></table>';

                    $('#detailsModal .modal-body').html(detailsHtml);
                    $('#detailsModal').modal('show');
                },
                error: function (xhr, status, error) {
                    console.log('AJAX Error:', error);
                }
            });
        });
    });
</script>



<?php include '../includes/footer.php'; ?>