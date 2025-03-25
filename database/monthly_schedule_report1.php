<?php
include '../includes/connection.php';
include '../pages/session.php';

date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'schedulemonthlyreportdatafetch') {
    $year = $_POST['year'];
    $month = $_POST['month'];
    $division_id = $_POST['division_id'];
    $depot_id = $_POST['depot_id'];

    // Get first and last date of the selected month
    $month_start_date = "$year-$month-01";
    $month_end_date = date("Y-m-t", strtotime($month_start_date));

    // Generate all dates in the selected month
    $all_dates = [];
    $current = new DateTime($month_start_date);
    $end = new DateTime($month_end_date);
    while ($current <= $end) {
        $all_dates[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    // Fetch all schedules from schedule_master
    $inactive_schedules = [];
    $query = "SELECT sch_key_no, depot_id FROM schedule_master WHERE division_id = ? AND depot_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $division_id, $depot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $key = $row['depot_id'] . '-' . $row['sch_key_no'];
        $inactive_schedules[$key] = [
            'depot_id' => $row['depot_id'],
            'sch_key_no' => $row['sch_key_no'],
            'inactive_dates' => [],
            'active_dates' => $all_dates,
            'data' => [] // Initialize data array
        ];
    }

    // Fetch inactive schedules from sch_actinact table
    $query = "SELECT sch_key_no, depot_id, 
       GREATEST(DATE(inact_from), ?) AS inact_from, 
       COALESCE(DATE(inact_to), ?) AS inact_to 
    FROM sch_actinact 
    WHERE division_id = ? 
    AND depot_id = ? 
    AND ((DATE(inact_from) BETWEEN ? AND ?) 
         OR (inact_to IS NOT NULL AND DATE(inact_to) BETWEEN ? AND ?) 
         OR (DATE(inact_from) <= ? AND (inact_to IS NULL OR DATE(inact_to) >= ?)))";

    $stmt = $db->prepare($query);
    $stmt->bind_param(
        "ssiissssss",
        $month_start_date,
        $month_end_date,
        $division_id,
        $depot_id,
        $month_start_date,
        $month_end_date,
        $month_start_date,
        $month_end_date,
        $month_start_date,
        $month_end_date
    );
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $key = $row['depot_id'] . '-' . $row['sch_key_no'];
        $inact_from = $row['inact_from'];
        $inact_to = $row['inact_to'];

        $start = new DateTime($inact_from);
        $end = new DateTime($inact_to);

        $inactive_dates = [];
        while ($start <= $end) {
            $inactive_dates[] = $start->format('Y-m-d');
            $start->modify('+1 day');
        }

        if (isset($inactive_schedules[$key])) {
            $inactive_schedules[$key]['inactive_dates'] = $inactive_dates;
            $inactive_schedules[$key]['active_dates'] = array_values(array_diff($all_dates, $inactive_dates));
        }
    }

    $sch_key_nos = array_column($inactive_schedules, 'sch_key_no');
    $depot_ids = array_column($inactive_schedules, 'depot_id');

    if (!empty($sch_key_nos) && !empty($depot_ids)) {
        $schedule_placeholders = implode(',', array_fill(0, count($sch_key_nos), '?'));
        $depot_placeholders = implode(',', array_fill(0, count($depot_ids), '?'));
        $date_placeholders = implode(',', array_fill(0, count($all_dates), '?'));

        // SQL query with JOIN to include schedule_master data
        $query = "SELECT 
            svo.*, 
            sm.sch_dep_time, 
            sm.sch_arr_time, 
            sm.`bus_number_1`, 
            sm.`bus_number_2`, 
            sm.`additional_bus_number`, 
            sm.`service_type_id`,
            sm.`single_crew`,
            sm.driver_token_1, sm.driver_token_2, sm.driver_token_3, 
            sm.driver_token_4, sm.driver_token_5, sm.driver_token_6, 
            sm.offreliverdriver_token_1, sm.offreliverdriver_token_2, 
            sm.conductor_token_1, sm.conductor_token_2, sm.conductor_token_3, 
            sm.offreliverconductor_token_1
          FROM sch_veh_out AS svo
          INNER JOIN schedule_master AS sm 
          ON svo.sch_no = sm.sch_key_no 
          AND svo.depot_id = sm.depot_id
          WHERE svo.departed_date IN ($date_placeholders) 
          AND svo.sch_no IN ($schedule_placeholders) 
          AND svo.depot_id IN ($depot_placeholders)";

        $stmt = $db->prepare($query);
        $params = array_merge($all_dates, $sch_key_nos, $depot_ids);
        $types = str_repeat('s', count($all_dates)) . str_repeat('i', count($sch_key_nos) + count($depot_ids));

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();


        // Store data in an associative array for quick lookups
        $schedule_data = [];
        while ($row = $result->fetch_assoc()) {
            $schedule_data[$row['sch_no']][$row['departed_date']] = $row;
        }
    }

    // Filter out schedules that are inactive for the entire month
    $active_schedules = array_filter($inactive_schedules, function ($schedule) use ($all_dates) {
        return count(array_intersect($all_dates, $schedule['inactive_dates'])) !== count($all_dates);
    });

    // Process each active schedule
    foreach ($active_schedules as &$schedule) {
        $sch_key_no = $schedule['sch_key_no'];
        $depot_id = $schedule['depot_id'];
        $inactive_dates = $schedule['inactive_dates'];
        $active_dates = array_diff($all_dates, $inactive_dates);

        // Get first available active date
        $first_active_date = reset($active_dates);

        // Fetch service_type_id only from active dates
        $service_type_id = $first_active_date && isset($schedule_data[$sch_key_no][$first_active_date]['service_type_id'])
            ? (string) $schedule_data[$sch_key_no][$first_active_date]['service_type_id']
            : null;

        // Fallback: If no active date found, take service_type_id from the first available schedule
        if ($service_type_id === null && isset($schedule_data[$sch_key_no])) {
            $first_available_date = array_key_first($schedule_data[$sch_key_no]);
            $service_type_id = isset($schedule_data[$sch_key_no][$first_available_date]['service_type_id'])
                ? $schedule_data[$sch_key_no][$first_available_date]['service_type_id']
                : null;
        }
        $single_crew = $first_active_date && isset($schedule_data[$sch_key_no][$first_active_date]['single_crew'])
            ? (string) $schedule_data[$sch_key_no][$first_active_date]['single_crew']
            : null;
        if ($single_crew === null && isset($schedule_data[$sch_key_no])) {
            $first_available_date = array_key_first($schedule_data[$sch_key_no]);
            $single_crew = isset($schedule_data[$sch_key_no][$first_available_date]['single_crew'])
                ? $schedule_data[$sch_key_no][$first_available_date]['single_crew']
                : null;
        }
        echo "<h3>Schedule No: {$sch_key_no} {$service_type_id} {$single_crew} </h3>";
        echo "<table border='1'>";
        echo "<tr><th>Content</th>";

        foreach ($all_dates as $date) {
            echo "<th>" . date('j', strtotime($date)) . "</th>";
        }
        echo "</tr>";

        

        // Define row order
        $status_fields = [
            'Departure: sch_dep_time' => 'dep_time_diff',
            'Bus Status' => 'bus_allotted_status',
        ];

        // Special case: If `service_type_id == 4`, merge Driver 1 and Driver 2 under one row using rowspan
        $has_driver_rowspan = ($service_type_id == 4);
        if ($has_driver_rowspan) {
            $status_fields['Driver Status (Merged)'] = 'driver_1_allotted_status';  // This row will have rowspan=2
        } else {
            $status_fields['Driver 1 Status'] = 'driver_1_allotted_status';
        }

        // Add conductor row if `single_crew == 'no'`
        if ($single_crew != 'yes' ) {
            $status_fields['Conductor Status'] = 'conductor_alloted_status';
        }

        // Add Arrival row at the end
        $status_fields['Arrival: sch_arr_time'] = 'arr_time_diff';

        // Generate table rows
        foreach ($status_fields as $label => $field) {
            echo "<tr>";

            // Apply rowspan for Driver Status if `service_type_id == 4`
            if ($label == 'Driver Status (Merged)') {
                echo "<td rowspan='2'>Driver Status</td>";
            } else {
                echo "<td>$label</td>";
            }

            foreach ($all_dates as $date) {
                $formatted_date = date('Y-m-d', strtotime($date));

                if (in_array($formatted_date, $inactive_dates)) {
                    echo "<td>Inactive</td>";
                } elseif (!isset($schedule_data[$sch_key_no][$formatted_date])) {
                    echo "<td>Cancel</td>";
                } else {
                    $value = $schedule_data[$sch_key_no][$formatted_date][$field] ?? null;
                    if (is_null($value)) {
                        echo "<td>N/A</td>";
                    } elseif ($field == 'dep_time_diff' || $field == 'arr_time_diff') {
                        echo "<td>" . ($value > 30 ? '❌' : '✅') . "</td>";
                    } else {
                        echo "<td>" . ($value == 0 ? '✅' : '❌') . "</td>";
                    }
                }
            }
            echo "</tr>";

            // If `service_type_id == 4`, add a second row for Driver 2 Status (but no extra label)
            if ($has_driver_rowspan && $label == 'Driver Status (Merged)') {
                echo "<tr>";
                foreach ($all_dates as $date) {
                    $formatted_date = date('Y-m-d', strtotime($date));

                    if (in_array($formatted_date, $inactive_dates)) {
                        echo "<td>Inactive</td>";
                    } elseif (!isset($schedule_data[$sch_key_no][$formatted_date])) {
                        echo "<td>Cancel</td>";
                    } else {
                        $value2 = $schedule_data[$sch_key_no][$formatted_date]['driver_2_allotted_status'] ?? null;
                        if (is_null($value2)) {
                            echo "<td>N/A</td>";
                        } else {
                            echo "<td>" . ($value2 == 0 ? '✅' : '❌') . "</td>";
                        }
                    }
                }
                echo "</tr>";
            }
        }
        echo "</table><br>";
    }
}
