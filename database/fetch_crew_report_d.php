<?php
// Include database connection and session
include '../includes/connection.php';
include '../pages/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $selectedDate = $data['date'];
    $formattedDate = DateTime::createFromFormat('Y-m-d', $selectedDate)->format('d/m/Y');

    // Fetch data from the database
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $query = "SELECT
        sm.sch_key_no AS sch_no,
        sm.sch_abbr AS description,
        sm.sch_dep_time AS dep_time,
        svo.driver_1_name,
        svo.driver_2_name,
        svo.driver_token_no_1,
        svo.driver_token_no_2,
        svo.conductor_name,
        svo.conductor_token_no,
        IFNULL(svo.dep_time, 'RN') AS departed_time,
        sm.sch_arr_time AS arr_time,
        IFNULL(svo.arr_time, 'RN') AS act_arr_time,
        IFNULL(sm.driver_token_1, 'N/A') AS driver1_token,
        IFNULL(sm.driver_token_2, 'N/A') AS driver2_token,
        IFNULL(sm.driver_token_3, 'N/A') AS driver3_token,
        IFNULL(sm.driver_token_4, 'N/A') AS driver4_token,
        IFNULL(sm.driver_token_5, 'N/A') AS driver5_token,
        IFNULL(sm.driver_token_6, 'N/A') AS driver6_token,
        IFNULL(sm.conductor_token_1, 'N/A') AS conductor1_token,
        IFNULL(sm.conductor_token_2, 'N/A') AS conductor2_token,
        IFNULL(sm.conductor_token_3, 'N/A') AS conductor3_token,
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
    WHERE
        sm.depot_id = ?
        AND sm.division_id = ?
    ORDER BY dep_time";

    $stmt = $db->prepare($query);
    $stmt->bind_param('siiii', $selectedDate, $division_id, $depot_id, $depot_id, $division_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $html = '<table><br><br>';
    $html .= '<h2 style="text-align: center;">Schedule Operation on Date: ' . $formattedDate . ' </h2><br>';
    $html .= '<p style="color: red;">Note * =(RN = Route not Operated),(N/A = Not Applicatble)</p>';
    $html .= '<tr><th>Sl No</th><th>Schedule Key No</th><th>Description</th><th>Sch Dep Time</th><th>Dep Time</th><th>Sch Arr Time</th><th>Arr Time</th><th>Drivers</th><th>Conductors</th><th>Driver 1 Status</th><th>Driver 2 Status</th><th>Conductor Status</th></tr>';

    $sl_no = 1;
    while ($row = $result->fetch_assoc()) {
        // Drivers
        $drivers = [];
        for ($i = 1; $i <= 6; $i++) {
            $driver_token = htmlspecialchars($row["driver{$i}_token"]);
            if ($driver_token !== 'N/A') {
                $drivers[] = "$driver_token";
            }
        }
        $drivers_display = !empty($drivers) ? implode(', ', $drivers) : 'N/A';

        // Conductors
        $conductors = [];
        for ($i = 1; $i <= 3; $i++) {
            $conductor_token = htmlspecialchars($row["conductor{$i}_token"]);
            if ($conductor_token !== 'N/A') {
                $conductors[] = "$conductor_token";
            }
        }
        $conductors_display = !empty($conductors) ? implode(', ', $conductors) : 'N/A';

        // Driver 1 status logic
        if ($row['driver1_status'] == '0') {
            $driver1_status_display = '<span style="color:green;">&#x2714;</span>';
        } elseif ($row['driver1_status'] == '1') {
            $driver1_status_display = htmlspecialchars($row['driver_1_name']) . ' (' . htmlspecialchars($row['driver_token_no_1']) . ')';
        } else {
            $driver1_status_display = 'N/A';
        }

        // Driver 2 status logic
        if ($row['driver2_status'] == '0') {
            $driver2_status_display = '<span style="color:green;">&#x2714;</span>';
        } elseif ($row['driver2_status'] == '1') {
            $driver2_status_display = htmlspecialchars($row['driver_2_name']) . ' (' . htmlspecialchars($row['driver_token_no_2']) . ')';
        } else {
            $driver2_status_display = 'N/A';
        }

        // Conductor status logic
        if ($row['conductor_status'] == '0') {
            $conductor_status_display = '<span style="color:green;">&#x2714;</span>';
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
        $html .= '<td>' . $drivers_display . '</td>';
        $html .= '<td>' . $conductors_display . '</td>';
        $html .= '<td>' . $driver1_status_display . '</td>';
        $html .= '<td>' . $driver2_status_display . '</td>';
        $html .= '<td>' . $conductor_status_display . '</td>';
        $html .= '</tr>';
    }

    $html .= '</table>';

    echo json_encode(['html' => $html]);
} else {
    echo json_encode(['html' => 'Invalid request method']);
}
