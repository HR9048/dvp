<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Session Expired please Login again.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'SECURITY') {
    ?>
 <h6 style="text-align:right;"><button class="btn btn-secondary"><a style="color:white;" href="depot_schinout.php"><i class="fa-solid fa-backward fa-beat fa-lg" style="color: #ffffff;"></i>&nbsp; Back</a></button></h6>

<h2 class="text-center">SECURITY MODULE</h2>
        <div class="container" style="padding:2px;width: 40%; min-width: 300px; margin: 0 auto; text-align: center;">
            <h2>Depot: <?php echo $_SESSION['DEPOT']; ?></h2>
            <p style="color:red;">Incomplete Schedule In Entry</p>
            <form id="sch_in_form" method="POST" class="mt-4">
                <div class="row">
                <div class="col-md-6">
                    <div class="col-md-12 mb-3">
                        <div class="form-group">
                            <label for="sch_no_in">Schedule Key Number</label>
                            <select class="form-control select2" id="sch_no_in" name="sch_no_in" required
                                style="min-width: 100px;">
                                <option value="">Select a Schedule Number</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="col-md-12 mb-3">
                        <div class="form-group">
                            <label for="out_date">Schedule out Date</label>
                            <input class="form-control" type="date" id="out_date" name="out_date" required>
                        </div>
                    </div>
                </div>
                </div>
                <div id="scheduleInDetails">
                    <!-- Fields will be populated here dynamically using JavaScript -->
                </div>

            </form>
        </div>
        <script>
        function fetchScheduleIn() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchScheduleIn' },
                success: function (response) {
                    var bodyBuilders = JSON.parse(response);
                    $.each(bodyBuilders, function (index, value) {
                        $('#sch_no_in').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }
        $(document).ready(function () {
            fetchScheduleIn();
        });
        $(document).ready(function () {
            function fetchScheduleDetails() {
                var scheduleNo = $('#sch_no_in').val();
                var outDate = $('#out_date').val();

                if (scheduleNo && outDate) {
                    $.ajax({
                        url: 'fetch_schedulein_details.php',
                        type: 'POST',
                        data: { scheduleNo: scheduleNo, outDate: outDate },
                        success: function (response) {
                            $('#scheduleInDetails').html(response);
                        },
                        error: function (xhr, status, error) {
                            console.error('Error fetching schedule details:', error);
                        }
                    });
                }
            }

            $('#sch_no_in, #out_date').change(fetchScheduleDetails);
        });
        $(document).ready(function () {
    $('#sch_in_form').on('submit', function (e) {
        e.preventDefault(); // Prevent default form submission

        // Serialize form data
        var formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: '../database/depot_submit_schedule_in.php', // URL of the PHP script
            data: formData,
            dataType: 'json', // Expect a JSON response
            success: function (response) {
                if (response.status === 'success') {
                    // Display SweetAlert on success
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload the page after the alert is closed
                        window.location.reload();
                    });
                } else {
                    // Display SweetAlert on error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function (xhr, status, error) {
                // Handle the AJAX request error
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred: ' + error,
                    confirmButtonText: 'OK'
                });
            }
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