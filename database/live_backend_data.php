<?php
include '../includes/connection.php';
// Handle fetch depots action
date_default_timezone_set('Asia/Kolkata');



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_bd_data') {
    $selectedDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $formattedDate = date('d-m-Y', strtotime($selectedDate));
    $monthStartDate = date('Y-m-01', strtotime($selectedDate));
    $year  = date('Y', strtotime($selectedDate));
    $month = date('n', strtotime($selectedDate));

    if ($month >= 4) {
        // April to December
        $financialYearStartDate = $year . '-04-01';
        $financialYearEndDate   = ($year + 1) . '-03-31';
    } else {
        // January to March
        $financialYearStartDate = ($year - 1) . '-04-01';
        $financialYearEndDate   = $year . '-03-31';
    }

    $monthAbbr = date('M', strtotime($selectedDate));
    $displayLabel = $monthAbbr . " BD";

    // Initialize data arrays
    $dailyBdCounts = [];
    $monthlyBdCounts = [];
    $yearlyBdCounts = [];

    // Query 1: Daily BD count (BDs on selected date)
    $queryDaily = "
    SELECT 
        l.division_id,
        l.depot_id,
        l.kmpl_division, 
        l.depot, 
        COALESCE(COUNT(bd.bus_number), 0) AS bd_count
    FROM location l
    LEFT JOIN bd_datas bd 
        ON l.division_id = bd.division_id 
        AND l.depot_id = bd.depot_id 
        AND bd.bd_date = '$selectedDate'
        AND bd.deleted != '1'
    WHERE l.division_id NOT IN ('0', '10') 
      AND l.depot != 'DIVISION' 
    GROUP BY l.division_id, l.depot_id
";
    $resultDaily = mysqli_query($db, $queryDaily);
    if (!$resultDaily) {
        die("Daily BD Query failed: " . mysqli_error($db));
    }

    $nextDate = date('Y-m-d', strtotime($selectedDate . ' +1 day'));

    while ($row = mysqli_fetch_assoc($resultDaily)) {
        $divisionId = $row['division_id'];
        $depotId = $row['depot_id'];
        $bdCount = $row['bd_count'];

        if ($bdCount == 0) {
            // Check if DVP entry exists for the next date
            $checkDvpQuery = "
            SELECT COUNT(*) AS dvp_exists 
            FROM dvp_data 
            WHERE division = '$divisionId' 
              AND depot = '$depotId' 
              AND date = '$nextDate'
        ";
            $checkResult = mysqli_query($db, $checkDvpQuery);
            $checkRow = mysqli_fetch_assoc($checkResult);

            if ($checkRow['dvp_exists'] > 0) {
                $dailyBdCounts[$row['kmpl_division']][$row['depot']] = 0; // DVP present → show 0
            } else {
                $dailyBdCounts[$row['kmpl_division']][$row['depot']] = '#'; // No DVP → show #
            }
        } else {
            $dailyBdCounts[$row['kmpl_division']][$row['depot']] = $bdCount;
        }
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_bd_detailed_data') {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $subtype = $_POST['subtype'];
    $selectedDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $month = date('m', strtotime($selectedDate));
    $year = date('Y', strtotime($selectedDate));
    //start financial year on every 1st april depend on the selected date
    if ($month < 4) {
        $financialYearStart = ($year - 1) . '-04-01';
    } else {
        $financialYearStart = $year . '-04-01';
    }
    $startdateofmonth = date('Y-m-01', strtotime($selectedDate));

    //VERIFY THE DATA EXIST OR NOT IF NOT RETURN HTML REQUIRED DATA ISSING
    $html = "";
    if (!$name || !$type || !$subtype || !$selectedDate) {
        $html .= "<p class='text-center text-danger'>Invalid parameters provided.</p>";
        echo json_encode(["status" => "success", "html" => $html]);
        exit;
    }
    $today = date("Y-m-d");

    if ($type === "Depot" || $type === "Division" || $type === "Overall") {
        if ($type === "Depot") {
            $depotQuery = "SELECT depot_id FROM location WHERE depot = ?";
            $stmt = $db->prepare($depotQuery);
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $depot_id = $row['depot_id'];
        } elseif ($type === "Division") {
            $depotQuery = "SELECT division_id FROM location WHERE kmpl_division = ?";
            $stmt = $db->prepare($depotQuery);
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $division_id = $row['division_id'];
        }
        if ($type === "Depot") {
            $locationType = "bd.depot_id = $depot_id";
        } elseif ($type === "Division") {
            $locationType = "bd.division_id = $division_id";
        } elseif ($type === "Overall") {
            $locationType = "1=1";
        }
        if ($subtype === "dayBD") {
            $depotquery = "SELECT bd.*, m.make_abbr as make, br.emission_norms, bc.cause as cause_name, bc.reason as reason_name, l.kmpl_division, l.depot
                FROM bd_datas bd
                LEFT JOIN bd_cause bc ON bd.cause = bc.cause_id AND bd.reason = bc.reason_id
                LEFT JOIN location l ON bd.depot_id = l.depot_id AND bd.division_id = l.division_id
                LEFT JOIN bus_registration br ON bd.bus_number = br.bus_number
                LEFT JOIN makes m ON br.make = m.make
                WHERE $locationType
                AND bd.bd_date = ?
                ORDER BY l.division_id, l.depot_id, bd.bd_date ASC";
            $stmt = $db->prepare($depotquery);
            $stmt->bind_param("s", $selectedDate);
            $stmt->execute();
        } elseif ($subtype === "monthBD") {
            $depotquery = "SELECT bd.*, m.make_abbr as make, br.emission_norms, bc.cause as cause_name, bc.reason as reason_name, l.kmpl_division, l.depot
                FROM bd_datas bd
                LEFT JOIN bd_cause bc ON bd.cause = bc.cause_id AND bd.reason = bc.reason_id
                LEFT JOIN location l ON bd.depot_id = l.depot_id AND bd.division_id = l.division_id
                LEFT JOIN bus_registration br ON bd.bus_number = br.bus_number
                LEFT JOIN makes m ON br.make = m.make
                WHERE $locationType
                AND bd.bd_date between ? AND ?
                ORDER BY l.division_id, l.depot_id, bd.bd_date ASC";
            $stmt = $db->prepare($depotquery);
            $stmt->bind_param("ss", $startdateofmonth, $selectedDate);
            $stmt->execute();
        } elseif ($subtype === "cummBD") {
            $depotquery = "SELECT bd.*, m.make_abbr as make, br.emission_norms, bc.cause as cause_name, bc.reason as reason_name, l.kmpl_division, l.depot
                FROM bd_datas bd
                LEFT JOIN bd_cause bc ON bd.cause = bc.cause_id AND bd.reason = bc.reason_id
                LEFT JOIN location l ON bd.depot_id = l.depot_id AND bd.division_id = l.division_id
                LEFT JOIN bus_registration br ON bd.bus_number = br.bus_number
                LEFT JOIN makes m ON br.make = m.make
                WHERE $locationType
                AND bd.bd_date between ? AND ?
                ORDER BY l.division_id, l.depot_id, bd.bd_date ASC";
            $stmt = $db->prepare($depotquery);
            $stmt->bind_param("ss", $financialYearStart, $selectedDate);
            $stmt->execute();
        } else {
            // Invalid subtype
            echo json_encode(["status" => "error", "message" => "Invalid subtype provided"]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid type provided"]);
        exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($type === "Depot") {
        if ($subtype === "dayBD") {
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Depot: <strong>$name</strong> on Date: <strong>" . date('d-m-Y', strtotime($selectedDate)) . "</strong></h6>";
        } elseif ($subtype === "monthBD") {
            $monthAbbr = date('M', strtotime($selectedDate));
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Depot: <strong>$name</strong> in Month: <strong>" . $monthAbbr . " " . date('Y', strtotime($selectedDate)) . "</strong></h6>";
        } elseif ($subtype === "cummBD") {
            $financialYear = (date('m', strtotime($selectedDate)) < 4) ? (date('Y', strtotime($selectedDate)) - 1) . "-" . date('Y', strtotime($selectedDate)) : date('Y', strtotime($selectedDate)) . "-" . (date('Y', strtotime($selectedDate)) + 1);
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Depot: <strong>$name</strong> in Year: <strong>" . $financialYear . "</strong></h6>";
        }
        $html .= "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; border-collapse: collapse;'>
                <tr>
                    <th>Sl. No</th>
                    <th>BD Date</th>
                    <th>Bus Number</th>
                    <th>Make</th>
                    <th>Norms</th>
                    <th>Cause</th>
                    <th>Reason</th>
                    <th>KM After Docking</th>

                </tr>";
        //if no row found in the result set then show no records found
        $serial_number = 1;
        if ($result->num_rows > 0) {
            // ✅ If rows are found
            while ($row = $result->fetch_assoc()) {
                $html .= "<tr>
            <td>{$serial_number}</td>
            <td>" . date('d-m-Y', strtotime($row['bd_date'])) . "</td>
            <td>{$row['bus_number']}</td>
            <td>{$row['make']}</td>
            <td>{$row['emission_norms']}</td>
            <td>{$row['cause_name']}</td>
            <td>{$row['reason_name']}</td>
            <td>{$row['km_after_docking']}</td>
        </tr>";
                $serial_number++;
            }
        } else {
            // ❌ No records found
            $html .= "<tr>
        <td colspan='6' style='text-align:center; color:red; font-weight:bold;'>No Data Found</td>
    </tr>";
        }

        $html .= "</table>";

        echo json_encode([
            "status" => "success",
            "html" => $html,
        ]);
    } elseif ($type === "Division") {
        if ($subtype === "dayBD") {
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Division: <strong>$name</strong> on Date: <strong>" . date('d-m-Y', strtotime($selectedDate)) . "</strong></h6>";
        } elseif ($subtype === "monthBD") {
            $monthAbbr = date('M', strtotime($selectedDate));
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Division: <strong>$name</strong> in Month: <strong>" . $monthAbbr . " " . date('Y', strtotime($selectedDate)) . "</strong></h6>";
        } elseif ($subtype === "cummBD") {
            $financialYear = (date('m', strtotime($selectedDate)) < 4) ? (date('Y', strtotime($selectedDate)) - 1) . "-" . date('Y', strtotime($selectedDate)) : date('Y', strtotime($selectedDate)) . "-" . (date('Y', strtotime($selectedDate)) + 1);
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Division: <strong>$name</strong> in Year: <strong>" . $financialYear . "</strong></h6>";
        }
        $html .= "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; border-collapse: collapse;'>
                <tr>
                    <th>Sl. No</th>
                    <th>Depot</th>
                    <th>BD Date</th>
                    <th>Bus Number</th>
                    <th>Make</th>
                    <th>Norms</th>
                    <th>Cause</th>
                    <th>Reason</th>
                    <th>KM After Docking</th>

                </tr>";
        $serial_number = 1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $html .= "<tr>
                    <td>{$serial_number}</td>
                    <td>{$row['depot']}</td>
                    <td>" . date('d-m-Y', strtotime($row['bd_date'])) . "</td>
                    <td>{$row['bus_number']}</td>
                    <td>{$row['make']}</td>
                    <td>{$row['emission_norms']}</td>
                    <td>{$row['cause_name']}</td>
                    <td>{$row['reason_name']}</td>
                    <td>{$row['km_after_docking']}</td>
                </tr>";
                $serial_number++;
            }
        } else {
            // ❌ No records found
            $html .= "<tr>
        <td colspan='7' style='text-align:center; color:red; font-weight:bold;'>No Data Found</td>
    </tr>";
        }
        $html .= "</table>";

        echo json_encode([
            "status" => "success",
            "html" => $html,
        ]);
    } elseif ($type === "Overall") {
        if ($subtype === "dayBD") {
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Corporation on Date: <strong>" . date('d-m-Y', strtotime($selectedDate)) . "</strong></h6>";
        } elseif ($subtype === "monthBD") {
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Corporation in Month: <strong>" . date('M', strtotime($selectedDate)) . " " . date('Y', strtotime($selectedDate)) . "</strong></h6>";
        } elseif ($subtype === "cummBD") {
            $financialYear = (date('m', strtotime($selectedDate)) < 4) ? (date('Y', strtotime($selectedDate)) - 1) . "-" . date('Y', strtotime($selectedDate)) : date('Y', strtotime($selectedDate)) . "-" . (date('Y', strtotime($selectedDate)) + 1);
            $html .= "<h6 class='text-center mb-3'>Break Down Details for Corporation in Year: <strong>" . $financialYear . "</strong></h6>";
        }
        $html .= "<table border='1' cellspacing='0' cellpadding='5' style='width:100%; border-collapse: collapse;'>
            <tr>
                <th>Sl. No</th>
                <th>Division</th>
                <th>Depot</th>
                <th>BD Date</th>
                <th>Bus Number</th>
                <th>Make</th>
                <th>Norms</th>
                <th>Cause</th>
                <th>Reason</th>
                <th>KM After Docking</th>

            </tr>";
        $serial_number = 1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $html .= "<tr>
                <td>{$serial_number}</td>
                <td>{$row['kmpl_division']}</td>
                <td>{$row['depot']}</td>
                <td>" . date('d-m-Y', strtotime($row['bd_date'])) . "</td>
                <td>{$row['bus_number']}</td>
                <td>{$row['make']}</td>
                <td>{$row['emission_norms']}</td>
                <td>{$row['cause_name']}</td>
                <td>{$row['reason_name']}</td>
                <td>{$row['km_after_docking']}</td>
            </tr>";
                $serial_number++;
            }
        } else {
            // ❌ No records found
            $html .= "<tr>
        <td colspan='8' style='text-align:center; color:red; font-weight:bold;'>No Data Found</td>
    </tr>";
        }
        $html .= "</table>";
        echo json_encode([
            "status" => "success",
            "html" => $html,
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No records found"]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetchprogramdata') {
    $date = $_POST['date'];
    $division_id  = 'All';
    $depot_id     = 'All';
    $program_type = 'All';
    $today        = date('Y-m-d');

    if (!$date || !$division_id || !$depot_id || !$program_type) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }

    // Get depot/division names
    $locationQuery = "SELECT division FROM location WHERE division_id = '$division_id' limit 1";
    $locationResult = mysqli_query($db, $locationQuery);
    $locationData = mysqli_fetch_assoc($locationResult);
    $divisionName = $locationData['division'] ?? 'Unknown';

    // Get depot/division names
    $locationQuery = "SELECT depot FROM location WHERE depot_id = '$depot_id'";
    $locationResult = mysqli_query($db, $locationQuery);
    $locationData = mysqli_fetch_assoc($locationResult);
    $depotName = $locationData['depot'] ?? 'Unknown';

    //if the division or depot is set as All then fetch all the data depending on the selection
    if ($division_id === 'All') {
        $divisionCondition = "1"; // No division filter
    } else {
        $divisionCondition = "prd.division_id = '$division_id'";
    }

    if ($depot_id === 'All') {
        $depotCondition = "1"; // No depot filter
    } else {
        $depotCondition = "prd.depot_id = '$depot_id'";
    }
        if ($division_id == 'All' && $depot_id == 'All') {
            $html = "<h3 class='text-center'>Pending Program Report for Central Office - $depotName</h3>";
        } elseif ($division_id != 'All' && $depot_id == 'All') {
            $html = "<h3 class='text-center'>Pending Program Report for $divisionName - All Depots</h3>";
        } elseif ($division_id != 'All' && $depot_id != 'All') {
            $html = "<h3 class='text-center'>Pending Program Report for $divisionName - $depotName</h3>";
        }
    
    echo "<h4 class='text-center'>Date: " . date('d-m-Y', strtotime($date)) . "</h4>";

    $sqlforreport = "SELECT prd.*, l.division, l.depot from program_summary_daily prd
    LEFT JOIN location l ON prd.depot_id = l.depot_id AND prd.division_id = l.division_id
    WHERE $divisionCondition
    AND $depotCondition
    and prd.summary_date = '$date'";

    $result = mysqli_query($db, $sqlforreport);
    echo "<table border='1' cellspacing='0' cellpadding='5' width='100%' style='margin-bottom: 30px; text-align:center;'>
              <thead>
                  <tr>
                      <th>SL No</th>
                      <th>Division</th>
                      <th>Depot</th>";
    if ($program_type == 'All') {
        echo "<th>Pending Docking</th><th>Delayed Docking</th><th>Pending EOC</th><th>Delayed EOC</th>";
    } elseif ($program_type == 'docking') {
        echo "<th>Pending Docking</th><th>Delayed Docking</th>";
    } elseif ($program_type == 'engine_oil_and_main_filter_change') {
        echo "<th>Pending EOC</th><th>Delayed EOC</th>";
    }
    echo "</tr>
              </thead>
              <tbody>";

    $sl_no = 1;
    $currentDivision = null;
    $divisionTotals = [
        'pending_docking' => 0,
        'delayed_docking' => 0,
        'pending_engine' => 0,
        'delayed_engine' => 0
    ];
    $grandTotals = [
        'pending_docking' => 0,
        'delayed_docking' => 0,
        'pending_engine' => 0,
        'delayed_engine' => 0
    ];

    while ($row = mysqli_fetch_assoc($result)) {
        // If division changes, print previous division total
        if ($currentDivision !== null && $currentDivision !== $row['division']) {
            echo "<tr style='font-weight:bold; background:#f0f0f0;'>
                    <td colspan='3' style='text-align:center;'>{$currentDivision} Total</td>";
            if ($program_type == 'All') {
                echo "<td>{$divisionTotals['pending_docking']}</td>
                                  <td>{$divisionTotals['delayed_docking']}</td>
                                  <td>{$divisionTotals['pending_engine']}</td>
                                  <td>{$divisionTotals['delayed_engine']}</td>";
            } elseif ($program_type == 'docking') {
                echo "<td>{$divisionTotals['pending_docking']}</td>
                                  <td>{$divisionTotals['delayed_docking']}</td>";
            } elseif ($program_type == 'engine_oil_and_main_filter_change') {
                echo "<td>{$divisionTotals['pending_engine']}</td>
                                  <td>{$divisionTotals['delayed_engine']}</td>";
            }
            echo "</tr>";

            // Reset division totals
            $divisionTotals = [
                'pending_docking' => 0,
                'delayed_docking' => 0,
                'pending_engine' => 0,
                'delayed_engine' => 0
            ];
        }

        // Update current division
        $currentDivision = $row['division'];

        // Print each depot row
        echo "<tr>
                <td>{$sl_no}</td>
                <td>{$row['division']}</td>
                <td>{$row['depot']}</td>";

        if ($program_type == 'All') {
            echo "<td>{$row['pending_docking']}</td>
                  <td>{$row['delayed_docking']}</td>
                  <td>{$row['pending_engine']}</td>
                  <td>{$row['delayed_engine']}</td>";
            $divisionTotals['pending_docking'] += $row['pending_docking'];
            $divisionTotals['delayed_docking'] += $row['delayed_docking'];
            $divisionTotals['pending_engine'] += $row['pending_engine'];
            $divisionTotals['delayed_engine'] += $row['delayed_engine'];

            $grandTotals['pending_docking'] += $row['pending_docking'];
            $grandTotals['delayed_docking'] += $row['delayed_docking'];
            $grandTotals['pending_engine'] += $row['pending_engine'];
            $grandTotals['delayed_engine'] += $row['delayed_engine'];
        } elseif ($program_type == 'docking') {
            echo "<td>{$row['pending_docking']}</td>
                  <td>{$row['delayed_docking']}</td>";
            $divisionTotals['pending_docking'] += $row['pending_docking'];
            $divisionTotals['delayed_docking'] += $row['delayed_docking'];
            $grandTotals['pending_docking'] += $row['pending_docking'];
            $grandTotals['delayed_docking'] += $row['delayed_docking'];
        } elseif ($program_type == 'engine_oil_and_main_filter_change') {
            echo "<td>{$row['pending_engine']}</td>
                  <td>{$row['delayed_engine']}</td>";
            $divisionTotals['pending_engine'] += $row['pending_engine'];
            $divisionTotals['delayed_engine'] += $row['delayed_engine'];
            $grandTotals['pending_engine'] += $row['pending_engine'];
            $grandTotals['delayed_engine'] += $row['delayed_engine'];
        }

        echo "</tr>";
        $sl_no++;
    }

    // Print last division total
    if ($currentDivision !== null) {
        echo "<tr style='font-weight:bold; background:#f0f0f0;'>
                <td colspan='3' style='text-align:center;'>{$currentDivision} Total</td>";
        if ($program_type == 'All') {
            echo "<td>{$divisionTotals['pending_docking']}</td>
                              <td>{$divisionTotals['delayed_docking']}</td>
                              <td>{$divisionTotals['pending_engine']}</td>
                              <td>{$divisionTotals['delayed_engine']}</td>";
        } elseif ($program_type == 'docking') {
            echo "<td>{$divisionTotals['pending_docking']}</td>
                              <td>{$divisionTotals['delayed_docking']}</td>";
        } elseif ($program_type == 'engine_oil_and_main_filter_change') {
            echo "<td>{$divisionTotals['pending_engine']}</td>
                              <td>{$divisionTotals['delayed_engine']}</td>";
        }
        echo "</tr>";
    }

    // Print corporation total
    if ($division_id === 'All') {
        echo "<tr style='font-weight:bold; background:#d0e0ff;'>
            <td colspan='3' style='text-align:center;'>Corporation Total</td>";
        if ($program_type == 'All') {
            echo "<td>{$grandTotals['pending_docking']}</td>
                          <td>{$grandTotals['delayed_docking']}</td>
                          <td>{$grandTotals['pending_engine']}</td>
                          <td>{$grandTotals['delayed_engine']}</td>";
        } elseif ($program_type == 'docking') {
            echo "<td>{$grandTotals['pending_docking']}</td>
                          <td>{$grandTotals['delayed_docking']}</td>";
        } elseif ($program_type == 'engine_oil_and_main_filter_change') {
            echo "<td>{$grandTotals['pending_engine']}</td>
                          <td>{$grandTotals['delayed_engine']}</td>";
        }
        echo "</tr>";
    }

    echo "</tbody></table>";

    exit;


}