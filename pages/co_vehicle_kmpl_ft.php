<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && ($_SESSION['JOB_TITLE'] == 'CME_CO')) {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>

    <h6>Select Data for KMPL Monthly Report</h6>
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

        <label for="sch_no">Schedule No:</label>
        <select id="sch_no" name="sch_no">
            <option value="">Select Schedule</option>
        </select>

        <label for="bus_number">Bus Number:</label>
        <select id="bus_number" name="bus_number">
            <option value="">Select Bus</option>
        </select>

        <label for="driver_token">Driver Token:</label>
        <select id="driver_token" name="driver_token">
            <option value="">Select Driver</option>
        </select>
        <button class="btn btn-primary" type="submit">Submit</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>

    </form>
    <div class="container1">
        <div id="reportContainer"></div>
    </div>
    <script>
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
    </script>
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    </script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#sch_no, #bus_number, #driver_token').select2();

            // Handle change event for any of the three dropdowns
            $('#sch_no, #bus_number, #driver_token').on('change', function() {
                var selectedId = $(this).attr('id');

                // Loop through each field and reset the others
                $('#sch_no, #bus_number, #driver_token').each(function() {
                    if ($(this).attr('id') !== selectedId) {
                        if ($(this).val()) {
                            $(this).val(null).trigger('change.select2'); // Reset the other two
                        }
                    }
                });
            });



            // Handle depot change and fetch data
            $('#depot').change(function() {
                var depotId = $(this).val();
                $('#sch_no, #bus_number, #driver_token').html('<option value="">Select</option>').trigger('change.select2'); // Reset fields

                if (depotId) {
                    fetchScheduleNos(depotId);
                    fetchBusNumbers(depotId);
                    fetchDriverTokens(depotId);
                }
            });

            function fetchScheduleNos(depotId) {
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: {
                        action: 'fetchScheduleNos',
                        depot_id: depotId
                    },
                    success: function(response) {
                        $('#sch_no').html(response);
                    }
                });
            }

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
                    }
                });
            }

            function fetchDriverTokens(depotId) {
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: {
                        action: 'getDepotDetails',
                        depot_id: depotId
                    },
                    success: function(response) {
                        var depotDetails = JSON.parse(response);
                        if (depotDetails.kmpl_division && depotDetails.kmpl_depot) {
                            callApis(depotDetails.kmpl_division, depotDetails.kmpl_depot);
                        } else {
                            console.error("Missing kmpl_division or kmpl_depot");
                        }
                    }
                });
            }

            function callApis(kmplDivision, kmplDepot) {
                var apiUrl1 = `../includes/data.php?division=${kmplDivision}&depot=${kmplDepot}`;
                var apiUrl2 = `../database/private_emp_api.php?division=${kmplDivision}&depot=${kmplDepot}`;

                $.when($.get(apiUrl1), $.get(apiUrl2)).done(function(response1, response2) {
                    var data1 = response1[0]?.data ?? [];
                    var data2 = response2[0]?.data ?? [];
                    var combinedData = [...data1, ...data2];

                    $('#driver_token').html('<option value="">Select Driver</option>');
                    combinedData.forEach(driver => {
                        $('#driver_token').append(`<option value="${driver.EMP_PF_NUMBER}">${driver.token_number}-(${driver.EMP_NAME})</option>`);
                    });
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("API Call Failed:", textStatus, errorThrown);
                });
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#scheduleForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                var from = $('#from').val();
                var to = $('#to').val();
                var division = $('#division').val();
                var depot = $('#depot').val();
                var sch_no = $('#sch_no').val();
                var bus_number = $('#bus_number').val();
                var driver_token = $('#driver_token').val();

                if (!sch_no && !bus_number && !driver_token) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required!',
                        text: 'Please select any one: Schedule No, Bus Number, or Driver Token.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                    return; // Stop execution
                }

                console.log('Submitting form with values:', from, to, division, depot, sch_no, bus_number, driver_token);

                $.ajax({
                    type: 'POST',
                    url: '../database/monthly_kmpl_report_ft.php',
                    data: JSON.stringify({
                        from: from,
                        to: to,
                        division: division,
                        depot: depot,
                        sch_no: sch_no,
                        bus_number: bus_number,
                        driver_token: driver_token
                    }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            console.error('Error:', response.error);
                            $('#reportContainer').html('<p>Error: ' + response.error + '</p>');
                            return;
                        }
                        $('#reportContainer').html(response.html);
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        $('#reportContainer').html('<p>Error loading report.</p>');
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