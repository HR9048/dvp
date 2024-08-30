<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME') {
    // Allow access
    ?>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .container,
            .container * {
                visibility: visible;
            }

            .container {
                width: 95%;
                text-align: right;
                position: absolute;
                top: 0;
                left: 0;
            }

        }
    </style>
    <?php
    // Fetch off_road_data from the database based on session division and depot name
    $division = $_SESSION['DIVISION_ID'];


    // Assuming you have stored the division in the session
    $session_division = $_SESSION['DIVISION_ID'];

    // Fetching latest details of vehicles that are off-road and belong to the user's division
    $sql = "SELECT ord.*, 
               loc.division AS depot_division, 
               loc.depot AS depot_name,
               DATEDIFF(CURDATE(), ord.off_road_date) AS days_off_road 
        FROM off_road_data ord
        INNER JOIN location loc ON ord.depot = loc.depot_id
        WHERE ord.status = 'off_road' 
        AND ord.division = '$session_division' 
        ORDER BY loc.depot_id,off_road_location ASC";
    $result = mysqli_query($db, $sql) or die(mysqli_error($db));

    // Initialize variables for rowspan logic
    $bus_numbers = [];
    $bus_number_rowspans_count = [];

    // Group data by bus number
    while ($row = mysqli_fetch_assoc($result)) {
        $bus_number = $row['bus_number'];
        if (!in_array($bus_number, $bus_numbers)) {
            $bus_numbers[] = $bus_number;
        }
        if (!isset($bus_number_rowspans_count[$bus_number])) {
            $bus_number_rowspans_count[$bus_number] = 0;
        }
        $bus_number_rowspans_count[$bus_number]++;
    }
    mysqli_data_seek($result, 0); // Reset the result pointer to the beginning
    ?>
    <div class="container1">
        <h4 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h4>
        <div style="display: flex; justify-content: space-between;">
            <h6 style="text-align:left; padding: 7%; margin: 0;"> DIVISION:
                <?php echo $_SESSION['DIVISION']; ?>
            </h6>
            <h6 style="text-align:center; padding: 7%; margin: 0;">Offroad Data</h6>
            <h6 style="text-align:right; padding: 7%; margin: 0;">Date:
                <?php echo date('Y-m-d'); ?>
            </h6>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sl. No</th>
                                    <th>Depot</th>
                                    <th>Bus Number</th>
                                    <th>Make</th>
                                    <th>Emission Norms</th>
                                    <th>Off Road From Date</th>
                                    <th>Number of days off-road</th>
                                    <th>Off Road Location</th>
                                    <th>Parts Required</th>
                                    <th>Remarks</th>
                                    <th>DWS Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Initialize serial number counter
                                $serial_number = 1;

                                // Loop through each bus number
                                foreach ($bus_numbers as $bus_number) {
                                    // Flag to indicate if it's the first row for the current bus number
                                    $first_row = true;
                                    // Count the number of rows for the current bus number
                                    $row_count = 0;
                                    // Loop through each row of the result set for the current bus number
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        if ($row['bus_number'] == $bus_number) {
                                            // Increment row count for the current bus number
                                            $row_count++;
                                            // Output data in table rows
                                            echo "<tr>";
                                            // Output serial number only for the first row of the current bus number
                                            if ($first_row) {
                                                echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>$serial_number</td>";
                                                echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['depot_name'] . "</td>";
                                                echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['bus_number'] . "</td>";
                                                echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['make'] . "</td>";
                                                echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['emission_norms'] . "</td>";
                                                $first_row = false;
                                            }
                                            // Extract data from the row
                                            $offRoadFromDate = $row['off_road_date'];
                                            $offRoadLocation = $row['off_road_location'];
                                            $partsRequired = $row['parts_required'];
                                            $remarks = $row['remarks'];
                                            $dws_remarks = $row['dws_remark'];

                                            // Calculate the number of days off-road
                                            $offRoadDate = new DateTime($offRoadFromDate);
                                            $today = new DateTime();
                                            $daysOffRoad = $today->diff($offRoadDate)->days;

                                            // Output the data in table rows
                                            echo "<td>" . date('d/m/y', strtotime($offRoadFromDate)) . "</td>";
                                            echo "<td>$daysOffRoad</td>";
                                            echo "<td>$offRoadLocation</td>";
                                            echo "<td>$partsRequired</td>";
                                            echo "<td>$remarks</td>";
                                            echo "<td>$dws_remarks</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    // Increment the serial number only if there were rows for the current bus number
                                    if ($row_count > 0) {
                                        $serial_number++;
                                    }
                                    // Reset the result pointer to the beginning for the next bus number
                                    mysqli_data_seek($result, 0);
                                }
                                ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <h6 style="text-align:left; padding: 10%; margin: 0;">JA</h6>
            <h6 style="text-align:center; padding: 10%; margin: 0;">DWS</h6>
            <h6 style="text-align:right; padding: 10%; margin: 0;">DME</h6>
        </div>
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