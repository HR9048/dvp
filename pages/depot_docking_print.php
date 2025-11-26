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
                font-size: 10px;
                color: black !important;
            }

            @page {
                size: auto;
                margin: 0;
            }
        }
    </style>
    <button class="btn btn-secondary back-btn" onclick="goBack()">Back</button>
    <script>
        function goBack() {
            window.history.back();
        }

        // Auto print on page load
        window.onload = function() {
            window.print();

            // Optional: Go back after print dialog closes
            window.onafterprint = function() {
                window.history.back();
            };
        };
    </script>
<?php

    if (!isset($_POST['bus_number']) || empty($_POST['bus_number'])) {
        echo "<script>
        window.location = 'depot_dashboard.php';
    </script>";
        exit;
    }

    $bus_number = isset($_POST['bus_number']) ? trim($_POST['bus_number']) : '';
    $bus_number = htmlspecialchars($bus_number, ENT_QUOTES, 'UTF-8'); // safe for output

    $today = date('Y-m-d');
    echo "<div class='container1'>";
    // Prevent SQL injection
    if (in_array($_SESSION['DEPOT_ID'], ['1', '8', '12', '13', '14', '15'])) {
        $programstart_date = '2025-07-31';
        $formated_programstart_date = date('d-m-Y', strtotime($programstart_date));
        $reportstart_date = '2025-08-01';
        $formated_reportstart_date = date('d-m-Y', strtotime($reportstart_date));
    } elseif (in_array($_SESSION['DEPOT_ID'], ['2', '3', '4', '5', '6', '7', '9', '10', '11', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53'])) {
        $programstart_date = '2025-09-30';
        $formated_programstart_date = date('d-m-Y', strtotime($programstart_date));
        $reportstart_date = '2025-10-01';
        $formated_reportstart_date = date('d-m-Y', strtotime($reportstart_date));
    }
    $program_labels = [
        'docking' => 'Docking',
        'engine_oil_and_main_filter_change' => 'Engine Oil And Main Filter Change',
        'gear_box_oil_change' => 'Gear Box Oil Change',
        'housing_oil_change' => 'Housing Oil Change',
        'engine_coolant_change' => 'Engine Coolant Change',
        'power_steering_oil_and_filter_change' => 'Power Steering Oil And Filter Change',
        'fuel_filter_change' => 'Fuel Filter Change',
        'fuel_strainer_change' => 'Fuel Strainer Change',
        'fuel_filter_cum_water_seprator' => 'Fuel Filter Cum Water Seprator Change',
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
    $bus_result = mysqli_query($db, "SELECT bus_number, make, emission_norms, model_type FROM bus_registration WHERE bus_number = '$bus_number'");
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
        WHERE deleted != '1' AND bus_number IN ($bus_list) AND date > '$programstart_date' AND date <= '$today'
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

    foreach ($grouped_buses as $group_key => $bus_list_group) {
        list($make, $emission, $model_type) = explode('|', $group_key);

        $pm_result = mysqli_query($db, "SELECT * FROM program_master WHERE make = '$make' AND model = '$emission' AND model_type = '$model_type' LIMIT 1");
        if (!mysqli_num_rows($pm_result)) continue;

        $pm = mysqli_fetch_assoc($pm_result);

        $programs = [];
        foreach ($pm as $prog => $km) {
            if (in_array($prog, ['id', 'make', 'model', 'model_type', 'created_at', 'updated_at'])) continue;
            if (!is_null($km) && $km !== '') {
                $programs[$prog] = $km;
            }
        }
        if (empty($programs)) continue;

        $rows = [];
        foreach ($bus_list_group as $bus_number) {
            foreach ($programs as $ptype => $prescribed_km) {
                $last_entry = $last_program_data[$bus_number][$ptype] ?? null;
                $program_date = $last_entry['date'] ?? null;

                $total_km = 0;

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
                            if ($date > $programstart_date) {
                                $total_km += $km;
                            }
                        }
                    }
                }

                $deviation = $total_km - $prescribed_km;


                if ($deviation > 500) {
                    $color = 'bg-danger text-white'; // 🚩 Above prescribed km
                } elseif ($deviation >= -500 && $deviation <= 500) {
                    $color = 'bg-warning'; // ⚠️ Within acceptable tolerance
                } elseif ($deviation >= -5000 && $deviation <= -501) {
                    $color = 'bg-success text-white'; // ✅ Below prescribed km but within buffer
                } else {
                    continue; // Filter out others
                }

                $rows[] = [
                    'bus_number' => $bus_number,
                    'program_type' => $program_labels[$ptype] ?? ucfirst(str_replace('_', ' ', $ptype)),
                    'total_km' => $total_km,
                    'last_km' => isset($last_entry['km']) ? $last_entry['km'] : 'Not Done',
                    'prescribed_km' => $prescribed_km,
                    'difference' => $deviation,
                ];
            }
        }

        if (!empty($rows)) {
            $any_rows_printed = true; // ✅ Set flag to true since we printed some data

            echo "<h4 class='mt-4 text-primary'>
    Bus number: $bus_number | Make: $make | Emission: $emission | Model Type: $model_type
</h4>";

            echo "<div><table class='table table-bordered' style='border-collapse: collapse;'>";
            echo "<thead>
<tr>
    <th>Sl No</th>
    <th>Program Name</th>
    <th>Prescribed KM</th>
    <th>Program KM</th>
    <th>Difference</th>
    <th>Action</th>
</tr>
</thead><tbody>";

            $slno = 1;
            foreach ($rows as $r) {
                $bus = $r['bus_number'];
                $difference = (int)$r['difference'];
                $action = "";

                // Apply conditions with refined labels
                if ($difference < -5000) {
                    $action = "<span class='badge bg-secondary'>No Action Required</span>";
                } elseif ($difference >= -5000 && $difference <= -500) {
                    $action = "<span class='badge bg-warning text-dark'>Upcoming Maintenance</span>";
                } elseif ($difference > -500 && $difference < 500) {
                    $action = "<span class='badge bg-info text-dark'>Attention Needed</span>";
                } elseif ($difference >= 500) {
                    $action = "<span class='badge bg-danger'>Maintenance Due</span>";
                }

                echo "<tr>
        <td style='padding:3px;'>{$slno}</td>
        <td style='padding:3px;'>{$r['program_type']}</td>
        <td style='padding:3px;'>{$r['prescribed_km']}</td>
        <td style='padding:3px;'>{$r['total_km']}</td>
        <td style='padding:3px;'>{$difference}</td>
        <td style='padding:3px;'>{$action}</td>
    </tr>";

                $slno++;
            }

            echo "</tbody></table></div>";
        }
    }

    echo "<h4 class='mt-4 text-primary'>Vehicle Last 30 Days KMPL and Defect Sheet</h4>";

    // Fetch data from database
    $last30days = date('Y-m-d', strtotime('-30 days'));
    $sqlforkmplofbus = "SELECT * FROM vehicle_kmpl WHERE bus_number = '$bus_number' AND deleted != 1 and date between '$last30days' and '$today' order by date DESC";
    $result = mysqli_query($db, $sqlforkmplofbus);

    if ($result && mysqli_num_rows($result) > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-bordered table-sm' style='border-collapse: collapse;'>";
        echo "<thead class='table-light'>
        <tr>
            <th>Sl No</th>
            <th>Date</th>
            <th>KM</th>
            <th>HSD</th>
            <th>KMPL</th>
            <th>Defects Noticed</th>
        </tr>
    </thead><tbody>";

        $slno = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
            <td style='padding:3px;'>{$slno}</td>
            <td style='padding:3px;'>" . date('d-m-Y', strtotime($row['date'])) . "</td>
            <td style='padding:3px;'>{$row['km_operated']}</td>
            <td style='padding:3px;'>{$row['hsd']}</td>
            <td style='padding:3px;'>{$row['kmpl']}</td>
            <td style='padding:3px;'>{$row['remarks']}</td>
        </tr>";
            $slno++;
        }

        echo "</tbody></table></div><br><br><br>";

        echo '<div style="display: flex; justify-content: space-between;">
            <h2 style="text-align:left; padding: 2%; margin: 0;">JA</h2>
            <h2 style="text-align:center; padding: 2%; margin: 0;">CM/AWS</h2>
            <h2 style="text-align:right; padding: 2%; margin: 0;">DM</h2>
        </div>';
    } else {
        echo "<div class='alert alert-warning'>No KMPL or defect records found for bus <strong>$bus_number</strong> in the last 30 days.</div>";
    }
    echo "</div>";
} else {
    echo "<script>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
