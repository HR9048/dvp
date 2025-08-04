<?php
require_once '../includes/tcpdf/tcpdf.php'; // Path to TCPDF library
require 'session.php'; // Include session handling script
include '../includes/connection.php'; // Include database connection script

// Check if selected_date is provided in the query string
if (isset($_GET['selected_date'])) {
    $selectedDate = $_GET['selected_date'];

    // Retrieve data from the database based on session variables and selected date
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];

    $sql = "SELECT  schedules, vehicles, spare, ORRWY, spareP, docking, ORDepot, ORDWS, CC, wup1, loan, wup, Police, notdepot, ORTotal, available, ES FROM dvp_data WHERE division = '$division' AND depot = '$depot' AND date = '$selectedDate'";

    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        // Initialize TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('KKRTC');
        $formattedDate = date('d-m-Y', strtotime($selectedDate));

        // Set the title and subject with the formatted date
        $pdf->SetTitle('DVP Print');
        $pdf->SetSubject('DVP Print Date: ' . $formattedDate);
        $pdf->SetKeywords('DVP, Report, KKRTC');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCellPadding(2); // Set padding for all table cells

        // Add a page
        $pdf->AddPage();

        // Set font size
        $pdf->SetFont('helvetica', '', 13);

        // Header section
        $html = '<h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br><br><br>
        <table style="width: 100%; margin-top: 50px;">
                    <tr>
                        <td style="text-align: left;"><b>' . $_SESSION['DIVISION'] . '</b></td>
                        <td style="text-align: center;"><b>' . $_SESSION['DEPOT'] . '</b></td>
                        <td style="text-align: right;"><b>' . date('d/m/Y', strtotime($selectedDate)) . '</b></td>
                    </tr>
                </table><br><br>';

        // Custom column headings
        $customHeadings = array(
            'schedules' => 'Number of Schedules',
            'vehicles' => 'Number Of Vehicles (Excluding RWY)',
            'spare' => 'Number of Spare Vehicles (Excluding RWY)',
            'spareP' => 'Percentage of Spare Vehicles (Excluding RWY)',
            'ORRWY' => 'Vehicles Off Road at RWY',
            'docking' => 'Vehicles stopped for Docking',
            'ORDepot' => 'Vehicles Off Road at Depot',
            'ORDWS' => 'Vehicles Off Road at DWS',
            'CC' => 'Vehicles Withdrawn for CC',
            'loan' => 'Vehicles loan given to other Depot/Training Center',
            'wup' => 'Vehicles Withdrawn for Fair',
            'wup1' => 'Vehicles Work Under Progress at Depot',
            'Police' => 'Vehicles at Police Station',
            'notdepot' => 'Vehicles Not Arrived to Depot',
            'Dealer' => 'Vehicles Held at Dealer Point',
            'ORTotal' => 'Total Vehicles not Available for Operation',
            'available' => 'Total Vehicles available for Operation',
            'ES' => 'Vehicles Excess/Shortage'
        );

        // Table header
        $html .= '<table style="width: 100%; border-collapse: collapse; border: 2px solid black;">
<tr style="background-color: white;">
    <th style="padding: 18px 18px 30px 18px; text-align: left; border: 1px solid black; width: 70%;"><b>Particulars</b></th>
    <th style="padding: 18px 18px 30px 18px; text-align: right; border: 1px solid black; width: 30%;"><b>DVP data</b></th>
</tr>';


        // Fetch data and add rows
        while ($row = $result->fetch_assoc()) {
            foreach ($row as $column => $value) {
                $heading = isset($customHeadings[$column]) ? $customHeadings[$column] : $column;
                $html .= '<tr>
                    <td style="padding: 18px; text-align: left; border: 1px solid black;">' . $heading . '</td>
                    <td style="padding: 18px; text-align: right; border: 1px solid black;">' . $value . '</td>
                </tr>';
            }
        }

        $html .= '</table>';

        $kmpldate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
        $formatedkmpldate = date('d/m/Y', strtotime($kmpldate));
        $kmplSql = "SELECT * FROM kmpl_data WHERE division = '$division' AND depot = '$depot' AND date = '$kmpldate'";
        $kmplResult = $db->query($kmplSql);
        if ($kmplResult->num_rows > 0) {
            $kmplRow = $kmplResult->fetch_assoc();
            $kmplHtml = '<br><br><table style="width: 100%; border-collapse: collapse; border: 2px solid black; margin-top: 20px;">
                <tr><th colspan="3" style="text-align:center; border: 1px solid black;" ><b>KMPL Details As on ' . $formatedkmpldate . '</b></th></tr>
                <tr>
                    <td style="border: 1px solid black;"><b>Total KM: </b>' . $kmplRow['total_km'] . '</td>
                    <td style="border: 1px solid black;"><b>HSD: </b>' . $kmplRow['hsd'] . '</td>
                    <td style="border: 1px solid black;"><b>KMPL: </b>' . $kmplRow['kmpl'] . '</td>
                </tr>
            </table>';
            $html .= $kmplHtml;
        } 



        // Footer section
        $html .= '<br><br><br><br><table style="width: 100%; margin-top: 50px;">
                    <tr>
                        <td style="text-align: left;"><b>CW</b></td>
                        <td style="text-align: center;"><b>CM/AWS</b></td>
                        <td style="text-align: right;"><b>DM</b></td>
                    </tr>
                </table>';
        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Generate formatted date for file name
        $formattedDate = date('d_m_Y', strtotime($selectedDate));
        $divisionName = $_SESSION['DIVISION'];
        $depotName = $_SESSION['DEPOT'];
        $fileName = $formattedDate . '_' . $divisionName . '_' . $depotName . '_dvp.pdf';

        // Close and output PDF document
        $pdf->Output($fileName, 'I');
        exit;
    } else {
        // Redirect to login.php if accessed directly without POST data
        header("Location:dvp_print.php");
    }

    $db->close();
} else {
    header("Location:dvp_print.php");
}
