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
    <form id="dateForm">
        <label for="date">Select Date:</label>
        <input type="date" id="date" name="date" required max="<?php echo date('Y-m-d'); ?>">
        <button class="btn btn-primary" type="submit">Generate Report</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>

    </form>

    <div class="container1">
        <div id="reportTable"></div>
    </div>
    <script>
        document.getElementById('dateForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var selectedDate = document.getElementById('date').value;

            fetch('../database/fetch_crew_report_d.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ date: selectedDate })
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw new Error(text); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.html.includes('Error')) {
                        throw new Error('Server Error: ' + data.html);
                    }
                    document.getElementById('reportTable').innerHTML = data.html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('reportTable').innerHTML = 'An error occurred while fetching the report.';
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