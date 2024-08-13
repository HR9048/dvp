<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
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
        /* Custom styles to ensure content fits within viewport */
        .container-fluid {
            max-width: 100%;
            padding-left: 15px;
            padding-right: 15px;
        }

        .nav-tabs {
            overflow-x: auto;
        }

        .table2 {
            table-layout: auto;
            /* Adjust based on content */
            word-wrap: break-word;
        }

        .table2 th,
        .table2 td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table2-responsive {
            overflow-x: auto;
        }



        /* Responsive Font Sizes */
        body {
            font-size: 1rem;
            /* Default font size */
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-size: calc(0.3rem + 1vw);
            /* Responsive heading sizes */
        }

        .table2 th,
        .table2 td {
            font-size: calc(0.75rem + 0.5vw);
            /* Responsive table cell font size */
        }

        /* Media Queries */
        @media (max-width: 768px) {

            .table2 th,
            .table2 td {
                font-size: calc(0.6rem + 0.5vw);
                /* Smaller font size for tablets */
            }

            .form-control,
            .btn {
                font-size: calc(0.8rem + 0.5vw);
                /* Adjust form controls and buttons */
            }
        }

        @media (max-width: 576px) {

            .table2 th,
            .table2 td {
                font-size: calc(0.5rem + 0.5vw);
                /* Smaller font size for mobile devices */
            }

            .form-control,
            .btn {
                font-size: calc(0.7rem + 0.5vw);
                /* Adjust form controls and buttons */
            }
        }
    </style>

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
    <div class="container-fluid">
        <h2 class="text-center">BUNK MODULE</h2>
        <nav>
            <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
                <button class="nav-link active custom-size" id="nav-home-tab" data-bs-toggle="tab"
                    data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">
                    <h5>DEPOT KMPL</h5>
                </button>
                <button class="nav-link custom-size" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                    type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                    <h5>Route wise KMPL</h5>
                </button>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                <br>
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <form class="form-inline d-flex flex-wrap me-4">
                            <div class="form-group mb-2">
                                <?php
                                $currentDate = new DateTime();
                                $currentYear = $currentDate->format("Y");
                                $currentMonth = $currentDate->format("m");
                                $startYear = 2024;
                                $startMonth = 4;

                                // Generate year range
                                $year_range = range($startYear, $currentYear);
                                ?>

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
                            <?php if (isset($_GET['year'])): ?>
                                <div class="form-group mb-2 ms-2">
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

                        <!-- Button aligned to the right -->
                        <button class="btn btn-primary" id="addDataButton" data-bs-toggle="modal"
                            data-bs-target="#dataEntryModal">Add KMPL</button>
                    </div>
                </div>

                <div class="table-responsive mt-5">
                    <h1 class="text-center">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1>
                    <br>
                    <div class="row">
                        <div class="col-12">
                            <table class="table2">
                                <h2 class="text-center"><?php echo $_SESSION['DEPOT']; ?> Depot KMPL</h2>
                                <thead>
                                    <tr>
                                        <th rowspan="2">Date</th>
                                        <th colspan="3" class="text-center">DAILY KMPL</th>
                                        <th colspan="3" class="text-center">CUMULATIVE KMPL</th>
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
                                    if (isset($_GET['year']) && isset($_GET['month'])) {
                                        $selected_month = intval($_GET['month']);
                                        $selected_year = intval($_GET['year']);

                                        $start_date = date('Y-m-01', mktime(0, 0, 0, $selected_month, 1, $selected_year));
                                        $end_date = date('Y-m-t', mktime(0, 0, 0, $selected_month, 1, $selected_year));

                                        if ($selected_year == date('Y') && $selected_month == date('m')) {
                                            $end_date = date('Y-m-d', strtotime('-1 day'));
                                        }

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
                                    } else {
                                        date_default_timezone_set('Asia/Kolkata');

                                        $start_date = date('Y-m-01');
                                        $end_date = date('Y-m-d', strtotime('-1 day'));

                                        if ($db) {
                                            $sql = "SELECT 
                                                DATE_FORMAT(date, '%Y-%m-%d') AS date,
                                                total_km,
                                                hsd, kmpl
                                            FROM 
                                                kmpl_data
                                            WHERE 
                                                DATE(date) BETWEEN '{$start_date}' AND '{$end_date}'
                                                AND division = '{$_SESSION['DIVISION_ID']}' AND depot = '{$_SESSION['DEPOT_ID']}'
                                            ORDER BY 
                                                date ASC";
                                        }
                                    }
                                    $result = mysqli_query($db, $sql);
                                    $cumulative_total_km_sum = 0;
                                    $cumulative_hsd_sum = 0;

                                    for ($date = $start_date; $date <= $end_date; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                                        echo "<tr>";
                                        echo "<td>" . date('d/m/Y', strtotime($date)) . "</td>";

                                        $found = false;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            if ($row['date'] === $date) {
                                                $found = true;
                                                echo "<td>" . $row['total_km'] . "</td>";
                                                echo "<td>" . $row['hsd'] . "</td>";
                                                echo "<td>" . $row['kmpl'] . "</td>";

                                                $cumulative_total_km_sum += $row['total_km'];
                                                $cumulative_hsd_sum += $row['hsd'];

                                                if ($date === date('Y-m-d')) {
                                                    break 2;
                                                }
                                            }
                                        }

                                        if (!$found) {
                                            echo "<td>0</td>";
                                            echo "<td>0</td>";
                                            echo "<td>0</td>";
                                        }

                                        echo "<td>" . $cumulative_total_km_sum . "</td>";
                                        echo "<td>" . $cumulative_hsd_sum . "</td>";
                                        if ($cumulative_hsd_sum != 0) {
                                            echo "<td>" . number_format(($cumulative_total_km_sum / $cumulative_hsd_sum), 2) . "</td>";
                                        } else {
                                            echo "<td>0</td>";
                                        }
                                        echo "</tr>";

                                        mysqli_data_seek($result, 0);
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-primary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                <div class="table-responsive">
                    <table class="table2">
                        <thead>
                            <tr>
                                <th class="d-none">ID</th>
                                <th>Sch No</th>
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
                                while ($row = $result->fetch_assoc()) {
                                    $driver_token = $row["driver_token_no_1"];
                                    if (!empty($row["driver_token_no_2"])) {
                                        $driver_token .= ", " . $row["driver_token_no_2"];
                                    }

                                    $conductor_token = !empty($row["conductor_token_no"]) ? $row["conductor_token_no"] : "Single Crew";

                                    echo "<tr>
                                    <td class='d-none'>" . $row["id"] . "</td>
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
        </div>

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
                        </div>
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
                    <h5 class="modal-title" id="dataModalLabel">Route Return Form</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="driverDefectForm">
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
                            <div class="col">
                                <div class="form-group">
                                    <label for="Rkmpl">KMPL</label>
                                    <input type="text" class="form-control" id="Rkmpl" name="Rkmpl" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="driverDefect">Driver Defect Noticed</label>
                                    <select class="form-control" id="driverDefect" name="driverDefect" required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">

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
            $('#driverDefectForm').on('submit', function (event) {
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
                        url: '../database/depot_driver_defect_insert.php',
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