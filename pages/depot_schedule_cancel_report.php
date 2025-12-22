<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM') {
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
    <input type="hidden" id="division" name="division" value="<?php echo $division_id; ?>">
    <input type="hidden" id="depot" name="depot" value="<?php echo $depot_id; ?>">



    <button class="btn btn-primary" type="submit">Submit</button>
    <button type="button" class="btn btn-success" onclick="window.print()">Print</button>
    <!-- button to download pdf on click call a function -->

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
    var todayDate = "<?php echo $currentDate; ?>"; 
    var reportstartDate = "2024-02-01"; 
    var formatted_reportstartDate = "February 1, 2024";
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
        // if form or to date is less then reportstartDate then show alert
        if (fromDate < new Date(reportstartDate) || toDate < new Date(reportstartDate)) {
            Swal.fire({
                icon: 'warning',
                title: 'Date Limit Exceeded!',
                text: 'Please select dates from ' + formatted_reportstartDate + '.',
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



$(document).ready(function() {
    $('#scheduleForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        var from = $('#from').val();
        var to = $('#to').val();
        var division = $('#division').val();
        var depot = $('#depot').val();

        // Show loading and clear report container
        $('#reportContainer').html('');
        $('#loadingIndicator').show();

        $.ajax({
            type: 'POST',
            url: '../includes/backend_data.php',
            dataType: 'json',
            data: {
                from: from,
                to: to,
                division: division,
                depot: depot,
                action: 'fetch_report_of_schedule_cancel_details'
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
                    text: 'A Network error occurred: ' + (xhr.responseText ||
                        error),
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