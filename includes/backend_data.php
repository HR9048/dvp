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

    $db->close();
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'crewdeputatuiondelete' || $_POST['action'] === 'crewdeputationreceive') {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'crewdeputationreceivefrom' || $_POST['action'] === 'crewdeputationrelease') {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'vehicledeputatuiondelete' || $_POST['action'] === 'vehicledeputationreceive') {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'vehicledeputationreceivefrom' || $_POST['action'] === 'vehicledeputationrelease') {
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
?>