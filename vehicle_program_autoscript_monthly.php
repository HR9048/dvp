<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');
header("Content-Type: application/json");
ini_set('max_execution_time', 0);
include 'includes/connection.php';

/*/ Check if API key is provided
$headers = getallheaders();
if (!isset($headers['X-API-KEY'])) {
    die(json_encode(["message" => "API Key missing"]));
}

$api_key = $headers['X-API-KEY'];*/
$api_key = '20170472417';

// Verify API key in the database
$stmt = $db->prepare("SELECT id FROM api_keys WHERE api_key = ?");
$stmt->bind_param('s', $api_key);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die(json_encode(["message" => "Invalid API Key"]));
    exit;
} else {
    // API key is valid, proceed with the script

    echo "API Key valid\n";

    echo "Monthly Vehicle Program Report generation Started for month: " . date('F Y', strtotime('first day of last month')) . "\n";


    $current_day = date('j');

    if ($current_day >= 4 && $current_day <= 21) {

        echo "Current day is between 4th and 20th\n";

        // Array for missing depots
        $missing_depots = [];

        // Fetch all depot IDs from location table
        $depots = $db->query("SELECT depot_id FROM location WHERE division_id NOT IN ('0', '10') AND depot != 'DIVISION' ORDER BY depot_id ASC");
        $report_month = date('m', strtotime('first day of last month'));
        $report_year  = date('Y', strtotime('first day of last month'));
        if ($depots->num_rows > 0) {

            // Depot groups for program start date logic
            $depots_1 = ['1', '8', '12', '13', '14', '15'];
            $depots_2 = ['2', '3', '4', '5', '6', '7', '9', '10', '11', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53'];

            // Load Program Master Data
            $program_master = [];
            $query_program_master = "SELECT make, model, model_type, docking, engine_oil_and_main_filter_change FROM program_master";
            $result_program_master = mysqli_query($db, $query_program_master);
            if ($result_program_master && mysqli_num_rows($result_program_master) > 0) {
                while ($p = mysqli_fetch_assoc($result_program_master)) {
                    $key = strtoupper(trim($p['make'])) . '|' . strtoupper(trim($p['model'])) . '|' . strtoupper(trim($p['model_type']));
                    $program_master[$key] = [
                        'docking' => (float)$p['docking'],
                        'engine_oil_and_main_filter_change' => (float)$p['engine_oil_and_main_filter_change']
                    ];
                }
            }

            // print the program master array
            print_r($program_master);

            while ($row = $depots->fetch_assoc()) {

                $depot_id = $row['depot_id'];

                // Check if the monthly summary already exists
                $stmt = $db->prepare("SELECT id FROM program_summary_monthly WHERE depot_id = ? AND report_month = ? AND report_year = ?");
                $stmt->bind_param('iii', $depot_id, $report_month, $report_year);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 0) {
                    $missing_depots[] = $depot_id;
                }

                $stmt->close();
            }
        }
        


        // If all depots already generated
        if (empty($missing_depots)) {
            echo "Monthly report already generated for all depots.\n";
            exit;
        } else {
            echo "Missing depots: " . implode(", ", $missing_depots) . "\n";

            // Generate reports for missing depots
            foreach ($missing_depots as $depot_id) {
                $dock_before = 0;
                $dock_last   = 0;

                $oil_before  = 0;
                $oil_last    = 0;


                echo "---------------------------------------------\n";
                echo "Generating report for depot ID: $depot_id\n";

                // Assign program start date based on depot group
                if (in_array($depot_id, $depots_1)) {

                    $programstart_date = '2025-08-01';
                    $formated_programstart_date = '01-08-2025';
                } elseif (in_array($depot_id, $depots_2)) {

                    $programstart_date = '2025-10-01';
                    $formated_programstart_date = '01-10-2025';
                } else {

                    $programstart_date = 'N/A';
                    $formated_programstart_date = 'N/A';
                }

                echo "Program Start Date (DB Format): $programstart_date\n";
                echo "Program Start Date (Display): $formated_programstart_date\n";

                // Fetch buses for this depot excluding transferred buses (Main Depot Buses)
                $sqlfor_buses = "SELECT br.bus_number, br.make, br.emission_norms AS model, br.model_type FROM bus_registration br WHERE  br.depot_name = ? AND br.bus_number NOT IN ( SELECT btd.bus_number FROM bus_transfer_data btd WHERE btd.order_date >= DATE_FORMAT(CURRENT_DATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND btd.to_depot = ? );";

                $stmt = $db->prepare($sqlfor_buses);
                $stmt->bind_param('ii', $depot_id, $depot_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $buses_main = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();


                // Fetch transferred buses TO this depot (Incoming Transfers)
                $sqlfor_transbuses = "SELECT btd.bus_number, br.make, br.emission_norms as model, br.model_type from bus_transfer_data btd left join bus_registration br on br.bus_number= btd.bus_number where btd.from_depot != br.depot_name AND btd.order_date >= DATE_FORMAT(CURRENT_DATE() - INTERVAL 1 MONTH, '%Y-%m-01') and btd.from_depot = ?";
                $stmt2 = $db->prepare($sqlfor_transbuses);
                $stmt2->bind_param('i', $depot_id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $buses_transfer = $result2->fetch_all(MYSQLI_ASSOC);
                $stmt2->close();


                // Fetch scrapped buses from this depot
                $sqlfor_scrapbuses = "SELECT bus_number,make,emission_norms as model, model_type FROM bus_scrap_data WHERE order_date >= DATE_FORMAT(CURRENT_DATE() - INTERVAL 1 MONTH, '%Y-%m-01') and depot = ?;";

                $stmt3 = $db->prepare($sqlfor_scrapbuses);
                $stmt3->bind_param('i', $depot_id);
                $stmt3->execute();
                $result3 = $stmt3->get_result();
                $buses_scrap = $result3->fetch_all(MYSQLI_ASSOC);
                $stmt3->close();


                // ---------------------------------------------
                // MERGE ALL BUS LISTS
                // ---------------------------------------------
                $all_buses = array_merge($buses_main, $buses_transfer, $buses_scrap);


                // ---------------------------------------------
                // DISPLAY FINAL LIST
                // ---------------------------------------------
                echo "Buses for depot ID $depot_id:\n";


                $prev_month_first = date('Y-m-01', strtotime('first day of last month'));
                $prev_month_last  = date('Y-m-t', strtotime('last day of last month'));

                foreach ($all_buses as $bus) {

                    echo "- Bus Number: {$bus['bus_number']}, Make: {$bus['make']}, Model: {$bus['model']}, Model Type: {$bus['model_type']}\n";

                    $bus_no = $bus['bus_number'];

                    // PROGRAM TYPES
                    $programs = ['engine_oil_and_main_filter_change', 'docking'];

                    foreach ($programs as $ptype) {

                        // If programstart_date not available → skip this program
                        if ($programstart_date == 'N/A') {
                            echo "   Program: $ptype → No Program Start Date (Skipping)\n";
                            continue;
                        }

                        // -------------------------------------------------------
                        // 1. Fetch BEFORE previous month record from program_data
                        // -------------------------------------------------------
                        $sql_before = "SELECT program_completed_km, program_date
                       FROM program_data
                       WHERE bus_number = ? 
                         AND program_type = ?
                         AND (program_date < ? OR program_date IS NULL)
                       ORDER BY program_date DESC
                       LIMIT 1";

                        $stmt1 = $db->prepare($sql_before);
                        $stmt1->bind_param("sss", $bus_no, $ptype, $prev_month_first);
                        $stmt1->execute();
                        $res_before = $stmt1->get_result()->fetch_assoc();  // <-- now defined
                        $stmt1->close();

                        // Determine BEFORE-MONTH start date for KM calculation
                        if ($res_before && !empty($res_before['program_date'])) {
                            $before_start = date('Y-m-d', strtotime($res_before['program_date'] . ' +1 day'));
                        } else {
                            // If no record or program_date is NULL → use programstart_date from page
                            $before_start = $programstart_date;
                        }
                        //before end date is previous month first -1 day
                        $before_end = date('Y-m-d', strtotime($prev_month_first . ' -1 day'));


                        // -------------------------------------------------------
                        // 2. Fetch LAST month record (up to previous month last)
                        /*/ -------------------------------------------------------
                        $sql_last = "SELECT program_completed_km, program_date
                     FROM program_data
                     WHERE bus_number = ?
                       AND program_type = ?
                       AND (program_date <= ? OR program_date IS NULL)
                     ORDER BY program_date DESC
                     LIMIT 1";

                        $stmt2 = $db->prepare($sql_last);
                        $stmt2->bind_param("sss", $bus_no, $ptype, $prev_month_last);
                        $stmt2->execute();
                        $res_last = $stmt2->get_result()->fetch_assoc();   // <-- now defined
                        $stmt2->close();*/
                        $res_last = $res_before;

                        // Determine LAST-MONTH start date for KM calculation
                        if ($res_last && !empty($res_last['program_date'])) {
                            $last_start = date('Y-m-d', strtotime($res_last['program_date'] . ' +1 day'));
                        } else {
                            // If no record or program_date is NULL → use programstart_date from page
                            $last_start = $programstart_date;
                        }

                        $last_end = $prev_month_last;


                        // -----------------------------------------
                        // 3. KM OPERATED CALCULATIONS
                        // -----------------------------------------

                        // KM Before Previous Month
                        $sql_km_before = "SELECT SUM(km_operated) AS total_km
                          FROM vehicle_kmpl
                          WHERE bus_number = ?
                            AND date BETWEEN ? AND ? and deleted !=1";

                        $stmt3 = $db->prepare($sql_km_before);
                        $stmt3->bind_param("sss", $bus_no, $before_start, $before_end);
                        $stmt3->execute();
                        $row_km_before = $stmt3->get_result()->fetch_assoc();
                        $km_before = $row_km_before['total_km'] ?? 0;
                        $stmt3->close();


                        // KM For Previous Month
                        $sql_km_last = "SELECT SUM(km_operated) AS total_km
                        FROM vehicle_kmpl
                        WHERE bus_number = ?
                          AND date BETWEEN ? AND ? and deleted !=1";

                        $stmt4 = $db->prepare($sql_km_last);
                        $stmt4->bind_param("sss", $bus_no, $last_start, $last_end);
                        $stmt4->execute();
                        $row_km_last = $stmt4->get_result()->fetch_assoc();
                        $km_last = $row_km_last['total_km'] ?? 0;
                        $stmt4->close();


                        // -----------------------------------------
                        // 4. OUTPUT RESULTS (INCLUDING EXTRA INFO)
                        // -----------------------------------------
                        echo "   Program: $ptype\n";

                        // --- Before Previous Month Block ---
                        echo "     Before Prev Month (< $prev_month_first):\n";

                        if ($res_before) {
                            echo "       Program KM: {$res_before['program_completed_km']}\n";
                            echo "       Program Date: " . ($res_before['program_date'] ?? 'NULL') . "\n";
                        } else {
                            echo "       Program KM: No record\n";
                            echo "       Program Date: No record\n";
                        }

                        echo "       ➤ KM Calculation Range: $before_start → $before_end\n";
                        echo "       ➤ KM Operated: $km_before KM\n";
                        // calculate total km operated before previous month                            
                        if ($res_before['program_date'] != null) {
                            $total_km_before = $km_before;
                        } else {
                            $total_km_before = $km_before + ($res_before['program_completed_km'] ?? 0);
                        }

                        echo "       ➤ Total KM Before Previous Month: $total_km_before KM\n";
                        // ---------------------------------------------------------
                        // PRESCRIBED KM COMPARISON FOR BEFORE PREVIOUS MONTH
                        // ---------------------------------------------------------

                        $make  = strtoupper(trim($bus['make']));
                        $model = strtoupper(trim($bus['model']));
                        $mtype = strtoupper(trim($bus['model_type']));
                        $key = "$make|$model|$mtype";

                        // -------------------------------
                        // PRESCRIBED KM CHECK (BEFORE PM)
                        // -------------------------------
                        if (isset($program_master[$key][$ptype])) {

                            $prescribed_km = $program_master[$key][$ptype];
                            $remaining_before = $prescribed_km - $total_km_before;

                            echo "       ➤ Prescribed KM: $prescribed_km KM\n";
                            echo "       ➤ Remaining KM Before Previous Month: $remaining_before KM\n";

                            // Docking
                            if ($ptype == 'docking' && $remaining_before <= 500) {
                                $dock_before++;
                                echo "       ➤ Docking Status Before PM: ❗ DUE\n";
                            }

                            // Engine Oil & Filter Change
                            if ($ptype == 'engine_oil_and_main_filter_change' && $remaining_before <= 500) {
                                $oil_before++;
                                echo "       ➤ Engine Oil Status Before PM: ❗ DUE\n";
                            }
                        }



                        // --- Last Month Block ---
                        echo "     Last Month ($prev_month_first → $prev_month_last):\n";

                        if ($res_last) {
                            echo "       Program KM: {$res_last['program_completed_km']}\n";
                            echo "       Program Date: " . ($res_last['program_date'] ?? 'NULL') . "\n";
                        } else {
                            echo "       Program KM: No record\n";
                            echo "       Program Date: No record\n";
                        }

                        echo "       ➤ KM Calculation Range: $last_start → $last_end\n";
                        echo "       ➤ KM Operated: $km_last KM\n";
                        // calculate total km operated till last month
                        if ($res_last['program_date'] != null) {
                            $total_km_last = $km_last;
                        } else {
                            $total_km_last = $km_last + ($res_last['program_completed_km'] ?? 0);
                        }
                        echo "       ➤ Total KM Till Last Month: $total_km_last KM\n";

                        // -------------------------------
                        // PRESCRIBED KM CHECK (LAST PM)
                        // -------------------------------
                        if (isset($program_master[$key][$ptype])) {

                            $prescribed_km = $program_master[$key][$ptype];
                            $remaining_last = $prescribed_km - $total_km_last;

                            echo "       ➤ Remaining KM Till Last Month: $remaining_last KM\n";

                            // Docking
                            if ($ptype == 'docking' && $remaining_last <= 500) {
                                $dock_last++;
                                echo "       ➤ Docking Status Last PM: ❗ DUE\n";
                            }

                            // Engine Oil & Filter Change
                            if ($ptype == 'engine_oil_and_main_filter_change' && $remaining_last <= 500) {
                                $oil_last++;
                                echo "       ➤ Engine Oil Status Last PM: ❗ DUE\n";
                            }
                        }
                    }

                    echo "---------------------------------------------------------\n";
                }





                echo "\n=============== DEPOT SUMMARY (Depot ID: $depot_id) ===============\n";

                echo "DOCKING:\n";
                echo "   Due as on 1st day of previous month : $dock_before\n";
                echo "   Due as on last day of previous month: $dock_last\n\n";

                echo "ENGINE OIL & MAIN FILTER:\n";
                echo "   Due as on 1st day of previous month : $oil_before\n";
                echo "   Due as on last day of previous month: $oil_last\n";

                echo "===================================================================\n\n";

                // ===========================================
                // INSERT SUMMARY INTO program_summary_monthly
                // ===========================================

                $dock_end = $dock_last - $dock_before;
                $oil_end = $oil_last - $oil_before;
                $total_docking = $dock_last;
                $total_eoc     = $oil_last;

                //find division_id for the depot
                $division_id = null;
                $div_result = $db->query("SELECT division_id FROM location WHERE depot_id = $depot_id LIMIT 1");
                if ($div_result && $div_result->num_rows > 0) {
                    $div_row = $div_result->fetch_assoc();
                    $division_id = $div_row['division_id'];
                }


                $insert_sql = "INSERT INTO program_summary_monthly 
    (
        report_month, report_year, division_id, depot_id,
        previous_month_due_docking, Current_month_due_docking, total_to_be_attend_docking,
        previous_month_due_eoc, Current_month_due_eoc, total_to_be_attend_eoc
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt_insert = $db->prepare($insert_sql);
                $stmt_insert->bind_param(
                    "iiiiiiiiii",
                    $report_month,
                    $report_year,
                    $division_id,           // You already have division_id earlier in your script
                    $depot_id,
                    $dock_before,
                    $dock_end,
                    $total_docking,
                    $oil_before,
                    $oil_end,
                    $total_eoc
                );

                if ($stmt_insert->execute()) {
                    echo "Record inserted into program_summary_monthly successfully.\n";
                } else {
                    echo "Error inserting data: " . $stmt_insert->error . "\n";
                }

                $stmt_insert->close();
            }
        }
    } else {
        echo "Current day is not between 4th and 20th. Exiting.\n";
        exit;
    }
}
