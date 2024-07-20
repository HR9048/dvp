<?php
include 'session.php'; // Assuming this file sets up session variables
include '../includes/connection.php'; // Assuming this file sets up database connection

if (isset($_POST['sch_no'])) {
    $sch_no = $_POST['sch_no'];

    // Query to fetch schedule details and schedule_status from both tables
    $scheduleQuery = "SELECT 
    sm.*,  -- Get all columns from schedule_master
    MAX(svo.schedule_status) AS schedule_status,
    MAX(svo.departed_date) AS departed_date,
    CASE 
        WHEN MAX(svo1.schedule_status) = 1 OR (COUNT(CASE WHEN or1.status = 'off_road' THEN 1 END) > 0) THEN NULL 
        ELSE MAX(sm.bus_number_1) 
    END AS bus_number_1,
    CASE 
        WHEN MAX(svo2.schedule_status) = 1 OR (COUNT(CASE WHEN or2.status = 'off_road' THEN 1 END) > 0) THEN NULL 
        ELSE MAX(sm.bus_number_2) 
    END AS bus_number_2,
    CASE 
        WHEN MAX(svo3.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_token_1)
    END AS driver_token_1,
    CASE 
        WHEN MAX(svo3.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_pf_1)
    END AS driver_pf_1,
    CASE 
        WHEN MAX(svo3.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_name_1)
    END AS driver_name_1,
    CASE 
        WHEN MAX(svo4.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_token_2)
    END AS driver_token_2,
    CASE 
        WHEN MAX(svo4.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_pf_2)
    END AS driver_pf_2,
    CASE 
        WHEN MAX(svo4.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_name_2)
    END AS driver_name_2,
    CASE 
        WHEN MAX(svo5.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_token_3)
    END AS driver_token_3,
    CASE 
        WHEN MAX(svo5.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_pf_3)
    END AS driver_pf_3,
    CASE 
        WHEN MAX(svo5.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_name_3)
    END AS driver_name_3,
    CASE 
        WHEN MAX(svo6.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_token_4)
    END AS driver_token_4,
    CASE 
        WHEN MAX(svo6.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_pf_4)
    END AS driver_pf_4,
    CASE 
        WHEN MAX(svo6.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_name_4)
    END AS driver_name_4,
    CASE 
        WHEN MAX(svo7.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_token_5)
    END AS driver_token_5,
    CASE 
        WHEN MAX(svo7.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_pf_5)
    END AS driver_pf_5,
    CASE 
        WHEN MAX(svo7.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_name_5)
    END AS driver_name_5,
    CASE 
        WHEN MAX(svo8.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_token_6)
    END AS driver_token_6,
    CASE 
        WHEN MAX(svo8.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_pf_6)
    END AS driver_pf_6,
    CASE 
        WHEN MAX(svo8.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.driver_name_6)
    END AS driver_name_6,
    CASE 
        WHEN MAX(svo9.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_token_1)
    END AS conductor_token_1,
    CASE 
        WHEN MAX(svo9.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_pf_1)
    END AS conductor_pf_1,
    CASE 
        WHEN MAX(svo9.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_name_1)
    END AS conductor_name_1,
    CASE 
        WHEN MAX(svo10.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_token_2)
    END AS conductor_token_2,
    CASE 
        WHEN MAX(svo10.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_pf_2)
    END AS conductor_pf_2,
    CASE 
        WHEN MAX(svo10.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_name_2)
    END AS conductor_name_2,
    CASE 
        WHEN MAX(svo11.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_token_3)
    END AS conductor_token_3,
    CASE 
        WHEN MAX(svo11.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_pf_3)
    END AS conductor_pf_3,
    CASE 
        WHEN MAX(svo11.schedule_status) = 1 THEN NULL
        ELSE MAX(sm.conductor_name_3)
    END AS conductor_name_3
FROM 
    schedule_master sm
LEFT JOIN (
    SELECT *
    FROM sch_veh_out
    WHERE sch_no = '$sch_no'
    ORDER BY id DESC
    LIMIT 1
) svo ON sm.sch_key_no = svo.sch_no
LEFT JOIN sch_veh_out svo1 ON sm.bus_number_1 = svo1.vehicle_no AND svo1.schedule_status = 1
LEFT JOIN sch_veh_out svo2 ON sm.bus_number_2 = svo2.vehicle_no AND svo2.schedule_status = 1
LEFT JOIN off_road_data or1 ON sm.bus_number_1 = or1.bus_number
LEFT JOIN off_road_data or2 ON sm.bus_number_2 = or2.bus_number
LEFT JOIN sch_veh_out svo3 ON sm.driver_pf_1 = svo3.driver_1_pf AND svo3.schedule_status = 1 OR sm.driver_pf_2 = svo3.driver_2_pf AND svo3.schedule_status = 1
LEFT JOIN sch_veh_out svo4 ON sm.driver_pf_2 = svo4.driver_1_pf AND svo4.schedule_status = 1 OR sm.driver_pf_2 = svo4.driver_2_pf AND svo4.schedule_status = 1
LEFT JOIN sch_veh_out svo5 ON sm.driver_pf_3 = svo5.driver_1_pf AND svo5.schedule_status = 1 OR sm.driver_pf_3 = svo5.driver_2_pf AND svo5.schedule_status = 1
LEFT JOIN sch_veh_out svo6 ON sm.driver_pf_4 = svo6.driver_1_pf AND svo6.schedule_status = 1 OR sm.driver_pf_4 = svo6.driver_2_pf AND svo6.schedule_status = 1
LEFT JOIN sch_veh_out svo7 ON sm.driver_pf_5 = svo7.driver_1_pf AND svo7.schedule_status = 1 OR sm.driver_pf_5 = svo7.driver_2_pf AND svo7.schedule_status = 1
LEFT JOIN sch_veh_out svo8 ON sm.driver_pf_6 = svo8.driver_1_pf AND svo8.schedule_status = 1 OR sm.driver_pf_6 = svo8.driver_2_pf AND svo8.schedule_status = 1
LEFT JOIN sch_veh_out svo9 ON sm.conductor_pf_1 = svo9.conductor_pf_no AND svo9.schedule_status = 1
LEFT JOIN sch_veh_out svo10 ON sm.conductor_pf_2 = svo10.conductor_pf_no AND svo10.schedule_status = 1
LEFT JOIN sch_veh_out svo11 ON sm.conductor_pf_3 = svo11.conductor_pf_no AND svo11.schedule_status = 1
WHERE 
    sm.sch_key_no = '$sch_no'
    AND sm.depot_id = '{$_SESSION['DEPOT_ID']}'
    AND sm.division_id = '{$_SESSION['DIVISION_ID']}'
GROUP BY 
    sm.sch_key_no";

    $scheduleResult = mysqli_query($db, $scheduleQuery);

    if ($scheduleResult && mysqli_num_rows($scheduleResult) > 0) {
        $scheduleDetails = mysqli_fetch_assoc($scheduleResult);
        echo json_encode($scheduleDetails);
    } else {
        echo json_encode(null); // No schedule with schedule_status = 1 found
    }
} else {
    echo json_encode(null); // No sch_no parameter passed
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
