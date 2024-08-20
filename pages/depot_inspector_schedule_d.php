<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access

    $division = $_SESSION['KMPL_DIVISION'];
    $depot = $_SESSION['KMPL_DEPOT'];


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Function to get maximum allowed drivers based on service class and single crew operation choice
        function getMaxAllowedDrivers($serviceClassName, $serviceTypeName, $singleCrewOperation)
        {
            $maxAllowedDrivers = null;

            switch ($serviceClassName) {
                case '1':
                    $maxAllowedDrivers = $singleCrewOperation === 'yes' ? 1 : 1;
                    break;
                case '2':
                    $maxAllowedDrivers = $singleCrewOperation === 'yes' ? 1 : 1;
                    break;
                case '3':
                    $maxAllowedDrivers = $singleCrewOperation === 'yes' ? 2 : 2;
                    break;
                case '4':
                    $maxAllowedDrivers = $singleCrewOperation === 'yes' ? 3 : 6;
                    break;
                case '5':
                    $maxAllowedDrivers = $singleCrewOperation === 'yes' ? 3 : 6;
                    break;

                default:
                    break;
            }

            return $maxAllowedDrivers;
        }

        // Function to get maximum allowed conductors based on service class and single crew operation choice
        function getMaxAllowedConductor($serviceClassName, $serviceTypeName, $singleCrewOperation)
        {
            $maxAllowedConductor = null;

            switch ($serviceClassName) {
                case '1':
                    $maxAllowedConductor = $singleCrewOperation === 'yes' ? 0 : 1;
                    break;
                case '2':
                    $maxAllowedConductor = $singleCrewOperation === 'yes' ? 0 : 2;
                    break;
                case '3':
                    $maxAllowedConductor = $singleCrewOperation === 'yes' ? 0 : 2;
                    break;
                case '4':
                    $maxAllowedConductor = $singleCrewOperation === 'yes' ? 0 : 3;
                    break;
                case '5':
                    $maxAllowedConductor = $singleCrewOperation === 'yes' ? 0 : 3;
                    break;

                default:
                    break;
            }

            return $maxAllowedConductor;
        }

        // Retrieve POST data
        $id = $_POST['id'];
        $sch_key_no = $_POST['sch_key_no'];
        $sch_abbr = $_POST['sch_abbr'];
        $sch_km = $_POST['sch_km'];
        $sch_dep_time = $_POST['sch_dep_time'];
        $sch_arr_time = $_POST['sch_arr_time'];
        $service_class_id = $_POST['service_class'];
        $service_type_id = $_POST['service_type'];
        $number_of_buses = $_POST['number_of_buses'];
        $single_crew = $_POST['single_crew_operation'];
        $numberOfDrivers = isset($_POST['number_of_drivers']) ? intval($_POST['number_of_drivers']) : 0;
        $numberOfConductor = isset($_POST['number_of_conductor']) ? intval($_POST['number_of_conductor']) : 0;

        // Validate single_crew operation
        $maxAllowedDrivers = getMaxAllowedDrivers($service_class_id, $service_type_id, $single_crew);
        $maxAllowedConductor = getMaxAllowedConductor($service_class_id, $service_type_id, $single_crew);

        if ($numberOfDrivers > $maxAllowedDrivers) {
            // Maximum allowed drivers exceeded
            echo "<script>alert('Maximum allowed drivers for this service is $maxAllowedDrivers.');</script>";
        } else if ($numberOfConductor > $maxAllowedConductor) {
            // Maximum allowed conductors exceeded
            echo "<script>alert('Maximum allowed conductors for this service is $maxAllowedConductor.');</script>";
        } else if ($numberOfDrivers > 0 && $numberOfDrivers <= $maxAllowedDrivers) {
            // Validate driver details
            $validDrivers = true;
            $driverTokens = [];
            $driverPFs = [];
            for ($i = 1; $i <= $numberOfDrivers; $i++) {
                $driverToken = $_POST['driver_token_' . $i];
                $driverName = $_POST['driver_' . $i . '_name'];
                $driverPF = $_POST['pf_no_d' . $i];

                // Check if any driver details are empty
                if (empty($driverToken) || empty($driverName) || empty($driverPF)) {
                    $validDrivers = false;
                    break;
                }

                // Collect driver tokens and PF numbers for duplicate check
                $driverTokens[] = $driverToken;
                $driverPFs[] = $driverPF;
            }

            // Validate conductor details
            $validConductors = true;
            $conductorTokens = [];
            $conductorPFs = [];
            for ($i = 1; $i <= $numberOfConductor; $i++) {
                $conductorToken = $_POST['conductor_token_' . $i];
                $conductorName = $_POST['conductor_' . $i . '_name'];
                $conductorPF = $_POST['pf_no_c' . $i];

                // Check if any conductor details are empty
                if (empty($conductorToken) || empty($conductorName) || empty($conductorPF)) {
                    $validConductors = false;
                    break;
                }

                // Collect conductor tokens and PF numbers for duplicate check
                $conductorTokens[] = $conductorToken;
                $conductorPFs[] = $conductorPF;
            }

            if (!$validDrivers) {
                echo "<script>alert('Please fill all the Driver details');</script>";
            } elseif (!$validConductors) {
                echo "<script>alert('Please fill all the Conductor details');</script>";
            } else {
                // Check for duplicate PF numbers and tokens
                $duplicateFound = false;
                $duplicateMessage = "";
                $tokenCheckSql = "SELECT id, driver_token_1, driver_token_2, driver_token_3, driver_pf_1, driver_pf_2, driver_pf_3, conductor_token_1, conductor_token_2, conductor_token_3, conductor_pf_1, conductor_pf_2, conductor_pf_3 FROM schedule_master WHERE id != ?";
                $params = [];
                $paramTypes = 'i';
                $params[] = $id;

                // Prepare and execute the token check query
                $tokenCheckStmt = $db->prepare($tokenCheckSql);
                $tokenCheckStmt->bind_param($paramTypes, ...$params);
                $tokenCheckStmt->execute();
                $tokenCheckResult = $tokenCheckStmt->get_result();

                // Collect existing tokens and PFs from the database
                $existingTokens = [];
                $existingPFs = [];
                while ($row = $tokenCheckResult->fetch_assoc()) {
                    $existingTokens[] = $row['driver_token_1'];
                    $existingTokens[] = $row['driver_token_2'];
                    $existingTokens[] = $row['driver_token_3'];
                    $existingTokens[] = $row['conductor_token_1'];
                    $existingTokens[] = $row['conductor_token_2'];
                    $existingTokens[] = $row['conductor_token_3'];

                    $existingPFs[] = $row['driver_pf_1'];
                    $existingPFs[] = $row['driver_pf_2'];
                    $existingPFs[] = $row['driver_pf_3'];
                    $existingPFs[] = $row['conductor_pf_1'];
                    $existingPFs[] = $row['conductor_pf_2'];
                    $existingPFs[] = $row['conductor_pf_3'];
                }



                if (!$duplicateFound) {
                    foreach ($driverPFs as $index => $pf) {
                        if (in_array($pf, $existingPFs)) {
                            $duplicateFound = true;
                            $duplicateMessage = "Duplicate PF number found: $pf with token number: " . $driverTokens[$index] . ". Please enter a different PF number.";
                            break;
                        }
                    }
                }


                if (!$duplicateFound) {
                    foreach ($conductorPFs as $index => $pf) {
                        if (in_array($pf, $existingPFs)) {
                            $duplicateFound = true;
                            $duplicateMessage = "Duplicate PF number found: $pf with token number: " . $conductorTokens[$index] . ". Please enter a different PF number.";
                            break;
                        }
                    }
                }

                if ($duplicateFound) {
                    echo "<script>alert('$duplicateMessage');</script>";
                } else {
                    // Prepare the SQL statement for updating schedules
                    $updateScheduleSql = "UPDATE schedule_master SET single_crew = ? WHERE id = ?";
                    $stmt = $db->prepare($updateScheduleSql);
                    $stmt->bind_param('si', $single_crew, $id);

                    // Execute update query
                    if ($stmt->execute()) {
                        // Insert or update driver details in schedule_master
                        for ($i = 1; $i <= $numberOfDrivers; $i++) {
                            $driverToken = $_POST['driver_token_' . $i];
                            $driverPF = $_POST['pf_no_d' . $i];
                            $driverName = $_POST['driver_' . $i . '_name'];

                            // Prepare the SQL statement for updating or inserting driver details in schedule_master
                            $updateDriverSql = "UPDATE schedule_master SET driver_token_" . $i . " = ?, driver_pf_" . $i . " = ?, driver_name_" . $i . " = ? WHERE id = ?";
                            $driverStmt = $db->prepare($updateDriverSql);
                            $driverStmt->bind_param('sssi', $driverToken, $driverPF, $driverName, $id);
                            $driverStmt->execute();
                        }

                        // Insert or update conductor details in schedule_master
                        for ($i = 1; $i <= $numberOfConductor; $i++) {
                            $conductorToken = $_POST['conductor_token_' . $i];
                            $conductorPF = $_POST['pf_no_c' . $i];
                            $conductorName = $_POST['conductor_' . $i . '_name'];

                            // Prepare the SQL statement for updating or inserting conductor details in schedule_master
                            $updateConductorSql = "UPDATE schedule_master SET conductor_token_" . $i . " = ?, conductor_pf_" . $i . " = ?, conductor_name_" . $i . " = ? WHERE id = ?";
                            $conductorStmt = $db->prepare($updateConductorSql);
                            $conductorStmt->bind_param('sssi', $conductorToken, $conductorPF, $conductorName, $id);
                            $conductorStmt->execute();
                        }
                        $division1 = $_SESSION['DIVISION_ID'];
                        $depot1 = $_SESSION['DEPOT_ID'];
                        // Insert data into crew_fixed_data table
                        $insertCrewDataSql = "INSERT INTO crew_fix_data (sch_no, division_id, depot_id,dep_time,arr_time, driver_token_1, driver_pf_1, driver_name_1, driver_token_2, driver_pf_2, driver_name_2, driver_token_3, driver_pf_3, driver_name_3, driver_token_4, driver_pf_4, driver_name_4, driver_token_5, driver_pf_5, driver_name_5, driver_token_6, driver_pf_6, driver_name_6, conductor_token_1, conductor_pf_1, conductor_name_1, conductor_token_2, conductor_pf_2, conductor_name_2, conductor_token_3, conductor_pf_3, conductor_name_3) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $crewStmt = $db->prepare($insertCrewDataSql);

                        // Bind the parameters for the insert statement
                        $crewStmt->bind_param(
                            'sissssssssssssssssssssssssssssss',
                            $sch_key_no,
                            $division1,
                            $depot1,
                            $sch_dep_time,
                            $sch_arr_time,
                            $_POST['driver_token_1'],
                            $_POST['pf_no_d1'],
                            $_POST['driver_1_name'],
                            $_POST['driver_token_2'],
                            $_POST['pf_no_d2'],
                            $_POST['driver_2_name'],
                            $_POST['driver_token_3'],
                            $_POST['pf_no_d3'],
                            $_POST['driver_3_name'],
                            $_POST['driver_token_4'],
                            $_POST['pf_no_d4'],
                            $_POST['driver_4_name'],
                            $_POST['driver_token_5'],
                            $_POST['pf_no_d5'],
                            $_POST['driver_5_name'],
                            $_POST['driver_token_6'],
                            $_POST['pf_no_d6'],
                            $_POST['driver_6_name'],
                            $_POST['conductor_token_1'],
                            $_POST['pf_no_c1'],
                            $_POST['conductor_1_name'],
                            $_POST['conductor_token_2'],
                            $_POST['pf_no_c2'],
                            $_POST['conductor_2_name'],
                            $_POST['conductor_token_3'],
                            $_POST['pf_no_c3'],
                            $_POST['conductor_3_name']
                        );

                        // Execute the insert query
                        if ($crewStmt->execute()) {
                            echo "<script>
                                alert('Schedule and crew data updated successfully');
                                window.location.href = 'depot_inspector_schedule_d.php';
                            </script>";
                        } else {
                            $insertError = 'Error inserting crew data: ' . $crewStmt->error;
                            echo "<script>alert('$insertError');</script>";
                        }

                        // Close the statement
                        $crewStmt->close();
                    } else {
                        $updateError = 'Error updating schedule: ' . $stmt->error;
                        echo "<script>alert('$updateError');</script>";
                    }


                    $stmt->close();
                }
            }
        } else {
            $updateError = 'Please enter a valid number of drivers.';
            echo "<script>alert('$updateError');</script>";
        }
    }
    ?>
    <style>
        .hide {
            display: none;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-container h2 {
            margin: 0;
        }

        .header-container .center {
            text-align: center;
            flex-grow: 1;
        }
    </style>
    <div class="header-container">
        <h2>Depot: <?php echo $_SESSION['DEPOT']; ?></h2>
        <h2 class="center">SCHEDULE MASTER(CREW)</h2>
    </div>
    <table id="dataTable">
        <thead>
            <tr>
                <th class="hide">ID</th>
                <th>Sch NO</th>
                <th>Description</th>
                <th>Sch Km</th>
                <th>Sch Dep Time</th>
                <th>Sch Arr Time</th>
                <th>Service Class</th>
                <th>Service Type</th>
                <th>Allotted Driver</th>
                <th>Allotted Conductor</th>
                <th>Action</th>
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
                    // Combine bus numbers, driver tokens, and half-reliever tokens
                    $driver_tokens = [$row['driver_token_1'], $row['driver_token_2'], $row['driver_token_3'], $row['driver_token_4'], $row['driver_token_5'], $row['driver_token_6']];
                    $conductor_tokens = [$row['conductor_token_1'], $row['conductor_token_2'], $row['conductor_token_3']];

                    // Check if all conductor tokens are null or empty
                    if (($row['single_crew'] === 'yes')) {
                        $conductor_tokens = ['Single Crew Operation'];
                    }
                    echo '<tr data-id="' . $row['ID'] . '">
                <td class="hide">' . $row['ID'] . '</td>
                <td>' . $row['sch_key_no'] . '</td>
                <td>' . $row['sch_abbr'] . '</td>
                <td>' . $row['sch_km'] . '</td>
                <td>' . $row['sch_dep_time'] . '</td>
                <td>' . $row['sch_arr_time'] . '</td>
                <td>' . $row['service_class_name'] . '</td>
                <td>' . $row['service_type_name'] . '</td>
                <td>';
                    foreach ($driver_tokens as $driver_token) {
                        if (!empty($driver_token)) {
                            echo $driver_token . '<br>';
                        }
                    }
                    echo '</td>
                <td>';
                    foreach ($conductor_tokens as $conductor_token) {
                        if (!empty($conductor_token)) {
                            echo $conductor_token . '<br>';
                        }
                    }
                    echo '</td>
                <td>';
                    if (empty($row['driver_token_1']) && empty($row['driver_token_2'])) {
                        echo '<button class="btn btn-warning update-details">Update</button>';
                    } else {
                        echo '<button class="btn btn-primary view-details">Details</button>';
                    }
                    echo '</td>
            </tr>';
                }
            } else {
                echo '<tr><td colspan="11">No results found</td></tr>';
            }
            ?>
        </tbody>
    </table>
    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Schedule Update</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="updateForm" method="post">
                        <input type="hidden" id="scheduleId" name="id">
                        <div id="scheduleFields"></div>
                        <div id="crewOperationFields"></div>
                        <div id="driverFields"></div>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="close" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            // Handle Update button click
            $('.update-details').on('click', function () {
                var scheduleId = $(this).closest('tr').data('id');
                $('#scheduleId').val(scheduleId);

                $.ajax({
                    url: 'get_schedule_details.php',
                    type: 'GET',
                    data: { id: scheduleId },
                    success: function (response) {
                        var details = JSON.parse(response);
                        var scheduleFieldsHtml = `
                                                    <div class="row">
                                                        <div class="col">
                                                            <div class="form-group">
                                                                <label for="sch_key_no">Schedule Key Number</label>
                                                                <input type="text" class="form-control" id="sch_key_no" name="sch_key_no" value="${details.sch_key_no}" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="form-group">
                                                                <label for="sch_abbr">Schedule Abbreviation</label>
                                                                <input type="text" class="form-control" id="sch_abbr" name="sch_abbr" value="${details.sch_abbr}" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="form-group">
                                                                <label for="sch_km">Schedule KM</label>
                                                                <input type="text" class="form-control" id="sch_km" name="sch_km" value="${details.sch_km}" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" id="number_of_buses" name="number_of_buses" value="${details.number_of_buses}">
                                                    <input type="hidden" id="id" name="id" value="${details.id}">
                                                    <input type="hidden" id="service_class" name="service_class" value="${details.service_type_id}">
                                                    <input type="hidden" id="service_type" name="service_type" value="${details.service_type_name}">
                    
                                                    <div class="row">
                                                        <div class="col">
                                                            <div class="form-group">
                                                                <label for="sch_dep_time">Departure Time</label>
                                                                <input type="text" class="form-control" id="sch_dep_time" name="sch_dep_time" value="${details.sch_dep_time}" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="form-group">
                                                                <label for="sch_arr_time">Arrival Time</label>
                                                                <input type="text" class="form-control" id="sch_arr_time" name="sch_arr_time" value="${details.sch_arr_time}" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="form-group">
                                                                <label for="service_class_name">Service Class</label>
                                                                <input type="text" class="form-control" id="service_class_name" name="service_class_name" value="${details.service_class_name}" readonly>
                                                            </div>
                                                        </div>
                                                    </div>`;

                        $('#scheduleFields').html(scheduleFieldsHtml);
                        $('#updateModal').modal('show');

                        // Add the single crew operation checkboxes and number of drivers/conductors fields
                        var crewOperationFieldsHtml = `
                                                    <div class="row">
                                                        <div class="col">
                                                            <div class="form-group">
                                                                <label>Single Crew Operation:</label>
                                                                <div>
                                                                    <input type="radio" id="single_crew_yes" name="single_crew_operation" value="yes" required>
                                                                    <label for="single_crew_yes">Yes</label>
                                                                    <input type="radio" id="single_crew_no" name="single_crew_operation" value="no" required>
                                                                    <label for="single_crew_no">No</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="form-group">
                                                                <label for="number_of_drivers">Enter the number of drivers:</label>
                                                                <input type="number" id="number_of_drivers" name="number_of_drivers" class="form-control" disabled required>
                                                            </div>
                                                        </div>
                                                        <div class="col" id="conductorColumn" style="display: none;">
                                                            <div class="form-group">
                                                                <label for="number_of_conductor">Enter the number of Conductors:</label>
                                                                <input type="number" id="number_of_conductor" name="number_of_conductor" class="form-control" required>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div id="driverFields"></div>
                                                    <div id="conductorFields"></div>
                                                    <div id="driverAllocationMessage"></div>`;

                        $('#crewOperationFields').html(crewOperationFieldsHtml);

                        // Listen for change in single crew operation checkboxes
                        $('input[name="single_crew_operation"]').change(function () {
                            var singleCrewOperation = $('input[name="single_crew_operation"]:checked').val();
                            if (singleCrewOperation === 'yes') {
                                $('#number_of_drivers').prop('disabled', false);
                                $('#conductorColumn').hide();
                                $('#number_of_conductor').val('').removeAttr('required');  // Correct method to remove attribute
                                $('#number_of_drivers').val('');
                                $('#driverFields').empty();
                                addConductorInputFields(0);
                            } else if (singleCrewOperation === 'no') {
                                $('#number_of_drivers').prop('disabled', false).val('');
                                $('#driverFields').empty();
                                $('#conductorColumn').show();
                            }
                        });

                        // Listen for input in number of drivers field
                        $('#number_of_drivers').on('input', function () {
                            var numberOfDrivers = $(this).val();
                            var serviceClassName = $('#service_class').val();
                            var serviceTypeName = $('#service_type').val();
                            var singleCrewOperation = $('input[name="single_crew_operation"]:checked').val();
                            var maxAllowedDrivers = getMaxAllowedDrivers(serviceClassName, serviceTypeName, singleCrewOperation);

                            if (numberOfDrivers > maxAllowedDrivers) {
                                $(this).val('');
                                addDriverInputFields(0);
                                alert(`Maximum allowed drivers for ${serviceTypeName} is ${maxAllowedDrivers}`);
                            } else {
                                addDriverInputFields(numberOfDrivers);
                            }
                        });
                        $('#number_of_conductor').on('input', function () {
                            var numberOfDrivers = $(this).val();
                            var serviceClassName = $('#service_class').val();
                            var serviceTypeName = $('#service_type').val();
                            var singleCrewOperation = $('input[name="single_crew_operation"]:checked').val();
                            var maxAllowedDrivers = getMaxAllowedConductor(serviceClassName, serviceTypeName, singleCrewOperation);
                            if (numberOfDrivers > maxAllowedDrivers) {
                                $(this).val('');
                                addConductorInputFields(0);
                                alert(`Maximum allowed conductor for ${serviceTypeName} is ${maxAllowedDrivers}`);
                            } else {
                                addConductorInputFields(numberOfDrivers);
                            }
                        });
                        // Function to add driver input fields based on the number of drivers
                        function addDriverInputFields(numberOfDrivers) {
                            var driverFieldsHtml = '';
                            for (var i = 1; i <= numberOfDrivers; i++) {
                                driverFieldsHtml += `
                                                            <div class="row driver-field">
                                                                <div class="col">
                                                                    <div class="form-group">
                                                                        <label for="driver_token_${i}">Driver ${i} Token:</label>
                                                                        <input type="number" id="driver_token_${i}" name="driver_token_${i}" class="form-control" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="form-group">
                                                                        <label for="pf_no_d${i}">Driver ${i} PF:</label>
                                                                        <input type="text" id="pf_no_d${i}" name="pf_no_d${i}" class="form-control" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="form-group">
                                                                        <label for="driver_${i}_name">Driver ${i} Name:</label>
                                                                        <input type="text" id="driver_${i}_name" name="driver_${i}_name" class="form-control" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>`;
                            }

                            $('#driverFields').html(driverFieldsHtml);

                            // Add event listener for each driver token input field
                            for (var j = 1; j <= numberOfDrivers; j++) {
                                (function (j) {
                                    $('#driver_token_' + j).on('blur', function () {
                                        var tokenNumber = $(this).val();
                                        if (tokenNumber) {
                                            if (isDuplicateToken(tokenNumber, j, 'driver')) {
                                                alert(`Duplicate entry for token ${tokenNumber}. Please update with another token number.`);
                                                clearFields('driver_' + j + '_name', 'pf_no_d' + j, 'driver_token_' + j);
                                            } else {
                                                fetchDriverDetails(tokenNumber, 'driver_' + j + '_name', 'pf_no_d' + j, 'driver_token_' + j);
                                            }
                                        }
                                    });
                                })(j);
                            }
                        }

                        // Listen for input in number of conductors field
                        $('#number_of_conductor').on('input', function () {
                            var numberOfConductors = $(this).val();
                            addConductorInputFields(numberOfConductors);
                        });

                        // Function to add conductor input fields based on the number of conductors
                        function addConductorInputFields(numberOfConductors) {
                            var conductorFieldsHtml = '';
                            for (var i = 1; i <= numberOfConductors; i++) {
                                conductorFieldsHtml += `
                                                            <div class="row conductor-field">
                                                                <div class="col">
                                                                    <div class="form-group">
                                                                        <label for="conductor_token_${i}">Conductor ${i} Token:</label>
                                                                        <input type="number" id="conductor_token_${i}" name="conductor_token_${i}" class="form-control" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="form-group">
                                                                        <label for="pf_no_c${i}">Conductor ${i} PF:</label>
                                                                        <input type="text" id="pf_no_c${i}" name="pf_no_c${i}" class="form-control" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="form-group">
                                                                        <label for="conductor_${i}_name">Conductor ${i} Name:</label>
                                                                        <input type="text" id="conductor_${i}_name" name="conductor_${i}_name" class="form-control" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>`;
                            }

                            $('#conductorFields').html(conductorFieldsHtml);

                            // Add event listener for each conductor token input field
                            for (var j = 1; j <= numberOfConductors; j++) {
                                (function (j) {
                                    $('#conductor_token_' + j).on('blur', function () {
                                        var tokenNumber = $(this).val();
                                        if (tokenNumber) {
                                            if (isDuplicateToken(tokenNumber, j, 'conductor')) {
                                                alert(`Duplicate entry for token ${tokenNumber}. Please update with another token number.`);
                                                clearFields('conductor_' + j + '_name', 'pf_no_c' + j, 'conductor_token_' + j);
                                            } else {
                                                fetchConductorDetails(tokenNumber, 'conductor_' + j + '_name', 'pf_no_c' + j, 'conductor_token_' + j);
                                            }
                                        }
                                    });
                                })(j);
                            }
                        }
                        function fetchConductorDetails(tokenNumber, nameElementId, pfElementId, tokenElementId) {
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
                                            // Check if the designation is DRIVER
                                            if (driver.EMP_DESGN_AT_APPOINTMENT === "DRIVER") {
                                                alert('The employee is a DRIVER. Please enter the token number of a Conductor or Driver cum Conductor.');
                                                clearFields(nameElementId, pfElementId, tokenElementId);
                                                return;
                                            }
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
                                    alert('An error occurred while fetching the conductor details.');
                                    clearFields(nameElementId, pfElementId, tokenElementId);
                                }
                            };
                            xhr.onerror = function () {
                                alert('A network error occurred while fetching the conductor details.');
                                clearFields(nameElementId, pfElementId, tokenElementId);
                            };
                            xhr.send();
                        }


                        // Function to get maximum allowed drivers based on service class and single crew operation choice
                        function getMaxAllowedDrivers(serviceClassName, serviceTypeName, singleCrewOperation) {
                            var maxAllowedDrivers = null;

                            switch (serviceClassName) {
                                case '1':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 1 : 1;
                                    break;
                                case '2':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 1 : 1;
                                    break;
                                case '3':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 2 : 2;
                                    break;
                                case '4':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 3 : 6;
                                    break;
                                case '5':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 3 : 6;
                                    break;

                                default:
                                    break;
                            }

                            return maxAllowedDrivers;
                        }
                        // Function to get maximum allowed drivers based on service class and single crew operation choice
                        function getMaxAllowedConductor(serviceClassName, serviceTypeName, singleCrewOperation) {
                            var maxAllowedDrivers = null;

                            switch (serviceClassName) {
                                case '1':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 0 : 1;
                                    break;
                                case '2':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 0 : 2;
                                    break;
                                case '3':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 0 : 2;
                                    break;
                                case '4':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 0 : 3;
                                    break;
                                case '5':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 0 : 3;
                                    break;

                                default:
                                    break;
                            }

                            return maxAllowedDrivers;
                        }

                        function fetchDriverDetails(tokenNumber, nameElementId, pfElementId, tokenElementId) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', 'http://localhost/data.php?token=' + tokenNumber, true);
                            xhr.onload = function () {
                                if (xhr.status === 200) {
                                    var response = JSON.parse(this.responseText);
                                    var data = response.data;
                                    console.log('API Response:', response.data); // Debug log for API response

                                    // Check if the data is null or empty
                                    if (!data || data.length === 0) {
                                        alert('The employee is not registered in KKRTC.');
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
                                            // Check if the employee is a conductor
                                            if (driver.EMP_DESGN_AT_APPOINTMENT === "CONDUCTOR") {
                                                alert('The employee is a conductor. Please enter the token number of a Driver or Driver cum Conductor.');
                                                clearFields(nameElementId, pfElementId, tokenElementId);
                                                return;
                                            }
                                            document.getElementById(nameElementId).value = driver.EMP_NAME || '';
                                            document.getElementById(pfElementId).value = driver.EMP_PF_NUMBER || '';
                                        } else {
                                            alert('The employee is not registered in KKRTC.');
                                            clearFields(nameElementId, pfElementId, tokenElementId);
                                        }
                                    } else {
                                        alert('The employee does not belong to the specified division and depot.');
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
                            document.getElementById(tokenElementId).value = '';
                        }


                        var sessionDivision = "<?php echo strtoupper($division); ?>".trim();
                        var sessionDepot = "<?php echo strtoupper($depot); ?>".trim();
                        // Function to check for duplicate tokens across both driver and conductor fields
                        function isDuplicateToken(token, currentIndex, type) {
                            var isDuplicate = false;

                            // Check driver fields
                            $('.driver-field input[type="number"]').each(function (index) {
                                var fieldId = $(this).attr('id');
                                var fieldIndex = parseInt(fieldId.match(/\d+/)[0]);
                                if (type === 'driver' && currentIndex === fieldIndex) return true; // Skip current driver field
                                if ($(this).val() == token) {
                                    isDuplicate = true;
                                    return false; // Break loop
                                }
                            });

                            // Check conductor fields
                            if (!isDuplicate) {
                                $('.conductor-field input[type="number"]').each(function (index) {
                                    var fieldId = $(this).attr('id');
                                    var fieldIndex = parseInt(fieldId.match(/\d+/)[0]);
                                    if (type === 'conductor' && currentIndex === fieldIndex) return true; // Skip current conductor field
                                    if ($(this).val() == token) {
                                        isDuplicate = true;
                                        return false; // Break loop
                                    }
                                });
                            }

                            return isDuplicate;
                        }

                        // Function to clear fields
                        function clearFields(nameFieldId, pfFieldId, tokenFieldId) {
                            $('#' + nameFieldId).val('');
                            $('#' + pfFieldId).val('');
                            $('#' + tokenFieldId).val('');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log('AJAX Error:', error);
                    }
                });
            });

            // Close modal when clicking the close button or outside the modal
            $('#updateModal .close').on('click', function () {
                $('#updateModal').modal('hide');
            });

            $('#updateModal').on('click', function (event) {
                if ($(event.target).is('#updateModal')) {
                    $('#updateModal').modal('hide');
                }
            });
        });
    </script>



    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Schedule <span id="scheduleKeyNumber"></span> Details
                    </h5>
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
                        $('#scheduleKeyNumber').text(details.sch_key_no);
                        var detailsHtml = '<table class="table table-bordered table-striped"><tbody>';

                        const excludedFields = ['id', 'division_id', 'depot_id', 'submitted_datetime', 'username'];
                        const fieldOrder = [
                            'sch_key_no', 'sch_abbr', 'sch_km', 'sch_dep_time', 'sch_arr_time', 'sch_count',
                            'service_class_name', 'service_type_name', 'single_crew',
                            'driver_token_1', 'driver_pf_1', 'driver_name_1',
                            'driver_token_2', 'driver_pf_2', 'driver_name_2',
                            'driver_token_3', 'driver_pf_3', 'driver_name_3',
                            'driver_token_4', 'driver_pf_4', 'driver_name_4',
                            'driver_token_5', 'driver_pf_5', 'driver_name_5',
                            'driver_token_6', 'driver_pf_6', 'driver_name_6',
                            'conductor_token_1', 'conductor_pf_1', 'conductor_name_1',
                            'conductor_token_2', 'conductor_pf_2', 'conductor_name_2',
                            'conductor_token_3', 'conductor_pf_3', 'conductor_name_3',


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
                            'single_crew': 'Single Crew',
                            'driver_token_1': 'Driver 1 Token',
                            'driver_pf_1': 'Driver 1 PF',
                            'driver_name_1': 'Driver 1 Name',
                            'driver_token_2': 'Driver 2 Token',
                            'driver_pf_2': 'Driver 2 PF',
                            'driver_name_2': 'Driver 2 Name',
                            'driver_token_3': 'Driver 3 Token',
                            'driver_pf_3': 'Driver 3 PF',
                            'driver_name_3': 'Driver 3 Name',
                            'driver_token_4': 'Driver 4 Token',
                            'driver_pf_4': 'Driver 4 PF',
                            'driver_name_4': 'Driver 4 Name',
                            'driver_token_5': 'Driver 5 Token',
                            'driver_pf_5': 'Driver 5 PF',
                            'driver_name_5': 'Driver 5 Name',
                            'driver_token_6': 'Driver 6 Token',
                            'driver_pf_6': 'Driver 6 PF',
                            'driver_name_6': 'Driver 6 Name',
                            'conductor_token_1': 'Conductor 1 Token',
                            'conductor_pf_1': 'Conductor 1 PF',
                            'conductor_name_1': 'Conductor 1 Name',
                            'conductor_token_2': 'Conductor 2 Token',
                            'conductor_pf_2': 'Conductor 2 PF',
                            'conductor_name_2': 'Conductor 2 Name',
                            'conductor_token_3': 'Conductor 3 Token',
                            'conductor_pf_3': 'Conductor 3 PF',
                            'conductor_name_3': 'Conductor 3 Name',
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
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>