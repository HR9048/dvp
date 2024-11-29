<?php
error_reporting(error_level: 0); // Disable all error reporting

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
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $user = $_SESSION['USERNAME'];

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
                    $maxAllowedDrivers = $singleCrewOperation === 'yes' ? 2 : 2;
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
        $driverPFs = [];
        for ($i = 1; $i <= $numberOfDrivers; $i++) {
            $driverPFs[] = $_POST['pf_no_d' . $i];
        }

        $conductorPFs = [];
        for ($i = 1; $i <= $numberOfConductor; $i++) {
            $conductorPFs[] = $_POST['pf_no_c' . $i];
        }

        // Combine all PFs for duplicate checking
        $allPFs = array_merge($driverPFs, $conductorPFs);

        // Check for duplicates in submitted data
        $pfCount = array_count_values($allPFs);
        $duplicatePFs = [];
        foreach ($pfCount as $pf => $count) {
            if ($count > 1 && !empty($pf)) {
                $duplicatePFs[] = $pf;
            }
        }

        if (!empty($duplicatePFs)) {
            $duplicatePFList = implode(', ', $duplicatePFs);
            echo "<script>
                alert('Duplicate PF numbers found: $duplicatePFList. Please enter unique PF numbers.');
                window.location = 'depot_inspector_schedule_d.php';
            </script>";
            exit; // Ensure no further processing happens after the alert and redirect
        }

        if ($numberOfDrivers > $maxAllowedDrivers) {
            echo "<script>alert('Maximum allowed drivers for this service is $maxAllowedDrivers.');</script>";
        } else if ($numberOfConductor > $maxAllowedConductor) {
            echo "<script>alert('Maximum allowed conductors for this service is $maxAllowedConductor.');</script>";
        } else if ($numberOfDrivers > 0 && $numberOfDrivers <= $maxAllowedDrivers) {
            // Validate driver and conductor details...

            // Check for duplicate PF numbers and tokens
            $duplicateFound = false;
            $duplicateMessage = "";
            $tokenCheckSql = "SELECT 
                driver_token_1, driver_token_2, driver_token_3, driver_pf_1, driver_pf_2, driver_pf_3, 
                conductor_token_1, conductor_token_2, conductor_token_3, conductor_pf_1, conductor_pf_2, conductor_pf_3 
                FROM schedule_master WHERE id != ?";
            $params = [$id];

            $tokenCheckStmt = $db->prepare($tokenCheckSql);
            $tokenCheckStmt->bind_param('i', $id);
            $tokenCheckStmt->execute();
            $tokenCheckResult = $tokenCheckStmt->get_result();

            // Collect existing PFs
            $existingPFs = [];
            while ($row = $tokenCheckResult->fetch_assoc()) {
                for ($j = 1; $j <= 3; $j++) {
                    $existingPFs[] = $row['driver_pf_' . $j];
                    $existingPFs[] = $row['conductor_pf_' . $j];
                }
            }

            // Check for duplicates in the form data
            $driverTokens = [];
            $driverPFs = [];
            for ($i = 1; $i <= $numberOfDrivers; $i++) {
                $driverToken = $_POST['driver_token_' . $i];
                $driverPF = $_POST['pf_no_d' . $i];

                // Check for duplicates
                if (in_array($driverPF, $existingPFs)) {
                    $duplicateFound = true;
                    $duplicateMessage .= "Driver token $driverToken has the same PF number: $driverPF. Please select a different token or PF number.<br>";
                }

                $driverTokens[] = $driverToken;
                $driverPFs[] = $driverPF;
            }

            $conductorTokens = [];
            $conductorPFs = [];
            for ($i = 1; $i <= $numberOfConductor; $i++) {
                $conductorToken = $_POST['conductor_token_' . $i];
                $conductorPF = $_POST['pf_no_c' . $i];

                // Check for duplicates
                if (in_array($conductorPF, $existingPFs)) {
                    $duplicateFound = true;
                    $duplicateMessage .= "Conductor token $conductorToken has the same PF number: $conductorPF. Please select a different token or PF number.<br>";
                }

                $conductorTokens[] = $conductorToken;
                $conductorPFs[] = $conductorPF;
            }

            if ($duplicateFound) {
                echo "<script>alert('$duplicateMessage');</script>";
            } else {
                // Fetch existing data for the current schedule
                $currentDataSql = "SELECT * FROM schedule_master WHERE id = ?";
                $currentDataStmt = $db->prepare($currentDataSql);
                $currentDataStmt->bind_param('i', $id);
                $currentDataStmt->execute();
                $currentData = $currentDataStmt->get_result()->fetch_assoc();



                // Check driver and conductor details
                $crewUpdates = [];

                // Check for drivers
                for ($i = 1; $i <= 3; $i++) { // Assuming max 2 drivers
                    $driverToken = $_POST['driver_token_' . $i];
                    $driverPF = $_POST['pf_no_d' . $i];
                    $driverName = $_POST['driver_' . $i . '_name'];
                    $existingDriverPF = $currentData['driver_pf_' . $i];

                    if ($driverPF !== $existingDriverPF) {
                        // Update existing driver's to_date to current datetime
                        $updateCrewSql = "UPDATE crew_fix_data SET to_date = NOW() WHERE division_id = ? AND depot_id = ? AND sch_key_no = ? AND crew_pf = ?";
                        $crewStmt = $db->prepare($updateCrewSql);
                        $crewStmt->bind_param('siis', $division_id, $depot_id, $sch_key_no, $existingDriverPF);
                        $crewStmt->execute();

                        // Collect new driver data for insertion
                        $crewUpdates[] = [
                            'token' => $driverToken,
                            'pf' => $driverPF,
                            'name' => $driverName,
                            'designation' => 'Driver'
                        ];
                    }
                }

                // Check for conductors
                for ($i = 1; $i <= 3; $i++) { // Assuming max 2 conductors
                    $conductorToken = $_POST['conductor_token_' . $i];
                    $conductorPF = $_POST['pf_no_c' . $i];
                    $conductorName = $_POST['conductor_' . $i . '_name'];
                    $existingConductorPF = $currentData['conductor_pf_' . $i];

                    if ($conductorPF !== $existingConductorPF) {
                        // Update existing conductor's to_date to current datetime
                        $updateCrewSql = "UPDATE crew_fix_data SET to_date = NOW() WHERE division_id = ? AND depot_id = ? AND sch_key_no = ? AND crew_pf = ?";
                        $crewStmt = $db->prepare($updateCrewSql);
                        $crewStmt->bind_param('siis', $division_id, $depot_id, $sch_key_no, $existingConductorPF);
                        $crewStmt->execute();

                        // Collect new conductor data for insertion
                        $crewUpdates[] = [
                            'token' => $conductorToken,
                            'pf' => $conductorPF,
                            'name' => $conductorName,
                            'designation' => 'Conductor'
                        ];
                    }
                }

                // Update schedule_master if necessary
                $updateScheduleSql = "UPDATE schedule_master SET single_crew = ? WHERE id = ?";
                $stmt = $db->prepare($updateScheduleSql); 
                $stmt->bind_param('si', $single_crew, $id);
                $stmt->execute();

                // Insert new crew details
                $insertCrewDataSql = "INSERT INTO crew_fix_data (sch_key_no, division_id, depot_id, crew_token, crew_pf, crew_name, designation, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $crewStmt = $db->prepare($insertCrewDataSql);

                foreach ($crewUpdates as $crew) {
                    if ($crew['token'] != null) {
                        $crewStmt->bind_param('siisssss', $sch_key_no, $division_id, $depot_id, $crew['token'], $crew['pf'], $crew['name'], $crew['designation'], $user);
                        $crewStmt->execute();
                    }
                }
                // Update driver details
                for ($i = 1; $i <= 6; $i++) {
                    if ($i <= $numberOfDrivers) {
                        $driverToken = $_POST['driver_token_' . $i];
                        $driverPF = $_POST['pf_no_d' . $i];
                        $driverName = $_POST['driver_' . $i . '_name']; 

                        // Only update if there's a change
                        if (
                            $currentData['driver_token_' . $i] !== $driverToken ||
                            $currentData['driver_pf_' . $i] !== $driverPF ||
                            $currentData['driver_name_' . $i] !== $driverName
                        ) {
                            $updateDriverSql = "UPDATE schedule_master SET driver_token_" . $i . " = ?, driver_pf_" . $i . " = ?, driver_name_" . $i . " = ? WHERE id = ?";
                            $driverStmt = $db->prepare($updateDriverSql);
                            $driverStmt->bind_param('sssi', $driverToken, $driverPF, $driverName, $id);
                            $driverStmt->execute();
                        }
                    } else {
                        // Set extra drivers to null
                        $updateDriverSql = "UPDATE schedule_master SET driver_token_" . $i . " = NULL, driver_pf_" . $i . " = NULL, driver_name_" . $i . " = NULL WHERE id = ?";
                        $driverStmt = $db->prepare($updateDriverSql);
                        $driverStmt->bind_param('i', $id);
                        $driverStmt->execute();
                    }
                }

                // Update conductor details
                for ($i = 1; $i <= 3; $i++) {
                    if ($i <= $numberOfConductor) {
                        $conductorToken = $_POST['conductor_token_' . $i];
                        $conductorPF = $_POST['pf_no_c' . $i];
                        $conductorName = $_POST['conductor_' . $i . '_name'];

                        // Only update if there's a change
                        if (
                            $currentData['conductor_token_' . $i] !== $conductorToken ||
                            $currentData['conductor_pf_' . $i] !== $conductorPF ||
                            $currentData['conductor_name_' . $i] !== $conductorName
                        ) {
                            $updateConductorSql = "UPDATE schedule_master SET conductor_token_" . $i . " = ?, conductor_pf_" . $i . " = ?, conductor_name_" . $i . " = ? WHERE id = ?";
                            $conductorStmt = $db->prepare($updateConductorSql);
                            $conductorStmt->bind_param('sssi', $conductorToken, $conductorPF, $conductorName, $id);
                            $conductorStmt->execute();
                        }
                    } else {
                        // Set extra conductors to null
                        $updateConductorSql = "UPDATE schedule_master SET conductor_token_" . $i . " = NULL, conductor_pf_" . $i . " = NULL, conductor_name_" . $i . " = NULL WHERE id = ?";
                        $conductorStmt = $db->prepare($updateConductorSql);
                        $conductorStmt->bind_param('i', $id);
                        $conductorStmt->execute();
                    }
                }
                echo "<script>alert('Schedule updated successfully.');</script>";
                // Optionally redirect or refresh the page
            }
        }
    }
    ?>

    <?php
    // Prepare and execute the query to count schedules
    $sql_count = "SELECT COUNT(*) AS schedule_count
FROM schedule_master
WHERE division_id = ? AND depot_id = ? and status='1'";

    $stmt = $db->prepare($sql_count);
    $stmt->bind_param("ii", $_SESSION['DIVISION_ID'], $_SESSION['DEPOT_ID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Get the count of schedules
    $schedule_count = $row['schedule_count'];



    // Prepare and execute the query to get sch_count values
    $sql = "SELECT sch_count
            FROM schedule_master
            WHERE division_id = ? AND depot_id = ? and status='1'";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $_SESSION['DIVISION_ID'], $_SESSION['DEPOT_ID']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize counters
    $total_count = 0;

    // Process the result set
    while ($row = $result->fetch_assoc()) {
        // Check the value of sch_count and adjust the total count accordingly
        $sch_count = $row['sch_count'];
        if ($sch_count == 1) {
            $total_count += 1;
        } elseif ($sch_count == 2) {
            $total_count += 2;
        }
        // If there are other cases, handle them as needed
        // else {
        //     $total_count += $sch_count; // Adjust as needed
        // }
    }


    // Close the connection
    $stmt->close();
    ?>



    <style>
        .modal {
            z-index: 1050;
            /* Bootstrap default */
        }

        .modal-backdrop {
            z-index: 1040;
            /* Bootstrap default */
        }

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
        <h4>Depot: <?php echo $_SESSION['DEPOT']; ?></h4>
        <h4 class="center">SCHEDULE MASTER</h4>
        <h4 class="center">Schedule Counts: <?php echo $total_count; ?></h4>
        <h4 class="center">Departure Counts: <?php echo $schedule_count; ?></h4>

    </div>
    <table id="dataTable4">
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
        AND skm.depot_id = '" . $_SESSION['DEPOT_ID'] . "'
        and skm.status='1'
    ORDER BY 
        skm.sch_dep_time";


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
                    echo '<div style="white-space: nowrap;">';
                    echo '<button class="btn btn-warning update-details">Update</button>&nbsp;';
                    echo '<button class="btn btn-primary view-details">Details</button>';
                    echo '</div>';
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
                                        <label>Conductor Less Operation:</label>
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
                            <div id="driverAllocationMessage"></div>
                        `;

                        $('#crewOperationFields').html(crewOperationFieldsHtml);
                        // Set radio buttons based on the fetched data
                        if (details.single_crew === 'yes') {
                            $('#single_crew_yes').prop('checked', true);
                            $('#number_of_drivers').prop('disabled', false); // Disable if single crew is yes
                            $('#number_of_drivers').val(details.driver_count);
                            $('#number_of_conductor').val('').removeAttr('required');  // Correct method to remove attribute
                            $('#driverFields').empty(); // Clear existing fields
                            addConductorInputFields(0); // Clear conductors since single crew is 'yes'

                            // Generate driver fields based on fetched count
                            addDriverInputFields(details.driver_count, details); // Add the driver fields here
                        } else if (details.single_crew === 'no') {
                            $('#single_crew_no').prop('checked', true);
                            $('#number_of_drivers').prop('disabled', false);
                            $('#driverFields').empty();
                            $('#conductorColumn').show();
                            $('#number_of_drivers').val(details.driver_count);
                            $('#number_of_conductor').val(details.conductor_count);

                            // Generate driver and conductor fields based on fetched counts
                            addDriverInputFields(details.driver_count, details); // Add the driver fields
                            addConductorInputFields(details.conductor_count, details); // Add the conductor fields
                        }


                        // Listen for change in single crew operation checkboxes
                        $('input[name="single_crew_operation"]').change(function () {
                            var singleCrewOperation = $('input[name="single_crew_operation"]:checked').val();
                            if (singleCrewOperation === 'yes') {
                                $('#number_of_drivers').prop('disabled', false);
                                $('#conductorColumn').hide();
                                $('#number_of_conductor').val('').removeAttr('required');  // Correct method to remove attribute
                                $('#number_of_drivers').val('');
                                $('#driverFields').empty();
                                $('#conductorFields').empty();
                                addConductorInputFields(0);
                            } else if (singleCrewOperation === 'no') {
                                $('#number_of_drivers').prop('disabled', false);
                                $('#driverFields').empty();
                                $('#conductorFields').empty();
                                $('#conductorColumn').show();
                                $('#number_of_drivers').val('');
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
                                addDriverInputFields(numberOfDrivers, details);
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
                                addConductorInputFields(numberOfDrivers, details);
                            }
                        });

                        // Function to add driver input fields based on the number of drivers
                        function addDriverInputFields(numberOfDrivers, details) {
                            var driverFieldsHtml = '';

                            for (var i = 1; i <= numberOfDrivers; i++) {
                                // Assuming details is an object where properties like details.driver_pf_1, details.driver_name_1, etc., exist.
                                var driverToken = details[`driver_token_${i}`] || ''; // Retrieve driver token or set as empty
                                var driverPf = details[`driver_pf_${i}`] || '';       // Retrieve driver PF or set as empty
                                var driverName = details[`driver_name_${i}`] || '';   // Retrieve driver name or set as empty

                                driverFieldsHtml += `
                    <div class="row driver-field">
                        <div class="col">
                            <div class="form-group">
                                <label for="driver_token_${i}">Driver ${i} Token:</label>
                                <input type="text" id="driver_token_${i}" name="driver_token_${i}" value="${driverToken}" class="form-control" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="pf_no_d${i}">Driver ${i} PF:</label>
                                <input type="text" id="pf_no_d${i}" name="pf_no_d${i}" value="${driverPf}" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="driver_${i}_name">Driver ${i} Name:</label>
                                <input type="text" id="driver_${i}_name" name="driver_${i}_name" value="${driverName}" class="form-control" readonly>
                            </div>
                        </div>
                    </div>`;
                            }

                            $('#driverFields').html(driverFieldsHtml);

                            // Add event listener for each driver token input field
                            for (var i = 1; i <= numberOfDrivers; i++) {
                                (function (i) {
                                    $('#driver_token_' + i).on('blur', function () {
                                        var tokenNumber = $(this).val();
                                        if (tokenNumber) {
                                            // Fetch driver details first (as it populates PF number)
                                            fetchDriverDetails(tokenNumber, 'driver_' + i + '_name', 'pf_no_d' + i, 'driver_token_' + i, division, depot);

                                            // Delay to wait for token details to be fetched
                                            setTimeout(function () {
                                                var pfNumber = $('#pf_no_d' + i).val();  // Get PF number after fetching details

                                                // Now check for duplicate token only if PF number is also the same
                                                if (isDuplicateTokenWithPF(tokenNumber, pfNumber, i, 'driver')) {
                                                    alert(`Duplicate entry for token ${tokenNumber} with the same PF number. Please update with another token or PF number.`);
                                                    clearFields('driver_' + i + '_name', 'pf_no_d' + i, 'driver_token_' + i);
                                                }
                                            }, 500);  // Adjust delay if necessary
                                        }
                                    });
                                })(i);
                            }


                        }

                        // Listen for input in number of conductors field
                        $('#number_of_conductor').on('input', function () {
                            var numberOfConductors = $(this).val();
                            addConductorInputFields(numberOfConductors, details);
                        });

                        // Function to add conductor input fields based on the number of conductors
                        function addConductorInputFields(numberOfConductors, details) {
                            var conductorFieldsHtml = '';

                            for (var i = 1; i <= numberOfConductors; i++) {
                                // Assuming details is an object where properties like details.conductor_pf_1, details.conductor_name_1, etc., exist.
                                var conductorToken = details[`conductor_token_${i}`] || '';  // Retrieve conductor token or set as empty
                                var conductorPf = details[`conductor_pf_${i}`] || '';        // Retrieve conductor PF or set as empty
                                var conductorName = details[`conductor_name_${i}`] || '';    // Retrieve conductor name or set as empty

                                conductorFieldsHtml += `
                    <div class="row conductor-field">
                        <div class="col">
                            <div class="form-group">
                                <label for="conductor_token_${i}">Conductor ${i} Token:</label>
                                <input type="text" id="conductor_token_${i}" name="conductor_token_${i}" value="${conductorToken}" class="form-control" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="pf_no_c${i}">Conductor ${i} PF:</label>
                                <input type="text" id="pf_no_c${i}" name="pf_no_c${i}" value="${conductorPf}" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="conductor_${i}_name">Conductor ${i} Name:</label>
                                <input type="text" id="conductor_${i}_name" name="conductor_${i}_name" value="${conductorName}" class="form-control" readonly>
                            </div>
                        </div>
                    </div>`;
                            }

                            $('#conductorFields').html(conductorFieldsHtml); // Inject the generated HTML into the conductorFields element


                            // Add event listener for each conductor token input field
                            for (var i = 1; i <= numberOfConductors; i++) {
                                (function (i) {
                                    $('#conductor_token_' + i).on('blur', function () {
                                        var tokenNumber = $(this).val();
                                        if (tokenNumber) {
                                            // Fetch conductor details first (as it populates PF number)
                                            fetchConductorDetails(tokenNumber, 'conductor_' + i + '_name', 'pf_no_c' + i, 'conductor_token_' + i, division, depot);

                                            // Delay to wait for token details to be fetched
                                            setTimeout(function () {
                                                var pfNumber = $('#pf_no_c' + i).val();  // Get PF number after fetching details

                                                // Now check for duplicate token only if PF number is also the same
                                                if (isDuplicateTokenWithPF(tokenNumber, pfNumber, i, 'conductor')) {
                                                    alert(`Duplicate entry for token ${tokenNumber} with the same PF number. Please update with another token or PF number.`);
                                                    clearFields('conductor_' + i + '_name', 'pf_no_c' + i, 'conductor_token_' + i);
                                                }
                                            }, 500);  // Adjust delay if necessary
                                        }
                                    });
                                })(i);
                            }

                        }
                        var division = '<?php echo $division; ?>'; // Ensure $division is correctly populated
var depot = '<?php echo $depot; ?>'; // Ensure $depot is correctly populated

function fetchConductorDetails(tokenNumber, nameElementId, pfElementId, tokenElementId, division, depot) {
    const tokenString = tokenNumber.toString();

    function fetchDataFromAPIs() {
        return new Promise((resolve, reject) => {
            let combinedData = [];

            // First API call
            var xhr1 = new XMLHttpRequest();
            xhr1.open('GET', '<?php echo getBaseUrl(); ?>/data.php?division=' + division + '&depot=' + depot, true);
            xhr1.onload = function () {
                if (xhr1.status === 200) {
                    var response1 = JSON.parse(this.responseText);
                    combinedData = combinedData.concat(response1.data || []);  // Append data if it exists

                    // Proceed to the second API call
                    var xhr2 = new XMLHttpRequest();
                    xhr2.open('GET', '../database/private_emp_api.php?division=' + division + '&depot=' + depot, true);
                    xhr2.onload = function () {
                        if (xhr2.status === 200) {
                            var response2 = JSON.parse(this.responseText);
                            combinedData = combinedData.concat(response2.data || []); // Append data if it exists
                            resolve(combinedData);
                        } else {
                            reject('Error fetching data from the second API.');
                        }
                    };
                    xhr2.onerror = function () {
                        reject('Network error while fetching from the second API.');
                    };
                    xhr2.send();
                } else {
                    reject('Error fetching data from the first API.');
                }
            };
            xhr1.onerror = function () {
                reject('Network error while fetching from the first API.');
            };
            xhr1.send();
        });
    }

    fetchDataFromAPIs()
        .then(matchingDrivers => {
            var filteredDrivers = matchingDrivers.filter(driver => {
                const driverTokenString = driver.token_number ? driver.token_number.toString() : '';

                return driver.Division.trim() === division &&
                    driver.Depot.trim() === depot &&
                    driverTokenString === tokenString;
            });

            if (filteredDrivers.length > 0) {
                if (filteredDrivers.length === 1) {
                    var driver = filteredDrivers[0];

                    if (driver.EMP_DESGN_AT_APPOINTMENT === "DRIVER") {
                        alert('The employee is a DRIVER. Please enter the token number of a conductor or Driver cum Conductor.');
                        clearFields(nameElementId, pfElementId, tokenElementId);
                        return;
                    }

                    document.getElementById(nameElementId).value = driver.EMP_NAME || '';
                    document.getElementById(pfElementId).value = driver.EMP_PF_NUMBER || '';
                    checkScheduleMaster(driver.EMP_PF_NUMBER, driver.EMP_NAME, tokenNumber, nameElementId, pfElementId, tokenElementId);

                } else if (filteredDrivers.length > 1) {
                    openDriverSelectionModal(filteredDrivers, function (selectedDriver) {
                        if (selectedDriver.EMP_DESGN_AT_APPOINTMENT === "DRIVER") {
                            alert('The selected employee is a DRIVER. Please select a conductor or Driver cum Conductor.');
                            $('#driverSelectionModal').modal('hide');
                            clearFields(nameElementId, pfElementId, tokenElementId);
                            return;
                        }

                        document.getElementById(nameElementId).value = selectedDriver.EMP_NAME || '';
                        document.getElementById(pfElementId).value = selectedDriver.EMP_PF_NUMBER || '';
                        checkScheduleMaster(selectedDriver.EMP_PF_NUMBER, selectedDriver.EMP_NAME, tokenNumber, nameElementId, pfElementId, tokenElementId);
                    });
                } else {
                    alert('No Conductor/DCC found for the ' + division + ' division and ' + depot + ' depot.');
                    clearFields(nameElementId, pfElementId, tokenElementId);
                }
            } else {
                alert('No Conductor/DCC found for the ' + division + ' division and ' + depot + ' depot.');
                clearFields(nameElementId, pfElementId, tokenElementId);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            clearFields(nameElementId, pfElementId, tokenElementId);
        });
}






                        var division = '<?php echo $division; ?>'; // Ensure $division is correctly populated
                        var depot = '<?php echo $depot; ?>'; // Ensure $depot is correctly populated

                        function fetchDriverDetails(tokenNumber, nameElementId, pfElementId, tokenElementId, division, depot) {

                           
                            function processDriversData(driversData1, driversData2) {
                                var combinedData = [...driversData1]; // Start with the first API data

                                if (driversData2.length > 0) {
                                    combinedData = [...combinedData, ...driversData2]; // Add second API data if available
                                }

                                if (!combinedData || combinedData.length === 0) {
                                    clearFields(nameElementId, pfElementId, tokenElementId);
                                    return;
                                }

                                // Filter the data based on division, depot, and token number (string or integer matching)
                                var matchingDrivers = combinedData.filter(driver => {
                                    return driver.Division.trim() === sessionDivision &&
                                        driver.Depot.trim() === sessionDepot &&
                                        (driver.token_number == tokenNumber); // Ensure token number matches regardless of type
                                });


                                if (matchingDrivers.length === 1) {
                                    var driver = matchingDrivers[0];

                                    if (driver.EMP_DESGN_AT_APPOINTMENT === "CONDUCTOR") {
                                        alert('The employee is a CONDUCTOR. Please enter the token number of a Driver or Driver cum Conductor.');
                                        clearFields(nameElementId, pfElementId, tokenElementId);
                                        return;
                                    }

                                    document.getElementById(nameElementId).value = driver.EMP_NAME || '';
                                    document.getElementById(pfElementId).value = driver.EMP_PF_NUMBER || '';

                                    // Call the function to check the schedule master
                                    checkScheduleMaster(driver.EMP_PF_NUMBER, driver.EMP_NAME, tokenNumber, nameElementId, pfElementId, tokenElementId);
                                } else if (matchingDrivers.length > 1) {
                                    openDriverSelectionModal(matchingDrivers, function (selectedDriver) {
                                        if (selectedDriver.EMP_DESGN_AT_APPOINTMENT === "CONDUCTOR") {
                                            alert('The selected employee is a Conductor. Please select a Driver or Driver cum Conductor.');
                                            $('#driverSelectionModal').modal('hide'); // Hide the modal
                                            clearFields(nameElementId, pfElementId, tokenElementId);
                                            return;
                                        }

                                        document.getElementById(nameElementId).value = selectedDriver.EMP_NAME || '';
                                        document.getElementById(pfElementId).value = selectedDriver.EMP_PF_NUMBER || '';

                                        // Call the function to check the schedule master
                                        checkScheduleMaster(selectedDriver.EMP_PF_NUMBER, selectedDriver.EMP_NAME, tokenNumber, nameElementId, pfElementId, tokenElementId);
                                    });
                                } else {
                                    alert('No Driver/DCC found for the ' + sessionDivision + ' division and ' + sessionDepot + ' depot.');
                                    clearFields(nameElementId, pfElementId, tokenElementId);
                                }
                            }

                            // First API call
                            var xhr1 = new XMLHttpRequest();
                            xhr1.open('GET', '<?php echo getBaseUrl(); ?>/data.php?division=' + sessionDivision + '&depot=' + sessionDepot, true);

                            xhr1.onload = function () {
                                if (xhr1.status === 200) {
                                    var response1 = JSON.parse(this.responseText);
                                    var driversData1 = response1.data || [];

                                    // Second API call
                                    var xhr2 = new XMLHttpRequest();
                                    xhr2.open('GET', '../database/private_emp_api.php?division=' + sessionDivision + '&depot=' + sessionDepot, true);

                                    xhr2.onload = function () {
                                        if (xhr2.status === 200) {
                                            var response2 = JSON.parse(this.responseText);
                                            var driversData2 = response2.data || [];

                                            if (driversData2.length === 0) {
                                            }

                                            // Process combined data from both APIs
                                            processDriversData(driversData1, driversData2);
                                        } else {
                                            alert('An error occurred while fetching the second driver details.');
                                            clearFields(nameElementId, pfElementId, tokenElementId);
                                        }
                                    };

                                    xhr2.onerror = function () {
                                        alert('A network error occurred while fetching the second driver details.');
                                        clearFields(nameElementId, pfElementId, tokenElementId);
                                    };

                                    xhr2.send();
                                } else {
                                    alert('An error occurred while fetching the first driver details.');
                                    clearFields(nameElementId, pfElementId, tokenElementId);
                                }
                            };

                            xhr1.onerror = function () {
                                alert('A network error occurred while fetching the first driver details.');
                                clearFields(nameElementId, pfElementId, tokenElementId);
                            };

                            xhr1.send();
                        }


                        function openDriverSelectionModal(drivers, onSelect) {

                            var driverList = document.getElementById('driverList'); // List inside your modal to show drivers

                            // Clear previous list items
                            driverList.innerHTML = '';

                            // Populate the list with drivers
                            drivers.forEach(driver => {
                                var listItem = document.createElement('li');
                                listItem.classList.add('list-group-item');
                                listItem.textContent = `Token: ${driver.token_number}, Name: ${driver.EMP_NAME}, Pf no: ${driver.EMP_PF_NUMBER}`;
                                listItem.onclick = function () {

                                    // When a driver is selected, call the callback with the selected driver data
                                    onSelect(driver);

                                    // Hide the modal
                                    $('#driverSelectionModal').modal('hide');
                                };
                                driverList.appendChild(listItem);
                            });

                            // Show the modal
                            $('#driverSelectionModal').modal('show');

                        }

                        // Function to close the modal
                        function closeModaltoken() {
                            var modal = document.getElementById('driverSelectionModal');
                            modal.style.display = 'none';
                        }



                        // Check in the schedule_master table if the PF number is already allotted
                        function checkScheduleMaster(pfNumber, empName, tokenNumber, nameElementId, pfElementId, tokenElementId) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', '../database/schedule_crew_check.php?pf_number=' + pfNumber, true); // Assuming schedule_check.php handles this
                            xhr.onload = function () {
                                if (xhr.status === 200) {
                                    var response = JSON.parse(this.responseText);
                                    var scheduleData = response.schedule;

                                    if (scheduleData && scheduleData.length > 0) {
                                        // PF number is already assigned, show modal for reallocation
                                        var scheduleNo = scheduleData[0].sch_key_no;
                                        showReallocationModal(empName, scheduleNo, pfNumber, tokenNumber, nameElementId, pfElementId, tokenElementId);
                                    } else {
                                        // PF number not found, return the data
                                        returnFetchedData(pfNumber, empName, tokenNumber);
                                    }
                                }
                            };
                            xhr.send();
                        }

                        // Show modal and handle user response
                        // Show modal and handle user response
                        function showReallocationModal(empName, scheduleNo, pfNumber, tokenNumber, nameElementId, pfElementId, tokenElementId) {
                            // Update modal message
                            document.getElementById('modal-message').innerText = empName + ' is already allotted to schedule ' + scheduleNo + '. Do you want to reallocate?';
                            document.getElementById('modal-message1').innerText = 'Note: Once you click Yes then the Driver/Conductor/DCC will be realocate to this schedule and removed from previous alloted schedule: ' + scheduleNo + '';

                            // Show the Bootstrap modal
                            $('#reallocationModal').modal('show');

                            // Handle Yes click for reallocation
                            document.getElementById('confirmReallocation').onclick = function () {
                                // Call AJAX to update schedule_master and reallocate
                                reallocateDriverDCC(pfNumber, tokenNumber, scheduleNo);
                                $('#reallocationModal').modal('hide'); // Close modal after reallocation
                            };

                            // Handle No click for not reallocating
                            document.getElementById('cancelReallocation').onclick = function () {
                                $('#reallocationModal').modal('hide');  // Close the modal
                                clearFields(nameElementId, pfElementId, tokenElementId);  // Clear the input fields
                            };
                        }


                        // AJAX call to update driver/DCC in schedule_master
                        function reallocateDriverDCC(pfNumber, tokenNumber, scheduleNo) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', '../database/reallocate_driver_dcc.php', true);
                            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                            // Log the data being sent to the console
                            xhr.onload = function () {
                                if (xhr.status === 200) {
                                    alert('Driver/DCC has been reallocated successfully.');
                                    // After reallocation, you can handle the data return logic here
                                    returnFetchedData(pfNumber, '', tokenNumber);
                                } else {
                                    alert('An error occurred while reallocating.');
                                }
                            };
                            xhr.send('pf_number=' + pfNumber + '&token_number=' + tokenNumber + '&old_schedule=' + scheduleNo);
                        }

                        // Handle the return of fetched data after reallocation
                        function returnFetchedData(pfNumber, empName, tokenNumber) {
                            // Return the fetched data to the form after allocation
                            document.getElementById('someInputFieldForPF').value = pfNumber;
                            document.getElementById('someInputFieldForName').value = empName;
                            document.getElementById('someInputFieldForToken').value = tokenNumber;
                        }

                        // Clear fields if needed
                        function clearFields(nameElementId, pfElementId, tokenElementId) {
                            document.getElementById(nameElementId).value = '';
                            document.getElementById(pfElementId).value = '';
                            document.getElementById(tokenElementId).value = '';
                        }



                        // Function to get maximum allowed drivers based on service class and single crew operation choice
                        function getMaxAllowedDrivers(serviceClassName, serviceTypeName, singleCrewOperation) {
                            var maxAllowedDrivers = null;

                            switch (serviceClassName) {
                                case '1':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 1 : 1;
                                    break;
                                case '2':
                                    maxAllowedDrivers = singleCrewOperation === 'yes' ? 2 : 2;
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
                            $('.driver-field input[type="text"]').each(function () {
                                var fieldId = $(this).attr('id');
                                var fieldIndex = parseInt(fieldId.match(/\d+/)[0]);
                                if (type === 'driver' && currentIndex === fieldIndex) return; // Skip current driver field
                                if ($(this).val() == token) {
                                    isDuplicate = true;
                                    return false; // Break loop
                                }
                            });

                            // Check conductor fields
                            if (!isDuplicate) {
                                $('.conductor-field input[type="text"]').each(function () {
                                    var fieldId = $(this).attr('id');
                                    var fieldIndex = parseInt(fieldId.match(/\d+/)[0]);
                                    if (type === 'conductor' && currentIndex === fieldIndex) return; // Skip current conductor field
                                    if ($(this).val() == token) {
                                        isDuplicate = true;
                                        return false; // Break loop
                                    }
                                });
                            }

                            return isDuplicate;
                        }
                        function isDuplicateTokenWithPF(token, pfNumber, currentIndex, type) {
                            var isDuplicate = false;

                            // Check driver fields
                            $('.driver-field input[type="text"]').each(function () {
                                var fieldId = $(this).attr('id');
                                var fieldIndex = parseInt(fieldId.match(/\d+/)[0]);

                                if (type === 'driver' && currentIndex === fieldIndex) return; // Skip current driver field

                                var currentToken = $('#driver_token_' + fieldIndex).val();
                                var currentPF = $('#pf_no_d' + fieldIndex).val();

                                // Check if both token and PF number match
                                if (currentToken === token && currentPF === pfNumber) {
                                    isDuplicate = true;
                                    return false; // Break loop if duplicate found
                                }
                            });

                            // Check conductor fields
                            if (!isDuplicate) {
                                $('.conductor-field input[type="text"]').each(function () {
                                    var fieldId = $(this).attr('id');
                                    var fieldIndex = parseInt(fieldId.match(/\d+/)[0]);

                                    if (type === 'conductor' && currentIndex === fieldIndex) return; // Skip current conductor field

                                    var currentToken = $('#conductor_token_' + fieldIndex).val(); // Adjust ID as needed
                                    var currentPF = $('#pf_no_c' + fieldIndex).val(); // Assuming PF number for conductor has a different ID pattern

                                    // Check if both token and PF number match
                                    if (currentToken === token && currentPF === pfNumber) {
                                        isDuplicate = true;
                                        return false; // Break loop if duplicate found
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
        function clearFields1(nameElementId, pfElementId, tokenElementId) {
            document.getElementById(nameElementId).value = '';
            document.getElementById(pfElementId).value = '';
            document.getElementById(tokenElementId).value = ''; // Ensure the token field is also cleared
        }

    </script>
    <!-- Modal Structure -->
    <div class="modal fade" id="driverSelectionModal" tabindex="-1" role="dialog"
        aria-labelledby="driverSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="driverSelectionModalLabel">The Entered Token has more then one Enteries
                        Please select any one</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul id="driverList" class="list-group">
                        <!-- List items will be dynamically added here -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModaltoken()">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal structure using Bootstrap -->
    <div class="modal fade" id="reallocationModal" tabindex="-1" role="dialog" aria-labelledby="reallocationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reallocationModalLabel">Reallocation Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="modal-message"></p>
                    <p style="color:red" id="modal-message1"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="confirmReallocation">Yes</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="cancelReallocation">No</button>
                </div>
            </div>
        </div>
    </div>


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
                            'single_crew': 'Conductor less Operation',
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
    <script>
        $(document).ready(function () {
            $('#dataTable4').DataTable({
                "paging": true, // Enable pagination
                "lengthChange": true, // Enable the row count dropdown
                "searching": true, // Enable search functionality
                "ordering": true, // Enable sorting
                "info": true, // Show table information summary
                "autoWidth": true, // Automatically adjust column widths
                "order": [[4, 'asc']] // Default ordering based on the 5th column (index 4), 'asc' means ascending
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