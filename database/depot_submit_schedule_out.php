<?php
include '../includes/connection.php';
include '../pages/session.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['act_dep_time1'])) {
    // Function to fetch data from API
    function fetchEmployeeData($pfNumber)
    {
        $division = $_SESSION['KMPL_DIVISION'];
        $depot = $_SESSION['KMPL_DEPOT'];

        // Fetch data from the first API based on division and depot
        $url = 'http://192.168.1.32:50/data1.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);
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
                    return $employee; // Return employee data if found in the first API
                }
            }
        }

        // If the data is not found in the first API, call the second API
        $urlPrivate = 'http://localhost/dvp/database/private_emp_api.php?division=' . urlencode($division) . '&depot=' .
            urlencode($depot);
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
                    return $employee; // Return employee data if found in the second API
                }
            }
        }

        // Return null if no employee is found in both APIs
        return null;
    }
    // Escape input data
    $sch_no = mysqli_real_escape_string($db, $_POST['sch_no']);
    $vehicle_no = mysqli_real_escape_string($db, $_POST['vehicle_no']);
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

    // Fetch driver and conductor data from API
    $driver1Data = fetchEmployeeData($driver_token_no_1);
    $driver2Data = !is_null($driver_token_no_2) ? fetchEmployeeData($driver_token_no_2) : null;
    $conductorData = !is_null($conductor_token_no) ? fetchEmployeeData($conductor_token_no) : null;
    // Ensure the API response contains the expected keys for driver 1
    if (isset($driver1Data['EMP_PF_NUMBER'], $driver1Data['EMP_NAME'], $driver1Data['token_number'])) {
        $driver1pfno = $driver1Data['EMP_PF_NUMBER'];
        $driver1name = $driver1Data['EMP_NAME'];
        $driver1token = $driver1Data['token_number'];
    } else {
        die('Error: API response does not contain the expected keys for driver 1.');
    }

    // Check if the vehicle number is allotted
    $busAllottedStatus = ($vehicle_no == $scheduleDetails['bus_number_1'] || $vehicle_no ==
        $scheduleDetails['bus_number_2']) ? 0 : 1;

    // Check if the driver tokens are allotted
    $driver1AllottedStatus = (
        $driver1Data['token_number'] == $scheduleDetails['driver_token_1'] ||
        $driver1Data['token_number'] == $scheduleDetails['driver_token_2'] ||
        $driver1Data['token_number'] == $scheduleDetails['driver_token_3'] ||
        $driver1Data['token_number'] == $scheduleDetails['half_releiver_token_1'] ||
        $driver1Data['token_number'] == $scheduleDetails['driver_token_4'] ||
        $driver1Data['token_number'] == $scheduleDetails['driver_token_5'] ||
        $driver1Data['token_number'] == $scheduleDetails['driver_token_6'] ||
        $driver1Data['token_number'] == $scheduleDetails['half_releiver_token_2']
    ) ? 0 : 1;

    $driver2AllottedStatus = is_null($driver2Data) ? null : (
        ($driver2Data['token_number'] == $scheduleDetails['driver_token_1'] ||
            $driver2Data['token_number'] == $scheduleDetails['driver_token_2'] ||
            $driver2Data['token_number'] == $scheduleDetails['driver_token_3'] ||
            $driver2Data['token_number'] == $scheduleDetails['half_releiver_token_1'] ||
            $driver2Data['token_number'] == $scheduleDetails['driver_token_4'] ||
            $driver2Data['token_number'] == $scheduleDetails['driver_token_5'] ||
            $driver2Data['token_number'] == $scheduleDetails['driver_token_6'] ||
            $driver2Data['token_number'] == $scheduleDetails['half_releiver_token_2']) ? 0 : 1
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
            ($conductorData['token_number'] == $scheduleDetails['conductor_token_1'] ||
                $conductorData['token_number'] == $scheduleDetails['conductor_token_2'] ||
                $conductorData['token_number'] == $scheduleDetails['conductor_token_3']) ? 0 : 1
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

    // Insert into schedules table
    $insertQuery = "INSERT INTO sch_veh_out (sch_no, vehicle_no, driver_token_no_1, driver_token_no_2, dep_time,
dep_time_diff, reason_for_late_departure, reason_early_departure, bus_allotted_status, driver_1_allotted_status,
driver_2_allotted_status, conductor_alloted_status, schedule_status, division_id, depot_id, driver_1_pf, driver_1_name,
driver_2_pf, driver_2_name, conductor_token_no, conductor_pf_no, conductor_name)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($insertQuery);
    $stmt->bind_param(
        "ssssssssiiiiiiisssssss",
        $sch_no,
        $vehicle_no,
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
        $conductorname
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
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: ../pages/login.php");
    exit;
}
?>