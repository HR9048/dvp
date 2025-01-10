<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && ($_SESSION['JOB_TITLE'] == 'DC' || $_SESSION['JOB_TITLE'] == 'ASO(Stat)' || $_SESSION['JOB_TITLE'] == 'DTO')) {
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

    <input type="hidden" id="division_id" name="division_id" value="<?php echo $_SESSION['DIVISION_ID']; ?>">
    <?php
// Assuming you're using a MySQLi connection
$division_id = $_SESSION['DIVISION_ID']; // Get session division ID

// Create a MySQLi connection (assuming $db is your MySQLi connection object)
$query = "SELECT depot_id, DEPOT FROM LOCATION WHERE DIVISION_ID = ?";
$stmt = $db->prepare($query);

// Bind the session division_id parameter
$stmt->bind_param('i', $division_id); // 'i' means integer type

// Execute the query
$stmt->execute();

// Fetch the results
$result = $stmt->get_result();

// Fetch all depot_id and DEPOT results
$depot_ids = [];
while ($row = $result->fetch_assoc()) {
    $depot_ids[] = [
        'depot_id' => $row['depot_id'],
        'depot'    => $row['DEPOT']
    ];
}

// Close the statement
$stmt->close();
?>


    <label for="year">Depot:</label>
    <select name="depot_id" id="depot_id">
        <option value="">Select Depot</option>
        <?php foreach ($depot_ids as $row): ?>
        <option value="<?= htmlspecialchars($row['depot_id']) ?>"><?= htmlspecialchars($row['depot']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Submit</button>
    <button class="btn btn-success" onclick="window.print()">Print</button>

</form>
<div class="container1">
    <div id="reportContainer"></div>
</div>
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
        const division_id = $('#division_id').val();
        const depot_id = $('#depot_id').val();

        $.ajax({
            type: 'POST',
            url: '../database/monthly_schedule_report.php',
            data: JSON.stringify({
                month: month,
                year: year,
                division_id: division_id,
                depot_id: depot_id
            }),
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