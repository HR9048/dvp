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
date_default_timezone_set('Asia/Kolkata');
$today = date('d-m-Y');
$tomorrow = date('d-m-Y', strtotime('+1 day'));
// Process form submission
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

    ?>

    <div class="container">
        <h2>Cancel Schedule</h2>
        <form action="" id="cancelScheduleForm">
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
        document.getElementById('cancelScheduleForm').addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent the default form submission

            // Fetch form data
            const scheduleNumber = document.getElementById('schedule_number').value.trim();
            const cancelDate = document.getElementById('cancel_date').value.trim();
            const reason = document.getElementById('reason').value.trim();

            // Form validation
            if (!scheduleNumber) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please select a schedule number.',
                    confirmButtonText: 'OK',
                });
                return;
            }

            if (!cancelDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please select a cancel date.',
                    confirmButtonText: 'OK',
                });
                return;
            }

            if (!reason) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please enter a reason for cancellation.',
                    confirmButtonText: 'OK',
                });
                return;
            }

            // Prepare form data
            const formData = new FormData(this);
            formData.append('action', 'cancel_schedule'); // Add an action identifier

            // Perform AJAX request
            fetch('../includes/backend_data.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => {
                    return response.text(); // Read the response as text to catch HTML or JSON
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text); // Attempt to parse JSON
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Schedule canceled successfully!',
                                confirmButtonText: 'OK',
                            }).then(() => {
                                document.getElementById('cancelScheduleForm').reset();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to cancel the schedule: ' + data.message,
                                confirmButtonText: 'OK',
                            });
                        }
                    } catch (error) {
                        // Show the raw response in case of an error
                        console.error('Error parsing JSON:', error);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Unexpected Response',
                            html: `<pre>${text}</pre>`, // Show the raw response
                            confirmButtonText: 'OK',
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while canceling the schedule.',
                        confirmButtonText: 'OK',
                    });
                });
        });
    </script>


    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>