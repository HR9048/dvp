<?php
include '../includes/connection.php';
include '../includes/depot_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Bunk' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    ?>
    <style>
        #dataEntryModal {
            display: none;
        }
        .nav-link.custom-size {
            font-size: 1.25rem;
            /* Increase font size */
            padding: 0.75rem 1.25rem;
            /* Increase padding */
        }
        .hide {
            display: none;
        }
    </style>
    <h2 class="text-center">BUNK MODULE</h2>
    <nav>
        <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
            <button class="nav-link active custom-size" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home"
                type="button" role="tab" aria-controls="nav-home" aria-selected="true"><b>DEPOT KMPL</b></button>
            <button class="nav-link custom-size" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                type="button" role="tab" aria-controls="nav-profile" aria-selected="false"><b>Route wise KMPL</b></button>
        </div>
    </nav>
    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab"><br>
            <form class="form-inline mb-3">
                <div class="form-group mr-2">
                    <?php
                    $currentDate = new DateTime();
                    $currentYear = $currentDate->format("Y");
                    $currentMonth = $currentDate->format("m");
                    $startYear = 2024;
                    $startMonth = 4;

                    // Generate year range
                    $year_range = range($startYear, $currentYear);
                    ?>

                    <label for="year" class="mr-2">Select Year:</label>
                    <select class="form-control" name="year" id="year" onchange="this.form.submit()">
                        <option value=''>Select Year</option>
                        <?php
                        foreach ($year_range as $year_val) {
                            $selected_year = (isset($_GET['year']) && $year_val == $_GET['year']) ? 'selected' : '';
                            echo '<option ' . $selected_year . ' value ="' . $year_val . '">' . $year_val . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <?php if (isset($_GET['year'])): // Check if a year is selected ?>
                    <div class="form-group mr-2">
                        <label for="month" class="mr-2">Select Month:</label>
                        <select class="form-control" name="month" id="month" onchange="this.form.submit()">
                            <option value=''>Select Month</option>
                            <?php
                            $month_range = array();
                            $selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
                            $selected_month = isset($_GET['month']) ? $_GET['month'] : date('n');

                            // Calculate start and end month based on selected year
                            $start = ($selected_year == $startYear) ? $startMonth : 1;
                            $end = ($selected_year == $currentYear) ? $currentMonth : 12;

                            for ($i = $start; $i <= $end; $i++) {
                                $month_range[$i] = date("F", mktime(0, 0, 0, $i, 1));
                            }

                            foreach ($month_range as $month_number => $month_name) {
                                $selected = ($selected_month == $month_number) ? 'selected' : '';
                                echo '<option ' . $selected . ' value ="' . $month_number . '">' . $month_name . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>



            <!-- Add button to open modal for entering daily data -->
            <div class="text-center">
                <button class="btn btn-primary" id="addDataButton" data-toggle="modal" data-target="#dataEntryModal">Add
                    KMPL</button>
            </div>
            <div class="container-fluid mt-5">
                <div class="container1">
                    <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br>

                    <!-- Form to select year and month -->

                    <?php
                    // Check if both year and month are selected
                    if (isset($_GET['year']) && isset($_GET['month'])) {
                        $selected_month = intval($_GET['month']);
                        $selected_year = intval($_GET['year']);

                        // Get the start and end date of the selected month
                        $start_date = date('Y-m-01', mktime(0, 0, 0, $selected_month, 1, $selected_year));
                        $end_date = date('Y-m-t', mktime(0, 0, 0, $selected_month, 1, $selected_year));

                        // If the selected month is the current month, adjust the end date to yesterday's date
                        if ($selected_year == date('Y') && $selected_month == date('m')) {
                            $end_date = date('Y-m-d', strtotime('-1 day')); // Adjust to yesterday's date
                        }

                        // Fetch data from the database for the selected month
                        $sql = "SELECT 
                        DATE_FORMAT(date, '%Y-%m-%d') AS date,
                        total_km,
                        hsd,
                        kmpl
                    FROM 
                        kmpl_data
                    WHERE 
                        DATE(date) BETWEEN '$start_date' AND '$end_date'
                        AND division = '{$_SESSION['DIVISION_ID']}' AND depot = '{$_SESSION['DEPOT_ID']}'
                    ORDER BY 
                        date ASC";

                        $result = mysqli_query($db, $sql);
                        ?>

                        <!-- Display the second table -->
                        <div class="row">
                            <div class="col-lg-12">
                                <!-- Table to display data -->
                                <table>
                                    <h2 style="text-align:center;"><?php echo $_SESSION['DEPOT']; ?> Depot KMPL</h2>
                                    <thead>
                                        <tr>
                                            <th rowspan="2">Date</th>
                                            <th colspan="3" style="text-align:center;">DAILY KMPL</th>
                                            <th colspan="3" style="text-align:center;">CUMULATIVE KMPL</th>
                                        </tr>
                                        <tr>
                                            <th>Gross KM</th>
                                            <th>HSD</th>
                                            <th>KMPL</th>
                                            <th>Gross KM</th>
                                            <th>HSD</th>
                                            <th>KMPL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Initialize cumulative sum variables
                                        $cumulative_total_km_sum = 0;
                                        $cumulative_hsd_sum = 0;

                                        // Output data for each day of the selected month
                                        for ($date = $start_date; $date <= $end_date; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                                            echo "<tr>";
                                            echo "<td>" . date('d/m/Y', strtotime($date)) . "</td>";

                                            // Check if data is available for the current date
                                            $found = false;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                if ($row['date'] === $date) {
                                                    // Data is available for the current date
                                                    $found = true;
                                                    echo "<td>" . $row['total_km'] . "</td>";
                                                    echo "<td>" . $row['hsd'] . "</td>";
                                                    echo "<td>" . $row['kmpl'] . "</td>"; // Daily KMPL
                                
                                                    // Update cumulative sums
                                                    $cumulative_total_km_sum += $row['total_km'];
                                                    $cumulative_hsd_sum += $row['hsd'];

                                                    // Stop calculating cumulative values after yesterday's date
                                                    if ($date === date('Y-m-d')) {
                                                        break 2; // Break both the inner and outer loops
                                                    }
                                                }
                                            }

                                            // If data is not found for the current date, display zeros for daily KMPL
                                            if (!$found) {
                                                echo "<td>0</td>";
                                                echo "<td>0</td>";
                                                echo "<td>0</td>";
                                            }

                                            // Output cumulative values
                                            echo "<td>" . $cumulative_total_km_sum . "</td>";
                                            echo "<td>" . $cumulative_hsd_sum . "</td>";
                                            // Check if HSD is not zero before calculating cumulative KMPL
                                            if ($cumulative_hsd_sum != 0) {
                                                echo "<td>" . number_format(($cumulative_total_km_sum / $cumulative_hsd_sum), 2) . "</td>"; // Cumulative KMPL
                                            } else {
                                                echo "<td>0</td>"; // Handle division by zero
                                            }
                                            echo "</tr>";

                                            // Move the result pointer to the beginning for the next iteration
                                            mysqli_data_seek($result, 0);
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php
                    } else {
                        ?>
                        <!-- Display the first table -->
                        <table>
                            <br>
                            <h2 style="text-align:center;"><?php echo $_SESSION['DEPOT']; ?> Depot KMPL</h2>
                            <thead>
                                <tr>
                                    <th rowspan="2">Date</th>
                                    <th colspan="3" style="text-align:center;">DAILY KMPL</th>
                                    <th colspan="3" style="text-align:center;">CUMULATIVE KMPL</th>
                                </tr>
                                <tr>
                                    <th>Gross KM</th>
                                    <th>HSD</th>
                                    <th>KMPL</th>
                                    <th>Gross KM</th>
                                    <th>HSD</th>
                                    <th>KMPL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Set timezone to Kolkata
                                date_default_timezone_set('Asia/Kolkata');

                                // Get the start and end date of the current month
                                $start_date = date('Y-m-01');
                                $end_date = date('Y-m-d', strtotime('-1 day'));
                                // Fetch data from the database for the current month
                                // Replace this with your database retrieval logic
                                if ($db) {
                                    // Query to fetch daily data for the current month
                                    $sql = "SELECT 
                                DATE_FORMAT(date, '%Y-%m-%d') AS date,
                                total_km,
                                hsd,kmpl
                            FROM 
                                kmpl_data
                            WHERE 
                                DATE(date) BETWEEN '{$start_date}' AND '{$end_date}'
                                AND division = '{$_SESSION['DIVISION_ID']}' AND depot = '{$_SESSION['DEPOT_ID']}'
                            ORDER BY 
                                date ASC";

                                    $result = mysqli_query($db, $sql);

                                    // Initialize cumulative sum variables
                                    $cumulative_total_km_sum = 0;
                                    $cumulative_hsd_sum = 0;

                                    // Output data for each day of the month
                                    for ($date = $start_date; $date <= $end_date; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                                        echo "<tr>";
                                        echo "<td>" . date('d/m/Y', strtotime($date)) . "</td>";

                                        // Check if data is available for the current date
                                        $found = false;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            if ($row['date'] === $date) {
                                                // Data is available for the current date
                                                $found = true;
                                                echo "<td>" . $row['total_km'] . "</td>";
                                                echo "<td>" . $row['hsd'] . "</td>";
                                                echo "<td>" . $row['kmpl'] . "</td>";

                                                // Update cumulative sums
                                                $cumulative_total_km_sum += $row['total_km'];
                                                $cumulative_hsd_sum += $row['hsd'];

                                                // Stop calculating cumulative values after yesterday's date
                                                if ($date === date('Y-m-d')) {
                                                    break 2; // Break both the inner and outer loops
                                                }
                                            }
                                        }

                                        // If data is not found for the current date, display zeros for daily KMPL
                                        if (!$found) {
                                            echo "<td>0</td>";
                                            echo "<td>0</td>";
                                            echo "<td>0</td>";
                                        }

                                        // Output cumulative values
                                        echo "<td>" . $cumulative_total_km_sum . "</td>";
                                        echo "<td>" . $cumulative_hsd_sum . "</td>";
                                        // Check if HSD is not zero before calculating cumulative KMPL
                                        if ($cumulative_hsd_sum != 0) {
                                            echo "<td>" . number_format(($cumulative_total_km_sum / $cumulative_hsd_sum), 2) . "</td>"; // Cumulative KMPL
                                        } else {
                                            echo "<td>0</td>"; // Handle division by zero
                                        }
                                        echo "</tr>";

                                        // Move the result pointer to the beginning for the next iteration
                                        mysqli_data_seek($result, 0);
                                    }

                                    // Close the database connection
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                    }
                    ?>

                    <br><br>
                    <div style="display: flex; justify-content: space-between;">
                        <h2 style="text-align:left; padding: 2%; margin: 0;">JA</h2>
                        <h2 style="text-align:center; padding: 2%; margin: 0;">CM/AWS</h2>
                        <h2 style="text-align:right; padding: 2%; margin: 0;">DM</h2>
                    </div>
                </div>



            </div>

            <!-- Print button -->
            <div class="text-center mt-3">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
            </div>

        </div>
    </div>
    <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
        <table>
            <thead>
                <tr>
                    <th class="hide">ID</th>
                    <th>Schedule No</th>
                    <th>Vehicle No</th>
                    <th>Driver Token</th>
                    <th>Conductor Token</th>
                    <th>Arrival Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $sql = "SELECT * FROM sch_veh_out WHERE division_id='$division_id' and depot_id='$depot_id' and schedule_status = 2 order by arr_time ASC";
                $result = $db->query($sql);

                if ($result->num_rows > 0) {
                    // Output data of each row
                    while ($row = $result->fetch_assoc()) {
                        $driver_token = $row["driver_token_no_1"];
                        if (!empty($row["driver_token_no_2"])) {
                            $driver_token .= ", " . $row["driver_token_no_2"];
                        }

                        $conductor_token = !empty($row["conductor_token_no"]) ? $row["conductor_token_no"] : "Single Crew";

                        echo "<tr>
                        <td class='hide'>" . $row["id"] . "</td>
                        <td>" . $row["sch_no"] . "</td>
                        <td>" . $row["vehicle_no"] . "</td>
                        <td>" . $driver_token . "</td>
                        <td>" . $conductor_token . "</td>
                        <td>" . date('H:i', strtotime($row["arr_time"])) . "</td>
                        <td><button class='btn btn-primary' onclick='openModal(this)'>Receive</button></td>
                      </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No results found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    </div>



    <div class="modal fade" id="dataEntryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Enter Daily KMPL</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form fields for entering data -->
                    <form id="entryForm">
                        <!-- Form fields -->
                        <?php
                        // Get the current date from the server
                        $serverDate = date('Y-m-d');

                        // Calculate the date for 31 days ago
                        $minDate = date('Y-m-d', strtotime('-31 days', strtotime($serverDate)));

                        // Calculate the date for yesterday
                        $maxDate = date('Y-m-d', strtotime('-1 day', strtotime($serverDate)));
                        ?>

                        <div class="form-group">
                            <label for="entryDate">Date:</label>
                            <input type="date" id="entryDate" class="form-control" name="entryDate" value="" required>

                            <script>
                                // Get the server dates from PHP
                                var minDate = "<?php echo $minDate; ?>";
                                var maxDate = "<?php echo $maxDate; ?>";

                                // Get the input element
                                var entryDateInput = document.getElementById('entryDate');

                                // Set the minimum and maximum date
                                entryDateInput.min = minDate;
                                entryDateInput.max = maxDate;

                                // Add event listener to handle manual date input
                                entryDateInput.addEventListener('change', function () {
                                    var selectedDate = new Date(this.value);
                                    var minDate = new Date(this.min);
                                    var maxDate = new Date(this.max);

                                    // If selected date is less than min, set to min
                                    if (selectedDate < minDate) {
                                        this.value = this.min;
                                    }
                                    // If selected date is greater than max, set to max
                                    else if (selectedDate > maxDate) {
                                        this.value = this.max;
                                    }
                                    // If selected date is within range, keep it as is
                                });
                            </script>
                            <!-- <input type="date" id="entryDate" class="form-control" name="entryDate" value=""
                            style="color:red" required> -->

                            <!-- <script>
                            var inputDate = document.getElementById('entryDate');
                            var today = new Date();
                            var minDate = new Date();
                            minDate.setDate(today.getDate() - 3); // 3 days back
                            var maxDate = new Date();
                            maxDate.setDate(today.getDate() - 1); // yesterday

                            inputDate.addEventListener('input', function () {
                                var selectedDate = new Date(this.value);
                                if (selectedDate < minDate) {
                                    this.value = minDate.toJSON().slice(0, 10);
                                } else if (selectedDate > maxDate) {
                                    this.value = maxDate.toJSON().slice(0, 10);
                                }
                            });
                        </script> -->
                        </div>
                        <!-- Display total KM and KMPL -->
                        <div class="form-group">
                            <label>Gross KM:</label>
                            <input type="number" class="form-control" id="totalKM" onchange="calculateTotals()" required>
                        </div>
                        <div class="form-group">
                            <label for="hsd">HSD:</label>
                            <input type="number" class="form-control" id="hsd" onchange="calculateTotals()" required>
                        </div>

                        <div class="form-group">
                            <label>KMPL:</label>
                            <input type="number" class="form-control" id="kmpl" readonly>
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveDataButton"
                                onclick="closeDataEntryModal()">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins)
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->

    <script>
        $(document).ready(function () {
            // Close modal when close button or cancel button is clicked
            $('.modal .close, .modal .modal-footer .btn-secondary').click(function () {
                $(this).closest('.modal').modal('hide');
            });
        });
        document.getElementById('addDataButton').addEventListener('click', function () {
            document.getElementById('dataEntryModal').style.display = 'block'; // Remove this line
        });

    </script>


    </div>
<!-- Modal -->
<div class="modal fade" id="dataModal" tabindex="-1" role="dialog" aria-labelledby="dataModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataModalLabel">Vehicle Schedule Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <input type="hidden" class="form-control" id="id" name="id" readonly>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="scheduleNo">Schedule No</label>
                                    <input type="text" class="form-control" id="scheduleNo" name="scheduleno" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="vehicleNo">Vehicle No</label>
                                    <input type="text" class="form-control" id="vehicleNo" name="vehicleNo" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="driverToken">Driver Token</label>
                                    <input type="text" class="form-control" id="driverToken" name="driverToken" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="conductorToken">Conductor Token</label>
                                    <input type="text" class="form-control" id="conductorToken" name="conductorToken"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="arrivalTime">Arrival Time</label>
                                    <input type="text" class="form-control" id="arrivalTime" name="arrivalTime" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="logsheetNo">Logsheet No</label>
                                    <input type="number" class="form-control" id="logsheetNo" name="logsheetNo" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="RkmOperated">KM Operated</label>
                                    <input type="number" class="form-control" id="RkmOperated" name="RkmOperated" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="Rhsd">HSD</label>
                                    <input type="number" class="form-control" id="Rhsd" name="Rhsd" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label for="Rkmpl">KMPL</label>
                                <input type="text" class="form-control" id="Rkmpl" name="Rkmpl" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="driverDefect">Driver Defect</label>
                                    <select class="form-control" id="driverDefect" name="driverDefect" required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                            <div class="col" id="remarkContainer" style="display: none;">
                                <div class="form-group">
                                    <label for="remark">Remark</label>
                                    <textarea class="form-control" id="remark" name="remark"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="button" style="text-align:center;">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(button) {
            // Get the row data
            var row = $(button).closest('tr');
            var id = row.find('td:eq(0)').text();
            var scheduleNo = row.find('td:eq(1)').text();
            var vehicleNo = row.find('td:eq(2)').text();
            var driverToken = row.find('td:eq(3)').text();
            var conductorToken = row.find('td:eq(4)').text();
            var arrivalTime = row.find('td:eq(5)').text();

            // Set the modal input values
            $('#id').val(id);
            $('#scheduleNo').val(scheduleNo);
            $('#vehicleNo').val(vehicleNo);
            $('#driverToken').val(driverToken);
            $('#conductorToken').val(conductorToken);
            $('#arrivalTime').val(arrivalTime);

            // Open the modal
            $('#dataModal').modal('show');

            // Function to populate defect types
            function driverdefecttype() {
                $.ajax({
                    url: '../includes/data_fetch.php',
                    type: 'GET',
                    data: { action: 'driverdefecttype' },
                    success: function (response) {
                        var service = JSON.parse(response);

                        // Clear existing options
                        $('#driverDefect').empty();

                        // Add default "Select" option
                        $('#driverDefect').append('<option value="">Select</option>');

                        // Add fetched options
                        $.each(service, function (index, value) {
                            $('#driverDefect').append('<option value="' + value.id + '">' + value.defect_name + '</option>');
                        });

                        // Trigger change event to set initial state
                        $('#driverDefect').trigger('change');
                    }
                });
            }
            driverdefecttype();
        }

        // Handle the change event for driver defect select field
        $('#driverDefect').on('change', function () {
            var selectedValue = $(this).val();
            if (selectedValue != '1') {
                $('#remarkContainer').show();
                $('#remark').prop('required', true);
            } else {
                $('#remarkContainer').hide();
                $('#remark').prop('required', false);
            }
        });

        // Ensure remark container is hidden on modal open
        $('#dataModal').on('shown.bs.modal', function () {
            $('#remarkContainer').hide();
            $('#remark').prop('required', false);
        });

        // Calculate Rkmpl when KM Operated or Rhsd changes
        $('#RkmOperated, #Rhsd').on('input', function () {
            var RkmOperated = parseFloat($('#RkmOperated').val()) || 0;
            var Rhsd = parseFloat($('#Rhsd').val()) || 0;

            // Calculate Rkmpl and set the value
            if (Rhsd > 0) {
                var Rkmpl = RkmOperated / Rhsd;
                $('#Rkmpl').val(Rkmpl.toFixed(2));
            } else {
                $('#Rkmpl').val('');
            }
        });
    </script>
    <script>
        $(document).ready(function () {
            // Handle form submission
            $('form').on('submit', function (event) {
                event.preventDefault(); // Prevent the default form submission

                // Validate the form fields
                var isValid = true;
                var id = $('#id').val();
                var logsheetNo = $('#logsheetNo').val();
                var RkmOperated = $('#RkmOperated').val();
                var Rhsd = $('#Rhsd').val();
                var driverDefect = $('#driverDefect').val();
                var remark = $('#remark').val();

                if (!id) {
                    isValid = false;
                    alert('Something went wrong. Please refresh the page and try again.');
                }
                if (!logsheetNo) {
                    isValid = false;
                    alert('Logsheet No is required.');
                }
                if (!RkmOperated) {
                    isValid = false;
                    alert('KM Operated is required.');
                }
                if (!Rhsd) {
                    isValid = false;
                    alert('HSD is required.');
                }
                if (!driverDefect) {
                    isValid = false;
                    alert('Driver Defect is required.');
                }
                if (driverDefect !== '1' && !remark) {
                    isValid = false;
                    alert('Remark is required when Driver Defect is not 1.');
                }

                if (isValid) {
                    // Gather form data
                    var formData = $(this).serialize();

                    // Submit the form data using AJAX
                    $.ajax({
                        url: 'depot_driver_defect_insert.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json', // Expecting JSON response
                        success: function (response) {
                            if (response.status === 'success') {
                                // Redirect to depot_kmpl.php on success
                                alert('recored updated successfully');
                                window.location.href = 'depot_kmpl.php';
                            } else {
                                // Show error message if any
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            // Handle errors
                            alert('An error occurred: ' + error);
                        }
                    });
                }
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
<!-- Bootstrap 
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>JS -->
<!-- Add this script section at the end of your HTML body -->
<script>
    document.getElementById('addDataButton').addEventListener('click', function () {
        document.getElementById('dataEntryModal').style.display = 'block'; // Show the modal
    });
    // Function to calculate total KM and KMPL
    function calculateTotals() {
        // Get values from form fields
        let totalKM = parseFloat(document.getElementById('totalKM').value);
        let hsd = parseFloat(document.getElementById('hsd').value);



        // Calculate KMPL
        let kmpl = (totalKM / hsd).toFixed(2); // Round to 2 decimal places

        console.log("KMPL: ", kmpl);

        // Display KMPL in the corresponding input field
        document.getElementById('kmpl').value = kmpl;
    }



    // Function to reload the page
    function reloadPage() {
        location.reload();
    }

    document.getElementById('saveDataButton').addEventListener('click', function () {
        // Validate the form
        if (!validateForm()) {
            return; // Prevent further execution if validation fails
        }

        // Calculate total KM and KMPL before submitting the form
        calculateTotals();

        // Retrieve data from the form
        let date = document.getElementById('entryDate').value;
        let hsd = document.getElementById('hsd').value;
        let totalKM = document.getElementById('totalKM').value;
        let kmpl = document.getElementById('kmpl').value;

        // Create an AJAX request
        let xhr = new XMLHttpRequest();
        xhr.open('POST', 'depot_kmpl_save.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    // Handle the response here
                    if (xhr.responseText === 'date_exists') {
                        // Date already exists in the database
                        alert('Data for the selected date already exists.');
                    } else if (xhr.responseText === 'success') {
                        // Data inserted successfully
                        alert('Data successfully inserted!');
                        location.reload();
                    } else {
                        // Failed to insert data
                        alert('Failed to insert data. Please try again.');
                        location.reload();
                    }
                } else {
                    // Show an alert message for insertion failure
                    alert('Failed to insert data. Please try again.');
                    location.reload();
                }
            }
        };
        xhr.send('date=' + date + '&hsd=' + hsd + '&totalKM=' + totalKM + '&kmpl=' + kmpl);
    });

    function validateForm() {
        var entryDate = document.getElementById('entryDate').value;
        if (!entryDate) {
            alert('Please select a date.');
            return false; // Prevent form submission
        }

        // Get gross km and hsd values
        var grossKm = parseFloat(document.getElementById('totalKM').value);
        var hsd = parseFloat(document.getElementById('hsd').value);

        // Check if gross km or hsd is zero or not set
        if (grossKm === 0 || isNaN(grossKm) || hsd === 0 || isNaN(hsd)) {
            alert('Gross KM and HSD are required and should not be zero.');
            return false; // Prevent form submission
        }

        return true; // Allow form submission
    }



    // Add event listener to entry form for input fields
    document.getElementById('entryForm').addEventListener('input', function () {
        // Calculate total KM and KMPL when any input field changes
        calculateTotals();
    });
    // Function to close the data entry modal
    function closeDataEntryModal() {
        // Find the modal element
        let modal = document.getElementById('dataEntryModal');
        // Close the modal using Bootstrap's modal method
        $(modal).modal('hide');
    }
</script>
<script>
    $(document).ready(function () {
        // Remove month and year parameters from the URL when the page loads
        window.history.replaceState({}, document.title, window.location.pathname);
    });

    // Your existing JavaScript code
</script>

