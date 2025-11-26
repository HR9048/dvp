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
        <label for="bus_number">Bus Number:</label>
        <select id="bus_number" name="bus_number">
            <option value="">First-select-depot</option>
        </select>


        <button class="btn btn-primary" type="submit">Submit</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>
        <button type="button" class="btn btn-info" onclick="downloadw3PDF()">Download PDF</button>


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

                        // Hide the option with text 'DIVISION'
                        $('#depot option').each(function() {
                            if ($(this).text().trim() === 'DIVISION') {
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
            // Initialize Select2 for bus_number select


            $('#bus_number').select2();

            // Fetch buses when depot changes
            $('#depot').on('change', function() {
                var depotId = $(this).val();
                if (depotId) {
                    fetchBusNumbers(depotId);
                } else {
                    $('#bus_number').html('<option value="">Select a depot first</option>');
                }
            });

            // If there's a pre-selected depot, trigger change
            var depotId = $('#depot').val();
            if (depotId) {
                $('#depot').trigger('change');
            }

            // AJAX function to fetch bus numbers
            function fetchBusNumbers(depotId) {
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: {
                        action: 'fetchBusNumbers',
                        depot_id: depotId
                    },
                    success: function(response) {
                        $('#bus_number').html(response);
                    },
                    error: function() {
                        $('#bus_number').html('<option value="">Failed to load buses</option>');
                    }
                });
            }
        });


        $(document).ready(function() {
            $('#scheduleForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                var from = $('#from').val();
                var to = $('#to').val();
                var division = $('#division').val();
                var depot = $('#depot').val();
                var bus_number = $('#bus_number').val();

                if (!bus_number) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required!',
                        text: 'Please select a Bus Number.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                var programstart_date = '';
                var formated_programstart_date = '';
                var depots_1 = ['1', '8', '12', '13', '14', '15'];
                var depots_2 = ['2', '3', '4', '5', '6', '7', '9', '10', '11', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53'];

                if (depots_1.includes(depot)) {
                    programstart_date = '2025-07-31';
                    formated_programstart_date = '01-08-2025';
                } else if (depots_2.includes(depot)) {
                    programstart_date = '2025-09-30';
                    formated_programstart_date = '01-10-2025';
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Depot Not Valid!',
                        text: 'Program not yet started for the selected depot. Please select a different depot.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                    return;
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

                $.ajax({
                    type: 'POST',
                    url: '../includes/backend_data.php',
                    dataType: 'json',
                    data: {
                        from: from,
                        to: to,
                        division: division,
                        depot: depot,
                        bus_number: bus_number,
                        action: 'fetch_report_of_w3_from_to'
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

        function downloadw3PDF() {
            var from = $('#from').val();
            var to = $('#to').val();
            var bus_number = $('#bus_number').val();
            var division = $('#division').val();
            var depot = $('#depot').val();

            let missingFields = [];

            if (!from) missingFields.push("From Date");
            if (!to) missingFields.push("To Date");
            if (!bus_number) missingFields.push("Bus Number");
            if (!division) missingFields.push("Division");
            if (!depot) missingFields.push("Depot");

            if (missingFields.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Fields Missing!',
                    text: 'Please fill the following: ' + missingFields.join(', '),
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // If all fields are filled, proceed to download
            window.location.href = 'w3_pdf_download.php?from=' + from + '&to=' + to + '&bus_number=' + bus_number +
                '&division=' + division + '&depot=' + depot;
        }
    </script>

<?php



} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>