<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session has expired. Please login.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'ASO(Stat)') {
    ?>
<form id="dateForm">
    <input type="hidden" id="division" name="division" value="<?php echo $_SESSION['DIVISION_ID']; ?>">

    <!-- Depot Dropdown -->
    <label for="depot">Select Depot:</label>
    <select id="depot" name="depot" required>
        <option value="">Select Depot</option>
        <?php
    // Fetch depots from the database based on the session division_id
    $divisionId = $_SESSION['DIVISION_ID'];
    $sql = "SELECT depot_id, depot FROM location WHERE division_id = '$divisionId' AND depot != 'DIVISION'";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['depot_id'] . '">' . $row['depot'] . '</option>';
        }
    }
    ?>
    </select>
    <button class="btn btn-primary" type="submit">Generate Report</button>
</form>
<div class="container1">

    <div id="reportContainer"></div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                $('#depot').html(data);
            }
        });
    });
}

// Bind submit event to form
$('#dateForm').submit(function(e) {
    e.preventDefault(); // Prevent default form submission
    $.ajax({
        url: '../database/fetch_schedule_report_status.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(data) {
            $('#reportContainer').html(data);
        }
    });
});

$(document).ready(function() {
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