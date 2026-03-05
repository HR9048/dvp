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
        FROM weekly_tyre_pressure_schedule_backup
        WHERE division_id='$division_id'
        AND depot_id='$depot_id'
        ORDER BY week_start DESC
    ");

    /* ===== Fetch report data ===== */
    $query = $db->query("
    SELECT w.bus_number,
           w.check_day1,
           w.check_day2,

           MIN(d.done_date) as done_date1,
           MAX(d.done_date) as done_date2

    FROM weekly_tyre_pressure_schedule w
    LEFT JOIN weekly_tyre_pressure_done d
        ON w.bus_number = d.bus_number
        AND w.week_start = d.week_start

    WHERE w.division_id='$division_id'
    AND w.depot_id='$depot_id'
    AND w.week_start='$week_start'

    GROUP BY w.bus_number
    ORDER BY w.check_day1 ASC, w.bus_number ASC
");
?>

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

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

            <button class="btn btn-primary btn-sm" onclick="window.print()">
                🖨 Print
            </button>
        </div>

        <div class="container1">
            <div class="text-center mb-3">
                <h4>Weekly Tyre Pressure Check Report</h4>
                <strong>Division:</strong> <?= $_SESSION['DIVISION']; ?> |
                <strong>Depot:</strong> <?= $_SESSION['DEPOT']; ?><br>
                <strong>Week Starting:</strong> <?= date('d-m-Y', strtotime($week_start)); ?>
            </div>

            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Sl No</th>
                        <th>Bus Number</th>
                        <th>Check Day 1</th>
                        <th>Check Day 2</th>
                        <th>Status</th>
                        <th>Done Date 1</th>
                        <th>Done Date 2</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    while ($row = $query->fetch_assoc()) {

                        $done1 = $row['done_date1'];
                        $done2 = $row['done_date2'];

                        $count = 0;
                        if ($done1) $count++;
                        if ($done2 && $done2 != $done1) $count++;

                        if ($count == 2) {
                            $status = "<span class='text-success'>Completed</span>";
                        } elseif ($count == 1) {
                            $status = "<span class='text-warning'>Partially Done</span>";
                        } else {
                            $status = "<span class='text-danger'>Pending</span>";
                        }

                        $done1_display = $done1 ? date('d-m-Y', strtotime($done1)) : "-";
                        $done2_display = ($done2 && $done2 != $done1) ? date('d-m-Y', strtotime($done2)) : "-";
                    ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= $row['bus_number']; ?></td>
                            <td><?= dayName($row['check_day1']); ?></td>
                            <td><?= dayName($row['check_day2']); ?></td>
                            <td><?= $status; ?></td>
                            <td><?= $done1_display; ?></td>
                            <td><?= $done2_display; ?></td>
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