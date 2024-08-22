<?php
include '../includes/connection.php';
include '../pages/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $month = $input['month'];
    $year = $input['year'];

    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    // Calculate the number of days in the selected month
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $tableHeader = "<th>Particular</th>";
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $tableHeader .= "<th>$i</th>";
    }

    // Initialize rows for the report
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

        // Log the date for debugging
        error_log("Fetching data for date: $date");

        // Query to fetch the data for each day
        $query = "SELECT 
                    COUNT(*) as total_departures,
                    COUNT(CASE WHEN dep_time_diff < 30 THEN 1 END) as departures_on_time,
                    COUNT(CASE WHEN arr_time_diff < 30 THEN 1 END) as arrivals_on_time,
                    COUNT(CASE WHEN bus_allotted_status = 0 THEN 1 END) as bus_operated,
                    COUNT(CASE WHEN driver_1_allotted_status = 0 THEN 1 END) as driver1_operated,
                    COUNT(CASE WHEN driver_2_allotted_status = 0 THEN 1 END) as driver2_operated,
                    COUNT(CASE WHEN conductor_alloted_status = 0 THEN 1 END) as conductor_operated
                  FROM sch_veh_out
                  WHERE depot_id = '$depot_id' AND division_id = '$division_id' AND departed_date = '$date'";

        $result = mysqli_query($db, $query);
        if ($result) {
            $data = mysqli_fetch_assoc($result);

            // Output the fetched data for debugging
            error_log(print_r($data, true)); // This will log the data to the server's error log

            // Calculate the required values
            $total_departures = $data['total_departures'];
            $departures_held = $total_departures; // Assuming all scheduled departures are held
            $departures_not_operated = $total_departures - $departures_held;

            $reportData['Actual Departures'][] = $total_departures;
            $reportData['Departures Held'][] = $departures_held;
            $reportData['Departures Not Operated'][] = $departures_not_operated;
            $reportData['Departures On Time'][] = $data['departures_on_time'];
            $reportData['Arrivals On Time'][] = $data['arrivals_on_time'];

            $reportData['Fixed Vehicles Operated (%)'][] = ($departures_held > 0) ? round(($data['bus_operated'] * 100) / $departures_held, 2) : 'N/A';
            $reportData['Fixed Driver 1 Operated (%)'][] = ($departures_held > 0) ? round(($data['driver1_operated'] * 100) / $departures_held, 2) : 'N/A';
            $reportData['Fixed Driver 2 Operated (%)'][] = ($departures_held > 0) ? round(($data['driver2_operated'] * 100) / $departures_held, 2) : 'N/A';
            $reportData['Fixed Conductor Operated (%)'][] = ($departures_held > 0) ? round(($data['conductor_operated'] * 100) / $departures_held, 2) : 'N/A';
        } else {
            error_log("Query failed: " . mysqli_error($db));
        }
    }

    // Build the table HTML
    $tableHtml = "<table class='table table-bordered'>";
    $tableHtml .= "<thead><tr>$tableHeader</tr></thead><tbody>";

    foreach ($reportData as $rowName => $rowData) {
        $tableHtml .= "<tr><td>$rowName</td>";
        foreach ($rowData as $cell) {
            $tableHtml .= "<td>$cell</td>";
        }
        $tableHtml .= "</tr>";
    }

    $tableHtml .= "</tbody></table>";

    echo json_encode(['html' => $tableHtml]);
}
?>
