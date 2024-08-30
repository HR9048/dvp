<?php
include '../includes/connection.php';
include '../pages/session.php';

// Get JSON input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

    // First Report: Schedule Report
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
        sm.sch_dep_time, DATE(svo.departed_date)";

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

        // Generate HTML for Schedule Report
        $scheduleReport = '<h2>Depot: ' . htmlspecialchars($depotname) . '<h2 style="text-align:center;"> Monthly Report for ' . htmlspecialchars(date('F', mktime(0, 0, 0, $month, 10))) . ' ' . htmlspecialchars($year) . '</h2>';
        $scheduleReport .= '<p style="color: red;">Note * : (SNO = Schedule not Operated),(SNA = Schedule not Arrived), (NA = Not Alloted), (N/A = Not Applicable)</p>';

        foreach ($schedules as $schedule_no => $schedule) {
            $scheduleReport .= '<h3>Schedule No: ' . htmlspecialchars($schedule_no) . '</h3>';
            $scheduleReport .= '<table border="1">';
            $scheduleReport .= '<tr><th>Content</th>';

            // Header for dates
            for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                $scheduleReport .= '<th>' . $i . '</th>';
            }
            $scheduleReport .= '</tr>';

            // Populate rows
            $scheduleReport .= '<tr>';
            $scheduleReport .= '<td>Dep:' . htmlspecialchars($schedule['sch_dep_time']) . '</td>';
            for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                    'dep_time_diff' => 'SNO',
                    'bus_allotted_status' => 'NO',
                    'driver_1_allotted_status' => 'NO',
                    'driver_2_allotted_status' => 'NO',
                    'arr_time_diff' => 'NO'
                ];

                $symbol = $data['dep_time_diff'] === 'SNO' ? 'SNO' : ($data['dep_time_diff'] > 30 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>');
                $scheduleReport .= '<td>' . $symbol . '</td>';
            }
            $scheduleReport .= '</tr>';

            $scheduleReport .= '<tr>';
            $filtered_bus_numbers = array_filter($schedule['bus_numbers']);
            $bus_numbers = !empty($filtered_bus_numbers) ? implode(', ', $filtered_bus_numbers) : 'NA';
            $scheduleReport .= '<td>' . htmlspecialchars($bus_numbers) . '</td>';
            for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                    'dep_time_diff' => 'NO',
                    'bus_allotted_status' => 'NO',
                    'driver_1_allotted_status' => 'NO',
                    'driver_2_allotted_status' => 'NO',
                    'arr_time_diff' => 'NO'
                ];

                $symbol = $data['bus_allotted_status'] == 0 ? '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>' : ($data['bus_allotted_status'] == 1 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : 'N/A');
                $scheduleReport .= '<td>' . $symbol . '</td>';
            }
            $scheduleReport .= '</tr>';

            if ($schedule['single_crew'] == 'no' && in_array($schedule['service_type_id'], [2, 3, 4, 5])) {
                // Add rowspan for Driver Allotted Status
                $scheduleReport .= '<tr>';
                $filtered_driver_tokens = array_filter($schedule['driver_tokens']);
                $crew = !empty($filtered_driver_tokens) ? implode(', ', $filtered_driver_tokens) : 'NA';

                $scheduleReport .= '<td rowspan="2">' . htmlspecialchars($crew) . '</td>'; // Rowspan of 2 rows
                for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                    $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                    $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                        'dep_time_diff' => 'NO',
                        'bus_allotted_status' => 'NO',
                        'driver_1_allotted_status' => 'NO',
                        'driver_2_allotted_status' => 'NO',
                        'arr_time_diff' => 'NO'
                    ];

                    $symbol1 = $data['driver_1_allotted_status'] == 0 ? '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>' : ($data['driver_1_allotted_status'] == 1 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : 'N/A');
                    $scheduleReport .= '<td>' . $symbol1 . '</td>';
                }
                $scheduleReport .= '</tr>';

                $scheduleReport .= '<tr>';
                for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                    $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                    $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                        'dep_time_diff' => 'NO',
                        'bus_allotted_status' => 'NO',
                        'driver_1_allotted_status' => 'NO',
                        'driver_2_allotted_status' => 'NO',
                        'arr_time_diff' => 'NO'
                    ];

                    $symbol2 = $data['driver_2_allotted_status'] == 0 ? '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>' : ($data['driver_2_allotted_status'] == 1 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : 'N/A');
                    $scheduleReport .= '<td>' . $symbol2 . '</td>';
                }
                $scheduleReport .= '</tr>';
            } else {
                // Single Driver Row
                $scheduleReport .= '<tr>';
                $filtered_driver_tokens = array_filter($schedule['driver_tokens']);
                $crew = !empty($filtered_driver_tokens) ? implode(', ', $filtered_driver_tokens) : 'NA';
                $scheduleReport .= '<td>' . htmlspecialchars($crew) . '</td>';

                for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                    $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                    $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                        'dep_time_diff' => 'NO',
                        'bus_allotted_status' => 'NO',
                        'driver_1_allotted_status' => 'NO',
                        'driver_2_allotted_status' => 'NO',
                        'arr_time_diff' => 'NO'
                    ];

                    $symbol1 = $data['driver_1_allotted_status'] == 0 ? '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>' : ($data['driver_1_allotted_status'] == 1 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : 'N/A');
                    $scheduleReport .= '<td>' . $symbol1 . '</td>';
                }
                $scheduleReport .= '</tr>';
            }

            $scheduleReport .= '<tr>';
            $scheduleReport .= '<td>Arr:' . htmlspecialchars($schedule['sch_arr_time']) . '</td>';
            for ($i = 1; $i <= date('t', strtotime($start_date)); $i++) {
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                    'dep_time_diff' => 'NO',
                    'bus_allotted_status' => 'NO',
                    'driver_1_allotted_status' => 'NO',
                    'driver_2_allotted_status' => 'NO',
                    'arr_time_diff' => 'NO'
                ];

                $symbol = $data['arr_time_diff'] === 'NO' ? 'SNA' : ($data['arr_time_diff'] > 30 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>');
                $scheduleReport .= '<td>' . $symbol . '</td>';
            }
            $scheduleReport .= '</tr>';

            $scheduleReport .= '</table><br>';
        }
    }

    // Second Report: Monthly Summary Report
    $daysInMonth = date('t', strtotime($start_date));

    $reportData = [
        'Actual Departures' => [],
        'Departures Held' => [],
        'Departures Not Operated' => [],
        'Departures On Time' => [],
        'Arrivals On Time' => [],
        'Fixed Vehicles Operated (%)' => [],
        'Fixed Driver 1 Operated (%)' => [],
        'Fixed Driver 2 Operated (%)' => [],
        'Fixed Conductor Operated (%)' => [],
    ];

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $scheduleQuery = "SELECT COUNT(*) as total_departures
                          FROM schedule_master sm
                          LEFT JOIN sch_veh_out svo ON sm.sch_key_no = svo.sch_no AND DATE(svo.departed_date) = '$date'
                          WHERE sm.depot_id = ? AND sm.division_id = ?";

        $stmt = $db->prepare($scheduleQuery);
        $stmt->bind_param('ss', $depot_id, $division_id);
        $stmt->execute();
        $scheduleResult = $stmt->get_result();
        $scheduleData = $scheduleResult->fetch_assoc();
        $total_departures = $scheduleData['total_departures'];

        $query = "SELECT COUNT(*) as departure_held,
        COUNT(CASE WHEN dep_time_diff <= 30 THEN 1 END) as departures_on_time,
        COUNT(CASE WHEN arr_time_diff <= 30 THEN 1 END) as arrivals_on_time,
        COUNT(CASE WHEN bus_allotted_status = 0 THEN 1 END) as bus_operated,
        COUNT(CASE WHEN driver_1_allotted_status = 0 THEN 1 END) as driver1_operated,
        COUNT(CASE WHEN driver_2_allotted_status = 0 THEN 1 END) as driver2_operated,
        COUNT(CASE WHEN conductor_alloted_status = 0 THEN 1 END) as conductor_operated
      FROM sch_veh_out
      WHERE depot_id = '$depot_id' AND division_id = '$division_id' AND departed_date = '$date'";

        $result = mysqli_query($db, $query);
        $data = $result->fetch_assoc();

        // Populate the report data
        $reportData['Actual Departures'][] = $total_departures;
        $reportData['Departures Held'][] = $data['departure_held'];
        $reportData['Departures Not Operated'][] = $total_departures - $data['departure_held'];
        $reportData['Departures On Time'][] = $data['departures_on_time'];
        $reportData['Arrivals On Time'][] = $data['arrivals_on_time'];

       // Calculate percentages with division by zero check
    if ($data['departure_held'] > 0) {
        $reportData['Fixed Vehicles Operated (%)'][] = number_format(($data['bus_operated'] / $data['departure_held']) * 100, 0) . '%';
        $reportData['Fixed Driver 1 Operated (%)'][] = number_format(($data['driver1_operated'] / $data['departure_held']) * 100, 0) . '%';
        $reportData['Fixed Driver 2 Operated (%)'][] = number_format(($data['driver2_operated'] / $data['departure_held']) * 100, 0) . '%';
        $reportData['Fixed Conductor Operated (%)'][] = number_format(($data['conductor_operated'] / $data['departure_held']) * 100, 0) . '%';
    } else {
        $reportData['Fixed Vehicles Operated (%)'][] = 'NA';
        $reportData['Fixed Driver 1 Operated (%)'][] = 'NA';
        $reportData['Fixed Driver 2 Operated (%)'][] = 'NA';
        $reportData['Fixed Conductor Operated (%)'][] = 'NA';
    }
    }

    $monthlySummaryReport = '<h2>Monthly Summary Report for ' . htmlspecialchars(date('F', mktime(0, 0, 0, $month, 10))) . ' ' . htmlspecialchars($year) . '</h2>';
    $monthlySummaryReport .= '<table border="1"><thead><tr>';
    $monthlySummaryReport .= '<th>Particulars</th>';

    // Header for dates
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $monthlySummaryReport .= '<th>' . $day . '</th>';
    }

    $monthlySummaryReport .= '</tr></thead><tbody>';

    foreach ($reportData as $metric => $values) {
        $monthlySummaryReport .= '<tr><td>' . htmlspecialchars($metric) . '</td>';
        foreach ($values as $value) {
            $monthlySummaryReport .= '<td>' . htmlspecialchars($value) . '</td>';
        }
        $monthlySummaryReport .= '</tr>';
    }

    $monthlySummaryReport .= '</tbody></table>';

    // Combine both reports
    $combinedReport = $scheduleReport . '<br>' . $monthlySummaryReport;

    // Return the combined HTML report
    echo json_encode(['html' => $combinedReport]);
}else {
    header('Location: ../pages/login.php');
    exit;
}
?>