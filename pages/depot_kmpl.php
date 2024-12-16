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
            <button class="btn btn-primary" id="addDataButton" data-bs-toggle="modal" data-bs-target="#dataEntryModal">Add
                KMPL</button>
        </div>
    </div>
    </div>
    <h2 class="text-center">Kalyana Karnataka Road Transport Corporation (KKRTC)</h2>
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