<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session has expired. Please login.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    ?>
    <form id="dateForm">
        <label for="division">Select Division:</label>
        <select id="division" name="division" required>
            <option value="">Select Division</option>
        </select>

        <label for="depot">Select Depot:</label>
        <select id="depot" name="depot" required>
            <option value="">Select Depot</option>
        </select>

        <button class="btn btn-primary" type="submit">Generate Report</button>
    </form>

    <div id="reportContainer"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchBusCategory() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchDivision' },
                success: function (response) {
                    var divisions = JSON.parse(response);
                    $.each(divisions, function (index, division) {
                        if (division.DIVISION !== 'HEAD-OFFICE' && division.DIVISION !== 'RWY') {
                            $('#division').append('<option value="' + division.division_id + '">' + division.DIVISION + '</option>');
                        }
                    });
                }
            });

            $('#division').change(function () {
                var Division = $(this).val();
                $.ajax({
                    url: '../includes/data_fetch.php?action=fetchDepot',
                    method: 'POST',
                    data: { division: Division },
                    success: function (data) {
                        // Update the depot dropdown with fetched data
                        $('#depot').html(data);

                        // Hide the option with text 'DIVISION'
                        $('#depot option').each(function () {
                            if ($(this).text().trim() === 'DIVISION') {
                                $(this).hide();
                            }
                        });
                    }
                });
            });
        }

        // Bind submit event to form
        $('#dateForm').submit(function (e) {
            e.preventDefault(); // Prevent default form submission
            $.ajax({
                url: '../database/fetch_schedule_report_status.php',
                method: 'POST',
                data: $(this).serialize(),
                success: function (data) {
                    $('#reportContainer').html(data);
                }
            });
        });

        $(document).ready(function () {
            fetchBusCategory();
        });
    </script>

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>