<?php
include 'session.php'; // Assuming this file sets up session variables
include '../includes/connection.php'; // Assuming this file sets up database connection
confirm_logged_in();
if (isset($_POST['sch_no'])) {
    $sch_no = $_POST['sch_no'];

    // Step 1 Query: Fetch rows from sch_veh_out with status 1 and 2
    $step1Query = "
        SELECT *
        FROM sch_veh_out
        WHERE sch_no = '$sch_no'
          AND division_id = '{$_SESSION['DIVISION_ID']}'
          AND depot_id = '{$_SESSION['DEPOT_ID']}'
          AND schedule_status IN (1, 2,3,4)
        ORDER BY id DESC
    ";

    $step1Result = mysqli_query($db, $step1Query);
    $step1Data = mysqli_fetch_all($step1Result, MYSQLI_ASSOC);

    // Step 2 Query: Retrieve the schedule data from schedule_master
    $scheduleQuery = "
        SELECT *
        FROM schedule_master
        WHERE sch_key_no = '$sch_no'
          AND division_id = '{$_SESSION['DIVISION_ID']}'
          AND depot_id = '{$_SESSION['DEPOT_ID']}'
    ";

    $scheduleResult = mysqli_query($db, $scheduleQuery);
    $scheduleData = mysqli_fetch_assoc($scheduleResult);

    if (!$scheduleData) {
        echo json_encode(null); // No schedule data found
        exit;
    }

    // Initialize finalData with scheduleData
    $finalData = $scheduleData;

    // Flags to determine if any bus number is nullified
    $busNumber1Nullified = false;
    $busNumber2Nullified = false;
    $AdditionalbusNullified = false;

    if (!empty($step1Data)) {
        // Process each row from step 1 data
        foreach ($step1Data as $row) {
            if ($row['schedule_status'] == 1 || $row['schedule_status'] == 2 || $row['schedule_status'] == 6) {
                // Nullify bus numbers if they match
                if ($scheduleData['bus_number_1'] == $row['vehicle_no']) {
                    $finalData['bus_number_1'] = NULL;
                    $busNumber1Nullified = true;
                }
                if ($scheduleData['bus_number_2'] == $row['vehicle_no']) {
                    $finalData['bus_number_2'] = NULL;
                    $busNumber2Nullified = true;
                }
                if ($scheduleData['additional_bus_number'] == $row['vehicle_no']) {
                    $finalData['additional_bus_number'] = NULL;
                    $AdditionalbusNullified = true;
                }

                // Nullify driver and conductor details if they match
                if ($scheduleData['driver_pf_1'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_1'] == $row['driver_1_pf']) {
                    $finalData['driver_pf_1'] = NULL;
                    $finalData['driver_token_1'] = NULL;
                    $finalData['driver_name_1'] = NULL;
                }
                if ($scheduleData['driver_pf_1'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_1'] == $row['driver_2_pf']) {
                    $finalData['driver_pf_1'] = NULL;
                    $finalData['driver_token_1'] = NULL;
                    $finalData['driver_name_1'] = NULL;
                }
                if ($scheduleData['driver_pf_1'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_1'] == $row['conductor_pf_no']) {
                    $finalData['driver_pf_1'] = NULL;
                    $finalData['driver_token_1'] = NULL;
                    $finalData['driver_name_1'] = NULL;
                }
                if ($scheduleData['driver_pf_2'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_2'] == $row['driver_1_pf']) {
                    $finalData['driver_pf_2'] = NULL;
                    $finalData['driver_token_2'] = NULL;
                    $finalData['driver_name_2'] = NULL;
                }
                if ($scheduleData['driver_pf_2'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_2'] == $row['driver_2_pf']) {
                    $finalData['driver_pf_2'] = NULL;
                    $finalData['driver_token_2'] = NULL;
                    $finalData['driver_name_2'] = NULL;
                }
                if ($scheduleData['driver_pf_2'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_2'] == $row['conductor_pf_no']) {
                    $finalData['driver_pf_2'] = NULL;
                    $finalData['driver_token_2'] = NULL;
                    $finalData['driver_name_2'] = NULL;
                }
                if ($scheduleData['driver_pf_3'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_3'] == $row['driver_1_pf']) {
                    $finalData['driver_pf_3'] = NULL;
                    $finalData['driver_token_3'] = NULL;
                    $finalData['driver_name_3'] = NULL;
                }
                if ($scheduleData['driver_pf_3'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_3'] == $row['driver_2_pf']) {
                    $finalData['driver_pf_3'] = NULL;
                    $finalData['driver_token_3'] = NULL;
                    $finalData['driver_name_3'] = NULL;
                }
                if ($scheduleData['driver_pf_3'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_3'] == $row['conductor_pf_no']) {
                    $finalData['driver_pf_3'] = NULL;
                    $finalData['driver_token_3'] = NULL;
                    $finalData['driver_name_3'] = NULL;
                }
                if ($scheduleData['driver_pf_4'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_4'] == $row['driver_1_pf']) {
                    $finalData['driver_pf_4'] = NULL;
                    $finalData['driver_token_4'] = NULL;
                    $finalData['driver_name_4'] = NULL;
                }
                if ($scheduleData['driver_pf_4'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_4'] == $row['driver_2_pf']) {
                    $finalData['driver_pf_4'] = NULL;
                    $finalData['driver_token_4'] = NULL;
                    $finalData['driver_name_4'] = NULL;
                }
                if ($scheduleData['driver_pf_4'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_4'] == $row['conductor_pf_no']) {
                    $finalData['driver_pf_4'] = NULL;
                    $finalData['driver_token_4'] = NULL;
                    $finalData['driver_name_4'] = NULL;
                }
                if ($scheduleData['driver_pf_5'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_5'] == $row['driver_1_pf']) {
                    $finalData['driver_pf_5'] = NULL;
                    $finalData['driver_token_5'] = NULL;
                    $finalData['driver_name_5'] = NULL;
                }
                if ($scheduleData['driver_pf_5'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_5'] == $row['driver_2_pf']) {
                    $finalData['driver_pf_5'] = NULL;
                    $finalData['driver_token_5'] = NULL;
                    $finalData['driver_name_5'] = NULL;
                }
                if ($scheduleData['driver_pf_5'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_5'] == $row['conductor_pf_no']) {
                    $finalData['driver_pf_5'] = NULL;
                    $finalData['driver_token_5'] = NULL;
                    $finalData['driver_name_5'] = NULL;
                }
                if ($scheduleData['driver_pf_6'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_6'] == $row['driver_1_pf']) {
                    $finalData['driver_pf_6'] = NULL;
                    $finalData['driver_token_6'] = NULL;
                    $finalData['driver_name_6'] = NULL;
                }
                if ($scheduleData['driver_pf_6'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_6'] == $row['driver_2_pf']) {
                    $finalData['driver_pf_6'] = NULL;
                    $finalData['driver_token_6'] = NULL;
                    $finalData['driver_name_6'] = NULL;
                }
                if ($scheduleData['driver_pf_6'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_6'] == $row['conductor_pf_no']) {
                    $finalData['driver_pf_6'] = NULL;
                    $finalData['driver_token_6'] = NULL;
                    $finalData['driver_name_6'] = NULL;
                }
                if ($scheduleData['conductor_pf_1'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['conductor_pf_1'] == $row['driver_1_pf']) {
                    $finalData['conductor_pf_1'] = NULL;
                    $finalData['conductor_token_1'] = NULL;
                    $finalData['conductor_name_1'] = NULL;
                }
                if ($scheduleData['conductor_pf_1'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['conductor_pf_1'] == $row['driver_2_pf']) {
                    $finalData['conductor_pf_1'] = NULL;
                    $finalData['conductor_token_1'] = NULL;
                    $finalData['conductor_name_1'] = NULL;
                }
                if ($scheduleData['conductor_pf_1'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['conductor_pf_1'] == $row['conductor_pf_no']) {
                    $finalData['conductor_pf_1'] = NULL;
                    $finalData['conductor_token_1'] = NULL;
                    $finalData['conductor_name_1'] = NULL;
                }
                if ($scheduleData['conductor_pf_2'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['conductor_pf_2'] == $row['driver_1_pf']) {
                    $finalData['conductor_pf_2'] = NULL;
                    $finalData['conductor_token_2'] = NULL;
                    $finalData['conductor_name_2'] = NULL;
                }
                if ($scheduleData['conductor_pf_2'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['conductor_pf_2'] == $row['driver_2_pf']) {
                    $finalData['conductor_pf_2'] = NULL;
                    $finalData['conductor_token_2'] = NULL;
                    $finalData['conductor_name_2'] = NULL;
                }
                if ($scheduleData['conductor_pf_2'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['conductor_pf_2'] == $row['conductor_pf_no']) {
                    $finalData['conductor_pf_2'] = NULL;
                    $finalData['conductor_token_2'] = NULL;
                    $finalData['conductor_name_2'] = NULL;
                }
                if ($scheduleData['conductor_pf_3'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['conductor_pf_3'] == $row['driver_1_pf']) {
                    $finalData['conductor_pf_3'] = NULL;
                    $finalData['conductor_token_3'] = NULL;
                    $finalData['conductor_name_3'] = NULL;
                }
                if ($scheduleData['conductor_pf_3'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['conductor_pf_3'] == $row['driver_2_pf']) {
                    $finalData['conductor_pf_3'] = NULL;
                    $finalData['conductor_token_3'] = NULL;
                    $finalData['conductor_name_3'] = NULL;
                }
                if ($scheduleData['conductor_pf_3'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['conductor_pf_3'] == $row['conductor_pf_no']) {
                    $finalData['conductor_pf_3'] = NULL;
                    $finalData['conductor_token_3'] = NULL;
                    $finalData['conductor_name_3'] = NULL;
                }
            } elseif ($row['schedule_status'] == 3 || $row['schedule_status'] == 4) {
                // Nullify bus numbers if they match
                if ($scheduleData['bus_number_1'] == $row['vehicle_no']) {
                    $finalData['bus_number_1'] = NULL;
                    $busNumber1Nullified = true;
                }
                if ($scheduleData['bus_number_2'] == $row['vehicle_no']) {
                    $finalData['bus_number_2'] = NULL;
                    $busNumber2Nullified = true;
                }
                if ($scheduleData['additional_bus_number'] == $row['vehicle_no']) {
                    $finalData['additional_bus_number'] = NULL;
                    $AdditionalbusNullified = true;
                }
            }
        }
    }

    // Step 3 Query: Check off-road data for bus numbers not nullified
    $offRoadQuery = "
   SELECT bus_number
   FROM off_road_data
   WHERE (bus_number = '{$scheduleData['bus_number_1']}'
          OR bus_number = '{$scheduleData['bus_number_2']}'
          OR bus_number = '{$scheduleData['additional_bus_number']}')
     AND status = 'off_road'
";

    $offRoadResult = mysqli_query($db, $offRoadQuery);
    $offRoadData = mysqli_fetch_all($offRoadResult, MYSQLI_ASSOC);

    if (!empty($offRoadData)) {
        foreach ($offRoadData as $offRoad) {
            if ($offRoad['bus_number'] == $scheduleData['bus_number_1']) {
                $finalData['bus_number_1'] = NULL;
            }
            if ($offRoad['bus_number'] == $scheduleData['bus_number_2']) {
                $finalData['bus_number_2'] = NULL;
            }
            if (isset($scheduleData['additional_bus_number']) && $offRoad['bus_number'] == $scheduleData['additional_bus_number']) {
                $finalData['additional_bus_number'] = NULL;
            }
        }
    }

    echo json_encode($finalData);
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

// SELECT sm.*, svo.schedule_status,svo.departed_date
//         FROM schedule_master sm
//         LEFT JOIN (
//             SELECT *
//             FROM sch_veh_out
//             WHERE sch_no = '$sch_no'
//             ORDER BY id DESC
//             LIMIT 1
//         ) svo ON sm.sch_key_no = svo.sch_no
//         WHERE sm.sch_key_no = '$sch_no'
//           AND sm.depot_id = '{$_SESSION['DEPOT_ID']}'
//           AND sm.division_id = '{$_SESSION['DIVISION_ID']}'";

// SELECT 
//     sm.*,
//     svo.schedule_status,
//     svo.departed_date,
//     CASE 
//         WHEN svo1.schedule_status = 1 THEN NULL 
//         ELSE sm.bus_number_1 
//     END AS bus_number_1,
//     CASE 
//         WHEN svo2.schedule_status = 1 THEN NULL 
//         ELSE sm.bus_number_2 
//     END AS bus_number_2
// FROM 
//     schedule_master sm
// LEFT JOIN (
//     SELECT *
//     FROM sch_veh_out
//     WHERE sch_no = '$sch_no'
//     ORDER BY id DESC
//     LIMIT 1
// ) svo ON sm.sch_key_no = svo.sch_no
// LEFT JOIN sch_veh_out svo1 ON sm.bus_number_1 = svo1.vehicle_no AND svo1.schedule_status = 1
// LEFT JOIN sch_veh_out svo2 ON sm.bus_number_2 = svo2.vehicle_no AND svo2.schedule_status = 1
// WHERE 
//     sm.sch_key_no = '$sch_no'
//     AND sm.depot_id = '{$_SESSION['DEPOT_ID']}'
//     AND sm.division_id = '{$_SESSION['DIVISION_ID']}'


// SELECT 
//     sm.*,  -- Get all columns from schedule_master
//     MAX(svo.schedule_status) AS schedule_status,
//     MAX(svo.departed_date) AS departed_date,
//     CASE 
//         WHEN MAX(svo1.schedule_status) = 1 OR (COUNT(CASE WHEN or1.status = 'off_road' THEN 1 END) > 0) THEN NULL 
//         ELSE MAX(sm.bus_number_1) 
//     END AS bus_number_1,
//     CASE 
//         WHEN MAX(svo2.schedule_status) = 1 OR (COUNT(CASE WHEN or2.status = 'off_road' THEN 1 END) > 0) THEN NULL 
//         ELSE MAX(sm.bus_number_2) 
//     END AS bus_number_2
// FROM 
//     schedule_master sm
// LEFT JOIN (
//     SELECT *
//     FROM sch_veh_out
//     WHERE sch_no = '$sch_no'
//     ORDER BY id DESC
//     LIMIT 1
// ) svo ON sm.sch_key_no = svo.sch_no
// LEFT JOIN sch_veh_out svo1 ON sm.bus_number_1 = svo1.vehicle_no AND svo1.schedule_status = 1
// LEFT JOIN sch_veh_out svo2 ON sm.bus_number_2 = svo2.vehicle_no AND svo2.schedule_status = 1
// LEFT JOIN off_road_data or1 ON sm.bus_number_1 = or1.bus_number
// LEFT JOIN off_road_data or2 ON sm.bus_number_2 = or2.bus_number
// WHERE 
//     sm.sch_key_no = '$sch_no'
//     AND sm.depot_id = '{$_SESSION['DEPOT_ID']}'
//     AND sm.division_id = '{$_SESSION['DIVISION_ID']}'
// GROUP BY 
//     sm.sch_key_no
?>