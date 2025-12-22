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
       
        <label for="division">Division:</label>
        <select id="division" name="division" required>
            <option value="">select</option>
        </select>

        <label for="depot">Depot:</label>
        <select id="depot" name="depot" required>
            <option value="">select</option>
        </select>

        <label for="status_type">Status Type:</label>
        <select id="status_type" name="status_type" required>
            <option value="">select</option>
            <option value="All">All</option>
            <option value="Working">Working</option>
            <option value="Not Working">Not Working</option>
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
                var status_type = $('#status_type').val();

                if (!division || !depot || !status_type) {
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
                        status_type: status_type,
                        action: 'fetch_report_of_tech_tool_details'
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