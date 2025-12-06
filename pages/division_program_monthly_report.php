<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DC' || $_SESSION['JOB_TITLE'] == 'DME') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>

    <h6>Select details for Program Report</h6>
    <form id="scheduleForm">
        <?php
        $currentDate = new DateTime();
        $currentYear = $currentDate->format("Y");
        $today = (int)$currentDate->format("j");      // day number
        $actualCurrentMonth = (int)$currentDate->format("n");

        if ($today > 4) {
            $currentMonth = $actualCurrentMonth - 1;   // previous month
        } else {
            $currentMonth = 0;  // show no months
        }

        $startYear = 2025;
        $startMonth = 10;

        // Generate year range
        $year_range = range($startYear, $currentYear);
        ?>

        <label for="year">Year:</label>
        <select id="year" name="year" onchange="updateMonths()" required>
            <option value="">Select</option>
            <?php
            foreach ($year_range as $year_val) {
                $selected = ($year_val == $currentYear) ? '' : '';
                echo '<option ' . $selected . ' value ="' . $year_val . '">' . $year_val . '</option>';
            }
            ?>
        </select>

        <label for="month">Month:</label>
        <select id="month" name="month" required>
            <option value="">Select</option>
            <?php
            if ($currentMonth > 0) {
                for ($i = $startMonth; $i <= $currentMonth; $i++) {
                    $month_name = date("F", mktime(0, 0, 0, $i, 1));
                    echo '<option value="' . $i . '">' . $month_name . '</option>';
                }
            }

            ?>
        </select>

        <input type="hidden" id="division" name="division" value="<?php echo $division_id; ?>">

        <label for="depot">Depot:</label>
        <select id="depot" name="depot" required>
            <option value="">select</option>
            <option value="All">All</option>
            <?php
            $depot_sql = "SELECT DEPOT_ID, DEPOT FROM location WHERE DIVISION_ID = '$division_id' and depot != 'DIVISION' ORDER BY DEPOT_ID;";
            $depot_result = mysqli_query($db, $depot_sql);
            while ($row = mysqli_fetch_assoc($depot_result)) {
                $depot_id_option = $row['DEPOT_ID'];
                $depot_name = $row['DEPOT'];
                echo "<option value='$depot_id_option'>$depot_name</option>";
            }
            ?>
        </select>

        <label for="program_type">Program Type:</label>
        <select id="program_type" name="program_type" required>
            <option value="">select</option>
            <option value="All">All</option>
            <?php
            $programtype_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'program_master'  AND TABLE_SCHEMA = 'kkrtcdvp_data'  AND ORDINAL_POSITION between 5 and 6 AND ORDINAL_POSITION < (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'program_master' AND TABLE_SCHEMA = 'kkrtcdvp_data') - 1 ORDER BY ORDINAL_POSITION;";
            $programtype_result = mysqli_query($db, $programtype_sql);

            while ($row = mysqli_fetch_assoc($programtype_result)) {
                $column_name = $row['COLUMN_NAME'];
                // Format for display: replace _ with space and capitalize first letters
                $display_name = ucwords(str_replace('_', ' ', $column_name));
                echo "<option value='$column_name'>$display_name</option>";
            }
            ?>
        </select>

        <button class="btn btn-primary" type="submit">Submit</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>

    </form>
    <div id="loadingIndicator" style="display:none; text-align:center; margin: 10px;">
        <img src="../images/loading.gif" alt="Loading..." width="150" />
        <p>Loading data, please wait...</p>
    </div>


    <div class="container1">
        <div id="reportContainer"></div>
    </div>
    <!-- Include SweetAlert2 -->
    <?php
    date_default_timezone_set('Asia/Kolkata'); // Set the time zone to Asia/Kolkata
    $currentDate = date('Y-m-d', strtotime('+1 day')); // Get current date plus 1 day in YYYY-MM-DD format
    ?>

    <script>
        //add select2 for program type
        $('#program_type').select2({
            placeholder: "Select Program Type",
            allowClear: true
        });

        

        function updateMonths() {
            // Get the selected year
            const yearSelect = document.getElementById("year");
            const monthSelect = document.getElementById("month");
            const selectedYear = parseInt(yearSelect.value);

            // Clear existing options in the month dropdown
            monthSelect.innerHTML = "";

            // Add a default "Select" option
            const defaultOption = document.createElement("option");
            defaultOption.value = "";
            defaultOption.textContent = "Select Month";
            defaultOption.selected = true;
            defaultOption.disabled = true;
            monthSelect.appendChild(defaultOption);

            // Define start year, start month, and current year/month
            const startYear = 2025;
            const startMonth = 10;
            const currentYear = new Date().getFullYear();
            const today = new Date().getDate();
            let actualCurrentMonth = new Date().getMonth() + 1;
            let currentMonth = (today > 4) ? actualCurrentMonth - 1 : 0;


            let start = 1; // Default start month
            let end = 12; // Default end month

            // Adjust start and end months based on the selected year
            if (selectedYear === startYear) {
                start = startMonth; // Start from December 2023
            }
            if (selectedYear === currentYear) {
                end = currentMonth; // End at the current month
            }

            // Populate the month dropdown
            for (let i = start; i <= end; i++) {
                const monthName = new Date(2000, i - 1, 1).toLocaleString("default", {
                    month: "long"
                });
                const option = document.createElement("option");
                option.value = i;
                option.textContent = monthName;
                monthSelect.appendChild(option);
            }
        }

        $(document).ready(function() {
            $('#scheduleForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                var division = $('#division').val();
                var depot = $('#depot').val();
                var program_type = $('#program_type').val();
                var year = $('#year').val();
                var month = $('#month').val();
                if (!year || !month || !division || !depot || !program_type) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Selection',
                        text: 'Please select all fields before submitting the form.',
                        confirmButtonText: 'OK'
                    });
                    return; // Exit the function if validation fails
                }

                if (month < 10 && year < 2026) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date',
                        text: 'Please select a month and year between Oct-2025 and <?php echo date('M-Y'); ?>. Reports available only for this range.',
                        confirmButtonText: 'OK'
                    });
                    return; // Exit the function if validation fails
                }


                // Show loading and clear report container
                $('#reportContainer').html('');
                $('#loadingIndicator').show();

                $.ajax({
                    type: 'POST',
                    url: '../includes/backend_data.php',
                    dataType: 'json',
                    data: {
                        year: year,
                        month: month,
                        division: division,
                        depot: depot,
                        program_type: program_type,
                        action: 'fetch_report_of_monthly_program'
                    },
                    success: function(response) {
                        $('#loadingIndicator').hide(); // hide loading on success
                        if (response.status === 'success') {
                            $('#reportContainer').html(response.data);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loadingIndicator').hide(); // hide loading on error
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'A Network error occurred: ' + (xhr.responseText || error),
                            confirmButtonText: 'OK'
                        });
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