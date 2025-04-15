<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Session Expired please Login again.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'SECURITY') {
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    $today = date('Y-m-d');

    $vehicle_out_numbers = [];

    /* $query = "SELECT br.bus_number AS id, br.bus_number AS text
    FROM bus_registration br
    LEFT JOIN sch_veh_out svo 
        ON svo.vehicle_no = br.bus_number 
        AND svo.schedule_status = '1' 
        AND svo.depot_id = ? 
        AND svo.division_id = ?
    WHERE br.depot_name = ? 
      AND br.division_name = ? 
      AND br.deleted != '1' 
      AND br.scraped != '1'
      AND svo.vehicle_no IS NULL

    UNION

    SELECT vd.bus_number AS id, CONCAT(vd.bus_number, ' (deputed)') AS text
    FROM vehicle_deputation vd
    LEFT JOIN sch_veh_out svo2 
        ON svo2.vehicle_no = vd.bus_number 
        AND svo2.schedule_status = '1' 
        AND svo2.depot_id = ? 
        AND svo2.division_id = ?
    WHERE vd.t_depot_id = ? 
      AND vd.t_division_id = ? 
      AND vd.deleted != '1' 
      AND vd.status != '1' 
      AND vd.tr_date = ?
      AND svo2.vehicle_no IS NULL
";


    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "sssssssss",
        $depot_id,
        $division_id,
        $depot_id,
        $division_id,
        $depot_id,
        $division_id,
        $depot_id,
        $division_id,
        $today
    ); */

    $query = "SELECT bus_number as id, bus_number as text from bus_registration WHERE depot_name = ? AND division_name = ? AND deleted != '1' AND scraped != '1'";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $depot_id, $division_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $vehicle_out_numbers[] = [
            'id' => $row['id'],
            'text' => $row['text']
        ];
    }
    $stmt->close();


?>
    <p style="text-align:right"><button class="btn btn-warning"><a href="depot_schedule_incomplete.php">ಅಪೂರ್ಣತೆಯ ಅನುಸೂಚಿ?</a></button></p>
    <nav>
        <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
            <button class="nav-link active custom-size" id="nav-home-tab" data-bs-toggle="tab"
                data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home"
                aria-selected="true">ವಾಹನ ಹೊರಗೆ</button>
            <button class="nav-link custom-size" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                type="button" role="tab" aria-controls="nav-profile" aria-selected="false">ವಾಹನ ಒಳಗೆ</button>
        </div>
    </nav>
    <div>
        <div class="tab-content" id="nav-tabContent"
            style="width: 40%; min-width: 300px; margin: 0 auto; text-align: center;">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                <div class="container" style="padding:2px">
                    <h4>ಘಟಕ: <?php echo $_SESSION['DEPOT']; ?></h4>
                    <p style="color: red;">ವಾಹನ ನಿರ್ಗಮನ</p>
                    <form id="sch_out_form" method="POST" class="mt-4">
                        <div class="form-group">
                            <label for="veh_no_out">ವಾಹನ ಸಂಖ್ಯೆ</label>
                            <select class="form-control select2" id="veh_no_out" name="veh_no_out" required style="width: 100%;">
                                <option value="">ವಾಹನ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>
                                <?php foreach ($vehicle_out_numbers as $vehicle): ?>
                                    <option value="<?= htmlspecialchars($vehicle['id']) ?>"><?= htmlspecialchars($vehicle['text']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display: none;" id="schedule_field_wrapper">
                            <label for="schedule_out_select">ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆ</label>
                            <select name="schedule_out_select" id="schedule_out_select" class="form-control" required style="width: 100%;">
                                <option value="">Select Schedule</option>
                            </select>
                        </div>
                        <div id="scheduleoutdetailsview"></div>
                    </form>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                <div class="container" style="padding:2px">
                    <h4>ಘಟಕ: <?php echo $_SESSION['DEPOT']; ?></h4>
                    <p style="color:red;">ವಾಹನ ಆಗಮನ</p>
                    <form id="sch_in_form" method="POST" class="mt-4">

                        <div class="form-group">
                            <label for="veh_no_in">ವಾಹನ ಸಂಖ್ಯೆ</label>
                            <select class="form-control select2" id="veh_no_in" name="veh_no_in" required style="width: 100%;">
                                <option value="">ವಾಹನ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ</option>
                                <?php
                                $query = "SELECT vehicle_no FROM sch_veh_out WHERE schedule_status = '1' AND depot_id = ? AND division_id = ?";
                                $stmt = $db->prepare($query);
                                $stmt->bind_param("ss", $depot_id, $division_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['vehicle_no']) . "'>" . htmlspecialchars($row['vehicle_no']) . "</option>";
                                }
                                $stmt->close();
                                ?>
                            </select>

                        </div>
                        <div id="scheduleInDetails">
                            <!-- Fields will be populated here dynamically using JavaScript -->
                        </div>

                    </form>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-exchange" role="tabpanel" aria-labelledby="nav-exchange-tab">
                <div class="container" style="padding:2px">
                    <h4>ಘಟಕ: <?php echo $_SESSION['DEPOT']; ?></h4>
                    <nav>
                        <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
                            <button class="nav-link active custom-size" id="nav-bus-tab" data-bs-toggle="tab"
                                data-bs-target="#nav-bus" type="button" role="tab" aria-controls="nav-bus"
                                aria-selected="false">ವಾಹನ ಬದಲಾವಣೆ</button>
                            <button class="nav-link custom-size" id="nav-crew-tab" data-bs-toggle="tab"
                                data-bs-target="#nav-crew" type="button" role="tab" aria-controls="nav-profile"
                                aria-selected="false">ಸಿಬ್ಬಂದಿ ಬದಲಾವಣೆ</button>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#veh_no_out').select2({
                placeholder: 'ವಾಹನ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });

            $('#veh_no_in').select2({
                placeholder: 'ವಾಹನ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });
            $('#schedule_out_select').select2({
                placeholder: 'ಅನುಸೂಚಿ ಕೀ ಸಂಖ್ಯೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ',
                allowClear: true
            });
        });


        $('#veh_no_out').change(function() {
            var veh_no_out = $(this).val();
            if (veh_no_out) {
                $.ajax({
                    type: "POST",
                    url: "../includes/backend_data.php",
                    dataType: "json",
                    data: {
                        action: 'vehicle_out_fetch_schedules',
                        veh_no_out: veh_no_out
                    },
                    success: function(response) {
                        if (response.status === 'error') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Vehicle Already on Operation',
                                text: response.message,
                                confirmButtonColor: '#d33'
                            }).then(() => {
                                $('#veh_no_out').val(''); // Clear selected value
                                $('#schedule_out_select').html('<option value="">Select Schedule</option>');
                                $('#schedule_field_wrapper').hide();
                            });
                        } else if (response.status === 'success') {
                            $('#schedule_out_select').html(response.options);
                            $('#schedule_field_wrapper').show();
                        }
                    }
                });
            } else {
                $('#schedule_out_select').html('<option value="">Select Schedule</option>');
                $('#schedule_field_wrapper').hide();
            }
        });




        $('#schedule_out_select').change(function() {
            var scheduleKey = $(this).val();
            if (scheduleKey) {
                $.ajax({
                    type: "POST",
                    url: "../includes/backend_data.php",
                    data: {
                        action: 'fetch_schedule_details',
                        schedule_key_no: scheduleKey
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            scheduledetailsforselectedschedule(data); // Call your function with the data
                        } catch (e) {
                            console.error("Invalid JSON received", e);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                    }
                });
            }
        });

        scheduledetailsforselectedschedule = function(data) {
            var detailsHtml = '<div class="form-group">';
            detailsHtml += '<label for="schedule_out_details">ಅನುಸೂಚಿ ವಿವರಗಳು</label>';
            detailsHtml += '<input type="text" class="form-control" id="schedule_out_details" value="' + data.scheduleDetails + '" readonly>';
            detailsHtml += '</div>';
            $('#scheduleoutdetailsview').html(detailsHtml);
        }
    </script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>