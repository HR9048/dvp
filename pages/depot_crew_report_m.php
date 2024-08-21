<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM')) {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    ?>

    <h2>Select Month and Year for Schedule Report</h2>
    <form id="scheduleForm">
    <?php
            $currentDate = new DateTime();
            $currentYear = $currentDate->format("Y");
            $currentMonth = $currentDate->format("m");
            $startYear = 2024;
            $startMonth = 4;

            // Generate year range
            $year_range = range($startYear, $currentYear);
            ?>

        <label for="month">Month:</label>
        <select id="month" name="month">
        <?php
                    $month_range = array();
                    $selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
                    $selected_month = isset($_GET['month']) ? $_GET['month'] : date('n');

                    // Calculate start and end month based on selected year
                    $start = ($selected_year == $startYear) ? $startMonth : 1;
                    $end = ($selected_year == $currentYear) ? $currentMonth : 12;

                    for ($i = $start; $i <= $end; $i++) {
                        $month_range[$i] = date("F", mktime(0, 0, 0, $i, 1));
                    }

                    foreach ($month_range as $month_number => $month_name) {
                        $selected = ($selected_month == $month_number) ? 'selected' : '';
                        echo '<option ' . $selected . ' value ="' . $month_number . '">' . $month_name . '</option>';
                    }
                    ?>
        </select>

        <label for="year">Year:</label>
        <select id="year" name="year">
        <?php
                foreach ($year_range as $year_val) {
                    $selected_year = (isset($_GET['year']) && $year_val == $_GET['year']) ? 'selected' : '';
                    echo '<option ' . $selected_year . ' value ="' . $year_val . '">' . $year_val . '</option>';
                }
                ?>
        </select>

        <button class="btn btn-primary" type="submit">Submit</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>

    </form>
    <div class="container1">
    <div id="reportContainer"></div>
    </div>

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
                        try {
                            const data = JSON.parse(response);
                            $('#reportContainer').html(data.html);
                        } catch (error) {
                            console.error('Failed to parse JSON:', error);
                            $('#reportContainer').html('<p>Error parsing response.</p>');
                        }
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
 