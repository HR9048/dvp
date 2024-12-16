<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session has expired. Please login.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM'|| $_SESSION['JOB_TITLE'] == 'SECURITY')) {
?>
<form id="dateForm">
    <input type="hidden" id="division" name="division" value="<?php echo $_SESSION['DIVISION_ID']; ?>">
    <input type="hidden" id="depot" name="depot" value="<?php echo $_SESSION['DEPOT_ID']; ?>">
</form>

<div id="reportContainer"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        // Automatically submit the form via AJAX on page load
        fetchReport();

        function fetchReport() {
            $.ajax({
                url: '../database/fetch_schedule_report_status.php',
                method: 'POST',
                data: $('#dateForm').serialize(),
                success: function (data) {
                    $('#reportContainer').html(data);
                }
            });
        }
    });
</script>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>
