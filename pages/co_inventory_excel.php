<?php
require '../../vendor/autoload.php'; // Make sure this path is correct

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Include your DB connection
include '../includes/connection.php';
include '../pages/session.php';
if ($_SESSION['TYPE'] == 'HEAD-OFFICE') {
    $condition = "1=1"; // Default condition to fetch all records
} elseif ($_SESSION['TYPE'] == 'RWY') {
    $condition = "bi.division_id = {$_SESSION['DIVISION_ID']} AND bi.depot_id = {$_SESSION['DEPOT_ID']}";
} elseif ($_SESSION['JOB_TITLE'] == 'DME') {
    $condition = "bi.division_id = {$_SESSION['DIVISION_ID']}";
} elseif ($_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech') {
    $condition = "bi.division_id = {$_SESSION['DIVISION_ID']} AND bi.depot_id = {$_SESSION['DEPOT_ID']}";
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
// Your query as-is
$query = "SELECT bi.*, 
    CASE WHEN bi.scraped = 0 THEN br.make ELSE bs.make END AS make,
    CASE WHEN bi.scraped = 0 THEN br.doc ELSE bs.doc END AS doc,
    CASE WHEN bi.scraped = 0 THEN br.emission_norms ELSE bs.emission_norms END AS emission_norms,
    CASE WHEN bi.scraped = 0 THEN br.chassis_number ELSE bs.chassis_number END AS chassis_number,
    CASE WHEN bi.scraped = 0 THEN br.bus_sub_category ELSE bs.bus_sub_category END AS bus_sub_category,
    CASE WHEN bi.scraped = 0 THEN br.bus_body_builder ELSE bs.bus_body_builder END AS bus_body_builder,
    CASE WHEN bi.scraped = 0 THEN br.seating_capacity ELSE bs.seating_capacity END AS seating_capacity,
    CASE WHEN bi.scraped = 0 THEN br.wheel_base ELSE bs.wheel_base END AS wheel_base,
    em.*, fm.*, gm.*, sm.*, am.*, ram.*, 
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
WHERE $condition";

$result = mysqli_query($db, $query);

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Column headers
$headers = ['Bus Number', 'Division', 'Depot', 'Make', 'DOC', 'Emission Norms', 'Chassis No', 'Bus Sub Category', 'Seating Capacity', 'Bus Body Builder', 'Wheel Base', 'Bus Progressive KM', 'Date of FC', 'Engine Card No', 'Engine No', 'Engine Make', 'Engine Type', 'Engine Model', 'Engine Condition', 'Engine KM', 'FIP/HPP Card No', 'FIP/HPP No', 'FIP/HPP Make', 'FIP Type', 'FIP Model', 'FIP Condition', 'FIP KM', 'Gear Box Card No', 'Gear Box No', 'Gear Make', 'Gear Type', 'Gear Model', 'Gear Condition', 'Gear KM', 'Starter Card No', 'Starter No', 'Starter Make', 'Starter Condition', 'Starter KM', 'Alternator Card No', 'Alternator No', 'Alternator Make', 'Alternator Condition', 'Alternator KM', 'Rear Axle Card No', 'Rear Axle No', 'Rear Axle Make', 'Rear Axle Condition', 'Rear Axle KM', 'Battery1 Card No', 'Battery1 No', 'Battery1 Make', 'Battery1 KM', 'Battery2 Card No', 'Battery2 No', 'Battery2 Make', 'Battery2 KM', 'Speed Governor', 'Speed Governor Model', 'speed governor Serial No', 'AC Unit', 'AC Model', 'LED Board', 'LED Board Make', 'LED Board Front', 'LED Board Rear', 'LED Board Front Inside', 'LED Board LHS Outside', 'Camera F Saloon', 'Camera Front Outside', 'Camera Rear Saloon', 'Camera Rear Outside', 'Camera Monitor', 'Camera Storage Unit', 'PIS Mike Amplifier', 'VLTS Unit Present', 'VLTS Unit Make', 'FDAS/FDSS Present', 'Fire Extinguisher Nos', 'Fire Extinguisher Total KG', 'First Aid Box Status'];

// Write headers
$col = 1;
foreach ($headers as $header) {
    $sheet->setCellValueByColumnAndRow($col++, 1, $header);
}

// Write data
$rowNum = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $col = 1;
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['bus_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['division']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['depot']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, date('d-m-Y', strtotime($row['doc'])));
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['emission_norms']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['chassis_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['bus_sub_category']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['seating_capacity']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['bus_body_builder']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['wheel_base']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['bus_progressive_km']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['date_of_fc']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['engine_card_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['engine_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['engine_make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['engine_type']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['engine_model']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['engine_condition']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['engine_progressive_km']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fip_hpp_card_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fip_hpp_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fip_hpp_make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fip_hpp_type']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fiphpp_model']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fip_hpp_condition']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fip_hpp_progressive_km']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['gear_box_card_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['gear_box_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['gear_box_make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['gear_box_type']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['gear_box_model']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['gear_box_condition']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['gear_box_progressive_km']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['starter_card_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['starter_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['starter_make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['starter_condition']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['starter_progressive_km']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['alternator_card_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['alternator_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['alternator_make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['alternator_condition']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['alternator_progressive_km']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['rear_axle_card_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['rear_axle_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['rear_axle_make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['rear_axle_condition']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['rear_axle_progressive_km']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['b1_battery_card_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['b1_battery_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['b1_battery_make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['b1_progressive_km']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['b2_battery_card_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['b2_battery_number']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['b2_battery_make']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['b2_progressive_km']);
    if ($row['speed_governor'] == 'FITTED') {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['speed_governor']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['speed_governor_model']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['speed_governor_serial_no']);
    } else {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['speed_governor']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
    }
    if ($row['bus_type'] == 'AC') {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['ac_unit']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['ac_model']);
    } else {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
    }
    if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['bus_sub_category'] == 'Branded DULT City') {
        if ($row['bus_sub_category'] == 'Jn-NURM Midi City') {
            if ($row['led_board'] == 'YES') {
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board_make']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board_front']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board_rear']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
            } else {
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
            }
        }
        if ($row['bus_sub_category'] == 'Branded DULT City') {
            if ($row['led_board'] == 'YES') {
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board_make']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board_front']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board_rear']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board_front_inside']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board_lhs_outside']);
            } else {
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['led_board']);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
            }
        }
    } else {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
    }
    if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6') {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['camera_f_saloon']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['camera_f_outside']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['camera_r_saloon']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['camera_r_outside']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['camera_monitor']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['camera_storage_unit']);
    } else {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
    }
    if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['pis_mike_amplefier']);
    } else {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
    }
    if ($row['emission_norms'] == 'BS-6') {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['vlts_unit_present']);
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['vlts_unit_make']);
    } else {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
    }
    if ($row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fdas_fdss_present']);
    } else {
        $sheet->setCellValueByColumnAndRow($col++, $rowNum, 'NA');
    }
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fire_extinguisher_nos']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['fire_extinguisher_total_kg']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['first_aid_box_status']);

    $rowNum++;
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="bus_inventory_details.xlsx"');
header('Cache-Control: max-age=0');

// Write and download
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <!-- create a button back to home page -->
    <button onclick="window.location.href='index.php'">Back to Home</button>
</body>

</html>