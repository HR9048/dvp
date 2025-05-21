<?php
require_once('../includes/tcpdf/tcpdf.php'); // Adjust path as needed
include '../includes/connection.php';
include 'session.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    die('Session expired');
}

$depot_id = $_SESSION['DEPOT_ID'];
$division_id = $_SESSION['DIVISION_ID'];
$type = $_SESSION['TYPE'];

if ($type == 'DEPOT') {
    $condition = "bi.depot_id = '$depot_id' AND bi.division_id = '$division_id'";
} else if ($type == 'DIVISION') {
    $condition = "bi.division_id = '$division_id'";
} else if ($type == 'RWY') {
    $condition = "bi.division_id = '$division_id' AND bi.depot_id = '$depot_id'";
} else {
    $condition = "1=1";
}

// Query
$query = "SELECT bi.*, 
    CASE WHEN bi.scraped = 0 THEN br.make ELSE bs.make END AS make,
               CASE WHEN bi.scraped = 0 THEN br.doc ELSE bs.doc END AS doc,
               CASE WHEN bi.scraped = 0 THEN br.emission_norms ELSE bs.emission_norms END AS emission_norms,
               CASE WHEN bi.scraped = 0 THEN br.chassis_number ELSE bs.chassis_number END AS chassis_number,
                CASE WHEN bi.scraped = 0 THEN br.bus_sub_category ELSE bs.bus_sub_category END AS bus_sub_category,
                CASE WHEN bi.scraped = 0 THEN br.bus_body_builder ELSE bs.bus_body_builder END AS bus_body_builder,
                CASE WHEN bi.scraped = 0 THEN br.seating_capacity ELSE bs.seating_capacity END AS seating_capacity,
                CASE WHEN bi.scraped = 0 THEN br.wheel_base ELSE bs.wheel_base END AS wheel_base,
    em.*, 
    fm.*, 
    gm.*, 
    sm.*,
    am.*, 
    ram.*, 
    b1.battery_card_number as b1_battery_card_number, b1.battery_number as b1_battery_number, b1.battery_make as b1_battery_make, b1.progressive_km as b1_progressive_km,
    b2.battery_card_number as b2_battery_card_number, b2.battery_number as b2_battery_number, b2.battery_make as b2_battery_make, b2.progressive_km as b2_progressive_km,
    l.division,l.depot,
    em.progressive_km as engine_progressive_km,
    et.type as engine_type,
    fm.progressive_km as fip_hpp_progressive_km,
    ft.type as fip_hpp_type,
    ft.model as fiphpp_model,
    gm.progressive_km as gear_box_progressive_km,
    gt.type as gear_box_type,
    sm.progressive_km as starter_progressive_km,
    am.progressive_km as alternator_progressive_km,
    ram.progressive_km as rear_axle_progressive_km,
    bct.bus_type
FROM bus_inventory bi
LEFT JOIN bus_registration br ON bi.bus_number = br.bus_number
LEFT JOIN bus_scrap_data bs ON bi.bus_number = bs.bus_number
LEFT JOIN engine_master em ON bi.engine_id = em.id
LEFT JOIN fip_hpp_master fm ON bi.fiphpp_id = fm.id
LEFT JOIN gearbox_master gm ON bi.gearbox_id = gm.id
LEFT JOIN starter_master sm ON bi.starter_id = sm.id
LEFT JOIN alternator_master am ON bi.alternator_id = am.id
LEFT JOIN rear_axle_master ram ON bi.rear_axel_id = ram.id
LEFT JOIN battery_master b1 ON bi.battery_1_id = b1.id
LEFT JOIN battery_master b2 ON bi.battery_2_id = b2.id
LEFT JOIN location l on bi.division_id = l.division_id and bi.depot_id = l.depot_id
LEFT JOIN engine_types et on em.engine_type_id = et.id
LEFT JOIN fip_types ft on fm.fip_hpp_type_id = ft.id
LEFT JOIN gearbox_types gt on gm.gear_box_type_id = gt.id
LEFT JOIN bus_seat_category bct ON 
    CASE WHEN bi.scraped = 0 THEN br.bus_sub_category ELSE bs.bus_sub_category END = bct.bus_sub_category
WHERE $condition
order by l.division_id, l.depot_id, bi.bus_number";

$result = mysqli_query($db, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($db));
}
// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('System');
$pdf->SetTitle('Bus Inventory PDF');
$pdf->SetSubject('DVP Report');
$pdf->SetKeywords('DVP, Report, KKRTC');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 8);


while ($row = mysqli_fetch_assoc($result)) {

    $html = '<div style=" page-break-after: always;
        width: 310mm;
        height: 450mm;
        padding: 1mm 1mm;
        box-sizing: border-box;
        overflow: hidden;"><h3 style="text-align: center";>Inventory Details for Bus Number: <strong>' . htmlspecialchars($row['bus_number']) . '</strong></h3>';
    $html .= '<table  border="1" cellpadding="5" class="table table-bordered" style=" width: 100%;
        border-collapse: collapse;
        font-size: 6px !important;
        table-layout: fixed;
        word-wrap: break-word;"><tbody>

        <tr><th><b>Bus Number</b></th><td>' . htmlspecialchars($row['bus_number']) . '</td><th><b>Division</b></th><td>' . htmlspecialchars($row['division']) . '</td><th><b>Depot</b></th><td>' . htmlspecialchars($row['depot']) . '</td><th><b>DOC</b></th><td>' . date('d-m-Y', strtotime($row['doc'])) . '</td></tr>
        <tr><th><b>Make</b></th><td>' . htmlspecialchars($row['make']) . '</td><th><b>Emission Norms</b></th><td>' . htmlspecialchars($row['emission_norms']) . '</td><th><b>Chassis No</b></th><td>' . htmlspecialchars($row['chassis_number']) . '</td><th><b>Bus Category</b></th><td>' . htmlspecialchars($row['bus_sub_category']) . '</td></tr>
        <tr><th><b>Body Builder</b></th><td>' . htmlspecialchars($row['bus_body_builder']) . '</td><th><b>Seating Capacity</b></th><td>' . htmlspecialchars($row['seating_capacity']) . '</td><th><b>wheel Base</b></th><td>' . htmlspecialchars($row['wheel_base']) . '</td><th><b>Bus Progressive Km</b></th><td>' . htmlspecialchars($row['bus_progressive_km']) . '</td></tr>
        <tr><th><b>FC date</b></th><td colspan="2">' . date('d-m-Y', strtotime($row['date_of_fc'])) . '</td></tr>
        <tr><th colspan="8"><h4 style="text-align:center">Engine Details</h4></th></tr>
        <tr><th><b>Card No</b></th><td>' . htmlspecialchars($row['engine_card_number']) . '</td><th><b>Engine No</b></th><td>' . htmlspecialchars($row['engine_number']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['engine_make']) . '</td><th><b>Model</b></th><td>' . htmlspecialchars($row['engine_model']) . '</td></tr>
        <tr><th><b>Type</b></th><td>' . htmlspecialchars($row['engine_type']) . '</td><th><b>Condition</b></th><td>' . htmlspecialchars($row['engine_condition']) . '</td><th colspan="2"><b>Progressive KM</b></th><td>' . htmlspecialchars($row['engine_progressive_km']) . '</td></tr>
        <tr><th colspan="8"><h4 style="text-align:center">FIP/HPP Details</h4></th></tr>
        <tr><th><b>Card No</b></th><td>' . htmlspecialchars($row['fip_hpp_card_number']) . '</td><th><b>FIP/HPP No</b></th><td>' . htmlspecialchars($row['fip_hpp_number']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['fip_hpp_make']) . '</td><th><b>Model</b></th><td>' . htmlspecialchars($row['fip_hpp_model']) . '</td></tr>
        <tr><th><b>Bus Make</b></th><td>' . htmlspecialchars($row['fip_hpp_bus_make']) . '</td><th><b>Type</b></th><td>' . htmlspecialchars($row['fip_hpp_type']) . '  ' . htmlspecialchars($row['fiphpp_model']) . '</td><th><b>Condition</b></th><td>' . htmlspecialchars($row['fip_hpp_condition']) . '</td><th><b>Progressive KM</b></th><td>' . htmlspecialchars($row['fip_hpp_progressive_km']) . '</td></tr>
        <tr><th colspan="8"><h4 style="text-align:center">Gear Box Details</h4></th></tr>
        <tr><th><b>Card No</b></th><td>' . htmlspecialchars($row['gear_box_card_number']) . '</td><th><b>Gear Box No</b></th><td>' . htmlspecialchars($row['gear_box_number']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['gear_box_make']) . '</td><th><b>Model</b></th><td>' . htmlspecialchars($row['gear_box_model']) . '</td></tr>
        <tr><th><b>Type</b></th><td colspan="2">' . htmlspecialchars($row['gear_box_type']) . '  ' . htmlspecialchars($row['gear_box_model']) . '</td><th><b>Condition</b></th><td>' . htmlspecialchars($row['gear_box_condition']) . '</td><th colspan="2"><b>Progressive KM</b></th><td>' . htmlspecialchars($row['gear_box_progressive_km']) . '</td></tr>
        <tr><th colspan="8"><h4 style="text-align:center">Starer Details</h4></th></tr>
        <tr><th><b>Card No</b></th><td>' . htmlspecialchars($row['starter_card_number']) . '</td><th><b>Starter No</b></th><td>' . htmlspecialchars($row['starter_number']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['starter_make']) . '</td><th><b>condition</b></th><td>' . htmlspecialchars($row['starter_condition']) . '</td></tr>
        <tr><th colspan="2"><b>Progressive KM</b></th><td colspan="2">' . htmlspecialchars($row['starter_progressive_km']) . '</td></tr>
        <tr><th colspan="8"><h4 style="text-align:center">Alternator Details</h4></th></tr>
        <tr><th><b>Card No</b></th><td>' . htmlspecialchars($row['alternator_card_number']) . '</td><th><b>Alternator No</b></th><td>' . htmlspecialchars($row['alternator_number']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['alternator_make']) . '</td><th><b>condition</b></th><td>' . htmlspecialchars($row['alternator_condition']) . '</td></tr>
        <tr><th colspan="2"><b>Progressive KM</b></th><td colspan="2">' . htmlspecialchars($row['alternator_progressive_km']) . '</td></tr>
        <tr><th colspan="8"><h4 style="text-align:center">Rear Axle Details</h4></th></tr>
        <tr><th><b>Card No</b></th><td>' . htmlspecialchars($row['rear_axle_card_number']) . '</td><th><b>Rear Axle No</b></th><td>' . htmlspecialchars($row['rear_axle_number']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['rear_axle_make']) . '</td><th><b>condition</b></th><td>' . htmlspecialchars($row['rear_axle_condition']) . '</td></tr>
        <tr><th colspan="2"><b>Progressive KM</b></th><td colspan="2">' . htmlspecialchars($row['rear_axle_progressive_km']) . '</td></tr>  
        <tr><th colspan="8"><h4 style="text-align:center">Battery Details</h4></th></tr>
        <tr><th><b>Card No 1</b></th><td>' . htmlspecialchars($row['b1_battery_card_number']) . '</td><th><b>battery No 1</b></th><td>' . htmlspecialchars($row['b1_battery_number']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['b1_battery_make']) . '</td><th><b>Progressive KM</b></th><td>' . htmlspecialchars($row['b1_progressive_km']) . '</td></tr>
        <tr><th><b>Card No 2</b></th><td>' . htmlspecialchars($row['b2_battery_card_number']) . '</td><th><b>battery No 2</b></th><td>' . htmlspecialchars($row['b2_battery_number']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['b2_battery_make']) . '</td><th><b>Progressive KM</b></th><td>' . htmlspecialchars($row['b2_progressive_km']) . '</td></tr>';
    if ($row['speed_governor'] == 'FITTED') {
        $html .= '<tr><th colspan="8"><h4 style="text-align:center">Speed Governor Details</h4></th></tr>
        <tr><th><b>Speed Governor</b></th><td>' . htmlspecialchars($row['speed_governor']) . '</td><th><b>Model</b></th><td>' . htmlspecialchars($row['speed_governor_model']) . '</td><th><b>Serial No</b></th><td>' . htmlspecialchars($row['speed_governor_serial_no']) . '</td></tr>';
    } else {
        $html .= '<tr><th colspan="8"><h4 style="text-align:center">Speed Governor Details</h4></th></tr>
        <tr><th><b>Speed Governor</b></th><td colspan="7">' . htmlspecialchars($row['speed_governor']) . '</td></tr>';
    }
    if ($row['bus_type'] == 'AC') {
        $html .= '<tr><th colspan="8"><h4 style="text-align:center">AC Unit Details</h4></th></tr>
        <tr><th><b>AC Unit</b></th><td>' . htmlspecialchars($row['ac_unit']) . '</td><th><b>Model</b></th><td colspan="5">' . htmlspecialchars($row['ac_model']) . '</td></tr>';
    }
    if ($row['bus_sub_category'] == 'Jn-NURM Midi City') {
        $html .= '<tr><th colspan="8"><h4 style="text-align:center">LED Board Details</h4></th></tr>';
        if ($row['led_board'] == 'YES') {
            $html .= '<tr><th><b>LED Board</b></th><td>' . htmlspecialchars($row['led_board']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['led_board_make']) . '</td><th><b>Front</b></th><td>' . htmlspecialchars($row['led_board_front']) . '</td><th><b>Rear</b></th><td>' . htmlspecialchars($row['led_board_rear']) . '</td></tr>';
        } else {
            $html .= '<tr><th><b>LED Board</b></th><td colspan="7">' . htmlspecialchars($row['led_board']) . '</td></tr>';
        }
    }
    if ($row['bus_sub_category'] == 'Branded DULT City') {
        $html .= '<tr><th colspan="8"><h4 style="text-align:center">LED Board Details</h4></th></tr>';
        if ($row['led_board'] == 'YES') {
            $html .= '<tr><th><b>LED Board</b></th><td>' . htmlspecialchars($row['led_board']) . '</td><th><b>Make</b></th><td>' . htmlspecialchars($row['led_board_make']) . '</td><th><b>Front</b></th><td>' . htmlspecialchars($row['led_board_front']) . '</td><th><b>Rear</b></th><td>' . htmlspecialchars($row['led_board_rear']) . '</td></tr>';
            $html .= '<tr><th><b>Front Inside</b></th><td>' . htmlspecialchars($row['led_board_front_inside']) . '</td><th><b>LHS Outside</b></th><td colspan="5">' . htmlspecialchars($row['led_board_lhs_outside']) . '</td></tr>';
        } else {
            $html .= '<tr><th><b>LED Board</b></th><td colspan="7">' . htmlspecialchars($row['led_board']) . '</td></tr>';
        }
    }
    if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6') {
        $html .= '<tr><th colspan="8"><h4 style="text-align:center">Camera Details</h4></th></tr>';
        $html .= '<tr><th><b>Front Saloon</b></th><td>' . htmlspecialchars($row['camera_f_saloon']) . '</td><th><b>Front Outside</b></th><td>' . htmlspecialchars($row['camera_f_outside']) . '</td><th><b>Rear Saloon</b></th><td>' . htmlspecialchars($row['camera_r_saloon']) . '</td><th><b>Rear Outside</b></th><td>' . htmlspecialchars($row['camera_r_outside']) . '</td></tr>';
        $html .= '<tr><th><b>Monitor</b></th><td>' . htmlspecialchars($row['camera_monitor']) . '</td><th><b>Storage Unit</b></th><td colspan="5">' . htmlspecialchars($row['camera_storage_unit']) . '</td></tr>';
    }
    if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
        $html .= '<tr><th><b>PIS Mike Amplifier</b></th><td colspan="7">' . htmlspecialchars($row['pis_mike_amplefier']) . '</td></tr>';
    }
    if ($row['emission_norms'] == 'BS-6') {
        $html .= '<tr><th colspan="8"><h4 style="text-align:center">General Details</h4></th></tr>
        <tr><th><b>VLTS Unit Present</b></th><td>' . htmlspecialchars($row['vlts_unit_present']) . '</td><th><b>Make</b></th><td colspan="5">' . htmlspecialchars($row['vlts_unit_make']) . '</td></tr>';
    }
    if ($row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
        $html .= '<tr><th><b>FDAS FDSS Present</b></th><td colspan="7">' . htmlspecialchars($row['fdas_fdss_present']) . '</td></tr>';
    }
    $html .= '<tr><th><b>Fire Extinguisher Nos</b></th><td>' . htmlspecialchars($row['fire_extinguisher_nos']) . '</td><th><b>Total KG</b></th><td>' . htmlspecialchars($row['fire_extinguisher_total_kg']) . '</td><th colspan="2"><b>First Aid Box Status</b></th><td colspan="2">' . htmlspecialchars($row['first_aid_box_status']) . '</td></tr>';
    $html .= '</tbody></table>';
    $html .= '</div>';


    $pdf->writeHTML($html, true, false, true, false, '');
}

$pdf->Output('bus_inventory_report_' . date('d_m_Y') . '.pdf', 'D');
