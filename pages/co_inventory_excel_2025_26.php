<?php
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include '../includes/connection.php';
include '../pages/session.php';

function colLetter($col) {
    $letter = '';
    while ($col > 0) {
        $col--;
        $letter = chr(65 + ($col % 26)) . $letter;
        $col = intval($col / 26);
    }
    return $letter;
}

if ($_SESSION['TYPE'] == 'HEAD-OFFICE') {
    $condition = "1=1";
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
FROM bus_inventory_2025_26 bi
LEFT JOIN bus_registration_2025_26 br ON bi.bus_number = br.bus_number
LEFT JOIN bus_scrap_data bs ON bi.bus_number = bs.bus_number
LEFT JOIN 2025_26_engine_master em ON bi.engine_id = em.id
LEFT JOIN 2025_26_fip_hpp_master fm ON bi.fiphpp_id = fm.id
LEFT JOIN 2025_26_gearbox_master gm ON bi.gearbox_id = gm.id
LEFT JOIN 2025_26_starter_master sm ON bi.starter_id = sm.id
LEFT JOIN 2025_26_alternator_master am ON bi.alternator_id = am.id
LEFT JOIN 2025_26_rear_axle_master ram ON bi.rear_axel_id = ram.id
LEFT JOIN 2025_26_battery_master b1 ON bi.battery_1_id = b1.id
LEFT JOIN 2025_26_battery_master b2 ON bi.battery_2_id = b2.id
LEFT JOIN location l ON bi.division_id = l.division_id AND bi.depot_id = l.depot_id
LEFT JOIN engine_types et ON em.engine_type_id = et.id
LEFT JOIN fip_types ft ON fm.fip_hpp_type_id = ft.id
LEFT JOIN gearbox_types gt ON gm.gear_box_type_id = gt.id
LEFT JOIN bus_seat_category bct ON 
    CASE WHEN bi.scraped = 0 THEN br.bus_sub_category ELSE bs.bus_sub_category END = bct.bus_sub_category
WHERE $condition";

$result = mysqli_query($db, $query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = [
    'Bus Number', 'Division', 'Depot', 'Make', 'DOC', 'Emission Norms', 'Chassis No',
    'Bus Sub Category', 'Seating Capacity', 'Bus Body Builder', 'Wheel Base',
    'Bus Progressive KM', 'Date of FC', 'Engine Card No', 'Engine No', 'Engine Make',
    'Engine Type', 'Engine Model', 'Engine Condition', 'Engine KM',
    'FIP/HPP Card No', 'FIP/HPP No', 'FIP/HPP Make', 'FIP Type', 'FIP Model',
    'FIP Condition', 'FIP KM', 'Gear Box Card No', 'Gear Box No', 'Gear Make',
    'Gear Type', 'Gear Model', 'Gear Condition', 'Gear KM',
    'Starter Card No', 'Starter No', 'Starter Make', 'Starter Condition', 'Starter KM',
    'Alternator Card No', 'Alternator No', 'Alternator Make', 'Alternator Condition', 'Alternator KM',
    'Rear Axle Card No', 'Rear Axle No', 'Rear Axle Make', 'Rear Axle Condition', 'Rear Axle KM',
    'Battery1 Card No', 'Battery1 No', 'Battery1 Make', 'Battery1 KM',
    'Battery2 Card No', 'Battery2 No', 'Battery2 Make', 'Battery2 KM',
    'Speed Governor', 'Speed Governor Model', 'Speed Governor Serial No',
    'AC Unit', 'AC Model',
    'LED Board', 'LED Board Make', 'LED Board Front', 'LED Board Rear', 'LED Board Front Inside', 'LED Board LHS Outside',
    'Camera F Saloon', 'Camera Front Outside', 'Camera Rear Saloon', 'Camera Rear Outside', 'Camera Monitor', 'Camera Storage Unit',
    'PIS Mike Amplifier', 'VLTS Unit Present', 'VLTS Unit Make', 'FDAS/FDSS Present',
    'Fire Extinguisher Nos', 'Fire Extinguisher Total KG', 'First Aid Box Status'
];

$col = 1;
foreach ($headers as $header) {
    $sheet->setCellValue(colLetter($col++) . '1', $header);
}

$rowNum = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $col = 1;

    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['bus_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['division']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['depot']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, !empty($row['doc']) && $row['doc'] != '0000-00-00' ? date('d-m-Y', strtotime($row['doc'])) : '');
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['emission_norms']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['chassis_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['bus_sub_category']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['seating_capacity']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['bus_body_builder']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['wheel_base']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['bus_progressive_km']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['date_of_fc']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['engine_card_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['engine_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['engine_make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['engine_type']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['engine_model']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['engine_condition']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['engine_progressive_km']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fip_hpp_card_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fip_hpp_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fip_hpp_make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fip_hpp_type']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fiphpp_model']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fip_hpp_condition']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fip_hpp_progressive_km']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['gear_box_card_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['gear_box_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['gear_box_make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['gear_box_type']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['gear_box_model']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['gear_box_condition']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['gear_box_progressive_km']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['starter_card_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['starter_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['starter_make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['starter_condition']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['starter_progressive_km']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['alternator_card_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['alternator_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['alternator_make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['alternator_condition']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['alternator_progressive_km']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['rear_axle_card_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['rear_axle_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['rear_axle_make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['rear_axle_condition']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['rear_axle_progressive_km']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['b1_battery_card_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['b1_battery_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['b1_battery_make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['b1_progressive_km']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['b2_battery_card_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['b2_battery_number']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['b2_battery_make']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['b2_progressive_km']);

    if ($row['speed_governor'] == 'FITTED') {
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['speed_governor']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['speed_governor_model']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['speed_governor_serial_no']);
    } else {
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['speed_governor']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
        $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
    }

    if ($row['bus_type'] == 'AC') {
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['ac_unit']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['ac_model']);
    } else {
        $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
        $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
    }

    if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['bus_sub_category'] == 'Branded DULT City' ||  $row['bus_sub_category'] == 'Double Door City') {
        if ($row['bus_sub_category'] == 'Jn-NURM Midi City') {
            if ($row['led_board'] == 'YES') {
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board_make']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board_front']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board_rear']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
            } else {
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
            }
        } else {
            if ($row['led_board'] == 'YES') {
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board_make']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board_front']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board_rear']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board_front_inside']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board_lhs_outside']);
            } else {
                $sheet->setCellValue(colLetter($col++) . $rowNum, $row['led_board']);
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
                $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
            }
        }
    } else {
        for ($i = 0; $i < 6; $i++) {
            $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
        }
    }

    if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6') {
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['camera_f_saloon']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['camera_f_outside']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['camera_r_saloon']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['camera_r_outside']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['camera_monitor']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['camera_storage_unit']);
    } else {
        for ($i = 0; $i < 6; $i++) {
            $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
        }
    }

    if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['pis_mike_amplefier']);
    } else {
        $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
    }

    if ($row['emission_norms'] == 'BS-6') {
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['vlts_unit_present']);
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['vlts_unit_make']);
    } else {
        $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
        $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
    }

    if ($row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
        $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fdas_fdss_present']);
    } else {
        $sheet->setCellValue(colLetter($col++) . $rowNum, 'NA');
    }

    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fire_extinguisher_nos']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['fire_extinguisher_total_kg']);
    $sheet->setCellValue(colLetter($col++) . $rowNum, $row['first_aid_box_status']);

    $rowNum++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="bus_inventory_details_2025_26.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;