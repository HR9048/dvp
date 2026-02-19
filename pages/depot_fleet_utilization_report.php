<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM')) {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>
    <style>
        /* add print style on landscape and add font size 10px */
        @media print {
            @page {
                size: landscape;
            }

            body {
                font-size: 10px;
            }

        }
    </style>
    <h6>Select details for Program Report</h6>
    <form id="scheduleForm">
        <?php
        $currentDate = new DateTime();
        $currentYear = $currentDate->format("Y");
        $today = (int)$currentDate->format("j");      // day number
        $actualCurrentMonth = (int)$currentDate->format("n");

        $currentMonth = $actualCurrentMonth;   // previous month


        $startYear = 2025;
        $startMonth = $start_month;

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
        <input type="hidden" id="depot" name="depot" value="<?php echo $depot_id; ?>">



        <button class="btn btn-primary" type="submit">Submit</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>
        <button class="btn btn-success" id="downloadExcel">Download Excel</button>


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
            const startMonth = <?php echo $start_month; ?>;
            const currentYear = new Date().getFullYear();
            const today = new Date().getDate();
            let actualCurrentMonth = new Date().getMonth() + 1;
            let currentMonth = actualCurrentMonth; // Default to previous month if today is not in the current month


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
                var year = $('#year').val();
                var month = $('#month').val();
                if (!year || !month || !division || !depot) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Selection',
                        text: 'Please select all fields before submitting the form.',
                        confirmButtonText: 'OK'
                    });
                    return; // Exit the function if validation fails
                }

                if (month < <?php echo $start_month; ?> && year < 2026) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date',
                        text: 'Please select a month and year between <?php echo $start_month_name; ?>-2025 and <?php echo date('M-Y'); ?>. Reports available only for this range.',
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
                        action: 'fetch_fleet_utilization_report'
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
        $(document).ready(function() {
            // Get current date from PHP
            var todayDate = "<?php echo $currentDate; ?>"; // Date in 'YYYY-MM-DD' format

            document.getElementById('downloadExcel').addEventListener('click', function() {
                // Get the HTML table element
                var table = document.querySelector('.container1');

                // Convert table to workbook
                var workbook = XLSX.utils.table_to_book(table, {
                    raw: true
                });

                // Get the first worksheet
                var worksheet = workbook.Sheets[workbook.SheetNames[0]];

                // Loop through all cells in the worksheet
                for (var cell in worksheet) {
                    if (worksheet.hasOwnProperty(cell) && cell[0] !== '!') {
                        var cellValue = worksheet[cell].v;

                        // ✅ Detect if it's a date in YYYY-MM-DD format
                        if (/^\d{4}-\d{2}-\d{2}$/.test(cellValue)) {
                            // Reformat to dd-mm-yyyy
                            var parts = cellValue.split("-");
                            var formattedDate = parts[2] + "-" + parts[1] + "-" + parts[0];

                            worksheet[cell].v = formattedDate; // Update cell value
                            worksheet[cell].t = 's'; // Force text format
                        }

                        // ✅ Prevent number conversion for text
                        if (typeof cellValue === 'string' && !isNaN(cellValue)) {
                            worksheet[cell].t = 's'; // Force text type for numeric strings
                        }
                    }
                }

                // Export Excel file with current date in file name
                XLSX.writeFile(workbook, 'Monthly_program_report.xlsx');
            });
        });
    </script>

<?php
} else {
    echo "<script>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php'; ?>