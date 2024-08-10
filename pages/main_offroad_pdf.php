<?php
require_once ('../includes/tcpdf/tcpdf.php');
require_once ('../includes/connection.php');

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

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('DVP Report off-road');
$pdf->SetSubject('DVP Report off-road');
$pdf->SetKeywords('DVP, Report, KKRTC, off-road');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetPageOrientation('L');
$pdf->SetMargins(10, 3, 10, 10); // Left, Top, Right, Bottom margins
$pdf->SetFooterMargin(0); // Ensure no footer margin
$pdf->SetAutoPageBreak(true, 0); // Disable auto page break and set margin to zero

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);
$html = ''; // Initialize $html variable

// Title and header
$html .= '<h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br><br><br>';
$html .= '<table style="width: 100%; margin-top: 50px;">';
$html .= '<tr>';
$html .= '<td style="text-align: left;"><h1><b>CENTRAL OFFICE</b></h1></td>';
$html .= '<td style="text-align: center;"><h1><b>KALABURAGI</b></h1></td>';
$html .= '<td style="text-align: right; "><h1><b>' . date('d/m/Y') . '</b></h1></td>';
$html .= '</tr>';
$html .= '</table><br><br>';

$html .= '<h2 style="text-align:center;">Divisionwise Summary</h2>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<tr>
            <th rowspan="2" style="width:30px;"><b>sl.no</b></th>
            <th rowspan="2" style="width:82px;"><b>DIVISION</b></th>
            <th colspan="2" style="text-align:center;"><b>DEPOT</b></th>
            <th colspan="7" style="width:410px;text-align:center;"><b>DWS</b></th>
            <th rowspan="2" style="width:40px;"><b>At DEALER</b></th>
            <th rowspan="2" style="width:40px;"><b>At POLICE</b></th>
            <th rowspan="2"><b>G. TOTAL</b></th>
          </tr>';
$html .= '<tr>
            <th><b>Off-Road</b></th>
            <th><b>WUP</b></th>
            <th style="width:55px;"><b>FCR</b></th>
            <th style="width:55px;"><b>HBR</b></th>
            <th style="width:55px;"><b>AMW</b></th>
            <th style="width:55px;"><b>A/R</b></th>
            <th style="width:80px;"><b>PROPOSAL</b></th>
            <th style="width:55px;"><b>OTHERS</b></th>
            <th style="width:55px;"><b>Total</b></th>
          </tr>';

while ($row = mysqli_fetch_assoc($result)) {
    // Increment serial number
    $serial_number++;

    $html .= "<tr>";
    $html .= "<td>{$serial_number}</td>";
    $html .= "<td>{$row['DIVISION']}</td>";
    $html .= "<td>{$row['DEPOT_COUNT']}</td>";
    $html .= "<td>{$row['DEPOT_WUP_COUNT']}</td>";
    $html .= "<td>{$row['FCR/RTO']}</td>";
    $html .= "<td>{$row['FCR/HBR']}</td>";
    $html .= "<td>{$row['AMW']}</td>";
    $html .= "<td>{$row['ACCIDENT REPAIR']}</td>";
    $html .= "<td>{$row['SCRAP PROPOSAL']}</td>";
    $html .= "<td>{$row['OTHERS']}</td>";
    $html .= "<td>{$row['DWS']}</td>";
    $html .= "<td>{$row['DEALER_COUNT']}</td>";
    $html .= "<td>{$row['POLICE_COUNT']}</td>";
    $html .= "<td><b>{$row['G. TOTAL']}</b></td>";
    $html .= "</tr>";

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

// Add totals row
$html .= '<tr style="font-weight:bold;">
            <td colspan="2">CORPORATION</td>
            <td>' . $total_DEPOT_COUNT . '</td>
            <td>' . $total_DEPOT_WUP_COUNT . '</td>
            <td>' . $total_FCR_RTO . '</td>
            <td>' . $total_FCR_HBR . '</td>
            <td>' . $total_AMW . '</td>
            <td>' . $total_ACCIDENT_REPAIR . '</td>
            <td>' . $total_SCRAP_PROPOSAL . '</td>
            <td>' . $total_OTHERS . '</td>
            <td>' . $total_DWS . '</td>
            <td>' . $total_RWY . '</td>
            <td>' . $total_POLICE . '</td>
            <td>' . $total_G_TOTAL . '</td>
          </tr>';
$html .= '</table>';

// Output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

//2nd table offroad

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

// Initialize variables for grouping data
$data = [];

// Group data by bus number
while ($row = mysqli_fetch_assoc($result)) {
    $bus_number = $row['bus_number'];
    if (!isset($data[$bus_number])) {
        $data[$bus_number] = [];
    }
    $data[$bus_number][] = $row;
}

// Set font
$pdf->SetFont('helvetica', '', 7);

// Initialize HTML variable
$html = '<h1 style="text-align:center;">DAILY VEHICLES OFF-ROAD POSITION</h1>';
$html .= '<table border="1" cellpadding="4" style="width: 100%; max-width: 100%;">';
$html .= '<thead>
            <tr>
                <th style="width:25px"><b>Sl No</b></th>
                <th style="width:20px"><b>D Sl no</b></th>
                <th style="width:33px"><b>Division</b></th>
                <th style="width:35px"><b>Depot</b></th>
                <th style="width:45px"><b>Bus Number</b></th>
                <th style="width:33px"><b>Make</b></th>
                <th style="width:39px"><b>Emission Norms</b></th>
                <th><b>Off Road From Date</b></th>
                <th style="width:41px"><b>No. of days off-road</b></th>
                <th style="width:38px"><b>Off Road Location</b></th>
                <th style="width:95px"><b>Parts Required</b></th>
                <th style="width:190px"><b>Remarks</b></th>
                <th style="width:150px"><b>DWS Remarks</b></th>
            </tr>
          </thead>
          <tbody>';

// Initialize serial number counters
$bus_serial_number = 1;
$division_serial_number = 1;
$current_division = null; // Track the current division

// Loop through each bus number
foreach ($data as $bus_number => $rows) {
    // Extract common data
    $division = $rows[0]['division'];
    $depot_name = $rows[0]['depot_name'];
    $make = $rows[0]['make'];
    $emission_norms = ($rows[0]['reg_emission_norms'] == 'BS-3' && $rows[0]['wheel_base'] == '193 Midi') ? 'BS-3 Midi' : $rows[0]['reg_emission_norms'];
    $received_dates = date('d/m/Y', strtotime($rows[0]['off_road_date']));
    $days_off_road = $rows[0]['days_off_road'];
    $off_road_locations = $rows[0]['off_road_location'];
    // Output data for each bus number
    $html .= "<tr>";
    $html .= "<td style=\"width: 25px;\">$bus_serial_number</td>";

    // Output division serial number only if division has changed
    if ($division != $current_division) {
        $current_division = $division;
        $division_serial_number = 1; // Reset division serial number
    }

    $html .= "<td style=\"width: 20px;\">$division_serial_number</td>";
    $html .= "<td style=\"width: 33px;\">" . ($division_abbreviations[$division] ?? $division) . "</td>";
    $html .= "<td style=\"width: 35px;\">$depot_name</td>";
    $html .= "<td style=\"width: 45px;\">$bus_number</td>";
    $html .= "<td style=\"width: 33px;\">$make</td>";
    $html .= "<td style=\"width: 39px;\">$emission_norms</td>";
    $html .= "<td>$received_dates</td>";
    $html .= "<td style=\"width: 41px;\">$days_off_road</td>";
    $html .= "<td style=\"width: 38px;\">$off_road_locations</td>";

    // Collect data for combined cells
    $parts_required = [];
    $remarks = [];
    $dws_remarks = [];

    foreach ($rows as $row) {
        $parts_required[] = $row['parts_required'];
        $remarks[] = $row['remarks'];
        $dws_remarks[] = $row['dws_remark'];
    }

    // Output the data in table rows
    $html .= "<td style=\"width: 95px;\">" . implode(" , ", $parts_required) . "</td>";
    $html .= "<td style=\"width: 190px;\">" . implode(" , ", $remarks) . "</td>";
    $html .= "<td style=\"width: 150px;\">" . implode(" , ", $dws_remarks) . "</td>";
    $html .= "</tr>";

    // Increment the bus serial number
    $bus_serial_number++;
    // Increment division serial number for each new division
    $division_serial_number++;
}

// Close the table
$html .= '</tbody></table>';

// Output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');



// third table 

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

// Initialize variables for grouping data
$data = [];

// Group data by bus number
while ($row = mysqli_fetch_assoc($result)) {
    $bus_number = $row['bus_number'];
    if (!isset($data[$bus_number])) {
        $data[$bus_number] = [];
    }
    $data[$bus_number][] = $row;
}

// Set font
$pdf->SetFont('helvetica', '', 9);

// Initialize HTML variable
$html = '<h2 style="text-align:center;">RWY Off-Road</h2>';
$html .= '<table border="1" cellpadding="4" cellspacing="0">';
$html .= '<thead>
            <tr>
                <th style="width:30px"><b>Sl. No</b></th>
                <th style="width:45px"><b>Division</b></th>
                <th style="width:60px"><b>Depot</b></th>
                <th style="width:80px"><b>Bus Number</b></th>
                <th style="width:60px"><b>Make</b></th>
                <th style="width:60px"><b>Emission Norms</b></th>
                <th style="width:70px"><b>Received Date</b></th>
                <th style="width:70px"><b>Number of days</b></th>
                <th style="width:80px"><b>Work Reason</b></th>
                <th style="width:100px"><b>Work Status</b></th>
                <th style="width:150px"><b>Remarks</b></th>
            </tr>
          </thead>
          <tbody>';

// Initialize serial number counter
$serial_number = 1;

// Loop through each bus number
foreach ($data as $bus_number => $rows) {
    // Extract common data
    $division = $rows[0]['division_name'];
    $depot_name = $rows[0]['depot_name'];
    $make = $rows[0]['make'];
    $emission_norms = ($rows[0]['reg_emission_norms'] == 'BS-3' && $rows[0]['wheel_base'] == '193 Midi') ? 'BS-3 Midi' : $rows[0]['reg_emission_norms'];
    $received_date = $rows[0]['received_date'];
    $received_dates = date('d/m/Y', strtotime($received_date));
    $today = new DateTime();

    // Calculate the number of days difference only for row 0
    $dateObj = new DateTime($received_date);
    $days_diff = $today->diff($dateObj)->days;

    // Output the data in table rows
    $html .= "<tr>";
    $html .= "<td style=\"width: 30px;\">$serial_number</td>";
    $html .= "<td style=\"width: 45px;\">$division</td>";
    $html .= "<td style=\"width: 60px;\">$depot_name</td>";
    $html .= "<td style=\"width: 80px;\">$bus_number</td>";
    $html .= "<td style=\"width: 60px;\">$make</td>";
    $html .= "<td style=\"width: 60px;\">$emission_norms</td>";
    $html .= "<td style=\"width: 70px;\">$received_dates</td>";
    $html .= "<td style=\"width: 70px;\">$days_diff</td>";
    $html .= "<td style=\"width: 80px;\">" . $rows[0]['work_reason'] . "</td>";
    $html .= "<td style=\"width: 100px;\">" . implode(" , ", array_column($rows, 'work_status')) . "</td>";
    $html .= "<td style=\"width: 150px;\">" . implode(" , ", array_column($rows, 'remarks')) . "</td>";
    $html .= "</tr>";

    // Increment the serial number
    $serial_number++;
}

$html .= '</tbody></table>';



// Output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');


// fourth table


// Set font
$pdf->SetFont('helvetica', '', 10);

// Initialize HTML variable
$html = '<h2 style="text-align:center;">RWY Summary</h2>';
$html .= '<table border="1" cellpadding="4" cellspacing="0">';
$html .= '<thead>
            <tr>
                <th style="width:85px"><b>Division</b></th>
                <th><b>In Yard</b></th>
                <th><b>Dismantling</b></th>
                <th><b>Proposal for Scrap</b></th>
                <th><b>Structure</b></th>
                <th><b>Paneling</b></th>
                <th><b>Waiting for Spares</b></th>
                <th><b>Pre Final</b></th>
                <th><b>Final</b></th>
                <th><b>Sent to Firm for Repair</b></th>
                <th style="width:40px"><b>Total</b></th>
            </tr>
          </thead>
          <tbody>';

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
$totalArray = [
    'In Yard' => 0,
    'Dismantling' => 0,
    'Proposal for Scrap' => 0,
    'Structure' => 0,
    'Paneling' => 0,
    'Waiting for spares from Division' => 0,
    'Pre Final' => 0,
    'Final' => 0,
    'Sent to firm for repair' => 0
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= "<tr>";
        $html .= "<td style=\"width: 85px;\">" . $row['Division'] . "</td>";
        $html .= "<td>" . $row['In Yard'] . "</td>";
        $html .= "<td>" . $row['Dismantling'] . "</td>";
        $html .= "<td>" . $row['Proposal for Scrap'] . "</td>";
        $html .= "<td>" . $row['Structure'] . "</td>";
        $html .= "<td>" . $row['Paneling'] . "</td>";
        $html .= "<td>" . $row['Waiting for spares from Division'] . "</td>";
        $html .= "<td>" . $row['Pre Final'] . "</td>";
        $html .= "<td>" . $row['Final'] . "</td>";
        $html .= "<td>" . $row['Sent to firm for repair'] . "</td>";
        // Calculate total
        $total = $row['In Yard'] + $row['Dismantling'] + $row['Proposal for Scrap'] + $row['Structure'] + $row['Paneling'] + $row['Waiting for spares from Division'] + $row['Pre Final'] + $row['Final'] + $row['Sent to firm for repair'];
        $html .= "<td style=\"width: 40px;\"><b>$total</b></td>";
        $html .= "</tr>";
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
    $html .= "<tr><td colspan='11'>No vehicles in RWY</td></tr>";
}

// Add Corporation row
$html .= "<tr>";
$html .= "<td><b>Corporation</b></td>";
$html .= "<td><b>{$totalArray['In Yard']}</b></td>";
$html .= "<td><b>{$totalArray['Dismantling']}</b></td>";
$html .= "<td><b>{$totalArray['Proposal for Scrap']}</b></td>";
$html .= "<td><b>{$totalArray['Structure']}</b></td>";
$html .= "<td><b>{$totalArray['Paneling']}</b></td>";
$html .= "<td><b>{$totalArray['Waiting for spares from Division']}</b></td>";
$html .= "<td><b>{$totalArray['Pre Final']}</b></td>";
$html .= "<td><b>{$totalArray['Final']}</b></td>";
$html .= "<td><b>{$totalArray['Sent to firm for repair']}</b></td>";
// Calculate total for corporation
$totalCorporation = $totalArray['In Yard'] + $totalArray['Dismantling'] + $totalArray['Proposal for Scrap'] + $totalArray['Structure'] + $totalArray['Paneling'] + $totalArray['Waiting for spares from Division'] + $totalArray['Pre Final'] + $totalArray['Final'] + $totalArray['Sent to firm for repair'];
$html .= "<td><b>$totalCorporation</b></td>";
$html .= "</tr>";

$html .= '</tbody></table>';

// Output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

//fifth table 

// Set font
$pdf->SetFont('helvetica', '', 10);

// Fetch data from database
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

// Check if there are rows returned
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

    // Start HTML content
    $html = '<h2 style="text-align:center;">Vehicles not received by RWY</h2>';
    $html .= '<table border="1" cellpadding="4" cellspacing="0">';
    $html .= '<thead>
                <tr>
                    <th style="width:25px"><b>Sl. No</b></th>
                    <th><b>Division</b></th>
                    <th style="width:50px"><b>Depot</b></th>
                    <th><b>Bus Number</b></th>
                    <th style="width:50px"><b>Make</b></th>
                    <th style="width:50px"><b>Emission Norms</b></th>
                    <th><b>Off Road From Date</b></th>
                    <th><b>Number of days off-road</b></th>
                    <th><b>Off Road Location</b></th>
                    <th><b>Parts Required</b></th>
                    <th style="width:100px"><b>Remarks</b></th>
                    <th style="width:100px"><b>DWS Remarks</b></th>
                </tr>
              </thead>
              <tbody>';

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
            $html .= "<tr>";

            // Output serial number only for the first row of the current bus number
            if ($key === 0) {
                $html .= "<td rowspan=\"" . count($rows) . "\" style=\"width: 25px;\">$serial_number</td>";
                $html .= "<td rowspan=\"" . count($rows) . "\">" . $row['division_name'] . "</td>";
                $html .= "<td rowspan=\"" . count($rows) . "\" style=\"width: 50px;\">" . $row['depot_name'] . "</td>";
                $html .= "<td rowspan=\"" . count($rows) . "\">" . $row['bus_number'] . "</td>";
                $html .= "<td rowspan=\"" . count($rows) . "\" style=\"width: 50px;\">" . $row['make'] . "</td>";
                $html .= "<td rowspan=\"" . count($rows) . "\" style=\"width: 50px;\">" . $row['emission_norms'] . "</td>";
            }

            // Extract data from the row
            $offRoadFromDate = date('d/m/Y', strtotime($row['off_road_date']));
            $daysOffRoad = $row['days_off_road'];
            $offRoadLocation = $row['off_road_location'];
            $partsRequired = $row['parts_required'];
            $remarks = $row['remarks'];
            $dws_remarks = $row['dws_remark'];

            // Output the data in table rows
            $html .= "<td>$offRoadFromDate</td>";
            $html .= "<td>$daysOffRoad</td>";
            $html .= "<td>$offRoadLocation</td>";
            $html .= "<td>$partsRequired</td>";
            $html .= "<td style=\"width: 100px;\">$remarks</td>";
            $html .= "<td style=\"width: 100px;\">$dws_remarks</td>";
            $html .= "</tr>";
        }

        // Increment the serial number
        $serial_number++;
    }

    // End HTML content
    $html .= '</tbody></table>';

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');
}

//sixth table

// Set font
$pdf->SetFont('helvetica', '', 10);

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
    // Start HTML content
    $html = '<h2 style="text-align:center;">Engine related OFF-ROAD POSITION</h2>';
    $html .= '<table border="1" cellpadding="4" cellspacing="0">';
    $html .= '<tr>
                <th rowspan="2" style="width:110px;"><b>DIVISION</b></th>
                <th colspan="5" style="text-align:center;width:160px;"><b>Tata</b></th>
                <th colspan="4" style="text-align:center;width:135px;"><b>Leyland</b></th>
                <th colspan="4" style="text-align:center;width:135px;"><b>Eicher</b></th>
                <th colspan="4" style="text-align:center;width:135px;"><b>Corona</b></th>
                <th colspan="1" style="text-align:center;width:40px;"><b>Volvo</b></th>
                <th rowspan="2" style="width:50px"><b>TOTAL</b></th>
            </tr>
            <tr>
                <th><b>BS-2</b></th>
                <th><b>BS-3</b></th>
                <th><b>MIDI</b></th>
                <th><b>BS-4</b></th>
                <th><b>BS-6</b></th>
                <th><b>BS-2</b></th>
                <th><b>BS-3</b></th>
                <th><b>BS-4</b></th>
                <th><b>BS-6</b></th>
                <th><b>BS-2</b></th>
                <th><b>BS-3</b></th>
                <th><b>BS-4</b></th>
                <th><b>BS-6</b></th>
                <th><b>BS-2</b></th>
                <th><b>BS-3</b></th>
                <th><b>BS-4</b></th>
                <th><b>BS-6</b></th>
                <th><b>BS-6</b></th>
            </tr>';

    $corporation_totals = array_fill(0, 19, 0); // Initialize array for corporation totals

    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        $html .= '<td>' . $row['Division'] . '</td>';
        $html .= '<td>' . $row['Tata_BS2'] . '</td>';
        $html .= '<td>' . $row['Tata_BS3'] . '</td>';
        $html .= '<td>' . $row['Tata_MIDI'] . '</td>';
        $html .= '<td>' . $row['Tata_BS4'] . '</td>';
        $html .= '<td>' . $row['Tata_BS6'] . '</td>';
        $html .= '<td>' . $row['Leyland_BS2'] . '</td>';
        $html .= '<td>' . $row['Leyland_BS3'] . '</td>';
        $html .= '<td>' . $row['Leyland_BS4'] . '</td>';
        $html .= '<td>' . $row['Leyland_BS6'] . '</td>';
        $html .= '<td>' . $row['Eicher_BS2'] . '</td>';
        $html .= '<td>' . $row['Eicher_BS3'] . '</td>';
        $html .= '<td>' . $row['Eicher_BS4'] . '</td>';
        $html .= '<td>' . $row['Eicher_BS6'] . '</td>';
        $html .= '<td>' . $row['Corona_BS2'] . '</td>';
        $html .= '<td>' . $row['Corona_BS3'] . '</td>';
        $html .= '<td>' . $row['Corona_BS4'] . '</td>';
        $html .= '<td>' . $row['Corona_BS6'] . '</td>';
        $html .= '<td>' . $row['Volvo_BS6'] . '</td>';
        $html .= '<td><b>' . $row['TOTAL'] . '</b></td>';
        $html .= '</tr>';

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
    $html .= '<tr style="font-weight:bold;">';
    $html .= '<td>Corporation Totals</td>';
    foreach ($corporation_totals as $total) {
        $html .= '<td>' . $total . '</td>';
    }
    $html .= '</tr>';

    // End HTML content
    $html .= '</table>';

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

}

//seventh table

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
$data = [];
$bus_number_rowspans_count = [];

// Group data by bus number and calculate rowspan counts
while ($row = mysqli_fetch_assoc($result)) {
    $bus_number = $row['bus_number'];
    if (!isset($data[$bus_number])) {
        $data[$bus_number] = [];
    }
    $data[$bus_number][] = $row;
    if (!isset($bus_number_rowspans_count[$bus_number])) {
        $bus_number_rowspans_count[$bus_number] = 0;
    }
    $bus_number_rowspans_count[$bus_number]++;
}



// Set font
$pdf->SetFont('helvetica', '', 7);

// Initialize HTML variable
$html = '<h1 style="text-align:center;">ENGINE RELATED OFF-ROAD VEHICLES</h1>';
$html .= '<table border="1" cellpadding="4" style="width: 100%; max-width: 100%;">';
$html .= '<thead>
            <tr>
                <th style="width:25px"><b>Sl No</b></th>
                <th style="width:20px"><b>D Sl no</b></th>
                <th style="width:33px"><b>Division</b></th>
                <th style="width:35px"><b>Depot</b></th>
                <th style="width:45px"><b>Bus Number</b></th>
                <th style="width:33px"><b>Make</b></th>
                <th style="width:39px"><b>Emission Norms</b></th>
                <th><b>Off Road From Date</b></th>
                <th style="width:41px"><b>No. of days off-road</b></th>
                <th style="width:35px"><b>Off Road Location</b></th>
                <th style="width:75px"><b>Parts Required</b></th>
                <th style="width:190px"><b>Remarks</b></th>
                <th style="width:150px"><b>DWS Remarks</b></th>
            </tr>
          </thead>
          <tbody>';

// Initialize serial number counters
$bus_serial_number = 1;
$division_serial_number = 1;
$current_division = null; // Track the current division

// Loop through each bus number
foreach ($data as $bus_number => $rows) {
    // Flag to indicate if it's the first row for the current bus number
    $first_row = true;

    // Loop through each row of the result set for the current bus number
    foreach ($rows as $row) {
        $html .= "<tr>";
        if ($first_row) {
            $html .= "<td rowspan=\"" . $bus_number_rowspans_count[$bus_number] . "\" style=\"width: 25px;\">$bus_serial_number</td>";
            // Output division serial number only if division has changed
            if ($row['division'] != $current_division) {
                $current_division = $row['division'];
                $division_serial_number = 1; // Reset division serial number
            }
            $html .= "<td rowspan=\"" . $bus_number_rowspans_count[$bus_number] . "\" style=\"width: 20px;\">$division_serial_number</td>";
            $html .= "<td rowspan=\"" . $bus_number_rowspans_count[$bus_number] . "\" style=\"width: 33px;\">" . ($division_abbreviations[$row['division']] ?? $row['division']) . "</td>";
            $html .= "<td rowspan=\"" . $bus_number_rowspans_count[$bus_number] . "\" style=\"width: 35px;\">" . $row['depot_name'] . "</td>";
            $html .= "<td rowspan=\"" . $bus_number_rowspans_count[$bus_number] . "\" style=\"width: 45px;\">" . $row['bus_number'] . "</td>";
            $html .= "<td rowspan=\"" . $bus_number_rowspans_count[$bus_number] . "\" style=\"width: 33px;\">" . $row['make'] . "</td>";

            if ($row['reg_emission_norms'] == 'BS-3' && $row['wheel_base'] == '193 Midi') {
                $emission_norms = 'BS-3 Midi';
            } else {
                $emission_norms = $row['reg_emission_norms'];
            }

            $html .= "<td rowspan=\"" . $bus_number_rowspans_count[$bus_number] . "\" style=\"width: 39px;\">$emission_norms</td>";
            $first_row = false;
        }
        // Extract data from the row
        $offRoadFromDate = date('d-m-Y', strtotime($row['off_road_date']));
        $offRoadLocation = $row['off_road_location'];
        $partsRequired = $row['parts_required'];
        $remarks = $row['remarks'];
        $dws_remarks = $row['dws_remark'];

        // Calculate the number of days off-road
        $offRoadDate = new DateTime($offRoadFromDate);
        $today = new DateTime();
        $daysOffRoad = $today->diff($offRoadDate)->days;

        // Output the data in table rows
        $html .= "<td>$offRoadFromDate</td>";
        $html .= "<td style=\"width: 42px;\">$daysOffRoad</td>";
        $html .= "<td style=\"width: 34px;\">$offRoadLocation</td>";
        $html .= "<td style=\"width: 75px;\">$partsRequired</td>";
        $html .= "<td style=\"width: 190px;\">$remarks</td>";
        $html .= "<td style=\"width: 150px;\">$dws_remarks</td>";
        $html .= "</tr>";
    }

    // Increment the bus serial number only if there were rows for the current bus number
    $bus_serial_number++;
    // Increment division serial number for each new division
    $division_serial_number++;
}

// Close the table
$html .= '</tbody></table>';


// Output the HTML content

// Set font
$pdf->SetFont('helvetica', '', 7);


// Add the final table at the end of the PDF
$html .= '<br><br><br><br><br><br>';
$html .= '<table style="width: 90%;">';
$html .= '<tr>';
$html .= '<td style="text-align: left;"><h1><b>JTO</b></h1></td>';
$html .= '<td style="text-align: center;"><h1><b>DME</b></h1></td>';
$html .= '<td style="text-align: center;"><h1><b>Dy-CME</b></h1></td>';
$html .= '<td style="text-align: right;"><h1><b>CME</b></h1></td>';
$html .= '</tr>';
$html .= '</table>';

// Output the final table to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Generate formatted date for file name
$formattedFileName = date('d_m_Y');
$fileName = $formattedFileName . '_offorad_position.pdf';

// Close and output PDF document
$pdf->Output($fileName, 'D');
?>