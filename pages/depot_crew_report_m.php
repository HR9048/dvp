<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    ?>
    <h2>Select Month and Year for Schedule Report</h2>
    <form id="scheduleForm">
        <label for="month">Month:</label>
        <select id="month" name="month">
            <!-- Months options -->
            <option value="8">January</option>
            <option value="2">February</option>
            <!-- Continue adding months -->
        </select>

        <label for="year">Year:</label>
        <select id="year" name="year">
            <!-- Years options -->
            <option value="2024">2024</option>
            <!-- Continue adding years -->
        </select>

        <button type="submit">Submit</button>
    </form>

    <div id="reportContainer"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#scheduleForm').on('submit', function(e) {
                e.preventDefault();
                const month = $('#month').val();
                const year = $('#year').val();

                $.ajax({
                    type: 'POST',
                    url: '../database/monthly_schedule_report.php',
                    data: JSON.stringify({ month: month, year: year }),
                    contentType: 'application/json',
                    success: function(response) {
                        $('#reportContainer').html(response.html);
                    },
                    error: function() {
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