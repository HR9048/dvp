<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech')) {
    date_default_timezone_set('Asia/Kolkata');
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    // Define the SQL query to fetch data


    $query = "SELECT bus_number, f_Division, f_Depot, t_Division, t_Depot, 
                 f_division_id, f_depot_id, t_division_id, t_depot_id, tr_date, created_by, status
          FROM vehicle_deputation
          WHERE deleted = 0 AND f_division_id = $division_id AND f_depot_id = $depot_id AND status IN (1, 2, 3)
          ORDER BY  tr_date";

$result = mysqli_query($db, $query);

$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bus_number = $row['bus_number'];

        // Group rows by EMP_PF_NUMBER and t_Division/t_Depot
        $key = $bus_number . '-' . $row['t_division_id'] . '-' . $row['t_depot_id'];
        if (!isset($data[$key])) {
            $data[$key] = [];
        }
        $data[$key][] = $row;
    }
}

// Process the data to group contiguous dates
$processedData = [];
foreach ($data as $key => $rows) {
    $groupedRows = [];
    $currentGroup = [];
    $previousDate = null;

    foreach ($rows as $row) {
        $currentDate = $row['tr_date'];

        if ($previousDate && (strtotime($currentDate) - strtotime($previousDate) > 86400)) {
            // Gap in dates found, save the current group
            $groupedRows[] = $currentGroup;
            $currentGroup = [];
        }

        $currentGroup[] = $row;
        $previousDate = $currentDate;
    }

    // Save the last group
    if (!empty($currentGroup)) {
        $groupedRows[] = $currentGroup;
    }

    $processedData[$key] = $groupedRows;
}

// Display the data in a table
echo '<h3 class="text-center">' . $_SESSION['DEPOT'] . ' Depot deputation to other Depot </h3><table border="1">';
echo '<tr>
        <th>Bus Number</th>
        <th>To Division</th>
        <th>To Depot</th>
        <th>From Date</th>
        <th>To Date</th>
        <th>Status</th>
        <th>Action</th>
      </tr>';

foreach ($processedData as $key => $groups) {
    foreach ($groups as $group) {
        // Get the first and last row in the group
        $firstRow = $group[0];
        $lastRow = end($group);

        // Determine status
        $status = '';
        $action = '';
        if ($firstRow['status'] == 1) {
            $status = 'Waiting for Deputation depot to receive';
            $action = '<button class="btn btn-danger" onclick="deleteRows(\'' . $firstRow['bus_number'] . '\', \'' . $firstRow['tr_date'] . '\', \'' . $lastRow['tr_date'] . '\')">Delete</button>';
        } elseif ($firstRow['status'] == 2) {
            $status = 'Received by Deputation depot';
            $action = ''; // No action for status 2
        } elseif ($firstRow['status'] == 3) {
            $status = 'Crew released from deputation depot waiting for receive';
            $action = '<button class="btn btn-success" onclick="receiveRows(\'' . $firstRow['bus_number'] . '\', \'' . $firstRow['tr_date'] . '\', \'' . $lastRow['tr_date'] . '\')">Receive</button>';
        }

        echo '<tr>';
        echo '<td>' . $firstRow['bus_number'] . '</td>';
        echo '<td>' . $firstRow['t_Division'] . '</td>';
        echo '<td>' . $firstRow['t_Depot'] . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($firstRow['tr_date'])) . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($lastRow['tr_date'])) . '</td>';
        echo '<td>' . $status . '</td>';
        echo '<td>' . $action . '</td>';
        echo '</tr>';
    }
}

echo '</table><br><br>';

   
    
    $query = "SELECT bus_number, f_Division, f_Depot, t_Division, t_Depot, 
                     f_division_id, f_depot_id, t_division_id, t_depot_id, tr_date, created_by, status
              FROM vehicle_deputation
              WHERE deleted = 0 and t_division_id = $division_id and t_depot_id = $depot_id and status in (1, 2)
              ORDER BY tr_date";

    $result = mysqli_query($db, $query);

    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $bus_number = $row['bus_number'];

            // Group rows by EMP_PF_NUMBER
            if (!isset($data[$bus_number])) {
                $data[$bus_number] = [];
            }
            $data[$bus_number][] = $row;
        }
    }

    // Process the data to group contiguous dates
    $processedData = [];
    foreach ($data as $bus_number => $rows) {
        $groupedRows = [];
        $currentGroup = [];
        $previousDate = null;

        foreach ($rows as $row) {
            $currentDate = $row['tr_date'];

            if ($previousDate && (strtotime($currentDate) - strtotime($previousDate) > 86400)) {
                // Gap in dates found, save the current group
                $groupedRows[] = $currentGroup;
                $currentGroup = [];
            }

            $currentGroup[] = $row;
            $previousDate = $currentDate;
        }

        // Save the last group
        if (!empty($currentGroup)) {
            $groupedRows[] = $currentGroup;
        }

        $processedData[$bus_number] = $groupedRows;
    }

    // Display the data in a table
    echo '<h3 class="text-center">Other Depot deputation to ' . $_SESSION['DEPOT'] . ' Depot</h3><table border="1">';
    echo '<tr>
            <th>Bus Number</th>
            <th>From Division</th>
            <th>From Depot</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>';

    foreach ($processedData as $bus_number => $groups) {
        foreach ($groups as $group) {
            // Get the first and last row in the group
            $firstRow = $group[0];
            $lastRow = end($group);

            // Determine status
            $status = '';
            $action = '';
            if ($firstRow['status'] == 1) {
                $status = 'Waiting for TI to receive Employee';
                $action = '<button class="btn btn-warning" onclick="receivefromRows(\'' . $bus_number . '\', \'' . $firstRow['tr_date'] . '\', \'' . $lastRow['tr_date'] . '\')">Receive</button>';
            } elseif ($firstRow['status'] == 2) {
                $status = 'Employee Received Waiting for Release';
                $action = '<button class="btn btn-success" onclick="releaseRows(\'' . $bus_number . '\', \'' . $firstRow['tr_date'] . '\', \'' . $lastRow['tr_date'] . '\')">Release</button>';
            } 

            echo '<tr>';
            echo '<td>' . $firstRow['bus_number'] . '</td>';
            echo '<td>' . $firstRow['f_Division'] . '</td>';
            echo '<td>' . $firstRow['f_Depot'] . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($firstRow['tr_date'])) . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($lastRow['tr_date'])) . '</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . $action . '</td>';
            echo '</tr>';
        }
    }

    echo '</table>';
    ?>
    <script>


function deleteRows(bus_number, fromDate, toDate) {
    performAction('vehicledeputatuiondelete', bus_number, fromDate, toDate);
}

function receiveRows(bus_number, fromDate, toDate) {
    performAction('vehicledeputationreceive', bus_number, fromDate, toDate);
}

function receivefromRows(bus_number, fromDate, toDate) {
    performAction('vehicledeputationreceivefrom', bus_number, fromDate, toDate);
}

function releaseRows(bus_number, fromDate, toDate) {
    performAction('vehicledeputationrelease', bus_number, fromDate, toDate);
}
function performAction(action, bus_number, fromDate, toDate) {
    // Confirm before proceeding with the action
    if (action === 'vehicledeputatuiondelete') {
        if (!confirm("Are you sure you want to delete rows for Bus Number: " + bus_number + " from " + fromDate + " to " + toDate + "?")) {
            return;
        }
    } else if (action === 'vehicledeputationreceive') {
        if (!confirm("Are you sure you want to mark rows for Bus Number: " + bus_number + " from " + fromDate + " to " + toDate + " as received?")) {
            return;
        }
    }else if (action === 'vehicledeputationreceivefrom') {
        if (!confirm("Are you sure you want to mark rows for Bus Number: " + bus_number + " from " + fromDate + " to " + toDate + " as received?")) {
            return;
        }
    }else if (action === 'vehicledeputationrelease') {
        if (!confirm("Are you sure you want to mark rows for Bus Number: " + bus_number + " from " + fromDate + " to " + toDate + " as release?")) {
            return;
        }
    }

    // Send the action request to PHP with PF number and date range
    fetch('../includes/data_fetch.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: action,
            bus_number: bus_number,
            fromDate: fromDate,
            toDate: toDate
        }),
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
        location.reload(); // Reload the page to reflect changes
    })
    .catch(error => console.error('Error:', error));
}

    </script>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>
