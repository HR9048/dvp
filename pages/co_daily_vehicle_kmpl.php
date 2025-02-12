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

    <h6>Select date for daily report</h6>
    <form id="scheduleForm">
        <label for="date">Select Date:</label>
        <input type="date" id="date" name="date" required>
        <button class="btn btn-primary" type="submit">Submit</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>
    </form>
    <div class="container1">
        <div id="reportContainer"></div>
    </div>


    <script>
        $(document).ready(function () {
            $("#scheduleForm").submit(function (e) {
                e.preventDefault();
                var selectedDate = $("#date").val();

                $.ajax({
                    url: "../database/depot_day_kmpl_report.php", // PHP file to process the request
                    type: "POST",
                    data: { date: selectedDate },
                    success: function (response) {
                        $("#reportContainer").html(response); // Show report inside the container
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