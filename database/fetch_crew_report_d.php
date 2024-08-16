<?php
// Include database connection and session
include '../includes/connection.php';
include '../pages/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $selectedDate = $data['date'];

    // Fetch data from the database
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $query = "
    SELECT
        svo.sch_no,
        sm.sch_abbr AS description,
        sm.sch_dep_time AS dep_time,
        svo.dep_time AS departed_time,
        sm.sch_arr_time AS arr_time,
        svo.act_arr_time,
        svo.driver_1_name AS driver1,
        svo.driver_2_name AS driver2,
        svo.conductor_name AS conductor,
        svo.driver_1_allotted_status AS driver1_status,
        svo.driver_2_allotted_status AS driver2_status,
        svo.conductor_alloted_status AS conductor_status
    FROM
        sch_veh_out svo
    JOIN
        schedule_master sm ON svo.sch_no = sm.sch_key_no
    WHERE
        svo.departed_date = ? AND 
        svo.division_id = ? AND 
        svo.depot_id = ?";

    $stmt = $db->prepare($query);
    $stmt->bind_param('sii', $selectedDate, $division_id, $depot_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $html = '<table>';
    $html .= '<tr><th>Sl No</th><th>Schedule Key No</th><th>Description</th><th>Sch Dep Time</th><th>Dep Time</th><th>sch Arr Time</th><th>Arr Time</th><th>Driver 1</th><th>Driver 2</th><th>Conductor</th><th>Driver 1 Status</th><th>Driver 2 Status</th><th>Conductor Status</th></tr>';

    $sl_no = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        $html .= '<td>' . $sl_no++ . '</td>';
        $html .= '<td>' . htmlspecialchars($row['sch_no']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['description']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['dep_time']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['departed_time']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['arr_time']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['act_arr_time']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['driver1']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['driver2']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['conductor']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['driver1_status']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['driver2_status']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['conductor_status']) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</table>';

    echo json_encode(['html' => $html]);
} else {
    echo json_encode(['html' => 'Invalid request method']);
}
