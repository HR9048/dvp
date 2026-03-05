<?php
session_start();
require('../includes/connection.php');
require_once('../includes/tcpdf/tcpdf.php');

// Verify login
if (!isset($_SESSION['MEMBER_ID'])) {
    echo "<script>alert('You are not logged in. Please log in to continue.'); window.location='login.php';</script>";
    exit;
}

// Verify job type
$allowedJobs = ['Mech', 'DM', 'CME_CO', 'DME', 'DC'];
if (!in_array($_SESSION['JOB_TITLE'], $allowedJobs)) {
    echo "<script>alert('You do not have permission to access this page.'); window.location='login.php';</script>";
    exit;
}



// Create TCPDF object
$pdf = new TCPDF('L', PDF_UNIT, 'A2', true, 'UTF-8', false); // use A2 as per your earlier requirement
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('KKRTC');
$pdf->SetTitle('Annexure-H W3 Chart Report');
$pdf->SetMargins(25, 1, 1);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(5);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();
$pdf->SetY(25);
// Custom styles (since bootstrap is not supported)
$style = "
<style>
    h2, h3, h4 { text-align: center; }
   
</style>
";

// Heading
$html = $style;

// Get input parameters
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$bus_number = $_GET['bus_number'] ?? '';
$division_id = $_GET['division'] ?? '';
$depot_id = $_GET['depot'] ?? '';

if (!$from || !$to || !$division_id || !$depot_id) {
    echo "<script>alert('Missing required parameters.'); window.location='index.php';</script>";
    exit;
}

// Fetch depot/division names
$locationQuery = "SELECT division, depot FROM location WHERE division_id = '$division_id' AND depot_id = '$depot_id'";
$locationResult = mysqli_query($db, $locationQuery);
$locationData = mysqli_fetch_assoc($locationResult);
$divisionName = $locationData['division'] ?? 'Unknown';
$depotName = $locationData['depot'] ?? 'Unknown';

// Setup variables
$sameDay = $from === $to;

$busCondition = ($bus_number === 'All') ? "1=1" : "bus_number = '$bus_number'";
$from_date = date('Y-m-d', strtotime($from . ' -1 day'));
if (in_array($depot_id, ['1', '8', '12', '13', '14', '15'])) {
    $programstart_date = '2025-08-01';
    $formated_programstart_date = date('d-m-Y', strtotime($programstart_date));
} elseif (in_array($depot_id, ['2', '3', '4', '5', '6', '7', '9', '10', '11', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53'])) {
    $programstart_date = '2025-10-01';
    $formated_programstart_date = date('d-m-Y', strtotime($programstart_date));
}
$kmpl_start_date = $programstart_date;

$air_suspension_bus_category_array = ['Rajahamsa', 'Corona Sleeper AC', 'Sleeper AC', 'Regular Sleeper Non AC', 'Amoghavarsha Sleeper Non AC', 'Kalyana Ratha'];

$html .= "<h3>Annexure-H W3 Chart Report for $divisionName - $depotName</h3>";
$html .= $sameDay
    ? "<h4>Date: " . date('d-m-Y', strtotime($from)) . "</h4>"
    : "<h4>From: " . date('d-m-Y', strtotime($from)) . " To: " . date('d-m-Y', strtotime($to)) . "</h4>";

// Fetch all buses
$buses = [];
$fromDateTimestamp = strtotime($from_date);
$busQuery = "SELECT bus_number, make, emission_norms AS model, model_type, bus_sub_category
             FROM bus_registration 
             WHERE $busCondition 
             AND division_name = '$division_id' 
             AND depot_name = '$depot_id' 
             AND deleted != 1";
$busResult = mysqli_query($db, $busQuery);

while ($row = mysqli_fetch_assoc($busResult)) {
    $buses[$row['bus_number']] = $row;
}

// Prepare list of bus numbers
$busKeys = array_map(fn($b) => "'" . mysqli_real_escape_string($db, $b) . "'", array_keys($buses));
$busKeyList = implode(',', $busKeys);

// Fetch all program data for those buses
$progDataQuery = "SELECT * FROM program_data WHERE bus_number IN ($busKeyList)";
$progDataResult = mysqli_query($db, $progDataQuery);

// Group program data
$allProgramData = [];
while ($row = mysqli_fetch_assoc($progDataResult)) {
    $busNo = $row['bus_number'];
    $type = $row['program_type'];
    $programDate = $row['program_date'];

    $timestamp = ($programDate && $programDate !== '0000-00-00') ? strtotime($programDate) : null;

    $allProgramData[$busNo][$type][] = [
        'row' => $row,
        'date' => $timestamp,
        'is_null_date' => is_null($timestamp),
    ];
}

// Final program data map
$programDataMap = [];

foreach ($allProgramData as $busNo => $programs) {
    foreach ($programs as $type => $entries) {
        $chosen = null;

        // Split into categories
        $nullDates = array_filter($entries, fn($e) => $e['is_null_date']);
        $validDates = array_filter($entries, fn($e) => !$e['is_null_date']);

        $beforeOrOnFrom = array_filter($validDates, fn($e) => $e['date'] <= $fromDateTimestamp);
        $afterFrom = array_filter($validDates, fn($e) => $e['date'] > $fromDateTimestamp);

        if (!empty($beforeOrOnFrom)) {
            // ✅ Use latest before or on from_date
            usort($beforeOrOnFrom, fn($a, $b) => $b['date'] <=> $a['date']);
            $chosen = $beforeOrOnFrom[0]['row'];
        } elseif (!empty($afterFrom)) {
            if (!empty($nullDates)) {
                // ✅ Prefer null-date row over future-dated rows
                $chosen = $nullDates[0]['row'];
            } else {
                // ❌ No valid past or null entry, fallback (can also be set to empty values)
                $chosen = [
                    'bus_number' => $busNo,
                    'program_type' => $type,
                    'program_date' => null,
                    'km' => null
                ];
            }
        } elseif (!empty($nullDates)) {
            // ✅ Only null-dated row present
            $chosen = $nullDates[0]['row'];
        } else {
            // ❌ No data found at all
            $chosen = [
                'bus_number' => $busNo,
                'program_type' => $type,
                'program_date' => null,
                'km' => null
            ];
        }

        $programDataMap[$busNo][$type] = $chosen;
    }
}



// Fetch kmpl totals for all buses from 2025-08-01 to $from
$kmplQuery = "SELECT bus_number, date as reading_date, km_operated 
              FROM vehicle_kmpl 
              WHERE bus_number IN ($busKeyList) 
              AND date BETWEEN '$kmpl_start_date' AND '$from_date'";
$kmplResult = mysqli_query($db, $kmplQuery);
$kmplMap = [];
while ($row = mysqli_fetch_assoc($kmplResult)) {
    $busNo = $row['bus_number'];
    $date = $row['reading_date'];
    $km = (float) $row['km_operated'];
    $kmplMap[$busNo][$date] = ($kmplMap[$busNo][$date] ?? 0) + $km;
}

// Helper: sum km from a date range
function sumKms($data, $from, $to)
{
    $sum = 0;
    foreach ($data as $date => $km) {
        if ($date >= $from && $date <= $to) {
            $sum += $km;
        }
    }
    return round($sum, 2);
}

$slNo = 1;
$progQuery = "SELECT * FROM program_master";
$progResult = mysqli_query($db, $progQuery);
$programMasterMap = [];

while ($row = mysqli_fetch_assoc($progResult)) {
    $key = $row['make'] . '|' . $row['model'] . '|' . $row['model_type'];
    $programMasterMap[$key] = $row;
}
function calculateCumulativePerDay($initial_kms, $kmpl_data, $from, $to, $vehicleNo, $programName)
{
    global $db; // use your db connection

    $result = [];
    $current_kms = $initial_kms;
    $current = strtotime($from);
    $end = strtotime($to);

    // Fetch program dates from DB
    $programDates = [];
    $query = "SELECT program_date FROM program_data 
              WHERE bus_number = '$vehicleNo' 
              AND program_type = '$programName' 
              AND program_date BETWEEN '$from' AND '$to'";
    $res = mysqli_query($db, $query);
    while ($row = mysqli_fetch_assoc($res)) {
        $programDates[] = $row['program_date'];
    }

    // Convert to associative for faster lookup
    $programDateMap = array_flip($programDates);
    $reset = false;

    while ($current <= $end) {
        $date = date('Y-m-d', $current);
        $daily_km = $kmpl_data[$date] ?? null;

        if ($reset) {
            $current_kms = 0;
            $reset = false;
        }

        if (is_numeric($daily_km)) {
            $current_kms += (float)$daily_km;
        }

        if (isset($programDateMap[$date])) {
            $result[$date] = [
                'value' => round($current_kms, 2),
                'color' => 'green'
            ];
            $reset = true; // next day will reset
        } else {
            $result[$date] = [
                'value' => round($current_kms, 2),
                'color' => 'default'
            ];
        }

        $current = strtotime('+1 day', $current);
    }

    return $result;
}

$monthGroups = [];


// Fetch daily vehicle_kmpl data for selected range
$dailyKmplQuery = "SELECT bus_number, date, km_operated 
                   FROM vehicle_kmpl 
                   WHERE bus_number IN ($busKeyList) 
                   AND date BETWEEN '$from' AND '$to'";
$dailyKmplResult = mysqli_query($db, $dailyKmplQuery);
$dailyKmplData = [];
while ($row = mysqli_fetch_assoc($dailyKmplResult)) {
    $dailyKmplData[$row['bus_number']][$row['date']] = $row['km_operated'];
}

// Fetch w3_chart_data only if not present in kmpl
$w3Query = "SELECT bus_number, report_date AS date,operation_type as km_operated 
            FROM w3_chart_data 
            WHERE bus_number IN ($busKeyList)
            AND deleted != '1' 
            AND report_date BETWEEN '$from' AND '$to'";
$w3Result = mysqli_query($db, $w3Query);
$w3Data = [];
while ($row = mysqli_fetch_assoc($w3Result)) {
    $w3Data[$row['bus_number']][$row['date']] = $row['km_operated'];
}

$start = new DateTime($from);
$end = new DateTime($to);

while ($start <= $end) {
    $monthKey = strtoupper($start->format('M Y'));
    $monthGroups[$monthKey][] = clone $start;
    $start->modify('+1 day');
}
$totalDays = 0;
foreach ($monthGroups as $dates) {
    $totalDays += count($dates);
}
$dayWidth = 80 / $totalDays;
$formatted_from = date('d-m-y', strtotime($from_date));
foreach ($buses as $vehicleNo => $bus) {
    $make = $bus['make'];
    $model = $bus['model'];
    $modelType = $bus['model_type'];
    $subCategory = $bus['bus_sub_category'];

    // Get program master row
    $key = $make . '|' . $model . '|' . $modelType;
    $progRow = $programMasterMap[$key] ?? null;
    if (!$progRow) continue;

    $programs = [];
    foreach ($progRow as $key => $targetKms) {
        if (in_array($key, ['id', 'make', 'model', 'model_type', 'created_at', 'updated_at']) || $targetKms === null || $targetKms === '') continue;
        if ($key === 'air_suspension_check' && !in_array($subCategory, $air_suspension_bus_category_array)) continue;

        $programName = $key;
        $progData = $programDataMap[$vehicleNo][$programName] ?? null;
        $readableName = ucwords(str_replace('_', ' ', $programName));
        $programs[] = [
            'realname' => $programName,
            'name' => $readableName,
            'value' => $targetKms
        ];
    }

    // Generate table for this vehicle
    // -------------------------------------------
    // TCPDF-safe table (fixed header + inline CSS)
    // -------------------------------------------
    $html .= '<table border="1" cellspacing="0" cellpadding="5" style="font-size:9pt; margin-bottom:30px; width:100%;">';
    $html .= '<thead>';

    // Header row 1: fixed columns + months
    $html .= '<tr bgcolor="#d9d9d9">';
    $html .= '  <th align="center" style="font-weight:bold; width: 3%;">SL No</th>';
    $html .= '  <th align="center" style="font-weight:bold; width: 7%">Vehicle No</th>';
    $html .= '  <th rowspan="2" align="center" style="font-weight:bold; width: 5%">Program Target KMS</th>';
    $html .= '  <th rowspan="2" align="center" style="font-weight:bold; width: 5%">Cumm. program <br/> kms as on ' . $formatted_from . '</th>';

    foreach ($monthGroups as $monthYear => $dates) {
        $colspan = count($dates);
        $monthWidth = $colspan * $dayWidth;
        $html .= '<th colspan="' . $colspan . '" align="center" 
                     style="font-weight:bold; width:' . number_format($monthWidth, 2) . '%;">'
            . $monthYear . '</th>';
    }

    $html .= '</tr>';

    // Header row 2: day numbers
    $html .= '<tr bgcolor="#efefef">';
    $html .= "  <td rowspan='2'><b>$slNo</b></td>";
    $html .= "  <td rowspan='2'><b>{$vehicleNo}</b></td>";
    foreach ($monthGroups as $dates) {
        foreach ($dates as $dateObj) {
            $html .= '  <th align="center" style="font-weight:bold;">' . $dateObj->format('j') . '</th>';
        }
    }
    $html .= '</tr>';

    $html .= '</thead>';

    $html .= '<tbody>';
    $totalDays = 0;
    foreach ($monthGroups as $dates) {
        $totalDays += count($dates);
    }

    // First data row: labels "Program Name" and "Daily KMS"
    $html .= '<tr bgcolor="#f9f9f9" style="max-width: 100%;">';
    $html .= '  <td colspan="2" align="center" style="font-weight:bold; width: 10%">Program Name</td>';
    $html .= '  <td colspan="2" align="center" style="font-weight:bold; width: 10%">Daily KMS</td>';
    $dayWidth = ($totalDays > 0) ? (80 / $totalDays) : 0;

    foreach ($monthGroups as $dates) {
        foreach ($dates as $dateObj) {
            $dateStr = $dateObj->format('Y-m-d');
            $value = 'NA';
            if (isset($dailyKmplData[$vehicleNo][$dateStr])) {
                $value = $dailyKmplData[$vehicleNo][$dateStr];
            } elseif (isset($w3Data[$vehicleNo][$dateStr])) {
                $value = $w3Data[$vehicleNo][$dateStr];
            }
            $html .= '  <td align="center" style="width:' . number_format($dayWidth, 2) . '%;">' . $value . '</td>';
        }
    }
    $html .= '</tr>';

    // Program rows
    foreach ($programs as $prog) {
        $programName = strtolower(str_replace(' ', '_', $prog['name']));
        $progData    = $programDataMap[$vehicleNo][$programName] ?? null;
        $program_date = $progData['program_date'] ?? null;
        $completed_km = (float)($progData['program_completed_km'] ?? 0);
        $kmData       = $kmplMap[$vehicleNo] ?? [];


        $start_date = $from;
        if (empty($program_date) || $program_date == '0000-00-00') {
            $initial_cumm_kms = $completed_km + sumKms($kmData, $kmpl_start_date, $from_date);
            $data = 0;
        } elseif (!empty($program_date) && $program_date !== '0000-00-00' && strtotime($program_date) > strtotime($from_date)) {
            $initial_cumm_kms = sumKms($kmData, $kmpl_start_date, $from_date);
            $data = 1;
        } elseif (!empty($program_date) && $program_date !== '0000-00-00' && strtotime($program_date) == strtotime($from_date)) {
            $initial_cumm_kms = 0;
            $data = 2;
        } elseif (!empty($program_date) && $program_date !== '0000-00-00' && strtotime($program_date) < strtotime($from_date)) {
            $program_date1 = date('Y-m-d', strtotime($program_date . ' +1 day'));
            $initial_cumm_kms = sumKms($kmData, $program_date1, $from_date);
            $data = 3;
        } else {
            $start_date1 = date('Y-m-d', strtotime($program_date . ' +1 day'));
            $initial_cumm_kms = sumKms($kmData, $start_date1, $from_date);
            $data = 4;
        }

        $dailyCumm = calculateCumulativePerDay(
            $initial_cumm_kms,
            $dailyKmplData[$vehicleNo] ?? [],
            $from,
            $to,
            $vehicleNo,
            $prog['realname']
        );

        // Row start
        $html .= '<tr>';

        // Program name spans "SL No" + "Vehicle No" columns
        $html .= '  <td colspan="2" align="left" bgcolor="#f4f6f7" style="font-weight:bold;">' . $prog['name'] . '</td>';

        // Target + initial cumm kms
        $html .= '  <td align="center">' . $prog['value'] . '</td>';
        $html .= '  <td align="center">' . $initial_cumm_kms . '</td>';

        // Day-wise values
        $target = (float)$prog['value'];
        foreach ($monthGroups as $dates) {
            foreach ($dates as $dateObj) {
                $dateStr = $dateObj->format('Y-m-d');
                $d = $dailyCumm[$dateStr] ?? ['value' => 'NA', 'color' => 'default'];
                $val   = $d['value'];
                $color = $d['color'] ?? 'default';

                $cellAttr = ' align="center"';
                $bg = '';

                if (is_numeric($val)) {
                    if ($color === 'green') {
                        $bg = ' bgcolor="#27ae60" style="color:#ffffff;"';
                    } else {
                        $numVal = (float)$val;
                        if ($numVal > $target + 500) {
                            $bg = ' bgcolor="#e74c3c" style="color:#ffffff;"';
                        } elseif (abs($numVal - $target) <= 500) {
                            $bg = ' bgcolor="#f1c40f"';
                        }
                    }
                }

                $html .= '  <td' . $cellAttr . $bg . '>' . $val . '</td>';
            }
        }

        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table><br><br>';
    $slNo++;

    
}
$html .= '
<br><br><br>
<table width="100%" cellpadding="10">
    <tr>
        <td width="33%" style="text-align:center;">
            <div style="border-top:1px solid #000; width:60%; margin:0 auto;"></div>
            ME Clerk
        </td>
        <td width="33%" style="text-align:center;">
            <div style="border-top:1px solid #000; width:60%; margin:0 auto;"></div>
            CM/AWS
        </td>
        <td width="33%" style="text-align:center;">
            <div style="border-top:1px solid #000; width:60%; margin:0 auto;"></div>
            DM
        </td>
    </tr>
</table>
';
// Output HTML to PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output file
$pdf->Output("W3_Report_{$divisionName}_{$depotName}.pdf", 'I');
exit;
