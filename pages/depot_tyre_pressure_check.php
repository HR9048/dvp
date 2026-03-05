<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page!'); window.location='logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM')) {

    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id    = $_SESSION['DEPOT_ID'];

    $today      = date('N'); // 1 to 7
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($today == 7) exit;   // Sunday no work

    $week_start = date('Y-m-d', strtotime('monday this week'));

    /* ================= DASHBOARD DATA ================= */

    $total_buses = $db->query("
        SELECT COUNT(*) as total
        FROM bus_registration
        WHERE division_name='$division_id'
        AND depot_name='$depot_id'
    ")->fetch_assoc()['total'];

    $done_today = $db->query("
        SELECT COUNT(*) as done
        FROM weekly_tyre_pressure_done
        WHERE division_id='$division_id'
        AND depot_id='$depot_id'
        AND done_date=CURDATE()
    ")->fetch_assoc()['done'];

    /* ================= TODAY TYRE SCHEDULE ================= */

    $today_query = $db->query("
        SELECT w.bus_number, w.week_start
        FROM weekly_tyre_pressure_schedule w
        WHERE (w.check_day1='$today' OR w.check_day2='$today')
        AND w.week_start='$week_start'
        AND w.division_id='$division_id'
        AND w.depot_id='$depot_id'

        AND NOT EXISTS (
            SELECT 1 FROM weekly_tyre_pressure_done d
            WHERE d.bus_number = w.bus_number
            AND d.week_start = w.week_start
            AND d.done_date = CURDATE()
        )
    ");

    $yesterday_query = $db->query("
        SELECT w.bus_number, w.week_start
        FROM weekly_tyre_pressure_schedule w
        WHERE (w.check_day1='$yesterday' OR w.check_day2='$yesterday')
        AND w.division_id='$division_id'
        AND w.depot_id='$depot_id'

        AND NOT EXISTS (
            SELECT 1 FROM weekly_tyre_pressure_done d
            WHERE d.bus_number = w.bus_number
            AND d.week_start = w.week_start
            AND d.done_date = CURDATE()
        )
    ");
?>

    <div class="container mt-4">

        <div class="card shadow-sm p-3 mb-4">
            <div class="row text-center">
                <div class="col-md-4">
                    <h5>Total Active Buses</h5>
                    <h2 class="text-primary"><?= $total_buses; ?></h2>
                </div>
                <div class="col-md-4">
                    <h5>Maintenance Done Today</h5>
                    <h2 class="text-success" id="doneCount"><?= $done_today; ?></h2>
                </div>
                <div class="col-md-4"> 
                    <h5><a href="depot_print_tyre_pressure.php" target="_blank">Print Report</a></h5>
                    <h5>Click here <i class="fa-solid fa-circle-arrow-up fa-sm" style="color: #000000;"></i></h5>
            </div>
        </div>

        <!-- TODAY TYRE PRESSURE -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-dark">
                🚍 Yesterdays Pending Tyre Pressure Check
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sl No</th>
                            <th>Bus Number</th>
                            <th>Select Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; ?>
                        <?php while ($row = $yesterday_query->fetch_assoc()) { ?>
                            <tr class="busRow">
                                <td><?= $i++; ?></td>
                                <td><strong><?= $row['bus_number']; ?></strong></td>
                                <td style="width:180px;">
                                    <input type="date"
                                        class="form-control form-control-sm doneDate"
                                        value="<?= date('Y-m-d'); ?>">
                                    <input type="hidden"
                                        class="weekStart"
                                        value="<?= $row['week_start']; ?>">
                                </td>
                                <td>
                                    <button class="btn btn-success btn-sm markTyreDoneBtn"
                                        data-bus="<?= $row['bus_number']; ?>">
                                        Mark Done
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TODAY TYRE PRESSURE -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                🚍 Today Tyre Pressure Check
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sl No</th>
                            <th>Bus Number</th>
                            <th>Select Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; ?>
                        <?php while ($row = $today_query->fetch_assoc()) { ?>
                            <tr class="busRow">
                                <td><?= $i++; ?></td>
                                <td><strong><?= $row['bus_number']; ?></strong></td>
                                <td style="width:180px;">
                                    <input type="date"
                                        class="form-control form-control-sm doneDate"
                                        value="<?= date('Y-m-d'); ?>">
                                    <input type="hidden"
                                        class="weekStart"
                                        value="<?= $row['week_start']; ?>">
                                </td>
                                <td>
                                    <button class="btn btn-success btn-sm markTyreDoneBtn"
                                        data-bus="<?= $row['bus_number']; ?>">
                                        Mark Done
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        $(document).on('click', '.markTyreDoneBtn', function() {

            let row = $(this).closest('.busRow');
            let bus_number = $(this).data('bus');
            let selectedDate = row.find('.doneDate').val();
            let week_start = row.find('.weekStart').val();

            let today = new Date().toISOString().split('T')[0];
            let yesterdayObj = new Date();
            yesterdayObj.setDate(yesterdayObj.getDate() - 1);
            let yesterday = yesterdayObj.toISOString().split('T')[0];

            if (selectedDate !== today && selectedDate !== yesterday) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Date',
                    text: 'Only Today or Yesterday allowed.'
                });
                return;
            }

            Swal.fire({
                title: 'Confirm Maintenance?',
                html: "Bus: <b>" + bus_number + "</b><br>Date: " + selectedDate,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Mark Done'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: '../includes/backend_data.php',
                        type: 'POST',
                        data: {
                            action: 'mark_done_tyre_pressure',
                            bus_number: bus_number,
                            done_date: selectedDate,
                            week_start: week_start
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            Swal.fire({
                                title: 'Updating...',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Marked Done!'
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        }
                    });

                }

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