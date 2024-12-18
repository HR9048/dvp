<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
$division_id = $_SESSION['DIVISION_ID'];
$depot_id = $_SESSION['DEPOT_ID'];
$username = $_SESSION['USERNAME'];
// Process form submission

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule_number'])) {
    // Validate and sanitize input
    $schedule_number = isset($_POST['schedule_number']) ? htmlspecialchars(trim($_POST['schedule_number'])) : null;
    $cancel_date = isset($_POST['cancel_date']) ? htmlspecialchars(trim($_POST['cancel_date'])) : null;

    // Convert the date format from dd-mm-yyyy to yyyy-mm-dd
    if ($cancel_date) {
        $date = DateTime::createFromFormat('d-m-Y', $cancel_date);
        if ($date) {
            $cancel_date = $date->format('Y-m-d');
        } else {
            echo "<script>alert('Invalid date format. Please use dd-mm-yyyy.');</script>";
            $cancel_date = null; // Set to null if the format is invalid
        }
    }
        $reason = isset($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : null;

    if (!empty($schedule_number) && !empty($cancel_date) && !empty($reason)) {
        // Insert data into the database
        $query = "INSERT INTO schedule_cancel (sch_key_no, cancel_date, reason, division_id, depot_id, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param('sssiis', $schedule_number, $cancel_date, $reason, $division_id, $depot_id, $username);

        if ($stmt->execute()) {
            // Success message and unset POST variables
            echo "<script>alert('Schedule successfully cancelled!');</script>";

            // Unset POST variables to avoid re-submission
            unset($_POST['schedule_number']);
        } else {
            echo "<script>alert('Failed to record the cancellation. Please try again.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('All fields are required. Please fill in the form completely.');</script>";
    }
}


// Fetch schedule numbers from the database
if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM')) {

    $query = "SELECT sch_key_no FROM schedule_master WHERE division_id = ? AND depot_id = ? AND status = 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ii', $division_id, $depot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row['sch_key_no'];
    }
    $stmt->close();

    // Get today's and tomorrow's date in 'dd-mm-yyyy' format and Asia/Kolkata timezone
    date_default_timezone_set('Asia/Kolkata');
    $today = date('d-m-Y');
    $tomorrow = date('d-m-Y', strtotime('+1 day'));
    ?>

    <div class="container">
        <h2>Cancel Schedule</h2>
        <form action="" method="POST" onsubmit="return validateForm();">
            <div class="form-group row">
                <div class="col">
                    <label for="schedule_number">Schedule Number:</label>
                    <select name="schedule_number" id="schedule_number" class="form-control" required>
                        <option value="">Select Schedule</option>
                        <?php foreach ($schedules as $schedule) { ?>
                            <option value="<?php echo htmlspecialchars($schedule); ?>">
                                <?php echo htmlspecialchars($schedule); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col">
                    <label for="cancel_date">Cancel Date:</label>
                    <select name="cancel_date" id="cancel_date" class="form-control" required>
                        <option value="">Select Date</option>
                        <option value="<?php echo $today; ?>">Today (<?php echo $today; ?>)</option>
                        <option value="<?php echo $tomorrow; ?>">Tomorrow (<?php echo $tomorrow; ?>)</option>
                    </select>
                </div>
                <div class="col">
                    <label for="reason">Reason:</label>
                    <div class="col-sm-9">
                        <input type="text" name="reason" id="reason" class="form-control"
                            placeholder="Enter cancellation reason" required>
                    </div>
                </div>
            </div>
            <br>
            <div class="form-group row">
                <div class="col-sm-12 text-center">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // JavaScript validation
        function validateForm() {
            const schedule = document.getElementById('schedule_number').value.trim();
            const date = document.getElementById('cancel_date').value.trim();
            const reason = document.getElementById('reason').value.trim();

            if (!schedule || !date || !reason) {
                alert('All fields are required. Please complete the form.');
                return false;
            }
            return true;
        }
    </script>

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>