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

            table {
                border-collapse: collapse;
                width: 100%;
            }

            table th,
            table td {
                color: black !important;
                border: 1px solid black;
                padding: 4px;
                font-size: 12px;
                text-align: center;
            }

            @page {
                size: landscape;
                margin: 0.5cm;
            }
        }
    </style>

    <!-- add a print button -->
    <div class="text-center mb-3">
        <button class="btn btn-secondary" onclick="window.print()">Print</button>
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
            'def_suction_filter_change' => 'DEF Suction Filter Change',
            'def_neck_filter_change' => 'DEF Neck Filter Change',
            'def_air_filter_change' => 'DEF Air Filter Change',
            'mc_assembely_with_oil_chnage' => 'Clutch M/C, Assembly & Oil Change',
            'air_suspension_check' => 'Air Suspension Check',
            'alternator_overhauling' => 'Alternator Overhauling',
            'air_compressor_overhaul' => 'Air Compressor Overhaul',
            'Air_compressor_read_calve' => 'Air Compressor Read Calve Change',
            'fan_belt_check_or_change' => 'Fan Belt Check/Change',
            'tappet_setting' => 'Tappet Setting',
            'spring_cambering_check' => 'Spring Cambering Check',
            'voith_retarder_oil_change' => 'Voith Retarder Oil Change',
            'tyre_rotation' => 'Tyre Rotation',
            'error_code_edc_check' => 'Error Code EDC Check',
            'apda_mesh_cleaning' => 'APDA Mesh Cleaning',
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

        ?>

        <style>
            .mp-wrap {
                margin-top: 10px;
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                /* space between tables */
            }

            .mp-group-title {
                width: 100%;
                margin: 16px 0 8px;
                font-weight: 700;
                color: black;
                /* removed blue color for print */
            }

            .mp-program-table {
                flex: 1 0 calc(10% - 6px);
                /* target ~10 per row */
                max-width: calc(16.66% - 6px);
                /* min 6 per row if space */
                box-sizing: border-box;
            }

            .mp-program-head {
                font-weight: 700;
                text-align: center;
                font-size: 0.9rem;
                padding: 4px 6px !important;
                border: 1px solid #dee2e6;
                background: none !important;
                /* remove background */
            }

            .mp-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }

            .mp-table th,
            .mp-table td {
                border: 1px solid #dee2e6;
                padding: 1px 4px !important;
                vertical-align: middle;
                font-size: 0.55rem;
                word-wrap: break-word;
                text-align: center;
            }

            .mp-table thead th {
                font-weight: 600;
                text-align: center;
                background: none !important;
                /* remove background */
            }
        </style>

        <div class="mp-wrap">
            <?php
            function compute_total_km($bus_number, $ptype, $last_program_data, $kmpl_data)
            {
                $total_km = 0;
                $last_entry = $last_program_data[$bus_number][$ptype] ?? null;
                $program_date = $last_entry['date'] ?? null;

                if (!empty($program_date) && $program_date !== '0000-00-00') {
                    $start_date = date('Y-m-d', strtotime($program_date . ' +1 day'));
                    if (!empty($kmpl_data[$bus_number])) {
                        foreach ($kmpl_data[$bus_number] as $date => $km) {
                            if ($date >= $start_date) {
                                $total_km += $km;
                            }
                        }
                    }
                } else {
                    $last_km = $last_entry['km'] ?? 0;
                    $total_km = $last_km;
                    if (!empty($kmpl_data[$bus_number])) {
                        foreach ($kmpl_data[$bus_number] as $date => $km) {
                            if ($date > '2025-07-31') {
                                $total_km += $km;
                            }
                        }
                    }
                }
                return $total_km;
            }

            $tyre_rotation_data = []; // store tyre rotation separately

            foreach ($grouped_buses as $group_key => $bus_list_group) {
                list($make, $emission, $model_type) = explode('|', $group_key);

                $pm_result = mysqli_query($db, "SELECT * FROM program_master WHERE make = '$make' AND model = '$emission' AND model_type = '$model_type' LIMIT 1");
                if (!mysqli_num_rows($pm_result)) continue;
                $pm = mysqli_fetch_assoc($pm_result);

                // keep only numeric km values
                $programs = [];
                foreach ($pm as $prog => $km) {
                    if (in_array($prog, ['id', 'make', 'model', 'model_type', 'created_at', 'updated_at'])) continue;
                    if ($km !== null && $km !== '') $programs[$prog] = (int)$km;
                }
                if (empty($programs)) continue;

                $group_has_data = false;
                $group_tables_html = "";

                foreach ($program_labels as $ptype => $pname) {
                    if (!isset($programs[$ptype])) continue;

                    $prescribed_km = $programs[$ptype];
                    $rows = [];
                    foreach ($bus_list_group as $bus_number) {
                        $total_km = compute_total_km($bus_number, $ptype, $last_program_data, $kmpl_data);
                        $deviation = $total_km - $prescribed_km;

                        if ($deviation > 500 || ($deviation >= -5000 && $deviation <= 500)) {
                            $rows[] = [
                                'bus_number' => $bus_number,
                                'total_km'   => $total_km
                            ];
                        }
                    }

                    // ✅ Handle Tyre Rotation Check separately
                    if ($pname === "Tyre Rotation Check" && !empty($rows)) {
                        foreach ($rows as $r) {
                            $tyre_rotation_data[] = $r; // save for later table
                        }
                        continue; // skip normal printing
                    }

                    if (!empty($rows)) {
                        $group_has_data = true;
                        $group_tables_html .= "<div class='mp-program-table'>
                <table class='mp-table'>
                    <thead>
                        <tr><th class='mp-program-head' colspan='2'>" . htmlspecialchars($pname) . "<br>({$prescribed_km})</th></tr>
                        <tr><th>Vehicle</th><th>KM</th></tr>
                    </thead>
                    <tbody>";

                        foreach ($rows as $r) {
                            $group_tables_html .= "<tr>
                    <td>" . htmlspecialchars($r['bus_number']) . "</td>
                    <td>" . (int)$r['total_km'] . "</td>
                </tr>";
                        }

                        $group_tables_html .= "</tbody></table></div>";
                    }
                }

                if ($group_has_data) {
                    echo "<div class='mp-group-title'>Make: " . htmlspecialchars($make) . " | Emission: " . htmlspecialchars($emission) . " | Model Type: " . htmlspecialchars($model_type) . "</div>";
                    echo $group_tables_html;
                }
            }

            // ✅ Print Tyre Rotation Check table at the end
            if (!empty($tyre_rotation_data)) {
                $vehicle_numbers = array_column($tyre_rotation_data, 'bus_number');
                $kms = array_column($tyre_rotation_data, 'total_km');

                $total_vehicles = count($vehicle_numbers);
                $chunked_vehicle_numbers = array_chunk($vehicle_numbers, 18);
                $chunked_kms = array_chunk($kms, 18);

                foreach ($chunked_vehicle_numbers as $index => $vehicle_chunk) {
                    echo "<div><table class='mp-table'>";
                    echo "<thead>";
                    if ($index === 0) {
                        echo "<tr><th class='mp-program-head' colspan='" . (count($vehicle_chunk) + 1) . "'><h4><b>Tyre Rotation Check</b></h4></th></tr>";
                    }
                    echo "<tr><th>Vehicle No</th>";
                    foreach ($vehicle_chunk as $v) {
                        echo "<th>" . htmlspecialchars($v) . "</th>";
                    }
                    echo "</tr></thead><tbody>";

                    // KM row
                    echo "<tr> <th>KM</th>";
                    foreach ($chunked_kms[$index] as $km_val) {
                        echo "<td>" . (int)$km_val . "</td>";
                    }
                    echo "</tr>";

                    echo "</tbody></table></div>";
                }
            }


            ?>
        </div>

    <?php
} else {
    echo "<script>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
