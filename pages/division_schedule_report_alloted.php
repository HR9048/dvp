<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is expired. Please login.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'ASO(Stat)' || $_SESSION['JOB_TITLE'] == 'DC' || $_SESSION['JOB_TITLE'] == 'DTO') {
?>
<style>
    .hide {
        display: none;
    }

    th,
    td {
        border: 1px solid black;
        text-align: left;
        font-size: 15px;
        padding: 1px !important;
    }

    th {
        background-color: #f2f2f2;
    }

    .dataTable th,
    .dataTable td {
        padding: 1px !important;
    }

    .btn {
        padding-top: 0px;
        padding-bottom: 0px;
    }

    table {
        margin: 20px auto; /* Center the table horizontally */
        width: 95%; /* Set the maximum width to 70% */
        border-collapse: collapse;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:nth-child(odd) {
        background-color: #ffffff;
    }

    tr:hover {
        background-color: #f1f1f1;
    }
</style>
<div class="container1">
    <h4 class="text-center">Depot wise Schedule Master Updated Details</h4>
    <?php
    $divi= $_SESSION['DIVISION_ID'];
    // Fetch divisions and depots
    $locationQuery = "SELECT division, kmpl_division, division_id, depot, kmpl_depot, depot_id FROM location WHERE division_id= $divi AND depot NOT IN ('DIVISION')";
    $locationResult = $db->query($locationQuery);

    // Prepare an array to hold the final data
    $data = [];

    while ($row = $locationResult->fetch_assoc()) {
        $division = $row['division'];
        $depot = $row['depot'];
        $division1 = $row['kmpl_division'];
        $depot1 = $row['kmpl_depot'];
        $division2 = $row['division_id'];
        $depot2 = $row['depot_id'];

        // Fetch Driver, Conductor, DCC from API
        //$apiUrl = "http://117.203.105.106:50/data.php?division=$division1&depot=$depot1";
        $apiUrl = "http://localhost:8880/dvp/includes/data.php?division=$division1&depot=$depot1";

        $apiResponse = file_get_contents($apiUrl);
        $apiData = json_decode($apiResponse, true);

        $drivers = 0;
        $conductors = 0;
        $dccVehicles = 0;

        if (isset($apiData['data'])) {
            foreach ($apiData['data'] as $employee) {
                if ($employee['EMP_DESGN_AT_APPOINTMENT'] == 'DRIVER') {
                    $drivers++;
                } elseif ($employee['EMP_DESGN_AT_APPOINTMENT'] == 'CONDUCTOR') {
                    $conductors++;
                } elseif ($employee['EMP_DESGN_AT_APPOINTMENT'] == 'DRIVER-CUM-CONDUCTOR') {
                    $dccVehicles++;
                }
            }
        }

        // Fetch Vehicles from bus_registration
        $vehicleQuery = "SELECT COUNT(*) AS vehicle_count FROM bus_registration WHERE division_name='$division2' AND depot_name='$depot2'";
        $vehicleResult = $db->query($vehicleQuery)->fetch_assoc();
        $vehicles = $vehicleResult['vehicle_count'];

        // Fetch schedules from schedule_master
        $scheduleQuery = "SELECT 
                        COUNT(*) AS total_schedules, 
                        SUM(bus_number_1 IS NOT NULL) AS schedules_with_vehicles, 
                        SUM(driver_token_1 IS NOT NULL) AS schedules_with_crew 
                      FROM schedule_master 
                      WHERE division_id='$division2' AND depot_id='$depot2' AND status=1";
        $scheduleResult = $db->query($scheduleQuery)->fetch_assoc();

        $totalSchedules = $scheduleResult['total_schedules'];
        $schedulesWithVehicles = $scheduleResult['schedules_with_vehicles'];
        $schedulesWithCrew = $scheduleResult['schedules_with_crew'];

        // Store data
        $data[] = [
            'division' => $division,
            'depot' => $depot,
            'drivers' => $drivers,
            'conductors' => $conductors,
            'dcc_vehicles' => $dccVehicles,
            'vehicles' => $vehicles,
            'total_schedules' => $totalSchedules,
            'schedules_with_vehicles' => $schedulesWithVehicles,
            'schedules_with_crew' => $schedulesWithCrew,
            'division_id' => $division2,
            'depot_id' => $depot2
        ];
    }

    // Prepare arrays to hold totals
    $divisionTotals = [];
    $overallTotals = [
        'drivers' => 0,
        'conductors' => 0,
        'dcc_vehicles' => 0,
        'vehicles' => 0,
        'total_schedules' => 0,
        'schedules_with_vehicles' => 0,
        'schedules_with_crew' => 0,
    ];

    // Store data with division-wise grouping
    foreach ($data as $row) {
        $division = $row['division'];

        if (!isset($divisionTotals[$division])) {
            $divisionTotals[$division] = [
                'drivers' => 0,
                'conductors' => 0,
                'dcc_vehicles' => 0,
                'vehicles' => 0,
                'total_schedules' => 0,
                'schedules_with_vehicles' => 0,
                'schedules_with_crew' => 0,
            ];
        }

        // Update division-wise totals
        foreach ($divisionTotals[$division] as $key => $value) {
            $divisionTotals[$division][$key] += $row[$key];
        }

        // Update overall totals
        foreach ($overallTotals as $key => $value) {
            $overallTotals[$key] += $row[$key];
        }
    }
    ?>

    <!-- Display Data in Table -->
    <table border="1">
        <tr>
            <th>S.No</th>
            <th>Division</th>
            <th>Depot</th>
            <th>Drivers</th>
            <th>Conductors</th>
            <th>DCC</th>
            <th>Vehicles</th>
            <th>Total Schedules</th>
            <th>Schedules with Vehicles alloted</th>
            <th>Schedules with Crew alloted</th>
        </tr>
        <?php
        $serialNumber = 1;
        $currentDivision = '';
        foreach ($data as $row): ?>
            <?php if ($currentDivision != $row['division']): ?>
                <?php if ($currentDivision != ''): ?>
                    <!-- Display Division Total for the previous division -->
                    <tr style="font-weight:bold;">
                        <td colspan="3">Total for <?= $currentDivision; ?></td>
                        <td ><?= $divisionTotals[$currentDivision]['drivers']; ?></td>
                        <td><?= $divisionTotals[$currentDivision]['conductors']; ?></td>
                        <td><?= $divisionTotals[$currentDivision]['dcc_vehicles']; ?></td>
                        <td><?= $divisionTotals[$currentDivision]['vehicles']; ?></td>
                        <td><?= $divisionTotals[$currentDivision]['total_schedules']; ?></td>
                        <td><?= $divisionTotals[$currentDivision]['schedules_with_vehicles']; ?></td>
                        <td><?= $divisionTotals[$currentDivision]['schedules_with_crew']; ?></td>
                    </tr>
                <?php endif; ?>
                <!-- Change Division -->
                <?php $currentDivision = $row['division']; ?>
            <?php endif; ?>
            <tr>
                <td><?= $serialNumber++; ?></td>
                <td><?= $row['division']; ?></td>
                <td><?= $row['depot']; ?></td>
                <td><?= $row['drivers']; ?></td>
                <td><?= $row['conductors']; ?></td>
                <td><?= $row['dcc_vehicles']; ?></td>
                <td><?= $row['vehicles']; ?></td>
                <td><?= $row['total_schedules']; ?></td>
                <td><?= $row['schedules_with_vehicles']; ?></td>
                <td><?= $row['schedules_with_crew']; ?></td>
            </tr>
        <?php endforeach; ?>

        <!-- Display Total for the last division -->
        <?php if ($currentDivision != ''): ?>
            <tr style="font-weight:bold;">
                <td colspan="3">Total for <?= $currentDivision; ?></td>
                <td ><?= $divisionTotals[$currentDivision]['drivers']; ?></td>
                <td><?= $divisionTotals[$currentDivision]['conductors']; ?></td>
                <td><?= $divisionTotals[$currentDivision]['dcc_vehicles']; ?></td>
                <td><?= $divisionTotals[$currentDivision]['vehicles']; ?></td>
                <td><?= $divisionTotals[$currentDivision]['total_schedules']; ?></td>
                <td><?= $divisionTotals[$currentDivision]['schedules_with_vehicles']; ?></td>
                <td><?= $divisionTotals[$currentDivision]['schedules_with_crew']; ?></td>
            </tr>
        <?php endif; ?>

        
    </table>
</div>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}

include '../includes/footer.php';
?>
