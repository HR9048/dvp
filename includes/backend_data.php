<?php
include 'connection.php';
include '../pages/session.php';
// Handle fetch depots action
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_schedule') {
    $schedule_number = $_POST['schedule_number'] ?? null;
    $cancel_date = $_POST['cancel_date'] ?? null;

    if ($cancel_date) {
        // Convert the date to yyyy-mm-dd format
        try {
            $cancel_date = (new DateTime($cancel_date))->format('Y-m-d');
        } catch (Exception $e) {
            // Handle invalid date format
            echo json_encode(['success' => false, 'message' => 'Invalid cancel date.']);
            exit;
        }
    }

    $reason = $_POST['reason'] ?? null;
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $username = $_SESSION['USERNAME'];

    if ($schedule_number && $cancel_date && $reason) {
        // Check if the schedule is already canceled on the same date
        $check_query = "SELECT * FROM schedule_cancel WHERE sch_key_no = ? AND cancel_date = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bind_param('ss', $schedule_number, $cancel_date);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Fetch existing data for message
            $existing_entry = $check_result->fetch_assoc();
            $formatted_date = (new DateTime($existing_entry['cancel_date']))->format('d-m-Y');
            echo json_encode([
                'success' => false,
                'message' => "Schedule number ($schedule_number) is already canceled on date ($formatted_date)."
            ]);
            exit;
        }

        $check_stmt->close();

        // Insert the new cancellation record
        $query = "INSERT INTO schedule_cancel (sch_key_no, cancel_date, reason, division_id, depot_id, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param('sssiis', $schedule_number, $cancel_date, $reason, $division_id, $depot_id, $username);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'depotcrewdeputationsubmit') {
    if (!isset($_POST['tableData'])) {
        echo "No data received.";
        exit;
    }

    $tableData = json_decode($_POST['tableData'], true);
    if (empty($tableData)) {
        echo "No data provided.";
        exit;
    }
    $errors = [];
    $successCount = 0;
    function fetchEmployeeDatacrewdeputation($pfNumber)
    {
        $division = $_SESSION['KMPL_DIVISION'];
        $depot = $_SESSION['KMPL_DEPOT'];

        // Fetch data from the first API based on division and depot
        $url = 'http://localhost:8880/dvp/includes/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);
        $response = file_get_contents($url);
        if ($response === FALSE) {
            $errors[] = "Error occurred while fetching data from LMS API";
        }

        $data = json_decode($response, true);

        // Check if the data array is present and contains expected keys
        if (isset($data['data']) && is_array($data['data'])) {
            // Loop through the employee data to find the matching PF number
            foreach ($data['data'] as $employee) {
                if ($employee['EMP_PF_NUMBER'] === $pfNumber) {
                    return $employee; // Return employee data if found in the first API
                }
            }
        }

        // If the data is not found in the first API, call the second API
        $urlPrivate = 'http://localhost:8880/dvp/database/private_emp_api.php?division=' . urlencode($division) . '&depot=' .
            urlencode($depot);
        $responsePrivate = file_get_contents($urlPrivate);
        if ($responsePrivate === FALSE) {
            $errors[] = "Error occurred while fetching data from the private API";
        }

        $dataPrivate = json_decode($responsePrivate, true);

        // Check if the data array is present and contains expected keys
        if (isset($dataPrivate['data']) && is_array($dataPrivate['data'])) {
            // Loop through the employee data to find the matching PF number
            foreach ($dataPrivate['data'] as $employee) {
                if ($employee['EMP_PF_NUMBER'] === $pfNumber) {
                    return $employee; // Return employee data if found in the second API
                }
            }
        }

        // Return null if no employee is found in both APIs
        return null;
    }


    foreach ($tableData as $row) {
        $lmsdivision = $_SESSION['KMPL_DIVISION'];
        $lmsdepot = $_SESSION['KMPL_DEPOT'];
        $division_id = $_SESSION['DIVISION_ID'];
        $depot_id = $_SESSION['DEPOT_ID'];
        $username = $_SESSION['USERNAME'];
        $crewpfnumber = $row['crewToken'];
        $driver1Data = fetchEmployeeDatacrewdeputation($crewpfnumber);
        $designation = $row['designation'];
        $toDivision = $row['toDivision'];
        $toDepot = $row['toDepot'];
        // Fetch division_id and depot_id from the location table based on toDivision and toDepot
        $query = "SELECT division_id, depot_id FROM location WHERE kmpl_division = '$toDivision' AND kmpl_depot = '$toDepot'";
        $result = mysqli_query($db, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            // Fetch the division_id and depot_id
            $location = mysqli_fetch_assoc($result);
            $division_id1 = $location['division_id'];
            $depot_id1 = $location['depot_id'];
        } else {
            // Handle the error if no matching records are found
            $errors[] = "No matching division or depot found for '$toDivision' and '$toDepot'.";
            continue; // Skip this row if there's an error fetching division_id and depot_id
        }
        $fromDate = $row['fromDate'];
        $toDate = $row['toDate'];
        if (isset($driver1Data['EMP_PF_NUMBER'], $driver1Data['EMP_NAME'], $driver1Data['token_number'])) {
            $driver1pfno = $driver1Data['EMP_PF_NUMBER'];
            $driver1name = $driver1Data['EMP_NAME'];
            $driver1token = $driver1Data['token_number'];
        } else {
            $errors[] = "Error: API response does not contain the expected keys for Crew Details.";
        }
        // Validate the data (optional but recommended)
        if (empty($crewpfnumber) || empty($designation) || empty($toDivision) || empty($toDepot) || empty($fromDate) || empty($toDate) || empty($lmsdivision) || empty($lmsdepot) || empty($division_id) || empty($depot_id)) {
            $errors[] = "Missing fields in one or more rows.";
            continue; // Skip this row if it's invalid
        }

        // Convert fromDate and toDate to DateTime objects
        $startDate = new DateTime($fromDate);
        $endDate = new DateTime($toDate);
        $dateInterval = new DateInterval('P1D'); // Interval of 1 day

        $duplicateDates = [];

        // Check for duplicates in the database
        while ($startDate <= $endDate) {
            $currentDate = $startDate->format('Y-m-d');

            $duplicateCheckQuery = "SELECT tr_date FROM crew_deputation 
                                    WHERE EMP_PF_NUMBER = ? AND tr_date = ? AND deleted = 0";
            $stmtCheck = mysqli_prepare($db, $duplicateCheckQuery);
            mysqli_stmt_bind_param($stmtCheck, 'ss', $driver1pfno, $currentDate);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);

            if (mysqli_num_rows($resultCheck) > 0) {
                $duplicateDates[] = $startDate->format('d-m-Y');
            }

            $startDate->add($dateInterval);
        }

        if (!empty($duplicateDates)) {
            $duplicates[] = [
                'pfNumber' => $driver1pfno,
                'token' => $driver1token,
                'name' => $driver1name,
                'dates' => $duplicateDates
            ];
            continue;
        }

        // Reset start date
        $startDate = new DateTime($fromDate);
        // Prepare the insert query with placeholders
        $insertQuery = "INSERT INTO crew_deputation (EMP_PF_NUMBER, EMP_NAME, token_number, EMP_DESGN_AT_APPOINTMENT, t_Division, t_Depot, tr_date, f_Division, f_Depot, f_division_id, f_depot_id, t_division_id, t_depot_id, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Loop through each date between fromDate and toDate
        while ($startDate <= $endDate) {
            $currentDate = $startDate->format('Y-m-d'); // Format the date as YYYY-MM-DD

            // Prepare the statement
            $stmtInsert = mysqli_prepare($db, $insertQuery);

            // Bind parameters for the insert query
            mysqli_stmt_bind_param(
                $stmtInsert,
                'ssssssssssssss',
                $driver1pfno,
                $driver1name,
                $driver1token,
                $designation,
                $toDivision,
                $toDepot,
                $currentDate,
                $lmsdivision,
                $lmsdepot,
                $division_id,
                $depot_id,
                $division_id1,
                $depot_id1,
                $username
            );

            if (mysqli_stmt_execute($stmtInsert)) {
                $successCount++;
            } else {
                $errors[] = "Error inserting row with Crew Token $crewpfnumber for date $currentDate: " . mysqli_error($db);
            }

            $startDate->add($dateInterval);
        }
    }

    // Return response
    if (!empty($duplicates)) {
        $duplicateMessages = [];
        foreach ($duplicates as $duplicate) {
            $duplicateMessages[] = "PF Number: {$duplicate['pfNumber']} (Token: {$duplicate['token']} - Name: {$duplicate['name']}) has duplicate entries on dates: " . implode(', ', $duplicate['dates']);
        }
        echo "error: " . implode("; ", $duplicateMessages);
    } elseif (!empty($errors)) {
        echo "error: " . implode(", ", $errors);
    } else {
        echo "Crew Successfully Deputed. The receiver depot needs to confirm the deputation.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crewdeputatuiondelete' || $_POST['action'] === 'crewdeputationreceive') {
    // Get the parameters from the POST request
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $pfNumber = isset($_POST['pfNumber']) ? $_POST['pfNumber'] : '';
    $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
    $toDate = isset($_POST['toDate']) ? $_POST['toDate'] : '';
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    // Sanitize inputs for security
    $action = mysqli_real_escape_string($db, $action);
    $pfNumber = mysqli_real_escape_string($db, $pfNumber);
    $fromDate = mysqli_real_escape_string($db, $fromDate);
    $toDate = mysqli_real_escape_string($db, $toDate);

    // Check if required parameters are available
    if ($action && $pfNumber && $fromDate && $toDate) {
        if ($action == 'crewdeputatuiondelete') {
            // Delete records based on pfNumber and the date range (fromDate and toDate)
            $deleteQuery = "UPDATE crew_deputation 
                        SET deleted = 1 
                        WHERE EMP_PF_NUMBER = '$pfNumber' and f_division_id = '$division_id' and f_depot_id = '$depot_id'
                        AND tr_date BETWEEN '$fromDate' AND '$toDate' 
                        AND deleted = 0
                        AND status = 1";

            if (mysqli_query($db, $deleteQuery)) {
                echo "Records successfully deleted for PF Number: $pfNumber from $fromDate to $toDate.";
            } else {
                echo "Error deleting records: " . mysqli_error($db);
            }
        } elseif ($action == 'crewdeputationreceive') {
            // Update the status of the records to indicate they have been received
            $receiveQuery = "UPDATE crew_deputation 
                         SET status = 0 
                         WHERE EMP_PF_NUMBER = '$pfNumber' and f_division_id = '$division_id' and f_depot_id = '$depot_id'
                         AND tr_date BETWEEN '$fromDate' AND '$toDate' 
                         AND deleted = 0
                         and status = 3";

            if (mysqli_query($db, $receiveQuery)) {
                echo "Records successfully marked as received for PF Number: $pfNumber from $fromDate to $toDate.";
            } else {
                echo "Error updating records: " . mysqli_error($db);
            }
        } else {
            echo "Invalid action!";
        }
    } else {
        echo "Missing required parameters!";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crewdeputationreceivefrom' || $_POST['action'] === 'crewdeputationrelease') {
    // Get the parameters from the POST request
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $pfNumber = isset($_POST['pfNumber']) ? $_POST['pfNumber'] : '';
    $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
    $toDate = isset($_POST['toDate']) ? $_POST['toDate'] : '';
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    // Sanitize inputs for security
    $action = mysqli_real_escape_string($db, $action);
    $pfNumber = mysqli_real_escape_string($db, $pfNumber);
    $fromDate = mysqli_real_escape_string($db, $fromDate);
    $toDate = mysqli_real_escape_string($db, $toDate);

    // Check if required parameters are available
    if ($action && $pfNumber && $fromDate && $toDate) {
        if ($action == 'crewdeputationreceivefrom') {
            // Delete records based on pfNumber and the date range (fromDate and toDate)
            $deleteQuery = "UPDATE crew_deputation 
                        SET status = 2 
                        WHERE EMP_PF_NUMBER = '$pfNumber' and t_division_id = '$division_id' and t_depot_id = '$depot_id'
                        AND tr_date BETWEEN '$fromDate' AND '$toDate' 
                        AND deleted = 0
                        AND status = 1";

            if (mysqli_query($db, $deleteQuery)) {
                echo "Records successfully Received for PF Number: $pfNumber from $fromDate to $toDate.";
            } else {
                echo "Error deleting records: " . mysqli_error($db);
            }
        } elseif ($action == 'crewdeputationrelease') {
            // Update the status of the records to indicate they have been received
            $receiveQuery = "UPDATE crew_deputation 
                         SET status = 3 
                         WHERE EMP_PF_NUMBER = '$pfNumber' and t_division_id = '$division_id' and t_depot_id = '$depot_id'
                         AND tr_date BETWEEN '$fromDate' AND '$toDate' 
                         AND deleted = 0
                         and status =2";

            if (mysqli_query($db, $receiveQuery)) {
                echo "Records successfully marked as received for PF Number: $pfNumber from $fromDate to $toDate.";
            } else {
                echo "Error updating records: " . mysqli_error($db);
            }
        } else {
            echo "Invalid action!";
        }
    } else {
        echo "Missing required parameters!";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'depotvehicledeputationsubmit') {
    if (!isset($_POST['tableData'])) {
        echo "No data received.";
        exit;
    }

    $tableData = json_decode($_POST['tableData'], true);
    if (empty($tableData)) {
        echo "No data provided.";
        exit;
    }
    $errors = [];
    $successCount = 0;


    foreach ($tableData as $row) {
        $lmsdivision = $_SESSION['KMPL_DIVISION'];
        $lmsdepot = $_SESSION['KMPL_DEPOT'];
        $division_id = $_SESSION['DIVISION_ID'];
        $depot_id = $_SESSION['DEPOT_ID'];
        $username = $_SESSION['USERNAME'];
        $vehicleNo = $row['vehicleNo'];
        $toDivision = $row['toDivision'];
        $toDepot = $row['toDepot'];
        // Fetch division_id and depot_id from the location table based on toDivision and toDepot
        $query = "SELECT division_id, depot_id FROM location WHERE kmpl_division = '$toDivision' AND kmpl_depot = '$toDepot'";
        $result = mysqli_query($db, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            // Fetch the division_id and depot_id
            $location = mysqli_fetch_assoc($result);
            $division_id1 = $location['division_id'];
            $depot_id1 = $location['depot_id'];
        } else {
            // Handle the error if no matching records are found
            $errors[] = "No matching division or depot found for '$toDivision' and '$toDepot'.";
            continue; // Skip this row if there's an error fetching division_id and depot_id
        }
        $fromDate = $row['fromDate'];
        $toDate = $row['toDate'];

        // Validate the data (optional but recommended)
        if (empty($vehicleNo) || empty($toDivision) || empty($toDepot) || empty($fromDate) || empty($toDate) || empty($lmsdivision) || empty($lmsdepot) || empty($division_id) || empty($depot_id)) {
            $errors[] = "Missing fields in one or more rows.";
            continue; // Skip this row if it's invalid
        }

        // Convert fromDate and toDate to DateTime objects
        $startDate = new DateTime($fromDate);
        $endDate = new DateTime($toDate);
        $dateInterval = new DateInterval('P1D'); // Interval of 1 day

        $duplicateDates = [];

        // Check for duplicates in the database
        while ($startDate <= $endDate) {
            $currentDate = $startDate->format('Y-m-d');

            $duplicateCheckQuery = "SELECT tr_date FROM vehicle_deputation 
                                    WHERE bus_number = ? AND tr_date = ? AND deleted = 0";
            $stmtCheck = mysqli_prepare($db, $duplicateCheckQuery);
            mysqli_stmt_bind_param($stmtCheck, 'ss', $vehicleNo, $currentDate);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);

            if (mysqli_num_rows($resultCheck) > 0) {
                $duplicateDates[] = $startDate->format('d-m-Y');
            }

            $startDate->add($dateInterval);
        }

        if (!empty($duplicateDates)) {
            $duplicates[] = [
                'vehicleNo' => $vehicleNo,
                'dates' => $duplicateDates
            ];
            continue;
        }

        // Reset start date
        $startDate = new DateTime($fromDate);
        // Prepare the insert query with placeholders
        $insertQuery = "INSERT INTO vehicle_deputation (bus_number, t_Division, t_Depot, tr_date, f_Division, f_Depot, f_division_id, f_depot_id, t_division_id, t_depot_id, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Loop through each date between fromDate and toDate
        while ($startDate <= $endDate) {
            $currentDate = $startDate->format('Y-m-d'); // Format the date as YYYY-MM-DD

            // Prepare the statement
            $stmtInsert = mysqli_prepare($db, $insertQuery);

            // Bind parameters for the insert query
            mysqli_stmt_bind_param(
                $stmtInsert,
                'sssssssssss',
                $vehicleNo,
                $toDivision,
                $toDepot,
                $currentDate,
                $lmsdivision,
                $lmsdepot,
                $division_id,
                $depot_id,
                $division_id1,
                $depot_id1,
                $username
            );

            if (mysqli_stmt_execute($stmtInsert)) {
                $successCount++;
            } else {
                $errors[] = "Error inserting row with Crew Token $vehicleNo for date $currentDate: " . mysqli_error($db);
            }

            $startDate->add($dateInterval);
        }
    }

    // Return response
    if (!empty($duplicates)) {
        $duplicateMessages = [];
        foreach ($duplicates as $duplicate) {
            $duplicateMessages[] = "PF Number: {$duplicate['vehicleNo']} has duplicate entries on dates: " . implode(', ', $duplicate['dates']);
        }
        echo "error: " . implode("; ", $duplicateMessages);
    } elseif (!empty($errors)) {
        echo "error: " . implode(", ", $errors);
    } else {
        echo "Vehicle Successfully Deputed. The receiver depot needs to confirm the deputation.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vehicledeputatuiondelete' || $_POST['action'] === 'vehicledeputationreceive') {
    // Get the parameters from the POST request
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $bus_number = isset($_POST['bus_number']) ? $_POST['bus_number'] : '';
    $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
    $toDate = isset($_POST['toDate']) ? $_POST['toDate'] : '';
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    // Sanitize inputs for security
    $action = mysqli_real_escape_string($db, $action);
    $bus_number = mysqli_real_escape_string($db, $bus_number);
    $fromDate = mysqli_real_escape_string($db, $fromDate);
    $toDate = mysqli_real_escape_string($db, $toDate);

    // Check if required parameters are available
    if ($action && $bus_number && $fromDate && $toDate) {
        if ($action == 'vehicledeputatuiondelete') {
            // Delete records based on busNumber and the date range (fromDate and toDate)
            $deleteQuery = "UPDATE vehicle_deputation 
                        SET deleted = 1 
                        WHERE bus_number = '$bus_number' and f_division_id = '$division_id' and f_depot_id = '$depot_id'
                        AND tr_date BETWEEN '$fromDate' AND '$toDate' 
                        AND deleted = 0
                        AND status = 1";

            if (mysqli_query($db, $deleteQuery)) {
                echo "Records successfully deleted for Bus Number: $bus_number from $fromDate to $toDate.";
            } else {
                echo "Error deleting records: " . mysqli_error($db);
            }
        } elseif ($action == 'vehicledeputationreceive') {
            // Update the status of the records to indicate they have been received
            $receiveQuery = "UPDATE vehicle_deputation 
                         SET status = 0 
                         WHERE bus_number = '$bus_number' and f_division_id = '$division_id' and f_depot_id = '$depot_id'
                         AND tr_date BETWEEN '$fromDate' AND '$toDate' 
                         AND deleted = 0
                         and status = 3";

            if (mysqli_query($db, $receiveQuery)) {
                echo "Records successfully marked as received for Bus Number: $bus_number from $fromDate to $toDate.";
            } else {
                echo "Error updating records: " . mysqli_error($db);
            }
        } else {
            echo "Invalid action!";
        }
    } else {
        echo "Missing required parameters!";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vehicledeputationreceivefrom' || $_POST['action'] === 'vehicledeputationrelease') {
    // Get the parameters from the POST request
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $bus_number = isset($_POST['bus_number']) ? $_POST['bus_number'] : '';
    $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
    $toDate = isset($_POST['toDate']) ? $_POST['toDate'] : '';
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    // Sanitize inputs for security
    $action = mysqli_real_escape_string($db, $action);
    $bus_number = mysqli_real_escape_string($db, $bus_number);
    $fromDate = mysqli_real_escape_string($db, $fromDate);
    $toDate = mysqli_real_escape_string($db, $toDate);

    // Check if required parameters are available
    if ($action && $bus_number && $fromDate && $toDate) {
        if ($action == 'vehicledeputationreceivefrom') {
            // Delete records based on bus_number and the date range (fromDate and toDate)
            $deleteQuery = "UPDATE vehicle_deputation 
                        SET status = 2 
                        WHERE bus_number = '$bus_number' and t_division_id = '$division_id' and t_depot_id = '$depot_id'
                        AND tr_date BETWEEN '$fromDate' AND '$toDate' 
                        AND deleted = 0
                        AND status = 1";

            if (mysqli_query($db, $deleteQuery)) {
                echo "Records successfully Received for Bus Number: $bus_number from $fromDate to $toDate.";
            } else {
                echo "Error deleting records: " . mysqli_error($db);
            }
        } elseif ($action == 'vehicledeputationrelease') {
            // Update the status of the records to indicate they have been received
            $receiveQuery = "UPDATE vehicle_deputation 
                         SET status = 3 
                         WHERE bus_number = '$bus_number' and t_division_id = '$division_id' and t_depot_id = '$depot_id'
                         AND tr_date BETWEEN '$fromDate' AND '$toDate' 
                         AND deleted = 0
                         and status =2";

            if (mysqli_query($db, $receiveQuery)) {
                echo "Records successfully marked as received for Bus Number: $bus_number from $fromDate to $toDate.";
            } else {
                echo "Error updating records: " . mysqli_error($db);
            }
        } else {
            echo "Invalid action!";
        }
    } else {
        echo "Missing required parameters!";
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetchScheduleNos') {
    $depot_id = $_POST['depot_id'];
    $query = "SELECT sch_key_no FROM schedule_master WHERE depot_id = ? ORDER BY sch_key_no ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $depot_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Schedule</option>';
    echo '<option value="All">All</option>';
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['sch_key_no']}'>{$row['sch_key_no']}</option>";
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetchBusNumbers') {
    $depot_id = $_POST['depot_id'];
    $query = "SELECT bus_number FROM bus_registration WHERE depot_name = ? ORDER BY bus_number ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $depot_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Bus</option>';
    echo '<option value="All">All</option>';
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['bus_number']}'>{$row['bus_number']}</option>";
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'getDepotDetails') {
    $depot_id = $_POST['depot_id'];
    $query = "SELECT kmpl_division, kmpl_depot FROM location WHERE depot_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $depot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'operationalstatisticsupload') {

    if (!isset($_SESSION['MEMBER_ID'], $_SESSION['TYPE'], $_SESSION['JOB_TITLE'])) {
        echo json_encode(["status" => "error", "message" => "Session expired. Please log in again."]);
        exit;
    }

    if ($_SESSION['TYPE'] !== 'DIVISION' || $_SESSION['JOB_TITLE'] !== 'ASO(Stat)') {
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        exit;
    }

    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_POST['depot_id'];
    $selected_date = $_POST['selected_date'];

    if (!isset($_FILES['pdf_file'])) {
        echo json_encode(["status" => "error", "message" => "No file uploaded."]);
        exit;
    }

    $file = $_FILES['pdf_file'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validate file type and size
    if ($file_ext !== 'pdf') {
        echo json_encode(["status" => "error", "message" => "Only PDF files are allowed!"]);
        exit;
    }
    if ($file_size > 1048576) { // 1MB limit
        echo json_encode(["status" => "error", "message" => "File size should be 1MB or less!"]);
        exit;
    }

    // Define upload directory
    $upload_dir = '../../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // File name format: divisionID_depotID_selectedDate.pdf
    $new_file_name = $division_id . "_" . $depot_id . "_" . $selected_date . ".pdf";
    $file_path = $upload_dir . $new_file_name;

    // Move the uploaded file
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Check if record already exists for this depot_id and date
        $check_query = "SELECT id FROM operational_statistics WHERE depot_id = '$depot_id' AND date = '$selected_date'";
        $check_result = mysqli_query($db, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            // If record exists, update the file_name
            $update_query = "UPDATE operational_statistics SET file_name = '$new_file_name' 
                             WHERE depot_id = '$depot_id' AND date = '$selected_date'";
            if (mysqli_query($db, $update_query)) {
                echo json_encode(["status" => "success", "message" => "File updated successfully!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Database update error: " . mysqli_error($db)]);
            }
        } else {
            // If no existing record, insert a new one
            $insert_query = "INSERT INTO operational_statistics (division_id, depot_id, date, file_name) 
                             VALUES ('$division_id', '$depot_id', '$selected_date', '$new_file_name')";
            if (mysqli_query($db, $insert_query)) {
                echo json_encode(["status" => "success", "message" => "File uploaded successfully!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Database insert error: " . mysqli_error($db)]);
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to upload file!"]);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'fetchLatestFile') {

    $division_id = mysqli_real_escape_string($db, $_POST['division']);
    $depot_id = mysqli_real_escape_string($db, $_POST['depot']);

    $query = "SELECT file_name, date FROM operational_statistics 
              WHERE division_id = '$division_id' AND depot_id = '$depot_id' 
              ORDER BY date DESC LIMIT 1";

    $result = mysqli_query($db, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $file_path = "../../uploads/" . $row['file_name'];

        if (file_exists($file_path)) {
            echo json_encode(["file" => $row['file_name'], "date" => $row['date']]);
        } else {
            echo json_encode(["file" => "file_not_found"]);
        }
    } else {
        echo json_encode(["file" => "no_file"]);
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kmpl_delete_id']) && isset($_POST['action']) && $_POST['action'] == 'delete_kmpl') {
    $id = $_POST['kmpl_delete_id'];

    if (!is_numeric($id)) {
        echo json_encode(["status" => "error", "message" => "Invalid ID provided"]);
        exit;
    }

    $deleteQuery = "UPDATE vehicle_kmpl SET deleted = '1' WHERE id = '$id'";
    $result = $db->query($deleteQuery);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "KMPL details deleted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed: " . $db->error]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "othercorporationfindvehicle") {
    $bus_number = $_POST["bus_number"];
    $report_date = $_POST["report_date"];
    $division_id = $_SESSION["DIVISION_ID"]; // Adjust as needed
    $depot_id = $_SESSION["DEPOT_ID"]; // Adjust as needed
    if (!$bus_number || !$report_date) {
        echo json_encode(["error" => "Missing parameters"]);
        exit;
    }
    $query = "
            SELECT br.bus_number FROM bus_registration br
            WHERE br.division_name = '$division_id' AND br.depot_name = '$depot_id'
            UNION 
            SELECT vd.bus_number FROM vehicle_deputation vd
            WHERE vd.t_division_id = '$division_id' 
            AND vd.t_depot_id = '$depot_id' 
            AND vd.tr_date = '$report_date' 
            AND vd.status NOT IN (1) 
            AND vd.deleted = 0
        ";

    $result = mysqli_query($db, $query);
    $busExists = false;

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row["bus_number"] == $bus_number) {
            $busExists = true;
            break;
        }
    }

    echo json_encode(["exists" => $busExists]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_vehicle_kmpl') {
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? '';
    $selectedDate = $_POST['date'] ?? '';

    if (empty($id) || empty($type) || empty($selectedDate)) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    // Adjust date (Selected Date - 1)
    $adjustedDate = date('Y-m-d', strtotime($selectedDate));

    $whereClause = "";

    if ($type === 'Depot') {
        $whereClause = "WHERE v.depot_id = '$id'";
    } elseif ($type === 'Division') {
        $whereClause = "WHERE v.division_id = '$id'";
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        exit;
    }

    $query = "SELECT 
                v.bus_number, v.route_no, v.km_operated, v.hsd, v.kmpl,
                v.driver_1_pf, v.driver_2_pf, l.depot as depot_name, l.kmpl_division, l.kmpl_depot
              FROM vehicle_kmpl v
              LEFT JOIN location l ON v.depot_id = l.depot_id
              $whereClause AND v.date = '$adjustedDate' and deleted != '1'
              order by v.depot_id, v.bus_number";

    $result = mysqli_query($db, $query);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'SQL Error: ' . mysqli_error($db)]);
        exit;
    }

    if (mysqli_num_rows($result) > 0) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found']);
    }
}
if (isset($_POST['action']) && isset($_POST['action']) && $_POST['action'] == 'fetchvehiclekmpldataentereddetails') {
    $selected_date = $_POST['selected_date'];
    $formatted_date = date('d-m-Y', strtotime($selected_date));
    $query = "SELECT 
    l.division, 
    l.division_id, 
    l.depot_id, 
    l.depot,
    COUNT(DISTINCT svo.vehicle_no) AS total_buses,
    COUNT(DISTINCT vk.bus_number) AS kmpl_registered
FROM location l
LEFT JOIN sch_veh_out svo 
    ON svo.depot_id = l.depot_id 
    AND svo.arr_date = '$selected_date'
LEFT JOIN vehicle_kmpl vk 
    ON vk.depot_id = l.depot_id 
    AND vk.date = '$selected_date'
where l.division_id not in ('0', '10') and l.DEPOT != 'DIVISION' and vk.deleted != '1'
GROUP BY l.division_id, l.depot_id
ORDER BY l.division_id, l.depot_id";

    $result = mysqli_query($db, $query);

    $table = '<h2 class="text-center">Vehicle wise kmpl entered report on date :' . $formatted_date . '</h2><table border="1" id="reportTable">
                <thead>
                    <tr>
                        <th>Sl. No</th>
                        <th>Division</th>
                        <th>Depot</th>
                        <th>Vehicles Operated in ORMS</th>
                        <th>Vehicles KMPL Registered</th>
                        <th>Difference</th>
                    </tr>
                </thead>
                <tbody>';

    $sl_no = 1;
    $previous_division = null;
    $division_total_buses = 0;
    $division_total_registered = 0;
    $overall_total_buses = 0;
    $overall_total_registered = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $not_operated = $row['total_buses'] - $row['kmpl_registered'];

        // Check if division has changed, and print division total before moving to the next
        if ($previous_division !== null && $previous_division !== $row['division']) {
            $division_not_operated = $division_total_buses - $division_total_registered;
            $table .= '<tr style="font-weight: bold; background-color: #e0e0e0;">
                            <td></td>
                            <td colspan="2">' . $previous_division . '</td>
                            <td>' . $division_total_buses . '</td>
                            <td>' . $division_total_registered . '</td>
                            <td>' . $division_not_operated . '</td>
                       </tr>';
            $division_total_buses = 0;
            $division_total_registered = 0;
        }

        // Row for each depot
        $table .= '<tr>
                        <td>' . $sl_no . '</td>
                        <td>' . $row['division'] . '</td>
                        <td>' . $row['depot'] . '</td>
                        <td>' . $row['total_buses'] . '</td>
                        <td>' . $row['kmpl_registered'] . '</td>
                        <td>' . $not_operated . '</td>
                   </tr>';
        $sl_no++;

        // Accumulate division totals
        $division_total_buses += $row['total_buses'];
        $division_total_registered += $row['kmpl_registered'];
        $overall_total_buses += $row['total_buses'];
        $overall_total_registered += $row['kmpl_registered'];

        $previous_division = $row['division'];
    }

    // Print the last division total after the loop
    if ($previous_division !== null) {
        $division_not_operated = $division_total_buses - $division_total_registered;
        $table .= '<tr style="font-weight: bold; background-color: #e0e0e0;">
                        <td></td>
                        <td colspan="2">' . $previous_division . '</td>
                        <td>' . $division_total_buses . '</td>
                        <td>' . $division_total_registered . '</td>
                        <td>' . $division_not_operated . '</td>
                   </tr>';
    }

    // Overall Total
    $overall_not_operated = $overall_total_buses - $overall_total_registered;
    $table .= '<tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td></td>
                    <td>Overall Total</td>
                    <td></td>
                    <td>' . $overall_total_buses . '</td>
                    <td>' . $overall_total_registered . '</td>
                    <td>' . $overall_not_operated . '</td>
               </tr>';

    $table .= '</tbody></table>';

    echo $table;
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'fetchvehiclekmpldataentereddetailsdivision') {

    $selected_date = mysqli_real_escape_string($db, $_POST['selected_date']);
    $formatted_date = date('d-m-Y', strtotime($selected_date));
    $division_id = mysqli_real_escape_string($db, $_SESSION['DIVISION_ID']);

    $query = "SELECT 
                l.division, 
                l.division_id, 
                l.depot_id, 
                l.depot,
                COUNT(DISTINCT svo.vehicle_no) AS total_buses,
                COUNT(DISTINCT vk.bus_number) AS kmpl_registered
              FROM location l
              LEFT JOIN sch_veh_out svo 
                ON svo.depot_id = l.depot_id 
                AND svo.arr_date = '$selected_date'
              LEFT JOIN vehicle_kmpl vk 
                ON vk.depot_id = l.depot_id 
                AND vk.date = '$selected_date'
              WHERE l.division_id NOT IN ('0', '10') 
                AND l.depot != 'DIVISION' 
                AND l.division_id = '$division_id' and vk.deleted != '1'
              GROUP BY l.division_id, l.depot_id
              ORDER BY l.division_id, l.depot_id";

    $result = mysqli_query($db, $query);

    $table = '<h2 class="text-center">Vehicle-wise KMPL Entered Report on ' . $formatted_date . '</h2>
              <table border="1" id="reportTable">
                <thead>
                    <tr>
                        <th>Sl. No</th>
                        <th>Depot</th>
                        <th>Vehicles Operated in ORMS</th>
                        <th>Vehicles KMPL Registered</th>
                        <th>Difference</th>
                    </tr>
                </thead>
                <tbody>';

    $sl_no = 1;
    $total_buses = 0;
    $total_registered = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $not_operated = $row['total_buses'] - $row['kmpl_registered'];

        // Depot-level data
        $table .= '<tr>
                        <td>' . $sl_no . '</td>
                        <td>' . $row['depot'] . '</td>
                        <td>' . $row['total_buses'] . '</td>
                        <td>' . $row['kmpl_registered'] . '</td>
                        <td>' . $not_operated . '</td>
                   </tr>';
        $sl_no++;

        // Accumulate division totals
        $total_buses += $row['total_buses'];
        $total_registered += $row['kmpl_registered'];
    }

    // Display division total
    $division_not_operated = $total_buses - $total_registered;
    $table .= '<tr style="background-color: #e0e0e0; font-weight: bold;">
                    <td></td>
                    <td>Division Total</td>
                    <td>' . $total_buses . '</td>
                    <td>' . $total_registered . '</td>
                    <td>' . $division_not_operated . '</td>
               </tr>';

    $table .= '</tbody></table>';

    echo $table;
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "inventorypartsdetailsfetch") {
    $part_name = $_POST['part_name'];

    if (empty($part_name)) {
        echo "<p>Part name is required.</p>";
        exit;
    }

    if ($part_name == 'Engine') {
        echo "<form id='engineForm'>"; // Wrap all fields inside a form
        echo "<div class='mb-3'>
        <label class='form-label'>Engine Card No:</label> 
        <input type='text' name='engine_card_number' id='engine_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Engine No:</label>
        <input type='text' name='engine_number' id='engine_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Engine Make:</label>
            <select name='engine_make' id='engine_make' class='form-control' required>";

        $sql = "SELECT * FROM makes";
        $result = mysqli_query($db, $sql);
        echo "<option value=''>Select Engine Make</option>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['make'] . "'>" . $row['make'] . "</option>";
        }

        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Engine Model:</label>
            <select name='engine_model' id='engine_model' class='form-control' required>
                <option value=''>Select Engine Model</option>";

        $sql = "SELECT * FROM norms";
        $result = mysqli_query($db, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['emission_norms'] . "'>" . $row['emission_norms'] . "</option>";
        }

        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Engine Type:</label>
            <select name='engine_type' id='engine_type' class='form-control' required>
                <option value=''>Select Engine Type</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Engine Condition:</label>
            <select name='engine_condition' id='engine_condition' class='form-control' required>
                <option value=''>Select Engine Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
                <option value='RECON'>RECON</option>
            </select>
        </div>";

        /*echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='progressive_km' id='progressive_km' class='form-control' required>
        </div>";*/

        echo "<div class='mb-3'>
            <button type='button' class='btn btn-primary' id='engine_submit'>Submit</button>
        </div>";

        echo "</form>"; // Close form

?>
        <script>
            $(document).ready(function() {
                // Fetch Engine Type based on Engine Make selection
                $('#engine_make').change(function() {
                    var make = $(this).val();
                    if (make !== "") {
                        $.ajax({
                            url: '../includes/backend_data.php',
                            type: 'POST',
                            data: {
                                action: 'fetchenginetypeforinventory',
                                engine_make: make
                            },
                            success: function(response) {
                                $('#engine_type').html(response);
                            }
                        });
                    } else {
                        $('#engine_type').html('<option value="">Select Engine Type</option>');
                    }
                });

                // Bind event listener for submit button
                $("#engine_submit").click(function() {
                    submitEngineForm();
                });
            });


            function submitEngineForm() {
                let form = $("#engineForm");
                let isValid = true;
                let missingFields = [];

                // Validate required fields
                form.find(".form-control").each(function() {
                    let inputValue = $(this).val();
                    if (inputValue === null || inputValue.trim() === "") {
                        let label = $(this).prev("label").text().trim(); // Get label text
                        missingFields.push(label);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        html: "<b>Please fill in the following fields:</b><br><br>" + missingFields.join("<br>"),
                    });
                    return;
                }

                // Prepare form data
                let formData = new FormData(form[0]);
                formData.append("action", "submitengineinventoryform");

                // AJAX Submission
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            let res = JSON.parse(response); // Parse JSON response
                            console.log("Server Response:", res);

                            if (res.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: res.message,
                                    confirmButtonText: 'OK' // Ensures user clicks OK
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload only after clicking OK
                                    }
                                });
                            } else if (res.status === "error") {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed!',
                                    html: res.messages ? res.messages.join("<br>") : res.message,
                                });
                            }
                        } catch (e) {
                            console.error("JSON Parse Error:", e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Invalid server response format.',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while submitting the form.',
                        });
                    }
                });

            }
        </script>
    <?php
    } elseif ($part_name == 'gear_box') {
        echo "<form id='gearBoxForm'>"; // Wrap all fields inside a form
        echo "<div class='mb-3'>
        <label class='form-label'>Gear Box Card No:</label> 
        <input type='text' name='gear_box_card_number' id='gear_box_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Gear Box No:</label>
        <input type='text' name='gear_box_number' id='gear_box_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Gear Box Make:</label>
            <select name='gear_box_make' id='gear_box_make' class='form-control' required>";

        $sql = "SELECT * FROM makes";
        $result = mysqli_query($db, $sql);
        echo "<option value=''>Select Gear Box Make</option>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['make'] . "'>" . $row['make'] . "</option>";
        }
        echo "</select>
            </div>";
        echo "<div class='mb-3'>
            <label class='form-label'>Gear Box Model:</label>
            <select name='gear_box_model' id='gear_box_model' class='form-control' required>
                <option value=''>Select Gear Box Model</option>";

        $sql = "SELECT * FROM norms";
        $result = mysqli_query($db, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['emission_norms'] . "'>" . $row['emission_norms'] . "</option>";
        }
        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Gear Box Type:</label>
            <select name='gear_box_type' id='gear_box_type' class='form-control' required>
                <option value=''>Select Gear Box Type</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Gear Box Condition:</label>
            <select name='gear_box_condition' id='gear_box_condition' class='form-control' required>
                <option value=''>Select Gear Box Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        /*echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='gear_box_progressive_km' id='gear_box_progressive_km' class='form-control' required>
        </div>";*/

        echo "<div class='mb-3'>
            <button type='button' class='btn btn-primary' id='gear_box_submit'>Submit</button>
        </div>";

        echo "</form>"; // Close form

    ?>
        <script>
            $(document).ready(function() {
                // Fetch Gear Box Type based on Gear Box Make selection
                $('#gear_box_make').change(function() {
                    var make = $(this).val();
                    if (make !== "") {
                        $.ajax({
                            url: '../includes/backend_data.php',
                            type: 'POST',
                            data: {
                                action: 'fetchgearboxtypeforinventory',
                                gear_box_make: make
                            },
                            success: function(response) {
                                $('#gear_box_type').html(response);
                            }
                        });
                    } else {
                        $('#gear_box_type').html('<option value="">Select Gear Box Type</option>');
                    }
                });

                // Bind event listener for submit button
                $("#gear_box_submit").click(function() {
                    submitGearBoxForm();
                });
            });

            function submitGearBoxForm() {
                let form = $("#gearBoxForm");
                let isValid = true;
                let missingFields = [];

                // Validate required fields
                form.find(".form-control").each(function() {
                    let inputValue = $(this).val();
                    if (inputValue === null || inputValue.trim() === "") {
                        let label = $(this).prev("label").text().trim(); // Get label text
                        missingFields.push(label);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        html: "<b>Please fill in the following fields:</b><br><br>" + missingFields.join("<br>"),
                    });
                    return;
                }

                // Prepare form data
                let formData = new FormData(form[0]);
                formData.append("action", "submitgearboxinventoryform");

                // AJAX Submission
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            let res = JSON.parse(response); // Parse JSON response
                            console.log("Server Response:", res);

                            if (res.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: res.message,
                                    confirmButtonText: 'OK' // Ensures user clicks OK
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload only after clicking OK
                                    }
                                });
                            } else if (res.status === "error") {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed!',
                                    html: res.messages ? res.messages.join("<br>") : res.message,
                                });
                            }
                        } catch (e) {
                            console.error("JSON Parse Error:", e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Invalid server response format.',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while submitting the form.',
                        });
                    }
                });

            }
        </script>
    <?php


    } elseif ($part_name == 'fip_hpp') {
        echo "<form id='fipHppForm'>"; // Wrap all fields inside a form
        echo "<div class='mb-3'>
        <label class='form-label'>FIP/HPP Card No:</label> 
        <input type='text' name='fip_hpp_card_number' id='fip_hpp_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>FIP/HPP No:</label>
        <input type='text' name='fip_hpp_number' id='fip_hpp_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP's Bus Make:</label>
            <select name='fip_hpp_bus_make' id='fip_hpp_bus_make' class='form-control' required>";

        $sql = "SELECT * FROM makes";
        $result = mysqli_query($db, $sql);
        echo "<option value=''>Select FIP/HPP's Bus Make</option>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['make'] . "'>" . $row['make'] . "</option>";
        }
        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP Make:</label>
            <select name='fip_hpp_make' id='fip_hpp_make' class='form-control' required>
                <option value=''>Select FIP/HPP Make</option>
                <option value='BOSCH'>BOSCH</option>
                <option value='DENSO'>DENSO</option>
                </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP Model:</label>
            <select name='fip_hpp_model' id='fip_hpp_model' class='form-control' required>
                <option value=''>Select FIP/HPP Model</option>";

        $sql = "SELECT * FROM norms";
        $result = mysqli_query($db, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['emission_norms'] . "'>" . $row['emission_norms'] . "</option>";
        }
        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP Type:</label>
            <select name='fip_hpp_type' id='fip_hpp_type' class='form-control' required>
                <option value=''>Select FIP/HPP Type</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP Condition:</label>
            <select name='fip_hpp_condition' id='fip_hpp_condition' class='form-control' required>
                <option value=''>Select FIP/HPP Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        /*echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='fip_hpp_progressive_km' id='fip_hpp_progressive_km' class='form-control' required>
        </div>";*/

        echo "<div class='mb-3'>
            <button type='button' class='btn btn-primary' id='fip_hpp_submit'>Submit</button>
        </div>";

        echo "</form>"; // Close form

    ?>
        <script>
            $(document).ready(function() {
                // Fetch FIP/HPP Type based on FIP/HPP Make selection
                $('#fip_hpp_bus_make').change(function() {
                    var make = $(this).val();
                    if (make !== "") {
                        $.ajax({
                            url: '../includes/backend_data.php',
                            type: 'POST',
                            data: {
                                action: 'fetchfiphtypeforinventory',
                                fiph_make: make
                            },
                            success: function(response) {
                                $('#fip_hpp_type').html(response);
                            }
                        });
                    } else {
                        $('#fip_hpp_type').html('<option value="">Select FIP/HPP Type</option>');
                    }
                });

                // Bind event listener for submit button
                $("#fip_hpp_submit").click(function() {
                    submitFipHppForm();
                });
            });

            function submitFipHppForm() {
                let form = $("#fipHppForm");
                let isValid = true;
                let missingFields = [];

                // Validate required fields
                form.find(".form-control").each(function() {
                    let inputValue = $(this).val();
                    if (inputValue === null || inputValue.trim() === "") {
                        let label = $(this).prev("label").text().trim(); // Get label text
                        missingFields.push(label);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        html: "<b>Please fill in the following fields:</b><br><br>" + missingFields.join("<br>"),
                    });
                    return;
                }

                // Prepare form data
                let formData = new FormData(form[0]);
                formData.append("action", "submitfiphppinventoryform");

                // AJAX Submission
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            let res = JSON.parse(response); // Parse JSON response
                            console.log("Server Response:", res);

                            if (res.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: res.message,
                                    confirmButtonText: 'OK' // Ensures user clicks OK
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload only after clicking OK
                                    }
                                });
                            } else if (res.status === "error") {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed!',
                                    html: res.messages ? res.messages.join("<br>") : res.message,
                                });
                            }
                        } catch (e) {
                            console.error("JSON Parse Error:", e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Invalid server response format.',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while submitting the form.',
                        });
                    }
                });
            }
        </script>
    <?php
    } elseif ($part_name == 'starter') {
        echo "<form id='starterForm'>"; // Wrap all fields inside a form
        echo "<div class='mb-3'>
        <label class='form-label'>Starter Card No:</label> 
        <input type='text' name='starter_card_number' id='starter_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Starter No:</label>
        <input type='text' name='starter_number' id='starter_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Starter Make:</label>
            <select name='starter_make' id='starter_make' class='form-control' required>
            <option value=''>Select Starter Make</option>
            <option value='BOSCH'>BOSCH</option>
            <option value='LUCAS'>LUCAS</option>
            <option value='BECON'>BECON</option>
            <option value='SEG (BOSCH)'>SEG (BOSCH)</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label' >Starter Condition:</label>
            <select name='starter_condition' id='starter_condition' class='form-control' required>
                <option value=''>Select Starter Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        /*echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='starter_progressive_km' id='starter_progressive_km' class='form-control' required>
        </div>";*/

        echo "<div class='mb-3'>
            <button type='button' class='btn btn-primary' id='starter_submit'>Submit</button>
        </div>";

        echo "</form>"; // Close form

    ?>

        <script>
            $(document).ready(function() {
                // Bind event listener for submit button
                $("#starter_submit").click(function() {
                    submitStarterForm();
                });
            });

            function submitStarterForm() {
                let form = $("#starterForm");
                let isValid = true;
                let missingFields = [];

                // Validate required fields
                form.find(".form-control").each(function() {
                    let inputValue = $(this).val();
                    if (inputValue === null || inputValue.trim() === "") {
                        let label = $(this).prev("label").text().trim(); // Get label text
                        missingFields.push(label);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        html: "<b>Please fill in the following fields:</b><br><br>" + missingFields.join("<br>"),
                    });
                    return;
                }

                // Prepare form data
                let formData = new FormData(form[0]);
                formData.append("action", "submitstarterinventoryform");

                // AJAX Submission
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            let res = JSON.parse(response); // Parse JSON response
                            console.log("Server Response:", res);

                            if (res.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: res.message,
                                    confirmButtonText: 'OK' // Ensures user clicks OK
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload only after clicking OK
                                    }
                                });
                            } else if (res.status === "error") {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed!',
                                    html: res.messages ? res.messages.join("<br>") : res.message,
                                });
                            }
                        } catch (e) {
                            console.error("JSON Parse Error:", e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Invalid server response format.',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while submitting the form.',
                        });
                    }
                });

            }
        </script>
    <?php
    } elseif ($part_name == 'alternator') {
        echo "<form id='alternatorForm'>"; // Wrap all fields inside a form
        echo "<div class='mb-3'>
        <label class='form-label'>Alternator Card No:</label>
        <input type='text' name='alternator_card_number' id='alternator_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Alternator No:</label>
        <input type='text' name='alternator_number' id='alternator_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Alternator Make:</label>
            <select name='alternator_make' id='alternator_make' class='form-control' required>
            <option value=''>Select Alternator Make</option>
            <option value='BOSCH'>BOSCH</option>
            <option value='LUCAS'>LUCAS</option>
            <option value='BECON'>BECON</option>
            <option value='SEG (BOSCH)'>SEG (BOSCH)</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Alternator Condition:</label>
            <select name='alternator_condition' id='alternator_condition' class='form-control' required>
                <option value=''>Select Alternator Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        /*echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='alternator_progressive_km' id='alternator_progressive_km' class='form-control' required>
        </div>";*/

        echo "<div class='mb-3'>
            <button type='button' class='btn btn-primary' id='alternator_submit'>Submit</button>
        </div>";

        echo "</form>"; // Close form

    ?>

        <script>
            $(document).ready(function() {
                // Bind event listener for submit button
                $("#alternator_submit").click(function() {
                    submitAlternatorForm();
                });
            });

            function submitAlternatorForm() {
                let form = $("#alternatorForm");
                let isValid = true;
                let missingFields = [];

                // Validate required fields
                form.find(".form-control").each(function() {
                    let inputValue = $(this).val();
                    if (inputValue === null || inputValue.trim() === "") {
                        let label = $(this).prev("label").text().trim(); // Get label text
                        missingFields.push(label);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        html: "<b>Please fill in the following fields:</b><br><br>" + missingFields.join("<br>"),
                    });
                    return;
                }

                // Prepare form data
                let formData = new FormData(form[0]);
                formData.append("action", "submitalternatorinventoryform");

                // AJAX Submission
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            let res = JSON.parse(response); // Parse JSON response
                            console.log("Server Response:", res);

                            if (res.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: res.message,
                                    confirmButtonText: 'OK' // Ensures user clicks OK
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload only after clicking OK
                                    }
                                });
                            } else if (res.status === "error") {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed!',
                                    html: res.messages ? res.messages.join("<br>") : res.message,
                                });
                            }
                        } catch (e) {
                            console.error("JSON Parse Error:", e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Invalid server response format.',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while submitting the form.',
                        });
                    }
                });
            }
        </script>
    <?php
    } elseif ($part_name == 'rear_axle') {
        echo "<form id='rearAxleForm'>"; // Wrap all fields inside a form
        echo "<div class='mb-3'>
        <label class='form-label'>Rear Axle Card No:</label>
        <input type='text' name='rear_axle_card_number' id='rear_axle_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Rear Axle No:</label>
        <input type='text' name='rear_axle_number' id='rear_axle_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Rear Axle Make:</label>
            <select name='rear_axle_make' id='rear_axle_make' class='form-control' required>
            <option value=''>Select Rear Axle Make</option>
            <option value='AIL'>AIL</option>
            <option value='BIL'>BIL</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Rear Axle Condition:</label>
            <select name='rear_axle_condition' id='rear_axle_condition' class='form-control' required>
                <option value=''>Select Rear Axle Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        /*echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='rear_axle_progressive_km' id='rear_axle_progressive_km' class='form-control' required>
        </div>";*/

        echo "<div class='mb-3'>
            <button type='button' class='btn btn-primary' id='rear_axle_submit'>Submit</button>
        </div>";

        echo "</form>"; // Close form

    ?>

        <script>
            $(document).ready(function() {
                // Bind event listener for submit button
                $("#rear_axle_submit").click(function() {
                    submitRearAxleForm();
                });
            });

            function submitRearAxleForm() {
                let form = $("#rearAxleForm");
                let isValid = true;
                let missingFields = [];

                // Validate required fields
                form.find(".form-control").each(function() {
                    let inputValue = $(this).val();
                    if (inputValue === null || inputValue.trim() === "") {
                        let label = $(this).prev("label").text().trim(); // Get label text
                        missingFields.push(label);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        html: "<b>Please fill in the following fields:</b><br><br>" + missingFields.join("<br>"),
                    });
                    return;
                }

                // Prepare form data
                let formData = new FormData(form[0]);
                formData.append("action", "submitrearaxleinventoryform");

                // AJAX Submission
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            let res = JSON.parse(response); // Parse JSON response
                            console.log("Server Response:", res);

                            if (res.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: res.message,
                                    confirmButtonText: 'OK' // Ensures user clicks OK
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload only after clicking OK
                                    }
                                });
                            } else if (res.status === "error") {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed!',
                                    html: res.messages ? res.messages.join("<br>") : res.message,
                                });
                            }
                        } catch (e) {
                            console.error("JSON Parse Error:", e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Invalid server response format.',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while submitting the form.',
                        });
                    }
                });
            }
        </script>
    <?php
    } elseif ($part_name == 'battery') {
        echo "<form id='batteryForm'>"; // Wrap all fields inside a form
        echo "<div class='mb-3'>
        <label class='form-label'>Battery Card No:</label>
        <input type='text' name='battery_card_number' id='battery_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Battery No:</label>
        <input type='text' name='battery_number' id='battery_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Battery Make:</label>
            <select name='battery_make' id='battery_make' class='form-control' required>
            <option value=''>Select Battery Make</option>";
        $sql = "SELECT * FROM battery_makes";
        $result = mysqli_query($db, $sql);

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['make'] . "'>" . $row['make'] . "</option>";
        }
        echo "</select>
            </div>";

        /*echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='battery_progressive_km' id='battery_progressive_km' class='form-control' required>
        </div>";*/

        echo "<div class='mb-3'>
            <button type='button' class='btn btn-primary' id='battery_submit'>Submit</button>
        </div>";

        echo "</form>"; // Close form

    ?>

        <script>
            $(document).ready(function() {
                // Bind event listener for submit button
                $("#battery_submit").click(function() {
                    submitBatteryForm();
                });
            });

            function submitBatteryForm() {
                let form = $("#batteryForm");
                let isValid = true;
                let missingFields = [];

                // Validate required fields
                form.find(".form-control").each(function() {
                    let inputValue = $(this).val();
                    if (inputValue === null || inputValue.trim() === "") {
                        let label = $(this).prev("label").text().trim(); // Get label text
                        missingFields.push(label);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        html: "<b>Please fill in the following fields:</b><br><br>" + missingFields.join("<br>"),
                    });
                    return;
                }

                // Prepare form data
                let formData = new FormData(form[0]);
                formData.append("action", "submitbatteryinventoryform");

                // AJAX Submission
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            let res = JSON.parse(response); // Parse JSON response
                            console.log("Server Response:", res);

                            if (res.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: res.message,
                                    confirmButtonText: 'OK' // Ensures user clicks OK
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload only after clicking OK
                                    }
                                });
                            } else if (res.status === "error") {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed!',
                                    html: res.messages ? res.messages.join("<br>") : res.message,
                                });
                            }
                        } catch (e) {
                            console.error("JSON Parse Error:", e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Invalid server response format.',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while submitting the form.',
                        });
                    }
                });
            }
        </script>
    <?php
    } elseif ($part_name == 'tyre') {
        echo "<form id='tyreForm'>"; // Wrap all fields inside a form
        echo "<div class='mb-3'>
        <label class='form-label'>Tyre Card No:</label>
        <input type='text' name='tyre_card_number' id='tyre_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Tyre No:</label>
        <input type='text' name='tyre_number' id='tyre_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Tyre Make:</label>
            <select name='tyre_make' id='tyre_make' class='form-control' required>
            <option value=''>Select Tyre Make</option>
            <option value='JK'>JK</option>
            <option value='CEAT'>CEAT</option>
            <option value='MRF'>MRF</option>
            <option value='APOLLO'>APOLLO</option>
            <option value='MICHELIN'>MICHELIN</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Tyre Size:</label>
            <select name='tyre_size' id='tyre_size' class='form-control' required>
            <option value=''>Select Tyre Size</option>
            <option value='900X20-16 PR NYLON TYRE'>900X20-16 PR NYLON TYRE</option>
            <option value='900X20-16 PR RADIAL TYRE'>900X20-16 PR RADIAL TYRE</option>
            <option value='10.00x20 PR,RIB'>1000x20 PR,RIB</option>
            <option value='10.00x20 PR,RIB NYLON TYRE'>10.00x20 PR,RIB NYLON TYRE</option>
            <option value='10.00x20 PR,RIB RADIAL TYRE'>10.00x20 PR,RIB RADIAL TYRE</option>
            <option value='295-80R 22.5 16PR'>295-80R 22.5 16PR</option>
            <option value='295-80R 22.5'>295-80R 22.5</option>
            <option value='235-75 R17.5 PR'>235-75 R17.5 PR</option>
            <option value='235-75 R17.5 14PR'>235-75 R17.5</option>
            <option value='235-75 R17.5 14PR'>235-75 R17.5 14PR</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Tyre Brand/Pattern:</label>
            <select name='tyre_brand' id='tyre_brand' class='form-control' required>
            <option value=''>Select Tyre Brand/Pattern</option>
            <option value='FM SUPER'>FM SUPER</option>
            <option value='VIKRANT-TRACK KING JK-JET RIB/JET-R-MILES'>VIKRANT-TRACK KING JK-JET RIB/JET-R-MILES</option>
            <option value='JK-JUH-3/JUC2'>JK-JUH-3/JUC2</option>
            <option value='S1T4'>S1T4</option>
            <option value='WINMILE-R'>WINMILE-R</option>
            <option value='WINMILE-X3R'>WINMILE-X3R</option>
            <option value='WINMILE-AW'>WINMILE-AW</option>
            <option value='JK-JUH-3+'>JK-JUH-3+</option>
            <option value='S1R4'>S1R4</option>
            <option value='S1R4 PLUS'>S1R4 PLUS</option>
            <option value='JK-KUM'>JK-KUM</option>
            <option value='STEEL MUSCLE S1 R4 PLUS'>STEEL MUSCLE S1 R4 PLUS</option>
            <option value='JK-JUH-5'>JK-JUH-5</option>
            <option value='ENDURACE RA'>ENDURACE RA</option>
            <option value='X-MULTI'>X-MULTI</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Tyre Condition:</label>
            <select name='tyre_condition' id='tyre_condition' class='form-control' required>
                <option value=''>Select Tyre Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        /*echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='tyre_progressive_km' id='tyre_progressive_km' class='form-control' required>
        </div>";*/

        echo "<div class='mb-3'>
            <button type='button' class='btn btn-primary' id='tyre_submit'>Submit</button>
        </div>";

        echo "</form>"; // Close form

    ?>
        <script>
            $(document).ready(function() {
                // Bind event listener for submit button
                $("#tyre_submit").click(function() {
                    submitTyreForm();
                });
            });

            function submitTyreForm() {
                let form = $("#tyreForm");
                let isValid = true;
                let missingFields = [];

                // Validate required fields
                form.find(".form-control").each(function() {
                    let inputValue = $(this).val();
                    if (inputValue === null || inputValue.trim() === "") {
                        let label = $(this).prev("label").text().trim(); // Get label text
                        missingFields.push(label);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        html: "<b>Please fill in the following fields:</b><br><br>" + missingFields.join("<br>"),
                    });
                    return;
                }

                // Prepare form data
                let formData = new FormData(form[0]);
                formData.append("action", "submittyreinventoryform");

                // AJAX Submission
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            let res = JSON.parse(response); // Parse JSON response
                            console.log("Server Response:", res);

                            if (res.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: res.message,
                                    confirmButtonText: 'OK' // Ensures user clicks OK
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload only after clicking OK
                                    }
                                });
                            } else if (res.status === "error") {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed!',
                                    html: res.messages ? res.messages.join("<br>") : res.message,
                                });
                            }
                        } catch (e) {
                            console.error("JSON Parse Error:", e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Invalid server response format.',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while submitting the form.',
                        });
                    }
                });
            }
        </script>
    <?php

    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'fetchenginetypeforinventory') {
    $make = $_POST['engine_make'];

    // Secure query to fetch engine types based on the selected make
    $sql = "SELECT id, type FROM engine_types WHERE make = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $make);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<option value=''>Select Engine Type</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . $row['type'] . "</option>";
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'fetchgearboxtypeforinventory') {
    $make = $_POST['gear_box_make'];

    // Secure query to fetch gearbox types based on the selected make
    $sql = "SELECT id, type FROM gearbox_types WHERE make = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $make);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<option value=''>Select Gear Box Type</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . $row['type'] . "</option>";
    }

    $stmt->close();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'fetchfiphtypeforinventory') {
    $make = $_POST['fiph_make'];

    // Secure query to fetch FIP/HPP types based on the selected make
    $sql = "SELECT id, type, model FROM fip_types WHERE make = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $make);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<option value=''>Select FIP/HPP Type</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . $row['type'] . " - " . $row['model'] . "</option>";
    }
    $stmt->close();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submitengineinventoryform') {
    // Sanitize and validate input data
    $engine_card_number = trim($_POST['engine_card_number']);
    $engine_number = trim($_POST['engine_number']);
    $engine_make = trim($_POST['engine_make']);
    $engine_model = trim($_POST['engine_model']);
    $engine_type_id = isset($_POST['engine_type']) ? (int) $_POST['engine_type'] : 0;
    $engine_condition = trim($_POST['engine_condition']);
    $progressive_km = null;
    $username = $_SESSION['USERNAME'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    // Validation checks
    $errors = [];

    if (empty($engine_card_number)) {
        $errors[] = "Engine Card Number is required.";
    }
    if (empty($engine_number)) {
        $errors[] = "Engine Number is required.";
    }
    if (empty($engine_make)) {
        $errors[] = "Engine Make is required.";
    }
    if (empty($engine_model)) {
        $errors[] = "Engine Model is required.";
    }
    if ($engine_type_id <= 0) {
        $errors[] = "Invalid Engine Type selected.";
    }
    if (!in_array($engine_condition, ['New', 'RC', 'RECON'])) {
        $errors[] = "Invalid Engine Condition.";
    }
    /*if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }*/

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT engine_card_number, engine_number FROM engine_master WHERE deleted!= '1' and (engine_card_number = ? OR engine_number = ?)";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ss", $engine_card_number, $engine_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $existingRecords = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['engine_card_number'] === $engine_card_number) {
            $existingRecords[] = "Engine Card Number: " . $engine_card_number;
        }
        if ($row['engine_number'] === $engine_number) {
            $existingRecords[] = "Engine Number: " . $engine_number;
        }
    }

    if (!empty($existingRecords)) {
        echo json_encode(["status" => "error", "message" => implode("<br>", $existingRecords) . " already exists!"]);
        exit;
    }

    mysqli_stmt_close($stmt);

    // Insert into database after validation
    $query = "INSERT INTO engine_master (engine_card_number, engine_number, engine_make, engine_model, engine_type_id, engine_condition, progressive_km, created_by, depot_id, division_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssisisii", $engine_card_number, $engine_number, $engine_make, $engine_model, $engine_type_id, $engine_condition, $progressive_km, $username, $depot_id, $division_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "Engine details submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($db)]);
    }

    mysqli_stmt_close($stmt);
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submitgearboxinventoryform') {
    // Sanitize and validate input data
    $gear_box_card_number = trim($_POST['gear_box_card_number']);
    $gear_box_number = trim($_POST['gear_box_number']);
    $gear_box_make = trim($_POST['gear_box_make']);
    $gear_box_model = trim($_POST['gear_box_model']);
    $gear_box_type_id = isset($_POST['gear_box_type']) ? (int) $_POST['gear_box_type'] : 0;
    $gear_box_condition = trim($_POST['gear_box_condition']);
    $progressive_km = null;
    $username = $_SESSION['USERNAME'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    // Validation checks
    $errors = [];

    if (empty($gear_box_card_number)) {
        $errors[] = "Gear Box Card Number is required.";
    }
    if (empty($gear_box_number)) {
        $errors[] = "Gear Box Number is required.";
    }
    if (empty($gear_box_make)) {
        $errors[] = "Gear Box Make is required.";
    }
    if (empty($gear_box_model)) {
        $errors[] = "Gear Box Model is required.";
    }
    if ($gear_box_type_id <= 0) {
        $errors[] = "Invalid Gear Box Type selected.";
    }
    if (!in_array($gear_box_condition, ['New', 'RC'])) {
        $errors[] = "Invalid Gear Box Condition.";
    }
    /*if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }*/

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT gear_box_card_number, gear_box_number FROM gearbox_master WHERE deleted!= '1' AND (gear_box_card_number = ? OR gear_box_number = ?)";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ss", $gear_box_card_number, $gear_box_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $existingRecords = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['gear_box_card_number'] === $gear_box_card_number) {
            $existingRecords[] = "Gear Box Card Number: " . $gear_box_card_number;
        }
        if ($row['gear_box_number'] === $gear_box_number) {
            $existingRecords[] = "Gear Box Number: " . $gear_box_number;
        }
    }

    if (!empty($existingRecords)) {
        echo json_encode(["status" => "error", "message" => implode("<br>", $existingRecords) . " already exists!"]);
        exit;
    }

    mysqli_stmt_close($stmt);

    // Insert into database after validation
    $query = "INSERT INTO gearbox_master (gear_box_card_number, gear_box_number, gear_box_make, gear_box_model, gear_box_type_id, gear_box_condition, progressive_km, created_by, depot_id, division_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssisisii", $gear_box_card_number, $gear_box_number, $gear_box_make, $gear_box_model, $gear_box_type_id, $gear_box_condition, $progressive_km, $username, $depot_id, $division_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "Gear Box details submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($db)]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submitfiphppinventoryform') {
    // Sanitize and validate input data
    $fip_hpp_card_number = trim($_POST['fip_hpp_card_number']);
    $fip_hpp_number = trim($_POST['fip_hpp_number']);
    $fip_hpp_bus_make = trim($_POST['fip_hpp_bus_make']);
    $fip_hpp_make = trim($_POST['fip_hpp_make']);
    $fip_hpp_model = trim($_POST['fip_hpp_model']);
    $fip_hpp_type_id = isset($_POST['fip_hpp_type']) ? (int) $_POST['fip_hpp_type'] : 0;
    $fip_hpp_condition = trim($_POST['fip_hpp_condition']);
    //$progressive_km = isset($_POST['fip_hpp_progressive_km']) ? (int) $_POST['fip_hpp_progressive_km'] : 0;
    $progressive_km = null;
    $username = $_SESSION['USERNAME'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    // Validation checks
    $errors = [];

    if (empty($fip_hpp_card_number)) {
        $errors[] = "FIP/HPP Card Number is required.";
    }
    if (empty($fip_hpp_number)) {
        $errors[] = "FIP/HPP Number is required.";
    }
    if (empty($fip_hpp_bus_make)) {
        $errors[] = "FIP/HPP Bus Make is required.";
    }
    if (empty($fip_hpp_make)) {
        $errors[] = "FIP/HPP Make is required.";
    }
    if (empty($fip_hpp_model)) {
        $errors[] = "FIP/HPP Model is required.";
    }
    if ($fip_hpp_type_id <= 0) {
        $errors[] = "Invalid FIP/HPP Type selected.";
    }
    if (!in_array($fip_hpp_condition, ['New', 'RC'])) {
        $errors[] = "Invalid FIP/HPP Condition.";
    }
    /*if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }*/

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT fip_hpp_card_number, fip_hpp_number FROM fip_hpp_master WHERE deleted!= '1' AND (fip_hpp_card_number = ? OR fip_hpp_number = ?)";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ss", $fip_hpp_card_number, $fip_hpp_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $existingRecords = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['fip_hpp_card_number'] === $fip_hpp_card_number) {
            $existingRecords[] = "FIP/HPP Card Number: " . $fip_hpp_card_number;
        }
        if ($row['fip_hpp_number'] === $fip_hpp_number) {
            $existingRecords[] = "FIP/HPP Number: " . $fip_hpp_number;
        }
    }

    if (!empty($existingRecords)) {
        echo json_encode(["status" => "error", "message" => implode("<br>", $existingRecords) . " already exists!"]);
        exit;
    }

    mysqli_stmt_close($stmt);

    // Insert into database after validation
    $query = "INSERT INTO fip_hpp_master (fip_hpp_card_number, fip_hpp_number, fip_hpp_bus_make, fip_hpp_make, fip_hpp_model, fip_hpp_type_id, fip_hpp_condition, progressive_km, created_by, depot_id, division_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sssssisisii", $fip_hpp_card_number, $fip_hpp_number, $fip_hpp_bus_make, $fip_hpp_make, $fip_hpp_model, $fip_hpp_type_id, $fip_hpp_condition, $progressive_km, $username, $depot_id, $division_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "FIP/HPP details submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($db)]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submitstarterinventoryform') {
    // Sanitize and validate input data
    $starter_card_number = trim($_POST['starter_card_number']);
    $starter_number = trim($_POST['starter_number']);
    $starter_make = trim($_POST['starter_make']);
    $starter_condition = trim($_POST['starter_condition']);
    //$progressive_km = isset($_POST['starter_progressive_km']) ? (int) $_POST['starter_progressive_km'] : 0;
    $progressive_km = null;
    $username = $_SESSION['USERNAME'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    // Validation checks
    $errors = [];

    if (empty($starter_card_number)) {
        $errors[] = "Starter Card Number is required.";
    }
    if (empty($starter_number)) {
        $errors[] = "Starter Number is required.";
    }
    if (empty($starter_make)) {
        $errors[] = "Starter Make is required.";
    }
    if (!in_array($starter_condition, ['New', 'RC'])) {
        $errors[] = "Invalid Starter Condition.";
    }
    /*if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }*/

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }


    $query = "SELECT starter_card_number, starter_number FROM starter_master WHERE deleted!= '1' AND (starter_card_number = ? OR starter_number = ?)";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ss", $starter_card_number, $starter_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $existingRecords = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['starter_card_number'] === $starter_card_number) {
            $existingRecords[] = "Starter Card Number: " . $starter_card_number;
        }
        if ($row['starter_number'] === $starter_number) {
            $existingRecords[] = "Starter Number: " . $starter_number;
        }
    }

    if (!empty($existingRecords)) {
        echo json_encode(["status" => "error", "message" => implode("<br>", $existingRecords) . " already exists!"]);
        exit;
    }

    mysqli_stmt_close($stmt);

    // Insert into database after validation
    $query = "INSERT INTO starter_master (starter_card_number, starter_number, starter_make, starter_condition, progressive_km, created_by, depot_id, division_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssisii", $starter_card_number, $starter_number, $starter_make, $starter_condition, $progressive_km, $username, $depot_id, $division_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "Starter details submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($db)]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submitalternatorinventoryform') {
    // Sanitize and validate input data
    $alternator_card_number = trim($_POST['alternator_card_number']);
    $alternator_number = trim($_POST['alternator_number']);
    $alternator_make = trim($_POST['alternator_make']);
    $alternator_condition = trim($_POST['alternator_condition']);
    //$progressive_km = isset($_POST['alternator_progressive_km']) ? (int) $_POST['alternator_progressive_km'] : 0;
    $progressive_km = null;
    $username = $_SESSION['USERNAME'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    // Validation checks
    $errors = [];

    if (empty($alternator_card_number)) {
        $errors[] = "Alternator Card Number is required.";
    }
    if (empty($alternator_number)) {
        $errors[] = "Alternator Number is required.";
    }
    if (empty($alternator_make)) {
        $errors[] = "Alternator Make is required.";
    }
    if (!in_array($alternator_condition, ['New', 'RC'])) {
        $errors[] = "Invalid Alternator Condition.";
    }
    /*if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }*/

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT alternator_card_number, alternator_number FROM alternator_master WHERE deleted!= '1' AND (alternator_card_number = ? OR alternator_number = ?)";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ss", $alternator_card_number, $alternator_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $existingRecords = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['alternator_card_number'] === $alternator_card_number) {
            $existingRecords[] = "Alternator Card Number: " . $alternator_card_number;
        }
        if ($row['alternator_number'] === $alternator_number) {
            $existingRecords[] = "Alternator Number: " . $alternator_number;
        }
    }

    if (!empty($existingRecords)) {
        echo json_encode(["status" => "error", "message" => implode("<br>", $existingRecords) . " already exists!"]);
        exit;
    }

    mysqli_stmt_close($stmt);

    // Insert into database after validation
    $query = "INSERT INTO alternator_master (alternator_card_number, alternator_number, alternator_make, alternator_condition, progressive_km, created_by, depot_id, division_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssisii", $alternator_card_number, $alternator_number, $alternator_make, $alternator_condition, $progressive_km, $username, $depot_id, $division_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "Alternator details submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($db)]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submitrearaxleinventoryform') {
    // Sanitize and validate input data
    $rear_axle_card_number = trim($_POST['rear_axle_card_number']);
    $rear_axle_number = trim($_POST['rear_axle_number']);
    $rear_axle_make = trim($_POST['rear_axle_make']);
    $rear_axle_condition = trim($_POST['rear_axle_condition']);
    //$progressive_km = isset($_POST['rear_axle_progressive_km']) ? (int) $_POST['rear_axle_progressive_km'] : 0;
    $progressive_km = null;
    $username = $_SESSION['USERNAME'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    // Validation checks
    $errors = [];

    if (empty($rear_axle_card_number)) {
        $errors[] = "Rear Axle Card Number is required.";
    }
    if (empty($rear_axle_number)) {
        $errors[] = "Rear Axle Number is required.";
    }
    if (empty($rear_axle_make)) {
        $errors[] = "Rear Axle Make is required.";
    }
    if (!in_array($rear_axle_condition, ['New', 'RC'])) {
        $errors[] = "Invalid Rear Axle Condition.";
    }
    /*if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }*/

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT rear_axle_card_number, rear_axle_number FROM rear_axle_master WHERE deleted!= '1' AND (rear_axle_card_number = ? OR rear_axle_number = ?)";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ss", $rear_axle_card_number, $rear_axle_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $existingRecords = [];

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['rear_axle_card_number'] === $rear_axle_card_number) {
            $existingRecords[] = "Rear Axle Card Number: " . $rear_axle_card_number;
        }
        if ($row['rear_axle_number'] === $rear_axle_number) {
            $existingRecords[] = "Rear Axle Number: " . $rear_axle_number;
        }
    }

    if (!empty($existingRecords)) {
        echo json_encode(["status" => "error", "message" => implode("<br>", $existingRecords) . " already exists!"]);
        exit;
    }

    mysqli_stmt_close($stmt);

    // Insert into database after validation
    $query = "INSERT INTO rear_axle_master (rear_axle_card_number, rear_axle_number, rear_axle_make, rear_axle_condition, progressive_km, created_by, depot_id, division_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssisii", $rear_axle_card_number, $rear_axle_number, $rear_axle_make, $rear_axle_condition, $progressive_km, $username, $depot_id, $division_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "Rear Axle details submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($db)]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submitbatteryinventoryform') {
    // Sanitize and validate input data
    $battery_card_number = trim($_POST['battery_card_number']);
    $battery_number = trim($_POST['battery_number']);
    $battery_make = trim($_POST['battery_make']);
    //$progressive_km = isset($_POST['battery_progressive_km']) ? (int) $_POST['battery_progressive_km'] : 0;
    $progressive_km = null;
    $username = $_SESSION['USERNAME'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    // Validation checks
    $errors = [];

    if (empty($battery_card_number)) {
        $errors[] = "Battery Card Number is required.";
    }
    if (empty($battery_number)) {
        $errors[] = "Battery Number is required.";
    }
    if (empty($battery_make)) {
        $errors[] = "Battery Make is required.";
    }
    /*if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }*/

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT battery_card_number, battery_number FROM battery_master WHERE deleted!= '1' AND (battery_card_number = ? OR battery_number = ?)";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ss", $battery_card_number, $battery_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $existingRecords = [];

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['battery_card_number'] === $battery_card_number) {
            $existingRecords[] = "Battery Card Number: " . $battery_card_number;
        }
        if ($row['battery_number'] === $battery_number) {
            $existingRecords[] = "Battery Number: " . $battery_number;
        }
    }

    if (!empty($existingRecords)) {
        echo json_encode(["status" => "error", "message" => implode("<br>", $existingRecords) . " already exists!"]);
        exit;
    }

    mysqli_stmt_close($stmt);

    // Insert into database after validation
    $query = "INSERT INTO battery_master (battery_card_number, battery_number, battery_make, progressive_km, created_by, depot_id, division_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sssisii", $battery_card_number, $battery_number, $battery_make, $progressive_km, $username, $depot_id, $division_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "Battery details submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($db)]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submittyreinventoryform') {
    // Sanitize and validate input data
    $tyre_card_number = trim($_POST['tyre_card_number']);
    $tyre_number = trim($_POST['tyre_number']);
    $tyre_make = trim($_POST['tyre_make']);
    $tyre_size = trim($_POST['tyre_size']);
    $tyre_brand = trim($_POST['tyre_brand']);
    $tyre_condition = trim($_POST['tyre_condition']);
    //$tyre_progressive_km = isset($_POST['tyre_progressive_km']) ? (int) $_POST['tyre_progressive_km'] : 0;
    $tyre_progressive_km = null;
    $username = $_SESSION['USERNAME'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    // Validation checks
    $errors = [];

    if (empty($tyre_card_number)) {
        $errors[] = "Tyre Card Number is required.";
    }
    if (empty($tyre_number)) {
        $errors[] = "Tyre Number is required.";
    }
    if (empty($tyre_make)) {
        $errors[] = "Tyre Make is required.";
    }
    if (empty($tyre_size)) {
        $errors[] = "Tyre Size is required.";
    }
    if (empty($tyre_brand)) {
        $errors[] = "Tyre Brand/Pattern is required.";
    }
    if (!in_array($tyre_condition, ['New', 'RC'])) {
        $errors[] = "Invalid Tyre Condition.";
    }
    /*if ($tyre_progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }*/

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT tyre_card_number, tyre_number FROM tyre_master WHERE deleted!= '1' AND (tyre_card_number = ? OR tyre_number = ?)";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ss", $tyre_card_number, $tyre_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $existingRecords = [];

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['tyre_card_number'] === $tyre_card_number) {
            $existingRecords[] = "Tyre Card Number: " . $tyre_card_number;
        }
        if ($row['tyre_number'] === $tyre_number) {
            $existingRecords[] = "Tyre Number: " . $tyre_number;
        }
    }

    if (!empty($existingRecords)) {
        echo json_encode(["status" => "error", "message" => implode("<br>", $existingRecords) . " already exists!"]);
        exit;
    }

    mysqli_stmt_close($stmt);

    // Insert into database after validation
    $query = "INSERT INTO tyre_master (tyre_card_number, tyre_number, tyre_make, tyre_size, tyre_brand, tyre_condition, progressive_km, created_by, depot_id, division_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $query);

    mysqli_stmt_bind_param($stmt, "ssssssisii", $tyre_card_number, $tyre_number, $tyre_make, $tyre_size, $tyre_brand, $tyre_condition, $tyre_progressive_km, $username, $depot_id, $division_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "Tyre details submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($db)]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'fetch_inventory_basic_data') {
    $category_type = $_POST['category'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    ?>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "paging": true, // Enable pagination
                "ordering": true, // Enable sorting
                "searching": true, // Enable search
                "info": true // Show table info
            });
        });
    </script>
    <?php
    if ($category_type == 'engine') {
        $query = "SELECT em.id, em.engine_card_number, em.engine_number, em.engine_make, em.engine_model, et.type as engine_type, em.engine_condition, em.progressive_km
        FROM engine_master em
        JOIN engine_types et ON em.engine_type_id = et.id
        WHERE em.depot_id = ? AND em.division_id = ? AND em.deleted != '1' and em.scrap_status != '1' and em.allotted != '1'";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        echo "<table class='table table-bordered table-striped table-hover' id='dataTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Engine Card Number</th>";
        echo "<th>Engine Number</th>";
        echo "<th>Engine Make</th>";
        echo "<th>Engine Model</th>";
        echo "<th>Engine Type</th>";
        echo "<th>Engine Condition</th>";
        echo "<th>Progressive KM</th>";
        echo "<th>Action</th>"; // New column for action buttons
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['engine_card_number'] . "</td>";
            echo "<td>" . $row['engine_number'] . "</td>";
            echo "<td>" . $row['engine_make'] . "</td>";
            echo "<td>" . $row['engine_model'] . "</td>";
            echo "<td>" . $row['engine_type'] . "</td>";
            echo "<td>" . $row['engine_condition'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "<td>
          <button class='btn btn-warning btn-sm engine-edit-btn' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></button>
          <button class='btn btn-danger btn-sm engine-delete-btn' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i></button>
        </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";

        // Edit Modal
        echo "<div class='modal fade' id='engineeditModel' tabindex='-1' role='dialog' aria-labelledby='engineeditModelLabel' aria-hidden='true'>
      <div class='modal-dialog' role='document'>
          <div class='modal-content'>
              <div class='modal-header'>
                  <h5 class='modal-title' id='engineeditModelLabel'>Edit Engine Details</h5>
                  <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                      <span aria-hidden='true'>&times;</span>
                  </button>
              </div>
              <div class='modal-body'>
                  <form id='engineeditForm'>
                      <input type='hidden' id='edit_id' name='id'>";
        echo "<div class='mb-3'>
        <label class='form-label'>Engine Card No:</label> 
        <input type='text' name='edit_engine_card_number' id='edit_engine_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Engine No:</label>
        <input type='text' name='edit_engine_number' id='edit_engine_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Engine Make:</label>
            <select name='edit_engine_make' id='edit_engine_make' class='form-control' required>";

        $sql = "SELECT * FROM makes";
        $result = mysqli_query($db, $sql);
        echo "<option value=''>Select Engine Make</option>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['make'] . "'>" . $row['make'] . "</option>";
        }

        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Engine Model:</label>
            <select name='edit_engine_model' id='edit_engine_model' class='form-control' required>
                <option value=''>Select Engine Model</option>";

        $sql = "SELECT * FROM norms";
        $result = mysqli_query($db, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['emission_norms'] . "'>" . $row['emission_norms'] . "</option>";
        }

        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Engine Type:</label>
            <select name='edit_engine_type' id='edit_engine_type' class='form-control' required>
                <option value=''>Select Engine Type</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Engine Condition:</label>
            <select name='edit_engine_condition' id='edit_engine_condition' class='form-control' required>
                <option value=''>Select Engine Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
                <option value='RECON'>RECON</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='edit_engine_progressive_km' id='edit_engine_progressive_km' class='form-control' required>
        </div>";

        echo " <button type='submit' class='btn btn-primary'>Update</button>
                  </form>
              </div>
          </div>
      </div>
    </div>";
    ?>
        <script>
            $(document).ready(function() {
                // Handle Delete
                $(document).on("click", ".engine-delete-btn", function() {
                    let id = $(this).data("id");
                    if (confirm("Are you sure you want to delete this engine?")) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "delete_engine_from_list",
                                id: id
                            },
                            success: function(response) {
                                alert(response);
                                location.reload(); // Refresh the page
                            }
                        });
                    }
                });
                $(document).on("change", "#edit_engine_make", function() {
                    let engineMake = $(this).val();

                    if (engineMake !== "") {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "fetchenginetypeforinventory",
                                engine_make: engineMake
                            },
                            success: function(response) {
                                $("#edit_engine_type").html(response);
                            }
                        });
                    } else {
                        // Reset engine type dropdown
                        $("#edit_engine_type").html("<option value=''>Select Engine Type</option>");
                    }
                });

                $(document).on("click", ".engine-edit-btn", function() {
                    let id = $(this).data("id");
                    $.ajax({
                        url: "../includes/backend_data.php",
                        type: "POST",
                        data: {
                            action: "get_engine_details_for_edit",
                            id: id
                        },
                        dataType: "json",
                        success: function(data) {
                            $("#edit_id").val(data.id);
                            $("#edit_engine_card_number").val(data.engine_card_number);
                            $("#edit_engine_number").val(data.engine_number);
                            $("#edit_engine_make").val(data.engine_make);
                            $("#edit_engine_model").val(data.engine_model);
                            $("#edit_engine_condition").val(data.engine_condition);
                            $("#edit_engine_progressive_km").val(data.progressive_km);

                            // Fetch engine types based on the loaded engine make
                            $.ajax({
                                url: "../includes/backend_data.php",
                                type: "POST",
                                data: {
                                    action: "fetchenginetypeforinventory",
                                    engine_make: data.engine_make
                                },
                                success: function(response) {
                                    $("#edit_engine_type").html(response);
                                    $("#edit_engine_type").val(data.engine_type_id); // Set the correct engine type
                                }
                            });

                            $("#engineeditModel").modal("show");
                        }
                    });
                });


                // Handle Update
                $("#engineeditForm").submit(function(e) {
                    e.preventDefault(); // Prevent default form submission

                    let id = $("#edit_id").val();
                    let engine_card_number = $("#edit_engine_card_number").val().trim();
                    let engine_number = $("#edit_engine_number").val().trim();
                    let engine_make = $("#edit_engine_make").val().trim();
                    let engine_model = $("#edit_engine_model").val().trim();
                    let engine_type = $("#edit_engine_type").val().trim();
                    let engine_condition = $("#edit_engine_condition").val();
                    let progressive_km = $("#edit_engine_progressive_km").val().trim();

                    // Validation Checks
                    if (engine_card_number === "" || engine_number === "" || engine_make === "" || engine_type === "" || engine_model === "" || engine_condition === "") {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "All fields are Required.",
                        });
                        return;
                    }

                    if (progressive_km !== "") {
                        if (isNaN(progressive_km)) {
                            Swal.fire({
                                icon: "warning",
                                title: "Validation Error",
                                text: "Progressive KM must be a number.",
                            });
                            return;
                        }
                        if (parseFloat(progressive_km) <= 0) {
                            Swal.fire({
                                icon: "warning",
                                title: "Validation Error",
                                text: "Progressive KM must be greater than zero.",
                            });
                            return;
                        }
                    }

                    // Confirm before update
                    Swal.fire({
                        title: "Are you sure?",
                        text: "You want to update this engine record.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, Update it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "../includes/backend_data.php",
                                type: "POST",
                                data: {
                                    action: "update_engine_details",
                                    id: id,
                                    engine_card_number: engine_card_number,
                                    engine_number: engine_number,
                                    engine_make: engine_make,
                                    engine_model: engine_model,
                                    engine_type: engine_type,
                                    engine_condition: engine_condition,
                                    progressive_km: progressive_km
                                },
                                success: function(response) {
                                    Swal.fire({
                                        icon: "success",
                                        title: "Success",
                                        text: response,
                                    }).then(() => {
                                        $("#engineeditModel").modal("hide");
                                        location.reload(); // Reload table data
                                    });
                                },
                                error: function() {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Error",
                                        text: "Something went wrong! Please try again.",
                                    });
                                }
                            });
                        }
                    });
                });
            });
        </script>
    <?php
    } else if ($category_type == 'gearbox') {
        //return the table of gearbox data
        //join the gearbox_master and gearbox_types table to get the gearbox type
        $query = "SELECT gm.id, gm.gear_box_card_number, gm.gear_box_number, gm.gear_box_make, gm.gear_box_model, gt.type as gear_box_type, gm.gear_box_condition, gm.progressive_km
                  FROM gearbox_master gm
                  JOIN gearbox_types gt ON gm.gear_box_type_id = gt.id
                  WHERE gm.depot_id = ? AND gm.division_id = ?  AND gm.deleted != '1' and gm.scrap_status != '1' and gm.allotted != '1'";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        //create a table and assign the data to the table and return the table
        echo "<table class='table table-bordered table-striped table-hover'  id='dataTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Gear Box Card Number</th>";
        echo "<th>Gear Box Number</th>";
        echo "<th>Gear Box Make</th>";
        echo "<th>Gear Box Model</th>";
        echo "<th>Gear Box Type</th>";
        echo "<th>Gear Box Condition</th>";
        echo "<th>Progressive KM</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['gear_box_card_number'] . "</td>";
            echo "<td>" . $row['gear_box_number'] . "</td>";
            echo "<td>" . $row['gear_box_make'] . "</td>";
            echo "<td>" . $row['gear_box_model'] . "</td>";
            echo "<td>" . $row['gear_box_type'] . "</td>";
            echo "<td>" . $row['gear_box_condition'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "<td>
          <button class='btn btn-warning btn-sm gear-box-edit-btn' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></button>
          <button class='btn btn-danger btn-sm gear-box-delete-btn' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i></button>
        </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";


        // Edit Modal
        echo "<div class='modal fade' id='gear_boxeditModel' tabindex='-1' role='dialog' aria-labelledby='gear_boxeditModelLabel' aria-hidden='true'>
      <div class='modal-dialog' role='document'>
          <div class='modal-content'>
              <div class='modal-header'>
                  <h5 class='modal-title' id='gear_boxeditModelLabel'>Edit Gear Box Details</h5>
                  <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                      <span aria-hidden='true'>&times;</span>
                  </button>
              </div>
              <div class='modal-body'>
                  <form id='gear_boxeditForm'>
                      <input type='hidden' id='edit_id' name='id'>";
        echo "<div class='mb-3'>
                      <label class='form-label'>Gear Box Card No:</label> 
                      <input type='text' name='edit_gear_box_card_number' id='edit_gear_box_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
                    </div>";

        echo "<div class='mb-3'>
                      <label class='form-label'>Gear Box No:</label>
                      <input type='text' name='edit_gear_box_number' id='edit_gear_box_number' class='form-control' required oninput='validateAndFormatInput(this)'>
                      </div>";

        echo "<div class='mb-3'>
                          <label class='form-label'>Gear Box Make:</label>
                          <select name='edit_gear_box_make' id='edit_gear_box_make' class='form-control' required>";

        $sql = "SELECT * FROM makes";
        $result = mysqli_query($db, $sql);
        echo "<option value=''>Select Gear Box Make</option>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['make'] . "'>" . $row['make'] . "</option>";
        }
        echo "</select>
                          </div>";
        echo "<div class='mb-3'>
                          <label class='form-label'>Gear Box Model:</label>
                          <select name='edit_gear_box_model' id='edit_gear_box_model' class='form-control' required>
                              <option value=''>Select Gear Box Model</option>";

        $sql = "SELECT * FROM norms";
        $result = mysqli_query($db, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['emission_norms'] . "'>" . $row['emission_norms'] . "</option>";
        }
        echo "</select>
                          </div>";

        echo "<div class='mb-3'>
                          <label class='form-label'>Gear Box Type:</label>
                          <select name='edit_gear_box_type' id='edit_gear_box_type' class='form-control' required>
                              <option value=''>Select Gear Box Type</option>
                          </select>
                      </div>";

        echo "<div class='mb-3'>
                          <label class='form-label'>Gear Box Condition:</label>
                          <select name='edit_gear_box_condition' id='edit_gear_box_condition' class='form-control' required>
                              <option value=''>Select Gear Box Condition</option>
                              <option value='New'>New</option>
                              <option value='RC'>RC</option>
                          </select>
                      </div>";

        echo "<div class='mb-3'>
                          <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
                          <input type='number' name='edit_gear_box_progressive_km' id='edit_gear_box_progressive_km' class='form-control' required>
                      </div>";

        echo " <button type='submit' class='btn btn-primary'>Update</button>
                  </form>
              </div>
          </div>
      </div>
    </div>";
    ?>
        <script>
            $(document).ready(function() {
                // Handle Delete
                $(document).on("click", ".gear-box-delete-btn", function() {
                    let id = $(this).data("id");
                    if (confirm("Are you sure you want to delete this Gear Box?")) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "delete_gear_box_from_list",
                                id: id
                            },
                            success: function(response) {
                                alert(response);
                                location.reload(); // Refresh the page
                            }
                        });
                    }
                });
                // Handle change of Gear Box Make to fetch and update Gear Box Type
                $(document).on("change", "#edit_gear_box_make", function() {
                    let make = $(this).val();

                    if (make !== "") {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "fetchgearboxtypeforinventory",
                                gear_box_make: make
                            },
                            success: function(response) {
                                $("#edit_gear_box_type").html(response);
                            }
                        });
                    } else {
                        $("#edit_gear_box_type").html("<option value=''>Select Gear Box Type</option>");
                    }
                });


                $(document).on("click", ".gear-box-edit-btn", function() {
                    let id = $(this).data("id");
                    $.ajax({
                        url: "../includes/backend_data.php",
                        type: "POST",
                        data: {
                            action: "get_gear_box_details_for_edit",
                            id: id
                        },
                        dataType: "json",
                        success: function(data) {
                            $("#edit_id").val(data.id);
                            $("#edit_gear_box_card_number").val(data.gear_box_card_number);
                            $("#edit_gear_box_number").val(data.gear_box_number);
                            $("#edit_gear_box_make").val(data.gear_box_make);
                            $("#edit_gear_box_model").val(data.gear_box_model);
                            //$("#edit_gear_box_type").val(data.gear_box_type_id);
                            $("#edit_gear_box_condition").val(data.gear_box_condition);
                            $("#edit_gear_box_progressive_km").val(data.progressive_km);

                            // Fetch gear_box types based on the loaded gear_box make
                            $.ajax({
                                url: "../includes/backend_data.php",
                                type: "POST",
                                data: {
                                    action: "fetchgearboxtypeforinventory",
                                    gear_box_make: data.gear_box_make
                                },
                                success: function(response) {
                                    $("#edit_gear_box_type").html(response);
                                    $("#edit_gear_box_type").val(data.gear_box_type_id); // Set the correct gear_box type
                                }
                            });

                            $("#gear_boxeditModel").modal("show");
                        }
                    });
                });


                // Handle Update
                $("#gear_boxeditForm").submit(function(e) {
                    e.preventDefault(); // Prevent default form submission

                    let id = $("#edit_id").val();
                    let gear_box_card_number = $("#edit_gear_box_card_number").val().trim();
                    let gear_box_number = $("#edit_gear_box_number").val().trim();
                    let gear_box_make = $("#edit_gear_box_make").val().trim();
                    let gear_box_model = $("#edit_gear_box_model").val().trim();
                    let gear_box_type = $("#edit_gear_box_type").val().trim();
                    let gear_box_condition = $("#edit_gear_box_condition").val();
                    let progressive_km = $("#edit_gear_box_progressive_km").val().trim();

                    // Validation Checks
                    if (gear_box_card_number === "" || gear_box_number === "" || gear_box_make === "" || gear_box_type === "" || gear_box_model === "" || gear_box_condition === "") {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "All fields are Required.",
                        });
                        return;
                    }

                    if (progressive_km !== "") {
                        if (isNaN(progressive_km)) {
                            Swal.fire({
                                icon: "warning",
                                title: "Validation Error",
                                text: "Progressive KM must be a number.",
                            });
                            return;
                        }
                        if (parseFloat(progressive_km) <= 0) {
                            Swal.fire({
                                icon: "warning",
                                title: "Validation Error",
                                text: "Progressive KM must be greater than zero.",
                            });
                            return;
                        }
                    }

                    // Confirm before update
                    Swal.fire({
                        title: "Are you sure?",
                        text: "You want to update this gear_box record.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, Update it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "../includes/backend_data.php",
                                type: "POST",
                                data: {
                                    action: "update_gear_box_details",
                                    id: id,
                                    gear_box_card_number: gear_box_card_number,
                                    gear_box_number: gear_box_number,
                                    gear_box_make: gear_box_make,
                                    gear_box_model: gear_box_model,
                                    gear_box_type: gear_box_type,
                                    gear_box_condition: gear_box_condition,
                                    progressive_km: progressive_km
                                },
                                success: function(response) {
                                    Swal.fire({
                                        icon: "success",
                                        title: "Success",
                                        text: response,
                                    }).then(() => {
                                        $("#gear_boxeditModel").modal("hide");
                                        location.reload(); // Reload table data
                                    });
                                },
                                error: function() {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Error",
                                        text: "Something went wrong! Please try again.",
                                    });
                                }
                            });
                        }
                    });
                });
            });
        </script>
    <?php
    } else if ($category_type == 'fiphpp') {
        //return the table of fip/hpp data
        //join the fip_hpp_master and fip_types table to get the fip/hpp type
        $query = "SELECT fm.id, fm.fip_hpp_card_number, fm.fip_hpp_number, fm.fip_hpp_make, fm.fip_hpp_model, ft.type as fip_hpp_type, fm.fip_hpp_condition, fm.progressive_km
                  FROM fip_hpp_master fm
                  JOIN fip_types ft ON fm.fip_hpp_type_id = ft.id
                  WHERE fm.depot_id = ? AND fm.division_id = ?  AND fm.deleted != '1' and fm.scrap_status != '1' and fm.allotted != '1'";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        //create a table and assign the data to the table and return the table
        echo "<table class='table table-bordered table-striped table-hover' id='dataTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>FIP/HPP Card Number</th>";
        echo "<th>FIP/HPP Number</th>";
        echo "<th>FIP/HPP Make</th>";
        echo "<th>FIP/HPP Model</th>";
        echo "<th>FIP/HPP Type</th>";
        echo "<th>FIP/HPP Condition</th>";
        echo "<th>Progressive KM</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['fip_hpp_card_number'] . "</td>";
            echo "<td>" . $row['fip_hpp_number'] . "</td>";
            echo "<td>" . $row['fip_hpp_make'] . "</td>";
            echo "<td>" . $row['fip_hpp_model'] . "</td>";
            echo "<td>" . $row['fip_hpp_type'] . "</td>";
            echo "<td>" . $row['fip_hpp_condition'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "<td>
            <button class='btn btn-warning btn-sm fip-hpp-edit-btn' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></button>
            <button class='btn btn-danger btn-sm fip-hpp-delete-btn' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i></button>
            </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";

        // Edit Modal for fip fpp
        echo "<div class='modal fade' id='fip_hppeditModel' tabindex='-1' role='dialog' aria-labelledby='fip_hppeditModelLabel' aria-hidden='true'>
                <div class='modal-dialog' role='document'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='fip_hppeditModelLabel'>Edit FIP/HPP Details</h5>
                            <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>
                        <div class='modal-body'>
                            <form id='fip_hppeditForm'>
                                <input type='hidden' id='edit_fip_hpp_id' name='edit_fip_hpp_id'>
                                <div class='mb-3'>
        <label class='form-label'>FIP/HPP Card No:</label> 
        <input type='text' name='edit_fip_hpp_card_number' id='edit_fip_hpp_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>FIP/HPP No:</label>
        <input type='text' name='edit_fip_hpp_number' id='edit_fip_hpp_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP's Bus Make:</label>
            <select name='edit_fip_hpp_bus_make' id='edit_fip_hpp_bus_make' class='form-control' required>";

        $sql = "SELECT * FROM makes";
        $result = mysqli_query($db, $sql);
        echo "<option value=''>Select FIP/HPP's Bus Make</option>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['make'] . "'>" . $row['make'] . "</option>";
        }
        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP Make:</label>
            <select name='edit_fip_hpp_make' id='edit_fip_hpp_make' class='form-control' required>
                <option value=''>Select FIP/HPP Make</option>
                <option value='BOSCH'>BOSCH</option>
                <option value='DENSO'>DENSO</option>
                </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP Model:</label>
            <select name='edit_fip_hpp_model' id='edit_fip_hpp_model' class='form-control' required>
                <option value=''>Select FIP/HPP Model</option>";

        $sql = "SELECT * FROM norms";
        $result = mysqli_query($db, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['emission_norms'] . "'>" . $row['emission_norms'] . "</option>";
        }
        echo "</select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP Type:</label>
            <select name='edit_fip_hpp_type' id='edit_fip_hpp_type' class='form-control' required>
                <option value=''>Select FIP/HPP Type</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>FIP/HPP Condition:</label>
            <select name='edit_fip_hpp_condition' id='edit_fip_hpp_condition' class='form-control' required>
                <option value=''>Select FIP/HPP Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='edit_fip_hpp_progressive_km' id='edit_fip_hpp_progressive_km' class='form-control' required>
        </div>";

        echo " <button type='submit' class='btn btn-primary'>Update</button>
                            </form>
                            </div>
          </div>
      </div>
    </div>";

    ?>
        <script>
            $(document).ready(function() {
                // Handle Delete
                $(document).on("click", ".fip-hpp-delete-btn", function() {
                    let id = $(this).data("id");
                    if (confirm("Are you sure you want to delete this FIP/HPP1?")) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "delete_fip_hpp_from_list",
                                id: id
                            },
                            success: function(response) {
                                alert(response);
                                location.reload(); // Refresh the page
                            }
                        });
                    }
                });
            });
            $(document).on("change", "#edit_fip_hpp_bus_make", function() {
                let fip_hppMake = $(this).val();

                if (fip_hppMake !== "") {
                    $.ajax({
                        url: "../includes/backend_data.php",
                        type: "POST",
                        data: {
                            action: "fetchfiphtypeforinventory",
                            fiph_make: fip_hppMake
                        },
                        success: function(response) {
                            $("#edit_fip_hpp_type").html(response);
                        }
                    });
                } else {
                    // Reset fip/hpp type dropdown
                    $("#edit_fip_hpp_type").html("<option value=''>Select FIP/HPP Type</option>");
                }
            });

            $(document).on("click", ".fip-hpp-edit-btn", function() {
                let id = $(this).data("id");
                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: {
                        action: "get_fip_hpp_details_for_edit",
                        id: id
                    },
                    dataType: "json",
                    success: function(data) {
                        $("#edit_fip_hpp_id").val(data.id);
                        $("#edit_fip_hpp_card_number").val(data.fip_hpp_card_number);
                        $("#edit_fip_hpp_number").val(data.fip_hpp_number);
                        $("#edit_fip_hpp_bus_make").val(data.fip_hpp_bus_make);
                        $("#edit_fip_hpp_make").val(data.fip_hpp_make);
                        $("#edit_fip_hpp_model").val(data.fip_hpp_model);
                        $("#edit_fip_hpp_condition").val(data.fip_hpp_condition);
                        $("#edit_fip_hpp_progressive_km").val(data.progressive_km);

                        // Fetch fip/hpp types based on the loaded fip/hpp make
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "fetchfiphtypeforinventory",
                                fiph_make: data.fip_hpp_bus_make
                            },
                            success: function(response) {
                                $("#edit_fip_hpp_type").html(response);
                                $("#edit_fip_hpp_type").val(data.fip_hpp_type_id); // Set the correct fip/hpp type
                            }
                        });

                        $("#fip_hppeditModel").modal("show");
                    }
                });
            });
            // Handle Update
            $("#fip_hppeditForm").submit(function(e) {
                e.preventDefault(); // Prevent default form submission

                let id = $("#edit_fip_hpp_id").val();
                let fip_hpp_card_number = $("#edit_fip_hpp_card_number").val().trim();
                let fip_hpp_number = $("#edit_fip_hpp_number").val().trim();
                let fip_hpp_bus_make = $("#edit_fip_hpp_bus_make").val().trim();
                let fip_hpp_make = $("#edit_fip_hpp_make").val().trim();
                let fip_hpp_model = $("#edit_fip_hpp_model").val().trim();
                let fip_hpp_type = $("#edit_fip_hpp_type").val().trim();
                let fip_hpp_condition = $("#edit_fip_hpp_condition").val();
                let progressive_km = $("#edit_fip_hpp_progressive_km").val().trim();

                // Validation Checks
                if (fip_hpp_card_number === "" || fip_hpp_number === "" || fip_hpp_bus_make === "" || fip_hpp_make === "" || fip_hpp_type === "" || fip_hpp_model === "" || fip_hpp_condition === "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Validation Error",
                        text: "All fields are Required.",
                    });
                    return;
                }

                if (progressive_km !== "") {
                    if (isNaN(progressive_km)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be a number.",
                        });
                        return;
                    }
                    if (parseFloat(progressive_km) <= 0) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be greater than zero.",
                        });
                        return;
                    }
                }

                // Confirm before update
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to update this FIP/HPP record.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Update it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "update_fip_hpp_details",
                                id: id,
                                fip_hpp_card_number: fip_hpp_card_number,
                                fip_hpp_number: fip_hpp_number,
                                fip_hpp_bus_make: fip_hpp_bus_make,
                                fip_hpp_make: fip_hpp_make,
                                fip_hpp_model: fip_hpp_model,
                                fip_hpp_type: fip_hpp_type,
                                fip_hpp_condition: fip_hpp_condition,
                                progressive_km: progressive_km
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: response,
                                }).then(() => {
                                    $("#fip_hppeditModel").modal("hide");
                                    location.reload(); // Reload table data
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: "Something went wrong! Please try again.",
                                });
                            }
                        });
                    }
                });
            });
        </script>
    <?php
    } else if ($category_type == 'starter') {
        //return the table of starter data
        $query = "SELECT sm.id, sm.starter_card_number, sm.starter_number, sm.starter_make, sm.starter_condition, sm.progressive_km
                  FROM starter_master sm
                  WHERE sm.depot_id = ? AND sm.division_id = ? and sm.deleted != '1' and sm.scrap_status != '1' and sm.allotted != '1'";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        //create a table and assign the data to the table and return the table
        echo "<table class='table table-bordered table-striped table-hover' id='dataTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Starter Card Number</th>";
        echo "<th>Starter Number</th>";
        echo "<th>Starter Make</th>";
        echo "<th>Starter Condition</th>";
        echo "<th>Progressive KM</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['starter_card_number'] . "</td>";
            echo "<td>" . $row['starter_number'] . "</td>";
            echo "<td>" . $row['starter_make'] . "</td>";
            echo "<td>" . $row['starter_condition'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "<td>
            <button class='btn btn-warning btn-sm starter-edit-btn' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></button>
            <button class='btn btn-danger btn-sm starter-delete-btn' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i></button>
            </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";

        // Edit Modal for starter
        echo "<div class='modal fade' id='startereditModel' tabindex='-1' role='dialog' aria-labelledby='startereditModelLabel' aria-hidden='true'>
            <div class='modal-dialog' role='document'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='startereditModelLabel'>Edit starter Details</h5>
                            <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>
                        <div class='modal-body'>
                            <form id='startereditForm'>
                                <input type='hidden' id='edit_starter_id' name='edit_starter_id'>
                                <div class='mb-3'>
        <label class='form-label'>Starter Card No:</label> 
        <input type='text' name='edit_starter_card_number' id='edit_starter_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Starter No:</label>
        <input type='text' name='edit_starter_number' id='edit_starter_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Starter Make:</label>
            <select name='edit_starter_make' id='edit_starter_make' class='form-control' required>
            <option value=''>Select Starter Make</option>
            <option value='BOSCH'>BOSCH</option>
            <option value='LUCAS'>LUCAS</option>
            <option value='BECON'>BECON</option>
            <option value='SEG (BOSCH)'>SEG (BOSCH)</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label' >Starter Condition:</label>
            <select name='edit_starter_condition' id='edit_starter_condition' class='form-control' required>
                <option value=''>Select Starter Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='edit_starter_progressive_km' id='edit_starter_progressive_km' class='form-control' required>
        </div>";

        echo " <button type='submit' class='btn btn-primary'>Update</button>

                            </form>
                        </div>
                    </div>
            </div>
        </div>";



    ?>
        <script>
            $(document).ready(function() {
                // Handle Delete
                $(document).on("click", ".starter-delete-btn", function() {
                    let id = $(this).data("id");
                    if (confirm("Are you sure you want to delete this Starter?")) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "delete_starter_from_list",
                                id: id
                            },
                            success: function(response) {
                                alert(response);
                                location.reload(); // Refresh the page
                            }
                        });
                    }
                });
            });
            $(document).on("click", ".starter-edit-btn", function() {
                let id = $(this).data("id");
                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: {
                        action: "get_starter_details_for_edit",
                        id: id
                    },
                    dataType: "json",
                    success: function(data) {
                        $("#edit_starter_id").val(data.id);
                        $("#edit_starter_card_number").val(data.starter_card_number);
                        $("#edit_starter_number").val(data.starter_number);
                        $("#edit_starter_make").val(data.starter_make);
                        $("#edit_starter_condition").val(data.starter_condition);
                        $("#edit_starter_progressive_km").val(data.progressive_km);

                        $("#startereditModel").modal("show");
                    }
                });
            });
            // Handle Update
            $("#startereditForm").submit(function(e) {
                e.preventDefault(); // Prevent default form submission

                let id = $("#edit_starter_id").val();
                let starter_card_number = $("#edit_starter_card_number").val().trim();
                let starter_number = $("#edit_starter_number").val().trim();
                let starter_make = $("#edit_starter_make").val().trim();
                let starter_condition = $("#edit_starter_condition").val();
                let progressive_km = $("#edit_starter_progressive_km").val().trim();

                // Validation Checks
                if (starter_card_number === "" || starter_number === "" || starter_make === "" || starter_condition === "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Validation Error",
                        text: "All fields are Required.",
                    });
                    return;
                }

                if (progressive_km !== "") {
                    if (isNaN(progressive_km)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be a number.",
                        });
                        return;
                    }
                    if (parseFloat(progressive_km) <= 0) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be greater than zero.",
                        });
                        return;
                    }
                }

                // Confirm before update
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to update this Starter record.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Update it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "update_starter_details",
                                id: id,
                                starter_card_number: starter_card_number,
                                starter_number: starter_number,
                                starter_make: starter_make,
                                starter_condition: starter_condition,
                                progressive_km: progressive_km
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: response,
                                }).then(() => {
                                    $("#startereditModel").modal("hide");
                                    location.reload(); // Reload table data
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: "Something went wrong! Please try again.",
                                });
                            }
                        });
                    }
                });
            });
        </script>
    <?php
    } else if ($category_type == 'alternator') {
        //return the table of alternator data
        $query = "SELECT am.id,am.alternator_card_number, am.alternator_number, am.alternator_make, am.alternator_condition, am.progressive_km
                  FROM alternator_master am
                  WHERE am.depot_id = ? AND am.division_id = ? and am.deleted != '1' and am.scrap_status != '1' and am.allotted != '1'";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        //create a table and assign the data to the table and return the table
        echo "<table class='table table-bordered table-striped table-hover' id='dataTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Alternator Card Number</th>";
        echo "<th>Alternator Number</th>";
        echo "<th>Alternator Make</th>";
        echo "<th>Alternator Condition</th>";
        echo "<th>Progressive KM</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['alternator_card_number'] . "</td>";
            echo "<td>" . $row['alternator_number'] . "</td>";
            echo "<td>" . $row['alternator_make'] . "</td>";
            echo "<td>" . $row['alternator_condition'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "<td>
            <button class='btn btn-warning btn-sm alternator-edit-btn' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></button>
            <button class='btn btn-danger btn-sm alternator-delete-btn' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i></button>
            </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";

        // Edit Modal for alternator
        echo "<div class='modal fade' id='alternatoreditModel' tabindex='-1' role='dialog' aria-labelledby='alternatoreditModelLabel' aria-hidden='true'>
            <div class='modal-dialog' role='document'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='alternatoreditModelLabel'>Edit Alternater Details</h5>
                            <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>
                        <div class='modal-body'>
                            <form id='alternatoreditForm'>
                                <input type='hidden' id='edit_alternator_id' name='edit_alternator_id'>
                                <div class='mb-3'>
        <label class='form-label'>Alternator Card No:</label>
        <input type='text' name='edit_alternator_card_number' id='edit_alternator_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
      </div>";

        echo "<div class='mb-3'>
        <label class='form-label'>Alternator No:</label>
        <input type='text' name='edit_alternator_number' id='edit_alternator_number' class='form-control' required oninput='validateAndFormatInput(this)'>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Alternator Make:</label>
            <select name='edit_alternator_make' id='edit_alternator_make' class='form-control' required>
            <option value=''>Select Alternator Make</option>
            <option value='BOSCH'>BOSCH</option>
            <option value='LUCAS'>LUCAS</option>
            <option value='BECON'>BECON</option>
            <option value='SEG (BOSCH)'>SEG (BOSCH)</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label' >Alternator Condition:</label>
            <select name='edit_alternator_condition' id='edit_alternator_condition' class='form-control' required>
                <option value=''>Select Alternator Condition</option>
                <option value='New'>New</option>
                <option value='RC'>RC</option>
            </select>
        </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='edit_alternator_progressive_km' id='edit_alternator_progressive_km' class='form-control' required>
        </div>";

        echo "<button type='submit' class='btn btn-primary'>Update</button>
                            </form>
                        </div>
                    </div>
            </div>
        </div>";
    ?>
        <script>
            $(document).ready(function() {
                // Handle Delete
                $(document).on("click", ".alternator-delete-btn", function() {
                    let id = $(this).data("id");
                    if (confirm("Are you sure you want to delete this Alternator?")) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "delete_alternator_from_list",
                                id: id
                            },
                            success: function(response) {
                                alert(response);
                                location.reload(); // Refresh the page
                            }
                        });
                    }
                });
            });
            $(document).on("click", ".alternator-edit-btn", function() {
                let id = $(this).data("id");
                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: {
                        action: "get_alternator_details_for_edit",
                        id: id
                    },
                    dataType: "json",
                    success: function(data) {
                        $("#edit_alternator_id").val(data.id);
                        $("#edit_alternator_card_number").val(data.alternator_card_number);
                        $("#edit_alternator_number").val(data.alternator_number);
                        $("#edit_alternator_make").val(data.alternator_make);
                        $("#edit_alternator_condition").val(data.alternator_condition);
                        $("#edit_alternator_progressive_km").val(data.progressive_km);

                        $("#alternatoreditModel").modal("show");
                    }
                });
            });
            // Handle Update
            $("#alternatoreditForm").submit(function(e) {
                e.preventDefault(); // Prevent default form submission

                let id = $("#edit_alternator_id").val();
                let alternator_card_number = $("#edit_alternator_card_number").val().trim();
                let alternator_number = $("#edit_alternator_number").val().trim();
                let alternator_make = $("#edit_alternator_make").val().trim();
                let alternator_condition = $("#edit_alternator_condition").val();
                let progressive_km = $("#edit_alternator_progressive_km").val().trim();


                // Validation Checks
                if (alternator_card_number === "" || alternator_number === "" || alternator_make === "" || alternator_condition === "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Validation Error",
                        text: "All fields are Required.",
                    });
                    return;
                }
                if (progressive_km !== "") {
                    if (isNaN(progressive_km)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be a number.",
                        });
                        return;
                    }
                    if (parseFloat(progressive_km) <= 0) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be greater than zero.",
                        });
                        return;
                    }
                }
                // Confirm before update
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to update this Alternator record.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Update it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "update_alternator_details",
                                id: id,
                                alternator_card_number: alternator_card_number,
                                alternator_number: alternator_number,
                                alternator_make: alternator_make,
                                alternator_condition: alternator_condition,
                                progressive_km: progressive_km
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: response,
                                }).then(() => {
                                    $("#startereditModel").modal("hide");
                                    location.reload(); // Reload table data
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: "Something went wrong! Please try again.",
                                });
                            }
                        });
                    }
                });
            });
        </script>
    <?php
    } else if ($category_type == 'rear_axle') {
        //return the table of rear axle data
        $query = "SELECT ram.id, ram.rear_axle_card_number, ram.rear_axle_number, ram.rear_axle_make, ram.rear_axle_condition, ram.progressive_km
                  FROM rear_axle_master ram
                  WHERE ram.depot_id = ? AND ram.division_id = ? and ram.deleted != '1' and ram.scrap_status != '1' and ram.allotted != '1'";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        //create a table and assign the data to the table and return the table 
        echo "<table class='table table-bordered table-striped table-hover' id='dataTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Rear Axle Card Number</th>";
        echo "<th>Rear Axle Number</th>";
        echo "<th>Rear Axle Make</th>";
        echo "<th>Rear Axle Condition</th>";
        echo "<th>Progressive KM</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['rear_axle_card_number'] . "</td>";
            echo "<td>" . $row['rear_axle_number'] . "</td>";
            echo "<td>" . $row['rear_axle_make'] . "</td>";
            echo "<td>" . $row['rear_axle_condition'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "<td>
            <button class='btn btn-warning btn-sm rear_axle-edit-btn' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></button>
            <button class='btn btn-danger btn-sm rear_axle-delete-btn' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i></button>
            </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";


        // Edit Modal for alternator
        echo "<div class='modal fade' id='rearaxleeditModel' tabindex='-1' role='dialog' aria-labelledby='rearaxleeditModelLabel' aria-hidden='true'>
            <div class='modal-dialog' role='document'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='rearaxleeditModelLabel'>Edit Rear-Axle Details</h5>
                            <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>
                        <div class='modal-body'>
                            <form id='rearaxleeditForm'>
                                <input type='hidden' id='edit_rear_axle_id' name='edit_rear_axle_id'>
                                <div class='mb-3'>
                                    <label class='form-label'>Rear Axle Card No:</label>
                                    <input type='text' name='edit_rear_axle_card_number' id='edit_rear_axle_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
                                </div>";

        echo "<div class='mb-3'>
                                <label class='form-label'>Rear Axle No:</label>
                                <input type='text' name='edit_rear_axle_number' id='edit_rear_axle_number' class='form-control' required oninput='validateAndFormatInput(this)'>
                                </div>";

        echo "<div class='mb-3'>
                                <label class='form-label'>Rear Axle Make:</label>
                                <select name='edit_rear_axle_make' id='edit_rear_axle_make' class='form-control' required>
                                <option value=''>Select Rear Axle Make</option>
                                <option value='AIL'>AIL</option>
                                <option value='BIL'>BIL</option>
                                </select>
                                </div>";

        echo "<div class='mb-3'>
                                <label class='form-label' >Rear Axle Condition:</label>
                                <select name='edit_rear_axle_condition' id='edit_rear_axle_condition' class='form-control' required>
                                    <option value=''>Select Rear Axle Condition</option>
                                    <option value='New'>New</option>
                                    <option value='RC'>RC</option>
                                </select>
                            </div>";

        echo "<div class='mb-3'>
                                <lable class='form-label'>Progressive KM (As on 31.03.2025)</label>
                                <input type='number' name='edit_rear_axle_progressive_km' id='edit_rear_axle_progressive_km' class='form-control' required>
                            </div>";

        echo "<button type='submit' class='btn btn-primary'>Update</button>
                            </form>
                        </div>
                    </div>

            </div>
        </div>";

    ?>
        <script>
            $(document).ready(function() {
                // Handle Delete
                $(document).on("click", ".rear_axle-delete-btn", function() {
                    let id = $(this).data("id");
                    if (confirm("Are you sure you want to delete this Rear Axle?")) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "delete_rear_axle_from_list",
                                id: id
                            },
                            success: function(response) {
                                alert(response);
                                location.reload(); // Refresh the page
                            }
                        });
                    }
                });
            });
            $(document).on("click", ".rear_axle-edit-btn", function() {
                let id = $(this).data("id");
                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: {
                        action: "get_rear_axle_details_for_edit",
                        id: id
                    },
                    dataType: "json",
                    success: function(data) {
                        $("#edit_rear_axle_id").val(data.id);
                        $("#edit_rear_axle_card_number").val(data.rear_axle_card_number);
                        $("#edit_rear_axle_number").val(data.rear_axle_number);
                        $("#edit_rear_axle_make").val(data.rear_axle_make);
                        $("#edit_rear_axle_condition").val(data.rear_axle_condition);
                        $("#edit_rear_axle_progressive_km").val(data.progressive_km);

                        $("#rearaxleeditModel").modal("show");
                    }
                });
            });
            // Handle Update
            $("#rearaxleeditForm").submit(function(e) {
                e.preventDefault(); // Prevent default form submission

                let id = $("#edit_rear_axle_id").val();
                let rear_axle_card_number = $("#edit_rear_axle_card_number").val().trim();
                let rear_axle_number = $("#edit_rear_axle_number").val().trim();
                let rear_axle_make = $("#edit_rear_axle_make").val().trim();
                let rear_axle_condition = $("#edit_rear_axle_condition").val();
                let progressive_km = $("#edit_rear_axle_progressive_km").val().trim();


                // Validation Checks
                if (rear_axle_card_number === "" || rear_axle_number === "" || rear_axle_make === "" || rear_axle_condition === "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Validation Error",
                        text: "All fields are Required.",
                    });
                    return;
                }
                if (progressive_km !== "") {
                    if (isNaN(progressive_km)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be a number.",
                        });
                        return;
                    }
                    if (parseFloat(progressive_km) <= 0) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be greater than zero.",
                        });
                        return;
                    }
                }
                // Confirm before update
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to update this Rear Axle record.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Update it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "update_rear_axle_details",
                                id: id,
                                rear_axle_card_number: rear_axle_card_number,
                                rear_axle_number: rear_axle_number,
                                rear_axle_make: rear_axle_make,
                                rear_axle_condition: rear_axle_condition,
                                progressive_km: progressive_km
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: response,
                                }).then(() => {
                                    $("#rearaxleeditModel").modal("hide");
                                    location.reload(); // Reload table data
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: "Something went wrong! Please try again.",
                                });
                            }
                        });
                    }
                });
            });
        </script>
    <?php
    } elseif ($category_type == 'battery') {
        //return the table of battery data
        $query = "SELECT bm.id, bm.battery_card_number, bm.battery_number, bm.battery_make, bm.progressive_km
                  FROM battery_master bm
                  WHERE bm.depot_id = ? AND bm.division_id = ? and bm.deleted != '1' and bm.scrap_status != '1' and bm.allotted != '1'";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        //create a table and assign the data to the table and return the table
        echo "<table class='table table-bordered table-striped table-hover' id='dataTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Battery Card Number</th>";
        echo "<th>Battery Number</th>";
        echo "<th>Battery Make</th>";
        echo "<th>Progressive KM</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['battery_card_number'] . "</td>";
            echo "<td>" . $row['battery_number'] . "</td>";
            echo "<td>" . $row['battery_make'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "<td>
            <button class='btn btn-warning btn-sm battery-edit-btn' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></button>
            <button class='btn btn-danger btn-sm battery-delete-btn' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i></button>
            </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        // Edit Modal for battery

        echo "<div class='modal fade' id='batteryeditModel' tabindex='-1' role='dialog' aria-labelledby='batteryeditModelLabel' aria-hidden='true'>
            <div class='modal-dialog' role='document'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='batteryeditModelLabel'>Edit Battery Details</h5>
                            <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>
                        <div class='modal-body'>
                            <form id='batteryeditForm'>
                                <input type='hidden' id='edit_battery_id' name='edit_battery_id'>
                                <div class='mb-3'>
                                <label class='form-label'>Battery Card No:</label>
                                <input type='text' name='edit_battery_card_number' id='edit_battery_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
                                </div>";

        echo "<div class='mb-3'>
                                <label class='form-label'>Battery No:</label>
                                <input type='text' name='edit_battery_number' id='edit_battery_number' class='form-control' required oninput='validateAndFormatInput(this)'>
                                </div>";

        echo "<div class='mb-3'>
                                <label class='form-label'>Battery Make:</label>
                                <select name='edit_battery_make' id='edit_battery_make' class='form-control' required>
                                <option value=''>Select Battery Make</option>";
        $sql = "SELECT * from battery_makes";
        $result = mysqli_query($db, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['make'] . "'>" . $row['make'] . "</option>";
        }
        echo "</select>
                                </div>";

        echo "<div class='mb-3'>
                                <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
                                <input type='number' name='edit_battery_progressive_km' id='edit_battery_progressive_km' class='form-control' required>
                                </div>";

        echo "<button type='submit' class='btn btn-primary'>Update</button>
                            </form>
                        </div>
                    </div>
            </div>
        </div>";
    ?>
        <script>
            $(document).ready(function() {
                // Handle Delete
                $(document).on("click", ".battery-delete-btn", function() {
                    let id = $(this).data("id");
                    if (confirm("Are you sure you want to delete this Battery?")) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "delete_battery_from_list",
                                id: id
                            },
                            success: function(response) {
                                alert(response);
                                location.reload(); // Refresh the page
                            }
                        });
                    }
                });
            });
            $(document).on("click", ".battery-edit-btn", function() {
                let id = $(this).data("id");
                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: {
                        action: "get_battery_details_for_edit",
                        id: id
                    },
                    dataType: "json",
                    success: function(data) {
                        $("#edit_battery_id").val(data.id);
                        $("#edit_battery_card_number").val(data.battery_card_number);
                        $("#edit_battery_number").val(data.battery_number);
                        $("#edit_battery_make").val(data.battery_make);
                        $("#edit_battery_progressive_km").val(data.progressive_km);

                        $("#batteryeditModel").modal("show");
                    }
                });
            });
            // Handle Update
            $("#batteryeditForm").submit(function(e) {
                e.preventDefault(); // Prevent default form submission

                let id = $("#edit_battery_id").val();
                let battery_card_number = $("#edit_battery_card_number").val().trim();
                let battery_number = $("#edit_battery_number").val().trim();
                let battery_make = $("#edit_battery_make").val().trim();
                let progressive_km = $("#edit_battery_progressive_km").val().trim();


                // Validation Checks
                if (battery_card_number === "" || battery_number === "" || battery_make === "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Validation Error",
                        text: "All fields are Required.",
                    });
                    return;
                }
                if (progressive_km !== "") {
                    if (isNaN(progressive_km)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be a number.",
                        });
                        return;
                    }
                    if (parseFloat(progressive_km) <= 0) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be greater than zero.",
                        });
                        return;
                    }
                }
                // Confirm before update
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to update this Battery record.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Update it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "update_battery_details",
                                id: id,
                                battery_card_number: battery_card_number,
                                battery_number: battery_number,
                                battery_make: battery_make,
                                progressive_km: progressive_km
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: response,
                                }).then(() => {
                                    $("#batteryeditModel").modal("hide");
                                    location.reload(); // Reload table data
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: "Something went wrong! Please try again.",
                                });
                            }
                        });
                    }
                });
            });
        </script>

    <?php
    } elseif ($category_type == 'tyre') {
        //return the table of tyre data
        $query = "SELECT tm.id, tm.tyre_card_number, tm.tyre_number, tm.tyre_make, tm.tyre_size, tm.tyre_brand, tm.tyre_condition, tm.progressive_km
                  FROM tyre_master tm
                  WHERE tm.depot_id = ? AND tm.division_id = ? AND tm.deleted != '1' and tm.scrap_status != '1' and tm.allotted != '1'";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        //create a table and assign the data to the table and return the table
        echo "<table class='table table-bordered table-striped table-hover' id='dataTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Tyre Card Number</th>";
        echo "<th>Tyre Number</th>";
        echo "<th>Tyre Make</th>";
        echo "<th>Tyre Size</th>";
        echo "<th>Tyre Brand/Pattern</th>";
        echo "<th>Tyre Condition</th>";
        echo "<th>Progressive KM</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['tyre_card_number'] . "</td>";
            echo "<td>" . $row['tyre_number'] . "</td>";
            echo "<td>" . $row['tyre_make'] . "</td>";
            echo "<td>" . $row['tyre_size'] . "</td>";
            echo "<td>" . $row['tyre_brand'] . "</td>";
            echo "<td>" . $row['tyre_condition'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "<td>
            <button class='btn btn-warning btn-sm tyre-edit-btn' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></button>
            <button class='btn btn-danger btn-sm tyre-delete-btn' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i></button>
            </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";

        // Edit Modal for tyre
        echo "<div class='modal fade' id='tyreeditModel' tabindex='-1' role='dialog' aria-labelledby='tyreeditModelLabel' aria-hidden='true'>
            <div class='modal-dialog' role='document'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='tyreeditModelLabel'>Edit Tyre Details</h5>
                            <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>
                        <div class='modal-body'>
                            <form id='tyreeditForm'>
                                <input type='hidden' id='edit_tyre_id' name='edit_tyre_id'>
                                
                                <div class='mb-3'>
                                <label class='form-label'>Tyre Card No:</label>
                                <input type='text' name='edit_tyre_card_number' id='edit_tyre_card_number' class='form-control' required oninput='validateAndFormatInput(this)'>
                                </div>

                                <div class='mb-3'>
                                <label class='form-label'>Tyre No:</label>
                                <input type='text' name='edit_tyre_number' id='edit_tyre_number' class='form-control' required oninput='validateAndFormatInput(this)'>
                                </div>

                                <div class='mb-3'>
                                <label class='form-label'>Tyre Make:</label>
                                <select name='edit_tyre_make' id='edit_tyre_make' class='form-control' required>
                                <option value=''>Select Tyre Make</option>
                                <option value='JK'>JK</option>
                                <option value='CEAT'>CEAT</option>
                                <option value='MRF'>MRF</option>
                                <option value='Apollo'>Apollo</option>
                                <option value='MICHELIN'>MICHELIN</option>
                                </select>
                                </div>

                                <div class='mb-3'>
                                <label class='form-label'>Tyre Size:</label>
                                <select name='edit_tyre_size' id='edit_tyre_size' class='form-control' required>
                                <option value=''>Select Tyre Size</option>
                                <option value='900X20-16 PR NYLON TYRE'>900X20-16 PR NYLON TYRE</option>
                                <option value='900X20-16 PR RADIAL TYRE'>900X20-16 PR RADIAL TYRE</option>
                                <option value='10.00x20 PR,RIB'>1000x20 PR,RIB</option>
                                <option value='10.00x20 PR,RIB NYLON TYRE'>10.00x20 PR,RIB NYLON TYRE</option>
                                <option value='10.00x20 PR,RIB RADIAL TYRE'>10.00x20 PR,RIB RADIAL TYRE</option>
                                <option value='295-80R 22.5 16PR'>295-80R 22.5 16PR</option>
                                <option value='295-80R 22.5'>295-80R 22.5</option>
                                <option value='235-75 R17.5 PR'>235-75 R17.5 PR</option>
                                <option value='235-75 R17.5 14PR'>235-75 R17.5</option>
                                <option value='235-75 R17.5 14PR'>235-75 R17.5 14PR</option>
                                </select>
                                </div>

                                <div class='mb-3'>
                                <label class='form-label'>Tyre Brand/Pattern:</label>
                                <select name='edit_tyre_brand' id='edit_tyre_brand' class='form-control' required>
                                <option value=''>Select Tyre Brand/Pattern</option>
                                <option value='FM SUPER'>FM SUPER</option>
                                <option value='VIKRANT-TRACK KING JK-JET RIB/JET-R-MILES'>VIKRANT-TRACK KING JK-JET RIB/JET-R-MILES</option>
                                <option value='JK-JUH-3/JUC2'>JK-JUH-3/JUC2</option>
                                <option value='S1T4'>S1T4</option>
                                <option value='WINMILE-R'>WINMILE-R</option>
                                <option value='WINMILE-X3R'>WINMILE-X3R</option>
                                <option value='WINMILE-AW'>WINMILE-AW</option>
                                <option value='JK-JUH-3+'>JK-JUH-3+</option>
                                <option value='S1R4'>S1R4</option>
                                <option value='S1R4 PLUS'>S1R4 PLUS</option>
                                <option value='JK-KUM'>JK-KUM</option>
                                <option value='STEEL MUSCLE S1 R4 PLUS'>STEEL MUSCLE S1 R4 PLUS</option>
                                <option value='JK-JUH-5'>JK-JUH-5</option>
                                <option value='ENDURACE RA'>ENDURACE RA</option>
                                <option value='X-MULTI'>X-MULTI</option>
                                </select>
                                </div>

                                <div class='mb-3'>
                                <label class='form-label'>Tyre Condition:</label>
                                <select name='edit_tyre_condition' id='edit_tyre_condition' class='form-control' required>
                                    <option value=''>Select Tyre Condition</option>
                                    <option value='New'>New</option>
                                    <option value='RC'>RC</option>
                                </select>
                                </div>

                                <div class='mb-3'>
                                <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
                                <input type='number' name='edit_tyre_progressive_km' id='edit_tyre_progressive_km' class='form-control' required>
                                </div>

                                <button type='submit' class='btn btn-primary'>Update</button>
                            </form>
                        </div>
                    </div>
            </div>
        </div>";
    ?>
        <script>
            $(document).ready(function() {
                // Handle Delete
                $(document).on("click", ".tyre-delete-btn", function() {
                    let id = $(this).data("id");
                    if (confirm("Are you sure you want to delete this Tyre?")) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "delete_tyre_from_list",
                                id: id
                            },
                            success: function(response) {
                                alert(response);
                                location.reload(); // Refresh the page
                            }
                        });
                    }
                });
            });
            $(document).on("click", ".tyre-edit-btn", function() {
                let id = $(this).data("id");
                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: {
                        action: "get_tyre_details_for_edit",
                        id: id
                    },
                    dataType: "json",
                    success: function(data) {
                        $("#edit_tyre_id").val(data.id);
                        $("#edit_tyre_card_number").val(data.tyre_card_number);
                        $("#edit_tyre_number").val(data.tyre_number);
                        $("#edit_tyre_make").val(data.tyre_make);
                        $("#edit_tyre_size").val(data.tyre_size);
                        $("#edit_tyre_brand").val(data.tyre_brand);
                        $("#edit_tyre_condition").val(data.tyre_condition);
                        $("#edit_tyre_progressive_km").val(data.progressive_km);

                        $("#tyreeditModel").modal("show");
                    }
                });
            });
            // Handle Update
            $("#tyreeditForm").submit(function(e) {
                e.preventDefault(); // Prevent default form submission

                let id = $("#edit_tyre_id").val();
                let tyre_card_number = $("#edit_tyre_card_number").val().trim();
                let tyre_number = $("#edit_tyre_number").val().trim();
                let tyre_make = $("#edit_tyre_make").val().trim();
                let tyre_size = $("#edit_tyre_size").val().trim();
                let tyre_brand = $("#edit_tyre_brand").val().trim();
                let tyre_condition = $("#edit_tyre_condition").val();
                let progressive_km = $("#edit_tyre_progressive_km").val().trim();


                // Validation Checks
                if (tyre_card_number === "" ||
                    tyre_number === "" || tyre_make === "" || tyre_size === "" || tyre_brand === "" || tyre_condition === "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Validation Error",
                        text: "All fields are Required.",
                    });
                    return;
                }
                if (progressive_km !== "") {
                    if (isNaN(progressive_km)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be a number.",
                        });
                        return;
                    }
                    if (parseFloat(progressive_km) <= 0) {
                        Swal.fire({
                            icon: "warning",
                            title: "Validation Error",
                            text: "Progressive KM must be greater than zero.",
                        });
                        return;
                    }
                }
                // Confirm before update
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to update this Tyre record.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Update it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "../includes/backend_data.php",
                            type: "POST",
                            data: {
                                action: "update_tyre_details",
                                id: id,
                                tyre_card_number: tyre_card_number,
                                tyre_number: tyre_number,
                                tyre_make: tyre_make,
                                tyre_size: tyre_size,
                                tyre_brand: tyre_brand,
                                tyre_condition: tyre_condition,
                                progressive_km: progressive_km
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: response,
                                }).then(() => {
                                    $("#tyreeditModel").modal("hide");
                                    location.reload(); // Reload table data
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: "Something went wrong! Please try again.",
                                });
                            }
                        });
                    }
                });
            });
        </script>
<?php
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'fetch_kmpl_report_day') {
    $depot_id = $_POST['depot'];
    $division_id = $_POST['division'];
    $date = $_POST['date'];

    $query = "SELECT vk.`bus_number`, vk.`route_no`, vk.`driver_1_pf`, vk.`driver_2_pf`, vk.`logsheet_no`, vk.`km_operated`, vk.`hsd`, vk.`kmpl`, vk.`thumps_id`, vk.`remarks`, b.`make`, b.`emission_norms`, f.`thumbs`
              FROM vehicle_kmpl vk
              JOIN bus_registration b ON vk.`bus_number` = b.`bus_number`
              JOIN feedback f ON vk.`thumps_id` = f.`id`
              WHERE vk.`depot_id` = ? AND vk.`division_id` = ? AND vk.deleted != '1' AND vk.`date` = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "iis", $depot_id, $division_id, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    function getEmployeeDetails($pf_no)
    {
        if (empty($pf_no)) return "N/A";

        $api_url = "http://192.168.1.34:8880/dvp/database/combined_api_data.php?pf_no=" . urlencode($pf_no);
        $response = file_get_contents($api_url);

        if ($response === false) {
            return "API Error"; // If API is unreachable
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return "JSON Error"; // If JSON is invalid
        }

        // Check if 'data' key exists and is not empty
        if (!empty($data['data']) && isset($data['data'][0]['EMP_NAME'], $data['data'][0]['token_number'])) {
            $emp_name = $data['data'][0]['EMP_NAME'];
            $token_no = $data['data'][0]['token_number'];
            return "$token_no ($emp_name)";
        }

        return "N/A"; // If no data found
    }

    // Format the date to dd-mm-yyyy
    $formatted_date = date("d-m-Y", strtotime($date));

    echo "<h1 class='text-center'>KMPL Report for Depot: " . $_SESSION['DEPOT'] . " of Date: " . $formatted_date . "</h1>";
    echo "<table class='table table-bordered table-striped table-hover' id='dataTable'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>S.No</th>";
    echo "<th>Bus Number</th>";
    echo "<th>Route Number</th>";
    echo "<th>Driver Token</th>";
    echo "<th>Logsheet Number</th>";
    echo "<th>KM Operated</th>";
    echo "<th>HSD</th>";
    echo "<th>KMPL</th>";
    echo "<th>Thumps</th>";
    echo "<th>Remarks</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    $sno = 1; // Initialize Serial Number
    $total_km_operated = 0;
    $total_hsd = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $driver_1_details = getEmployeeDetails($row['driver_1_pf']);
        $driver_2_details = getEmployeeDetails($row['driver_2_pf']);

        $total_km_operated += $row['km_operated'];
        $total_hsd += $row['hsd'];

        echo "<tr>";
        echo "<td>" . $sno++ . "</td>"; // Serial Number
        echo "<td>" . $row['bus_number'] . "</td>";
        echo "<td>" . $row['route_no'] . "</td>";
        echo "<td>";
        echo $driver_1_details;
        if (!empty($row['driver_2_pf'])) {
            echo "<br>" . $driver_2_details;
        }
        echo "</td>";
        echo "<td>" . $row['logsheet_no'] . "</td>";
        echo "<td>" . $row['km_operated'] . "</td>";
        echo "<td>" . $row['hsd'] . "</td>";
        echo "<td>" . $row['kmpl'] . "</td>";

        // Display thumbs up/down only for Leyland BS-6 buses
        if ($row['make'] == 'Leyland' && $row['emission_norms'] == 'BS-6') {
            echo "<td>" . $row['thumbs'] . "</td>";
        } else {
            echo "<td>N/A</td>";
        }

        echo "<td>" . $row['remarks'] . "</td>";
        echo "</tr>";
    }

    // Calculate overall KMPL (avoid division by zero)
    $overall_kmpl = ($total_hsd > 0) ? round($total_km_operated / $total_hsd, 2) : 0;

    // Add total row
    echo "<tr style='font-weight: bold; background-color: #f8f9fa;'>";
    echo "<td colspan='5' class='text-center'>Total</td>"; // Merge first 5 columns
    echo "<td>" . $total_km_operated . "</td>"; // Total KM Operated
    echo "<td>" . $total_hsd . "</td>"; // Total HSD
    echo "<td>" . $overall_kmpl . "</td>"; // Overall KMPL
    echo "<td colspan='2' class='text-center'>N/A</td>"; // Other columns
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_bus_details' && isset($_POST['bus_number'])) {
    $bus_number = mysqli_real_escape_string($db, $_POST['bus_number']);
    $query = mysqli_query($db, "SELECT 
    br.*, 
    bs.bus_type 
FROM bus_registration br
LEFT JOIN bus_seat_category bs 
    ON br.bus_sub_category = bs.bus_sub_category
WHERE br.bus_number = '$bus_number'");

    if ($row = mysqli_fetch_assoc($query)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'chassis_number' => $row['chassis_number'],
                'make' => $row['make'],
                'emission_norms' => $row['emission_norms'],
                'bus_category' => $row['bus_sub_category'],
                'bus_body_builder' => $row['bus_body_builder'],
                'seating_capacity' => $row['seating_capacity'],
                'date_of_commission' => $row['doc'],
                'wheel_base' => $row['wheel_base'],
                'bus_type' => $row['bus_type']
            ]
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_bus_details_scraped' && isset($_POST['bus_number'])) {
    $bus_number = mysqli_real_escape_string($db, $_POST['bus_number']);
    $query = mysqli_query($db, "SELECT 
    br.*, 
    bs.bus_type 
FROM bus_scrap_data br
LEFT JOIN bus_seat_category bs 
    ON br.bus_sub_category = bs.bus_sub_category
WHERE br.bus_number = '$bus_number'");

    if ($row = mysqli_fetch_assoc($query)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'chassis_number' => $row['chassis_number'],
                'make' => $row['make'],
                'emission_norms' => $row['emission_norms'],
                'bus_category' => $row['bus_sub_category'],
                'bus_body_builder' => $row['bus_body_builder'],
                'seating_capacity' => $row['seating_capacity'],
                'date_of_commission' => $row['doc'],
                'wheel_base' => $row['wheel_base'],
                'bus_type' => $row['bus_type']
            ]
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "get_engine_details" && isset($_POST['make']) && isset($_POST['norms'])) {
    $make = $_POST['make'];
    $norms = $_POST['norms'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $q = mysqli_query($db, "SELECT id, engine_number, engine_card_number FROM engine_master WHERE deleted !='1' and scrap_status != '1' and allotted != '1' and engine_make = '$make' AND engine_model = '$norms' and division_id = $division_id and depot_id = $depot_id");

    echo '<option value="">-- Select Engine Number(card no.)--</option>';
    while ($r = mysqli_fetch_assoc($q)) {
        echo '<option value="' . $r['id'] . '">' . $r['engine_number'] . ' (' . $r['engine_card_number'] . ')</option>';
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "get_fiphpp_details" && isset($_POST['make']) && isset($_POST['norms'])) {
    $make = $_POST['make'];
    $norms = $_POST['norms'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $q = mysqli_query($db, "SELECT id, `fip_hpp_card_number`, `fip_hpp_number` FROM `fip_hpp_master` WHERE deleted !='1' and scrap_status != '1' and allotted != '1' and `fip_hpp_bus_make` = '$make' AND `fip_hpp_model` = '$norms' and `division_id` = $division_id and `depot_id` = $depot_id");

    echo '<option value="">-- Select FIP/HPP Number(card no.)--</option>';
    while ($r = mysqli_fetch_assoc($q)) {
        echo '<option value="' . $r['id'] . '">' . $r['fip_hpp_number'] . ' (' . $r['fip_hpp_card_number'] . ')</option>';
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "get_gearbox_details" && isset($_POST['make']) && isset($_POST['norms'])) {
    $make = $_POST['make'];
    $norms = $_POST['norms'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $q = mysqli_query($db, "SELECT id, `gear_box_card_number`, `gear_box_number` FROM `gearbox_master` WHERE deleted !='1' and scrap_status != '1' and allotted != '1' and `gear_box_make` = '$make' AND `gear_box_model` = '$norms' and `division_id` = $division_id and `depot_id` = $depot_id");

    echo '<option value="">-- Select Gear Box Number(card no.)--</option>';
    while ($r = mysqli_fetch_assoc($q)) {
        echo '<option value="' . $r['id'] . '">' . $r['gear_box_number'] . ' (' . $r['gear_box_card_number'] . ')</option>';
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "get_starter_details") {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $q = mysqli_query($db, "SELECT id, `starter_card_number`, `starter_number` FROM `starter_master` WHERE deleted !='1' and scrap_status != '1' and allotted != '1' and `division_id` = $division_id and `depot_id` = $depot_id");

    echo '<option value="">-- Select Starter Number(card no.)--</option>';
    while ($r = mysqli_fetch_assoc($q)) {
        echo '<option value="' . $r['id'] . '">' . $r['starter_number'] . ' (' . $r['starter_card_number'] . ')</option>';
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "get_alternator_details") {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $q = mysqli_query($db, "SELECT id, `alternator_card_number`, `alternator_number` FROM `alternator_master` WHERE deleted !='1' and scrap_status != '1' and allotted != '1' and `division_id` = $division_id and `depot_id` = $depot_id");

    echo '<option value="">-- Select Alternator Number(card no.)--</option>';
    while ($r = mysqli_fetch_assoc($q)) {
        echo '<option value="' . $r['id'] . '">' . $r['alternator_number'] . ' (' . $r['alternator_card_number'] . ')</option>';
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "get_rearaxel_details") {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $q = mysqli_query($db, "SELECT * FROM `rear_axle_master` WHERE deleted !='1' and scrap_status != '1' and allotted != '1' and `division_id` = $division_id and `depot_id` = $depot_id");

    echo '<option value="">-- Select Rear Axel Number(card no.)--</option>';
    while ($r = mysqli_fetch_assoc($q)) {
        echo '<option value="' . $r['id'] . '">' . $r['rear_axle_number'] . ' (' . $r['rear_axle_card_number'] . ')</option>';
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "get_battery_details") {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $q = mysqli_query($db, "SELECT * FROM `battery_master` WHERE deleted !='1' and scrap_status != '1' and allotted != '1' and `division_id` = $division_id and `depot_id` = $depot_id");

    echo '<option value="">-- Select Battery Number(card no.)--</option>';
    while ($r = mysqli_fetch_assoc($q)) {
        echo '<option value="' . $r['id'] . '">' . $r['battery_number'] . ' (' . $r['battery_card_number'] . ')</option>';
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_engine_from_list') {
    $id = $_POST['id'];
    //before deleting check if the engine is allotted or not if allotted find the bus number and return the bus number the vehicle no present in bus_inventory table
    $query = "SELECT bus_number FROM bus_inventory WHERE engine_id = ? AND deleted != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "Engine is allotted to bus number: " . $row['bus_number'] . ". Cannot delete.";
        exit;
    }



    $query = "UPDATE engine_master SET deleted = 1 WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Engine deleted successfully.";
    } else {
        echo "Error deleting engine.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_engine_details_for_edit') {
    $id = $_POST['id'];
    $query = "SELECT * FROM engine_master WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_assoc($result));
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_engine_details') {
    $id = $_POST['id'];
    $engine_card_number = trim($_POST['engine_card_number']);
    $engine_number = trim($_POST['engine_number']);
    $engine_make = trim($_POST['engine_make']);
    $engine_model = trim($_POST['engine_model']);
    $engine_type = trim($_POST['engine_type']);
    $engine_condition = trim($_POST['engine_condition']);
    $progressive_km = trim($_POST['progressive_km']);

    // Backend Validation: Check Empty Fields
    if (empty($engine_card_number) || empty($engine_number) || empty($engine_make) || empty($engine_model) || empty($engine_type) || empty($engine_condition)) {
        echo "All fields are required";
        exit;
    }

    if (!empty($progressive_km) && !is_numeric($progressive_km)) {
        echo "Progressive KM must be a number.";
        exit;
    }

    // Check for Duplicate Engine Number
    $query = "SELECT id FROM engine_master WHERE engine_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $engine_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate engine number";
        exit;
    }

    // Check for Duplicate Engine Card Number
    $query = "SELECT id FROM engine_master WHERE engine_card_number = ? AND id != ?  AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $engine_card_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate engine card number";
        exit;
    }

    // Update Engine Record
    $query = "UPDATE engine_master SET engine_card_number = ?, engine_number = ?, engine_make = ?, engine_type_id = ?, engine_model = ?, engine_condition = ?, progressive_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sssssssi", $engine_card_number, $engine_number, $engine_make, $engine_type, $engine_model, $engine_condition, $progressive_km, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_gear_box_details_for_edit') {
    $id = $_POST['id'];
    $query = "SELECT * FROM gearbox_master WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_assoc($result));
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_gear_box_details') {
    $id = $_POST['id'];
    $gear_box_card_number = trim($_POST['gear_box_card_number']);
    $gear_box_number = trim($_POST['gear_box_number']);
    $gear_box_make = trim($_POST['gear_box_make']);
    $gear_box_model = trim($_POST['gear_box_model']);
    $gear_box_type = trim($_POST['gear_box_type']);
    $gear_box_condition = trim($_POST['gear_box_condition']);
    $progressive_km = trim($_POST['progressive_km']);

    // Backend Validation: Check Empty Fields
    if (empty($gear_box_card_number) || empty($gear_box_number) || empty($gear_box_make) || empty($gear_box_model) || empty($gear_box_type) || empty($gear_box_condition)) {
        echo "All fields are required";
        exit;
    }

    if (!empty($progressive_km) && !is_numeric($progressive_km)) {
        echo "Progressive KM must be a number.";
        exit;
    }

    // Check for Duplicate gear_box Number
    $query = "SELECT id FROM gearbox_master WHERE gear_box_number = ? AND id != ?  AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $gear_box_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate gearbox number";
        exit;
    }

    // Check for Duplicate gear_box Card Number
    $query = "SELECT id FROM gearbox_master WHERE gear_box_card_number = ? AND id != ?  AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $gear_box_card_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate gearbox card number";
        exit;
    }

    // Update gear_box Record
    $query = "UPDATE gearbox_master SET gear_box_card_number = ?, gear_box_number = ?, gear_box_make = ?, gear_box_model = ?, gear_box_condition = ?, progressive_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssssi", $gear_box_card_number, $gear_box_number, $gear_box_make, $gear_box_model, $gear_box_condition, $progressive_km, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_gear_box_from_list') {
    $id = $_POST['id'];
    //before deleting check if the engine is allotted or not if allotted find the bus number and return the bus number the vehicle no present in bus_inventory table
    $query = "SELECT bus_number FROM bus_inventory WHERE gearbox_id = ? AND deleted != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "Gear Box is allotted to bus number: " . $row['bus_number'] . ". Cannot delete.";
        exit;
    }

    $query = "UPDATE gearbox_master SET deleted = 1 WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Gear Box deleted successfully.";
    } else {
        echo "Error deleting GearBox.";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_fip_hpp_from_list') {
    $id = $_POST['id'];
    //before deleting check if the engine is allotted or not if allotted find the bus number and return the bus number the vehicle no present in bus_inventory table
    $query = "SELECT bus_number FROM bus_inventory WHERE fiphpp_id = ? AND deleted != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "FIP/HPP is allotted to bus number: " . $row['bus_number'] . ". Cannot delete.";
        exit;
    }

    $query = "UPDATE fip_hpp_master SET deleted = 1 WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo "FIP/HPP deleted successfully.";
    } else {
        echo "Error deleting FIP/HPP.";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_fip_hpp_details_for_edit') {
    $id = $_POST['id'];
    $query = "SELECT * FROM fip_hpp_master WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_assoc($result));
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_fip_hpp_details') {
    $id = $_POST['id'];
    $fip_hpp_card_number = trim($_POST['fip_hpp_card_number']);
    $fip_hpp_number = trim($_POST['fip_hpp_number']);
    $fip_hpp_bus_make = trim($_POST['fip_hpp_bus_make']);
    $fip_hpp_make = trim($_POST['fip_hpp_make']);
    $fip_hpp_model = trim($_POST['fip_hpp_model']);
    $fip_hpp_type = trim($_POST['fip_hpp_type']);
    $fip_hpp_condition = trim($_POST['fip_hpp_condition']);
    $progressive_km = trim($_POST['progressive_km']);

    // Backend Validation: Check Empty Fields
    if (empty($fip_hpp_card_number) || empty($fip_hpp_number) || empty($fip_hpp_bus_make) || empty($fip_hpp_make) || empty($fip_hpp_model) || empty($fip_hpp_condition) || empty($fip_hpp_type)) {
        echo "All fields are required";
        exit;
    }

    if (!empty($progressive_km) && !is_numeric($progressive_km)) {
        echo "Progressive KM must be a number.";
        exit;
    }

    // Check for Duplicate FIP/HPP Number
    $query = "SELECT id FROM fip_hpp_master WHERE fip_hpp_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $fip_hpp_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate fiphpp number";
        exit;
    }

    // Check for Duplicate FIP/HPP Card Number
    $query = "SELECT id FROM fip_hpp_master WHERE fip_hpp_card_number = ? AND id != ? and deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $fip_hpp_card_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate fiphpp card number";
        exit;
    }

    // Update FIP/HPP Record
    $query = "UPDATE fip_hpp_master SET fip_hpp_card_number = ?, fip_hpp_number = ?, fip_hpp_bus_make = ?, fip_hpp_make = ?, fip_hpp_model = ?, fip_hpp_type_id = ?, fip_hpp_condition = ?, progressive_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssssssi", $fip_hpp_card_number, $fip_hpp_number, $fip_hpp_bus_make, $fip_hpp_make, $fip_hpp_model, $fip_hpp_type, $fip_hpp_condition, $progressive_km, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_starter_from_list') {
    $id = $_POST['id'];
    //before deleting check if the engine is allotted or not if allotted find the bus number and return the bus number the vehicle no present in bus_inventory table
    $query = "SELECT bus_number FROM bus_inventory WHERE starter_id = ? AND deleted != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "Starter is allotted to bus number: " . $row['bus_number'] . ". Cannot delete.";
        exit;
    }

    $query = "UPDATE starter_master SET deleted = 1 WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Starter deleted successfully.";
    } else {
        echo "Error deleting Starter.";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_starter_details_for_edit') {
    $id = $_POST['id'];
    $query = "SELECT * FROM starter_master WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_assoc($result));
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_starter_details') {
    $id = $_POST['id'];
    $starter_card_number = trim($_POST['starter_card_number']);
    $starter_number = trim($_POST['starter_number']);
    $starter_make = trim($_POST['starter_make']);
    $starter_condition = trim($_POST['starter_condition']);
    $progressive_km = trim($_POST['progressive_km']);

    // Backend Validation: Check Empty Fields
    if (empty($starter_card_number) || empty($starter_number) || empty($starter_make) || empty($starter_condition)) {
        echo "All fields are required";
        exit;
    }

    if (!empty($progressive_km) && !is_numeric($progressive_km)) {
        echo "Progressive KM must be a number.";
        exit;
    }

    // Check for Duplicate Starter Number
    $query = "SELECT id FROM starter_master WHERE starter_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $starter_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate starter number";
        exit;
    }

    // Check for Duplicate Starter Card Number
    $query = "SELECT id FROM starter_master WHERE starter_card_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $starter_card_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate starter card number";
        exit;
    }

    // Update Starter Record
    $query = "UPDATE starter_master SET starter_card_number = ?, starter_number = ?, starter_make = ?,  starter_condition = ?, progressive_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sssssi", $starter_card_number, $starter_number, $starter_make, $starter_condition, $progressive_km, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_alternator_from_list') {
    $id = $_POST['id'];
    //before deleting check if the engine is allotted or not if allotted find the bus number and return the bus number the vehicle no present in bus_inventory table
    $query = "SELECT bus_number FROM bus_inventory WHERE alternator_id = ? AND deleted != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "Alternator is allotted to bus number: " . $row['bus_number'] . ". Cannot delete.";
        exit;
    }

    $query = "UPDATE alternator_master SET deleted = 1 WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Alternator deleted successfully.";
    } else {
        echo "Error deleting Alternator.";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_alternator_details_for_edit') {
    $id = $_POST['id'];
    $query = "SELECT * FROM alternator_master WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_assoc($result));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_alternator_details') {
    $id = $_POST['id'];
    $alternator_card_number = trim($_POST['alternator_card_number']);
    $alternator_number = trim($_POST['alternator_number']);
    $alternator_make = trim($_POST['alternator_make']);
    $alternator_condition = trim($_POST['alternator_condition']);
    $progressive_km = trim($_POST['progressive_km']);

    // Backend Validation: Check Empty Fields
    if (empty($alternator_card_number) || empty($alternator_number) || empty($alternator_make) || empty($alternator_condition)) {
        echo "All fields are required";
        exit;
    }

    if (!empty($progressive_km) && !is_numeric($progressive_km)) {
        echo "Progressive KM must be a number.";
        exit;
    }

    // Check for Duplicate Alternator Number
    $query = "SELECT id FROM alternator_master WHERE alternator_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $alternator_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate alternator number";
        exit;
    }

    // Check for Duplicate Alternator Card Number
    $query = "SELECT id FROM alternator_master WHERE alternator_card_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $alternator_card_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate alternator card number";
        exit;
    }

    // Update Alternator Record
    $query = "UPDATE alternator_master SET alternator_card_number = ?, alternator_number = ?, alternator_make = ?,  alternator_condition = ?, progressive_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sssssi", $alternator_card_number, $alternator_number, $alternator_make, $alternator_condition, $progressive_km, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_rear_axle_from_list') {
    $id = $_POST['id'];
    //before deleting check if the engine is allotted or not if allotted find the bus number and return the bus number the vehicle no present in bus_inventory table
    $query = "SELECT bus_number FROM bus_inventory WHERE rear_axel_id = ? AND deleted != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "Rear Axel is allotted to bus number: " . $row['bus_number'] . ". Cannot delete.";
        exit;
    }

    $query = "UPDATE rear_axle_master SET deleted = 1 WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Rear Axel deleted successfully.";
    } else {
        echo "Error deleting Rear Axel.";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_rear_axle_details_for_edit') {
    $id = $_POST['id'];
    $query = "SELECT * FROM rear_axle_master WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_assoc($result));
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_rear_axle_details') {
    $id = $_POST['id'];
    $rear_axle_card_number = trim($_POST['rear_axle_card_number']);
    $rear_axle_number = trim($_POST['rear_axle_number']);
    $rear_axle_make = trim($_POST['rear_axle_make']);
    $rear_axle_condition = trim($_POST['rear_axle_condition']);
    $progressive_km = trim($_POST['progressive_km']);

    // Backend Validation: Check Empty Fields
    if (empty($rear_axle_card_number) || empty($rear_axle_number) || empty($rear_axle_make) || empty($rear_axle_condition)) {
        echo "All fields are required";
        exit;
    }

    if (!empty($progressive_km) && !is_numeric($progressive_km)) {
        echo "Progressive KM must be a number.";
        exit;
    }

    // Check for Duplicate Rear Axel Number
    $query = "SELECT id FROM rear_axle_master WHERE rear_axle_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $rear_axle_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate rear axel number";
        exit;
    }

    // Check for Duplicate Rear Axel Card Number
    $query = "SELECT id FROM rear_axle_master WHERE rear_axle_card_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $rear_axle_card_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate rear axel card number";
        exit;
    }

    // Update Rear Axel Record
    $query = "UPDATE rear_axle_master SET rear_axle_card_number = ?, rear_axle_number = ?, rear_axle_make = ?,  rear_axle_condition = ?, progressive_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sssssi", $rear_axle_card_number, $rear_axle_number, $rear_axle_make, $rear_axle_condition, $progressive_km, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_battery_from_list') {
    $id = $_POST['id'];
    //before deleting check if the battery is allotted or not in battery_1_id and battery_2_id if allotted find the bus number and return the bus number the vehicle no present in bus_inventory table
    $query = "SELECT bus_number FROM bus_inventory WHERE battery_1_id = ? OR battery_2_id = ? AND deleted != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "Battery is allotted to bus number: " . $row['bus_number'] . ". Cannot delete.";
        exit;
    }

    $query = "UPDATE battery_master SET deleted = 1 WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Battery deleted successfully.";
    } else {
        echo "Error deleting Battery.";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_battery_details_for_edit') {
    $id = $_POST['id'];
    $query = "SELECT * FROM battery_master WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_assoc($result));
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_battery_details') {
    $id = $_POST['id'];
    $battery_card_number = trim($_POST['battery_card_number']);
    $battery_number = trim($_POST['battery_number']);
    $battery_make = trim($_POST['battery_make']);
    $progressive_km = trim($_POST['progressive_km']);

    // Backend Validation: Check Empty Fields
    if (empty($battery_card_number) || empty($battery_number) || empty($battery_make)) {
        echo "All fields are required";
        exit;
    }

    if (!empty($progressive_km) && !is_numeric($progressive_km)) {
        echo "Progressive KM must be a number.";
        exit;
    }

    // Check for Duplicate Battery Number
    $query = "SELECT id FROM battery_master WHERE battery_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $battery_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate battery number";
        exit;
    }

    // Check for Duplicate Battery Card Number
    $query = "SELECT id FROM battery_master WHERE battery_card_number = ? AND id != ? AND deleted != '1' AND scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $battery_card_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate battery card number";
        exit;
    }

    // Update Battery Record
    $query = "UPDATE battery_master SET battery_card_number = ?, battery_number = ?, battery_make = ?, progressive_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssi", $battery_card_number, $battery_number, $battery_make, $progressive_km, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_tyre_from_list') {
    $id = $_POST['id'];

    $query = "UPDATE tyre_master SET deleted = 1 WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo "Tyre deleted successfully.";
    } else {
        echo "Error deleting Tyre.";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_tyre_details_for_edit') {
    $id = $_POST['id'];
    $query = "SELECT * FROM tyre_master WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_assoc($result));
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_tyre_details') {
    $id = $_POST['id'];
    $tyre_card_number = trim($_POST['tyre_card_number']);
    $tyre_number = trim($_POST['tyre_number']);
    $tyre_make = trim($_POST['tyre_make']);
    $tyre_size = trim($_POST['tyre_size']);
    $tyre_brand = trim($_POST['tyre_brand']);
    $tyre_condition = trim($_POST['tyre_condition']);
    $progressive_km = trim($_POST['progressive_km']);

    // Backend Validation: Check Empty Fields
    if (empty($tyre_card_number) || empty($tyre_number) || empty($tyre_make) || empty($tyre_condition) || empty($tyre_size) || empty($tyre_brand)) {
        echo "All fields are required";
        exit;
    }

    if (!empty($progressive_km) && !is_numeric($progressive_km)) {
        echo "Progressive KM must be a number.";
        exit;
    }

    // Check for Duplicate Tyre Number
    $query = "SELECT id FROM tyre_master WHERE tyre_number = ? AND id != ? and deleted != '1' and scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $tyre_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate tyre number";
        exit;
    }

    // Check for Duplicate Tyre Card Number
    $query = "SELECT id FROM tyre_master WHERE tyre_card_number = ? AND id != ? and deleted != '1' and scrap_status != '1'";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "si", $tyre_card_number, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    //show error message if duplicate tyre card number found
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "duplicate tyre card number";
        exit;
    }

    // Update Tyre Record
    $query = "UPDATE tyre_master SET tyre_card_number = ?, tyre_number = ?, tyre_make = ?, tyre_size = ?, tyre_brand = ?,  tyre_condition = ?, progressive_km = ? WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sssssssi", $tyre_card_number, $tyre_number, $tyre_make, $tyre_size, $tyre_brand, $tyre_condition, $progressive_km, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'getProgressiveKMofParts') {
    $partType = $_POST["part_type"];
    $partId = $_POST["part_id"];

    // Map part types to respective table names
    $tableMapping = [
        "engine_no" => "engine_master",
        "fiphpp_no" => "fip_hpp_master",
        "gearbox_no" => "gearbox_master",
        "starter_no" => "starter_master",
        "alternator_no" => "alternator_master",
        "rear_axel_no" => "rear_axle_master",
        "battery_1_no" => "battery_master",
        "battery_2_no" => "battery_master"
    ];

    if (array_key_exists($partType, $tableMapping)) {
        $tableName = $tableMapping[$partType];
        $query = "SELECT progressive_km FROM $tableName WHERE id = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $partId);
        $stmt->execute();
        $stmt->bind_result($progressive_km);
        $stmt->fetch();
        $stmt->close();

        // If progressive_km is NULL, send an empty response
        if ($progressive_km === null) {
            echo json_encode(["status" => "success", "progressive_km" => ""]);
        } else {
            echo json_encode(["status" => "success", "progressive_km" => $progressive_km]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid part type"]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submit_bus_inventory') {
    // Sanitize and validate input data
    $busNumber = $_POST['bus_number'];
    $chessis_number = $_POST['chassis_number'];
    $make = $_POST['make'];
    $emission_norms = $_POST['emission_norms'];
    $bus_category = $_POST['bus_category'];
    $bus_body_builder = $_POST['bus_body_builder'];
    $seating_capacity = $_POST['seating_capacity'];
    $date_of_commission = $_POST['date_of_commission'];
    $wheel_base = $_POST['wheel_base'];
    $bus_type = $_POST['bus_type'];
    $date_of_fc = $_POST['date_of_fc'];
    $bus_progressive_km = $_POST['bus_progressive_km'];
    $engine_no = $_POST['engine_no'];
    $engine_no_progressive_km = $_POST['engine_no_progressive_km'];
    $fiphpp_no = $_POST['fiphpp_no'];
    $fiphpp_no_progressive_km = $_POST['fiphpp_no_progressive_km'];
    $gearbox_no = $_POST['gearbox_no'];
    $gearbox_no_progressive_km = $_POST['gearbox_no_progressive_km'];
    $starter_no = $_POST['starter_no'];
    $starter_no_progressive_km = $_POST['starter_no_progressive_km'];
    $alternator_no = $_POST['alternator_no'];
    $alternator_no_progressive_km = $_POST['alternator_no_progressive_km'];
    $rear_axel_no = $_POST['rear_axel_no'];
    $rear_axel_no_progressive_km = $_POST['rear_axel_no_progressive_km'];
    $battery_1_no = $_POST['battery_1_no'];
    $battery_1_no_progressive_km = $_POST['battery_1_no_progressive_km'];
    $battery_2_no = $_POST['battery_2_no'];
    $battery_2_no_progressive_km = $_POST['battery_2_no_progressive_km'];
    $speed_governor = $_POST['speed_governor'];


    if ($speed_governor == 'FITTED') {
        $speed_governor_model = $_POST['speed_governor_model'];
        $speed_governor_serial_no = $_POST['speed_governor_serial_no'];
    } else {
        $speed_governor_model = null;
        $speed_governor_serial_no = null;
    }
    if ($bus_type == 'AC') {
        $ac_unit = $_POST['ac_unit'];
        $ac_model = $_POST['ac_model'];
    } else {
        $ac_unit = null;
        $ac_model = null;
    }

    if ($bus_category == 'Jn-NURM Midi City' || $bus_category == 'Branded DULT City') {
        if ($bus_category == 'Jn-NURM Midi City') {
            $led_board = $_POST['led_board'];
            if ($led_board == 'YES') {
                $led_board_make = $_POST['led_board_make'];
                $led_board_front = $_POST['led_board_front'];
                $led_board_front_inside = null;
                $led_board_rear = $_POST['led_board_rear'];
                $led_board_lhs_outside = null;
            } else {
                $led_board_make = null;
                $led_board_front = null;
                $led_board_front_inside = null;
                $led_board_rear = null;
                $led_board_lhs_outside = null;
            }
        } elseif ($bus_category == 'Branded DULT City') {
            $led_board = $_POST['led_board'];
            if ($led_board == 'YES') {
                $led_board_make = $_POST['led_board_make'];
                $led_board_front = $_POST['led_board_front'];
                $led_board_front_inside = $_POST['led_board_front_inside'];
                $led_board_rear = $_POST['led_board_rear'];
                $led_board_lhs_outside = $_POST['led_board_lhs_outside'];
            } else {
                $led_board_make = null;
                $led_board_front = null;
                $led_board_front_inside = null;
                $led_board_rear = null;
                $led_board_lhs_outside = null;
            }
        }
    } else {
        $led_board = null;
        $led_board_make = null;
        $led_board_front = null;
        $led_board_front_inside = null;
        $led_board_rear = null;
        $led_board_lhs_outside = null;
    }
    if ($bus_category == 'Jn-NURM Midi City' || $emission_norms == 'BS-6') {
        $camera_f_saloon = $_POST['camera_f_saloon'];
        $camera_f_outside = $_POST['camera_f_outside'];
        $camera_r_saloon = $_POST['camera_r_saloon'];
        $camera_r_outside = $_POST['camera_r_outside'];
        $camera_monitor = $_POST['camera_monitor'];
        $camera_storage_unit = $_POST['camera_storage_unit'];
    } else {
        $camera_f_saloon = null;
        $camera_f_outside = null;
        $camera_r_saloon = null;
        $camera_r_outside = null;
        $camera_monitor = null;
        $camera_storage_unit = null;
    }
    if ($bus_category == 'Jn-NURM Midi City' || $emission_norms == 'BS-6' || $emission_norms == 'BS-4') {
        $pis_mike_amplefier = $_POST['pis_mike_amplefier'];
    } else {
        $pis_mike_amplefier = null;
    }
    if ($emission_norms == 'BS-6') {
        $vlts_unit_present = $_POST['vlts_unit_present'];
        $vlts_unit_make = $_POST['vlts_unit_make'];
    } else {
        $vlts_unit_present = null;
        $vlts_unit_make = null;
    }
    if ($emission_norms == 'BS-6' || $emission_norms == 'BS-4') {
        $fdas_fdss_present = $_POST['fdas_fdss_present'];
    } else {
        $fdas_fdss_present = null;
    }
    $fire_extinguisher_nos = $_POST['fire_extinguisher_nos'];
    $fire_extinguisher_total_kg = $_POST['fire_extinguisher_total_kg'];
    $first_aid_box_status = $_POST['first_aid_box_status'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $user_name = $_SESSION['USERNAME'];
    $inventory_date = '2025-03-31';
    $scrap_status_from_bus = $_POST['scrap_status'];

    //validate the inputs depend on condition if any value is not found if condition also true then return a response of erro which fields are missing
    $missing_fields = [];
    if (empty($busNumber)) $missing_fields[] = "Bus Number";
    if (empty($chessis_number)) $missing_fields[] = "Chassis Number";
    if (empty($make)) $missing_fields[] = "Make";
    if (empty($emission_norms)) $missing_fields[] = "Emission Norms";
    if (empty($bus_category)) $missing_fields[] = "Bus Category";
    if (empty($bus_body_builder)) $missing_fields[] = "Bus Body Builder";
    if (empty($seating_capacity)) $missing_fields[] = "Seating Capacity";
    if (empty($date_of_commission)) $missing_fields[] = "Date of Commission";
    if (empty($wheel_base)) $missing_fields[] = "Wheel Base";
    if (empty($bus_type)) $missing_fields[] = "Bus Type";
    if (empty($date_of_fc)) $missing_fields[] = "Date of FC";
    if (empty($bus_progressive_km)) $missing_fields[] = "Bus Progressive KM";
    if (empty($engine_no)) $missing_fields[] = "Engine No";
    if (empty($engine_no_progressive_km)) $missing_fields[] = "Engine No Progressive KM";
    if (empty($fiphpp_no)) $missing_fields[] = "FIP/HPP No";
    if (empty($fiphpp_no_progressive_km)) $missing_fields[] = "FIP/HPP No Progressive KM";
    if (empty($gearbox_no)) $missing_fields[] = "Gearbox No";
    if (empty($gearbox_no_progressive_km)) $missing_fields[] = "Gearbox No Progressive KM";
    if (empty($starter_no)) $missing_fields[] = "Starter No";
    if (empty($starter_no_progressive_km)) $missing_fields[] = "Starter No Progressive KM";
    if (empty($alternator_no)) $missing_fields[] = "Alternator No";
    if (empty($alternator_no_progressive_km)) $missing_fields[] = "Alternator No Progressive KM";
    if (empty($rear_axel_no)) $missing_fields[] = "Rear Axel No";
    if (empty($rear_axel_no_progressive_km)) $missing_fields[] = "Rear Axel No Progressive KM";
    if (empty($battery_1_no)) $missing_fields[] = "Battery 1 No";
    if (empty($battery_1_no_progressive_km)) $missing_fields[] = "Battery 1 No Progressive KM";
    if (empty($battery_2_no)) $missing_fields[] = "Battery 2 No";
    if (empty($battery_2_no_progressive_km)) $missing_fields[] = "Battery 2 No Progressive KM";
    if (empty($speed_governor)) $missing_fields[] = "Speed Governor";
    if ($speed_governor == 'FITTED') {
        if (empty($speed_governor_model)) $missing_fields[] = "Speed Governor Model";
        if (empty($speed_governor_serial_no)) $missing_fields[] = "Speed Governor Serial No";
    }
    if ($bus_type == 'AC') {
        if (empty($ac_unit)) $missing_fields[] = "AC Unit";
        if (empty($ac_model)) $missing_fields[] = "AC Model";
    }
    if ($bus_category == 'Jn-NURM Midi City' || $bus_category == 'Branded DULT City') {
        if ($bus_category == 'Jn-NURM Midi City') {
            if (empty($led_board)) $missing_fields[] = "LED Board";
            if ($led_board == 'YES') {
                if (empty($led_board_make)) $missing_fields[] = "LED Board Make";
                if (empty($led_board_front)) $missing_fields[] = "LED Board Front";
                if (empty($led_board_rear)) $missing_fields[] = "LED Board Rear";
            }
        } elseif ($bus_category == 'Branded DULT City') {
            if (empty($led_board)) $missing_fields[] = "LED Board";
            if ($led_board == 'YES') {
                if (empty($led_board_make)) $missing_fields[] = "LED Board Make";
                if (empty($led_board_front)) $missing_fields[] = "LED Board Front";
                if (empty($led_board_front_inside)) $missing_fields[] = "LED Board Front Inside";
                if (empty($led_board_rear)) $missing_fields[] = "LED Board Rear";
                if (empty($led_board_lhs_outside)) $missing_fields[] = "LED Board LHS Outside";
            }
        }
    }
    if ($bus_category == 'Jn-NURM Midi City' || $emission_norms == 'BS-6') {
        if (empty($camera_f_saloon)) $missing_fields[] = "Camera Front Saloon";
        if (empty($camera_f_outside)) $missing_fields[] = "Camera Front Outside";
        if (empty($camera_r_saloon)) $missing_fields[] = "Camera Rear Saloon";
        if (empty($camera_r_outside)) $missing_fields[] = "Camera Rear Outside";
        if (empty($camera_monitor)) $missing_fields[] = "Camera Monitor";
        if (empty($camera_storage_unit)) $missing_fields[] = "Camera Storage Unit";
    }
    if ($bus_category == 'Jn-NURM Midi City' || $emission_norms == 'BS-6' || $emission_norms == 'BS-4') {
        if (empty($pis_mike_amplefier)) $missing_fields[] = "PIS Mike Amplifier";
    }
    if ($emission_norms == 'BS-6') {
        if (empty($vlts_unit_present)) $missing_fields[] = "VLTS Unit Present";
        if (empty($vlts_unit_make)) $missing_fields[] = "VLTS Unit Make";
    }
    if ($emission_norms == 'BS-6' || $emission_norms == 'BS-4') {
        if (empty($fdas_fdss_present)) $missing_fields[] = "FDAS FDSS Present";
    }
    if (empty($fire_extinguisher_nos)) $missing_fields[] = "Fire Extinguisher Nos";
    if ($fire_extinguisher_total_kg === '' || !isset($fire_extinguisher_total_kg)) {
        $missing_fields[] = "Fire Extinguisher Total KG";
    }
    if (empty($first_aid_box_status)) $missing_fields[] = "First Aid Box Status";


    // If there are missing fields, return an error response
    if (!empty($missing_fields)) {
        echo json_encode(["status" => "error", "message" => "Missing fields: " . implode(", ", $missing_fields)]);
        exit;
    }

    // Check if the bus number already exists in the bus_inventory table
    $query = "SELECT id FROM bus_inventory WHERE bus_number = ? AND deleted != '1' and inventory_date = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $busNumber, $inventory_date);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Bus number already exists."]);
        $stmt->close();
        exit;
    }

    $tableName = [
        "engine_no" => "engine_master",
        "fiphpp_no" => "fip_hpp_master",
        "gearbox_no" => "gearbox_master",
        "starter_no" => "starter_master",
        "alternator_no" => "alternator_master",
        "rear_axel_no" => "rear_axle_master",
        "battery_1_no" => "battery_master",
        "battery_2_no" => "battery_master"
    ];

    $partId = [
        "engine_no" => $engine_no,
        "fiphpp_no" => $fiphpp_no,
        "gearbox_no" => $gearbox_no,
        "starter_no" => $starter_no,
        "alternator_no" => $alternator_no,
        "rear_axel_no" => $rear_axel_no,
        "battery_1_no" => $battery_1_no,
        "battery_2_no" => $battery_2_no
    ];

    $partName = [
        "engine_no" => "Engine",
        "fiphpp_no" => "FIP/HPP",
        "gearbox_no" => "Gearbox",
        "starter_no" => "Starter",
        "alternator_no" => "Alternator",
        "rear_axel_no" => "Rear Axel",
        "battery_1_no" => "Battery 1",
        "battery_2_no" => "Battery 2"
    ];

    foreach ($tableName as $key => $value) {
        $query = "SELECT id, allotted, deleted, scrap_status FROM $value WHERE id = ?";
        $stmt = $db->prepare($query);

        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "SQL error for table '$value': " . $db->error]);
            exit;
        }

        $stmt->bind_param("i", $partId[$key]);
        $stmt->execute();
        $stmt->bind_result($id, $alloted, $deleted, $scrap_status);
        $stmt->fetch();

        if ($alloted == 1) {
            echo json_encode(["status" => "error", "message" => "{$partName[$key]} is already alloted to another bus please select different {$partName[$key]}"]);
            exit;
        } elseif ($deleted == 1) {
            echo json_encode(["status" => "error", "message" => "{$partName[$key]} is already deleted from list please select other {$partName[$key]}"]);
            exit;
        } elseif ($scrap_status == 1) {
            echo json_encode(["status" => "error", "message" => "{$partName[$key]} is already scrapped from the list please select other {$partName[$key]}"]);
            exit;
        }

        $stmt->close();
    }



    // update every parts progressive km in the respective table
    $query = "UPDATE engine_master SET progressive_km = ? , allotted = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $engine_no_progressive_km, $engine_no);
    $stmt->execute();
    $stmt->close();

    $query = "UPDATE fip_hpp_master SET progressive_km = ? , allotted = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $fiphpp_no_progressive_km, $fiphpp_no);
    $stmt->execute();
    $stmt->close();

    $query = "UPDATE gearbox_master SET progressive_km = ? , allotted = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $gearbox_no_progressive_km, $gearbox_no);
    $stmt->execute();
    $stmt->close();

    $query = "UPDATE starter_master SET progressive_km = ? , allotted = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $starter_no_progressive_km, $starter_no);
    $stmt->execute();
    $stmt->close();

    $query = "UPDATE alternator_master SET progressive_km = ? , allotted = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $alternator_no_progressive_km, $alternator_no);
    $stmt->execute();

    $stmt->close();

    $query = "UPDATE rear_axle_master SET progressive_km = ? , allotted = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $rear_axel_no_progressive_km, $rear_axel_no);
    $stmt->execute();
    $stmt->close();

    $query = "UPDATE battery_master SET progressive_km = ?, allotted = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $battery_1_no_progressive_km, $battery_1_no);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $battery_2_no_progressive_km, $battery_2_no);
    $stmt->execute();
    $stmt->close();
    $todaydate = date('Y-m-d');
    $engine_no1 = 'engine_master';
    $fiphpp_no1 = 'fip_hpp_master';
    $gearbox_no1 = 'gearbox_master';
    $starter_no1 = 'starter_master';
    $alternator_no1 = 'alternator_master';
    $rear_axel_no1 = 'rear_axle_master';
    $battery_1_no1 = 'battery_master';
    $battery_2_no1 = 'battery_master';
    $part_count1 = '1';
    $part_count2 = '2';
    //insert every parts id and part name into inventory_part_track table
    $query = "INSERT INTO `inventory_item_track`(`part_name`, `part_id`, `bus_number`, `from_date`, part_count, division_id, depot_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssiii", $engine_no1, $engine_no, $busNumber, $todaydate, $part_count1, $division_id, $depot_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssiii", $fiphpp_no1, $fiphpp_no, $busNumber, $todaydate, $part_count1, $division_id, $depot_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssiii", $gearbox_no1, $gearbox_no, $busNumber, $todaydate, $part_count1, $division_id, $depot_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssiii", $starter_no1, $starter_no, $busNumber, $todaydate, $part_count1, $division_id, $depot_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssiii", $alternator_no1, $alternator_no, $busNumber, $todaydate, $part_count1, $division_id, $depot_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssiii", $rear_axel_no1, $rear_axel_no, $busNumber, $todaydate, $part_count1, $division_id, $depot_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssiii", $battery_1_no1, $battery_1_no, $busNumber, $todaydate, $part_count1, $division_id, $depot_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssiii", $battery_2_no1, $battery_2_no, $busNumber, $todaydate, $part_count2, $division_id, $depot_id);
    $stmt->execute();
    $stmt->close();

    //insert the data into bus_inventory table
    $sqlinventory = "INSERT INTO `bus_inventory`( `bus_number`, `date_of_fc`, `bus_progressive_km`, `engine_id`, `fiphpp_id`, `gearbox_id`, `starter_id`, `alternator_id`, `rear_axel_id`, `battery_1_id`, `battery_2_id`, `speed_governor`, `speed_governor_model`, `speed_governor_serial_no`, `ac_unit`, `ac_model`, `led_board`, `led_board_make`, `led_board_front`, `led_board_front_inside`, `led_board_rear`, `led_board_lhs_outside`, `camera_f_saloon`, `camera_f_outside`, `camera_r_saloon`, `camera_r_outside`, `camera_monitor`, `camera_storage_unit`, `pis_mike_amplefier`, `vlts_unit_present`, `vlts_unit_make`, `fdas_fdss_present`, `fire_extinguisher_nos`, `fire_extinguisher_total_kg`, `first_aid_box_status`, division_id, depot_id, created_by, inventory_date, scraped) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sqlinventory);

    $stmt->bind_param("sssssssssssssssssssssssssssssssssssiissi", $busNumber, $date_of_fc, $bus_progressive_km, $engine_no, $fiphpp_no, $gearbox_no, $starter_no, $alternator_no, $rear_axel_no, $battery_1_no, $battery_2_no, $speed_governor, $speed_governor_model, $speed_governor_serial_no, $ac_unit, $ac_model, $led_board, $led_board_make, $led_board_front, $led_board_front_inside, $led_board_rear, $led_board_lhs_outside, $camera_f_saloon, $camera_f_outside, $camera_r_saloon, $camera_r_outside, $camera_monitor, $camera_storage_unit, $pis_mike_amplefier, $vlts_unit_present, $vlts_unit_make, $fdas_fdss_present, $fire_extinguisher_nos, $fire_extinguisher_total_kg, $first_aid_box_status, $division_id, $depot_id, $user_name, $inventory_date, $scrap_status_from_bus);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Bus inventory data submitted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $stmt->error]);
    }
    $stmt->close();
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'get_bus_details_for_inventory_view') {
    $id = intval($_POST['id']);

    $query = "SELECT bi.*, 
               CASE WHEN bi.scraped = 0 THEN br.make ELSE bs.make END AS make,
               CASE WHEN bi.scraped = 0 THEN br.doc ELSE bs.doc END AS doc,
               CASE WHEN bi.scraped = 0 THEN br.emission_norms ELSE bs.emission_norms END AS emission_norms,
               CASE WHEN bi.scraped = 0 THEN br.chassis_number ELSE bs.chassis_number END AS chassis_number,
                CASE WHEN bi.scraped = 0 THEN br.bus_sub_category ELSE bs.bus_sub_category END AS bus_sub_category,
                CASE WHEN bi.scraped = 0 THEN br.bus_body_builder ELSE bs.bus_body_builder END AS bus_body_builder,
                CASE WHEN bi.scraped = 0 THEN br.seating_capacity ELSE bs.seating_capacity END AS seating_capacity,
                CASE WHEN bi.scraped = 0 THEN br.wheel_base ELSE bs.wheel_base END AS wheel_base,
            
            em.*, 
            fm.*, 
            gm.*, 
            sm.*,
            am.*, 
            ram.*, 
            b1.battery_card_number as b1_battery_card_number, b1.battery_number as b1_battery_number, b1.battery_make as b1_battery_make, b1.progressive_km as b1_progressive_km,
            b2.battery_card_number as b2_battery_card_number, b2.battery_number as b2_battery_number, b2.battery_make as b2_battery_make, b2.progressive_km as b2_progressive_km,
            l.division,l.depot,
            em.progressive_km as engine_progressive_km,
            et.type as engine_type,
            fm.progressive_km as fip_hpp_progressive_km,
            ft.type as fip_hpp_type,
            ft.model as fiphpp_model,
            gm.progressive_km as gear_box_progressive_km,
            gt.type as gear_box_type,
            sm.progressive_km as starter_progressive_km,
            am.progressive_km as alternator_progressive_km,
            ram.progressive_km as rear_axle_progressive_km,
            bct.bus_type
        FROM bus_inventory bi
        LEFT JOIN bus_registration br ON bi.bus_number = br.bus_number
        LEFT JOIN bus_scrap_data bs ON bi.bus_number = bs.bus_number
        LEFT JOIN engine_master em ON bi.engine_id = em.id
        LEFT JOIN fip_hpp_master fm ON bi.fiphpp_id = fm.id
        LEFT JOIN gearbox_master gm ON bi.gearbox_id = gm.id
        LEFT JOIN starter_master sm ON bi.starter_id = sm.id
        LEFT JOIN alternator_master am ON bi.alternator_id = am.id
        LEFT JOIN rear_axle_master ram ON bi.rear_axel_id = ram.id
        LEFT JOIN battery_master b1 ON bi.battery_1_id = b1.id
        LEFT JOIN battery_master b2 ON bi.battery_2_id = b2.id
        LEFT JOIN location l on bi.division_id = l.division_id and bi.depot_id = l.depot_id
        LEFT JOIN engine_types et on em.engine_type_id = et.id
        LEFT JOIN fip_types ft on fm.fip_hpp_type_id = ft.id
        LEFT JOIN gearbox_types gt on gm.gear_box_type_id = gt.id
        LEFT JOIN bus_seat_category bct ON 
    CASE WHEN bi.scraped = 0 THEN br.bus_sub_category ELSE bs.bus_sub_category END = bct.bus_sub_category
        WHERE bi.id = $id
    ";

    $result = mysqli_query($db, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        echo "<h1 class='text-center mb-4'>Inventory Details for Bus Number: <strong>" . htmlspecialchars($row['bus_number']) . "</strong></h1>";
        echo "<table class='table table-bordered '><tbody>";

        // Bus Basic Info
        echo "<tr><th>Bus Number</th><td>" . htmlspecialchars($row['bus_number']) . "</td><th>Division</th><td>" . htmlspecialchars($row['division']) . "</td><th>Depot</th><td>" . htmlspecialchars($row['depot']) . "</td><th>DOC</th><td>" . date('d-m-Y', strtotime($row['doc'])) . "</td></tr>";
        echo "<tr><th>Make</th><td>" . htmlspecialchars($row['make']) . "</td><th>Emission Norms</th><td>" . htmlspecialchars($row['emission_norms']) . "</td><th>Chassis No</th><td>" . htmlspecialchars($row['chassis_number']) . "</td><th>Bus Category</th><td>" . htmlspecialchars($row['bus_sub_category']) . "</td></tr>";
        echo "<tr><th>Body Builder</th><td>" . htmlspecialchars($row['bus_body_builder']) . "</td><th>Seating Capacity</th><td>" . htmlspecialchars($row['seating_capacity']) . "</td><th>wheel Base</th><td>" . htmlspecialchars($row['wheel_base']) . "</td><th>Bus Progressive Km</th><td>" . htmlspecialchars($row['bus_progressive_km']) . "</td></tr>";
        echo "<tr><th>FC date</th><td colspan='2'>" . date('d-m-Y', strtotime($row['date_of_fc'])) . "</td></tr>";
        echo "<tr><th colspan='8'><h4 class='text-center'>Engine Details</h4></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['engine_card_number']) . "</td><th>Engine No</th><td>" . htmlspecialchars($row['engine_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['engine_make']) . "</td><th>Model</th><td>" . htmlspecialchars($row['engine_model']) . "</td></tr>";
        echo "<tr><th>Type</th><td>" . htmlspecialchars($row['engine_type']) . "</td><th>Condition</th><td>" . htmlspecialchars($row['engine_condition']) . "</td><th colspan='2'>Progressive KM</th><td>" . htmlspecialchars($row['engine_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h4 class='text-center'>FIP/HPP Details</h4></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['fip_hpp_card_number']) . "</td><th>FIP/HPP No</th><td>" . htmlspecialchars($row['fip_hpp_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['fip_hpp_make']) . "</td><th>Model</th><td>" . htmlspecialchars($row['fip_hpp_model']) . "</td></tr>";
        echo "<tr><th>Bus Make</th><td>" . htmlspecialchars($row['fip_hpp_bus_make']) . "</td><th>Type</th><td>" . htmlspecialchars($row['fip_hpp_type']) . "  " . htmlspecialchars($row['fiphpp_model']) . "</td><th>Condition</th><td>" . htmlspecialchars($row['fip_hpp_condition']) . "</td><th>Progressive KM</th><td>" . htmlspecialchars($row['fip_hpp_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h4 class='text-center'>Gear Box Details</h4></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['gear_box_card_number']) . "</td><th>Gear Box No</th><td>" . htmlspecialchars($row['gear_box_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['gear_box_make']) . "</td><th>Model</th><td>" . htmlspecialchars($row['gear_box_model']) . "</td></tr>";
        echo "<tr><th>Type</th><td colspan='2'>" . htmlspecialchars($row['gear_box_type']) . "  " . htmlspecialchars($row['gear_box_model']) . "</td><th>Condition</th><td>" . htmlspecialchars($row['gear_box_condition']) . "</td><th colspan='2'>Progressive KM</th><td>" . htmlspecialchars($row['gear_box_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h4 class='text-center'>Starer Details</h4></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['starter_card_number']) . "</td><th>Starter No</th><td>" . htmlspecialchars($row['starter_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['starter_make']) . "</td><th>condition</th><td>" . htmlspecialchars($row['starter_condition']) . "</td></tr>";
        echo "<tr><th colspan='2'>Progressive KM</th><td colspan='2'>" . htmlspecialchars($row['starter_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h4 class='text-center'>Alternator Details</h4></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['alternator_card_number']) . "</td><th>Alternator No</th><td>" . htmlspecialchars($row['alternator_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['alternator_make']) . "</td><th>condition</th><td>" . htmlspecialchars($row['alternator_condition']) . "</td></tr>";
        echo "<tr><th colspan='2'>Progressive KM</th><td colspan='2'>" . htmlspecialchars($row['alternator_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h4 class='text-center'>Rear Axle Details</h4></th></tr>";
        echo "<tr><th>Card No</th><td>" . htmlspecialchars($row['rear_axle_card_number']) . "</td><th>Rear Axle No</th><td>" . htmlspecialchars($row['rear_axle_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['rear_axle_make']) . "</td><th>condition</th><td>" . htmlspecialchars($row['rear_axle_condition']) . "</td></tr>";
        echo "<tr><th colspan='2'>Progressive KM</th><td colspan='2'>" . htmlspecialchars($row['rear_axle_progressive_km']) . "</td></tr>";
        echo "<tr><th colspan='8'><h4 class='text-center'>Battery Details</h4></th></tr>";
        echo "<tr><th>Card No 1</th><td>" . htmlspecialchars($row['b1_battery_card_number']) . "</td><th>battery No 1</th><td>" . htmlspecialchars($row['b1_battery_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['b1_battery_make']) . "</td><th>Progressive KM</th><td>" . htmlspecialchars($row['b1_progressive_km']) . "</td></tr>";
        echo "<tr><th>Card No 2</th><td>" . htmlspecialchars($row['b2_battery_card_number']) . "</td><th>battery No 2</th><td>" . htmlspecialchars($row['b2_battery_number']) . "</td><th>Make</th><td>" . htmlspecialchars($row['b2_battery_make']) . "</td><th>Progressive KM</th><td>" . htmlspecialchars($row['b2_progressive_km']) . "</td></tr>";
        if ($row['speed_governor'] == 'FITTED') {
            echo "<tr><th colspan='8'><h4 class='text-center'>Speed Governor Details</h4></th></tr>";
            echo "<tr><th>Speed Governor</th><td>" . htmlspecialchars($row['speed_governor']) . "</td><th>Model</th><td>" . htmlspecialchars($row['speed_governor_model']) . "</td><th>Serial No</th><td>" . htmlspecialchars($row['speed_governor_serial_no']) . "</td></tr>";
        } else {
            echo "<tr><th colspan='8'><h4 class='text-center'>Speed Governor Details</h4></th></tr>";
            echo "<tr><th>Speed Governor</th><td>" . htmlspecialchars($row['speed_governor']) . "</td></tr>";
        }
        if ($row['bus_type'] == 'AC') {
            echo "<tr><th colspan='8'><h4 class='text-center'>AC Unit Details</h4></th></tr>";
            echo "<tr><th>AC Unit</th><td>" . htmlspecialchars($row['ac_unit']) . "</td><th>Model</th><td>" . htmlspecialchars($row['ac_model']) . "</td></tr>";
        }
        if ($row['bus_sub_category'] == 'Jn-NURM Midi City') {
            echo "<tr><th colspan='8'><h4 class='text-center'>LED Board Details</h4></th></tr>";
            if ($row['led_board'] == 'YES') {
                echo "<tr><th>LED Board</th><td>" . htmlspecialchars($row['led_board']) . "</td><th>Make</th><td>" . htmlspecialchars($row['led_board_make']) . "</td><th>Front</th><td>" . htmlspecialchars($row['led_board_front']) . "</td><th>Rear</th><td>" . htmlspecialchars($row['led_board_rear']) . "</td></tr>";
            } else {
                echo "<tr><th>LED Board</th><td>" . htmlspecialchars($row['led_board']) . "</td></tr>";
            }
        }
        if ($row['bus_sub_category'] == 'Branded DULT City') {
            echo "<tr><th colspan='8'><h4 class='text-center'>LED Board Details</h4></th></tr>";
            if ($row['led_board'] == 'YES') {
                echo "<tr><th>LED Board</th><td>" . htmlspecialchars($row['led_board']) . "</td><th>Make</th><td>" . htmlspecialchars($row['led_board_make']) . "</td><th>Front</th><td>" . htmlspecialchars($row['led_board_front']) . "</td><th>Rear</th><td>" . htmlspecialchars($row['led_board_rear']) . "</td></tr>";
                echo "<tr><th>Front Inside</th><td>" . htmlspecialchars($row['led_board_front_inside']) . "</td><th>LHS Outside</th><td>" . htmlspecialchars($row['led_board_lhs_outside']) . "</td></tr>";
            } else {
                echo "<tr><th>LED Board</th><td>" . htmlspecialchars($row['led_board']) . "</td></tr>";
            }
        }
        if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6') {
            echo "<tr><th colspan='8'><h4 class='text-center'>Camera Details</h4></th></tr>";
            echo "<tr><th>Front Saloon</th><td>" . htmlspecialchars($row['camera_f_saloon']) . "</td><th>Front Outside</th><td>" . htmlspecialchars($row['camera_f_outside']) . "</td><th>Rear Saloon</th><td>" . htmlspecialchars($row['camera_r_saloon']) . "</td><th>Rear Outside</th><td>" . htmlspecialchars($row['camera_r_outside']) . "</td></tr>";
            echo "<tr><th>Monitor</th><td>" . htmlspecialchars($row['camera_monitor']) . "</td><th>Storage Unit</th><td>" . htmlspecialchars($row['camera_storage_unit']) . "</td>";
        }
        if ($row['bus_sub_category'] == 'Jn-NURM Midi City' || $row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
            echo "<th>PIS Mike Amplifier</th><td>" . htmlspecialchars($row['pis_mike_amplefier']) . "</td></tr>";
        }
        if ($row['emission_norms'] == 'BS-6') {
            echo "<tr><th colspan='8'><h4 class='text-center'>General Details</h4></th></tr>";
            echo "<tr><th>VLTS Unit Present</th><td>" . htmlspecialchars($row['vlts_unit_present']) . "</td><th>Make</th><td>" . htmlspecialchars($row['vlts_unit_make']) . "</td>";
        }
        if ($row['emission_norms'] == 'BS-6' || $row['emission_norms'] == 'BS-4') {
            echo "<th>FDAS FDSS Present</th><td>" . htmlspecialchars($row['fdas_fdss_present']) . "</td></tr>";
        }
        echo "<tr><th>Fire Extinguisher Nos</th><td>" . htmlspecialchars($row['fire_extinguisher_nos']) . "</td><th>Total KG</th><td>" . htmlspecialchars($row['fire_extinguisher_total_kg']) . "</td><th>First Aid Box Status</th><td>" . htmlspecialchars($row['first_aid_box_status']) . "</td></tr>";

        echo "</tbody></table>";
    } else {
        echo "<p class='text-danger'>No details found.</p>";
    }

    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'vehicle_out_fetch_schedules') {
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];
    $bus_number = $_POST['veh_no_out'];
    $todaydate = date('Y-m-d');

    // Step 1: Check if the vehicle is already on operation (status = 1)
    /*$check_query = "SELECT vehicle_no FROM sch_veh_out WHERE vehicle_no = ? AND schedule_status = 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bind_param("s", $bus_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Selected vehicle is already on Operation. Please select a different vehicle.'
        ]);
        exit;
    }*/

    // Step 2: Fetch schedules if the vehicle is not in operation and sch_key_no not in(SELECT `sch_no` FROM `sch_veh_out` WHERE `division_id`= ? and `depot_id` = ? and `departed_date`= ?)
    $query = "SELECT sch_key_no, bus_number_1, bus_number_2, additional_bus_number FROM schedule_master WHERE division_id = ? AND depot_id = ? and status = 1  ORDER BY sch_dep_time ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $division_id, $depot_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $allotted = '';
    $non_allotted = '';

    while ($row = $result->fetch_assoc()) {
        $sch_key_no = $row['sch_key_no'];
        $is_allotted = (
            $row['bus_number_1'] === $bus_number ||
            $row['bus_number_2'] === $bus_number ||
            $row['additional_bus_number'] === $bus_number
        );

        $label = $is_allotted ? "{$sch_key_no} (Allotted)" : $sch_key_no;
        $option = "<option value=\"{$sch_key_no}\">{$label}</option>";

        if ($is_allotted) {
            $allotted .= $option;
        } else {
            $non_allotted .= $option;
        }
    }

    echo json_encode([
        'status' => 'success',
        'options' => '<option value="">Select Schedule</option>' . $allotted . $non_allotted
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_schedule_details') {
    $sch_no = $_POST['schedule_key_no'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $todaydate = date('Y-m-d');
    $bus_number = $_POST['busnumber'];
    // Query 1: Check if vehicle is already on operation
    $query1 = "SELECT COUNT(*) AS vehicle_check 
           FROM sch_veh_out 
           WHERE vehicle_no = ? 
             AND schedule_status = 1";
    $stmt1 = $db->prepare($query1);
    $stmt1->bind_param("s", $bus_number);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $row1 = $result1->fetch_assoc();

    // Check vehicle status
    if ($row1['vehicle_check'] > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Selected vehicle is already on Operation. Please select a different vehicle.'
        ]);
        $stmt1->close();
        exit;
    }
    $stmt1->close();

    // Query 2: Check if schedule is already operated today
    $query2 = "SELECT COUNT(*) AS schedule_check 
           FROM sch_veh_out 
           WHERE division_id = ? 
             AND depot_id = ? 
             AND sch_no = ? 
             AND departed_date = ?";
    $stmt2 = $db->prepare($query2);
    $stmt2->bind_param("ssss", $division_id, $depot_id, $sch_no, $todaydate);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $row2 = $result2->fetch_assoc();

    // Check schedule status
    if ($row2['schedule_check'] > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Selected Schedule is already on Operation for today. Please select a different Schedule No.'
        ]);
        $stmt2->close();
        exit;
    }
    $stmt2->close();



    // Check if schedule already departed
    /*$queryforscheduleverify = "SELECT sch_no 
                           FROM sch_veh_out 
                           WHERE division_id = ? 
                             AND depot_id = ? 
                             AND sch_no = ? 
                             AND departed_date = ?";
    $stmt = $db->prepare($queryforscheduleverify);
    $stmt->bind_param("ssss", $division_id, $depot_id, $sch_no, $todaydate);
    $stmt->execute();
    $result = $stmt->get_result();

    // ✅ This will now reliably check
    if ($result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Selected Schedule is already on Operation on Todays date. Please select a different Schedule No.'
        ]);
        $stmt->close();
        exit;
    }
    $stmt->close();*/

    // Step 1 Query: Fetch rows from sch_veh_out with status 1 and 2
    $step1Query = "
        SELECT *
        FROM sch_veh_out
        WHERE sch_no = '$sch_no'
          AND division_id = $division_id
          AND depot_id = $depot_id
          AND schedule_status ='1'
        ORDER BY id DESC
    ";

    $step1Result = mysqli_query($db, $step1Query);
    $step1Data = mysqli_fetch_all($step1Result, MYSQLI_ASSOC);

    // Step 2 Query: Retrieve the schedule data from schedule_master
    $scheduleQuery = "
        SELECT *
        FROM schedule_master
        WHERE sch_key_no = '$sch_no'
          AND division_id = $division_id
          AND depot_id = $depot_id
    ";

    $scheduleResult = mysqli_query($db, $scheduleQuery);
    $scheduleData = mysqli_fetch_assoc($scheduleResult);

    if (!$scheduleData) {
        echo json_encode(null); // No schedule data found
        exit;
    }

    // Initialize finalData with scheduleData
    $data = $scheduleData;



    if (!empty($step1Data)) {
        // Process each row from step 1 data
        foreach ($step1Data as $row) {
            if ($row['schedule_status'] == 1 || $row['schedule_status'] == 2 || $row['schedule_status'] == 6 || $row['schedule_status'] == 7) {
                // Nullify bus numbers if they match

                // Nullify driver and conductor details if they match
                if ($scheduleData['driver_pf_1'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_1'] == $row['driver_1_pf']) {
                    $data['driver_pf_1'] = NULL;
                    $data['driver_token_1'] = NULL;
                    $data['driver_name_1'] = NULL;
                }
                if ($scheduleData['driver_pf_1'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_1'] == $row['driver_2_pf']) {
                    $data['driver_pf_1'] = NULL;
                    $data['driver_token_1'] = NULL;
                    $data['driver_name_1'] = NULL;
                }
                if ($scheduleData['driver_pf_1'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_1'] == $row['conductor_pf_no']) {
                    $data['driver_pf_1'] = NULL;
                    $data['driver_token_1'] = NULL;
                    $data['driver_name_1'] = NULL;
                }
                if ($scheduleData['driver_pf_2'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_2'] == $row['driver_1_pf']) {
                    $data['driver_pf_2'] = NULL;
                    $data['driver_token_2'] = NULL;
                    $data['driver_name_2'] = NULL;
                }
                if ($scheduleData['driver_pf_2'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_2'] == $row['driver_2_pf']) {
                    $data['driver_pf_2'] = NULL;
                    $data['driver_token_2'] = NULL;
                    $data['driver_name_2'] = NULL;
                }
                if ($scheduleData['driver_pf_2'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_2'] == $row['conductor_pf_no']) {
                    $data['driver_pf_2'] = NULL;
                    $data['driver_token_2'] = NULL;
                    $data['driver_name_2'] = NULL;
                }
                if ($scheduleData['driver_pf_3'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_3'] == $row['driver_1_pf']) {
                    $data['driver_pf_3'] = NULL;
                    $data['driver_token_3'] = NULL;
                    $data['driver_name_3'] = NULL;
                }
                if ($scheduleData['driver_pf_3'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_3'] == $row['driver_2_pf']) {
                    $data['driver_pf_3'] = NULL;
                    $data['driver_token_3'] = NULL;
                    $data['driver_name_3'] = NULL;
                }
                if ($scheduleData['driver_pf_3'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_3'] == $row['conductor_pf_no']) {
                    $data['driver_pf_3'] = NULL;
                    $data['driver_token_3'] = NULL;
                    $data['driver_name_3'] = NULL;
                }
                if ($scheduleData['driver_pf_4'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_4'] == $row['driver_1_pf']) {
                    $data['driver_pf_4'] = NULL;
                    $data['driver_token_4'] = NULL;
                    $data['driver_name_4'] = NULL;
                }
                if ($scheduleData['driver_pf_4'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_4'] == $row['driver_2_pf']) {
                    $data['driver_pf_4'] = NULL;
                    $data['driver_token_4'] = NULL;
                    $data['driver_name_4'] = NULL;
                }
                if ($scheduleData['driver_pf_4'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_4'] == $row['conductor_pf_no']) {
                    $data['driver_pf_4'] = NULL;
                    $data['driver_token_4'] = NULL;
                    $data['driver_name_4'] = NULL;
                }
                if ($scheduleData['driver_pf_5'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_5'] == $row['driver_1_pf']) {
                    $data['driver_pf_5'] = NULL;
                    $data['driver_token_5'] = NULL;
                    $data['driver_name_5'] = NULL;
                }
                if ($scheduleData['driver_pf_5'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_5'] == $row['driver_2_pf']) {
                    $data['driver_pf_5'] = NULL;
                    $data['driver_token_5'] = NULL;
                    $data['driver_name_5'] = NULL;
                }
                if ($scheduleData['driver_pf_5'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_5'] == $row['conductor_pf_no']) {
                    $data['driver_pf_5'] = NULL;
                    $data['driver_token_5'] = NULL;
                    $data['driver_name_5'] = NULL;
                }
                if ($scheduleData['driver_pf_6'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['driver_pf_6'] == $row['driver_1_pf']) {
                    $data['driver_pf_6'] = NULL;
                    $data['driver_token_6'] = NULL;
                    $data['driver_name_6'] = NULL;
                }
                if ($scheduleData['driver_pf_6'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['driver_pf_6'] == $row['driver_2_pf']) {
                    $data['driver_pf_6'] = NULL;
                    $data['driver_token_6'] = NULL;
                    $data['driver_name_6'] = NULL;
                }
                if ($scheduleData['driver_pf_6'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['driver_pf_6'] == $row['conductor_pf_no']) {
                    $data['driver_pf_6'] = NULL;
                    $data['driver_token_6'] = NULL;
                    $data['driver_name_6'] = NULL;
                }
                if ($scheduleData['conductor_pf_1'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['conductor_pf_1'] == $row['driver_1_pf']) {
                    $data['conductor_pf_1'] = NULL;
                    $data['conductor_token_1'] = NULL;
                    $data['conductor_name_1'] = NULL;
                }
                if ($scheduleData['conductor_pf_1'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['conductor_pf_1'] == $row['driver_2_pf']) {
                    $data['conductor_pf_1'] = NULL;
                    $data['conductor_token_1'] = NULL;
                    $data['conductor_name_1'] = NULL;
                }
                if ($scheduleData['conductor_pf_1'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['conductor_pf_1'] == $row['conductor_pf_no']) {
                    $data['conductor_pf_1'] = NULL;
                    $data['conductor_token_1'] = NULL;
                    $data['conductor_name_1'] = NULL;
                }
                if ($scheduleData['conductor_pf_2'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['conductor_pf_2'] == $row['driver_1_pf']) {
                    $data['conductor_pf_2'] = NULL;
                    $data['conductor_token_2'] = NULL;
                    $data['conductor_name_2'] = NULL;
                }
                if ($scheduleData['conductor_pf_2'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['conductor_pf_2'] == $row['driver_2_pf']) {
                    $data['conductor_pf_2'] = NULL;
                    $data['conductor_token_2'] = NULL;
                    $data['conductor_name_2'] = NULL;
                }
                if ($scheduleData['conductor_pf_2'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['conductor_pf_2'] == $row['conductor_pf_no']) {
                    $data['conductor_pf_2'] = NULL;
                    $data['conductor_token_2'] = NULL;
                    $data['conductor_name_2'] = NULL;
                }
                if ($scheduleData['conductor_pf_3'] !== NULL && $row['driver_1_pf'] !== NULL && $scheduleData['conductor_pf_3'] == $row['driver_1_pf']) {
                    $data['conductor_pf_3'] = NULL;
                    $data['conductor_token_3'] = NULL;
                    $data['conductor_name_3'] = NULL;
                }
                if ($scheduleData['conductor_pf_3'] !== NULL && $row['driver_2_pf'] !== NULL && $scheduleData['conductor_pf_3'] == $row['driver_2_pf']) {
                    $data['conductor_pf_3'] = NULL;
                    $data['conductor_token_3'] = NULL;
                    $data['conductor_name_3'] = NULL;
                }
                if ($scheduleData['conductor_pf_3'] !== NULL && $row['conductor_pf_no'] !== NULL && $scheduleData['conductor_pf_3'] == $row['conductor_pf_no']) {
                    $data['conductor_pf_3'] = NULL;
                    $data['conductor_token_3'] = NULL;
                    $data['conductor_name_3'] = NULL;
                }
            }
        }
    }



    echo json_encode($data);
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'vehicle_out_submit') {
    // Function to fetch data from API
    function fetchEmployeeData($pfNumber)
    {
        $division = $_SESSION['KMPL_DIVISION'];
        $depot = $_SESSION['KMPL_DEPOT'];

        // Fetch data from the first API based on division and depot
        $url = 'http://localhost:8880/dvp/includes/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);
        $response = file_get_contents($url);
        if ($response === FALSE) {
            die('Error occurred while fetching data from LMS API');
        }

        $data = json_decode($response, true);

        // Check if the data array is present and contains expected keys
        if (isset($data['data']) && is_array($data['data'])) {
            // Loop through the employee data to find the matching PF number
            foreach ($data['data'] as $employee) {
                if ($employee['EMP_PF_NUMBER'] === $pfNumber) {
                    $employee['deputed'] = 0; // Add deputed status as 0
                    return $employee;
                }
            }
        }

        // If the data is not found in the first API, call the second API
        $urlPrivate = 'http://localhost:8880/dvp/database/private_emp_api.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);
        $responsePrivate = file_get_contents($urlPrivate);
        if ($responsePrivate === FALSE) {
            die('Error occurred while fetching data from the private API');
        }

        $dataPrivate = json_decode($responsePrivate, true);

        // Check if the data array is present and contains expected keys
        if (isset($dataPrivate['data']) && is_array($dataPrivate['data'])) {
            // Loop through the employee data to find the matching PF number
            foreach ($dataPrivate['data'] as $employee) {
                if ($employee['EMP_PF_NUMBER'] === $pfNumber) {
                    $employee['deputed'] = 0; // Add deputed status as 0
                    return $employee;
                }
            }
        }

        // If the data is not found in the first two APIs, call the third API
        $urlDeputation = 'http://localhost:8880/dvp/database/deputation_crew_api1.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);
        $responseDeputation = file_get_contents($urlDeputation);
        if ($responseDeputation === FALSE) {
            die('Error occurred while fetching data from the deputation crew API');
        }

        $dataDeputation = json_decode($responseDeputation, true);

        // Check if the data array is present and contains expected keys
        if (isset($dataDeputation['data']) && is_array($dataDeputation['data'])) {
            // Loop through the employee data to find the matching PF number
            foreach ($dataDeputation['data'] as $employee) {
                if ($employee['EMP_PF_NUMBER'] === $pfNumber) {
                    $employee['deputed'] = 1; // Add deputed status as 1
                    return $employee;
                }
            }
        }

        // Return null if no employee is found in all three APIs
        return null;
    }

    // Escape input data
    $sch_no = mysqli_real_escape_string($db, $_POST['schedule_no_out']);
    $vehicle_no = mysqli_real_escape_string($db, $_POST['veh_no_out']);
    $driver_token_no_1 = mysqli_real_escape_string($db, $_POST['driver_token_no_1']);
    $driver_token_no_2 = isset($_POST['driver_token_no_2']) && !empty($_POST['driver_token_no_2']) ?
        mysqli_real_escape_string($db, $_POST['driver_token_no_2']) : null;
    $conductor_token_no = isset($_POST['conductor_token_no']) && !empty($_POST['conductor_token_no']) ?
        mysqli_real_escape_string($db, $_POST['conductor_token_no']) : null;
    $act_dep_time = mysqli_real_escape_string($db, $_POST['act_dep_time']);
    $time_diff = mysqli_real_escape_string($db, $_POST['time_diff']);
    $reason_for_late_departure = isset($_POST['reason_for_late_departure']) && !empty($_POST['reason_for_late_departure']) ?
        mysqli_real_escape_string($db, $_POST['reason_for_late_departure']) : null;
    $reason_early_departure = isset($_POST['reason_early_departure']) && !empty($_POST['reason_early_departure']) ?
        mysqli_real_escape_string($db, $_POST['reason_early_departure']) : null;

    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    // Check if schedule data already exists for today
    $today = date('Y-m-d');
    $checkQuery = "
SELECT COUNT(*) as count
FROM sch_veh_out
WHERE sch_no = '$sch_no'
AND division_id = '$division_id'
AND depot_id = '$depot_id'
AND departed_date = '$today'
";
    $checkResult = mysqli_query($db, $checkQuery);
    $checkData = mysqli_fetch_assoc($checkResult);

    if ($checkData['count'] > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'The schedule already departed.'
        ]);
        exit;
    }

    // Fetch schedule details using sch_no
    $fetchScheduleDetails = "SELECT * FROM schedule_master WHERE sch_key_no = '$sch_no' AND division_id = '$division_id' AND
depot_id = '$depot_id'";
    $scheduleDetailsResult = mysqli_query($db, $fetchScheduleDetails) or die(mysqli_error($db));
    $scheduleDetails = mysqli_fetch_assoc($scheduleDetailsResult);

    $schedule_status = 1;
    $d1deputedStatus = null;
    $d2deputedStatus = null;
    $cdeputedStatus = null;

    // Fetch driver and conductor data from API
    $driver1Data = fetchEmployeeData($driver_token_no_1);
    $driver2Data = !is_null($driver_token_no_2) ? fetchEmployeeData($driver_token_no_2) : null;
    $conductorData = !is_null($conductor_token_no) ? fetchEmployeeData($conductor_token_no) : null;
    // Ensure the API response contains the expected keys for driver 1
    if (isset($driver1Data['EMP_PF_NUMBER'], $driver1Data['EMP_NAME'], $driver1Data['token_number'])) {
        $driver1pfno = $driver1Data['EMP_PF_NUMBER'];
        $driver1name = $driver1Data['EMP_NAME'];
        $driver1token = $driver1Data['token_number'];
        $d1deputedStatus = $driver1Data['deputed']; // 0 or 1 based on API source

    } else {
        die('Error: API response does not contain the expected keys for driver 1.');
    }

    if (!is_null($driver_token_no_2)) {
        if (isset($driver2Data['EMP_PF_NUMBER'], $driver2Data['EMP_NAME'], $driver2Data['token_number'])) {
            $driver2pfno = $driver2Data['EMP_PF_NUMBER'];
            $driver2name = $driver2Data['EMP_NAME'];
            $driver2token = $driver2Data['token_number'];
            $d2deputedStatus = $driver2Data['deputed']; // 0 or 1 based on API source

        } else {
            die('Error: API response does not contain the expected keys for driver 2.');
        }
    }
    if (!is_null($conductor_token_no)) {
        if (isset($conductorData['EMP_PF_NUMBER'], $conductorData['EMP_NAME'], $conductorData['token_number'])) {
            $conductorpf = $conductorData['EMP_PF_NUMBER'];
            $conductorname = $conductorData['EMP_NAME'];
            $conductortoken = $conductorData['token_number'];
            $cdeputedStatus = $conductorData['deputed']; // 0 or 1 based on API source

        } else {
            die('Error: API response does not contain the expected keys for Conductor.');
        }
    }
    // Check if the vehicle number is allotted
    $busAllottedStatus = ($vehicle_no == $scheduleDetails['bus_number_1'] || $vehicle_no ==
        $scheduleDetails['bus_number_2']) ? 0 : 1;

    // Check if the driver tokens are allotted
    $driver1AllottedStatus = (
        $driver1Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_1'] ||
        $driver1Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_2'] ||
        $driver1Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_3'] ||
        $driver1Data['EMP_PF_NUMBER'] == $scheduleDetails['offreliverdriver_pf_1'] ||
        $driver1Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_4'] ||
        $driver1Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_5'] ||
        $driver1Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_6'] ||
        $driver1Data['EMP_PF_NUMBER'] == $scheduleDetails['offreliverdriver_pf_2']
    ) ? 0 : 1;

    $driver2AllottedStatus = is_null($driver2Data) ? null : (
        ($driver2Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_1'] ||
            $driver2Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_2'] ||
            $driver2Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_3'] ||
            $driver2Data['EMP_PF_NUMBER'] == $scheduleDetails['offreliverdriver_pf_1'] ||
            $driver2Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_4'] ||
            $driver2Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_5'] ||
            $driver2Data['EMP_PF_NUMBER'] == $scheduleDetails['driver_pf_6'] ||
            $driver2Data['EMP_PF_NUMBER'] == $scheduleDetails['offreliverdriver_pf_2']) ? 0 : 1
    );

    // Initialize conductorAllottedStatus
    $conductorAllottedStatus = null;
    $conductorpf = null;
    $conductorname = null;
    $conductortoken = null;

    if ($scheduleDetails['single_crew'] == 'yes') {
        $conductorAllottedStatus = null;
    } else {
        $conductorAllottedStatus = is_null($conductorData) ? null : (
            ($conductorData['EMP_PF_NUMBER'] == $scheduleDetails['conductor_pf_1'] ||
                $conductorData['EMP_PF_NUMBER'] == $scheduleDetails['conductor_pf_2'] ||
                $conductorData['EMP_PF_NUMBER'] == $scheduleDetails['conductor_pf_3'] ||
                $conductorData['EMP_PF_NUMBER'] == $scheduleDetails['offreliverconductor_pf_1']) ? 0 : 1
        );
        if ($conductorData) {
            $conductorpf = $conductorData['EMP_PF_NUMBER'];
            $conductorname = $conductorData['EMP_NAME'];
            $conductortoken = $conductorData['token_number'];
        }
    }

    // Set driver 2 details if present
    $driver2pfno = null;
    $driver2name = null;
    if ($driver2Data) {
        $driver2token = $driver2Data['token_number'];
        $driver2pfno = $driver2Data['EMP_PF_NUMBER'];
        $driver2name = $driver2Data['EMP_NAME'];
    }

    $todays_date = date('Y-m-d');

    // Check if the vehicle is deputed by querying the vehicle_deputation table
    $isdeputed = 0; // Default to 0 (not deputed)

    // Query to check if the bus is deputed for today's date
    $query = "
        SELECT 1 
        FROM vehicle_deputation 
        WHERE bus_number = '$vehicle_no'
          AND tr_date = '$todays_date'
          AND t_division_id = '$division_id'
          AND t_depot_id = '$depot_id'
          AND status = '2'
    ";

    // Execute the query
    $result = mysqli_query($db, $query);

    // If a record is found, set $isdeputed to 1 (deputed)
    if (mysqli_num_rows($result) > 0) {
        $isdeputed = 1;
    }
    // Insert into schedules table
    $insertQuery = "INSERT INTO sch_veh_out (sch_no, vehicle_no,v_deputed, driver_token_no_1, driver_token_no_2, dep_time,
dep_time_diff, reason_for_late_departure, reason_early_departure, bus_allotted_status, driver_1_allotted_status,
driver_2_allotted_status, conductor_alloted_status, schedule_status, division_id, depot_id, driver_1_pf, driver_1_name,
driver_2_pf, driver_2_name, conductor_token_no, conductor_pf_no, conductor_name, d1_deputed, d2_deputed, c_deputed)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($insertQuery);
    $stmt->bind_param(
        "sssssssssiiiiiiissssssssss",
        $sch_no,
        $vehicle_no,
        $isdeputed,
        $driver1token,
        $driver2token,
        $act_dep_time,
        $time_diff,
        $reason_for_late_departure,
        $reason_early_departure,
        $busAllottedStatus,
        $driver1AllottedStatus,
        $driver2AllottedStatus,
        $conductorAllottedStatus,
        $schedule_status,
        $division_id,
        $depot_id,
        $driver1pfno,
        $driver1name,
        $driver2pfno,
        $driver2name,
        $conductortoken,
        $conductorpf,
        $conductorname,
        $d1deputedStatus,
        $d2deputedStatus,
        $cdeputedStatus
    );

    if ($stmt->execute()) {
        // Return JSON success message
        echo json_encode([
            'status' => 'success',
            'message' => 'The schedule has been successfully departed.'
        ]);
    } else {
        // Return JSON error message
        echo json_encode([
            'status' => 'error',
            'message' => 'Error occurred: ' . $stmt->error
        ]);
    }


    $stmt->close();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update_model_type') {


    $bus_no = $_POST['bus_number'] ?? '';
    $model_type = $_POST['model_type'] ?? '';

    if (!$bus_no || !$model_type) {
        echo json_encode(['status' => 'error', 'message' => 'Bus number or model type missing']);
        exit;
    }
    if (empty($bus_no) || empty($model_type)) {
        echo json_encode(['status' => 'error', 'message' => 'Bus number or model type missing']);
        exit;
    }

    // Prepared statement to avoid SQL injection
    $stmt = $db->prepare("UPDATE bus_registration SET model_type = ? WHERE bus_number = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $db->error]);
        exit;
    }
    $stmt->bind_param("ss", $model_type, $bus_no);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Model type updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'fetch_comparison_report') {
    $date = $_POST['selected_date'];
    $formateddate = date('d-m-Y', strtotime($date));
    if ($_SESSION['TYPE'] == 'HEAD-OFFICE') {
        $conditionforreport = "l.division_id NOT IN (0, 10) and l.depot NOT IN ('DIVISION')";
    } else {
        $conditionforreport = "l.division_id = {$_SESSION['DIVISION_ID']} and l.depot NOT IN ('DIVISION')";
    }
    $query = "SELECT 
    l.division, 
    l.division_id, 
    l.depot, 
    l.depot_id,

    -- From bus_registration (buses held)
    IFNULL(br_data.buses_held, 0) AS buses_held,

    -- From logsheet (vehicle_kmpl)
    IFNULL(vk_data.logsheet_entry_count, 0) AS logsheet_entry_count,
    IFNULL(vk_data.logsheet_km, 0) AS logsheet_km,
    IFNULL(vk_data.logsheet_hsd, 0) AS logsheet_hsd,
    IFNULL(vk_data.logsheet_kmpl, 0) AS logsheet_kmpl,

    -- From kmpl_data (already depotwise)
    IFNULL(kd.total_km, 0) AS manual_km,
    IFNULL(kd.hsd, 0) AS manual_hsd,
    IFNULL(kd.kmpl, 0) AS manual_kmpl

FROM location l

-- 🔹 Join bus_registration to count buses held per depot
LEFT JOIN (
    SELECT 
        division_name AS division_id, 
        depot_name AS depot_id, 
        COUNT(DISTINCT bus_number) AS buses_held
    FROM bus_registration
    GROUP BY division_name, depot_name
) AS br_data
ON l.division_id = br_data.division_id AND l.depot_id = br_data.depot_id

-- 🔹 Join vehicle_kmpl for logsheet totals
LEFT JOIN (
    SELECT 
        division_id, 
        depot_id,
        COUNT(bus_number) AS logsheet_entry_count,
        SUM(km_operated) AS logsheet_km,
        SUM(hsd) AS logsheet_hsd,
        ROUND(SUM(km_operated)/NULLIF(SUM(hsd), 0), 2) AS logsheet_kmpl
    FROM vehicle_kmpl
    WHERE date = '$date' and deleted !=1
    GROUP BY division_id, depot_id
) AS vk_data
ON l.division_id = vk_data.division_id AND l.depot_id = vk_data.depot_id

-- 🔹 Join kmpl_data which is already depotwise
LEFT JOIN (
    SELECT 
        division AS division_id, 
        depot AS depot_id,
        total_km,
        hsd,
        kmpl
    FROM kmpl_data
    WHERE date = '$date'
) AS kd
ON l.division_id = kd.division_id AND l.depot_id = kd.depot_id

-- 🔹 Exclude unwanted divisions
WHERE $conditionforreport

ORDER BY l.division_id, l.depot_id

";



    echo "<style>
    .mismatch {
        background-color: #ffc7c7; /* light red for mismatches */
    }
</style>";

    $result = mysqli_query($db, $query);
    if (!$result) {
        echo "Error: " . mysqli_error($db);
        exit;
    }

    echo "<h3 class=\"text-center\">Depot-wise KMPL Comparison Report $formateddate</h3><table border='1' cellpadding='6' cellspacing='0'>
<tr>
    <th rowspan='2'>Division</th>
    <th rowspan='2'>Depot</th>
    <th rowspan='2'>Buses Held</th>
    <th rowspan='2'>Logsheet Entries</th>
    <th colspan='3'>Depot Manual</th>
    <th colspan='3'>Vehicle Logsheet</th>
    <th colspan='2'>Diff</th>
</tr>
<tr>
    <th>KM</th>
    <th>HSD</th>
    <th>KMPL</th>
    <th>KM</th>
    <th>HSD</th>
    <th>KMPL</th>
    <th>KM</th>
    <th>HSD</th>
</tr>";

    $current_division = '';
    $division_totals = [
        'buses_held' => 0,
        'logsheet_entry_count' => 0,
        'manual_km' => 0,
        'manual_hsd' => 0,
        'logsheet_km' => 0,
        'logsheet_hsd' => 0
    ];
    $corp_totals = $division_totals;

    while ($row = mysqli_fetch_assoc($result)) {
        // Detect new division and show total row
        if ($current_division !== '' && $current_division !== $row['division']) {
            echo "<tr style='font-weight: bold; background: #f0f0f0;'>
            <td colspan='2'>{$current_division} Total</td>
            <td>{$division_totals['buses_held']}</td>
            <td>{$division_totals['logsheet_entry_count']}</td>
            <td>{$division_totals['manual_km']}</td>
            <td>{$division_totals['manual_hsd']}</td>
            <td>" . round($division_totals['manual_km'] / max($division_totals['manual_hsd'], 1), 2) . "</td>
            <td>{$division_totals['logsheet_km']}</td>
            <td>{$division_totals['logsheet_hsd']}</td>
            <td>" . round($division_totals['logsheet_km'] / max($division_totals['logsheet_hsd'], 1), 2) . "</td>
            <td>$division_km_diff</td>
    <td>$division_hsd_diff</td>
        </tr>";

            $division_totals = array_fill_keys(array_keys($division_totals), 0);
        }

        $current_division = $row['division'];

        // Update running totals
        foreach (['buses_held', 'logsheet_entry_count', 'manual_km', 'manual_hsd', 'logsheet_km', 'logsheet_hsd'] as $key) {
            $division_totals[$key] += $row[$key];
            $corp_totals[$key] += $row[$key];
        }

        // Mismatch highlighting
        $km_class   = ($row['manual_km'] != $row['logsheet_km']) ? 'mismatch' : '';
        $hsd_class  = ($row['manual_hsd'] != $row['logsheet_hsd']) ? 'mismatch' : '';
        $kmpl_class = ($row['manual_kmpl'] != $row['logsheet_kmpl']) ? 'mismatch' : '';

        $km_diff = $row['manual_km'] - $row['logsheet_km'];
        $hsd_diff = $row['manual_hsd'] - $row['logsheet_hsd'];

        $diff_km_class  = ($km_diff != 0) ? 'mismatch' : '';
        $diff_hsd_class = ($hsd_diff != 0) ? 'mismatch' : '';

        $corp_km_diff = $corp_totals['manual_km'] - $corp_totals['logsheet_km'];
        $corp_hsd_diff = $corp_totals['manual_hsd'] - $corp_totals['logsheet_hsd'];

        $division_km_diff = $division_totals['manual_km'] - $division_totals['logsheet_km'];
        $division_hsd_diff = $division_totals['manual_hsd'] - $division_totals['logsheet_hsd'];

        echo "<tr>
        <td>{$row['division']}</td>
        <td>{$row['depot']}</td>
        <td>{$row['buses_held']}</td>
        <td>{$row['logsheet_entry_count']}</td>
        <td class='$km_class'>{$row['manual_km']}</td>
        <td class='$hsd_class'>{$row['manual_hsd']}</td>
        <td class='$kmpl_class'>{$row['manual_kmpl']}</td>
        <td class='$km_class'>{$row['logsheet_km']}</td>
        <td class='$hsd_class'>{$row['logsheet_hsd']}</td>
        <td class='$kmpl_class'>{$row['logsheet_kmpl']}</td>
        <td class='$diff_km_class'>$km_diff</td>
        <td class='$diff_hsd_class'>$hsd_diff</td>
    
    </tr>";
    }

    // Final division total
    if ($current_division !== '') {
        echo "<tr style='font-weight: bold; background: #f0f0f0;'>
        <td colspan='2'>{$current_division} Total</td>
        <td>{$division_totals['buses_held']}</td>
        <td>{$division_totals['logsheet_entry_count']}</td>
        <td>{$division_totals['manual_km']}</td>
        <td>{$division_totals['manual_hsd']}</td>
        <td>" . round($division_totals['manual_km'] / max($division_totals['manual_hsd'], 1), 2) . "</td>
        <td>{$division_totals['logsheet_km']}</td>
        <td>{$division_totals['logsheet_hsd']}</td>
        <td>" . round($division_totals['logsheet_km'] / max($division_totals['logsheet_hsd'], 1), 2) . "</td>
        <td>$division_km_diff</td>
    <td>$division_hsd_diff</td>
        
    </tr>";
    }

    // Final corporation total
    if ($_SESSION['TYPE'] == 'HEAD-OFFICE') {

        echo "<tr style='font-weight: bold; background: #d0ffd0;'>
    <td colspan='2'>Corporation Total</td>
    <td>{$corp_totals['buses_held']}</td>
    <td>{$corp_totals['logsheet_entry_count']}</td>
    <td>{$corp_totals['manual_km']}</td>
    <td>{$corp_totals['manual_hsd']}</td>
    <td>" . round($corp_totals['manual_km'] / max($corp_totals['manual_hsd'], 1), 2) . "</td>
    <td>{$corp_totals['logsheet_km']}</td>
    <td>{$corp_totals['logsheet_hsd']}</td>
    <td>" . round($corp_totals['logsheet_km'] / max($corp_totals['logsheet_hsd'], 1), 2) . "</td>
    <td>$corp_km_diff</td>
    <td>$corp_hsd_diff</td>

</tr>";
    }

    echo "</table>";
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'save_program_data') {
    $bus_number = $_POST['bus_number'] ?? '';
    $program_type = $_POST['program_type'] ?? '';
    $program_completed_km = $_POST['program_completed_km'] ?? '';
    $program_date = $_POST['program_date'] ?? '';
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    if (!$bus_number || !$program_type || !$program_completed_km || !$program_date) {
        echo "Missing required fields.";
        exit;
    }

    $program_completed_km = intval($program_completed_km);
    $program_date = mysqli_real_escape_string($db, $program_date);

    // before inserting, check if the program already exists for the bus
    $checkQuery = mysqli_query($db, "SELECT COUNT(*) as count FROM program_data 
        WHERE bus_number = '$bus_number' AND program_type = '$program_type' AND program_date = '$program_date' AND division_id = '$division_id' AND depot_id = '$depot_id'");

    //if it has data then update else insert
    $checkData = mysqli_fetch_assoc($checkQuery);
    // If the program already exists, update it else insert a new record
    if ($checkData['count'] > 0) {
        // Update existing record
        $update = mysqli_query($db, "UPDATE program_data SET program_completed_km = '$program_completed_km' 
            WHERE bus_number = '$bus_number' AND program_type = '$program_type' AND program_date = '$program_date'");

        echo $update ? "Program updated successfully." : "Error updating program.";
        exit;
    } else {

        // Insert only (no update)
        $insert = mysqli_query($db, "INSERT INTO program_data (bus_number, program_type, program_completed_km, program_date, division_id, depot_id) 
        VALUES ('$bus_number', '$program_type', '$program_completed_km', '$program_date', '$division_id', '$depot_id')");

        echo $insert ? "Program saved successfully." : "Error saving program.";
        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'fetch_report_of_defect_record') {

    $from = $_POST['from'];
    $to = $_POST['to'];
    $division_id = $_POST['division'];
    $depot_id = $_POST['depot'];
    $sch_no = $_POST['sch_no'];
    $bus_number = $_POST['bus_number'];
    $driver_pf = $_POST['driver_token'];

    // Get Division & Depot Names
    $locationQuery = "SELECT division, depot FROM location WHERE division_id = '$division_id' AND depot_id = '$depot_id'";
    $locationResult = mysqli_query($db, $locationQuery);
    $locationData = mysqli_fetch_assoc($locationResult);
    $divisionName = $locationData['division'] ?? 'Unknown';
    $depotName = $locationData['depot'] ?? 'Unknown';

    if (!$from || !$to || !$division_id || !$depot_id) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    function getDriverNameToken($pf_no)
    {
        if (!$pf_no || $pf_no == 'NA') return 'NA';

        $api_url = "http://localhost:8880/dvp_test/database/combined_api_data.php?pf_no=" . urlencode($pf_no);
        $response = @file_get_contents($api_url);

        if ($response === FALSE) return $pf_no; // Fallback

        $data = json_decode($response, true);

        if (!empty($data['data'][0])) {
            $name = $data['data'][0]['EMP_NAME'] ?? '';
            $token = $data['data'][0]['token_number'] ?? '';
            return $name . ' (' . $token . ')';
        }

        return $pf_no;
    }


    $where = "division_id = '$division_id' AND depot_id = '$depot_id' AND date BETWEEN '$from' AND '$to' AND deleted != 1";
    $activeFilter = '';
    $filterText = '';

    if (!empty($sch_no) && $sch_no === 'All') {
        $where .= " AND 1=1";
        $activeFilter = 'route number';
        $filterText = "Routes: All";
    } elseif (!empty($bus_number) && $bus_number === 'All') {
        $where .= " AND 1=1";
        $activeFilter = 'bus number';
        $filterText = "Buses: All";
    } elseif (!empty($driver_pf) && $driver_pf === 'All') {
        $where .= " AND 1=1";
        $activeFilter = 'driver name';
        $filterText = "Drivers: All";
    } elseif (!empty($sch_no) && $sch_no !== 'All') {
        $where .= " AND route_no = '$sch_no'";
        $activeFilter = 'route number';
        $filterText = "Route No: $sch_no";
    } elseif (!empty($bus_number) && $bus_number !== 'All') {
        $where .= " AND bus_number = '$bus_number'";
        $activeFilter = 'bus number';
        $filterText = "Bus Number: $bus_number";
    } elseif (!empty($driver_pf) && $driver_pf !== 'All') {
        $where .= " AND (driver_1_pf = '$driver_pf' OR driver_2_pf = '$driver_pf')";
        $activeFilter = 'driver name';
        $filterText = "Driver Name: " . getDriverNameToken($driver_pf);
    } else {
        $activeFilter = 'All';
        $filterText = "All Routes | All Buses | All Drivers";
    }

    $query = "SELECT route_no, bus_number, driver_1_pf, driver_2_pf, date, remarks 
          FROM vehicle_kmpl 
          WHERE $where 
          ORDER BY date, route_no, bus_number, driver_1_pf";

    $result = mysqli_query($db, $query);
    $sameDay = ($from === $to);

    $fromFormatted = date("d-m-Y", strtotime($from));
    $toFormatted = date("d-m-Y", strtotime($to));

    $html = "<h3 class='text-center'>Defect Record Report for $divisionName - $depotName</h3>";
    $html .= "<h4 class='text-center'>From: $fromFormatted To: $toFormatted</h4>";
    $html .= "<h4 class='text-center'>$filterText</h4><br>";

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        if ($sameDay) {
            $data[] = $row;
        } else {
            $key = '';
            if ($activeFilter === 'route number') $key = $row['route_no'] ?? 'Unknown';
            elseif ($activeFilter === 'bus number') $key = $row['bus_number'] ?? 'Unknown';
            elseif ($activeFilter === 'driver name') $key = $row['driver_1_pf'] ?? $row['driver_2_pf'] ?? 'Unknown';
            else $key = $row['route_no'] ?? 'Unknown'; // default to group by route
            $data[$key][] = $row;
        }
    }

    if (empty($data)) {
        $html .= "<p style='text-align:center; color:red;'>No defect remarks found in the given date range.</p>";
    } else {
        if ($sameDay) {
            // Single table view
            $html .= "<table border='1' cellpadding='6' cellspacing='0' style='width:100%; font-size: 12px; margin-bottom: 20px;'>";
            $html .= "<tr>
            <th>Date</th>
            <th>Route No</th>
            <th>Bus Number</th>
            <th>Driver 1 Name</th>
            <th>Driver 2 Name</th>
            <th>Defect Remarks</th>
        </tr>";
            foreach ($data as $entry) {
                $html .= "<tr>
                <td>" . date('d-m-Y', strtotime($entry['date'])) . "</td>
                <td>" . htmlspecialchars($entry['route_no'] ?? 'NA') . "</td>
                <td>" . htmlspecialchars($entry['bus_number'] ?? 'NA') . "</td>
                <td>" . getDriverNameToken($entry['driver_1_pf']) . "</td>
                <td>" . getDriverNameToken($entry['driver_2_pf']) . "</td>
                <td>" . nl2br(htmlspecialchars($entry['remarks'])) . "</td>
            </tr>";
            }
            $html .= "</table>";
        } else {
            if ($activeFilter === 'All') {
                // Determine if sch_no, bus_number, or driver_pf specifically has "All"
                if (!empty($sch_no) && $sch_no === 'All') {
                    // Group by Route No
                    foreach ($data as $route => $entries) {
                        $html .= "<h4>Route No: $route</h4>";
                        $html .= "<table border='1' cellpadding='6' cellspacing='0' style='width:100%; font-size: 12px; margin-bottom: 20px;'>";
                        $html .= "<tr>
                <th>Date</th>
                <th>Bus Number</th>
                <th>Driver 1 Name</th>
                <th>Driver 2 Name</th>
                <th>Defect Remarks</th>
            </tr>";
                        foreach ($entries as $entry) {
                            $html .= "<tr>
                    <td>" . date('d-m-Y', strtotime($entry['date'])) . "</td>
                    <td>" . htmlspecialchars($entry['bus_number'] ?? 'NA') . "</td>
                    <td>" . getDriverNameToken($entry['driver_1_pf']) . "</td>
                    <td>" . getDriverNameToken($entry['driver_2_pf']) . "</td>
                    <td>" . nl2br(htmlspecialchars($entry['remarks'])) . "</td>
                </tr>";
                        }
                        $html .= "</table>";
                    }
                }

                if (!empty($bus_number) && $bus_number === 'All') {
                    // Group by Bus Number
                    $busGroups = [];
                    foreach ($data as $entries) {
                        foreach ($entries as $row) {
                            $busGroups[$row['bus_number'] ?? 'Unknown'][] = $row;
                        }
                    }

                    foreach ($busGroups as $bus => $entries) {
                        $html .= "<h4>Bus Number: $bus</h4>";
                        $html .= "<table border='1' cellpadding='6' cellspacing='0' style='width:100%; font-size: 12px; margin-bottom: 20px;'>";
                        $html .= "<tr>
                <th>Date</th>
                <th>Route No</th>
                <th>Driver 1 Name</th>
                <th>Driver 2 Name</th>
                <th>Defect Remarks</th>
            </tr>";
                        foreach ($entries as $entry) {
                            $html .= "<tr>
                    <td>" . date('d-m-Y', strtotime($entry['date'])) . "</td>
                    <td>" . htmlspecialchars($entry['route_no'] ?? 'NA') . "</td>
                    <td>" . getDriverNameToken($entry['driver_1_pf']) . "</td>
                    <td>" . getDriverNameToken($entry['driver_2_pf']) . "</td>
                    <td>" . nl2br(htmlspecialchars($entry['remarks'])) . "</td>
                </tr>";
                        }
                        $html .= "</table>";
                    }
                }

                if (!empty($driver_pf) && $driver_pf === 'All') {
                    // Group by driver_1_pf and driver_2_pf
                    $driverGroups = [];
                    foreach ($data as $entries) {
                        foreach ($entries as $row) {
                            $d1 = $row['driver_1_pf'] ?: 'NA';
                            $d2 = $row['driver_2_pf'] ?: 'NA';
                            $driverGroups[$d1][] = $row;
                            if ($d1 !== $d2) $driverGroups[$d2][] = $row;
                        }
                    }

                    foreach ($driverGroups as $driver => $entries) {
                        $html .= "<h4>Driver PF: " . getDriverNameToken($driver) . "</h4>";
                        $html .= "<table border='1' cellpadding='6' cellspacing='0' style='width:100%; font-size: 12px; margin-bottom: 20px;'>";
                        $html .= "<tr>
                <th>Date1</th>
                <th>Route No</th>
                <th>Bus Number</th>
                <th>Defect Remarks</th>
            </tr>";
                        foreach ($entries as $entry) {
                            $html .= "<tr>
                    <td>" . date('d-m-Y', strtotime($entry['date'])) . "</td>
                    <td>" . htmlspecialchars($entry['route_no'] ?? 'NA') . "</td>
                    <td>" . htmlspecialchars($entry['bus_number'] ?? 'NA') . "</td>
                    <td>" . nl2br(htmlspecialchars($entry['remarks'])) . "</td>
                </tr>";
                        }
                        $html .= "</table>";
                    }
                }
            } else {
                // Existing filter logic
                foreach ($data as $group => $entries) {
                    $label = ucfirst($activeFilter);

                    if ($label == 'Driver name') {
                        $html .= "<h4>$label: " . getDriverNameToken($group) . "</h4>";
                    } else {
                        $html .= "<h4>$label: $group</h4>";
                    }
                    $html .= "<table border='1' cellpadding='6' cellspacing='0' style='width:100%; font-size: 12px; margin-bottom: 20px;'>";
                    $html .= "<tr>
                    <th>Date</th>";
                    if ($activeFilter !== 'route number') $html .= "<th>Route No</th>";
                    if ($activeFilter !== 'bus number') $html .= "<th>Bus Number</th>";
                    if ($activeFilter !== 'driver name') {
                        $html .= "<th>Driver 1 Name</th><th>Driver 2 Name</th>";
                    }
                    $html .= "<th>Defect Remarks</th></tr>";

                    foreach ($entries as $entry) {
                        $html .= "<tr>
                        <td>" . date('d-m-Y', strtotime($entry['date'])) . "</td>";
                        if ($activeFilter !== 'route number') $html .= "<td>" . htmlspecialchars($entry['route_no'] ?? 'NA') . "</td>";
                        if ($activeFilter !== 'bus number') $html .= "<td>" . htmlspecialchars($entry['bus_number'] ?? 'NA') . "</td>";
                        if ($activeFilter !== 'driver name') {
                            $html .= "<td>" . getDriverNameToken($entry['driver_1_pf']) . "</td>";
                            $html .= "<td>" . getDriverNameToken($entry['driver_2_pf']) . "</td>";
                        }
                        $html .= "<td>" . nl2br(htmlspecialchars($entry['remarks'])) . "</td></tr>";
                    }

                    $html .= "</table><br>";
                }
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $html
    ]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkBusOffroadinrwy') {
    $busNumber = mysqli_real_escape_string($db, $_POST['busNumber']);

    $query = "SELECT * FROM rwy_offroad WHERE bus_number = '$busNumber' AND status = 'off_road' LIMIT 1";
    $result = mysqli_query($db, $query);

    if (mysqli_num_rows($result) > 0) {
        echo 'offroad';
    } else {
        echo 'not_offroad';
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['w3_delete_id']) && isset($_POST['action']) && $_POST['action'] == 'delete_w3_single') {
    $id = $_POST['w3_delete_id'];

    if (!is_numeric($id)) {
        echo json_encode(["status" => "error", "message" => "Invalid ID provided"]);
        exit;
    }

    $deleteQuery = "UPDATE w3_chart_data SET deleted = '1' WHERE id = '$id'";
    $result = $db->query($deleteQuery);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "W3 Chart details deleted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed: " . $db->error]);
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "insertupdatesinglevehiclekmpl") {
    try {
        $id = $_POST['id'] ?? null;
        $bus_number = $_POST['bus_number'] ?? '';
        $operation_type = $_POST['operation_type'] ?? '';
        $reportdate = $_POST['reportDate'] ?? '';
        $division_id = $_POST['division_id'] ?? '';
        $depot_id = $_POST['depot_id'] ?? '';

        if (empty($bus_number) || empty($operation_type) || empty($reportdate) || empty($division_id) || empty($depot_id)) {
            echo json_encode(["status" => "error", "message" => "Required fields are missing"]);
            exit;
        }

        if (!empty($id)) {
            $stmt = $db->prepare("UPDATE w3_chart_data SET operation_type=? WHERE id=? AND bus_number=? and report_date=?");
            $stmt->bind_param("ssss", $operation_type, $id, $bus_number, $reportdate);
        } else {
            // If no ID is provided, insert a new record
            //before inserting, check if the record already exists check logsheet no for the report data is exisist then update else insert
            $checkStmt = $db->prepare("SELECT id FROM w3_chart_data WHERE bus_number=? AND report_date=? AND division_id=? AND depot_id=? and deleted != '1' LIMIT 1");
            $checkStmt->bind_param("ssss", $bus_number, $reportdate, $division_id, $depot_id);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                // Record exists, update it
                // fetch the id of the existing record
                $checkStmt->bind_result($existing_id);
                $checkStmt->fetch();
                $checkStmt->close();

                // Update the existing record

                $stmt = $db->prepare("UPDATE w3_chart_data SET operation_type=? WHERE id=?");
                $stmt->bind_param("ss", $operation_type, $existing_id);
            } else {
                // Record does not exist, insert a new one
                $stmt = $db->prepare("INSERT INTO w3_chart_data (bus_number, operation_type, report_date, division_id, depot_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $bus_number, $operation_type, $reportdate, $division_id, $depot_id);
            }
        }

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => !empty($id) ? "Record updated successfully" : "Record inserted successfully",
                "id" => $db->insert_id ?: $id
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Network Error"]);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Exception: " . $e->getMessage()]);
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "insertvehiclew3data") {
    $username = $_SESSION['USERNAME'] ?? '';

    date_default_timezone_set('Asia/Kolkata');
    $submitted_datetime = date('Y-m-d H:i:s');

    // Convert indexed POST keys back into an array
    $data = [];
    foreach ($_POST as $key => $value) {
        if (is_numeric($key) && is_array($value)) {
            $data[] = $value;
        }
    }

    if (!empty($data)) {
        $insertSuccess = true;
        $errorMessage = "";

        foreach ($data as $index => $row) {
            $id = $row['id'] ?? null;

            if (!empty($id)) {
                $updateQuery = "UPDATE w3_chart_data SET bus_number = ?, operation_type = ?, report_date = ?, division_id = ?, depot_id = ? WHERE id = ?";
                $stmt = $db->prepare($updateQuery);
                $stmt->bind_param(
                    "ssssss",
                    $row['bus_number'],
                    $row['operation_type'],
                    $row['report_date'],
                    $row['division_id'],
                    $row['depot_id'],
                    $id
                );

                if (!$stmt->execute()) {
                    $insertSuccess = false;
                    $errorMessage = "Failed to update row with ID " . $id;
                    break;
                }
            } else {
                // Check for existing record
                $checkQuery = "SELECT id FROM w3_chart_data WHERE bus_number = ? AND report_date = ? AND division_id = ? AND depot_id = ? AND deleted != '1' LIMIT 1";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bind_param("ssii", $row['bus_number'], $row['report_date'], $row['division_id'], $row['depot_id']);
                $checkStmt->execute();
                $checkStmt->store_result();

                $recordExists = $checkStmt->num_rows > 0;
                $existing_id = null;
                $checkStmt->bind_result($existing_id);
                $checkStmt->fetch();
                $checkStmt->close();

                if ($recordExists) {
                    $updateQuery = "UPDATE w3_chart_data SET bus_number = ?, operation_type = ?, division_id = ?, depot_id = ?, report_date = ? WHERE id = ?";
                    $stmt = $db->prepare($updateQuery);
                    $stmt->bind_param(
                        "sssssi",
                        $row['bus_number'],
                        $row['operation_type'],
                        $row['division_id'],
                        $row['depot_id'],
                        $row['report_date'],
                        $existing_id
                    );

                    if (!$stmt->execute()) {
                        $insertSuccess = false;
                        $errorMessage = "Failed to update existing row for bus " . $row['bus_number'];
                        break;
                    }
                } else {
                    $insertQuery = "INSERT INTO w3_chart_data (bus_number, operation_type, division_id, depot_id, report_date) 
                                    VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insertQuery);
                    $stmt->bind_param(
                        "sssss",
                        $row['bus_number'],
                        $row['operation_type'],
                        $row['division_id'],
                        $row['depot_id'],
                        $row['report_date']
                    );

                    if (!$stmt->execute()) {
                        $insertSuccess = false;
                        $errorMessage = "Failed to insert new row for bus " . $row['bus_number'] . ". Error: " . $stmt->error;
                        break;
                    }
                }
            }
        }

        if ($insertSuccess) {
            echo json_encode(["success" => true, "message" => "W3 Chart Data added/updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => $errorMessage]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "No data received."]);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "fetch_report_of_w3_from_to") {
    $from = $_POST['from'];
    $to = $_POST['to'];
    $division_id = $_POST['division'];
    $depot_id = $_POST['depot'];
    $bus_number = $_POST['bus_number'];

    if (!$from || !$to || !$division_id || !$depot_id) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }

    // Get depot/division names
    $locationQuery = "SELECT division, depot FROM location WHERE division_id = '$division_id' AND depot_id = '$depot_id'";
    $locationResult = mysqli_query($db, $locationQuery);
    $locationData = mysqli_fetch_assoc($locationResult);
    $divisionName = $locationData['division'] ?? 'Unknown';
    $depotName = $locationData['depot'] ?? 'Unknown';

    $sameDay = $from === $to;
    $busCondition = ($bus_number === 'All') ? "1=1" : "bus_number = '$bus_number'";
    $from_date = date('Y-m-d', strtotime($from . ' -1 day'));
    $kmpl_start_date = '2025-08-01';

    $air_suspension_bus_category_array = ['Rajahamsa', 'Corona Sleeper AC', 'Sleeper AC', 'Regular Sleeper Non AC', 'Amoghavarsha Sleeper Non AC', 'Kalyana Ratha'];

    $html = "<h3 class='text-center'>Annexure-H W3 Chart Report for $divisionName - $depotName</h3>";
    $html .= $sameDay
        ? "<h4 class='text-center'>Date: " . date('d-m-Y', strtotime($from)) . "</h4>"
        : "<h4 class='text-center'>From: " . date('d-m-Y', strtotime($from)) . " To: " . date('d-m-Y', strtotime($to)) . "</h4>";

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
        $html .= "<table border='1' cellspacing='0' cellpadding='5' width='100%' style='margin-bottom: 30px; text-align:center;'>
        <thead>
            <tr>
                <th>SL No</th>
                <th>Vehicle No</th>
                <th rowspan='2'>Program Target KMS</th>
                <th rowspan='2'>Cumm. program <br> kms as on {$formatted_from}</th>";

        foreach ($monthGroups as $monthYear => $dates) {
            $colspan = count($dates);
            $html .= "<th style='text-align:center;' colspan='{$colspan}'>$monthYear</th>";
        }
        $html .= "</tr>
            <tr>
                <td rowspan='2'><b>$slNo</b></td>
                <td rowspan='2'><b>{$vehicleNo}</b></td>";
        foreach ($monthGroups as $dates) {
            foreach ($dates as $dateObj) {
                $html .= "<th style='text-align:center;'>" . $dateObj->format('j') . "</th>";
            }
        }

        $html .= "</tr>
        </thead>
        <tbody>
            <tr>
                <td colspan='2' style='text-align:center;'><b>Program Name  &#8595;</b></td>
                <td colspan='2' style='text-align:center;'><b>Daily KMS --></b></td>";
        foreach ($monthGroups as $dates) {
            foreach ($dates as $dateObj) {
                $dateStr = $dateObj->format('Y-m-d');
                $value = 'NA';

                if (isset($dailyKmplData[$vehicleNo][$dateStr])) {
                    $value = $dailyKmplData[$vehicleNo][$dateStr];
                } elseif (isset($w3Data[$vehicleNo][$dateStr])) {
                    $value = $w3Data[$vehicleNo][$dateStr];
                }

                $html .= "<td>$value</td>";
            }
        }
        $html .= "</tr>";

        foreach ($programs as $prog) {
            $programName = strtolower(str_replace(' ', '_', $prog['name']));
            $progData = $programDataMap[$vehicleNo][$programName] ?? null;
            $program_date = $progData['program_date'] ?? null;
            $completed_km = (float)($progData['program_completed_km'] ?? 0);
            $kmData = $kmplMap[$vehicleNo] ?? [];
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

            $dailyCumm = calculateCumulativePerDay($initial_cumm_kms, $dailyKmplData[$vehicleNo] ?? [], $from, $to, $vehicleNo, $prog['realname']);


            $html .= "<tr>
        <td colspan='2' style='text-align:left;'>{$prog['name']}</td>
        <td>{$prog['value']} {$program_date}</td>
        <td>{$initial_cumm_kms}</td>";
            foreach ($monthGroups as $dates) {
                foreach ($dates as $dateObj) {
                    $dateStr = $dateObj->format('Y-m-d');

                    $data = $dailyCumm[$dateStr] ?? ['value' => 'NA', 'color' => 'default'];
                    $val  = $data['value'];
                    $color = $data['color'] ?? 'default';
                    $target  = (float) $prog['value'];
                    if (is_numeric($val)) {
                        if ($color === 'green') {
                            $html .= "<td style='background-color:green;'>$val</td>";
                        } else {
                            $numVal = (float) $val;

                            if ($numVal > $target + 500) {                // more than +500
                                $html .= "<td style='background-color:red;'>$val</td>";
                            } elseif (abs($numVal - $target) <= 500) {    // within ±500
                                $html .= "<td style='background-color:yellow;'>$val</td>";
                            } else {                                      // anything else
                                $html .= "<td>$val</td>";
                            }
                        }
                    } else {
                        $html .= "<td>$val</td>";
                    }
                }
            }


            $html .= "</tr>";
        }


        $html .= "</tbody></table>";
        $slNo++;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $html
    ]);
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "get_program_km_for_bus") {


    if (!isset($_POST['bus_number'], $_POST['program_type'], $_POST['program_date'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters.'
        ]);
        exit;
    }
    $bus_number = mysqli_real_escape_string($db, $_POST['bus_number']);
    $program_type = mysqli_real_escape_string($db, $_POST['program_type']);
    $selected_date = mysqli_real_escape_string($db, $_POST['program_date']); // expected format: Y-m-d
    $last_km = 0;
    $last_date = null;

    // Step 1: Get the last program entry (if any)
    $last_km_query = "
    SELECT program_completed_km, program_date 
    FROM program_data 
    WHERE bus_number = '$bus_number' 
    AND program_type = '$program_type' 
    ORDER BY program_date DESC 
    LIMIT 1
";
    $last_km_result = mysqli_query($db, $last_km_query);

    if ($row = mysqli_fetch_assoc($last_km_result)) {
        $last_km = (float)$row['program_completed_km'];
        $last_date = $row['program_date'];
    }

    // Step 2: Calculate KM from vehicle_kmpl
    $from_date = '2025-08-01';

    if ($last_date && ($last_date !== '0000-00-00' || $last_date !== null)) {
        $from_date = date('Y-m-d', strtotime($last_date . ' +1 day'));
    }

    $km_query = "
    SELECT SUM(km_operated) AS total_km 
    FROM vehicle_kmpl 
    WHERE bus_number = '$bus_number' 
    AND date >= '$from_date' 
    AND date <= '$selected_date'
    AND deleted != '1'
";
    $km_result = mysqli_query($db, $km_query);
    $total_km = 0;

    if ($km_row = mysqli_fetch_assoc($km_result)) {
        $total_km = (float)$km_row['total_km'];
    }

    $estimated_km = $last_km + $total_km;

    echo json_encode([
        'success' => true,
        'program_km' => round($estimated_km)
    ]);
}
