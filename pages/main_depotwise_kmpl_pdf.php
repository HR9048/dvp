<?php
require_once('../includes/tcpdf/tcpdf.php');
include '../includes/connection.php';
include 'session.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
confirm_logged_in();
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    if (isset($_GET['date'])) {
        // Get the selected date from the query parameter
        $selectedDate = $_GET['date'];
        $firstDateOfMonth = date("Y-m-01", strtotime($selectedDate));

        // Define preferred order of divisions
        $preferredDivisions = array(
            "1" => "KLB1",
            "2" => "KLB2",
            "3" => "YDG",
            "4" => "BDR",
            "5" => "RCH",
            "6" => "KPL",
            "7" => "BLR",
            "8" => "HSP",
            "9" => "VJP",
        );

        // Fetch daily wise KMPL data for the selected date and cumulative data
        $query = "SELECT 
            k.division,
            l.depot as depot_name,
            SUM(CASE WHEN k.date = '$selectedDate' THEN k.total_km ELSE 0 END) AS daily_total_km,
            SUM(CASE WHEN k.date = '$selectedDate' THEN k.hsd ELSE 0 END) AS daily_hsd,
            SUM(CASE WHEN k.date = '$selectedDate' THEN k.kmpl ELSE 0 END) AS daily_kmpl,
            SUM(CASE WHEN k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate' THEN k.total_km ELSE 0 END) AS total_total_km,
            SUM(CASE WHEN k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate' THEN k.hsd ELSE 0 END) AS total_hsd,
            SUM(CASE WHEN k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate' THEN k.kmpl ELSE 0 END) AS total_kmpl
        FROM 
            kmpl_data k
        JOIN 
            location l ON k.depot = l.depot_id
        WHERE 
            k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate'
        GROUP BY 
            k.division, 
            depot_name 
        ORDER BY 
            FIELD(k.division, '" . implode("', '", array_keys($preferredDivisions)) . "'), 
            l.depot_id";
        $result = mysqli_query($db, $query) or die(mysqli_error($db));

        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Name');
        $pdf->SetTitle('DVP Report');
        $pdf->SetSubject('DVP Report');
        $pdf->SetKeywords('DVP, Report, KKRTC');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 8);
        $html = ''; // Initialize $html variable

        $html .= '<h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br><br><br>';
        $html .= '<table style="width: 100%; margin-top: 50px;">';
        $html .= '<tr>';
        $html .= '<td style="text-align: left;"><b>CENTRAL OFFICE</b></td>';
        $html .= '<td style="text-align: center;"><b>KALABUGAGI</b></td>';
        $html .= '<td style="text-align: right;"><b>' . date('d/m/Y', strtotime($selectedDate)) . '</b></td>';
        $html .= '</tr>';
        $html .= '</table><br><br>';
        $html .= '<table border="1" cellpadding="5">
<thead>
    <tr>
        <th rowspan="2" style="text-align: center;"><b>SL NO</b></th>
        <th rowspan="2" style="text-align: center;"><b>DIVISION</b></th>
        <th rowspan="2" style="text-align: center;"><b>DEPOT</b></th>
        <th colspan="3" style="text-align: center;"><b>Daily wise KM</b></th>
        <th colspan="3" style="text-align: center;"><b>Cumulative KMPL</b></th>
    </tr>
    <tr>
        <th><b>Total KM</b></th>
        <th><b>HSD</b></th>
        <th><b>KMPL</b></th>
        <th><b>Total KM</b></th>
        <th><b>HSD</b></th>
        <th><b>KMPL</b></th>
    </tr>
</thead>
<tbody>';

        $index = 1;
        $divisionTotal = array('total_km' => 0, 'hsd' => 0, 'kmpl' => 0);
        $cumulativeTotal = array('total_km' => 0, 'hsd' => 0, 'kmpl' => 0, 'total_total_km' => 0, 'total_hsd' => 0);
        $divisionCumulativeTotal = array();
        $currentDivision = null;

        while ($row = mysqli_fetch_assoc($result)) {
            if ($currentDivision !== $row['division']) {
                // Display division-wise total
                if ($currentDivision !== null) {
                    // Calculate KMPL for division total
                    if ($divisionTotal['hsd'] != 0) {
                        $divisionTotal['kmpl'] = number_format($divisionTotal['total_km'] / $divisionTotal['hsd'], 2);
                    } else {
                        $divisionTotal['kmpl'] = 0;
                    }
                    // Fetch division name from location table
                    $divisionQuery = "SELECT division FROM location WHERE division_id = '$currentDivision'";
                    $divisionResult = mysqli_query($db, $divisionQuery) or die(mysqli_error($db));
                    $divisionRow = mysqli_fetch_assoc($divisionResult);
                    $divisionName = isset($divisionRow['division']) ? $divisionRow['division'] : $currentDivision;
                    $html .= '<tr>
                <td colspan="3"><b>Total ' . $divisionName . '</b></td>
                <td><b>' . $divisionTotal['total_km'] . '</b></td>
                <td><b>' . $divisionTotal['hsd'] . '</b></td>
                <td><b>' . $divisionTotal['kmpl'] . '</b></td>
                <td><b>' . $divisionCumulativeTotal[$currentDivision]['total_km'] . '</b></td>
                <td><b>' . $divisionCumulativeTotal[$currentDivision]['hsd'] . '</b></td>
                <td><b>' . number_format($divisionCumulativeTotal[$currentDivision]['total_km'] / $divisionCumulativeTotal[$currentDivision]['hsd'], 2) . '</b></td>
            </tr>';
                }

                $divisionTotal = array('total_km' => 0, 'hsd' => 0, 'kmpl' => 0);
                $currentDivision = $row['division'];
            }

            $divisionTotal['total_km'] += $row['daily_total_km'];
            $divisionTotal['hsd'] += $row['daily_hsd'];
            $cumulativeTotal['total_km'] += $row['daily_total_km'];
            $cumulativeTotal['hsd'] += $row['daily_hsd'];
            $cumulativeTotal['total_total_km'] += $row['total_total_km'];
            $cumulativeTotal['total_hsd'] += $row['total_hsd'];

            if (!isset($divisionCumulativeTotal[$row['division']])) {
                $divisionCumulativeTotal[$row['division']] = array(
                    'total_km' => $row['total_total_km'],
                    'hsd' => $row['total_hsd']
                );
            } else {
                $divisionCumulativeTotal[$row['division']]['total_km'] += $row['total_total_km'];
                $divisionCumulativeTotal[$row['division']]['hsd'] += $row['total_hsd'];
            }

            $html .= '<tr>
        <td>' . $index . '</td>
        <td>' . (isset($preferredDivisions[$row['division']]) ? $preferredDivisions[$row['division']] : $row['division']) . '</td>
        <td>' . $row['depot_name'] . '</td>
        <td>' . $row['daily_total_km'] . '</td>
        <td>' . $row['daily_hsd'] . '</td>
        <td>' . ($row['daily_hsd'] > 0 ? number_format($row['daily_total_km'] / $row['daily_hsd'], 2) : '0.00') . '</td>
        <td>' . $row['total_total_km'] . '</td>
        <td>' . $row['total_hsd'] . '</td>
        <td>' . number_format($row['total_total_km'] / $row['total_hsd'], 2) . '</td>
    </tr>';

            $index++;
        }

        if ($currentDivision !== null) {
            if ($divisionTotal['hsd'] != 0) {
                $divisionTotal['kmpl'] = number_format($divisionTotal['total_km'] / $divisionTotal['hsd'], 2);
            } else {
                $divisionTotal['kmpl'] = 0;
            }
            $divisionQuery = "SELECT division FROM location WHERE division_id = '$currentDivision'";
            $divisionResult = mysqli_query($db, $divisionQuery) or die(mysqli_error($db));
            $divisionRow = mysqli_fetch_assoc($divisionResult);
            $divisionName = isset($divisionRow['division']) ? $divisionRow['division'] : $currentDivision;
            $html .= '<tr>
        <td colspan="3"><b>Total ' . $divisionName . '</b></td>
        <td><b>' . $divisionTotal['total_km'] . '</b></td>
        <td><b>' . $divisionTotal['hsd'] . '</b></td>
        <td><b>' . $divisionTotal['kmpl'] . '</b></td>
        <td><b>' . $divisionCumulativeTotal[$currentDivision]['total_km'] . '</b></td>
        <td><b>' . $divisionCumulativeTotal[$currentDivision]['hsd'] . '</b></td>
        <td><b>' . number_format($divisionCumulativeTotal[$currentDivision]['total_km'] / $divisionCumulativeTotal[$currentDivision]['hsd'], 2) . '</b></td>
    </tr>';
        }

        // Calculate grand total KMPL
        if ($cumulativeTotal['hsd'] != 0) {
            $cumulativeTotal['kmpl'] = number_format($cumulativeTotal['total_km'] / $cumulativeTotal['hsd'], 2);
        } else {
            $cumulativeTotal['kmpl'] = 0;
        }
        if ($cumulativeTotal['total_hsd'] != 0) {
            $cumulativeTotal['total_kmpl'] = number_format($cumulativeTotal['total_total_km'] / $cumulativeTotal['total_hsd'], 2);
        } else {
            $cumulativeTotal['total_kmpl'] = 0;
        }
        $html .= '<tr>
    <td colspan="3"><b>Total Corporation</b></td>
    <td><b>' . $cumulativeTotal['total_km'] . '</b></td>
    <td><b>' . $cumulativeTotal['hsd'] . '</b></td>
    <td><b>' . $cumulativeTotal['kmpl'] . '</b></td>
    <td><b>' . $cumulativeTotal['total_total_km'] . '</b></td>
    <td><b>' . $cumulativeTotal['total_hsd'] . '</b></td>
    <td><b>' . $cumulativeTotal['total_kmpl'] . '</b></td>
</tr>';

        $html .= '</tbody>
</table>';
        $html .= '<br><br><br><br>';
        $html .= '<table style="width: 90%;">';
        $html .= '<tr>';
        $html .= '<td style="text-align: left;"><b>JTO</b></td>';
        $html .= '<td style="text-align: center;"><b>DME</b></td>';
        $html .= '<td style="text-align: center;"><b>Dy-CME</b></td>';
        $html .= '<td style="text-align: right;"><b>CME</b></td>';
        $html .= '</tr>';
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document
        $formattedFileName = date('d_m_Y', strtotime($selectedDate));
        $fileName = $formattedFileName . '_depotwise_kmpl.pdf';
        // Close and output PDF document
        $pdf->Output($fileName, 'D');
        exit;
    } else {
        // Redirect to login.php if accessed directly without POST data
        header("Location: main_depotwise_kmpl.php");
    }
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}

?>