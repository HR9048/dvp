<?php
include '../includes/connection.php';
include '../pages/session.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$month = $data['month'];
$year = $data['year'];

// Get session variables
$depot_id = $_SESSION['DEPOT_ID'];
$division_id = $_SESSION['DIVISION_ID'];
$depotname = $_SESSION['KMPL_DEPOT'];
// Calculate start and end dates of the selected month
$start_date = date("$year-$month-01");
$end_date = date("Y-m-t", strtotime($start_date));

// Adjust the end date if the selected month is the current month
if ($year == date('Y') && $month == date('m')) {
    $end_date = date('Y-m-d');
}

// Query to get all schedules
$query = "SELECT 
        sm.sch_key_no AS sch_no,
        sm.sch_dep_time,
        sm.bus_number_1,
        sm.bus_number_2,
        sm.driver_token_1,
        sm.driver_token_2,
        sm.driver_token_3,
        sm.driver_token_4,
        sm.driver_token_5,
        sm.driver_token_6,
        sm.sch_arr_time,
        DATE(svo.departed_date) AS date,
        COALESCE(svo.dep_time_diff, 'N/A') AS dep_time_diff,
        COALESCE(svo.bus_allotted_status, 'N/A') AS bus_allotted_status,
        COALESCE(svo.driver_1_allotted_status, 'N/A') AS driver_1_allotted_status,
        COALESCE(svo.driver_2_allotted_status, 'N/A') AS driver_2_allotted_status,
        COALESCE(svo.arr_time_diff, 'N/A') AS arr_time_diff,
        sm.single_crew,
        sm.service_type_id
    FROM 
        schedule_master sm
    LEFT JOIN 
        sch_veh_out svo ON sm.sch_key_no = svo.sch_no AND svo.departed_date BETWEEN ? AND ?
    WHERE 
        sm.division_id = ?
        AND sm.depot_id = ?
    ORDER BY
        sm.sch_dep_time, DATE(svo.departed_date)
";

$stmt = $db->prepare($query);
$stmt->bind_param('ssss', $start_date, $end_date, $division_id, $depot_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();

    // Initialize an array to store data by schedule
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedule_no = $row['sch_no'];
        $date = $row['date'];

        if (!isset($schedules[$schedule_no])) {
            $schedules[$schedule_no] = [
                'sch_dep_time' => $row['sch_dep_time'],
                'bus_numbers' => [
                    $row['bus_number_1'],
                    $row['bus_number_2']
                ],
                'driver_tokens' => [
                    $row['driver_token_1'],
                    $row['driver_token_2'],
                    $row['driver_token_3'],
                    $row['driver_token_4'],
                    $row['driver_token_5'],
                    $row['driver_token_6']
                ],
                'sch_arr_time' => $row['sch_arr_time'],
                'dates' => [],
                'single_crew' => $row['single_crew'],
                'service_type_id' => $row['service_type_id']
            ];
        }

        $schedules[$schedule_no]['dates'][$date] = [
            'dep_time_diff' => $row['dep_time_diff'],
            'bus_allotted_status' => $row['bus_allotted_status'],
            'driver_1_allotted_status' => $row['driver_1_allotted_status'],
            'driver_2_allotted_status' => $row['driver_2_allotted_status'],
            'arr_time_diff' => $row['arr_time_diff']
        ];
    }

    // Generate HTML report
    $report = '<h2>Depot: ' . htmlspecialchars($depotname) . '<h2 style="text-align:center;"> Monthly Report for ' . htmlspecialchars(date('F', mktime(0, 0, 0, $month, 10))) . ' ' . htmlspecialchars($year) . '</h2>';
    $report .= '<p style="color: red;">Note * : (RNO = Route not Operated),(RNC = Route not Completed), (NA = Not Alloted), (N/A = Not Applicable)</p>';

    foreach ($schedules as $schedule_no => $schedule) {
        $report .= '<h3>Schedule No: ' . htmlspecialchars($schedule_no) . '</h3>';
        $report .= '<table border="1">';
        $report .= '<tr><th>Content</th>';

        // Header for dates
        for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
            $report .= '<th>' . $i . '</th>';
        }
        $report .= '</tr>';

        // Populate rows
        $report .= '<tr>';
        $report .= '<td>Dep:' . htmlspecialchars($schedule['sch_dep_time']) . '</td>';
        for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
            $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
            $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                'dep_time_diff' => 'RNO',
                'bus_allotted_status' => 'NO',
                'driver_1_allotted_status' => 'NO',
                'driver_2_allotted_status' => 'NO',
                'arr_time_diff' => 'NO'
            ];

            $symbol = $data['dep_time_diff'] === 'RNO' ? 'RNO' : ($data['dep_time_diff'] > 30 ? '❌' : '✅');
            $report .= '<td>' . $symbol . '</td>';
        }
        $report .= '</tr>';

        $report .= '<tr>';
        // Filter out empty bus numbers and join the remaining with a comma
        $filtered_bus_numbers = array_filter($schedule['bus_numbers']);
        $bus_numbers = !empty($filtered_bus_numbers) ? implode(', ', $filtered_bus_numbers) : 'NA';

        // Append the bus numbers to the report, ensuring HTML special characters are properly escaped
        $report .= '<td>' . htmlspecialchars($bus_numbers) . '</td>';
        for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
            $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
            $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                'dep_time_diff' => 'NO',
                'bus_allotted_status' => 'NO',
                'driver_1_allotted_status' => 'NO',
                'driver_2_allotted_status' => 'NO',
                'arr_time_diff' => 'NO'
            ];

            $symbol = $data['bus_allotted_status'] == 0 ? '✅' : ($data['bus_allotted_status'] == 1 ? '❌' : 'N/A');
            $report .= '<td>' . $symbol . '</td>';
        }
        $report .= '</tr>';

        if ($schedule['single_crew'] == 'no' && in_array($schedule['service_type_id'], [2, 3, 4, 5])) {
            // Add rowspan for Driver Allotted Status
            $report .= '<tr>';
            $filtered_bus_numbers1 = array_filter($schedule['driver_tokens']);
            $crew = !empty($filtered_bus_numbers) ? implode(', ', $filtered_bus_numbers1) : 'NA';

            $report .= '<td rowspan="2">' . htmlspecialchars($crew) . '</td>'; // Rowspan of 2 rows
            for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                    'dep_time_diff' => 'NO',
                    'bus_allotted_status' => 'NO',
                    'driver_1_allotted_status' => 'NO',
                    'driver_2_allotted_status' => 'NO',
                    'arr_time_diff' => 'NO'
                ];

                $symbol1 = $data['driver_1_allotted_status'] == 0 ? '✅' : ($data['driver_1_allotted_status'] == 1 ? '❌' : 'N/A');
                $report .= '<td>' . $symbol1 . '</td>';
            }
            $report .= '</tr>';

            $report .= '<tr>';
            for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                    'dep_time_diff' => 'NO',
                    'bus_allotted_status' => 'NO',
                    'driver_1_allotted_status' => 'NO',
                    'driver_2_allotted_status' => 'NO',
                    'arr_time_diff' => 'NO'
                ];

                $symbol2 = $data['driver_2_allotted_status'] == 0 ? '✅' : ($data['driver_2_allotted_status'] == 1 ? '❌' : 'N/A');
                $report .= '<td>' . $symbol2 . '</td>';
            }
            $report .= '</tr>';
        } else {
            // Normal Driver Allotted Status row
            $report .= '<tr>';
            $filtered_bus_numbers2 = array_filter($schedule['driver_tokens']);
            $crew1 = !empty($filtered_bus_numbers) ? implode(', ', $filtered_bus_numbers2) : 'NA';

            $report .= '<td>' . htmlspecialchars($crew1) . '</td>';
            for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                    'dep_time_diff' => 'NO',
                    'bus_allotted_status' => 'NO',
                    'driver_1_allotted_status' => 'NO',
                    'driver_2_allotted_status' => 'NO',
                    'arr_time_diff' => 'NO'
                ];

                $symbol = $data['driver_1_allotted_status'] == 0 ? '✅' : ($data['driver_1_allotted_status'] == 1 ? '❌' : 'N/A');
                $report .= '<td>' . $symbol . '</td>';
            }
            $report .= '</tr>';
        }

        $report .= '<tr>';


        $report .= '<tr>';
        $report .= '<td>Arr:' . htmlspecialchars($schedule['sch_arr_time']) . '</td>';
        for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
            $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
            $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                'dep_time_diff' => 'NO',
                'bus_allotted_status' => 'NO',
                'driver_1_allotted_status' => 'NO',
                'driver_2_allotted_status' => 'NO',
                'arr_time_diff' => 'NO'
            ];

            if ($data['arr_time_diff'] === 'N/A' && $data['dep_time_diff'] !== 'N/A') {
                $symbol = 'RNC'; // Show RNC if arr_time_diff is 'N/A' and dep_time_diff is present
            } else {
                $symbol = $data['arr_time_diff'] === 'NO' ? 'RNO' : ($data['arr_time_diff'] > 30 ? '❌' : '✅');
            }
            $report .= '<td>' . $symbol . '</td>';
        }
        $report .= '</tr>';

        $report .= '</table>';
    }

    echo json_encode(['html' => $report]);
} else {
    echo json_encode(['html' => '<p>Error executing query: ' . htmlspecialchars($stmt->error) . '</p>']);
}
?>