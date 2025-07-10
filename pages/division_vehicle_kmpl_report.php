<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && ($_SESSION['JOB_TITLE'] == 'DME') || ($_SESSION['JOB_TITLE'] == 'DC')) {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    ?>
     <div id="loader">
        <div class="loader-icon"></div>
    </div>
    <div id="mainContent" style="display: none;">
        <!-- Loading Spinner -->



        <form id="reportForm">
            <label>Select Date:</label>
            <input type="date" id="selectedDate" name="selected_date" max="<?= date('Y-m-d'); ?>" required>
            <button type="submit">Generate Report</button>
        </form>
        <div id="loading" style="display: none;">
            <div class="spinner" style="text-align: center !important ;"></div>
            <div class="loading-text">Loading, please wait...</div>
        </div>
        <div class="container1">
            
            <div id="reportTable" style="margin-top: 30px;"></div>
        </div>
        <div class="text-center mt-3">
            <button class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
        <script>
            window.addEventListener("load", function() {
                document.getElementById("loader").style.display = "none";
                document.getElementById("mainContent").style.display = "block";
            });
            document.getElementById('reportForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const selectedDate = document.getElementById('selectedDate').value;
                const today = new Date().toISOString().split('T')[0];

                if (selectedDate > today) {
                    Swal.fire('Invalid Date', 'Future dates are not allowed.', 'error');
                    return;
                }

                // Show loading symbol
                document.getElementById('loading').style.display = 'block';
                document.getElementById('reportTable').innerHTML = '';

                fetch('../includes/backend_data.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=fetch_comparison_report&selected_date=' + encodeURIComponent(selectedDate)
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Hide loading and show result
                        document.getElementById('loading').style.display = 'none';
                        document.getElementById('reportTable').innerHTML = data;
                    })
                    .catch(error => {
                        document.getElementById('loading').style.display = 'none';
                        Swal.fire('Error', 'Something went wrong while fetching data.', 'error');
                    });
            });
        </script>

    </div>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>