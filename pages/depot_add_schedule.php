<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM') {

    ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Addb Schedule Form</h4>
                    </div>
                    <div class="card-body">
                        <form action="your_submit_url" method="POST">
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
                                    < class="form-element">
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








                    <div class="form-element">
                        <label for="service" class="form-label">Service</label>
                        <select class="form-select" id="service" name="service" required>
                            <option value="" selected disabled>Select Service</option>
                            <option value="service1">Service 1</option>
                            <option value="service2">Service 2</option>
                            <option value="service3">Service 3</option>
                            <!-- Add more services as needed -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="serviceType" class="form-label">Service Type</label>
                        <select class="form-select" id="serviceType" name="service_type" required>
                            <option value="" selected disabled>Select Service Type</option>
                            <option value="type1">Type 1</option>
                            <option value="type2">Type 2</option>
                            <option value="type3">Type 3</option>
                            <!-- Add more service types as needed -->
                        </select>
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
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>