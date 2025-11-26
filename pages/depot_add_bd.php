<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $today_date = date('Y-m-d');
    $check_query = "SELECT COUNT(*) as dvp_count FROM dvp_data WHERE division='$division_id' AND depot='$depot_id' AND date='$today_date'";
    $check_result = mysqli_query($db, $check_query);
    $check_row = mysqli_fetch_assoc($check_result);
    $dvp_count = $check_row['dvp_count'];
    if ($dvp_count > 0) {
        echo "<script type='text/javascript'>Swal.fire({icon: 'error',title: 'DVP Already Submitted',text: 'DVP for today has already been submitted. You cannot add breakdowns now.',}).then((result) => {if (result.isConfirmed) {window.location = 'depot_dashboard.php';}});</script>";
        exit;
    }

?>
<div class="container" style="margin-top: 20px; width: 70%;">
    <div class="container1">
        <h2 class="text-center">Add BreakDown</h2>
        <form method="POST" id="bd_form">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for=bd_date>BreakDown Date:</label>
                        <input type="date" class="form-control" id="bd_date" name="bd_date" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" readonly>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="bus_number">Bus Number:</label>
                        <select class="form-control" id="bus_number" name="bus_number" required>
                            <option value="" disabled selected>Select Bus Number</option>
                            <?php
                // Fetch bus numbers from the database
                $query = "SELECT BUS_NUMBER FROM bus_registration WHERE depot_name = ? AND division_name = ?
          UNION
          SELECT bus_number FROM bus_scrap_data WHERE order_date > '2025-03-31' AND depot = ? AND division = ?";

if ($stmt = $db->prepare($query)) {
    $stmt->bind_param("ssss", $depot_id, $division_id, $depot_id, $division_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . htmlspecialchars($row['BUS_NUMBER']) . "'>" . htmlspecialchars($row['BUS_NUMBER']) . "</option>";
    }

    $stmt->close();
                } else {
                    echo "<option value='' disabled>Error fetching bus numbers</option>";
                }
                ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="route_number">Route Number:</label>
                        <input type="text" class="form-control" id="route_number" name="route_number" required>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="bd_location">BreakDown Location:</label>
                        <input type="text" class="form-control" id="bd_location" name="bd_location" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="cause">Cause:</label>
                        <select class="form-control" id="cause" name="cause" required>
                            <option value="" disabled selected>Select Cause</option>
                            <?php
                // Fetch causes from the database
                $query = "SELECT distinct(cause_id), cause FROM bd_cause";
                if ($stmt = $db->prepare($query)) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['cause_id'] . "'>" . htmlspecialchars($row['cause']) . "</option>";
                    }
                    $stmt->close();
                } else {
                    echo "<option value='' disabled>Error fetching causes</option>";
                }
                ?>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="reason">Reason:</label>
                        <select class="form-control" id="reason" name="reason" required>
                            <option value="" disabled selected>Select Reason</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="km_after_docking">KM After Docking:</label>
                        <input type="number" class="form-control" id="km_after_docking" name="km_after_docking"
                            required>
                    </div>
                </div>
                <div class="col"></div>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    <script>
    $(document).ready(function() {
        $('#bus_number').select2({
            placeholder: "Select Bus Number",
            width: '100%'
        });
        $('#reason').select2({
            placeholder: "Select Reason",
            width: '100%'
        });
    });

    $(document).ready(function() {
        $('#cause').on('change', function() {
            var cause_id = $(this).val();
            var action = 'fetch_bd_reason';

            if (cause_id) {
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: {
                        cause_id: cause_id,
                        action: action
                    },
                    success: function(data) {
                        $('#reason').html(data);
                    },
                    error: function() {
                        alert('Error retrieving reasons.');
                    }
                });
            } else {
                $('#reason').html('<option value="">Select Reason</option>');
            }
        });
    });
    document.getElementById('bd_date').addEventListener('change', function() {
        var bdDate = this.value;
        if (!bdDate) return;

        var selectedDate = new Date(bdDate);
        selectedDate.setHours(0, 0, 0, 0); // Normalize selected date (remove time)

        var minDate = new Date('2025-04-01');
        minDate.setHours(0, 0, 0, 0); // Normalize min date

        var today = new Date();
        today.setHours(0, 0, 0, 0); // Normalize today's date

        if (selectedDate < minDate) {
            Swal.fire({
                icon: 'error',
                title: 'Date Too Early',
                text: 'The selected date cannot be earlier than 01-04-2025.',
            });
            this.value = '';
        } else if (selectedDate > today) {
            Swal.fire({
                icon: 'error',
                title: 'Date In Future',
                text: 'The selected date cannot be in the future.',
            });
            this.value = '';
        }
    });
    $(document).ready(function() {
        $('#bd_form').on('submit', function(e) {
            e.preventDefault();

            var bdDate = $('#bd_date').val();
            var busNumber = $('#bus_number').val();
            var routeNumber = $('#route_number').val();
            var bdLocation = $('#bd_location').val();
            var cause = $('#cause').val();
            var reason = $('#reason').val();
            var kmAfterDocking = $('#km_after_docking').val();

            // Validate bdDate
            var selectedDate = new Date(bdDate);
            selectedDate.setHours(0, 0, 0, 0);
            var minDate = new Date('2025-04-01');
            minDate.setHours(0, 0, 0, 0);
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < minDate || selectedDate > today) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date',
                    text: 'Invalide date selected please select a valid date.',
                });
                return;
            }

            // All fields required validation
            if (!busNumber || !cause || !reason || !kmAfterDocking || !routeNumber || !bdLocation) {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Data',
                    text: 'All fields are required!',
                });
                return;
            }

            var formData = $(this).serialize();
            formData += '&action=insert_bd_data'; // Add action parameter

            $.ajax({
                url: '../includes/backend_data.php',
                type: 'POST',
                data: formData,
                dataType: 'json', // Expect JSON response
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.success,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else if (response.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Unexpected server response.',
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'AJAX Error',
                        text: 'An error occurred while inserting data: ' + error
                    });
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