<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();
// Get JSON input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    $month = $data['month'];
    $year = $data['year'];

    // Get session variables
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depotname = $_SESSION['KMPL_DEPOT'];

    $start_date = date("$year-$month-01");
    $end_date = date("Y-m-t", strtotime($start_date));

    // Adjust the end date if the selected month is the current month
    if ($year == date('Y') && $month == date('m')) {
        $end_date = date('Y-m-d'); // Set end date to current date
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
        sm.service_type_id,
        svo.vehicle_no,
        svo.driver_token_no_1,
        svo.driver_token_no_2,
        svo.arr_time,
        svo.dep_time
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
                    'service_type_id' => $row['service_type_id'],
                    'vehicle_no' => $row['vehicle_no'],
                    'driver_token_no_1' => $row['driver_token_no_1'],
                    'driver_token_no_2' => $row['driver_token_no_2'],
                    'arr_time' => $row['arr_time'],
                    'dep_time' => $row['dep_time']

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
            $safe_schedule_no = str_replace('/', '-', $schedule_no);

            // Use the sanitized version in the data-target attribute
            $scheduleReport .= '<h3>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal' . $safe_schedule_no . '">
                  Schedule No: ' . htmlspecialchars($schedule_no, ENT_QUOTES, 'UTF-8') . '
                </button>
            </h3>';
            $scheduleReport .= '<table border="1">';
            $scheduleReport .= '<tr><th>Content</th>';

            // Header for dates
            $days_in_month = date('t', strtotime($start_date));
            $current_day = $year == date('Y') && $month == date('m') ? date('j') : $days_in_month;
            for ($i = 1; $i <= $current_day; $i++) {
                $scheduleReport .= '<th>' . $i . '</th>';
            }
            $scheduleReport .= '</tr>';
            // Populate rows
            $scheduleReport .= '<tr>';
            $scheduleReport .= '<td>Dep:' . htmlspecialchars($schedule['sch_dep_time']) . '</td>';
            for ($i = 1; $i <= $current_day; $i++) {
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
            for ($i = 1; $i <= $current_day; $i++) {
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                    'dep_time_diff' => 'NO',
                    'bus_allotted_status' => 'NO',
                    'driver_1_allotted_status' => 'NO',
                    'driver_2_allotted_status' => 'NO',
                    'arr_time_diff' => 'NO'
                ];

                $symbol = $data['bus_allotted_status'] == 'NO' ? 'N/A' : ($data['bus_allotted_status'] == 1 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>');
                $scheduleReport .= '<td>' . $symbol . '</td>';
            }
            $scheduleReport .= '</tr>';

            if ($schedule['single_crew'] == 'no' && in_array($schedule['service_type_id'], [2, 3, 4, 5])) {
                // Add rowspan for Driver Allotted Status
                $scheduleReport .= '<tr>';
                $filtered_driver_tokens = array_filter($schedule['driver_tokens']);
                $crew = !empty($filtered_driver_tokens) ? implode(', ', $filtered_driver_tokens) : 'NA';

                $scheduleReport .= '<td rowspan="2">' . htmlspecialchars($crew) . '</td>'; // Rowspan of 2 rows
                for ($i = 1; $i <= $current_day; $i++) {
                    $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                    $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                        'dep_time_diff' => 'NO',
                        'bus_allotted_status' => 'NO',
                        'driver_1_allotted_status' => 'NO',
                        'driver_2_allotted_status' => 'NO',
                        'arr_time_diff' => 'NO'
                    ];

                    $symbol1 = $data['driver_1_allotted_status'] == 'NO' ? 'N/A' : ($data['driver_1_allotted_status'] == 1 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>');
                    $scheduleReport .= '<td>' . $symbol1 . '</td>';
                }
                $scheduleReport .= '</tr>';

                $scheduleReport .= '<tr>';
                for ($i = 1; $i <= $current_day; $i++) {
                    $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                    $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                        'dep_time_diff' => 'NO',
                        'bus_allotted_status' => 'NO',
                        'driver_1_allotted_status' => 'NO',
                        'driver_2_allotted_status' => 'NO',
                        'arr_time_diff' => 'NO'
                    ];

                    $symbol2 = $data['driver_2_allotted_status'] == 'NO' ? 'N/A' : ($data['driver_2_allotted_status'] == 1 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>');
                    $scheduleReport .= '<td>' . $symbol2 . '</td>';
                }
                $scheduleReport .= '</tr>';
            } else {
                // Single Driver Row
                $scheduleReport .= '<tr>';
                $filtered_driver_tokens = array_filter($schedule['driver_tokens']);
                $crew = !empty($filtered_driver_tokens) ? implode(', ', $filtered_driver_tokens) : 'NA';
                $scheduleReport .= '<td>' . htmlspecialchars($crew) . '</td>';

                for ($i = 1; $i <= $current_day; $i++) {
                    $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                    $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                        'dep_time_diff' => 'NO',
                        'bus_allotted_status' => 'NO',
                        'driver_1_allotted_status' => 'NO',
                        'driver_2_allotted_status' => 'NO',
                        'arr_time_diff' => 'NO'
                    ];

                    $symbol1 = $data['driver_1_allotted_status'] == 'NO' ? 'N/A' : ($data['driver_1_allotted_status'] == 1 ? '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>' : '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>');
                    $scheduleReport .= '<td>' . $symbol1 . '</td>';
                }
                $scheduleReport .= '</tr>';
            }

            $scheduleReport .= '<tr>';
            $scheduleReport .= '<td>Arr:' . htmlspecialchars($schedule['sch_arr_time']) . '</td>';
            for ($i = 1; $i <= $current_day; $i++) {
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $data = isset($schedule['dates'][$date_key]) ? $schedule['dates'][$date_key] : [
                    'dep_time_diff' => 'NO',
                    'bus_allotted_status' => 'NO',
                    'driver_1_allotted_status' => 'NO',
                    'driver_2_allotted_status' => 'NO',
                    'arr_time_diff' => 'NO'
                ];

                $symbol = '';

                if ($data['arr_time_diff'] === 'NO') {
                    $symbol = 'SNO'; // Handle 'NO' case
                } elseif (is_numeric($data['arr_time_diff'])) {
                    // Check if the value is numeric and perform the following checks
                    if ($data['arr_time_diff'] > 30) {
                        $symbol = '<i class="fa-solid fa-square-xmark fa-xl" style="color: #3aad08f5;"></i>'; // Green Cross for time > 30
                    } elseif ($data['arr_time_diff'] >= 0 && $data['arr_time_diff'] <= 30) {
                        $symbol = '<i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i>'; // Green Check for time between 0 and 30
                    } elseif ($data['arr_time_diff'] < 0) {
                        $symbol = '<i class="fa-solid fa-square-xmark fa-xl" style="color: #e40c0c;"></i>'; // Red Cross for time < 0
                    }
                } else {
                    $symbol = 'SNA'; // Handle null or non-numeric values
                }

                $scheduleReport .= '<td>' . $symbol . '</td>';
            }
            $scheduleReport .= '</tr>';

            $scheduleReport .= '</table><br>';
            // Modal for Schedule Details

            $scheduleReport .= '
<div class="modal fade" id="scheduleModal' . $safe_schedule_no . '" tabindex="-1" role="dialog" aria-labelledby="scheduleModalLabel' . $safe_schedule_no . '" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel' . $safe_schedule_no . '">Schedule Report for Schedule No: ' . htmlspecialchars($schedule_no, ENT_QUOTES, 'UTF-8') . ' for Month ' . htmlspecialchars(date('F', mktime(0, 0, 0, $month, 10))) . ' ' . htmlspecialchars($year) . '</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p><b>Vehicle No:</b> ' . htmlspecialchars($bus_numbers) . '  <b>Driver(s):</b> ' . htmlspecialchars($crew) . ' <b>Departure Time:</b> ' . htmlspecialchars($schedule['sch_dep_time']) . '  <b>Arrival Time:</b> ' . htmlspecialchars($schedule['sch_arr_time']) . '</p>
                <!-- Add .table-responsive to make table responsive -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Bus Number</th>
                                <th>Driver 1 Token</th>';
            if ($schedule['single_crew'] == 'no' && in_array($schedule['service_type_id'], [2, 3, 4, 5])) {
                $scheduleReport .= '<th>Driver 2 Token</th>';
            }
            $scheduleReport .= '
                                <th>Departure Time</th>
                                <th>Arrival Time</th>
                            </tr>
                        </thead>
                        <tbody>';

            $currentMonth = date('m');
            $currentYear = date('Y');
            $currentDate = date('Y-m-d');
            // Extract the date keys from the schedule
            // Extract the date keys from the schedule
            $dateKeys = array_keys($schedule['dates']);

            // Check if the date keys array is empty or contains empty strings
            if (!empty($dateKeys) && !empty($dateKeys[0])) {
                // Use the first date from the array if available
                $firstDayOfMonth = date('Y-m-01', strtotime(reset($dateKeys)));
            } else {
                // If no data found for the entire month, use the provided month and year
                $firstDayOfMonth = date('Y-m-01', strtotime("$year-$month-01"));
            }



            // Get the list of all dates in the month

            $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

            // Show details up to today's date if the month is the current month
            if ($currentMonth == date('m', strtotime($firstDayOfMonth)) && $currentYear == date('Y', strtotime($firstDayOfMonth))) {
                $lastDayOfMonth = $currentDate;
            }

            $dateInterval = new DateInterval('P1D');
            $period = new DatePeriod(new DateTime($firstDayOfMonth), $dateInterval, (new DateTime($lastDayOfMonth))->modify('+1 day'));

            foreach ($period as $dateObj) {
                $date = $dateObj->format('Y-m-d');

                $scheduleReport .= '<tr><td>' . htmlspecialchars(date('d/m/Y', strtotime($date))) . '</td>';

                if (isset($schedule['dates'][$date])) {
                    $data = $schedule['dates'][$date];

                    // Display Bus Number or Check Mark
                    if ($data['bus_allotted_status'] === '0') {
                        $scheduleReport .= '<td><i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i></td>';
                    } elseif ($data['bus_allotted_status'] === '1') {
                        $vehicle_no = isset($schedule['vehicle_no']) ? htmlspecialchars($schedule['vehicle_no']) : 'N/A';
                        $scheduleReport .= '<td><b>' . $vehicle_no . '</b></td>';
                    } else {
                        $scheduleReport .= '<td>SNO</td>';
                    }

                    // Driver 1 Token or Check Mark
                    if ($data['driver_1_allotted_status'] === '0') {
                        $scheduleReport .= '<td><i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i></td>';
                    } elseif ($data['driver_1_allotted_status'] === '1') {
                        $scheduleReport .= '<td><b>' . htmlspecialchars($schedule['driver_token_no_1']) . '</b></td>';
                    } else {
                        $scheduleReport .= '<td>SNO</td>';
                    }

                    // Driver 2 Token or Check Mark (if applicable)
                    if ($schedule['single_crew'] == 'no' && in_array($schedule['service_type_id'], [2, 3, 4, 5])) {
                        if ($data['driver_2_allotted_status'] === '0') {
                            $scheduleReport .= '<td><i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i></td>';
                        } elseif ($data['driver_2_allotted_status'] === '1') {
                            $scheduleReport .= '<td><b>' . htmlspecialchars($schedule['driver_token_no_2']) . '</b></td>';
                        } else {
                            $scheduleReport .= '<td>SNO</td>';
                        }
                    }

                    // Departure Time
                    if ($data['dep_time_diff'] <= '30') {
                        $scheduleReport .= '<td><i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i></td>';
                    } elseif ($data['dep_time_diff'] === 'N/A') {
                        $scheduleReport .= '<td>SNO</td>';
                    } elseif ($data['dep_time_diff'] > '30') {
                        $scheduleReport .= '<td style="color:red"><b>' . htmlspecialchars($schedule['dep_time']) . '</b></td>';
                    }

                    // Arrival Time
                    if ($data['arr_time_diff'] <= '30' && $data['arr_time_diff'] >= '0') {
                        $scheduleReport .= '<td><i class="fa-solid fa-square-check fa-xl" style="color: #3aad08f5;"></i></td>';
                    } elseif ($data['arr_time_diff'] === 'N/A') {
                        $scheduleReport .= '<td>SNO</td>';
                    } elseif ($data['arr_time_diff'] > '30') {
                        $scheduleReport .= '<td style="color:green"><b>' . htmlspecialchars($schedule['arr_time']) . '</b></td>';
                    } elseif ($data['arr_time_diff'] < '0') {
                        $scheduleReport .= '<td style="color:red"><b>' . htmlspecialchars($schedule['arr_time']) . '</b></td>';
                    }
                } else {
                    // If no data found for this date, display "SNO"
                    $scheduleReport .= '<td>SNO</td><td>SNO</td>';
                    if ($schedule['single_crew'] == 'no' && in_array($schedule['service_type_id'], [2, 3, 4, 5])) {
                        $scheduleReport .= '<td>SNO</td>';
                    }
                    $scheduleReport .= '<td>SNO</td><td>SNO</td>';
                }

                $scheduleReport .= '</tr>';
            }

            $scheduleReport .= '</tbody>
                    </table>
                    <p style="color: red;">Note * : (SNO = Schedule not Operated),(SNA = Schedule not Arrived), (NA = Not Alloted), (N/A = Not Applicable)</p>
                </div> <!-- End of .table-responsive -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>';


        }
    }

    // Second Report: Monthly Summary Report
    $today = date('Y-m-d');
    $currentMonth = date('m');
    $currentYear = date('Y');

    // Determine if the selected month and year are the current month and year
    $isCurrentMonthYear = ($month == $currentMonth && $year == $currentYear);

    // Calculate the number of days in the month
    $daysInMonth = date('t', strtotime($start_date));

    // Adjust the number of days if it's the current month
    if ($isCurrentMonthYear) {
        $todayDay = date('j'); // Get today's day of the month
        $daysInMonth = min($daysInMonth, $todayDay); // Limit days to today
    }

    $reportData = [
        'Schedule Departures' => [],
        'Actual Departures' => [],
        'Departures Not Departed' => [],
        'Departures On Time' => [],
        '% of Departure' => [],
        'Arrivals On Time' => [],
        '% of Arrival' => [],
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
        count(dep_time_diff) as total_dep, 
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
        $reportData['Schedule Departures'][] = $total_departures;
        $reportData['Actual Departures'][] = $data['departure_held'];
        $reportData['Departures Not Departed'][] = $total_departures - $data['departure_held'];
        $reportData['Departures On Time'][] = $data['departures_on_time'];
        if ($data['departure_held'] > 0) {
            $reportData['% of Departure'][] = number_format(($data['departures_on_time'] / $data['departure_held']) * 100, 0) . '%';
        } else {
            $reportData['% of Departure'][] = 'NA';
        }
        $reportData['Arrivals On Time'][] = $data['arrivals_on_time'];
        if ($data['departure_held'] > 0) {
            $reportData['% of Arrival'][] = number_format(($data['arrivals_on_time'] / $data['departure_held']) * 100, 0) . '%';
        } else {
            $reportData['% of Arrival'][] = 'NA';
        }
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
}
?>