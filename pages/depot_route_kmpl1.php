<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Bunk' || $_SESSION['JOB_TITLE'] == 'DM')) {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id1 = $_SESSION['KMPL_DIVISION'];
    $depot_id1 = $_SESSION['KMPL_DEPOT'];
    ?>
    <form id="busReportForm" method="POST">
        <label for="reportDate">Select Date:</label>
        <input type="date" name="report_date" id="reportDate" required>
        <button type="submit">Submit</button>
    </form>

    <div id="reportTable" style="margin-top: 20px;">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_date'])) {
            $report_date = $_POST['report_date'];

            // Fetch bus numbers
            $busQuery = "SELECT bus_number FROM bus_registration WHERE division_name = '$division_id' AND depot_name = '$depot_id'";
            $busResult = $db->query($busQuery);

            if ($busResult && $busResult->num_rows > 0) {
                echo '<div class="table-container">';
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
                    <th>Driver Defect</th>
                    <th>Remarks</th>
                  </tr>';

                $sl_no = 1;
                while ($busRow = $busResult->fetch_assoc()) {
                    $bus_number = $busRow['bus_number'];

                    // Fetch route numbers
                    $routeQuery = "SELECT sch_key_no FROM schedule_master WHERE division_id = '$division_id' AND depot_id = '$depot_id'";
                    $routeResult = $db->query($routeQuery);

                    // Fetch driver tokens from APIs
                    $apiUrl1 = "http://192.168.1.32:50/data.php?division=$division_id1&depot=$depot_id1";
                    $apiUrl2 = "http://192.168.1.32/transfer/dvp/database/private_emp_api.php?division=$division_id1&depot=$depot_id1";
                    $apiUrl3 = "http://192.168.1.32/transfer/dvp/database/deputation_crew_api1.php?division=$division_id1&depot=$depot_id1&date=$report_date";

                    $drivers1 = json_decode(file_get_contents($apiUrl1), true)['data'] ?? [];
                    $drivers2 = json_decode(file_get_contents($apiUrl2), true)['data'] ?? [];
                    $drivers3 = json_decode(file_get_contents($apiUrl3), true)['data'] ?? [];
                    $allDrivers = array_merge($drivers1, $drivers2, $drivers3);

                    echo '<tr>';
                    echo '<td>' . $sl_no++ . '</td>';
                    echo '<td>' . htmlspecialchars($bus_number) . '</td>';

                    // Route number select
                    echo '<td>';
                    echo '<select class="route-select">';
                    echo '<option style="width:100%;" value="">Select</option>'; // Default option
                    if ($routeResult && $routeResult->num_rows > 0) {
                        while ($routeRow = $routeResult->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($routeRow['sch_key_no']) . '">' . htmlspecialchars($routeRow['sch_key_no']) . '</option>';
                        }
                    } else {
                        echo '<option value="">No Routes Available</option>';
                    }
                    echo '</select>';
                    echo '</td>';

                    // Driver Token 1 select
                    echo '<td>';
                    echo '<select style="width:100%;" class="driver-select">';
                    echo '<option value="">Select</option>';
                    if (!empty($allDrivers)) {
                        foreach ($allDrivers as $driver) {
                            if (!empty($driver['EMP_PF_NUMBER']) && !empty($driver['token_number']) && !empty($driver['EMP_NAME'])) {
                                echo '<option value="' . htmlspecialchars($driver['EMP_PF_NUMBER']) . '">' .
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
                    echo '<select style="width:100%;" class="driver-select">';
                    echo '<option value="">Select</option>';
                    if (!empty($allDrivers)) {
                        foreach ($allDrivers as $driver) {
                            if (!empty($driver['EMP_PF_NUMBER']) && !empty($driver['token_number']) && !empty($driver['EMP_NAME'])) {
                                echo '<option value="' . htmlspecialchars($driver['EMP_PF_NUMBER']) . '">' .
                                    htmlspecialchars($driver['token_number'] . ' (' . $driver['EMP_NAME'] . ')') .
                                    '</option>';
                            }
                        }
                    } else {
                        echo '<option value="">No Drivers Available</option>';
                    }
                    echo '</select>';
                    echo '</td>';

                    echo '<td><input style="width:100%;" type="text" name="logsheet_no[]" ></td>';
                    echo '<td><input style="width:100%;" type="number" name="km_operated[]" class="km_operated" oninput="calculateKMPL(this)"></td>';
                    echo '<td><input style="width:90px;" type="number" name="hsd[]" class="hsd" oninput="calculateKMPL(this)"></td>';
                    echo '<td><input style="width:100%;" type="number" name="kmpl[]" class="kmpl" readonly></td>';
                    echo '<td>';
                    echo '<select style="width:100%;" name="driver_defect[]">';
                    echo '<option value="">Select</option>';

                    // Query to fetch defect names
                    $defectQuery = "SELECT id, defect_name FROM driver_defect";
                    $defectResult = $db->query($defectQuery);

                    if ($defectResult && $defectResult->num_rows > 0) {
                        while ($defectRow = $defectResult->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($defectRow['id']) . '">' .
                                htmlspecialchars($defectRow['defect_name']) .
                                '</option>';
                        }
                    } else {
                        echo '<option value="">No Defects Available</option>';
                    }

                    echo '</select>';
                    echo '</td>';
                    echo '<td><input style="width:100%;" type="text" name="remarks[]" ></td>';
                    echo '</tr>';
                }
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
    </style>

    <script>
        $(document).ready(function () {
            $('.route-select, .driver-select').select2();
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
        }
    </script>

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>