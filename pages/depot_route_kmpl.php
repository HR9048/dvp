<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE']) || !isset($_SESSION['DIVISION_ID']) || !isset($_SESSION['DEPOT_ID'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Bunk' || $_SESSION['JOB_TITLE'] == 'DM')) {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id1 = $_SESSION['KMPL_DIVISION'];
    $depot_id1 = $_SESSION['KMPL_DEPOT'];
?>
    <style>
        .table-container {
            max-width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 8px;
            white-space: nowrap;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .hidden {
            display: none;
        }

        #loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            justify-content: center;
            align-items: center;
            font-size: 20px;
            font-weight: bold;
        }
    </style>

    <script>
        $(document).ready(function() {
            $('.route-select, .driver-select, .driver-selects').select2();
        });

        function calculateKMPL(element) {
            // Find the row where the input is located
            const row = element.closest('tr');

            // Get the inputs for km_operated and hsd within the same row
            const kmOperatedInput = row.querySelector('.km_operated');
            const hsdInput = row.querySelector('.hsd');
            const kmplInput = row.querySelector('.kmpl');

            // Parse values as numbers
            const kmOperated = parseFloat(kmOperatedInput.value) || 0;
            const hsd = parseFloat(hsdInput.value) || 0;

            // Calculate kmpl (handle division by zero)
            const kmpl = hsd > 0 ? (kmOperated / hsd).toFixed(2) : 0;

            // Set the kmpl value
            kmplInput.value = kmpl;
            updateTotal(); // Recalculate total row when any row is updated

        }

        function updateTotal() {
            let totalKmOperated = 0;
            let totalHsd = 0;

            document.querySelectorAll('.km_operated').forEach(input => {
                totalKmOperated += parseFloat(input.value) || 0;
            });

            document.querySelectorAll('.hsd').forEach(input => {
                totalHsd += parseFloat(input.value) || 0;
            });

            let totalKmpl = totalHsd > 0 ? (totalKmOperated / totalHsd).toFixed(2) : '0.00';

            // Update only if elements exist
            let totalKmOperatedEl = document.getElementById('total_km_operated');
            let totalHsdEl = document.getElementById('total_hsd');
            let totalKmplEl = document.getElementById('total_kmpl');

            if (totalKmOperatedEl) totalKmOperatedEl.textContent = totalKmOperated.toFixed(2);
            if (totalHsdEl) totalHsdEl.textContent = totalHsd.toFixed(2);
            if (totalKmplEl) totalKmplEl.textContent = totalKmpl;
        }
    </script>

    <div id="loading">
        Loading, please wait...
    </div>

    <div id="page-content">
        <form id="busReportForm" method="POST" onsubmit="return validateAndSubmit1();">
            <label for="reportDate">Select Date:</label>
            <input type="date" name="report_date" id="reportDate" required>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <div id="loading" style="display: none;">
        <p>Loading, please wait...</p>
    </div>

    <script>
        function validateAndSubmit1() {
            let reportDate = document.getElementById('reportDate').value;
            if (!reportDate) {
                Swal.fire('Error', 'Please select a date.', 'error');
                return false;
            }

            let selectedDate = new Date(reportDate);
            let today = new Date();
            let yesterday = new Date();
            let fourDaysAgo = new Date();

            yesterday.setDate(today.getDate());
            fourDaysAgo.setDate(today.getDate() - 200);

            // Convert dates to 'YYYY-MM-DD' format for accurate comparison
            let selectedDateString = selectedDate.toISOString().split('T')[0];
            let yesterdayString = yesterday.toISOString().split('T')[0];
            let fourDaysAgoString = fourDaysAgo.toISOString().split('T')[0];

            if (selectedDateString > yesterdayString || selectedDateString < fourDaysAgoString) {
                Swal.fire('Date Outside Allowed Range',
                    `Date must be between ${fourDaysAgo.toLocaleDateString('en-GB')} and ${yesterday.toLocaleDateString('en-GB')}.`,
                    'error'
                );
                return false; // Prevent submission
            }

            // If date is valid, show loading screen and hide content
            document.getElementById("loading").style.display = "flex";
            document.getElementById("page-content").style.display = "none";

            return true; // Allow form submission
        }

        window.onload = function() {
            document.getElementById("loading").style.display = "none";
            document.getElementById("page-content").style.display = "block";
        };
    </script>

    <form method="post">
        <div id="reportTable" style="margin-top: 20px;">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_date'])) {
                $report_date = $_POST['report_date'];
                $division_id = $_SESSION['DIVISION_ID'];
                $depot_id = $_SESSION['DEPOT_ID'];
                echo '<script>document.getElementById("loading").style.display = "flex";</script>';
                echo '<script>document.getElementById("page-content").style.display = "none";</script>';
                ob_flush();
                flush();
                // Run your script here (fetch data, process, etc.)
                sleep(3); // Simulating processing time
                echo '<button type="button" class="btn btn-success" id="addRepeatVehicle">Add Repeat Vehicle</button>';
                // Fetch bus numbers
                $busQuery = "SELECT br.bus_number, br.make, br.emission_norms 
FROM bus_registration br
WHERE br.division_name = '$division_id' 
AND br.depot_name = '$depot_id'

UNION 

SELECT vd.bus_number, COALESCE(br.make, '') AS make, COALESCE(br.emission_norms, '') AS emission_norms
FROM vehicle_deputation vd
LEFT JOIN bus_registration br ON vd.bus_number = br.bus_number
WHERE vd.t_division_id = '$division_id' 
AND vd.t_depot_id = '$depot_id' 
AND vd.tr_date = '$report_date' 
AND vd.status NOT IN (1) 
AND vd.deleted = 0

";
                $busResult = $db->query($busQuery);

                // Fetch existing vehicle_kmpl data for this date, division, and depot
                $kmplQuery = "SELECT * FROM vehicle_kmpl 
                              WHERE division_id = '$division_id' AND depot_id = '$depot_id' AND date = '$report_date' and c_change != '1' and deleted = '0'";
                $kmplResult = $db->query($kmplQuery);

                // Store existing KMPL data in an associative array using bus_number as key
                $kmplData = [];
                while ($kmplRow = $kmplResult->fetch_assoc()) {
                    $kmplData[$kmplRow['bus_number']] = $kmplRow;
                }
                $formatted_date = date("d-m-Y", strtotime($report_date));
                if ($busResult && $busResult->num_rows > 0) {
                    echo '<div class="table-container"><h1 class="text-center">Division: ' . $_SESSION['DIVISION'] . '  Depot: ' . $_SESSION['DEPOT'] . ' KMPL Entry for Date: ' . htmlspecialchars($formatted_date) . '</h1>';
                    echo '<table id="kmpl_table" border="1" cellpadding="5" cellspacing="0">';
                    echo '<tr>
                        <th>Sl. No.</th>
                        <th>Bus Number</th>
                        <th>Route Number</th>
                        <th>Driver Token 1</th>
                        <th>Driver Token 2</th>
                        <th>Logsheet No</th>
                        <th>KM Operated</th>
                        <th>HSD</th>
                        <th>KMPL</th>
                        <th>Thump Status</th>
                        <th>Logsheet Defects</th>
                        <th class="hidden">Make</th>
                        <th class="hidden">Norms</th>
                        <th class="hidden">Division</th>
                        <th class="hidden">Depot</th>
                        <th class="hidden">ID</th>
                        <th class="hidden">vc</th>
                        <th class="hidden">cc</th>
                        <th>Action</th>
                      </tr>';

                    $sl_no = 1;

                    // Fetch driver tokens from APIs
                    $apiUrl1 = "http://localhost:8880/dvp/includes/data.php?division=$division_id1&depot=$depot_id1";
                    $apiUrl2 = "http://localhost:8880/dvp/database/private_emp_api.php?division=$division_id1&depot=$depot_id1";
                    $apiUrl3 = "http://localhost:8880/dvp/database/deputation_crew_api1.php?division=$division_id1&depot=$depot_id1&date=$report_date";

                    $drivers1 = json_decode(file_get_contents($apiUrl1), true)['data'] ?? [];
                    $drivers2 = json_decode(file_get_contents($apiUrl2), true)['data'] ?? [];
                    $drivers3 = json_decode(file_get_contents($apiUrl3), true)['data'] ?? [];
                    $allDrivers = array_merge($drivers1, $drivers2, $drivers3);
                    while ($busRow = $busResult->fetch_assoc()) {
                        $bus_number = $busRow['bus_number'];
                        $make = $busRow['make'];
                        $emission_norms = $busRow['emission_norms'];

                        // Fetch vehicle_kmpl data for this bus (if exists)
                        $existingData = $kmplData[$bus_number] ?? null;


                        // Fetch route numbers
                        $routeQuery = "SELECT sch_key_no FROM schedule_master WHERE division_id = '$division_id' AND depot_id = '$depot_id'";
                        $routeResult = $db->query($routeQuery);


                        echo '<tr>';
                        echo '<td>' . $sl_no++ . '</td>';
                        echo '<td>' . htmlspecialchars($bus_number) . '</td>';

                        // Route number select
                        echo '<td>';
                        echo '<select class="route-select" onchange="handleScheduleChange(this)">';
                        echo '<option style="width:100%;" value="">Select</option>'; // Default option

                        if ($routeResult && $routeResult->num_rows > 0) {
                            while ($routeRow = $routeResult->fetch_assoc()) {
                                $selected = ($existingData['route_no'] ?? '') == $routeRow['sch_key_no'] ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($routeRow['sch_key_no']) . '" ' . $selected . '>' . htmlspecialchars($routeRow['sch_key_no']) . '</option>';
                            }
                        }
                        echo '<option value="CC">CC</option>';
                        echo '<option value="BD">BD</option>';
                        echo '<option value="Extra Operation">Extra Operation</option>';
                        echo '<option value="Jatra Operation">Jatra Operation</option>';
                        echo '<option value="DWS">DWS</option>';
                        echo '<option value="RWY">RWY</option>';
                        echo '<option value="Road Test">Road Test</option>';
                        echo '<option value="Relief">Relief</option>';

                        echo '</select>';
                        echo '</td>';



                        // Driver Token 1 select
                        echo '<td>';
                        echo '<select style="width:100%;" class="driver-select" onchange="updateOptions();">';
                        echo '<option value="">Select</option>'; // Default option
                        if (!empty($allDrivers)) {
                            foreach ($allDrivers as $driver) {
                                if (!empty($driver['EMP_PF_NUMBER']) && !empty($driver['token_number']) && !empty($driver['EMP_NAME'])) {
                                    $selected = ($existingData['driver_1_pf'] ?? '') == $driver['EMP_PF_NUMBER'] ? 'selected' : ''; // Check existing data
                                    echo '<option value="' . htmlspecialchars($driver['EMP_PF_NUMBER']) . '" ' . $selected . '>' .
                                        htmlspecialchars($driver['token_number'] . ' (' . $driver['EMP_NAME'] . ')') .
                                        '</option>';
                                }
                            }
                        } else {
                            echo '<option value="">No Drivers Available</option>';
                        }
                        echo '</select>';
                        echo '</td>';

                        // Driver Token 2 select
                        echo '<td>';
                        echo '<select style="width:100%;" class="driver-selects" onchange="updateOptions();">';
                        echo '<option value="">Select</option>'; // Default option
                        if (!empty($allDrivers)) {
                            foreach ($allDrivers as $driver) {
                                if (!empty($driver['EMP_PF_NUMBER']) && !empty($driver['token_number']) && !empty($driver['EMP_NAME'])) {
                                    $selected = ($existingData['driver_2_pf'] ?? '') == $driver['EMP_PF_NUMBER'] ? 'selected' : ''; // Check existing data
                                    echo '<option value="' . htmlspecialchars($driver['EMP_PF_NUMBER']) . '" ' . $selected . '>' .
                                        htmlspecialchars($driver['token_number'] . ' (' . $driver['EMP_NAME'] . ')') .
                                        '</option>';
                                }
                            }
                        } else {
                            echo '<option value="">No Drivers Available</option>';
                        }
                        echo '</select>';
                        echo '</td>';


                        echo '<td><input style="width:100%;" type="text" name="logsheet_no[]" value="' . ($existingData['logsheet_no'] ?? '') . '"></td>';
                        echo '<td><input style="width:100%;" type="number" name="km_operated[]" class="km_operated" value="' . ($existingData['km_operated'] ?? '') . '" oninput="calculateKMPL(this)"></td>';
                        echo '<td><input style="width:90px;" type="number" name="hsd[]" class="hsd" value="' . ($existingData['hsd'] ?? '') . '" oninput="calculateKMPL(this)"></td>';
                        echo '<td><input style="width:90px;" type="number" name="kmpl[]" class="kmpl" value="' . ($existingData['kmpl'] ?? '') . '" readonly></td>';
                        echo '<td>';
                        if ($make == 'Leyland' && $emission_norms == 'BS-6') {
                            echo '<select style="width:100%;" name="thump_status[]">';
                            echo '<option value="">Select</option>';
                            $feedbackQuery = "SELECT id, name, thumbs, percentage FROM feedback WHERE id NOT IN('0')";
                            $feedbackResult = $db->query($feedbackQuery);
                            if ($feedbackResult && $feedbackResult->num_rows > 0) {
                                while ($feedbackRow = $feedbackResult->fetch_assoc()) {
                                    $selected = ($existingData['thumps_id'] ?? '') == $feedbackRow['id'] ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($feedbackRow['id']) . '" ' . $selected . '>' .
                                        htmlspecialchars($feedbackRow['thumbs']) .
                                        '</option>';
                                }
                            } else {
                                echo '<option value="">No Feedback Available</option>';
                            }
                            echo '</select>';
                        } else {
                            echo 'N/A';
                        }
                        echo '</td>';


                        echo '<td><input style="width:100%;" type="text" name="remarks[]" value="' . ($existingData['remarks'] ?? '') . '"></td>';
                        echo '<td class="hidden">' . htmlspecialchars($make) . '</td>';
                        echo '<td class="hidden">' . htmlspecialchars($emission_norms) . '</td>';
                        echo '<td class="hidden">' . $division_id . '</td>';
                        echo '<td class="hidden">' . $depot_id . '</td>';
                        echo '<td class="hidden">' . ($existingData['id'] ?? '') . '</td>';
                        echo '<td class="v-change-cell hidden">' . ($existingData['v_change'] ?? '') . '</td>';
                        echo '<td class="c_change hidden">' . ($existingData['c_change'] ?? '') . '</td>';
                        echo '<td>';
                        if (!empty($existingData['id'])) {
                            echo ' <button type="button" class="delete-btn btn btn-danger" onclick="deleteRow(' . $existingData['id'] . ')">
                                    <i class="fas fa-trash"></i>
                                  </button> &nbsp;<button type="button" class="update-btn btn btn-primary">Update</button></td>';
                        } else {
                            echo ' <button style="width:100%;" type="button" class="update-btn btn btn-primary">Update</button></td>';
                        }
                        echo '</tr>';
                    }
                    // Query to fetch vehicle_kmpl data where c_change = '1'
                    $changedKmplQuery = "
    SELECT vk.*, br.make, br.emission_norms 
    FROM vehicle_kmpl vk
    LEFT JOIN bus_registration br ON vk.bus_number = br.bus_number
    WHERE vk.division_id = '$division_id' 
    AND vk.depot_id = '$depot_id' 
    AND vk.date = '$report_date' 
    AND vk.c_change in ('1','2') 
    AND vk.deleted = '0'
";


                    $changedKmplResult = $db->query($changedKmplQuery);

                    if ($changedKmplResult && $changedKmplResult->num_rows > 0) {
                        while ($changedRow = $changedKmplResult->fetch_assoc()) {
                            echo '<tr id="kmpl-row-' . $changedRow['id'] . '">';
                            echo '<td>' . $sl_no++ . '</td>';
                            echo '<td>' . htmlspecialchars($changedRow['bus_number']) . '</td>';

                            // Route number select with only existing database values
                            echo '<td>';
                            echo '<select style="width:100%;" class="route-select">';
                            echo '<option value="' . htmlspecialchars($changedRow['route_no']) . '" selected>' . htmlspecialchars($changedRow['route_no']) . '</option>';
                            echo '</select>';
                            echo '</td>';

                            // Driver Token 1 select
                            echo '<td>';
                            echo '<select style="width:100%;" class="driver-select">';
                            echo '<option value="' . htmlspecialchars($changedRow['driver_1_pf']) . '" selected>' . htmlspecialchars($changedRow['driver_1_pf']) . '</option>';
                            echo '</select>';
                            echo '</td>';

                            // Driver Token 2 select
                            echo '<td>';
                            echo '<select style="width:100%;" class="driver-selects">';
                            echo '<option value="' . htmlspecialchars($changedRow['driver_2_pf']) . '" selected>' . htmlspecialchars($changedRow['driver_2_pf']) . '</option>';
                            echo '</select>';
                            echo '</td>';

                            echo '<td><input style="width:100%;" type="text" name="logsheet_no[]" value="' . ($changedRow['logsheet_no'] ?? '') . '"></td>';
                            echo '<td><input style="width:100%;" type="number" name="km_operated[]" class="km_operated" value="' . ($changedRow['km_operated'] ?? '') . '" oninput="calculateKMPL(this)"></td>';
                            echo '<td><input style="width:90px;" type="number" name="hsd[]" class="hsd" value="' . ($changedRow['hsd'] ?? '') . '" oninput="calculateKMPL(this)"></td>';
                            echo '<td><input style="width:90px;" type="number" name="kmpl[]" class="kmpl" value="' . ($changedRow['kmpl'] ?? '') . '" readonly></td>';

                            echo '<td>';
                            if ($changedRow['make'] == 'Leyland' && $changedRow['emission_norms'] == 'BS-6') {
                                echo '<select style="width:100%;" name="thump_status[]">';
                                echo '<option value="">Select</option>';
                                $feedbackQuery = "SELECT id, name, thumbs, percentage FROM feedback WHERE id NOT IN('0')";
                                $feedbackResult = $db->query($feedbackQuery);
                                if ($feedbackResult && $feedbackResult->num_rows > 0) {
                                    while ($feedbackRow = $feedbackResult->fetch_assoc()) {
                                        $selected = ($changedRow['thumps_id'] ?? '') == $feedbackRow['id'] ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($feedbackRow['id']) . '" ' . $selected . '>' .
                                            htmlspecialchars($feedbackRow['thumbs']) .
                                            '</option>';
                                    }
                                } else {
                                    echo '<option value="">No Feedback Available</option>';
                                }
                                echo '</select>';
                            } else {
                                echo 'N/A';
                            }
                            echo '</td>';

                            echo '<td><input style="width:100%;" type="text" name="remarks[]" value="' . ($changedRow['remarks'] ?? '') . '"></td>';
                            echo '<td class="hidden">' . htmlspecialchars($changedRow['make']) . '</td>';
                            echo '<td class="hidden">' . htmlspecialchars($changedRow['emission_norms']) . '</td>';
                            echo '<td class="hidden">' . $division_id . '</td>';
                            echo '<td class="hidden">' . $depot_id . '</td>';
                            echo '<td class="hidden">' . htmlspecialchars($changedRow['id']) . '</td>';
                            echo '<td class="v-change-cell hidden">' . htmlspecialchars($changedRow['v_change']) . '</td>';
                            echo '<td class="c_change hidden">' . htmlspecialchars($changedRow['c_change']) . '</td>';

                            // Update button
                            echo '<td>';

                            // Delete button (with a trash icon)
                            echo ' <button type="button" class="delete-btn btn btn-danger" onclick="deleteRow(' . $changedRow['id'] . ')">
<i class="fa fa-trash"></i>
</button>&nbsp;<button type="button" class="update-btn btn btn-primary">Update</button>';

                            echo '</td>';
                            echo '</tr>';
                        }
                    }

                    echo '<tr id="total_row" style="font-weight: bold; background-color: #f2f2f2;">';
                    echo '<td>Total</td>';
                    echo '<td><input type="numner" value="1" hidden></td>';
                    echo '<td><input type="numner" value="1" hidden></td>';
                    echo '<td><input type="numner" value="1" hidden></td>';
                    echo '<td><input type="numner" value="1" hidden></td>';
                    echo '<td><input type="numner" value="1" hidden></td>';
                    echo '<td id="total_km_operated">0.00</td>';
                    echo '<td id="total_hsd">0.00</td>';
                    echo '<td id="total_kmpl">0.00</td>';
                    echo '<td></td>';
                    echo '<td></td>';
                    echo '<td class="hidden"></td>';
                    echo '<td class="hidden"></td>';
                    echo '<td class="hidden">' . $division_id . '</td>';
                    echo '<td class="hidden">' . $depot_id . '</td>';
                    echo '<td class="hidden"></td>';
                    echo '<td class="hidden"></td>';
                    echo '<td class="hidden"></td>';
                    echo '<td class="hidden1"></td>';
                    echo '</tr>';
                    echo '</table><br>';
                    echo '<div class="text-center my-3">';
                    echo '<button id="submitBtn" class="btn btn-success">Submit</button>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<p>No buses available for the selected depot and division.</p>';
                }
            }
            ?>
        </div>
    </form>
    <!-- Bootstrap Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Select Schedule & Drivers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form id="modalForm">
                        <div class="mb-3">
                            <label for="modalSchedule"><strong>Schedule No:</strong></label>
                            <select id="modalSchedule" class="form-control"></select>
                        </div>

                        <div class="mb-3">
                            <label for="modalDriver1"><strong>Driver 1:</strong></label>
                            <select id="modalDriver1" class="form-control"></select>
                        </div>

                        <div class="mb-3">
                            <label for="modalDriver2"><strong>Driver 2:</strong></label>
                            <select id="modalDriver2" class="form-control"></select>
                        </div>
                    </form>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitModalForm()">Submit</button>
                </div>
            </div>
        </div>
    </div>
    <?php


    // Fetch Route Numbers (Schedule Numbers)

    if (isset($report_date)) {



        $apiUrl11 = "http://localhost:8880/dvp/includes/data.php?division=$division_id1&depot=$depot_id1";
        $apiUrl21 = "http://localhost:8880/dvp/database/private_emp_api.php?division=$division_id1&depot=$depot_id1";
        $apiUrl31 = "http://localhost:8880/dvp/database/deputation_crew_api1.php?division=$division_id1&depot=$depot_id1&date=$report_date";

        $drivers11 = json_decode(file_get_contents($apiUrl11), true)['data'] ?? [];
        $drivers21 = json_decode(file_get_contents($apiUrl21), true)['data'] ?? [];
        $drivers31 = json_decode(file_get_contents($apiUrl31), true)['data'] ?? [];
        $allDrivers1 = array_merge($drivers11, $drivers21, $drivers31);
    }
    ?>

    <div class="modal fade" id="repeatVehicleModal" tabindex="-1" aria-labelledby="repeatVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="repeatVehicleModalLabel">Select Vehicle Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="repeatVehicleForm">
                        <!-- Bus Number Dropdown -->
                        <div class="mb-3">
                            <label for="busNumber" class="form-label">Bus Number:</label>
                            <select id="busNumber" class="form-select" onchange="updateHiddenFields()">
                                <option value="">Select</option>
                                <option value="other">Other Corporation</option> <!-- Added "Other Corporation" option -->
                                <?php
                                $busQuery = "SELECT br.bus_number, br.make, br.emission_norms 
                 FROM bus_registration br
                 WHERE br.division_name = '$division_id' 
                 AND br.depot_name = '$depot_id'
                 UNION 
                 SELECT vd.bus_number, COALESCE(br.make, '') AS make, COALESCE(br.emission_norms, '') AS emission_norms
                 FROM vehicle_deputation vd
                 LEFT JOIN bus_registration br ON vd.bus_number = br.bus_number
                 WHERE vd.t_division_id = '$division_id' 
                 AND vd.t_depot_id = '$depot_id' 
                 AND vd.tr_date = '$report_date' 
                 AND vd.status NOT IN (1) 
                 AND vd.deleted = 0";

                                $busResult = mysqli_query($db, $busQuery);
                                while ($bus = mysqli_fetch_assoc($busResult)) { ?>
                                    <option value="<?= htmlspecialchars($bus['bus_number']) ?>"
                                        data-make="<?= htmlspecialchars($bus['make']) ?>"
                                        data-emission_norms="<?= htmlspecialchars($bus['emission_norms']) ?>">
                                        <?= $bus['bus_number'] ?>
                                    </option>
                                <?php } ?>
                            </select>

                            <div id="otherBusInput" style="display: none; margin-top: 10px;">
                                <label for="otherBusNumber" class="form-label">Enter Bus Number:</label>
                                <input type="text" id="otherBusNumber" class="form-control" oninput="validateBusNumber()" placeholder="KA32F0001" style="text-transform: uppercase;">
                                <small id="error-msg" style="color: red; display: none;">Invalid format! Use KA32F0001.</small>
                            </div>
                        </div>
                        <input type="hidden" id="make" name="make">
                        <input type="hidden" id="emission_norms" name="emission_norms">
                        <!-- Route Number Dropdown -->
                        <div class="mb-3">
                            <label for="routeNumber" class="form-label">Route Number:</label>
                            <select id="routeNumber" class="form-select">
                                <option value="">Select</option>
                                <?php
                                $routeQuery = "SELECT sch_key_no FROM schedule_master WHERE division_id = '$division_id' AND depot_id = '$depot_id'";
                                $routeResult = mysqli_query($db, $routeQuery);
                                while ($route = mysqli_fetch_assoc($routeResult)) { ?>
                                    <option value="<?= $route['sch_key_no'] ?>"><?= $route['sch_key_no'] ?></option>
                                <?php } ?>
                                <option value="CC">CC</option>
                                <option value="BD">BD</option>
                                <option value="Extra Operation">Extra Operation</option>
                                <option value="Jatra Operation">Jatra Operation</option>
                                <option value="DWS">DWS</option>
                                <option value="RWY">RWY</option>
                                <option value="Road Test">Road Test</option>
                                <option value="Relief">Relief</option>

                            </select>
                        </div>

                        <!-- Driver 1 Dropdown -->
                        <div class="mb-3">
                            <label for="driver1" class="form-label">Driver 1:</label>
                            <select id="driver1" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($allDrivers as $driver) {
                                    if (!empty($driver['EMP_PF_NUMBER'])) { ?>
                                        <option value="<?= htmlspecialchars($driver['EMP_PF_NUMBER']) ?>">
                                            <?= htmlspecialchars($driver['token_number'] . ' (' . $driver['EMP_NAME'] . ')') ?>
                                        </option>
                                <?php }
                                } ?>
                            </select>
                        </div>

                        <!-- Driver 2 Dropdown -->
                        <div class="mb-3">
                            <label for="driver2" class="form-label">Driver 2:</label>
                            <select id="driver2" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($allDrivers as $driver) {
                                    if (!empty($driver['EMP_PF_NUMBER'])) { ?>
                                        <option value="<?= htmlspecialchars($driver['EMP_PF_NUMBER']) ?>">
                                            <?= htmlspecialchars($driver['token_number'] . ' (' . $driver['EMP_NAME'] . ')') ?>
                                        </option>
                                <?php }
                                } ?>
                            </select>
                        </div>
                        <div class="modal-footer d-flex justify-content-between">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="submitaddvehicle()">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Select2 Initialization -->
    <script>
        document.getElementById("otherBusNumber").addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault(); // Prevent form submission
                validateBusNumber(); // Validate the input
            }
        });

        function validateBusNumber() {
            var inputField = document.getElementById("otherBusNumber");
            var errorMsg = document.getElementById("error-msg");
            var busSelect = document.getElementById("busNumber");
            var otherBusInputDiv = document.getElementById("otherBusInput");

            var busPattern = /^[A-Z a-z]{2}\d{2}[A-Z a-z]\d{4}$/; // Format: KA32F0001

            // Convert input to uppercase
            var busNumber = inputField.value.toUpperCase();

            if (busPattern.test(busNumber)) {
                errorMsg.style.display = "none";

                // Check if the option already exists to avoid duplicates
                var optionExists = Array.from(busSelect.options).some(option => option.value === busNumber);
                if (!optionExists) {
                    var newOption = new Option(busNumber, busNumber, true, true);
                    busSelect.appendChild(newOption);
                }

                // Select the entered bus number
                busSelect.value = busNumber;
                inputField.value = ""; // Clear input field

                // Hide input field after successful entry
                otherBusInputDiv.style.display = "none";
            } else {
                errorMsg.style.display = "block";
            }
        }

        function handleBusSelection() {
            var busSelect = document.getElementById("busNumber");
            var routeNumber = document.getElementById("routeNumber");
            var driver1 = document.getElementById("driver1");
            var driver2 = document.getElementById("driver2");

            if (busSelect.value === "other") {
                addOtherOptionBelowSelect(routeNumber);
                addOtherOptionBelowSelect(driver1);
                addOtherOptionBelowSelect(driver2);
            } else {
                removeOtherOption(routeNumber);
                removeOtherOption(driver1);
                removeOtherOption(driver2);
            }
        }

        function addOtherOptionBelowSelect(selectElement) {
            let options = selectElement.options;

            // Check if "Other" already exists
            if (![...options].some(opt => opt.value === "other")) {
                let newOption = new Option("Other", "other");
                selectElement.add(newOption, options[1]); // Insert after "Select"
            }
        }

        function removeOtherOption(selectElement) {
            let options = Array.from(selectElement.options);
            options.forEach(opt => {
                if (opt.value === "other") {
                    selectElement.removeChild(opt);
                }
            });
        }

        $(document).ready(function() {
            $('#repeatVehicleModal select').select2({
                width: '100%',
                dropdownParent: $('#repeatVehicleModal')
            });
        });

        function updateHiddenFields() {
            let busSelect = document.getElementById("busNumber");
            let selectedOption = busSelect.options[busSelect.selectedIndex];
            let otherBusInput = document.getElementById("otherBusInput");
            let otherBusNumber = document.getElementById("otherBusNumber");

            // If "Other Corporation" is selected, show input field
            if (busSelect.value === "other") {
                otherBusInput.style.display = "block";
                otherBusNumber.value = ""; // Clear input field
                document.getElementById("make").value = "";
                document.getElementById("emission_norms").value = "";
            } else {
                otherBusInput.style.display = "none";
                document.getElementById("make").value = selectedOption.getAttribute("data-make") || "";
                document.getElementById("emission_norms").value = selectedOption.getAttribute("data-emission_norms") || "";
            }
            handleBusSelection();
        }


        function deleteRow(kmplId) {
            Swal.fire({
                title: "Are you sure?",
                text: "This action cannot be undone and page will refresh!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "../includes/backend_data.php", // Ensure correct file path
                        type: "POST",
                        data: {
                            action: "delete_kmpl", // Passing action
                            kmpl_delete_id: kmplId
                        },
                        dataType: "json",
                        success: function(response) {

                            if (response.status === "success") {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: response.message,
                                    icon: "success",
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload(); // Reload the page after deletion
                                });
                            } else {
                                Swal.fire("Error!", response.message, "error");
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire("Error!", "Something went wrong: " + error, "error");
                        }
                    });
                }
            });
        }
    </script>

    <?php
    $feedbackOptions = '<option value="">Select</option>';
    $feedbackQuery = "SELECT id, name, thumbs, percentage FROM feedback WHERE id NOT IN('0')";
    $feedbackResult = $db->query($feedbackQuery);
    if ($feedbackResult && $feedbackResult->num_rows > 0) {
        while ($feedbackRow = $feedbackResult->fetch_assoc()) {
            $feedbackOptions .= '<option value="' . htmlspecialchars($feedbackRow['id']) . '">' .
                htmlspecialchars($feedbackRow['thumbs']) .
                '</option>';
        }
    } else {
        $feedbackOptions = '<option value="">No Feedback Available</option>';
    }
    ?>
    <div id="feedbackOptions" style="display: none;"><?= $feedbackOptions ?></div>

    <script>
        function addRepeatVehicle() {

            // Get the report date
            const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>";

            if (reportDate.trim() === "") {
                // Show SweetAlert if no date is selected
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Date',
                    text: 'Please select a date before adding a vehicle!',
                });
            } else {
                // Show the modal (if using Bootstrap)
                let modal = new bootstrap.Modal(document.getElementById("repeatVehicleModal"));
                modal.show();
            }
        }

        function submitaddvehicle() {
            // Get selected values from modal
            let busNumber = document.getElementById("busNumber").value;
            let routeNumber = document.getElementById("routeNumber").value;
            let driver1 = document.getElementById("driver1").value;
            let driver2 = document.getElementById("driver2").value;
            let make = document.getElementById("make").value;
            let emission_norms = document.getElementById("emission_norms").value;

            // Check if bus number is valid
            let busPattern = /^[A-Z a-z]{2}\d{2}[A-Z a-z]\d{4}$/;
            if (!busPattern.test(busNumber)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Bus Number',
                    text: 'Bus number format is incorrect! Please enter in the format KA32F0001.',
                });
                return; // Stop function execution
            }
            if (!busNumber || !routeNumber || !driver1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Fields',
                    text: 'Please fill all required fields before submitting!',
                });
                return;
            }

            let table = document.getElementById("kmpl_table"); // Ensure your table has this ID
            let rowCount = table.rows.length;
            let division_id = <?php echo $division_id ?>;
            let depot_id = <?php echo $depot_id ?>;
            // Find the second-last row
            let insertIndex = rowCount > 1 ? rowCount - 1 : rowCount;
            // Get feedback options from the hidden div
            let feedbackOptions = document.getElementById("feedbackOptions").innerHTML.trim();

            let thumpStatusCell = (make === "Leyland" && emission_norms === "BS-6") ?
                `<select style="width:100%;" name="thump_status[]">${feedbackOptions}</select>` :
                `N/A`;

            // Determine Serial Number (Take last row SN and increment)
            let lastSnCell = table.rows[rowCount - 2]?.cells[0];
            let newSerialNumber = lastSnCell ? parseInt(lastSnCell.innerText) + 1 : 1;
            checkBusNumber(busNumber, reportDate, function(c_change) {
                // Create a new row
                let newRow = table.insertRow(insertIndex);

                // Insert cells and populate with selected values
                newRow.innerHTML = `
        <td>${newSerialNumber}</td>
        <td>${busNumber}</td>
        <td>
        <select class="route-select" name="routeNumber[]" style="width:100%;"><option value="${routeNumber}">${routeNumber}</option></select>
    </td>
    <td>
        <select class="driver-select" name="driver1[]" style="width:100%;"><option value="${driver1}">${driver1}</option></select>
    </td>
    <td>
        <select class="driver-selects" name="driver2[]" style="width:100%;"><option value="${driver2}">${driver2}</option></select>
    </td>
        <td><input style="width:100%;" type="text" name="logsheet_no[]"></td>
        <td><input style="width:100%;" type="number" name="km_operated[]" class="km_operated" oninput="calculateKMPL(this)"></td>
        <td><input style="width:90px;" type="number" name="hsd[]" class="hsd" oninput="calculateKMPL(this)"></td>
        <td><input style="width:90px;" type="number" name="kmpl[]" class="kmpl" readonly></td>
        <td>${thumpStatusCell}</td>
        <td><input style="width:100%;" type="text" name="remarks[]"></td>
        <td class="hidden">${make}</td>
        <td class="hidden">${emission_norms}</td>
        <td class="hidden">${division_id}</td>
        <td class="hidden">${depot_id}</td>
        <td class="hidden"></td>
        <td class="hidden"></td>
            <td class="c_change hidden">${c_change}</td>
        <td>
        <button type="button" class="delete-btn btn btn-danger">
                <i class="fa fa-trash"></i>
            </button>
    <button type="button" class="update-btn btn btn-primary">
        Update
    </button>
</td>

    `;
                newRow.querySelector(".delete-btn").addEventListener("click", function() {
                    table.deleteRow(newRow.rowIndex);
                });
                // 🔥 Fix: Ensure the button retains Bootstrap styles
                let lastButton = newRow.querySelector('.update-btn');
                if (lastButton) {
                    lastButton.classList.add('btn', 'btn-primary');
                }

                // Close modal after adding row
                let modal = bootstrap.Modal.getInstance(document.getElementById("repeatVehicleModal"));
                modal.hide();


                // Update total values
                updateTotal();
                resetModalFields();
            });
        }
        const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>";

        function checkBusNumber(busNumber, reportDate, callback) {
            $.ajax({
                url: "../includes/backend_data.php", // Replace with actual PHP script path
                type: "POST",
                data: {
                    action: "othercorporationfindvehicle",
                    bus_number: busNumber,
                    report_date: reportDate // Send selected report date
                },
                dataType: "json",
                success: function(response) {
                    callback(response.exists ? 1 : 2); // If bus exists, set c_change = 1, otherwise 2
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    console.error("Response Text:", xhr.responseText); // Log full response
                    callback(2); // Default to 2 if an error occurs
                }
            });
        }

        function resetModalFields() {
            $("#busNumber, #routeNumber, #driver1, #driver2").val("").trigger("change");
            $("#make, #emission_norms").val("");
        }


        // Ensure button triggers the function correctly
        document.addEventListener("DOMContentLoaded", function() {
            let addButton = document.getElementById("addRepeatVehicle");

            if (addButton) {
                addButton.addEventListener("click", function(event) {
                    event.preventDefault();
                    addRepeatVehicle();
                });
            }
        });
    </script>
    <script>
        // Object to keep track of selected values across rows
        let selectedValues = {
            route: [],
            driver1: [],
            driver2: []
        };

        function updateOptions() {
            // Get all selects for route, driver1, and driver2
            const routeSelects = document.querySelectorAll('.route-select');
            const driver1Selects = document.querySelectorAll('.driver-select');
            const driver2Selects = document.querySelectorAll('.driver-selects');

            // Reset selected values array on each update
            const currentRouteValues = [...routeSelects].map(select => select.value);
            const currentDriver1Values = [...driver1Selects].map(select => select.value);
            const currentDriver2Values = [...driver2Selects].map(select => select.value);

            // Update available options based on selections in other rows
            updateSelectOptions(routeSelects, currentRouteValues);
            updateDriverSelectOptions(driver1Selects, currentDriver1Values, currentDriver2Values);
            updateDriverSelectOptions(driver2Selects, currentDriver2Values, currentDriver1Values);
        }

        function handleScheduleChange(selectElement) {
            let selectedValue = selectElement.value;

            if (selectedValue === "Road Test" || selectedValue === "Relief") {
                openModal(selectElement);
            } else {
                updateSelectOptions(document.querySelectorAll('.route-select'), getSelectedValues(), false); // Update table selects
            }
        }

        function openModal(selectElement) {
            let row = selectElement.closest("tr"); // Get the selected row
            let modal = new bootstrap.Modal(document.getElementById("scheduleModal")); // Bootstrap modal instance

            // Store row index inside the modal for reference
            document.getElementById("scheduleModal").dataset.rowIndex = row.rowIndex;

            // Populate modal dropdowns with options from the table
            populateModalOptions(row);

            // Open the modal
            modal.show();
        }

        function populateModalOptions(row) {
            let modalSchedule = document.getElementById("modalSchedule");
            let modalDriver1 = document.getElementById("modalDriver1");
            let modalDriver2 = document.getElementById("modalDriver2");

            // Copy available options from the row without disabling anything
            modalSchedule.innerHTML = row.querySelector(".route-select").innerHTML;
            modalDriver1.innerHTML = row.querySelector(".driver-select").innerHTML;
            modalDriver2.innerHTML = row.querySelector(".driver-selects").innerHTML;

            // Ensure all options inside the modal remain enabled
            enableAllOptions(modalSchedule);
            enableAllOptions(modalDriver1);
            enableAllOptions(modalDriver2);
        }

        function enableAllOptions(selectElement) {
            let options = selectElement.options;
            for (let i = 0; i < options.length; i++) {
                options[i].disabled = false;
            }
        }

        // Function to get selected values from the table
        function getSelectedValues() {
            let selectedValues = [];
            document.querySelectorAll('.route-select').forEach(select => {
                if (select.value) selectedValues.push(select.value);
            });
            return selectedValues;
        }



        // Modal Form Submission - Updates Table
        document.getElementById('modalForm').addEventListener('submit', function(e) {
            e.preventDefault();

            let modalSchedule = document.getElementById('modalSchedule').value;
            let modalDriver1 = document.getElementById('modalDriver1').value;
            let modalDriver2 = document.getElementById('modalDriver2').value;

            if (!modalSchedule || !modalDriver1) {
                alert("Schedule No and Driver 1 are required!");
                return;
            }

            let rowIndex = document.getElementById("scheduleModal").dataset.rowIndex;
            let tableRow = document.querySelectorAll('table tbody tr')[rowIndex - 1]; // Get row from table

            // Update table select fields
            tableRow.querySelector('.route-select').value = modalSchedule;
            tableRow.querySelector('.driver-select').value = modalDriver1;
            tableRow.querySelector('.driver-selects').value = modalDriver2;

            // Set v_change to 1
            tableRow.querySelector('td:last-child').innerText = "1";

            // Refresh table selects to disable already chosen values
            updateSelectOptions(document.querySelectorAll('.route-select'), getSelectedValues(), false);

            // Close modal
            let modalInstance = bootstrap.Modal.getInstance(document.getElementById('scheduleModal'));
            modalInstance.hide();
        });


        function submitModalForm() {
            let modal = document.getElementById("scheduleModal");
            let rowIndex = modal.dataset.rowIndex;
            let table = document.querySelector("table");
            let row = table.rows[rowIndex];

            let selectedSchedule = document.getElementById("modalSchedule").value;
            let selectedDriver1 = document.getElementById("modalDriver1").value;
            let selectedDriver2 = document.getElementById("modalDriver2").value;

            // Validation - Schedule and Driver 1 are required
            if (!selectedSchedule || !selectedDriver1) {
                alert("Schedule No and Driver 1 are required!");
                return;
            }

            // Assign values to the table row
            let routeSelect = row.querySelector(".route-select");
            let driver1Select = row.querySelector(".driver-select");
            let driver2Select = row.querySelector(".driver-selects");
            let vChangeCell = row.querySelector(".v-change-cell"); // Target the `v_change` column

            routeSelect.value = selectedSchedule;
            driver1Select.value = selectedDriver1;
            driver2Select.value = selectedDriver2;

            // ✅ Force UI update by triggering a change event
            routeSelect.dispatchEvent(new Event('change'));
            driver1Select.dispatchEvent(new Event('change'));
            driver2Select.dispatchEvent(new Event('change'));

            // ✅ Set `v_change` to 1
            vChangeCell.innerText = "1";

            // ✅ Close the modal
            bootstrap.Modal.getInstance(modal).hide();
        }



        function updateSelectOptions(selects, selectedValues, isModal = false) {
            selects.forEach(select => {
                const options = [...select.options];
                options.forEach(option => {
                    if (!isModal) {
                        // Disable options that are already selected in other dropdowns (except for modal)
                        if (option.value !== "" && !["BD", "CC", "Extra Operation", "Jatra Operation", "DWS", "RWY", "Road Test", "Relief", ].includes(option.value) && selectedValues.includes(option.value)) {
                            option.disabled = true; // Disable if already selected and not "BD" or "CC"
                        } else {
                            option.disabled = false; // Enable if not selected or is "BD" or "CC"
                        }
                    } else {
                        // In modal, keep everything enabled
                        option.disabled = false;
                    }
                });
            });
        }

        function updateDriverSelectOptions(driverSelects, selectedDriverValues, otherDriverValues, isModal = false) {
            driverSelects.forEach(select => {
                const options = [...select.options];
                options.forEach(option => {
                    if (!isModal) {
                        // Disable already selected driver options in the main table
                        if (option.value !== "" && (selectedDriverValues.includes(option.value) || otherDriverValues.includes(option.value))) {
                            option.disabled = true;
                        } else {
                            option.disabled = false;
                        }
                    } else {
                        // In modal, keep everything enabled
                        option.disabled = false;
                    }
                });
            });
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            updateOptions();
            updateTotal(); // Ensure total is updated on page load
        });
        $(document).ready(function() {
            $("#submitBtn").click(function(event) {
                event.preventDefault(); // Prevents page reload
                let button = $(this);
                button.prop("disabled", true).text("Submitting..."); // Disable and change text
                validateAndSubmit(button); // Pass the button reference
            });
        });

        function validateAndSubmit(button) {
            const rows = document.querySelectorAll('#reportTable tr'); // Get all rows
            let validRows = []; // To store valid row data
            let hasData = false; // Flag to check if any row has data
            const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>";

            // Loop through each row except the last one (total row)
            for (let i = 1; i < rows.length - 1; i++) {
                const row = rows[i];
                const inputs = row.querySelectorAll('input, select');

                // Extract values from the row
                const routeNo = row.querySelector('.route-select').value;
                const driverToken1 = row.querySelector('.driver-select').value;
                const driverToken2 = row.querySelector('.driver-selects')?.value;
                const logsheetNo = row.querySelector('input[name="logsheet_no[]"]')?.value;
                const kmOperated = row.querySelector('input[name="km_operated[]"]').value;
                const hsd = row.querySelector('input[name="hsd[]"]').value;
                const remarks = row.querySelector('input[name="remarks[]"]')?.value;
                const thumpStatus = row.querySelector('select[name="thump_status[]"]')?.value;
                const division_id = row.querySelector('td:nth-child(14)').innerText;
                const depot_id = row.querySelector('td:nth-child(15)').innerText;
                const id = row.querySelector('td:nth-child(16)').innerText;
                const vc = row.querySelector('td:nth-child(17)').innerText;
                const cc = row.querySelector('td:nth-child(18)').innerText;

                // Check if any required field is filled
                if (routeNo || driverToken1 || logsheetNo || kmOperated || hsd || remarks) {
                    hasData = true;

                    let missingFields = [];

                    if (!routeNo) missingFields.push("Route No");
                    if (!driverToken1) missingFields.push("Driver Token 1");
                    if (!logsheetNo) missingFields.push("Logsheet No");
                    if (!kmOperated) missingFields.push("KM Operated");
                    if (!hsd) missingFields.push("HSD");
                    if (!remarks) missingFields.push("Logsheet Defect");
                    // If any required field is missing, show error
                    if (missingFields.length > 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Incomplete Row',
                            html: `Row ${i} is incomplete.<br> Missing Fields: <b>${missingFields.join(", ")}</b>`,
                        });
                        button.prop("disabled", false).text("Submit"); // Re-enable button
                        return;
                    }

                    // Validate Thump Status for Leyland BS-6 buses
                    const make = row.querySelector('td:nth-child(12)').innerText;
                    const emissionNorms = row.querySelector('td:nth-child(13)').innerText;
                    if (make === 'Leyland' && emissionNorms === 'BS-6' && !thumpStatus) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Thump Status Required',
                            text: `Row ${i}: Thump Status is required for Leyland BS-6 buses.`
                        });
                        button.prop("disabled", false).text("Submit"); // Re-enable button
                        return;
                    }


                    // Push valid row data
                    validRows.push({
                        bus_number: row.querySelector('td:nth-child(2)').innerText,
                        route_no: routeNo,
                        driver_token1: driverToken1,
                        driver_token2: driverToken2 || null,
                        logsheet_no: logsheetNo,
                        km_operated: kmOperated,
                        hsd: hsd,
                        kmpl: row.querySelector('input[name="kmpl[]"]').value,
                        thump_status: thumpStatus || 0,
                        remarks: remarks || null,
                        division_id: division_id,
                        depot_id: depot_id,
                        id: id || null,
                        vc: vc || null,
                        cc: cc || null
                    });
                }
            }

            // Handle last row separately (Totals Row)
            const lastRow = rows[rows.length - 1];
            const totalKmOperated = lastRow.querySelector('td:nth-child(7)').innerText;
            const totalHsd = lastRow.querySelector('td:nth-child(8)').innerText;
            const totalKmpl = lastRow.querySelector('td:nth-child(9)').innerText;
            const division_id = lastRow.querySelector('td:nth-child(14)').innerText;
            const depot_id = lastRow.querySelector('td:nth-child(15)').innerText;

            validRows.push({
                total_km_operated: totalKmOperated,
                total_hsd: totalHsd,
                total_kmpl: totalKmpl,
                division_id: division_id,
                depot_id: depot_id
            });


            if (!hasData) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Data',
                    text: 'No valid data entered in the table.'
                });
                button.prop("disabled", false).text("Submit"); // Re-enable button
                return;
            }
            // Proceed with form submission (AJAX or other method)

            // If all rows are valid, submit via AJAX
            submitData(validRows, button);
        }

        function submitData(data, button) {
            const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>"; // Ensure it's set

            if (!reportDate) {
                alert("Report date is missing. Please select a valid date.");
                button.prop("disabled", false).text("Submit"); // Re-enable button
                return;
            }
            // Create an AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../database/depot_insert_vehicle_kmpl.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            // Add action name to the data
            const requestData = {
                action: 'insertvehiclekmpldata',
                date: reportDate,
                data: data
            };
            xhr.onload = function() {
                try {
                    // Try to parse the response as JSON
                    const response = JSON.parse(xhr.responseText);

                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'KMPL Data Added successfully!',
                        }).then(() => {
                            window.location.href = 'depot_kmpl.php';
                        });
                    } else {
                        // Show error in alert
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error: ' + response.message,
                        });
                        // Log error to console
                        button.prop("disabled", false).text("Submit"); // Re-enable button
                        console.error('Error response:', response);
                    }
                } catch (e) {
                    // Handle invalid JSON response
                    const errorMessage = 'Network Error Occures Please Try again Once';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage,
                    });
                    console.error('Invalid JSON response:', xhr.responseText);
                    button.prop("disabled", false).text("Submit"); // Re-enable button
                }
            };

            xhr.onerror = function() {
                // Show connection error in alert
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: 'Request failed. Please check your connection.',
                });
                // Log error to console
                console.error('Request failed. Check your connection.');
                button.prop("disabled", false).text("Submit"); // Re-enable button
            };

            // Send the data as JSON
            xhr.send(JSON.stringify(requestData));
        }
        $(document).ready(function() {
            $("#kmpl_table").on("click", ".update-btn", function() {
                let button = $(this);
                let row = $(this).closest("tr"); // Get clicked row

                button.prop("disabled", true).text("Updating...");

                function getCellValue(cellSelector, isSelect = false) {
                    let element = row.find(cellSelector);
                    if (isSelect) {
                        return element.length ? element.val()?.trim() || "" : "";
                    } else {
                        return element.find("input").length ?
                            element.find("input").val()?.trim() || "" :
                            element.text().trim();
                    }
                }

                let id = getCellValue("td:nth-child(16)");
                let vc = getCellValue("td:nth-child(17)");
                let cc = getCellValue("td:nth-child(18)");
                let busNumber = getCellValue("td:nth-child(2)");
                let routeNumber = row.find("td:nth-child(3) select option:selected").val()?.trim() || "";
                let driverToken1 = row.find("td:nth-child(4) select option:selected").val()?.trim() || "";
                let driverToken2 = row.find("td:nth-child(5) select option:selected").val()?.trim() || "";
                let logsheetNo = row.find("td:nth-child(6) input").val()?.trim() || "";
                let kmOperated = row.find("td:nth-child(7) input").val()?.trim() || "";
                let hsd = row.find("td:nth-child(8) input").val()?.trim() || "";
                let kmpl = row.find("td:nth-child(9) input").val()?.trim() || "";
                let thumpStatus = row.find("td:nth-child(10) select option:selected").val()?.trim() || "";
                let logsheetDefects = getCellValue("td:nth-child(11)");
                let division_id = getCellValue("td:nth-child(14)");
                let depot_id = getCellValue("td:nth-child(15)");
                const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>";

                if (!reportDate) {
                    alert("Report date is missing. Please select a valid date.");
                    button.prop("disabled", false).text("Update"); // Re-enable button
                    return;
                }
                // Validate Thump Status for Leyland BS-6 buses
                let make = getCellValue("td:nth-child(12)");
                let emissionNorms = getCellValue("td:nth-child(13)");


                let missingFields = [];
                if (!busNumber) missingFields.push("Bus Number");
                if (!routeNumber) missingFields.push("Route Number");
                if (!driverToken1) missingFields.push("Driver Token 1");
                if (!logsheetNo) missingFields.push("Logsheet No");
                if (!kmOperated) missingFields.push("KM Operated");
                if (!hsd) missingFields.push("HSD");
                if (!kmpl) missingFields.push("KMPL");
                if (!logsheetDefects) missingFields.push("Logsheet Defect ");


                if (missingFields.length > 0) {
                    Swal.fire({
                        icon: "warning",
                        title: "Validation Error",
                        html: `<b>The following fields are missing:</b><br><br>
                       <ul style="text-align:left;">
                           ${missingFields.map((field) => `<li>${field}</li>`).join("")}
                       </ul>`,
                    });
                    button.prop("disabled", false).text("Update"); // Re-enable button
                    return;
                }

                if (make === 'Leyland' && emissionNorms === 'BS-6' && !thumpStatus) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Thump Status Required',
                        text: `Thump Status is required for Leyland BS-6 buses.`
                    });
                    button.prop("disabled", false).text("Update"); // Re-enable button
                    return;
                }

                // ✅ AJAX Request
                $.ajax({
                    url: "../database/depot_update_single_vehicle_kmpl.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "insertupdatesinglevehiclekmpl",
                        id: id,
                        bus_number: busNumber,
                        route_number: routeNumber,
                        driver_token_1: driverToken1,
                        driver_token_2: driverToken2,
                        logsheet_no: logsheetNo,
                        km_operated: kmOperated,
                        hsd: hsd,
                        kmpl: kmpl,
                        thump_status: thumpStatus,
                        logsheet_defects: logsheetDefects,
                        reportDate: reportDate,
                        division_id: division_id,
                        depot_id: depot_id,
                        vc: vc,
                        cc: cc
                    },
                    success: function(response) {
                        if (response.status === "success") {
                            row.find("td:nth-child(16)").text(response.id);
                            row.find("td:nth-child(17)").text(response.vc);
                            row.find("td:nth-child(18)").text(response.cc);

                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                        button.prop("disabled", false).text("Update"); // Re-enable button
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'AJAX Error',
                            text: "Check console for details"
                        });
                        button.prop("disabled", false).text("Update"); // Re-enable button
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