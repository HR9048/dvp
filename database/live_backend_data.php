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
            $depotquery = "SELECT bd.*, bc.cause as cause_name, bc.reason as reason_name, l.kmpl_division, l.depot
                FROM bd_datas bd
                LEFT JOIN bd_cause bc ON bd.cause = bc.cause_id AND bd.reason = bc.reason_id
                LEFT JOIN location l ON bd.depot_id = l.depot_id AND bd.division_id = l.division_id
                WHERE $locationType
                AND bd.bd_date = ?
                ORDER BY l.division_id, l.depot_id, bd.bd_date ASC";
            $stmt = $db->prepare($depotquery);
            $stmt->bind_param("s", $selectedDate);
            $stmt->execute();
        } elseif ($subtype === "monthBD") {
            $depotquery = "SELECT bd.*, bc.cause as cause_name, bc.reason as reason_name, l.kmpl_division, l.depot
                FROM bd_datas bd
                LEFT JOIN bd_cause bc ON bd.cause = bc.cause_id AND bd.reason = bc.reason_id
                LEFT JOIN location l ON bd.depot_id = l.depot_id AND bd.division_id = l.division_id
                WHERE $locationType
                AND bd.bd_date between ? AND ?
                ORDER BY l.division_id, l.depot_id, bd.bd_date ASC";
            $stmt = $db->prepare($depotquery);
            $stmt->bind_param("ss", $startdateofmonth, $selectedDate);
            $stmt->execute();
        } elseif ($subtype === "cummBD") {
            $depotquery = "SELECT bd.*, bc.cause as cause_name, bc.reason as reason_name, l.kmpl_division, l.depot
                FROM bd_datas bd
                LEFT JOIN bd_cause bc ON bd.cause = bc.cause_id AND bd.reason = bc.reason_id
                LEFT JOIN location l ON bd.depot_id = l.depot_id AND bd.division_id = l.division_id
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

    if ($result->num_rows > 0) {
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
                    <th>Cause</th>
                    <th>Reason</th>
                    <th>KM After Docking</th>

                </tr>";
            $serial_number = 1;
            while ($row = $result->fetch_assoc()) {

                $html .= "<tr>
                    <td>{$serial_number}</td>
                    <td>" . date('d-m-Y', strtotime($row['bd_date'])) . "</td>
                    <td>{$row['bus_number']}</td>
                    <td>{$row['cause_name']}</td>
                    <td>{$row['reason_name']}</td>
                    <td>{$row['km_after_docking']}</td>
                </tr>";
                $serial_number++;
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
                    <th>Cause</th>
                    <th>Reason</th>
                    <th>KM After Docking</th>

                </tr>";
            $serial_number = 1;
            while ($row = $result->fetch_assoc()) {

                $html .= "<tr>
                    <td>{$serial_number}</td>
                    <td>{$row['depot']}</td>
                    <td>" . date('d-m-Y', strtotime($row['bd_date'])) . "</td>
                    <td>{$row['bus_number']}</td>
                    <td>{$row['cause_name']}</td>
                    <td>{$row['reason_name']}</td>
                    <td>{$row['km_after_docking']}</td>
                </tr>";
                $serial_number++;
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
                <th>Cause</th>
                <th>Reason</th>
                <th>KM After Docking</th>

            </tr>";
            $serial_number = 1;
            while ($row = $result->fetch_assoc()) {

                $html .= "<tr>
                <td>{$serial_number}</td>
                <td>{$row['kmpl_division']}</td>
                <td>{$row['depot']}</td>
                <td>" . date('d-m-Y', strtotime($row['bd_date'])) . "</td>
                <td>{$row['bus_number']}</td>
                <td>{$row['cause_name']}</td>
                <td>{$row['reason_name']}</td>
                <td>{$row['km_after_docking']}</td>
            </tr>";
                $serial_number++;
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
}
