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

$api_key = $headers['X-API-KEY'];
*/
$api_key = '20170472417';
// Verify API key in the database
$stmt = $db->prepare("SELECT id FROM api_keys WHERE api_key = ?");
$stmt->bind_param('s', $api_key);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die(json_encode(["message" => "Invalid API Key"]));
}

$sql = "SELECT division, depot, division_id, depot_id 
        FROM location 
        WHERE division_id NOT IN (0,10) 
        AND depot != 'DIVISION' 
        ORDER BY division_id, depot_id";

$depots_1 = ['1', '8', '12', '13', '14', '15'];
$depots_2 = ['2', '3', '4', '5', '6', '7', '9', '10', '11', '16', '17', '18', '19', '20', 
             '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', 
             '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', 
             '47', '48', '49', '50', '51', '52', '53'];

$final_data = [];
$today = date('Y-m-d');

// --- Fetch Program Master once ---
$program_master = [];
$query_program_master = "SELECT make, model, model_type, docking, engine_oil_and_main_filter_change 
                         FROM program_master";
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

// --- Main depot + vehicle data ---
if ($stmt = $db->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $depot_id = $row['depot_id'];

            // Determine program start date by depot
            if (in_array($depot_id, $depots_1)) {
                $programstart_date = '2025-07-31';
                $formated_programstart_date = '01-08-2025';
            } elseif (in_array($depot_id, $depots_2)) {
                $programstart_date = '2025-09-30';
                $formated_programstart_date = '01-10-2025';
            } else {
                $programstart_date = 'N/A';
                $formated_programstart_date = 'N/A';
            }

            // --- Initialize depot summary ---
            $depot_summary = [
                "total_buses" => 0,
                "pending_docking" => 0,
                "delayed_docking" => 0,
                "pending_engine" => 0,
                "delayed_engine" => 0
            ];

            // Fetch vehicle data for this depot
            $vehicles = [];
            $query_vehicles = "SELECT bus_number, make, emission_norms AS model, model_type
                               FROM bus_registration 
                               WHERE depot_name = '$depot_id'";
            $result_vehicle = mysqli_query($db, $query_vehicles);

            if ($result_vehicle && mysqli_num_rows($result_vehicle) > 0) {
                while ($v = mysqli_fetch_assoc($result_vehicle)) {
                    $depot_summary["total_buses"]++;

                    $bus_number = $v['bus_number'];
                    $make = strtoupper(trim($v['make']));
                    $model = strtoupper(trim($v['model']));
                    $model_type = strtoupper(trim($v['model_type']));
                    $key = "$make|$model|$model_type";

                    // --- Get program master limits ---
                    $docking_limit = $program_master[$key]['docking'] ?? 0;
                    $engine_limit = $program_master[$key]['engine_oil_and_main_filter_change'] ?? 0;

                    // --- Fetch latest program data ---
                    $sql_docking = "SELECT program_date, program_completed_km 
                                    FROM program_data 
                                    WHERE bus_number = '$bus_number' 
                                      AND program_type = 'docking'
                                    ORDER BY program_date DESC 
                                    LIMIT 1";
                    $res_docking = mysqli_query($db, $sql_docking);
                    $last_docking = ($res_docking && mysqli_num_rows($res_docking) > 0)
                        ? mysqli_fetch_assoc($res_docking)
                        : null;

                    $sql_engine = "SELECT program_date, program_completed_km 
                                   FROM program_data 
                                   WHERE bus_number = '$bus_number' 
                                     AND program_type = 'engine_oil_and_main_filter_change'
                                   ORDER BY program_date DESC 
                                   LIMIT 1";
                    $res_engine = mysqli_query($db, $sql_engine);
                    $last_engine = ($res_engine && mysqli_num_rows($res_engine) > 0)
                        ? mysqli_fetch_assoc($res_engine)
                        : null;

                    // ==========================
                    // 🚍 DOCKING CALCULATION
                    // ==========================
                    $dock_ref_date = $last_docking['program_date'] ?? $programstart_date;
                    $dock_ref_km = $last_docking['program_completed_km'] ?? 0;
                    $dock_start = $last_docking ? date('Y-m-d', strtotime($dock_ref_date . ' +1 day')) : $programstart_date;
                    $dock_end = $today;

                    $dock_operated_km = 0;
                    $sql_operated_docking = "
                        SELECT IFNULL(SUM(km_operated), 0) AS total_km 
                        FROM vehicle_kmpl 
                        WHERE bus_number = '$bus_number' 
                          AND deleted != 1 
                          AND date BETWEEN '$dock_start' AND '$dock_end'";
                    $res_op_dock = mysqli_query($db, $sql_operated_docking);
                    if ($res_op_dock && mysqli_num_rows($res_op_dock) > 0) {
                        $dock_operated_km = (float)mysqli_fetch_assoc($res_op_dock)['total_km'];
                    }

                    $dock_total_km = empty($last_docking['program_date'])
                        ? $dock_ref_km + $dock_operated_km
                        : $dock_operated_km;

                    $dock_diff = $docking_limit - $dock_total_km;

                    // Status logic (delayed counts as pending too)
                    if ($dock_diff <= 500 && $dock_diff >= -500) {
                        $status_docking = "Pending";
                        $depot_summary["pending_docking"]++;
                    } elseif ($dock_diff < -500) {
                        $status_docking = "Delayed";
                        $depot_summary["delayed_docking"]++;
                        $depot_summary["pending_docking"]++; // also count as pending
                    } else {
                        $status_docking = "OK";
                    }

                    // ==========================
                    // 🛢️ ENGINE OIL CALCULATION
                    // ==========================
                    $eng_ref_date = $last_engine['program_date'] ?? $programstart_date;
                    $eng_ref_km = $last_engine['program_completed_km'] ?? 0;
                    $eng_start = $last_engine ? date('Y-m-d', strtotime($eng_ref_date . ' +1 day')) : $programstart_date;
                    $eng_end = $today;

                    $eng_operated_km = 0;
                    $sql_operated_engine = "
                        SELECT IFNULL(SUM(km_operated), 0) AS total_km 
                        FROM vehicle_kmpl 
                        WHERE bus_number = '$bus_number' 
                          AND deleted != 1 
                          AND date BETWEEN '$eng_start' AND '$eng_end'";
                    $res_op_eng = mysqli_query($db, $sql_operated_engine);
                    if ($res_op_eng && mysqli_num_rows($res_op_eng) > 0) {
                        $eng_operated_km = (float)mysqli_fetch_assoc($res_op_eng)['total_km'];
                    }

                    $eng_total_km = empty($last_engine['program_date'])
                        ? $eng_ref_km + $eng_operated_km
                        : $eng_operated_km;

                    $eng_diff = $engine_limit - $eng_total_km;

                    if ($eng_diff <= 500 && $eng_diff >= 0) {
                        $status_engine = "Pending";
                        $depot_summary["pending_engine"]++;
                    } elseif ($eng_diff < -500) {
                        $status_engine = "Delayed";
                        $depot_summary["delayed_engine"]++;
                        $depot_summary["pending_engine"]++; // also count as pending
                    } else {
                        $status_engine = "OK";
                    }

                    $vehicles[] = [
                        "bus_number" => $bus_number,
                        "make" => $make,
                        "model" => $model,
                        "model_type" => $model_type,
                        "docking" => [
                            "limit_km" => $docking_limit,
                            "total_km" => $dock_total_km,
                            "difference" => $dock_diff,
                            "status" => $status_docking
                        ],
                        "engine_oil_change" => [
                            "limit_km" => $engine_limit,
                            "total_km" => $eng_total_km,
                            "difference" => $eng_diff,
                            "status" => $status_engine
                        ]
                    ];
                }
            }

            $final_data[] = [
                "division" => $row['division'],
                "depot" => $row['depot'],
                "division_id" => $row['division_id'],
                "depot_id" => $depot_id,
                "program_start_date" => $programstart_date,
                "summary" => $depot_summary,
                "vehicles" => $vehicles
            ];
            // --- Prepare UPSERT for summary table ---
$ins = $db->prepare("
    INSERT INTO program_summary_daily
      (summary_date, division_id, depot_id, total_buses, pending_docking, delayed_docking, pending_engine, delayed_engine)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      total_buses = VALUES(total_buses),
      pending_docking = VALUES(pending_docking),
      delayed_docking = VALUES(delayed_docking),
      pending_engine = VALUES(pending_engine),
      delayed_engine = VALUES(delayed_engine)
");
            $summary_date = date('Y-m-d');
            $division_id = $row['division_id'];
            $depot_id = $depot_id;
            $total_buses = $depot_summary["total_buses"];
            $pending_docking = $depot_summary["pending_docking"];
            $delayed_docking = $depot_summary["delayed_docking"];
            $pending_engine = $depot_summary["pending_engine"];
            $delayed_engine = $depot_summary["delayed_engine"];

            $ins->bind_param(
                'siiiiiii',
                $summary_date,
                $division_id,
                $depot_id,
                $total_buses,
                $pending_docking,
                $delayed_docking,
                $pending_engine,
                $delayed_engine
            );
            $ins->execute();
            $ins->close();
        }

        echo json_encode(["depots" => $final_data], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(["message" => "No Location data found"]);
    }

    $stmt->close();



} else {
    echo json_encode(["message" => "Invalid request"]);
}
?>