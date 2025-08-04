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
        WHERE deleted != '1' AND bus_number IN ($bus_list) AND date > '2025-07-30' AND date <= '$today'
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
                            if ($date > '2025-07-30') {
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
                    'class' => $color
                ];
            }
        }

        if (!empty($rows)) {
            $any_rows_printed = true; // ✅ Set flag to true since we printed some data

            echo "<h4 class='mt-4 text-primary'>Make: $make | Emission: $emission | Model Type: $model_type</h4>";
            echo "<div class='table-responsive'><table class='table table-bordered'>";
            echo "<thead>
        <tr>
            <th>Bus Number</th>
            <th>Program Name</th>
            <th>Program KM</th>
            <th>Prescribed KM</th>
            <th>Difference</th>
            <th>Actions</th>
        </tr></thead><tbody>";

            foreach ($rows as $r) {
                $bus = $r['bus_number'];
                $ptype_raw = array_search($r['program_type'], $program_labels) ?: strtolower(str_replace(' ', '_', $r['program_type']));
                echo "<tr class='{$r['class']}'>
                <td>{$bus}</td>
                <td>{$r['program_type']}</td>
                <td>{$r['total_km']}</td>
                <td>{$r['prescribed_km']}</td>
                <td>{$r['difference']}</td>
                <td>";
                if ($r['class'] !== 'bg-success text-white') {
                    echo "<button class='btn btn-sm btn-primary' onclick=\"openProgramModal('{$bus}', '{$ptype_raw}', '{$r['program_type']}')\">Update</button>";
                }
                echo "</td>

            </tr>";
            }

            echo "</tbody></table></div>";
        }
    }

    // ✅ Final fallback message if no rows were printed
    if (!$any_rows_printed) {
        echo "<div class='alert alert-info text-center mt-4'><strong>None of the vehicle programs are present.</strong></div>";
    }

?>
    <!-- Program Update Modal -->
    <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="programForm" onsubmit="submitProgramUpdate(event)">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Program</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="modalBusNumber">
                        <input type="hidden" id="modalProgramType">

                        <p><strong>Bus Number:</strong> <span id="modalBusDisplay"></span></p>
                        <p><strong>Program:</strong> <span id="modalProgramLabel"></span></p>


                        <div class="mb-3">
                            <label for="program_date" class="form-label">Maintenance Done Date</label>
                            <input type="date" class="form-control" id="program_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Submit</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
    $today = new DateTime();
    $fromdate = clone $today;
    $fromdate->modify('-2 days');

    // Format dates for JavaScript display (d-m-Y)
    $serverDateFormatted = $today->format('d-m-Y');
    $fromDateFormatted = $fromdate->format('d-m-Y');

    // Also keep machine-readable format (Y-m-d) for comparison
    $serverDate = $today->format('Y-m-d');
    $fromDate = $fromdate->format('Y-m-d');
    ?>
    <script>
        let programModal = new bootstrap.Modal(document.getElementById('programModal'));

        function openProgramModal(bus_number, program_type, program_label) {
            document.getElementById("modalBusNumber").value = bus_number;
            document.getElementById("modalProgramType").value = program_type;
            document.getElementById("modalBusDisplay").innerText = bus_number;
            document.getElementById("modalProgramLabel").innerText = program_label;
            document.getElementById("program_date").value = new Date().toISOString().split('T')[0];
            programModal.show();
        }

        function submitProgramUpdate(event) {
    event.preventDefault();

    const bus_number = $("#modalBusNumber").val();
    const program_type = $("#modalProgramType").val();
    const program_date = $("#program_date").val();
    const program_label = $("#modalProgramLabel").text();
    const $submitBtn = $("#programForm button[type='submit']");
    const format_program_date = new Date(program_date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    if (!bus_number || !program_type || !program_date) {
        Swal.fire("Error", "Please fill all fields.", "error");
        return;
    }

    // Disable the submit button
    $submitBtn.prop("disabled", true).text("Submitting...");

    // Step 1: Fetch program KM via AJAX
    $.ajax({
        url: "../includes/backend_data.php",
        method: "POST",
        data: {
            action: "get_program_km_for_bus",
            bus_number: bus_number,
            program_type: program_type,
            program_date: program_date
        },
        dataType: "json",
        success: function(response) {
            if (!response.success) {
                Swal.fire("Error", response.message || "Failed to fetch KM data.", "error");
                $submitBtn.prop("disabled", false).text("Submit");
                return;
            }

            const program_completed_km = response.program_km;

            // Step 2: Show confirmation dialog
            Swal.fire({
                title: 'Confirm Update',
                html: `Do you want to save <strong>${program_completed_km} KM</strong> for <strong>${program_label}</strong> on Date: <strong>${format_program_date}</strong> For<br>Bus Number: <strong>${bus_number}</strong>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Save it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Step 3: Submit final data
                    $.ajax({
                        url: "../includes/backend_data.php",
                        method: "POST",
                        data: {
                            action: "save_program_data",
                            bus_number: bus_number,
                            program_type: program_type,
                            program_completed_km: program_completed_km,
                            program_date: program_date
                        },
                        success: function(response) {
                            Swal.fire("Success", response, "success").then(() => {
                                programModal.hide();
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire("Error", "An error occurred while saving data.", "error");
                        },
                        complete: function() {
                            $submitBtn.prop("disabled", false).text("Submit");
                        }
                    });
                } else {
                    $submitBtn.prop("disabled", false).text("Submit");
                }
            });
        },
        error: function() {
            Swal.fire("Error", "Failed to fetch program KM.", "error");
            $submitBtn.prop("disabled", false).text("Submit");
        }
    });
}





        const serverDate = '<?= $serverDate ?>'; // For comparisons
        const allowedFromDate = '<?= $fromDate ?>';
        const serverDateDisplay = '<?= $serverDateFormatted ?>'; // For display
        const allowedFromDateDisplay = '<?= $fromDateFormatted ?>';

        document.getElementById('program_date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date(serverDate);
            const fromdate = new Date(allowedFromDate);

            selectedDate.setHours(0, 0, 0, 0);
            today.setHours(0, 0, 0, 0);
            fromdate.setHours(0, 0, 0, 0);

            if (selectedDate > today) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date',
                    text: 'Future dates are not allowed.',
                });
                this.value = '';
                return;
            }

            if (selectedDate < fromdate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Date',
                    text: `Allowed date range is ${allowedFromDateDisplay} to ${serverDateDisplay}.`,
                    confirmButtonText: 'OK'
                });
                this.value = '';
                return;
            }
        });
    </script>


<?php
} else {
    echo "<script>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
