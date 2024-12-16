<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Retrieve and sanitize form data
        $schedule_no = $_POST['schedule_no'];
        $description = $_POST['description'];
        $schedule_km = $_POST['schedule_km'];
        $dep_time = $_POST['dep_time'];
        $arr_time = $_POST['arr_time'];
        $service_class_id = $_POST['service_class_id'];
        $service_type_id = $_POST['service_type_id'];
        $division = $_SESSION['DIVISION_ID'];
        $depot = $_SESSION['DEPOT_ID'];
        $status='1';

        // Determine schedule count based on service type id
        if ($service_type_id == 1 || $service_type_id == 2) {
            $sch_count = 1;
        } elseif ($service_type_id == 3 || $service_type_id == 4) {
            $sch_count = 2;
        } else {
            $sch_count = 0;
        }

        // Determine number of buses based on service type id
        if ($service_type_id == 1 || $service_type_id == 2) {
            $number_of_buses = 1;
        } elseif ($service_type_id == 3 || $service_type_id == 4) {
            $number_of_buses = 2;
        } else {
            $number_of_buses = 0;
        }

        // Validate required fields
        if (empty($schedule_no)) {
            echo "<script>alert('Schedule No is required'); window.history.back();</script>";
            exit;
        }
        if (empty($description)) {
            echo "<script>alert('Description is required'); window.history.back();</script>";
            exit;
        }
        if (empty($schedule_km)) {
            echo "<script>alert('Schedule KM is required'); window.history.back();</script>";
            exit;
        }
        if (empty($dep_time)) {
            echo "<script>alert('Schedule Departure Time is required'); window.history.back();</script>";
            exit;
        }
        if (empty($arr_time)) {
            echo "<script>alert('Schedule Arrival Time is required'); window.history.back();</script>";
            exit;
        }
        if (empty($service_class_id)) {
            echo "<script>alert('Service Class is required'); window.history.back();</script>";
            exit;
        }
        if (empty($service_type_id)) {
            echo "<script>alert('Service Type is required'); window.history.back();</script>";
            exit;
        }

        // Prepare SQL query with placeholders
        $sql = "INSERT INTO schedule_master 
                    (division_id, depot_id, sch_key_no, sch_abbr, sch_km, sch_dep_time, sch_arr_time, service_class_id, service_type_id, sch_count, number_of_buses, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Prepare statement
        if ($stmt = mysqli_prepare($db, $sql)) {
            // Bind parameters to the prepared statement
            mysqli_stmt_bind_param(
                $stmt,
                'iisssssiiiii',
                $division,
                $depot,
                $schedule_no,
                $description,
                $schedule_km,
                $dep_time,
                $arr_time,
                $service_class_id,
                $service_type_id,
                $sch_count,
                $number_of_buses,
                $status
            );

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Schedule added successfully'); window.location = 'depot_add_schedule.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_stmt_error($stmt) . "'); window.history.back();</script>";
            }

            // Close the statement
            mysqli_stmt_close($stmt);
        } else {
            echo "<script>alert('Error preparing the SQL statement.'); window.history.back();</script>";
        }
    }

    mysqli_close($db); // Close the database connection

    ?>


    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Addb Schedule Form</h4>
                    </div>
                    <div class="card-body">
                        <form id="add_schedule" method="POST">
                            <div class="row">
                                <div class="col">
                                    <div class="form-element">
                                        <label for="scheduleNo" class="form-label">Schedule No</label>
                                        <input type="text" class="form-control" id="scheduleNo" name="schedule_no"
                                            placeholder="Enter Schedule No" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-element">
                                        <label for="description" class="form-label">Description</label>
                                        <input type="text" class="form-control" id="description" name="description"
                                            placeholder="Enter Description" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="form-element">
                                        <label for="scheduleKm" class="form-label">Schedule KM</label>
                                        <input type="number" class="form-control" id="scheduleKm" name="schedule_km"
                                            placeholder="Enter Schedule KM" required>
                                    </div>
                                </div>
                                <div class="col">

                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="form-element">
                                        <label for="depTime" class="form-label">Schedule Departure Time</label>
                                        <input type="time" class="form-control" id="depTime" name="dep_time" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-element">
                                        <label for="arrTime" class="form-label">Schedule Arrival Time</label>
                                        <input type="time" class="form-control" id="arrTime" name="arr_time" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="service_class_id">Service Class:</label>
                                        <select id="service_class_id" name="service_class_id" class="form-control" required>
                                            <!-- Options for Service Class ID -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="service_type_id">Schedule Type:</label>
                                        <select id="service_type_id" name="service_type_id" class="form-control" required>
                                            <!-- Options for Service Type ID -->
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize event listeners for the initial state
        document.addEventListener('DOMContentLoaded', function () {
            //reattachEventListeners();
            ScheduleType();
            ServiceClass();
        });

        function ServiceClass() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'ServiceClass' },
                success: function (response) {
                    var service = JSON.parse(response);

                    // Clear existing options
                    $('#service_class_id').empty();

                    // Add default "Select" option
                    $('#service_class_id').append('<option value="">Select</option>');

                    // Add fetched options
                    $.each(service, function (index, value) {
                        $('#service_class_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        }

        function ScheduleType() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'ScheduleType' },
                success: function (response) {
                    var service = JSON.parse(response);

                    // Clear existing options
                    $('#service_type_id').empty();

                    // Add default "Select" option
                    $('#service_type_id').append('<option value="">Select</option>');

                    // Add fetched options
                    $.each(service, function (index, value) {
                        $('#service_type_id').append('<option value="' + value.id + '">' + value.type + '</option>');
                    });
                }
            });
        }
    </script>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>