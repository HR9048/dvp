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

    <h6>Select Month and Year for Schedule Report</h6>
    <form id="scheduleForm">
    <?php
    $currentDate = new DateTime();
    $currentYear = $currentDate->format("Y");
    $currentMonth = $currentDate->format("n");
    $startYear = 2024;
    $startMonth = 12;

    // Generate year range
    $year_range = range($startYear, $currentYear);
    ?>

    <label for="year">Year:</label>
    <select id="year" name="year" onchange="updateMonths()" required>
        <option value="">Select</option>
        <?php
        foreach ($year_range as $year_val) {
            $selected = ($year_val == $currentYear) ? '' : '';
            echo '<option ' . $selected . ' value ="' . $year_val . '">' . $year_val . '</option>';
        }
        ?>
    </select>

    <label for="month">Month:</label>
    <select id="month" name="month" required>
        <option value="">Select</option>
        <?php
        for ($i = $startMonth; $i <= $currentMonth; $i++) {
            $month_name = date("F", mktime(0, 0, 0, $i, 1));
            $selected = ($i == $currentMonth) ? 'selected' : '';
            echo '<option ' . $selected . ' value="' . $i . '">' . $month_name . '</option>';
        }
        ?>
    </select>

    <button class="btn btn-primary" type="submit">Submit</button>
</form>

<script>
function updateMonths() {
    // Get the selected year
    const yearSelect = document.getElementById("year");
    const monthSelect = document.getElementById("month");
    const selectedYear = parseInt(yearSelect.value);

    // Clear existing options in the month dropdown
    monthSelect.innerHTML = "";

    // Add a default "Select" option
    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = "Select Month";
    defaultOption.selected = true;
    defaultOption.disabled = true;
    monthSelect.appendChild(defaultOption);

    // Define start year, start month, and current year/month
    const startYear = 2024;
    const startMonth = 12;
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1; // Month is zero-based

    let start = 1; // Default start month
    let end = 12; // Default end month

    // Adjust start and end months based on the selected year
    if (selectedYear === startYear) {
        start = startMonth; // Start from December 2023
    }
    if (selectedYear === currentYear) {
        end = currentMonth; // End at the current month
    }

    // Populate the month dropdown
    for (let i = start; i <= end; i++) {
        const monthName = new Date(2000, i - 1, 1).toLocaleString("default", { month: "long" });
        const option = document.createElement("option");
        option.value = i;
        option.textContent = monthName;
        monthSelect.appendChild(option);
    }
}

</script>


    <div class="container1">
        <div id="reportContainer"></div>
    </div>

    <script>
        $(document).ready(function () {
            $('#scheduleForm').on('submit', function (e) {
                e.preventDefault();
                const month = $('#month').val();
                const year = $('#year').val();

                $.ajax({
                    type: 'POST',
                    url: '../database/monthly_schedule_report.php',
                    data: JSON.stringify({ month: month, year: year }),
                    contentType: 'application/json',
                    success: function (response) {
                        try {
                            const data = JSON.parse(response);
                            $('#reportContainer').html(data.html);
                        } catch (error) {
                            console.error('Failed to parse JSON:', error);
                            $('#reportContainer').html('<p>Error parsing response.</p>');
                        }
                    },
                    error: function (xhr, status, error) {
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