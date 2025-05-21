<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Session Expired please Login again.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' || $_SESSION['TYPE'] == 'DIVISION' || $_SESSION['TYPE'] == 'RWY' || $_SESSION['TYPE'] == 'HEAD-OFFICE') {
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];

    if($_SESSION['TYPE'] == 'DEPOT'){
        $condition = "bi.depot_id = '$depot_id' AND bi.division_id = '$division_id'";
    }else if($_SESSION['TYPE'] == 'DIVISION'){
        $condition = "bi.division_id = '$division_id'";
    }else if($_SESSION['TYPE'] == 'RWY'){
        $condition = "bi.division_id = '$division_id' AND bi.depot_id = '$depot_id'";
    }else if($_SESSION['TYPE'] == 'HEAD-OFFICE'){
        $condition = "1=1"; // No condition for HEAD-OFFICE
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
order by l.division_id, l.depot_id, bi.bus_number
";

echo "
<style>
@media print {
    .a4-page {
        page-break-after: always;
        width: 310mm;
        height: 450mm;
        padding: 15mm 10mm;
        box-sizing: border-box;
        overflow: hidden;
    }

   
}

@media screen {
    .a4-page {
        width: 210mm;
        min-height: 297mm;
        padding: 15mm 10mm;
        margin: 10px auto;
        border: 1px solid #ccc;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        box-sizing: border-box;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 2px !important;
        table-layout: fixed;
        word-wrap: break-word;
    }

    th, td {
        border: 1px solid #000;
        padding: 1px !important;
        vertical-align: top;
        overflow-wrap: break-word;
        word-break: break-word;
    }

   
}
    th,td {
        font-size: 14px !important;
}
    h5 {
        font-size: 16px !important;
    }
    

</style>

";

$result = mysqli_query($db, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($db));
}
//button for print and pdf download
echo "<div class='container' style='text-align: center; margin-bottom: 20px;'>";
echo "<button class='btn btn-primary' onclick=\"window.print()\">Print</button>";
echo "<button class='btn btn-secondary' onclick=\"window.location.href='co_inventory_pdf.php'\">Download PDF</button>";
echo "</div>";
echo "<div class='container1'>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<div class='a4-page'>";
    echo "<h4 class='text-center mb-4'>Inventory Details for Bus Number: <strong>" . htmlspecialchars($row['bus_number']) . "</strong></h4>";
        echo "<table class='table table-bordered '><tbody>";

        // Bus Basic Info
        echo "<tr><th>Bus Number</th><td>" . htmlspecialchars($row['bus_number']) . "</td><th>Division</th><td>" . htmlspecialchars($row['division']) . "</td><th>Depot</th><td>" . htmlspecialchars($row['depot']) . "</td><th>DOC</th><td>" . date('d-m-Y', strtotime($row['doc'])) . "</td></tr>";
        echo "<tr><th>Make</th><td>" . htmlspecialchars($row['make']) . "</td><th>Emission Norms</th><td>" . htmlspecialchars($row['emission_norms']) . "</td><th>Chassis No</th><td>" . htmlspecialchars($row['chassis_number']) . "</td><th>Bus Category</th><td>" . htmlspecialchars($row['bus_sub_category']) . "</td></tr>";
        echo "<tr><th>Body Builder</th><td>" . htmlspecialchars($row['bus_body_builder']) . "</td><th>Seating Capacity</th><td>" . htmlspecialchars($row['seating_capacity']) . "</td><th>wheel Base</th><td>" . htmlspecialchars($row['wheel_base']) . "</td><th>Bus Progressive Km</th><td>" . htmlspecialchars($row['bus_progressive_km']) . "</td></tr>";
        echo "<tr><th>FC date</th><td colspan='2'>" . date('d-m-Y', strtotime($row['date_of_fc'])) . "</td></tr>";
        echo "<tr><th colspan='8'><h5 class='text-center'>Engine Details</h5></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['engine_card_number']) . "</td><th>Engine No</th><td>" . htmlspecialchars($row['engine_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['engine_make']) . "</td><th>Model</th><td>" . htmlspecialchars($row['engine_model']) . "</td></tr>";
        echo "<tr><th>Type</th><td>" . htmlspecialchars($row['engine_type']) . "</td><th>Condition</th><td>" . htmlspecialchars($row['engine_condition']) . "</td><th colspan='2'>Progressive KM</th><td>" . htmlspecialchars($row['engine_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h5 class='text-center'>FIP/HPP Details</h5></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['fip_hpp_card_number']) . "</td><th>FIP/HPP No</th><td>" . htmlspecialchars($row['fip_hpp_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['fip_hpp_make']) . "</td><th>Model</th><td>" . htmlspecialchars($row['fip_hpp_model']) . "</td></tr>";
        echo "<tr><th>Bus Make</th><td>" . htmlspecialchars($row['fip_hpp_bus_make']) . "</td><th>Type</th><td>" . htmlspecialchars($row['fip_hpp_type']) . "  " . htmlspecialchars($row['fiphpp_model']) . "</td><th>Condition</th><td>" . htmlspecialchars($row['fip_hpp_condition']) . "</td><th>Progressive KM</th><td>" . htmlspecialchars($row['fip_hpp_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h5 class='text-center'>Gear Box Details</h5></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['gear_box_card_number']) . "</td><th>Gear Box No</th><td>" . htmlspecialchars($row['gear_box_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['gear_box_make']) . "</td><th>Model</th><td>" . htmlspecialchars($row['gear_box_model']) . "</td></tr>";
        echo "<tr><th>Type</th><td colspan='2'>" . htmlspecialchars($row['gear_box_type']) . "  " . htmlspecialchars($row['gear_box_model']) . "</td><th>Condition</th><td>" . htmlspecialchars($row['gear_box_condition']) . "</td><th colspan='2'>Progressive KM</th><td>" . htmlspecialchars($row['gear_box_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h5 class='text-center'>Starer Details</h5></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['starter_card_number']) . "</td><th>Starter No</th><td>" . htmlspecialchars($row['starter_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['starter_make']) . "</td><th>condition</th><td>" . htmlspecialchars($row['starter_condition']) . "</td></tr>";
        echo "<tr><th colspan='2'>Progressive KM</th><td colspan='2'>" . htmlspecialchars($row['starter_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h5 class='text-center'>Alternator Details</h5></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['alternator_card_number']) . "</td><th>Alternator No</th><td>" . htmlspecialchars($row['alternator_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['alternator_make']) . "</td><th>condition</th><td>" . htmlspecialchars($row['alternator_condition']) . "</td></tr>";
        echo "<tr><th colspan='2'>Progressive KM</th><td colspan='2'>" . htmlspecialchars($row['alternator_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h5 class='text-center'>Rear Axle Details</h5></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['rear_axle_card_number']) . "</td><th>Rear Axle No</th><td>" . htmlspecialchars($row['rear_axle_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['rear_axle_make']) . "</td><th>condition</th><td>" . htmlspecialchars($row['rear_axle_condition']) . "</td></tr>";
        echo "<tr><th colspan='2'>Progressive KM</th><td colspan='2'>" . htmlspecialchars($row['rear_axle_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h5 class='text-center'>Battery Details</h5></th></tr>";
        echo "<tr><th>Card No 1</th><td>" . htmlspecialchars($row['b1_battery_card_number']) . "</td><th>battery No 1</th><td>" . htmlspecialchars($row['b1_battery_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['b1_battery_make']) . "</td><th>Progressive KM</th><td>" . htmlspecialchars($row['b1_progressive_km']) . "</td></tr>";
        echo "<tr><th>Card No 2</th><td>" . htmlspecialchars($row['b2_battery_card_number']) . "</td><th>battery No 2</th><td>" . htmlspecialchars($row['b2_battery_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['b2_battery_make']) . "</td><th>Progressive KM</th><td>" . htmlspecialchars($row['b2_progressive_km']) . "</td></tr>";
        if ($row['speed_governor'] == 'FITTED') {
            echo "<tr><th colspan='8'><h5 class='text-center'>Speed Governor Details</h5></th></tr>";
            echo "<tr><th>Speed Governor</th><td>" . htmlspecialchars($row['speed_governor']) . "</td><th>Model</th><td>" . htmlspecialchars($row['speed_governor_model']) . "</td><th>Serial No</th><td>" . htmlspecialchars($row['speed_governor_serial_no']) . "</td></tr>";
        } else {
            echo "<tr><th colspan='8'><h5 class='text-center'>Speed Governor Details</h5></th></tr>";
            echo "<tr><th>Speed Governor</th><td>" . htmlspecialchars($row['speed_governor']) . "</td></tr>";
        }
        if ($row['bus_type'] == 'AC') {
            echo "<tr><th colspan='8'><h5 class='text-center'>AC Unit Details</h5></th></tr>";
            echo "<tr><th>AC Unit</th><td>" . htmlspecialchars($row['ac_unit']) . "</td><th>Model</th><td>" . htmlspecialchars($row['ac_model']) . "</td></tr>";
        }
        if ($row['bus_sub_category'] == 'Jn-NURM Midi City') {
            echo "<tr><th colspan='8'><h5 class='text-center'>LED Board Details</h5></th></tr>";
            if ($row['led_board'] == 'YES') {
                echo "<tr><th>LED Board</th><td>" . htmlspecialchars($row['led_board']) . "</td><th>Make</th><td>" . htmlspecialchars($row['led_board_make']) . "</td><th>Front</th><td>" . htmlspecialchars($row['led_board_front']) . "</td><th>Rear</th><td>" . htmlspecialchars($row['led_board_rear']) . "</td></tr>";
            } else {
                echo "<tr><th>LED Board</th><td>" . htmlspecialchars($row['led_board']) . "</td></tr>";
            }
        } 
        if ($row['bus_sub_category'] == 'Branded DULT City') {
            echo "<tr><th colspan='8'><h5 class='text-center'>LED Board Details</h5></th></tr>";
            if ($row['led_board'] == 'YES') {
                echo "<tr><th>LED Board</th><td>" . htmlspecialchars($row['led_board']) . "</td><th>Make</th><td>" . htmlspecialchars($row['led_board_make']) . "</td><th>Front</th><td>" . htmlspecialchars($row['led_board_front']) . "</td><th>Rear</th><td>" . htmlspecialchars($row['led_board_rear']) . "</td></tr>";
                echo "<tr><th>Front Inside</th><td>" . htmlspecialchars($row['led_board_front_inside']) . "</td><th>LHS Outside</th><td>" . htmlspecialchars($row['led_board_lhs_outside']) . "</td></tr>";
            } else {
                echo "<tr><th>LED Board</th><td>" . htmlspecialchars($row['led_board']) . "</td></tr>";
            }
        }
        if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6') {
            echo "<tr><th colspan='8'><h5 class='text-center'>Camera Details</h5></th></tr>";
            echo "<tr><th>Front Saloon</th><td>" . htmlspecialchars($row['camera_f_saloon']) . "</td><th>Front Outside</th><td>" . htmlspecialchars($row['camera_f_outside']) . "</td><th>Rear Saloon</th><td>" . htmlspecialchars($row['camera_r_saloon']) . "</td><th>Rear Outside</th><td>" . htmlspecialchars($row['camera_r_outside']) . "</td></tr>";
            echo "<tr><th>Monitor</th><td>" . htmlspecialchars($row['camera_monitor']) . "</td><th>Storage Unit</th><td>" . htmlspecialchars($row['camera_storage_unit']) . "</td>";
        }
        if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
            echo "<th>PIS Mike Amplifier</th><td>" . htmlspecialchars($row['pis_mike_amplefier']) . "</td></tr>";
        }
        if ($row['emission_norms'] == 'BS-6') {
            echo "<tr><th colspan='8'><h5 class='text-center'>General Details</h5></th></tr>";
            echo "<tr><th>VLTS Unit Present</th><td>" . htmlspecialchars($row['vlts_unit_present']) . "</td><th>Make</th><td>" . htmlspecialchars($row['vlts_unit_make']) . "</td>";
        }
        if ($row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
            echo "<th>FDAS FDSS Present</th><td>" . htmlspecialchars($row['fdas_fdss_present']) . "</td></tr>";
        }
        echo "<tr><th>Fire Extinguisher Nos</th><td>" . htmlspecialchars($row['fire_extinguisher_nos']) . "</td><th>Total KG</th><td>" . htmlspecialchars($row['fire_extinguisher_total_kg']) . "</td><th>First Aid Box Status</th><td>" . htmlspecialchars($row['first_aid_box_status']) . "</td></tr>";

        echo "</tbody></table>";
    echo "</div>";
}
echo "</div>";

    ?>





<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>