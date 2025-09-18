<?php
include '../includes/connection.php';
// Handle fetch depots action
date_default_timezone_set('Asia/Kolkata');



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_bd_data') {
    $selectedDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $formattedDate = date('d-m-Y', strtotime($selectedDate));
    $monthStartDate = date('Y-m-01', strtotime($selectedDate));
    $financialYearStartDate = date('Y-04-01', strtotime($selectedDate));

    $monthAbbr = date('M', strtotime($selectedDate));
    $displayLabel = $monthAbbr . " BD";

    // Initialize data arrays
    $dailyBdCounts = [];
    $monthlyBdCounts = [];
    $yearlyBdCounts = [];

    // Query 1: Daily BD count (BDs on selected date)
    $queryDaily = "SELECT 
    l.kmpl_division, 
    l.depot, 
    COALESCE(COUNT(bd.`bus_number`), 0) AS bd_count
FROM location l
LEFT JOIN bd_datas bd 
    ON l.division_id = bd.division_id 
    AND l.depot_id = bd.depot_id 
    AND bd.bd_date = '$selectedDate'
    AND bd.deleted != '1'
WHERE l.division_id NOT IN ('0', '10') 
AND l.depot != 'DIVISION' 
GROUP BY l.division_id, l.depot_id;
";
    $resultDaily = mysqli_query($db, $queryDaily);
    if (!$resultDaily) {
        die("Daily BD Query failed: " . mysqli_error($db));
    }
    while ($row = mysqli_fetch_assoc($resultDaily)) {
        $dailyBdCounts[$row['kmpl_division']][$row['depot']] = $row['bd_count'];
    }

    // Query 2: Monthly cumulative BD count
    $queryMonthly = "SELECT l.kmpl_division, l.depot, COUNT(bd.`bus_number`) AS bd_count
           FROM location l
           LEFT JOIN bd_datas bd 
           ON l.division_id = bd.division_id 
           AND l.depot_id = bd.depot_id 
           WHERE l.division_id NOT IN ('0', '10') 
           AND l.depot != 'DIVISION' 
           AND bd.bd_date BETWEEN '$monthStartDate' AND '$selectedDate'
           AND bd.deleted != '1'
           GROUP BY l.division_id, l.depot_id;";
    $resultMonthly = mysqli_query($db, $queryMonthly);
    if (!$resultMonthly) {
        die("Monthly BD Query failed: " . mysqli_error($db));
    }
    while ($row = mysqli_fetch_assoc($resultMonthly)) {
        $monthlyBdCounts[$row['kmpl_division']][$row['depot']] = $row['bd_count'];
    }

    // Query 3: Yearly cumulative BD count
    $queryYearly = "SELECT l.kmpl_division, l.depot, COUNT(bd.`bus_number`) AS bd_count
           FROM location l
           LEFT JOIN bd_datas bd 
           ON l.division_id = bd.division_id 
           AND l.depot_id = bd.depot_id 
           WHERE l.division_id NOT IN ('0', '10') 
           AND l.depot != 'DIVISION' 
           AND bd.bd_date BETWEEN '$financialYearStartDate' AND '$selectedDate'
           AND bd.deleted != '1'
           GROUP BY l.division_id, l.depot_id;";
    $resultYearly = mysqli_query($db, $queryYearly);
    if (!$resultYearly) {
        die("Yearly BD Query failed: " . mysqli_error($db));
    }
    while ($row = mysqli_fetch_assoc($resultYearly)) {
        $yearlyBdCounts[$row['kmpl_division']][$row['depot']] = $row['bd_count'];
    }

    // Build the final report
    $report = [];
    foreach ($dailyBdCounts as $division => $depots) {
        foreach ($depots as $depot => $dailyCount) {
            $monthlyCount = isset($monthlyBdCounts[$division][$depot]) ? $monthlyBdCounts[$division][$depot] : 0;
            $yearlyCount = isset($yearlyBdCounts[$division][$depot]) ? $yearlyBdCounts[$division][$depot] : 0;

            $report[] = [
                'division' => $division,
                'depot' => $depot,
                'daily_bd_count' => $dailyCount,
                'monthly_bd_count' => $monthlyCount,
                'yearly_bd_count' => $yearlyCount,
            ];
        }
    }

    echo json_encode([
        'formatted_date' => $formattedDate,
        'display_label' => $displayLabel,
        'data' => $report
    ]);
}
