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

            // Update the total row
            document.getElementById('total_km_operated').textContent = totalKmOperated.toFixed(2);
            document.getElementById('total_hsd').textContent = totalHsd.toFixed(2);
            document.getElementById('total_kmpl').textContent = totalKmpl;
        }
    </script>
    <form id="busReportForm" method="POST" onsubmit="return validateAndSubmit1();">
        <label for="reportDate">Select Date:</label>
        <input type="date" name="report_date" id="reportDate" required>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

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
            fourDaysAgo.setDate(today.getDate() - 4);

            // Convert dates to 'YYYY-MM-DD' for accurate comparison
            let selectedDateString = selectedDate.toISOString().split('T')[0];
            let yesterdayString = yesterday.toISOString().split('T')[0];
            let fourDaysAgoString = fourDaysAgo.toISOString().split('T')[0];

            if (selectedDateString > yesterdayString || selectedDateString < fourDaysAgoString) {
                Swal.fire('Date Outside Allowed Range',
                    `Date must be between ${fourDaysAgo.toLocaleDateString('en-GB')} and ${yesterday.toLocaleDateString('en-GB')}.`,
                    'error'
                );
                return false;
            }

            return true; // Allow form submission
        }
    </script>

    <form method="post" onsubmit="event.preventDefault(); validateAndSubmit();">
        <div id="reportTable" style="margin-top: 20px;">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_date'])) {
                $report_date = $_POST['report_date'];
                $division_id = $_SESSION['DIVISION_ID'];
                $depot_id = $_SESSION['DEPOT_ID'];

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
                              WHERE division_id = '$division_id' AND depot_id = '$depot_id' AND date = '$report_date'";
                $kmplResult = $db->query($kmplQuery);

                // Store existing KMPL data in an associative array using bus_number as key
                $kmplData = [];
                while ($kmplRow = $kmplResult->fetch_assoc()) {
                    $kmplData[$kmplRow['bus_number']] = $kmplRow;
                }
                $formatted_date = date("d-m-Y", strtotime($report_date));
                if ($busResult && $busResult->num_rows > 0) {
                    echo '<div class="table-container"><h1 class="text-center">Division: ' . $_SESSION['DIVISION'] . '  Depot: ' . $_SESSION['DEPOT'] . ' KMPL Entry for Date: ' . htmlspecialchars($formatted_date) . '</h1>';
                    echo '<table border="1" cellpadding="5" cellspacing="0">';
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
                        <th>ID</th>
                        <th>Action</th>
                      </tr>';

                    $sl_no = 1;
                    while ($busRow = $busResult->fetch_assoc()) {
                        $bus_number = $busRow['bus_number'];
                        $make = $busRow['make'];
                        $emission_norms = $busRow['emission_norms'];

                        // Fetch vehicle_kmpl data for this bus (if exists)
                        $existingData = $kmplData[$bus_number] ?? null;


                        // Fetch route numbers
                        $routeQuery = "SELECT sch_key_no FROM schedule_master WHERE division_id = '$division_id' AND depot_id = '$depot_id'";
                        $routeResult = $db->query($routeQuery);

                        // Fetch driver tokens from APIs
                        $apiUrl1 = "http://localhost:8880/dvp/includes/data.php?division=$division_id1&depot=$depot_id1";
                        $apiUrl2 = "http://localhost:8880/dvp/database/private_emp_api.php?division=$division_id1&depot=$depot_id1";
                        $apiUrl3 = "http://localhost:8880/dvp/database/deputation_crew_api1.php?division=$division_id1&depot=$depot_id1&date=$report_date";

                        $drivers1 = json_decode(file_get_contents($apiUrl1), true)['data'] ?? [];
                        $drivers2 = json_decode(file_get_contents($apiUrl2), true)['data'] ?? [];
                        $drivers3 = json_decode(file_get_contents($apiUrl3), true)['data'] ?? [];
                        $allDrivers = array_merge($drivers1, $drivers2, $drivers3);
                        echo '<tr>';
                        echo '<td>' . $sl_no++ . '</td>';
                        echo '<td>' . htmlspecialchars($bus_number) . '</td>';

                        // Route number select
                        echo '<td>';
                        echo '<select class="route-select" onchange="updateOptions()">';
                        echo '<option style="width:100%;" value="">Select</option>'; // Default option

                        if ($routeResult && $routeResult->num_rows > 0) {
                            while ($routeRow = $routeResult->fetch_assoc()) {
                                $selected = ($existingData['route_no'] ?? '') == $routeRow['sch_key_no'] ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($routeRow['sch_key_no']) . '" ' . $selected . '>' . htmlspecialchars($routeRow['sch_key_no']) . '</option>';
                            }
                            echo '<option value="CC">CC</option>';
                            echo '<option value="BD">BD</option>';
                            echo '<option value="Extra Operation">Extra Operation</option>';
                            echo '<option value="Jatra Operation">Jatra Operation</option>';
                            echo '<option value="Road Test">Road Test</option>';
                            echo '<option value="Relief">Relief</option>';
                        } else {
                            echo '<option value="">No Routes Available</option>';
                        }

                        echo '</select>';
                        echo '</td>';


                        // Driver Token 1 select
                        // Driver Token 1 select
                        echo '<td>';
                        echo '<select style="width:100%;" class="driver-select" onchange="updateOptions()">';
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
                        echo '<select style="width:100%;" class="driver-selects" onchange="updateOptions()">';
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
                                        htmlspecialchars($feedbackRow['thumbs'] . ',' . $feedbackRow['name'] . ' (' . $feedbackRow['percentage'] . ')') .
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
                        echo '<td>' . ($existingData['id'] ?? '') . '</td>';
                        echo '<td><button style="width:100%;" type="button" class="btn btn-success">Update</button></td>';
                        echo '</tr>';
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
                    echo '</tr>';
                    echo '</table><br>';
                    echo '<div class="text-center my-3">';
                    echo '<button type="submit" class="btn btn-success">Submit</button>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<p>No buses available for the selected depot and division.</p>';
                }
            }
            ?>
        </div>
    </form>
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

        function updateSelectOptions(selects, selectedValues) {
            selects.forEach(select => {
                const options = [...select.options];
                options.forEach(option => {
                    // Do not disable the "Select" option or options with value "BD" or "CC"
                    if (option.value !== "" && !["BD", "CC", "Extra Operation", "Jatra Operation", "Road Test", "Relief", ].includes(option.value) && selectedValues.includes(option.value)) {
                        option.disabled = true; // Disable if already selected and not "BD" or "CC"
                    } else {
                        option.disabled = false; // Enable if not selected or is "BD" or "CC"
                    }
                });
            });
        }

        function updateDriverSelectOptions(driverSelects, selectedDriverValues, otherDriverValues) {
            driverSelects.forEach(select => {
                const options = [...select.options];
                options.forEach(option => {
                    // Do not disable the "Select" option
                    if (option.value !== "" && (selectedDriverValues.includes(option.value) || otherDriverValues.includes(option.value))) {
                        option.disabled = true;
                    } else {
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

        function validateAndSubmit() {
            const rows = document.querySelectorAll('#reportTable tr'); // Get all rows
            let validRows = []; // To store valid row data
            let hasData = false; // Flag to check if any row has data
            const reportDate = "<?php echo $report_date; ?>";

            // Loop through each row except the last one (total row)
            for (let i = 1; i < rows.length - 1; i++) {
                const row = rows[i];
                const inputs = row.querySelectorAll('input, select');

                // Extract values from the row
                const routeNo = row.querySelector('.route-select').value;
                const driverToken1 = row.querySelector('.driver-select').value;
                const driverToken2 = row.querySelector('.driver-selects')?.value;
                const logsheetNo = row.querySelector('input[name="logsheet_no[]"]').value;
                const kmOperated = row.querySelector('input[name="km_operated[]"]').value;
                const hsd = row.querySelector('input[name="hsd[]"]').value;
                const remarks = row.querySelector('input[name="remarks[]"]').value;
                const thumpStatus = row.querySelector('select[name="thump_status[]"]')?.value;
                const division_id = row.querySelector('td:nth-child(14)').innerText;
                const depot_id = row.querySelector('td:nth-child(15)').innerText;
                // Check if any required field is filled
                if (routeNo || driverToken1 || logsheetNo || kmOperated || hsd) {
                    hasData = true;

                    // Validate required fields
                    if (!routeNo || !driverToken1 || !logsheetNo || !kmOperated || !hsd) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Incomplete Row',
                            text: `Row ${i} is incomplete. Please fill all required fields.`
                        });
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
                        depot_id: depot_id
                    });
                }
            }

            // Handle last row separately (Totals Row)
            const lastRow = rows[rows.length - 1];
            const totalKmOperated = lastRow.querySelector('td:nth-child(7)').innerText;
            const totalHsd = lastRow.querySelector('td:nth-child(8)').innerText;
            const totalKmpl = lastRow.querySelector('td:nth-child(9)').innerText;

            validRows.push({
                total_km_operated: totalKmOperated,
                total_hsd: totalHsd,
                total_kmpl: totalKmpl
            });


            if (!hasData) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Data',
                    text: 'No valid data entered in the table.'
                });
                return;
            }

            // Proceed with form submission (AJAX or other method)

            // If all rows are valid, submit via AJAX
            submitData(validRows);
        }

        function submitData(data) {
            const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>"; // Ensure it's set

            if (!reportDate) {
                alert("Report date is missing. Please select a valid date.");
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
                        console.error('Error:', response.message);
                    }
                } catch (e) {
                    // Handle invalid JSON response
                    const errorMessage = 'Invalid response from the server. Check the console for details.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage,
                    });
                    console.error('Invalid JSON response:', xhr.responseText);
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
            };

            // Send the data as JSON
            xhr.send(JSON.stringify(requestData));
        }
    </script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>