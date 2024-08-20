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

    $month = $data['month'];
    $year = $data['year'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    // Fetch all schedules for the selected month and year
    $query = "
    SELECT 
        sm.sch_key_no AS sch_no,
        cfd.driver_token_1, cfd.conductor_token_1, cfd.from_date, cfd.to_date,
        bfd.bus_number_1
    FROM 
        schedule_master sm
    LEFT JOIN 
        crew_fix_data cfd ON sm.sch_key_no = cfd.sch_no
    LEFT JOIN 
        bus_fix_data bfd ON sm.sch_key_no = bfd.sch_no
    WHERE 
        sm.division_id = ? AND sm.depot_id = ?
    GROUP BY 
        sm.sch_key_no, cfd.from_date, cfd.to_date
    ORDER BY 
        sm.sch_key_no";

    $stmt = $db->prepare($query);
    $stmt->bind_param('ii', $division_id, $depot_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        echo json_encode(['html' => 'Error fetching data: ' . $stmt->error]);
        exit;
    }

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $html = '';

    while ($row = $result->fetch_assoc()) {
        $html .= '<div class="schedule-container">';
        $html .= '<h3>Schedule No: ' . htmlspecialchars($row['sch_no']) . '</h3>';
        $html .= '<p><strong>Driver Token:</strong> ' . htmlspecialchars($row['driver_token_1']) . '</p>';
        $html .= '<p><strong>Conductor Token:</strong> ' . htmlspecialchars($row['conductor_token_1']) . '</p>';
        $html .= '<p><strong>Bus Number:</strong> ' . htmlspecialchars($row['bus_number_1']) . '</p>';

        // Table structure with dates as headers
        $html .= '<table border="1">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Content</th>';
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $html .= '<th>' . $day . '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        // Rows for each schedule containing dep_time, vehicle_no, driver_no1, driver_no2, arr_time
        $html .= '<tr><td>Dep Time</td>';
        $html .= generateTableRow($row['sch_no'], $daysInMonth, 'dep_time', $db);
        $html .= '</tr>';
        
        $html .= '<tr><td>Vehicle No</td>';
        $html .= generateTableRow($row['sch_no'], $daysInMonth, 'vehicle_no', $db);
        $html .= '</tr>';

        $html .= '<tr><td>Driver No1</td>';
        $html .= generateTableRow($row['sch_no'], $daysInMonth, 'driver_1_name', $db);
        $html .= '</tr>';
        
        $html .= '<tr><td>Driver No2</td>';
        $html .= generateTableRow($row['sch_no'], $daysInMonth, 'driver_2_name', $db);
        $html .= '</tr>';

        $html .= '<tr><td>Arr Time</td>';
        $html .= generateTableRow($row['sch_no'], $daysInMonth, 'arr_time', $db);
        $html .= '</tr>';

        $html .= '</tbody></table></div><br>';
    }

    $stmt->close();

    echo json_encode(['html' => $html]);
} else {
    echo json_encode(['html' => 'Invalid request method']);
}
// Function to generate table rows for each day
function generateTableRow($sch_no, $daysInMonth, $field, $db) {
    $validFields = ['dep_time', 'arr_time', 'bus_number_1', 'driver_token_1', 'driver_token_2']; // Valid fields
    if (!in_array($field, $validFields)) {
        return ''; // Return an empty string if the field is not valid
    }

    $rowHtml = '';
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = date('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);

        // Correctly reference the schedule_master column
        $sch_field = 'sch_' . $field;

        $dailyQuery = "
        SELECT 
            svo.$field, TIMESTAMPDIFF(MINUTE, svo.$field, sm.$sch_field) AS time_diff
        FROM 
            sch_veh_out svo
        LEFT JOIN 
            schedule_master sm ON svo.sch_no = sm.sch_key_no
        WHERE 
            svo.departed_date = ? AND svo.sch_no = ?";
        
        $dailyStmt = $db->prepare($dailyQuery);
        if (!$dailyStmt) {
            // Output the SQL error
            die('Error in preparing statement: ' . $db->error . " with query: " . $dailyQuery);
        }

        $dailyStmt->bind_param('si', $currentDate, $sch_no);
        if (!$dailyStmt->execute()) {
            die('Error in executing statement: ' . $dailyStmt->error);
        }

        $dailyResult = $dailyStmt->get_result();
        if ($dailyRow = $dailyResult->fetch_assoc()) {
            if ($field == 'dep_time' || $field == 'arr_time') {
                // Time difference checks for dep_time and arr_time
                if ($dailyRow['time_diff'] > 30 || $dailyRow['time_diff'] < -30) {
                    $rowHtml .= '<td style="color:red;">&#x2716;</td>';
                } else {
                    $rowHtml .= '<td style="color:green;">&#x2714;</td>';
                }
            } else {
                // Display the field's value for other cases
                $rowHtml .= '<td>' . htmlspecialchars($dailyRow[$field]) . '</td>';
            }
        } else {
            $rowHtml .= '<td>NA</td>';
        }
    }
    return $rowHtml;
}

