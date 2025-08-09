<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM')) {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    // 1. Fetch all required program types dynamically
    $program_types = [];
    $column_query = "SHOW COLUMNS FROM program_master";
    $column_result = mysqli_query($db, $column_query);
    $exclude_columns = ['id', 'make', 'model', 'model_type', 'created_at', 'updated_at'];

    while ($column = mysqli_fetch_assoc($column_result)) {
        if (!in_array($column['Field'], $exclude_columns)) {
            $program_types[] = $column['Field'];
        }
    }

    $air_suspension_bus_category_array = ['Rajahamsa', 'Corona Sleeper AC', 'Sleeper AC', 'Regular Sleeper Non AC', 'Amoghavarsha Sleeper Non AC', 'Kalyana Ratha'];
    // 2. Fetch all buses for this depot/division
    $bus_query = "SELECT br.bus_number, br.make, br.emission_norms, br.bus_progressive_km_31032025, br.model_type, bs.bus_type, bs.bus_category, br.bus_sub_category FROM bus_registration br left join bus_seat_category bs on bs.bus_sub_category= br.bus_sub_category WHERE br.depot_name = $depot_id AND br.division_name = $division_id";
    $bus_result = mysqli_query($db, $bus_query);

    $incomplete = false;

    while ($bus = mysqli_fetch_assoc($bus_result)) {
        $bus_number = $bus['bus_number'];
        $make = $bus['make'];
        $model = $bus['model_type'];
        $emission = $bus['emission_norms'];

        // Fetch applicable programs for the bus
        $prog_val_query = "SELECT * FROM program_master WHERE make = '$make' AND model = '$emission' AND model_type = '$model' LIMIT 1";
        $prog_val_result = mysqli_query($db, $prog_val_query);
        $prog_val_row = mysqli_fetch_assoc($prog_val_result);

        // Fetch program data entered
        $program_query = "SELECT program_type FROM program_data WHERE bus_number = '$bus_number'";
        $program_result = mysqli_query($db, $program_query);

        $filled_programs = [];
        while ($row = mysqli_fetch_assoc($program_result)) {
            $filled_programs[] = $row['program_type'];
        }

        if ($prog_val_row) {
            foreach ($program_types as $ptype) {
                // Skip air_suspension logic
                if ($ptype === 'air_suspension_check' && !in_array($bus['bus_sub_category'], $air_suspension_bus_category_array)) {
                    continue;
                }

                if (!is_null($prog_val_row[$ptype]) && !in_array($ptype, $filled_programs)) {
                    $incomplete = true;
                    break 2;
                }
            }
        }
    }

    if ($incomplete) {
        echo "<script>
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Data',
            text: 'Last Maintenance KM is missing. Please update it after the bus is serviced.',
            confirmButtonText: 'Go to Update Page'
        }).then(() => {
            window.location.href = 'depot_program_update.php';
        });
    </script>";
        exit;
    }
    $today = date('d-m-Y');
?>
    <style>
        @media print {
            body {
                color: black !important;
                transform-origin: top left;
            }

            .container1 {
                width: 100%;
                margin: 0 auto;
            }

            table th,
            table td {
                color: black !important;
            }

            @page {
                size: auto;
                margin: 0;
            }
        }
    </style>

    <!-- add a print button -->
    <div class="text-center mb-3">
        <a href="depot_program_print.php" class="btn btn-secondary" target="_blank">Print</a>
    </div>

    <div class="container1">
        <h2 class="text-center text-primary mt-4"><?php echo $_SESSION['KMPL_DEPOT']; ?> Maintenance Program As on Date: <?php echo $today; ?></h2>
        <div class="text-center mb-3">

        </div>

        <?php
        $today = date('Y-m-d');
        $program_labels = [
            'docking' => 'Docking',
            'engine_oil_and_main_filter_change' => 'Engine Oil And Main Filter Change',
            'gear_box_oil_change' => 'Gear Box Oil Change',
            'housing_oil_change' => 'Housing Oil Change',
            'engine_coolant_change' => 'Engine Coolant Change',
            'power_steering_oil_and_filter_change' => 'Power Steering Oil And Filter Change',
            'fuel_filter_change' => 'Fuel Filter Change',
            'fuel_strainer_change' => 'Fuel Strainer Change',
            'diesel_filter_change' => 'Diesel Filter Change',
            'def_suction_filter' => 'DEF Suction Filter Change',
            'def_neck_filter' => 'DEF Neck Filter Change',
            'def_air_filter' => 'DEF Air Filter Change',
            'mc_assembely_with_oil_chnage' => 'Clutch M/C, Assembly & Oil Change',
            'air_suspension_check' => 'Air Suspension Check',
            'alternator_overhaul_check' => 'Alternator Overhaul Check',
            'air_compressor_overhaul' => 'Air Compressor Overhaul',
            'Air_compressor_read_calve' => 'Air Compressor Read Calve Change',
            'fan_belt_change' => 'Fan Belt Change',
            'tappet_setting_check' => 'Tappet Setting Check',
            'spring_cambering_check' => 'Spring Cambering Check',
            'voith_retarder_oil_change' => 'Voith Retarder Oil Change',
            'tyre_rotation_check' => 'Tyre Rotation Check',
            'error_code_edc_check' => 'Error Code EDC Check',
            'apda_mesh_cleaning_check' => 'APDA Mesh Cleaning Check',
            'apda_major_kit_change' => 'APDA Major Kit Change',
            'fuel_tank_ventilation_filter_change' => 'Fuel Tank Ventilation Filter Change',
            'air_filter_insert_primary_change' => 'Air Filter Insert Primary Change',
            'air_filter_kit_change' => 'Air Filter Kit Change',
            'gear_box_oil_filter_change' => 'Gear Box Oil Filter Change',
            'air_drier_filter_change' => 'Air Drier Filter Change',
            'coolant_pump_and_alternator_belt_change' => 'Coolant Pump and Alternator Belt Change',
            'particulate_filter_insert_change' => 'Particulate Filter Insert Change',
            'air_supply_system_check' => 'Air Supply System Check'
        ];




        $buses = [];
        $bus_result = mysqli_query($db, "SELECT bus_number, make, emission_norms, model_type FROM bus_registration WHERE division_name = '$division_id' AND depot_name = '$depot_id'");
        while ($row = mysqli_fetch_assoc($bus_result)) {
            $buses[$row['bus_number']] = [
                'make' => $row['make'],
                'emission_norms' => $row['emission_norms'],
                'model_type' => $row['model_type']
            ];
        }

        if (empty($buses)) {
            echo "<p>No buses found.</p>";
            return;
        }

        $bus_numbers = array_keys($buses);
        $bus_list = "'" . implode("','", $bus_numbers) . "'";

        // Get last program data and km in one pass
        $last_program_data = [];
        $program_result = mysqli_query($db, "
        SELECT pd.bus_number, pd.program_type, pd.program_completed_km, pd.program_date
        FROM program_data pd
        INNER JOIN (
            SELECT bus_number, program_type, MAX(id) as max_id
            FROM program_data
            WHERE bus_number IN ($bus_list)
            GROUP BY bus_number, program_type
        ) latest ON pd.id = latest.max_id
    ");
        while ($row = mysqli_fetch_assoc($program_result)) {
            $bus = $row['bus_number'];
            $ptype = $row['program_type'];
            $last_program_data[$bus][$ptype] = [
                'km' => $row['program_completed_km'],
                'date' => $row['program_date']
            ];
        }

        // Collect all vehicle_kmpl data in one query
        $kmpl_data = [];
        $kmpl_result = mysqli_query($db, "
        SELECT bus_number, date, km_operated FROM vehicle_kmpl
        WHERE deleted != '1' AND bus_number IN ($bus_list) AND date > '2025-07-31' AND date <= '$today'
    ");
        while ($row = mysqli_fetch_assoc($kmpl_result)) {
            $bus = $row['bus_number'];
            $date = $row['date'];
            $km = $row['km_operated'];
            $kmpl_data[$bus][$date] = ($kmpl_data[$bus][$date] ?? 0) + $km;
        }

        $grouped_buses = [];
        foreach ($buses as $bus_number => $meta) {
            $key = $meta['make'] . "|" . $meta['emission_norms'] . "|" . $meta['model_type'];
            $grouped_buses[$key][] = $bus_number;
        }
        $any_rows_printed = false; // Flag to track if any table rows were printed

        $program_data = [];

        $program_data = [];

        foreach ($grouped_buses as $group_key => $bus_list_group) {
            list($make, $emission, $model_type) = explode('|', $group_key);

            $pm_result = mysqli_query($db, "SELECT * FROM program_master WHERE make = '$make' AND model = '$emission' AND model_type = '$model_type' LIMIT 1");
            if (!mysqli_num_rows($pm_result)) continue;

            $pm = mysqli_fetch_assoc($pm_result);

            // Step 1: Extract only valid program fields (skip metadata columns)
            $programs = [];
            foreach ($pm as $prog => $km) {
                if (!in_array($prog, ['id', 'make', 'model', 'model_type', 'created_at', 'updated_at']) && $km !== null && $km !== '') {
                    $programs[$prog] = $km;
                }
            }

            // 🚫 If no applicable programs, skip this group
            if (empty($programs)) continue;

            // Step 2: Loop each bus in group
            foreach ($bus_list_group as $bus_number) {

                // Step 3: For each applicable program, check if bus has done it or not
                foreach ($programs as $ptype => $prescribed_km) {

                    // ✅ Vehicle is eligible for this program (as per program_master)
                    // Whether or not it has done the program, we'll show it if KM exists

                    $last_entry = $last_program_data[$bus_number][$ptype] ?? null;
                    $program_date = $last_entry['date'] ?? null;
                    $total_km = 0;

                    if ($last_entry && $program_date !== '0000-00-00') {
                        $start_date = date('Y-m-d', strtotime($program_date . ' +1 day'));
                        if (!empty($kmpl_data[$bus_number])) {
                            foreach ($kmpl_data[$bus_number] as $date => $km) {
                                if ($date >= $start_date) {
                                    $total_km += $km;
                                }
                            }
                        }
                    } else {
                        // ❗ Program never done, accumulate from 2025-08-01
                        if (!empty($kmpl_data[$bus_number])) {
                            foreach ($kmpl_data[$bus_number] as $date => $km) {
                                if ($date > '2025-07-31') {
                                    $total_km += $km;
                                }
                            }
                        }
                    }

                    // Step 4: Compare with prescribed KM
                    $deviation = $total_km - $prescribed_km;

                    // Step 5: Set class for table color
                    if ($deviation > 500) {
                        $color = 'bg-danger text-white';
                    } elseif ($deviation >= -500 && $deviation <= 500) {
                        $color = 'bg-warning';
                    } else {
                        continue; // Skip if under-deviation
                    }

                    // ✅ Add this vehicle under this specific program only
                    $program_data[$ptype][] = [
                        'bus_number' => $bus_number,
                        'total_km' => $total_km,
                        'class' => $color,
                        'program_type' => $program_labels[$ptype] ?? ucfirst(str_replace('_', ' ', $ptype)),
                    ];
                }
            }
        }

        if (!empty($program_data)) {
            echo "<div class='table-responsive'>";
            echo "<table class='table table-bordered'>";
            echo "<thead><tr>";

            foreach ($program_data as $ptype => $entries) {
                if (empty($entries)) continue;
                $program_name = $entries[0]['program_type'];
                echo "<th colspan='2' class='text-center'>$program_name</th>";
            }

            echo "</tr><tr>";
            foreach ($program_data as $ptype => $entries) {
                if (empty($entries)) continue;
                echo "<th>Vehicle No</th><th>Total KM</th>";
            }
            echo "</tr></thead><tbody>";

            $max_rows = max(array_map('count', $program_data));

            for ($i = 0; $i < $max_rows; $i++) {
                echo "<tr>";
                foreach ($program_data as $ptype => $entries) {
                    if (isset($entries[$i])) {
                        $entry = $entries[$i];
                        echo "<td class='{$entry['class']}'>{$entry['bus_number']}</td>";
                        echo "<td class='{$entry['class']}'>{$entry['total_km']}</td>";
                    } else {
                        echo "<td></td><td></td>";
                    }
                }
                echo "</tr>";
            }

            echo "</tbody></table></div>";
        } else {
            echo "<div class='alert alert-info text-center mt-4'><strong>None of the vehicle programs are present.</strong></div>";
        }




        ?>






        <script>
            // window.onload = function () {
            //     window.print();
            //};
        </script>


    <?php
} else {
    echo "<script>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
