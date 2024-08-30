<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/division_sidebar.php';
include_once 'session.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME') {
    // Allow access
    if (isset($_POST['from_date']) && isset($_POST['to_date'])) {
        $from_date = $_POST['from_date'];
        $to_date = $_POST['to_date'];

        $division = $_SESSION['DIVISION_ID'];

        $query = "SELECT ord.id, ord.bus_number, ord.make, ord.emission_norms, ord.off_road_date, ord.on_road_date, ord.off_road_location, ord.parts_required, ord.remarks, loc.depot AS depot_name 
    FROM off_road_data ord
    INNER JOIN location loc ON ord.depot = loc.depot_id
    WHERE ord.division = '$division' 
    AND ord.off_road_date BETWEEN '$from_date' AND '$to_date' order by depot_name";
        $result = mysqli_query($db, $query) or die(mysqli_error($db));

        $bus_number_rowspans = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $bus_number = $row['bus_number'];
            if (!isset($bus_number_rowspans[$bus_number])) {
                $bus_number_rowspans[$bus_number] = 0;
            }
            $bus_number_rowspans[$bus_number]++;
        }

        mysqli_data_seek($result, 0);
    } else {
        $bus_number_rowspans = [];
        $result = [];
    }
    ?>
    <form action="" method="POST" class="form-inline">
        <label for="from_date" class="mr-2">From Date:</label>
        <input type="date" id="from_date" name="from_date" max="<?php echo date('Y-m-d'); ?>" class="form-control mr-2">
        <label for="to_date" class="mr-2">To Date:</label>
        <input type="date" id="to_date" name="to_date" max="<?php echo date('Y-m-d'); ?>" class="form-control mr-2">
        <button class="btn btn-primary" type="submit">Show Data</button>
    </form><br>
    <div class="container1">
        <h4 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h4><br>
        <h6 style="text-align:left; padding: 2%; width: 24%;">
            <?php echo $_SESSION['DIVISION']; ?>
        </h6>
        <h6 style="text-align:center; width: 24%;">
            <?php echo $_SESSION['DEPOT']; ?>
        </h6>
        <h6 style="text-align:right;padding: 5%; width: 24%;">
            From:
            <?php echo $_POST['from_date']; ?>
        </h6>
        <h6 style="text-align:right;padding: 5%; width: 24%;">
            To:
            <?php echo $_POST['to_date']; ?>
        </h6>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Bus Number</th>
                        <th>Depot</th>
                        <th>Make</th>
                        <th>Emission Norms</th>
                        <th>Off Road From Date</th>
                        <th>On Road Date</th>
                        <th>Number of days offroad</th>
                        <th>Off Road Location</th>
                        <th>Parts Required</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($bus_number_rowspans as $bus_number => $rowspan_count) {
                        echo "<tr>";
                        $first_row = true;
                        while ($row = mysqli_fetch_assoc($result)) {
                            if ($row['bus_number'] == $bus_number) {
                                if ($first_row) {
                                    echo "<td rowspan='$rowspan_count'>" . $row['bus_number'] . "</td>";
                                    echo "<td rowspan='$rowspan_count'>" . $row['depot_name'] . "</td>";
                                    echo "<td rowspan='$rowspan_count'>" . $row['make'] . "</td>";
                                    echo "<td rowspan='$rowspan_count'>" . $row['emission_norms'] . "</td>";
                                    $first_row = false;
                                }
                                $offRoadFromDate = new DateTime($row['off_road_date']);
                                $onRoadDate = !empty($row['on_road_date']) ? new DateTime($row['on_road_date']) : null;
                                $offRoadLocation = $row['off_road_location'];
                                $partsRequired = $row['parts_required'];
                                $remarks = $row['remarks'];

                                if (!empty($onRoadDate) && $onRoadDate >= new DateTime($from_date) && $onRoadDate <= new DateTime($to_date)) {
                                    $daysOffRoad = $offRoadFromDate->diff($onRoadDate)->days;
                                } else {
                                    $daysOffRoad = $offRoadFromDate->diff(new DateTime($to_date))->days;
                                }

                                echo "<td>{$offRoadFromDate->format('d/m/y')}</td>";
                                echo "<td>";
                                if (!empty($onRoadDate) && $onRoadDate >= new DateTime($from_date) && $onRoadDate <= new DateTime($to_date)) {
                                    echo $onRoadDate->format('Y-m-d');
                                } else {
                                    echo "off road";
                                }
                                echo "</td>";

                                echo "<td>$daysOffRoad</td>";
                                echo "<td>$offRoadLocation</td>";
                                echo "<td>$partsRequired</td>";
                                echo "<td>$remarks</td>";
                                echo "</tr>";
                            }
                        }
                        mysqli_data_seek($result, 0);
                    }

                    ?>
                </tbody>
            </table>
        </div>
        <h6 style="text-align:left; padding: 10%;">JA</h6>
        <h6 style="text-align:center;">CM/AWS</h6>
        <h6 style="text-align:right;padding: 5%;">DM</h6>
    </div>

    <div class="text-center mt-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>