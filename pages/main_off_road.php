<?php
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

// Fetching latest details of vehicles that are off-road and belong to the user's division
$sql = "SELECT *, DATEDIFF(CURDATE(), off_road_date) AS days_off_road FROM off_road_data WHERE status = 'off_road' ORDER BY division,depot, off_road_location ASC";
$result = $db->query($sql);
?>
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <button class="btn btn-success" id="downloadExcel">Download Excel</button>
    <button class="btn btn-danger" id="downloadText">Download Text</button>
    <a href="main_offroad_pdf.php" class="btn btn-success">Download PDF</a>
</div>
<div class="container1">
    <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br><br>
    <h2 style="display: inline-block; width: 33%; text-align:left; padding-left: 100px;">CENTRAL OFFICE</h2>
    <h2 style="display: inline-block; width: 33%;text-align:center;">KALABURAGI</h2>
    <h2 style="display: inline-block; width: 33%; text-align:right;">
        <?php echo date('d-m-Y'); ?>
    </h2>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive" style="overflow-x: auto;">
                    <?php
                    // Perform SQL query
                    $sql = "SELECT 
            CASE 
                WHEN o.division = '1' THEN 1
                WHEN o.division = '2' THEN 2
                WHEN o.division = '3' THEN 3
                WHEN o.division = '4' THEN 4
                WHEN o.division = '5' THEN 5
                WHEN o.division = '6' THEN 6
                WHEN o.division = '7' THEN 7
                WHEN o.division = '8' THEN 8
                WHEN o.division = '9' THEN 9
                ELSE 10
            END AS division_order,
            l.division AS `DIVISION`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'DWS' AND o.parts_required = 'FCR/RTO' THEN o.bus_number END) AS `FCR/RTO`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'DWS' AND o.parts_required = 'FCR/HBR' THEN o.bus_number END) AS `FCR/HBR`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'DWS' AND o.parts_required = 'AMW' THEN o.bus_number END) AS `AMW`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'DWS' AND o.parts_required = 'Accident Repairs' THEN o.bus_number END) AS `ACCIDENT REPAIR`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'DWS' AND o.parts_required NOT IN ('FCR/RTO', 'FCR/HBR', 'AMW', 'Accident Repairs', 'Approval for Scrap') THEN o.bus_number END) AS `OTHERS`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'Depot' AND o.parts_required != 'Minor Work under Progress' THEN o.bus_number END) AS `DEPOT_COUNT`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'Depot' AND o.parts_required = 'Minor Work under Progress' THEN o.bus_number END) AS `DEPOT_WUP_COUNT`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'Authorized Dealer'  THEN o.bus_number END) AS `DEALER_COUNT`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'Police Station'  THEN o.bus_number END) AS `POLICE_COUNT`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'RWY' THEN o.bus_number END) AS `RWY`,
            COUNT(DISTINCT CASE WHEN o.off_road_location = 'DWS' THEN o.bus_number END) AS `DWS`,
            COUNT(DISTINCT CASE WHEN o.parts_required = 'Approval for Scrap' THEN o.bus_number END) AS `SCRAP PROPOSAL`,
            COUNT(DISTINCT CASE WHEN o.off_road_location != 'RWY' THEN o.bus_number END) AS `G. TOTAL`
        FROM 
            off_road_data o
        JOIN location l ON l.division_id = o.division
        WHERE 
            o.status = 'off_road' AND
            o.id IN (SELECT MAX(id) FROM off_road_data WHERE status = 'off_road' GROUP BY bus_number, division)
        GROUP BY 
            o.division
        ORDER BY 
            division_order";

                    $result = mysqli_query($db, $sql);

                    if (!$result) {
                        die("Query failed: " . mysqli_error($db));
                    }
                    // Initialize serial number
                    $serial_number = 0;
                    // Initialize variables to hold totals
                    $total_DEPOT_COUNT = 0;
                    $total_DWS = 0;
                    $total_FCR_RTO = 0;
                    $total_FCR_HBR = 0;
                    $total_AMW = 0;
                    $total_ACCIDENT_REPAIR = 0;
                    $total_OTHERS = 0;
                    $total_SCRAP_PROPOSAL = 0;
                    $total_RWY = 0;
                    $total_POLICE = 0;
                    $total_G_TOTAL = 0;
                    $total_DEPOT_WUP_COUNT = 0;
                    ?>

                    <h2 style="text-align:center;">Divisionwise Summary</h2>
                    <table>
                        <tr>
                            <th rowspan="2">sl.no</th>
                            <th rowspan="2">DIVISION</th>
                            <th colspan="2" style="text-align:center;">DEPOT</th>
                            <th colspan="7" style="text-align:center;">DWS</th>
                            <!-- Colspan set to 6 for 5 sub-columns + DWS Total -->
                            <th rowspan="2">At DEALER</th>
                            <th rowspan="2">At POLICE</th>
                            <th rowspan="2"><b>G. TOTAL</b></th> <!-- Added bold -->
                        </tr>
                        <tr>
                            <th>Off-Road</th>
                            <th>WUP</th>
                            <th>FCR</th>
                            <th>HBR</th>
                            <th>AMW</th>
                            <th>A/R</th>
                            <th>PROPOSAL</th>
                            <th>OTHERS</th>
                            <th>Total</th>
                        </tr>
                        <?php
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Increment serial number
                            $serial_number++;

                            echo "<tr>";
                            echo "<td>{$serial_number}</td>"; // Output serial number
                            echo "<td>{$row['DIVISION']}</td>";
                            echo "<td>{$row['DEPOT_COUNT']}</td>"; // Displaying the count of off-road instances for each depot
                            echo "<td>{$row['DEPOT_WUP_COUNT']}</td>"; // Displaying the count of off-road instances for each depot
                            echo "<td>{$row['FCR/RTO']}</td>";
                            echo "<td>{$row['FCR/HBR']}</td>";
                            echo "<td>{$row['AMW']}</td>";
                            echo "<td>{$row['ACCIDENT REPAIR']}</td>";
                            echo "<td>{$row['SCRAP PROPOSAL']}</td>";
                            echo "<td>{$row['OTHERS']}</td>";
                            echo "<td>{$row['DWS']}</td>"; // Displaying the total count of DWS instances
                            echo "<td>{$row['DEALER_COUNT']}</td>";
                            echo "<td>{$row['POLICE_COUNT']}</td>";
                            echo "<td><b>{$row['G. TOTAL']}</b></td>";
                            echo "</tr>";

                            // Accumulate totals
                            $total_DEPOT_COUNT += $row['DEPOT_COUNT'];
                            $total_DEPOT_WUP_COUNT += $row['DEPOT_WUP_COUNT'];
                            $total_DWS += $row['DWS'];
                            $total_FCR_RTO += $row['FCR/RTO'];
                            $total_FCR_HBR += $row['FCR/HBR'];
                            $total_AMW += $row['AMW'];
                            $total_ACCIDENT_REPAIR += $row['ACCIDENT REPAIR'];
                            $total_SCRAP_PROPOSAL += $row['SCRAP PROPOSAL'];
                            $total_OTHERS += $row['OTHERS'];
                            $total_RWY += $row['DEALER_COUNT'];
                            $total_POLICE += $row['POLICE_COUNT'];
                            $total_G_TOTAL += $row['G. TOTAL'];
                        }
                        ?>

                        <!-- Add Corporation row -->
                        <tr style="font-weight:bold;">
                            <td colspan="2">CORPORATION</td>
                            <td>
                                <?php echo $total_DEPOT_COUNT; ?>
                            </td>
                            <td>
                                <?php echo $total_DEPOT_WUP_COUNT; ?>
                            </td>
                            <td>
                                <?php echo $total_FCR_RTO; ?>
                            </td>
                            <td>
                                <?php echo $total_FCR_HBR; ?>
                            </td>
                            <td>
                                <?php echo $total_AMW; ?>
                            </td>
                            <td>
                                <?php echo $total_ACCIDENT_REPAIR; ?>
                            </td>
                            <td>
                                <?php echo $total_SCRAP_PROPOSAL; ?>
                            </td>
                            <td>
                                <?php echo $total_OTHERS; ?>
                            </td>
                            <td>
                                <?php echo $total_DWS; ?>
                            </td>
                            <td>
                                <?php echo $total_RWY; ?>
                            </td>
                            <td>
                                <?php echo $total_POLICE; ?>
                            </td>
                            <td>
                                <?php echo $total_G_TOTAL; ?>
                            </td>
                        </tr>
                    </table>




                    <?php
                    // Fetch off_road_data from the database based on session division and depot name
                    $division_abbreviations = array(
                        '1' => 'KLB1',
                        '2' => 'KLB2',
                        '3' => 'YDG',
                        '4' => 'BDR',
                        '5' => 'RCH',
                        '6' => 'KPL',
                        '7' => 'BLR',
                        '8' => 'HSP',
                        '9' => 'VJP'
                    );

                    $sql = "SELECT o1.*, 
       l.depot AS depot_name,
       br.emission_norms AS reg_emission_norms,
       br.wheel_base,
       DATEDIFF(CURDATE(), o1.off_road_date) AS days_off_road 
FROM off_road_data o1
JOIN location l ON o1.depot = l.depot_id
JOIN bus_registration br ON o1.bus_number = br.bus_number
WHERE o1.status = 'off_road' 
  AND o1.off_road_location NOT IN ('RWY') 
  AND NOT EXISTS (
      SELECT 1 
      FROM off_road_data o2 
      WHERE o1.bus_number = o2.bus_number 
        AND o2.off_road_location = 'RWY'
        AND o2.id > o1.id
  )
ORDER BY l.division_id, o1.off_road_location, l.depot_id ASC";

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

                    <br><br>
                    <h2 style="text-align:center;">DAILY VEHICLES OFF-ROAD POSITION</h2>
                    <table style="width: 100%; max-width: 100%;">
                        <thead>
                            <tr>
                                <th>Sl No</th>
                                <th>Divn (Sl No)</th>
                                <th>Division</th>
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
                            // Initialize serial number counters
                            $bus_serial_number = 1;
                            $division_serial_number = 1;
                            $current_division = null; // Track the current division
                            
                            // Loop through each bus number
                            foreach ($bus_numbers as $bus_number) {
                                // Collect all rows for the current bus number
                                $bus_rows = [];
                                while ($row = mysqli_fetch_assoc($result)) {
                                    if ($row['bus_number'] == $bus_number) {
                                        $bus_rows[] = $row;
                                    }
                                }
                                if (count($bus_rows) > 0) {
                                    // Extract data from the first row
                                    $first_row = $bus_rows[0];
                                    $depot_name = $first_row['depot_name'];
                                    $make = $first_row['make'];
                                    $emission_norms = ($first_row['reg_emission_norms'] == 'BS-3' && $first_row['wheel_base'] == '193 Midi') ? 'BS-3 Midi' : $first_row['reg_emission_norms'];

                                    // Collect and format off-road dates, locations, parts required, remarks, and DWS remarks
                                    $off_road_from_dates = array_map(function ($row) {
                                        $dateObj = new DateTime($row['off_road_date']);
                                        return $dateObj->format('d/m/Y');
                                    }, $bus_rows);
                                    $days_off_road = array_map(function ($row) {
                                        return $row['days_off_road'];
                                    }, $bus_rows);
                                    $off_road_locations = array_map(function ($row) {
                                        return $row['off_road_location'];
                                    }, $bus_rows);
                                    $parts_required = array_map(function ($row) {
                                        return $row['parts_required'];
                                    }, $bus_rows);
                                    $remarks = array_map(function ($row) {
                                        return $row['remarks'];
                                    }, $bus_rows);
                                    $dws_remarks = array_map(function ($row) {
                                        return $row['dws_remark'];
                                    }, $bus_rows);

                                    // Output the data in table rows
                                    echo "<tr>";
                                    echo "<td>$bus_serial_number</td>";
                                    if ($first_row['division'] != $current_division) {
                                        $current_division = $first_row['division'];
                                        $division_serial_number = 1; // Reset division serial number
                                    }
                                    echo "<td>$division_serial_number</td>";
                                    echo "<td>" . ($division_abbreviations[$first_row['division']] ?? $first_row['division']) . "</td>";
                                    echo "<td>$depot_name</td>";
                                    echo "<td>$bus_number</td>";
                                    echo "<td>$make</td>";
                                    echo "<td>$emission_norms</td>";
                                    echo "<td>" . implode(", ", $off_road_from_dates) . "</td>";
                                    echo "<td>" . implode(", ", $days_off_road) . "</td>";
                                    echo "<td>" . implode(", ", $off_road_locations) . "</td>";
                                    echo "<td>" . implode(", ", $parts_required) . "</td>";
                                    echo "<td>" . implode(", ", $remarks) . "</td>";
                                    echo "<td>" . implode(", ", $dws_remarks) . "</td>";
                                    echo "</tr>";

                                    // Increment the bus serial number and division serial number
                                    $bus_serial_number++;
                                    $division_serial_number++;
                                }
                                // Reset the result pointer to the beginning for the next bus number
                                mysqli_data_seek($result, 0);
                            }
                            ?>
                        </tbody>
                    </table>


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

                                // Collect data for combined cells
                                $received_dates = [];
                                $days_off_road = [];
                                $work_reasons = [];
                                $work_statuses = [];
                                $remarks = [];

                                foreach ($rows as $row) {
                                    $dateObj = new DateTime($row['received_date']);
                                    $received_dates[] = $dateObj->format('d/m/Y');
                                    $days_off_road[] = $row['days_off_road'];
                                    $work_reasons[] = $row['work_reason'];
                                    $work_statuses[] = $row['work_status'];
                                    $remarks[] = $row['remarks'];
                                }

                                // Output data for each bus number
                                echo "<tr>";
                                echo "<td>$serial_number</td>";
                                echo "<td>" . $rows[0]['division_name'] . "</td>";
                                echo "<td>" . $rows[0]['depot_name'] . "</td>";
                                echo "<td>" . $rows[0]['bus_number'] . "</td>";
                                echo "<td>" . $rows[0]['make'] . "</td>";
                                // Check the emission norms and wheelbase to determine if it should be "BS-3 Midi"
                                $emission_norms = ($rows[0]['reg_emission_norms'] == 'BS-3' && $rows[0]['wheel_base'] == '193 Midi') ? 'BS-3 Midi' : $rows[0]['reg_emission_norms'];
                                echo "<td>$emission_norms</td>";
                                echo "<td>" . implode("<br>", $received_dates) . "</td>";
                                echo "<td>" . implode("<br>", $days_off_road) . "</td>";
                                echo "<td>" . $rows[0]['work_reason'] . "</td>";
                                echo "<td>" . implode("<br>", $work_statuses) . "</td>";
                                echo "<td>" . implode("<br>", $remarks) . "</td>";
                                echo "</tr>";

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
                    <?php
                    // Fetching latest details of vehicles that are off-road and belong to the user's division
                    $sql = "SELECT 
        o.*, 
        l.division AS division_name, 
        l.depot AS depot_name,
        DATEDIFF(CURDATE(), o.off_road_date) AS days_off_road
    FROM off_road_data o
    JOIN location l ON o.depot = l.depot_id
    WHERE o.status = 'off_road'
        AND o.off_road_location = 'RWY'
        AND NOT EXISTS (
            SELECT 1
            FROM (
                SELECT r.bus_number, r.status, r.on_road_date
                FROM rwy_offroad r
                JOIN (
                    SELECT bus_number, MAX(ID) AS max_id
                    FROM rwy_offroad
                    GROUP BY bus_number
                ) latest ON r.bus_number = latest.bus_number AND r.ID = latest.max_id
                WHERE r.status = 'off_road'
                   OR (r.status = 'on_road' AND r.on_road_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY))
            ) latest_status
            WHERE latest_status.bus_number = o.bus_number
        )
    ORDER BY l.division_id, l.depot_id, o.off_road_date ASC";

                    $result = mysqli_query($db, $sql) or die(mysqli_error($db));

                    if (mysqli_num_rows($result) > 0) {
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
                        ?><br><br>
                        <h2>Vehicles not received by RWY</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Sl. No</th>
                                    <th>Division</th>
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
                                            echo "<td rowspan='" . count($rows) . "'>" . $row['emission_norms'] . "</td>";
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

                                    // Increment the serial number
                                    $serial_number++;
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                    }
                    ?>
                    <?php
                    // Define your SQL query
                    $sql = "SELECT
            l.division AS Division,
            COUNT(DISTINCT CASE WHEN o.make = 'Tata' AND o.emission_norms = 'BS-2' THEN o.bus_number END) AS Tata_BS2,
            COUNT(DISTINCT CASE WHEN o.make = 'Tata' AND o.emission_norms = 'BS-3' AND 
                NOT EXISTS (SELECT * FROM bus_registration WHERE bus_registration.bus_number = o.bus_number AND bus_registration.wheel_base = '193 midi') THEN o.bus_number END) AS Tata_BS3,
            COUNT(DISTINCT CASE WHEN o.make = 'Tata' AND o.emission_norms = 'BS-3' AND 
                EXISTS (SELECT * FROM bus_registration WHERE bus_registration.bus_number = o.bus_number AND bus_registration.wheel_base = '193 midi') THEN o.bus_number END) AS Tata_MIDI,
            COUNT(DISTINCT CASE WHEN o.make = 'Tata' AND o.emission_norms = 'BS-4' THEN o.bus_number END) AS Tata_BS4,
            COUNT(DISTINCT CASE WHEN o.make = 'Tata' AND o.emission_norms = 'BS-6' THEN o.bus_number END) AS Tata_BS6,
            COUNT(DISTINCT CASE WHEN o.make = 'Leyland' AND o.emission_norms = 'BS-2' THEN o.bus_number END) AS Leyland_BS2,
            COUNT(DISTINCT CASE WHEN o.make = 'Leyland' AND o.emission_norms = 'BS-3' THEN o.bus_number END) AS Leyland_BS3,
            COUNT(DISTINCT CASE WHEN o.make = 'Leyland' AND o.emission_norms = 'BS-4' THEN o.bus_number END) AS Leyland_BS4,
            COUNT(DISTINCT CASE WHEN o.make = 'Leyland' AND o.emission_norms = 'BS-6' THEN o.bus_number END) AS Leyland_BS6,
            COUNT(DISTINCT CASE WHEN o.make = 'Eicher' AND o.emission_norms = 'BS-2' THEN o.bus_number END) AS Eicher_BS2,
            COUNT(DISTINCT CASE WHEN o.make = 'Eicher' AND o.emission_norms = 'BS-3' THEN o.bus_number END) AS Eicher_BS3,
            COUNT(DISTINCT CASE WHEN o.make = 'Eicher' AND o.emission_norms = 'BS-4' THEN o.bus_number END) AS Eicher_BS4,
            COUNT(DISTINCT CASE WHEN o.make = 'Eicher' AND o.emission_norms = 'BS-6' THEN o.bus_number END) AS Eicher_BS6,
            COUNT(DISTINCT CASE WHEN o.make = 'Corona' AND o.emission_norms = 'BS-2' THEN o.bus_number END) AS Corona_BS2,
            COUNT(DISTINCT CASE WHEN o.make = 'Corona' AND o.emission_norms = 'BS-3' THEN o.bus_number END) AS Corona_BS3,
            COUNT(DISTINCT CASE WHEN o.make = 'Corona' AND o.emission_norms = 'BS-4' THEN o.bus_number END) AS Corona_BS4,
            COUNT(DISTINCT CASE WHEN o.make = 'Corona' AND o.emission_norms = 'BS-6' THEN o.bus_number END) AS Corona_BS6,
            COUNT(DISTINCT CASE WHEN o.make = 'Volvo' AND o.emission_norms = 'BS-6' THEN o.bus_number END) AS Volvo_BS6,
            COUNT(DISTINCT o.bus_number) AS TOTAL
        FROM off_road_data o
        LEFT JOIN bus_registration b ON o.bus_number = b.bus_number
        LEFT JOIN location l ON o.division = l.division_id
        WHERE o.status = 'off_road' AND o.parts_required = 'Engine'
        GROUP BY l.division_id";

                    // Execute the SQL query
                    $result = $db->query($sql);

                    // Check if the query was successful
                    if ($result) {
                        // Display the table
                        ?><br><br><br>
                        <!-- HTML table to display the data -->
                        <h2 style="text-align:center;">Engine related OFF-ROAD POSITION</h2>
                        <table>
                            <tr>
                                <th rowspan="2">DIVISION</th>
                                <th colspan="5" style="text-align:center;">Tata</th>
                                <th colspan="4" style="text-align:center;">Leyland</th>
                                <th colspan="4" style="text-align:center;">Eicher</th>
                                <th colspan="4" style="text-align:center;">Corona</th>
                                <th colspan="1" style="text-align:center;">Volvo</th>
                                <th rowspan="2">TOTAL</th>
                            </tr>
                            <tr>
                                <th>BS-2</th>
                                <th>BS-3</th>
                                <th>MIDI</th>
                                <th>BS-4</th>
                                <th>BS-6</th>
                                <th>BS-2</th>
                                <th>BS-3</th>
                                <th>BS-4</th>
                                <th>BS-6</th>
                                <th>BS-2</th>
                                <th>BS-3</th>
                                <th>BS-4</th>
                                <th>BS-6</th>
                                <th>BS-2</th>
                                <th>BS-3</th>
                                <th>BS-4</th>
                                <th>BS-6</th>
                                <th>BS-6</th>
                            </tr>
                            <?php
                            $corporation_totals = array_fill(0, 19, 0); // Initialize array for corporation totals
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['Division'] . "</td>";
                                echo "<td>" . $row['Tata_BS2'] . "</td>";
                                echo "<td>" . $row['Tata_BS3'] . "</td>";
                                echo "<td>" . $row['Tata_MIDI'] . "</td>";
                                echo "<td>" . $row['Tata_BS4'] . "</td>";
                                echo "<td>" . $row['Tata_BS6'] . "</td>";
                                echo "<td>" . $row['Leyland_BS2'] . "</td>";
                                echo "<td>" . $row['Leyland_BS3'] . "</td>";
                                echo "<td>" . $row['Leyland_BS4'] . "</td>";
                                echo "<td>" . $row['Leyland_BS6'] . "</td>";
                                echo "<td>" . $row['Eicher_BS2'] . "</td>";
                                echo "<td>" . $row['Eicher_BS3'] . "</td>";
                                echo "<td>" . $row['Eicher_BS4'] . "</td>";
                                echo "<td>" . $row['Eicher_BS6'] . "</td>";
                                echo "<td>" . $row['Corona_BS2'] . "</td>";
                                echo "<td>" . $row['Corona_BS3'] . "</td>";
                                echo "<td>" . $row['Corona_BS4'] . "</td>";
                                echo "<td>" . $row['Corona_BS6'] . "</td>";
                                echo "<td>" . $row['Volvo_BS6'] . "</td>";
                                echo "<td><b>" . $row['TOTAL'] . "</b></td>";
                                echo "</tr>";

                                // Add each division's totals to corporation totals
                                $corporation_totals[0] += $row['Tata_BS2'];
                                $corporation_totals[1] += $row['Tata_BS3'];
                                $corporation_totals[2] += $row['Tata_MIDI'];
                                $corporation_totals[3] += $row['Tata_BS4'];
                                $corporation_totals[4] += $row['Tata_BS6'];
                                $corporation_totals[5] += $row['Leyland_BS2'];
                                $corporation_totals[6] += $row['Leyland_BS3'];
                                $corporation_totals[7] += $row['Leyland_BS4'];
                                $corporation_totals[8] += $row['Leyland_BS6'];
                                $corporation_totals[9] += $row['Eicher_BS2'];
                                $corporation_totals[10] += $row['Eicher_BS3'];
                                $corporation_totals[11] += $row['Eicher_BS4'];
                                $corporation_totals[12] += $row['Eicher_BS6'];
                                $corporation_totals[13] += $row['Corona_BS2'];
                                $corporation_totals[14] += $row['Corona_BS3'];
                                $corporation_totals[15] += $row['Corona_BS4'];
                                $corporation_totals[16] += $row['Corona_BS6'];
                                $corporation_totals[17] += $row['Volvo_BS6'];
                                $corporation_totals[18] += $row['TOTAL'];
                            }
                            // Display the row for corporation totals
                            echo "<tr style='font-weight:bold;'>";
                            echo "<td>Corporation Totals</td>";
                            foreach ($corporation_totals as $total) {
                                echo "<td>" . $total . "</td>";
                            }
                            echo "</tr>";
                            ?>
                        </table>
                        <?php
                    } else {
                        echo "Error: " . $sql . "<br>" . $db->error;
                    }



                    $division_abbreviations = array(
                        '1' => 'KLB1',
                        '2' => 'KLB2',
                        '3' => 'YDG',
                        '4' => 'BDR',
                        '5' => 'RCH',
                        '6' => 'KPL',
                        '7' => 'BLR',
                        '8' => 'HSP',
                        '9' => 'VJP'
                    );

                    $sql = "SELECT o1.*, 
           l.depot AS depot_name,
           br.emission_norms AS reg_emission_norms,
           br.wheel_base,
           DATEDIFF(CURDATE(), o1.off_road_date) AS days_off_road 
    FROM off_road_data o1
    JOIN location l ON o1.depot = l.depot_id
    JOIN bus_registration br ON o1.bus_number = br.bus_number
    WHERE o1.status = 'off_road' and o1.parts_required='Engine'
      AND o1.off_road_location NOT IN ('RWY') 
      AND NOT EXISTS (
          SELECT 1 
          FROM off_road_data o2 
          WHERE o1.bus_number = o2.bus_number 
            AND o2.off_road_location = 'RWY'
            AND o2.id > o1.id
      )
    ORDER BY l.division_id, o1.off_road_location, l.depot_id ASC";

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
                    <br><br>

                    <h2 style="text-align:center;">ENGINE RELATED OFF-ROAD VEHICLES</h2>

                    <table style="width: 100%; max-width: 100%;">
                        <thead>
                            <tr>
                                <th>Sl No</th>
                                <th>Divn (Sl No)</th>
                                <th>Division</th>
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
                            // Initialize serial number counters
                            $bus_serial_number = 1;
                            $division_serial_number = 1;
                            $current_division = null; // Track the current division
                            
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
                                        // Output bus serial number only for the first row of the current bus number
                                        if ($first_row) {
                                            echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>$bus_serial_number</td>";
                                            // Output division serial number only if division has changed
                                            if ($row['division'] != $current_division) {
                                                $current_division = $row['division'];
                                                $division_serial_number = 1; // Reset division serial number
                                            }
                                            echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>$division_serial_number</td>";
                                            echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . ($division_abbreviations[$row['division']] ?? $row['division']) . "</td>";
                                            echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['depot_name'] . "</td>";
                                            echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['bus_number'] . "</td>";
                                            echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['make'] . "</td>";
                                            // Check the emission norms and wheelbase to determine if it should be "BS-3 Midi"
                                            if ($row['reg_emission_norms'] == 'BS-3' && $row['wheel_base'] == '193 Midi') {
                                                $emission_norms = 'BS-3 Midi';
                                            } else {
                                                $emission_norms = $row['reg_emission_norms'];
                                            }

                                            echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $emission_norms . "</td>";
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
                                        echo "<td>" . date('d/m/Y', strtotime($offRoadFromDate)) . "</td>";
                                        echo "<td>$daysOffRoad</td>";
                                        echo "<td>$offRoadLocation</td>";
                                        echo "<td>$partsRequired</td>";
                                        echo "<td>$remarks</td>";
                                        echo "<td>$dws_remarks</td>";
                                        echo "</tr>";
                                    }
                                }
                                if ($row_count > 0) {
                                    $bus_serial_number++;
                                    $division_serial_number++;
                                }
                                mysqli_data_seek($result, 0);
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <BR><BR></BR></BR>
    <h4 style="display: inline-block; width: 24%; text-align:center;">JTO</h4>
    <h4 style="display: inline-block; width: 24%; text-align:center;">DME</h4>
    <h4 style="display: inline-block; width: 24%;text-align:center;">DY CME</h4>
    <h4 style="display: inline-block; width: 24%; text-align:right; padding-right:200px">CME</h4>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
<script>
    document.getElementById('downloadExcel').addEventListener('click', function () {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
        var yyyy = today.getFullYear();
        today = dd + '-' + mm + '-' + yyyy;
        var htmlContent = document.querySelector('.container1').outerHTML;
        var workbook = XLSX.utils.table_to_book(document.querySelector('.container1'));
        XLSX.writeFile(workbook, today + 'Off-Road.xlsx');
    });
    document.getElementById('downloadText').addEventListener('click', function () {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
        var yyyy = today.getFullYear();
        today = dd + '-' + mm + '-' + yyyy;
        var textContent = document.querySelector('.container1').innerText;
        var blob = new Blob([textContent], { type: 'text/plain' });
        var link = document.createElement('a');
        link.download = today + '_Off-Road.txt'; // Set the file name
        link.href = window.URL.createObjectURL(blob);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
</script>
<?php include '../includes/footer.php'; ?>