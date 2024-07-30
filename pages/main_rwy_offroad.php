<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/sidebar.php';

$query = 'SELECT ID, t.TYPE FROM users u JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DEPOT') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Depot Page");
            window.location = "../includes/depot_verify.php";
        </script>
    <?php } elseif ($Aa == 'DIVISION') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
        </script>
    <?php } elseif ($Aa == 'RWY') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to RWY Page");
            window.location = "rwy.php";
        </script>
    <?php }
}
?>
<?php
$formatted_date = date('d/m/Y');

?>
<div class="container1">
    <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br><br>
    <h2 style="display: inline-block; width: 33%; text-align:left; padding-left: 100px;">CENTRAL OFFICE</h2>
    <h2 style="display: inline-block; width: 33%;text-align:center;">KALABURAGI</h2>
    <h2 style="display: inline-block; width: 33%; text-align:right;">
        <?php echo $formatted_date; ?>
    </h2>
    <br><br>
    <?php
                    // Fetching latest details of vehicles that are off-road and belong to the user's division
                    
                    $sql = "SELECT r.*,
               l.depot AS depot_name,
               br.emission_norms AS reg_emission_norms,
               br.wheel_base,
               CASE 
                   WHEN l.division = 'KALABURAGI-1' THEN 'KLB1'
                   WHEN l.division = 'KALABURAGI-2' THEN 'KLB2'
                   WHEN l.division = 'YADAGIRI' THEN 'YDG'
                   WHEN l.division = 'BIDAR' THEN 'BDR'
                   WHEN l.division = 'RAICHURU' THEN 'RCH'
                   WHEN l.division = 'KOPPALA' THEN 'KPL'
                   WHEN l.division = 'BALLARI' THEN 'BLR'
                   WHEN l.division = 'HOSAPETE' THEN 'HSP'
                   WHEN l.division = 'VIJAYAPURA' THEN 'VJP'
                   ELSE 'Unknown'
               END AS division_name,
               IFNULL(r.no_of_days, DATEDIFF(CURDATE(), r.received_date)) AS days_off_road
        FROM rwy_offroad r
        JOIN location l ON r.depot = l.depot_id
        JOIN bus_registration br ON r.bus_number = br.bus_number
        WHERE r.status = 'off_road'
        ORDER BY l.division_id, l.depot_id, r.received_date ASC";



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
                    $formatted_date = date('d/m/Y');

                    ?>
                    <br><br>
                    <h2 style="text-align:center;">RWY Off-Road</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Sl. No</th>
                                <th>Division</th>
                                <th>Depot</th>
                                <th>Bus Number</th>
                                <th>Make</th>
                                <th>Emission Norms</th>
                                <th>Received Date</th>
                                <th>Number of days</th>
                                <th>Work Reason</th>
                                <th>Work Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Initialize serial number counter
                            $serial_number = 1;

                            // Loop through each bus number
                            foreach ($bus_numbers as $bus_number) {
                                // Fetch all rows for the current bus number
                                $rows = [];
                                mysqli_data_seek($result, 0); // Reset the result pointer
                                while ($row = mysqli_fetch_assoc($result)) {
                                    if ($row['bus_number'] == $bus_number) {
                                        $rows[] = $row;
                                    }
                                }

                                // Output data for each row
                                foreach ($rows as $key => $row) {
                                    echo "<tr>";

                                    // Output serial number only for the first row of the current bus number
                                    if ($key === 0) {
                                        echo "<td rowspan='" . count($rows) . "'>$serial_number</td>";
                                        echo "<td rowspan='" . count($rows) . "'>" . $row['division_name'] . "</td>";
                                        echo "<td rowspan='" . count($rows) . "'>" . $row['depot_name'] . "</td>";
                                        echo "<td rowspan='" . count($rows) . "'>" . $row['bus_number'] . "</td>";
                                        echo "<td rowspan='" . count($rows) . "'>" . $row['make'] . "</td>";
                                        // Check the emission norms and wheelbase to determine if it should be "BS-3 Midi"
                    if ($row['reg_emission_norms'] == 'BS-3' && $row['wheel_base'] == '193 Midi') {
                        $emission_norms = 'BS-3 Midi';
                    } else {
                        $emission_norms = $row['reg_emission_norms'];
                    }
                    
                    echo "<td rowspan='" . count($rows) . "'>" . $emission_norms . "</td>";
                }
                                    // Extract data from the row
                                    $offRoadFromDate = $row['received_date'];
                                    $partsRequired = $row['work_reason'];
                                    $workstatus = $row['work_status'];
                                    $remarks = $row['remarks'];
                                    $daysOffRoad = $row['no_of_days'];
                                    if ($daysOffRoad === null) {
                                        $offRoadDate = new DateTime($offRoadFromDate);
                                        $today = new DateTime();
                                        $daysOffRoad = $today->diff($offRoadDate)->days;
                                    }

                                    // Output the data in table rows
                                    echo "<td>" . date('d/m/Y', strtotime($offRoadFromDate)) . "</td>";
                                    echo "<td>$daysOffRoad</td>";
                                    echo "<td>$partsRequired</td>";
                                    echo "<td>$workstatus</td>";
                                    echo "<td>$remarks</td>";
                                    echo "</tr>";
                                }

                                // Increment the serial number
                                $serial_number++;
                            }
                            ?>
                        </tbody>
                    </table>

                    <br><br>
                    <h2 style="text-align:center;">RWY Summary</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Division</th>
                                <th>In Yard</th>
                                <th>Dismantling</th>
                                <th>Proposal for Scrap</th>
                                <th>Structure</th>
                                <th>Paneling</th>
                                <th>Waiting for Spares</th>
                                <th>Pre Final</th>
                                <th>Final</th>
                                <th>Sent to Firm for Repair</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            // Fetch data from database
                            $sql = "SELECT 
            l.division AS Division,
            SUM(CASE WHEN t1.work_status = 'In Yard' THEN 1 ELSE 0 END) AS `In Yard`,
            SUM(CASE WHEN t1.work_status = 'Dismantling' THEN 1 ELSE 0 END) AS Dismantling,
            SUM(CASE WHEN t1.work_status = 'Proposal for scrap' THEN 1 ELSE 0 END) AS `Proposal for Scrap`,
            SUM(CASE WHEN t1.work_status = 'Structure' THEN 1 ELSE 0 END) AS Structure,
            SUM(CASE WHEN t1.work_status = 'Paneling' THEN 1 ELSE 0 END) AS Paneling,
            SUM(CASE WHEN t1.work_status = 'Waiting for spares from Division' THEN 1 ELSE 0 END) AS `Waiting for spares from Division`,
            SUM(CASE WHEN t1.work_status = 'Pre Final' THEN 1 ELSE 0 END) AS `Pre Final`,
            SUM(CASE WHEN t1.work_status = 'Final' THEN 1 ELSE 0 END) AS Final,
            SUM(CASE WHEN t1.work_status = 'Sent to firm for repair' THEN 1 ELSE 0 END) AS `Sent to firm for repair`
        FROM 
            rwy_offroad AS t1
        JOIN 
            (SELECT DISTINCT division, division_id FROM location) AS l ON t1.division = l.division_id
        WHERE 
            t1.status = 'off_road' AND
            t1.id IN (SELECT MAX(id) FROM rwy_offroad AS t2 WHERE t2.bus_number = t1.bus_number GROUP BY t2.bus_number)
        GROUP BY 
            l.division_id";



                            $result = $db->query($sql);

                            // Display data in table
                            $totalArray = ['In Yard' => 0, 'Dismantling' => 0, 'Proposal for Scrap' => 0, 'Structure' => 0, 'Paneling' => 0, 'Waiting for spares from Division' => 0, 'Pre Final' => 0, 'Final' => 0, 'Sent to firm for repair' => 0];
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['Division'] . "</td>";
                                    echo "<td>" . $row['In Yard'] . "</td>";
                                    echo "<td>" . $row['Dismantling'] . "</td>";
                                    echo "<td>" . $row['Proposal for Scrap'] . "</td>";
                                    echo "<td>" . $row['Structure'] . "</td>";
                                    echo "<td>" . $row['Paneling'] . "</td>";
                                    echo "<td>" . $row['Waiting for spares from Division'] . "</td>";
                                    echo "<td>" . $row['Pre Final'] . "</td>";
                                    echo "<td>" . $row['Final'] . "</td>";
                                    echo "<td>" . $row['Sent to firm for repair'] . "</td>";
                                    // Calculate total
                                    $total = $row['In Yard'] + $row['Dismantling'] + $row['Proposal for Scrap'] + $row['Structure'] + $row['Paneling'] + $row['Waiting for spares from Division'] + $row['Pre Final'] + $row['Final'] + $row['Sent to firm for repair'];
                                    echo "<td><b>$total</b></td>";
                                    echo "</tr>";
                                    // Add to total array
                                    $totalArray['In Yard'] += $row['In Yard'];
                                    $totalArray['Dismantling'] += $row['Dismantling'];
                                    $totalArray['Proposal for Scrap'] += $row['Proposal for Scrap'];
                                    $totalArray['Structure'] += $row['Structure'];
                                    $totalArray['Paneling'] += $row['Paneling'];
                                    $totalArray['Waiting for spares from Division'] += $row['Waiting for spares from Division'];
                                    $totalArray['Pre Final'] += $row['Pre Final'];
                                    $totalArray['Final'] += $row['Final'];
                                    $totalArray['Sent to firm for repair'] += $row['Sent to firm for repair'];
                                }
                            } else {
                                echo "<tr><td colspan='11'>No vehicles in RWY</td></tr>";
                            }

                            // Add Corporation row
                            echo "<tr>";
                            echo "<td><b>Corporation</b></td>";
                            echo "<td><b>{$totalArray['In Yard']}</b></td>";
                            echo "<td><b>{$totalArray['Dismantling']}</b></td>";
                            echo "<td><b>{$totalArray['Proposal for Scrap']}</b></td>";
                            echo "<td><b>{$totalArray['Structure']}</b></td>";
                            echo "<td><b>{$totalArray['Paneling']}</b></td>";
                            echo "<td><b>{$totalArray['Waiting for spares from Division']}</b></td>";
                            echo "<td><b>{$totalArray['Pre Final']}</b></td>";
                            echo "<td><b>{$totalArray['Final']}</b></td>";
                            echo "<td><b>{$totalArray['Sent to firm for repair']}</b></td>";
                            // Calculate total for corporation
                            $totalCorporation = $totalArray['In Yard'] + $totalArray['Dismantling'] + $totalArray['Proposal for Scrap'] + $totalArray['Structure'] + $totalArray['Paneling'] + $totalArray['Waiting for spares from Division'] + $totalArray['Pre Final'] + $totalArray['Final'] + $totalArray['Sent to firm for repair'];
                            echo "<td><b>$totalCorporation</b></td>";
                            echo "</tr>";

                            ?>
                        </tbody>
                    </table>

</div>
<!-- Print button -->
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
</div>


<?php $db->close();
include '../includes/footer.php'; ?>