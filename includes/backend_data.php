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
              $whereClause AND v.date = '$adjustedDate'
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
where l.division_id not in ('0', '10') and l.DEPOT != 'DIVISION'
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
                AND l.division_id = '$division_id'
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

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='progressive_km' id='progressive_km' class='form-control' required>
        </div>";

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

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='gear_box_progressive_km' id='gear_box_progressive_km' class='form-control' required>
        </div>";

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

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='fip_hpp_progressive_km' id='fip_hpp_progressive_km' class='form-control' required>
        </div>";

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

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='starter_progressive_km' id='starter_progressive_km' class='form-control' required>
        </div>";

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

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='alternator_progressive_km' id='alternator_progressive_km' class='form-control' required>
        </div>";

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

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='rear_axle_progressive_km' id='rear_axle_progressive_km' class='form-control' required>
        </div>";

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

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='battery_progressive_km' id='battery_progressive_km' class='form-control' required>
        </div>";

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
            <option value='1000x20 R'>1000x20 R</option>
            <option value='1000x20 N'>1000x20 N</option>
            <option value='295/R/22.5'>295/R/22.5</option>
            <option value='235/7.5 R'>235/7.5 R</option>
            </select>
            </div>";

        echo "<div class='mb-3'>
            <label class='form-label'>Tyre Brand/Pattern:</label>
            <select name='tyre_brand' id='tyre_brand' class='form-control' required>
            <option value=''>Select Tyre Brand/Pattern</option>
            <option value='WINMILE X3R'>WINMILE X3R</option>
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

        echo "<div class='mb-3'>
            <label class='form-label'>Progressive KM (As on 31.03.2025)</label>
            <input type='number' name='tyre_progressive_km' id='tyre_progressive_km' class='form-control' required>
        </div>";

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
    $progressive_km = isset($_POST['progressive_km']) ? (int) $_POST['progressive_km'] : 0;
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
    if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT engine_card_number, engine_number FROM engine_master WHERE engine_card_number = ? OR engine_number = ?";
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
    $progressive_km = isset($_POST['gear_box_progressive_km']) ? (int) $_POST['gear_box_progressive_km'] : 0;
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
    if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT gear_box_card_number, gear_box_number FROM gearbox_master WHERE gear_box_card_number = ? OR gear_box_number = ?";
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
    $progressive_km = isset($_POST['fip_hpp_progressive_km']) ? (int) $_POST['fip_hpp_progressive_km'] : 0;
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
    if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT fip_hpp_card_number, fip_hpp_number FROM fip_hpp_master WHERE fip_hpp_card_number = ? OR fip_hpp_number = ?";
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
    $progressive_km = isset($_POST['starter_progressive_km']) ? (int) $_POST['starter_progressive_km'] : 0;
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
    if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }


    $query = "SELECT starter_card_number, starter_number FROM starter_master WHERE starter_card_number = ? OR starter_number = ?";
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
    $progressive_km = isset($_POST['alternator_progressive_km']) ? (int) $_POST['alternator_progressive_km'] : 0;
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
    if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT alternator_card_number, alternator_number FROM alternator_master WHERE alternator_card_number = ? OR alternator_number = ?";
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
    $progressive_km = isset($_POST['rear_axle_progressive_km']) ? (int) $_POST['rear_axle_progressive_km'] : 0;
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
    if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT rear_axle_card_number, rear_axle_number FROM rear_axle_master WHERE rear_axle_card_number = ? OR rear_axle_number = ?";
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
    $progressive_km = isset($_POST['battery_progressive_km']) ? (int) $_POST['battery_progressive_km'] : 0;
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
    if ($progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT battery_card_number, battery_number FROM battery_master WHERE battery_card_number = ? OR battery_number = ?";
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
    $tyre_progressive_km = isset($_POST['tyre_progressive_km']) ? (int) $_POST['tyre_progressive_km'] : 0;
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
    if ($tyre_progressive_km < 0) {
        $errors[] = "Progressive KM cannot be negative.";
    }

    // If errors exist, return them as JSON response
    if (!empty($errors)) {
        echo json_encode(["status" => "error", "messages" => $errors]);
        exit;
    }

    $query = "SELECT tyre_card_number, tyre_number FROM tyre_master WHERE tyre_card_number = ? OR tyre_number = ?";
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
            "paging": true,       // Enable pagination
            "ordering": true,     // Enable sorting
            "searching": true,    // Enable search
            "info": true          // Show table info
        });
    });
</script>
<?php
    if ($category_type == 'engine') {
        //return the table of engine data
        //join the engine_master and engine_types table to get the engine type
        $query = "SELECT em.engine_card_number, em.engine_number, em.engine_make, em.engine_model, et.type as engine_type, em.engine_condition, em.progressive_km
                  FROM engine_master em
                  JOIN engine_types et ON em.engine_type_id = et.id
                  WHERE em.depot_id = ? AND em.division_id = ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $depot_id, $division_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        //create a table and assign the data to the table and return the table and for table add dataTable to sort the data
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
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        
    } else if ($category_type == 'gearbox') {
        //return the table of gearbox data
        //join the gearbox_master and gearbox_types table to get the gearbox type
        $query = "SELECT gm.gear_box_card_number, gm.gear_box_number, gm.gear_box_make, gm.gear_box_model, gt.type as gear_box_type, gm.gear_box_condition, gm.progressive_km
                  FROM gearbox_master gm
                  JOIN gearbox_types gt ON gm.gear_box_type_id = gt.id
                  WHERE gm.depot_id = ? AND gm.division_id = ?";
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
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else if ($category_type == 'fiphpp') {
        //return the table of fip/hpp data
        //join the fip_hpp_master and fip_types table to get the fip/hpp type
        $query = "SELECT fm.fip_hpp_card_number, fm.fip_hpp_number, fm.fip_hpp_make, fm.fip_hpp_model, ft.type as fip_hpp_type, fm.fip_hpp_condition, fm.progressive_km
                  FROM fip_hpp_master fm
                  JOIN fip_types ft ON fm.fip_hpp_type_id = ft.id
                  WHERE fm.depot_id = ? AND fm.division_id = ?";
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
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else if ($category_type == 'starter') {
        //return the table of starter data
        $query = "SELECT sm.starter_card_number, sm.starter_number, sm.starter_make, sm.starter_condition, sm.progressive_km
                  FROM starter_master sm
                  WHERE sm.depot_id = ? AND sm.division_id = ?";
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
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }else if ($category_type == 'alternator') {
        //return the table of alternator data
        $query = "SELECT am.alternator_card_number, am.alternator_number, am.alternator_make, am.alternator_condition, am.progressive_km
                  FROM alternator_master am
                  WHERE am.depot_id = ? AND am.division_id = ?";
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
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }else if ($category_type == 'rear_axle') {
        //return the table of rear axle data
        $query = "SELECT ram.rear_axle_card_number, ram.rear_axle_number, ram.rear_axle_make, ram.rear_axle_condition, ram.progressive_km
                  FROM rear_axle_master ram
                  WHERE ram.depot_id = ? AND ram.division_id = ?";
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
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }elseif ($category_type == 'battery') {
        //return the table of battery data
        $query = "SELECT bm.battery_card_number, bm.battery_number, bm.battery_make, bm.progressive_km
                  FROM battery_master bm
                  WHERE bm.depot_id = ? AND bm.division_id = ?";
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
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['battery_card_number'] . "</td>";
            echo "<td>" . $row['battery_number'] . "</td>";
            echo "<td>" . $row['battery_make'] . "</td>";
            echo "<td>" . $row['progressive_km'] . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }elseif ($category_type == 'tyre') {
        //return the table of tyre data
        $query = "SELECT tm.tyre_card_number, tm.tyre_number, tm.tyre_make, tm.tyre_size, tm.tyre_brand, tm.tyre_condition, tm.progressive_km
                  FROM tyre_master tm
                  WHERE tm.depot_id = ? AND tm.division_id = ?";
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
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }
}
