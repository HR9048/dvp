<?php
include '../includes/connection.php';
include '../pages/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['html' => 'Invalid JSON input']);
        exit;
    }

    $selectedDate = $data['date'];
    $formattedDate = DateTime::createFromFormat('Y-m-d', $selectedDate)->format('d/m/Y');
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $query = "SELECT
        sm.sch_key_no AS sch_no,
        sm.sch_abbr AS description,
        sm.sch_dep_time AS dep_time,
        sc.name AS service_class_name,
        svo.vehicle_no AS bus_number_went,
        svo.driver_1_name,
        svo.driver_2_name,
        svo.driver_token_no_1,
        svo.driver_token_no_2,
        svo.conductor_name,
        svo.conductor_token_no,
        IFNULL(svo.dep_time, 'SNO') AS departed_time,
        sm.sch_arr_time AS arr_time,
        CASE
            WHEN svo.dep_time IS NULL AND svo.arr_time IS NULL THEN 'SNO'
            WHEN svo.dep_time IS NOT NULL AND svo.arr_time IS NULL THEN 'SNA'
            ELSE IFNULL(svo.arr_time, 'SNO')
        END AS act_arr_time,
        IFNULL(bfd.bus_number_1, 'N/A') AS bus_number_1,
        IFNULL(bfd.bus_number_2, 'N/A') AS bus_number_2,
        IFNULL(bfd.additional_bus_number, 'N/A') AS bus_number_3,
        IFNULL(cfd.driver_token_1, 'N/A') AS driver1_token,
        IFNULL(cfd.driver_token_2, 'N/A') AS driver2_token,
        IFNULL(cfd.driver_token_3, 'N/A') AS driver3_token,
        IFNULL(cfd.driver_token_4, 'N/A') AS driver4_token,
        IFNULL(cfd.driver_token_5, 'N/A') AS driver5_token,
        IFNULL(cfd.driver_token_6, 'N/A') AS driver6_token,
        IFNULL(cfd.conductor_token_1, 'N/A') AS conductor1_token,
        IFNULL(cfd.conductor_token_2, 'N/A') AS conductor2_token,
        IFNULL(cfd.conductor_token_3, 'N/A') AS conductor3_token,
        IFNULL(svo.bus_allotted_status, 'N/A') AS bus_status,
        IFNULL(svo.driver_1_allotted_status, 'N/A') AS driver1_status,
        IFNULL(svo.driver_2_allotted_status, 'N/A') AS driver2_status,
        IFNULL(svo.conductor_alloted_status, 'N/A') AS conductor_status
    FROM
        schedule_master sm
    LEFT JOIN
        sch_veh_out svo ON sm.sch_key_no = svo.sch_no
        AND svo.departed_date = ?
        AND svo.division_id = ?
        AND svo.depot_id = ?
        AND svo.schedule_status != 6
    LEFT JOIN
        bus_fix_data bfd ON sm.sch_key_no = bfd.sch_no
        AND (DATE(bfd.from_date) = ? OR (? BETWEEN DATE(bfd.from_date) AND IFNULL(DATE(bfd.to_date), ?)))
        AND bfd.division_id = ?
        AND bfd.depot_id = ?
        AND bfd.status = 0
    LEFT JOIN
        crew_fix_data cfd ON sm.sch_key_no = cfd.sch_no
        AND (DATE(cfd.from_date) = ? OR (? BETWEEN DATE(cfd.from_date) AND IFNULL(DATE(cfd.to_date), ?)))
        AND cfd.division_id = ?
        AND cfd.depot_id = ?
        AND cfd.status = 0
    LEFT JOIN
        service_class sc ON sm.service_class_id = sc.id
    WHERE
        sm.depot_id = ?
        AND sm.division_id = ?
    ORDER BY dep_time";

    $stmt = $db->prepare($query);
    if ($stmt === false) {
        echo json_encode(['html' => 'Error in preparing statement: ' . $db->error]);
        exit;
    }

    $stmt->bind_param('siisssiisssiiii', $selectedDate, $division_id, $depot_id, $selectedDate, $selectedDate, $selectedDate, $division_id, $depot_id, $selectedDate, $selectedDate, $selectedDate, $division_id, $depot_id, $depot_id, $division_id);
    if (!$stmt->execute()) {
        echo json_encode(['html' => 'Error in executing statement: ' . $stmt->error]);
        exit;
    }

    $result = $stmt->get_result();
    if ($result === false) {
        echo json_encode(['html' => 'Error in fetching results: ' . $stmt->error]);
        exit;
    }

    $html = '<table><br><br>';
    $html .= '<h2 style="text-align: center;">Schedule Operation on Date: ' . $formattedDate . '</h2><br>';
    $html .= '<p style="color: red;">Note * : (SNO = Schedule not Operated),(SNA = Schedule not Arrived), (NA = Not Alloted), (N/A = Not Applicable)</p>';
    $html .= '<tr><th>Sl No</th><th>Schedule Key No</th><th>Description</th><th>Sch Dep Time</th><th>Dep Time</th><th>Sch Arr Time</th><th>Arr Time</th><th>Service Class</th><th>Buses</th><th>Drivers</th><th>Conductors</th><th>Bus Status</th><th>Driver 1 Status</th><th>Driver 2 Status</th><th>Conductor Status</th></tr>';

    $sl_no = 1;
    while ($row = $result->fetch_assoc()) {
        $buses = [];
        for ($i = 1; $i <= 3; $i++) {
            $bus_number = htmlspecialchars($row["bus_number_{$i}"]);
            if ($bus_number !== 'N/A') {
                $buses[] = "$bus_number";
            }
        }
        $bus_display = !empty($buses) ? implode(', ', $buses) : 'NA';

        // Drivers
        $drivers = [];
        for ($i = 1; $i <= 6; $i++) {
            $driver_token = htmlspecialchars($row["driver{$i}_token"]);
            if ($driver_token !== 'N/A') {
                $drivers[] = "$driver_token";
            }
        }
        $drivers_display = !empty($drivers) ? implode(', ', $drivers) : 'NA';

        // Conductors
        $conductors = [];
        for ($i = 1; $i <= 3; $i++) {
            $conductor_token = htmlspecialchars($row["conductor{$i}_token"]);
            if ($conductor_token !== 'N/A') {
                $conductors[] = "$conductor_token";
            }
        }
        $conductors_display = !empty($conductors) ? implode(', ', $conductors) : 'NA';

        // Bus status logic
        if ($row['bus_status'] == '0') {
            $bus_status_display = '<span>✅</span>';
        } elseif ($row['bus_status'] == '1') {
            $bus_status_display = htmlspecialchars($row['bus_number_went']);
        } else {
            $bus_status_display = 'N/A';
        }

        // Driver 1 status logic
        if ($row['driver1_status'] == '0') {
            $driver1_status_display = '<span>✅</span>';
        } elseif ($row['driver1_status'] == '1') {
            $driver1_status_display = htmlspecialchars($row['driver_1_name']) . ' (' . htmlspecialchars($row['driver_token_no_1']) . ')';
        } else {
            $driver1_status_display = 'N/A';
        }

        // Driver 2 status logic
        if ($row['driver2_status'] == '0') {
            $driver2_status_display = '<span">✅</span>';
        } elseif ($row['driver2_status'] == '1') {
            $driver2_status_display = htmlspecialchars($row['driver_2_name']) . ' (' . htmlspecialchars($row['driver_token_no_2']) . ')';
        } else {
            $driver2_status_display = 'N/A';
        }

        // Conductor status logic
        if ($row['conductor_status'] == '0') {
            $conductor_status_display = '<span>✅</span>';
        } elseif ($row['conductor_status'] == '1') {
            $conductor_status_display = htmlspecialchars($row['conductor_name']) . ' (' . htmlspecialchars($row['conductor_token_no']) . ')';
        } else {
            $conductor_status_display = 'N/A';
        }

        $html .= '<tr>';
        $html .= '<td>' . $sl_no++ . '</td>';
        $html .= '<td>' . htmlspecialchars($row['sch_no']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['description']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['dep_time']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['departed_time']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['arr_time']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['act_arr_time']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['service_class_name']) . '</td>';
        $html .= '<td>' . $bus_display . '</td>';
        $html .= '<td>' . $drivers_display . '</td>';
        $html .= '<td>' . $conductors_display . '</td>';
        $html .= '<td>' . $bus_status_display . '</td>';
        $html .= '<td>' . $driver1_status_display . '</td>';
        $html .= '<td>' . $driver2_status_display . '</td>';
        $html .= '<td>' . $conductor_status_display . '</td>';
        $html .= '</tr>';
    }

    $html .= '</table>';
    echo json_encode(['html' => $html]);
}else {
    header('Location: ../pages/login.php');
    exit;
}

?>