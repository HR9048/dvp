<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page'); window.location='logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM')) {

    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id    = $_SESSION['DEPOT_ID'];

    if (isset($_POST['week_start']) && !empty($_POST['week_start'])) {
        $week_start = $_POST['week_start'];
    } else {
        $week_start = date('Y-m-d', strtotime('monday this week'));
    }


    function dayName($day)
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        ];
        return $days[$day] ?? '';
    }

    /* ===== Fetch available weeks ===== */
    $week_list = $db->query("
        SELECT DISTINCT week_start 
        FROM weekly_maintenance_schedule_backup
        WHERE division_id='$division_id'
        AND depot_id='$depot_id'
        ORDER BY week_start DESC
    ");

    /* ===== Fetch report data ===== */
    $query = $db->query("
        SELECT w.bus_number,
               w.primary_day,
               w.backup_day,
               d.done_date
        FROM weekly_maintenance_schedule w
        LEFT JOIN weekly_maintenance_done d
            ON w.bus_number = d.bus_number
            AND w.week_start = d.week_start
        WHERE w.division_id='$division_id'
        AND w.depot_id='$depot_id'
        AND w.week_start='$week_start'
        ORDER BY w.primary_day ASC, w.bus_number ASC
    ");
?>

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-3">


            <div>
                <form method="POST" id="weekForm" class="d-flex gap-2">

                    <select name="week_start"
                        id="week_start"
                        class="form-select form-select-sm"
                        style="width:250px;">
                        <?php while ($week = $week_list->fetch_assoc()) { ?>
                            <option value="<?= $week['week_start']; ?>"
                                <?= ($week['week_start'] == $week_start) ? 'selected' : ''; ?>>
                                Week Starting: <?= date('d-m-Y', strtotime($week['week_start'])); ?>
                            </option>
                        <?php } ?>
                    </select>


                </form>

            </div>

            <button class="btn btn-primary btn-sm" onclick="window.print()">
                🖨 Print
            </button>
        </div>
        <div class="container1">
            <div class="text-center mb-3">
                <h4>Weekly Maintenance Schedule Report</h4>
                <strong>Division:</strong> <?= $_SESSION['DIVISION']; ?> |
                <strong>Depot:</strong> <?= $_SESSION['DEPOT']; ?><br>
                <strong>Week Starting:</strong> <?= date('d-m-Y', strtotime($week_start)); ?>
            </div>

            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Sl No</th>
                        <th>Bus Number</th>
                        <th>Primary Day</th>
                        <th>Backup Day</th>
                        <th>Status</th>
                        <th>Done Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    while ($row = $query->fetch_assoc()) {

                        $status = $row['done_date'] ? "Done" : "Pending";
                        $done_date = $row['done_date'] ? date('d-m-Y', strtotime($row['done_date'])) : "-";
                    ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= $row['bus_number']; ?></td>
                            <td><?= dayName($row['primary_day']); ?></td>
                            <td><?= dayName($row['backup_day']); ?></td>
                            <td><?= $status; ?></td>
                            <td><?= $done_date; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            $('#week_start').select2({
                placeholder: "Select Week",
                allowClear: false,
                width: 'resolve'
            });

            $('#week_start').on('change', function() {
                $('#weekForm').submit();
            });

        });
    </script>

<?php

} else {
    echo "<script>alert('Access Denied'); window.location='processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>