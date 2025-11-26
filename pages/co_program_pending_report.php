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
        <label for="date">Date:</label>
        <input type="date" id="date" name="date" required>

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
                var program_type = $('#program_type').val();
                var date = $('#date').val();

                if (!date || !division || !depot || !program_type) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Selection',
                        text: 'Please select all fields before submitting the form.',
                        confirmButtonText: 'OK'
                    });
                    return; // Exit the function if validation fails
                }

                if(date < '2025-11-10' || date > '<?php echo date('Y-m-d'); ?>'){
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date',
                        text: 'Please select a date between 10-11-2025 and <?php echo date('d-m-Y'); ?>. Reports available only for this range.',
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
                        date: date,
                        division: division,
                        depot: depot,
                        program_type: program_type,
                        action: 'fetch_report_of_program_pending'
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