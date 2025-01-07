<?php
require('../pages/session.php');
require_once '../includes/connection.php'; // Include the db.php file to access the database connection

confirm_logged_in();

function fetchDivision()
{
    global $db;
    $query = "SELECT DISTINCT DIVISION, division_id FROM location";
    $result = $db->query($query);
    $divisions = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $divisions[] = array(
                'DIVISION' => $row['DIVISION'],
                'division_id' => $row['division_id']
            );
        }
    }
    return $divisions;
}
function fetchDivision1()
{
    global $db;
    $query = "SELECT DISTINCT DIVISION FROM location";
    $result = $db->query($query);
    $Division = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $Division[] = $row['DIVISION'];
        }
    }
    return $Division;
}

function fetchDepot()
{
    global $db;
    if (isset($_POST['division']) && !empty($_POST['division'])) {
        // Sanitize the input to prevent SQL injection
        $Division = $_POST['division'];
        $query = "SELECT depot_id, DEPOT FROM location WHERE division_id = ?";

        // Prepare the statement
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $Division);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<option value='' disabled selected>Select Depot</option>";

        // Check if there are any results
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                if ($row['DEPOT'] !== 'DIVIS') {
                    // Output each option with depot_id as value and DEPOT as the visible text
                    echo "<option value='" . $row['depot_id'] . "'>" . $row['DEPOT'] . "</option>";
                }
            }
        } else {
            echo "<option value=''>No depot found</option>";
        }
    }
}
function fetchDepot1()
{
    global $db;
    if (isset($_POST['division']) && !empty($_POST['division'])) {
        // Sanitize the input to prevent SQL injection
        $Division = $_POST['division'];
        $query = "SELECT DEPOT,depot_id FROM location WHERE division = ?";

        // Prepare the statement
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $Division);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<option value='' disabled selected>Select Depot</option>";

        // Check if there are any results
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                if ($row['DEPOT'] !== 'DIVISION') {
                    // Output each option with depot_id as value and DEPOT as the visible text
                    echo "<option value='" . $row['depot_id'] . "'>" . $row['DEPOT'] . "</option>";
                }
            }
        } else {
            echo "<option value=''>No depot found</option>";
        }
    }
}
// Function to fetch makes
function fetchMakes()
{
    global $db;
    $query = "SELECT * FROM makes";
    $result = $db->query($query);
    $makes = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $makes[] = $row['make'];
        }
    }
    return $makes;
}

// Function to fetch emission norms
function fetchEmissionNorms()
{
    global $db;
    $query = "SELECT * FROM norms";
    $result = $db->query($query);
    $emissionNorms = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $emissionNorms[] = $row['emission_norms'];
        }
    }
    return $emissionNorms;

}

// Function to fetch wheel base
function fetchWheelBase()
{
    global $db;
    $query = "SELECT * FROM wheelbase";
    $result = $db->query($query);
    $wheelBase = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $wheelBase[] = $row['wheel_base'];
        }
    }
    return $wheelBase;
}

// Function to fetch body builder
function fetchBodyBuilder()
{
    global $db;
    $query = "SELECT * FROM bus_body_builder";
    $result = $db->query($query);
    $bodyBuilders = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bodyBuilders[] = $row['body_type'];
        }
    }
    return $bodyBuilders;
}
function fetchDesignation()
{
    global $db;
    $query = "SELECT JOB_TITLE FROM job";
    $result = $db->query($query);
    $Designation = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $Designation[] = $row['JOB_TITLE'];
        }
    }
    return $Designation;
}
function fetchBusCategory()
{
    global $db;
    $query = "SELECT DISTINCT bus_category FROM bus_seat_category";
    $result = $db->query($query);
    $busCategory = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $busCategory[] = $row['bus_category'];
        }
    }
    return $busCategory;
}

function fetchBusSubCategory()
{
    global $db;
    if (isset($_POST['bus_category']) && !empty($_POST['bus_category'])) {
        // Sanitize the input to prevent SQL injection
        $busCategory = $_POST['bus_category'];
        $query = "SELECT bus_sub_category FROM bus_seat_category WHERE bus_category = '$busCategory'";
        echo "<option value='' disabled selected>Select Bus Sub Category</option>";
        $result = $db->query($query);

        // Check if there are any results
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['bus_sub_category'] . "'>" . $row['bus_sub_category'] . "</option>";
            }
        } else {
            echo "<option value=''>No bus sub-categories found</option>";
        }
    }
}
function fetchOffroadLocation()
{
    global $db;
    $query = "SELECT DISTINCT location_id, location_name FROM off_road_location";
    $result = $db->query($query);
    $Location = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $Location[] = $row['location_name'];
        }
    }
    return $Location;
}
function fetchReason()
{
    global $db;
    if (isset($_POST['offRoadLocation']) && !empty($_POST['offRoadLocation'])) {
        // Sanitize the input to prevent SQL injection
        $Reason = $_POST['offRoadLocation'];
        $query = "SELECT r.reason_name
        FROM reason r
        INNER JOIN off_road_location o ON r.location_id = o.location_id
        WHERE o.location_name = '$Reason'";

        $result = $db->query($query);

        // Check if there are any results
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                // Check if the reason name is not null or empty
                if ($row['reason_name'] !== null && $row['reason_name'] !== '') {
                    // Echo each option as a checkbox wrapped in a div
                    echo '<div class="form-check my-1">';
                    echo '<input class="form-check-input" type="checkbox" name="partsRequired[]" value="' . $row['reason_name'] . '">';
                    echo '<label class="form-check-label d-block">' . $row['reason_name'] . '</label>';
                    echo '</div>';
                    echo '</div>';
                }
            }
        }
    }
}

function ServiceClass()
{
    global $db;
    $query = "SELECT id, name FROM service_class";
    $result = $db->query($query);
    $service = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $service[] = array(
                'id' => $row['id'],
                'name' => $row['name']
            );
        }
    }
    return $service;
}
function ScheduleType()
{
    global $db;
    $query = "SELECT id,type FROM schedule_type";
    $result = $db->query($query);
    $schedule = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schedule[] = array(
                'id' => $row['id'],
                'type' => $row['type']
            );
        }
    }
    return $schedule;
}
function cameradefecttype()
{
    global $db;
    $query = "SELECT id, defect_name FROM depot_camera_defect_type";
    $result = $db->query($query);
    $defect = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $defect[] = array(
                'id' => $row['id'],
                'defect_name' => $row['defect_name']
            );
        }
    }
    return $defect;
}

function fetchSchedule()
{
    global $db;
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $todays_date = date('Y-m-d');

    $query = "SELECT sm.sch_key_no 
    FROM schedule_master sm
    LEFT JOIN sch_veh_out svo
        ON sm.sch_key_no = svo.sch_no 
        AND svo.division_id = '$division_id' 
        AND svo.depot_id = '$depot_id' 
        AND svo.departed_date = '$todays_date'
    LEFT JOIN schedule_cancel sc
        ON sm.sch_key_no = sc.sch_key_no
        AND sm.division_id = sc.division_id
        AND sm.depot_id = sc.depot_id 
        AND sc.cancel_date = '$todays_date'
    WHERE sm.division_id = '$division_id' 
        AND sm.depot_id = '$depot_id'
        AND sm.status = '1'
        AND svo.sch_no IS NULL
        AND sc.sch_key_no IS NULL
    ORDER BY sm.sch_dep_time ASC";

    $result = $db->query($query);
    $schno = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schno[] = $row['sch_key_no'];
        }
    }
    return $schno;
}

function fetchScheduleIn()
{
    global $db;
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $query = "SELECT DISTINCT  svo.sch_no 
              FROM schedule_master sm
              INNER JOIN sch_veh_out svo
              ON sm.sch_key_no = svo.sch_no 
              AND svo.division_id = '$division_id' 
              AND svo.depot_id = '$depot_id' 
              AND svo.schedule_status = 1
              WHERE sm.division_id = '$division_id' 
              AND sm.depot_id = '$depot_id'
              ORDER BY sm.sch_arr_time ASC";

    $result = $db->query($query);
    $schno = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schno[] = $row['sch_no'];
        }
    }
    return $schno;
}
function driverdefecttype()
{
    global $db;
    $query = "SELECT id, defect_name FROM driver_defect";
    $result = $db->query($query);
    $defect = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $defect[] = array(
                'id' => $row['id'],
                'defect_name' => $row['defect_name']
            );
        }
    }
    return $defect;
}
function rampdefecttype()
{
    global $db;
    $query = "SELECT id, defect_name FROM ramp_defect";
    $result = $db->query($query);
    $defect = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $defect[] = array(
                'id' => $row['id'],
                'defect_name' => $row['defect_name']
            );
        }
    }
    return $defect;
}
// Handle fetch depots action
if (isset($_POST['action']) && $_POST['action'] === 'fetchDepots' && isset($_POST['divisionId']) && !empty($_POST['divisionId'])) {
    $divisionId = $_POST['divisionId']; // Fetch divisionId from POST

    // SQL query to fetch depots based on the division ID
    $sql = "SELECT depot_id, depot FROM location WHERE division_id = ? AND depot != 'DIVISION'";

    // Prepare and execute the query
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('i', $divisionId);  // Bind divisionId to the query
        $stmt->execute();
        $result = $stmt->get_result();

        $depots = [];
        while ($row = $result->fetch_assoc()) {
            $depots[] = $row;  // Collect depot data
        }

        if (empty($depots)) {
            echo json_encode(['error' => 'No depots found']);
        } else {
            echo json_encode($depots);  // Return depots as JSON
        }
    } else {
        echo json_encode(['error' => 'Database query failed']);
    }
}

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'fetchdivisioncrew') {

    $query = "SELECT DISTINCT kmpl_division FROM location where division_id not in ('0','10')";
    $result = mysqli_query($db, $query);

    $divisions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $divisions[] = ['division' => $row['kmpl_division']];
    }
    echo json_encode($divisions);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'fetchdepotcrew') {
    if (isset($_POST['division'])) {
        $depot_id = $_SESSION['DEPOT_ID'];
        $division = $_POST['division'];
        $query = "SELECT kmpl_depot FROM location WHERE kmpl_division = ? and depot != 'DIVISION' and depot_id != $depot_id"; // Filter by division name
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $division); // Bind the division name
        $stmt->execute();
        $result = $stmt->get_result();

        $depots = [];
        while ($row = $result->fetch_assoc()) {
            $depots[] = ['depot' => $row['kmpl_depot']];
        }
        echo json_encode($depots);
    } else {
        echo json_encode(['error' => 'Division is required']);
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
        $url = 'http://192.168.1.32:50/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);
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
        $urlPrivate = 'http://192.168.1.32/transfer/dvp/database/private_emp_api.php?division=' . urlencode($division) . '&depot=' .
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
        if (empty($vehicleNo) ||  empty($toDivision) || empty($toDepot) || empty($fromDate) || empty($toDate) || empty($lmsdivision) || empty($lmsdepot) || empty($division_id) || empty($depot_id)) {
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
// Check if an action is specified in the request
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Call the appropriate function based on the action
    switch ($action) {
        case 'fetchMakes':
            echo json_encode(fetchMakes());
            break;
        case 'fetchEmissionNorms':
            echo json_encode(fetchEmissionNorms());
            break;
        case 'fetchWheelBase':
            echo json_encode(fetchWheelBase());
            break;
        case 'fetchBodyBuilder':
            echo json_encode(fetchBodyBuilder());
            break;
        case 'fetchBusCategory':
            echo json_encode(fetchBusCategory());
            break;
        case 'fetchBusSubCategory':
            echo json_encode(fetchBusSubCategory());
            break;
        case 'fetchOffroadLocation':
            echo json_encode(fetchOffroadLocation());
            break;
        case 'fetchReason':
            echo json_encode(fetchReason());
            break;
        case 'fetchDivision':
            echo json_encode(fetchDivision());
            break;
        case 'fetchDepot':
            echo json_encode(fetchDepot());
            break;
        case 'fetchDesignation':
            echo json_encode(fetchDesignation());
            break;
        case 'fetchDepot1':
            echo json_encode(fetchDepot1());
            break;
        case 'fetchDivision1':
            echo json_encode(fetchDivision1());
            break;
        case 'ServiceClass':
            echo json_encode(ServiceClass());
            break;
        case 'ScheduleType':
            echo json_encode(ScheduleType());
            break;
        case 'cameradefecttype':
            echo json_encode(cameradefecttype());
            break;
        case 'fetchSchedule':
            echo json_encode(fetchSchedule());
            break;
        case 'fetchScheduleIn':
            echo json_encode(fetchScheduleIn());
            break;
        case 'driverdefecttype':
            echo json_encode(driverdefecttype());
            break;
        case 'rampdefecttype':
            echo json_encode(rampdefecttype());
            break;
        default:
            echo json_encode(array('error' => 'Invalid action'));
            break;
    }
}

?>