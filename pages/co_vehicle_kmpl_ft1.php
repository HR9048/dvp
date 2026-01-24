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

    <h6>Select details for Program Report</h6>
    <form id="scheduleForm">
        <label for="from">From:</label>
        <input id="from" type="date" name="from">

        <label for="to">To:</label>
        <input id="to" type="date" name="to">

        <label for="division">Division:</label>
        <select id="division" name="division">
            <option value="">select</option>
        </select>

        <label for="depot">Depot:</label>
        <select id="depot" name="depot">
            <option value="">select</option>
        </select>

        <label for="kmpl_type">KMPL Type:</label>
        <select id="kmpl_type" name="kmpl_type" required>
            <option value="">select</option>
            <option value="All">All</option>
            <option value="<5.00">Less then 5.00</option>
            <option value="5.00-5.20">5.00 to 5.20</option>
            <option value=">5.20">Greater then 5.20</option>
        </select>

        <button class="btn btn-primary" type="submit">Submit</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>
        <button class="btn btn-secondary" onclick="functionExcelExport('Tech_Tool_details')" type="button">Export to Excel</button>

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

        //add select2 for program type
        $('#status_type').select2({
            placeholder: "Select Status Type",
            allowClear: true
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

                var division = $('#division').val();
                var depot = $('#depot').val();
                var kmpl_type = $('#kmpl_type').val();
                var fromdate = $('#from').val();
                var todate = $('#to').val();

                if (!division || !depot || !kmpl_type || ! fromdate || !todate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Selection',
                        text: 'Please select all fields before submitting the form.',
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
                        division: division,
                        depot: depot,
                        kmpl_type: kmpl_type,
                        from: fromdate,
                        to: todate,
                        action: 'fetch_report_of_kmpl_diff_details'
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