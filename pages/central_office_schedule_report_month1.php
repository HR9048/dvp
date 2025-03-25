<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && ($_SESSION['JOB_TITLE'] == 'CME_CO' )) {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    ?>
<div id="loadingOverlay" style="display: none;">
    <div class="loading-spinner"></div>
    <p>Loading, please wait...</p>
</div>
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
    <label for="division">Select Division:</label>
        <select id="division" name="division" required>
            <option value="">Select Division</option>
        </select>

        <label for="depot">Select Depot:</label>
        <select id="depot" name="depot" required>
            <option value="">Select Depot</option>
        </select>

    <button class="btn btn-primary" type="submit">Submit</button>
    <button class="btn btn-success" onclick="window.print()">Print</button>

</form>
<div class="container1">
    <div id="reportContainer"></div>
</div>
<script>
function fetchBusCategory() {
    $.ajax({
        url: '../includes/data_fetch.php',
        type: 'GET',
        data: {
            action: 'fetchDivision'
        },
        success: function(response) {
            var divisions = JSON.parse(response);
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

                // Hide the option with text 'DIVISION'
                $('#depot option').each(function() {
                    if ($(this).text().trim() === 'DIVISION') {
                        $(this).hide();
                    }
                });
            }
        });
    });
}
$(document).ready(function () {
            fetchBusCategory();
        });
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
        const monthName = new Date(2000, i - 1, 1).toLocaleString("default", {
            month: "long"
        });
        const option = document.createElement("option");
        option.value = i;
        option.textContent = monthName;
        monthSelect.appendChild(option);
    }
}
</script>
<script>
$(document).ready(function() {
    $('#scheduleForm').on('submit', function(e) {
        e.preventDefault();
        const month = $('#month').val();
        const year = $('#year').val();
        const division_id = $('#division').val();
        const depot_id = $('#depot').val();

        // Show loading overlay when form is submitted
        $('#loadingOverlay').fadeIn();

        $.ajax({
            type: 'POST',
            url: '../database/monthly_schedule_report1.php',
            data: {
                action: 'schedulemonthlyreportdatafetch', // Action parameter
                month: month,
                year: year,
                division_id: division_id,
                depot_id: depot_id
            },
            success: function(response) {
                // Update the report container with the HTML response
                $('#reportContainer').html(response);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#reportContainer').html('<p>Error loading report.</p>');
            },
            complete: function() {
                // Hide loading overlay after request is completed
                $('#loadingOverlay').fadeOut();
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