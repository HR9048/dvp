<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>

    <h6>Select details for W3 Report</h6>
    <form id="scheduleForm">

        <label for="from">From:</label>
        <input id="from" type="date" name="from" required>

        <label for="to">To:</label>
        <input id="to" type="date" name="to" required>
        <label for="division">Division:</label>
        <select id="division" name="division" required>
            <option value="">select</option>
        </select>

        <label for="depot">Depot:</label>
        <select id="depot" name="depot" required>
            <option value="">select</option>
        </select>

        <label for="program_type">Program Type:</label>
        <select id="program_type" name="program_type" required>
            <option value="">select</option>
            <option value="All">All</option>
            <?php
            $programtype_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'program_master'  AND TABLE_SCHEMA = 'kkrtcdvp_data'  AND ORDINAL_POSITION > 4  AND ORDINAL_POSITION < (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'program_master' AND TABLE_SCHEMA = 'kkrtcdvp_data') - 1 ORDER BY ORDINAL_POSITION;";
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
        $(document).ready(function() {
            // Get current date from PHP
            var todayDate = "<?php echo $currentDate; ?>"; // Date in 'YYYY-MM-DD' format

            // Date Validation: Ensure 'From' is not greater than 'To' and not greater than today
            $('#from, #to').on('change', function() {
                var fromDate = new Date($('#from').val());
                var toDate = new Date($('#to').val());
                var today = new Date(todayDate); // Use PHP's provided current date
                today.setHours(0, 0, 0, 0); // Reset hours to compare only dates

                // Validate the 'From' date
                if ($('#from').val()) {
                    if (fromDate > today) { // Allow today but not future dates
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid From Date!',
                            text: 'From date cannot be in the future. Please select today or a past date.',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'OK'
                        });
                        $('#from').val(''); // Clear the invalid date
                        return;
                    }
                }

                // Validate the 'To' date
                if ($('#to').val()) {
                    if (toDate > today) { // Allow today but not future dates
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid To Date!',
                            text: 'To date cannot be in the future. Please select today or a past date.',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'OK'
                        });
                        $('#to').val(''); // Clear the invalid date
                        return;
                    }
                }

                // Validate that 'From' date is not greater than 'To' date
                if ($('#from').val() && $('#to').val()) {
                    if (fromDate > toDate) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Date Selection!',
                            text: 'From date cannot be greater than To date. Please select valid dates.',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'OK'
                        });

                        // Clear the date fields
                        $('#from').val('');
                        $('#to').val('');
                    }
                }
                // if form or to date is less then 01-08-2025 then show alert
                if (fromDate < new Date('2025-08-01') || toDate < new Date('2025-08-01')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Limit Exceeded!',
                        text: 'Please select dates after 01-08-2025.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                    $('#from').val('');
                    $('#to').val('');
                }
            });

            // Form Submission Validation
            $('#scheduleForm').submit(function(e) {
                var fromDate = $('#from').val();
                var toDate = $('#to').val();

                if (!fromDate || !toDate) {
                    e.preventDefault(); // Prevent form submission

                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Required!',
                        text: 'Please select both From and To dates before submitting.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                var from = new Date(fromDate);
                var to = new Date(toDate);
                var today = new Date(todayDate); // Use PHP's provided current date
                today.setHours(0, 0, 0, 0); // Reset hours to compare only dates

                if (from > today || to > today) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date!',
                        text: 'Selected dates cannot be in the future. Please select today or a past date.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        function fetchBusCategory() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: {
                    action: 'fetchDivision'
                },
                success: function(response) {
                    var divisions = JSON.parse(response);
                    //add All option
                    $('#division').append('<option value="All">All</option>');
                    $.each(divisions, function(index, division) {
                        if (division.DIVISION !== 'HEAD-OFFICE' && division.DIVISION !== 'RWY') {
                            $('#division').append('<option value="' + division.division_id + '">' + division
                                .DIVISION + '</option>');
                        }
                    });
                }
            });

            $('#division').change(function() {
                var Division = $(this).val();
                $.ajax({
                    url: '../includes/data_fetch.php?action=fetchDepot',
                    method: 'POST',
                    data: {
                        division: Division
                    },
                    success: function(data) {
                        // Update the depot dropdown with fetched data
                        $('#depot').html(data);
                        $('#depot').prepend('<option value="All">All</option>');
                        // Hide the option with text 'DIVISION'
                        $('#depot option').each(function() {
                            if ($(this).text().trim() === 'DIVISION' || $(this).text().trim() === 'KALABURAGI') {
                                $(this).hide();
                            }
                        });
                    }
                });
            });
        }
        $(document).ready(function() {
            fetchBusCategory();
        });


        $(document).ready(function() {
            $('#scheduleForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                var from = $('#from').val();
                var to = $('#to').val();
                var division = $('#division').val();
                var depot = $('#depot').val();
                var program_type = $('#program_type').val();

                var programstart_date = '';
                var formated_programstart_date = '';
                var depots_1 = ['1', '8', '12', '13', '14', '15'];
                var depots_2 = ['111'];

                if (depots_1.includes(depot)) {
                    programstart_date = '2025-07-31';
                    formated_programstart_date = '31-07-2025';
                } else if (depots_2.includes(depot)) {
                    programstart_date = '2025-09-30';
                    formated_programstart_date = '30-09-2025';
                } else {
                    if (depot != 'All') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Depot Not Valid!',
                            text: 'Program not yet started for the selected depot. Please select a different depot.',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                }

                if (from < programstart_date || to < programstart_date) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Not Valid!',
                        text: 'Please select another date because the program start date for the selected depot is ' + formated_programstart_date + '.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                    return;
                }


                // Show loading and clear report container
                $('#reportContainer').html('');
                $('#loadingIndicator').show();
                console.log("from: " + from + ", to: " + to + ", division: " + division + ", depot: " + depot + ", program_type: " + program_type);

                $.ajax({
                    type: 'POST',
                    url: '../includes/backend_data.php',
                    dataType: 'json',
                    data: {
                        from: from,
                        to: to,
                        division: division,
                        depot: depot,
                        program_type: program_type,
                        action: 'fetch_report_of_program_fromto'
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