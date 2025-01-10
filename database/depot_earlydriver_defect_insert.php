<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data
    $id = $_POST['id1']; // Use null coalescing operator for safety
    $scheduleNo = $_POST['scheduleno1'];
    $logsheetNo = $_POST['logsheetNo1'];
    $needFuel = $_POST['needFuel'];
    $driverDefect = $_POST['driverDefect1'];
    $remark = $_POST['remark1'];
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $RkmOperated = $_POST['RkmOperated1'];
    $Rhsd = $_POST['Rhsd1'];
    $Rkmpl = $_POST['Rkmpl1'];
    $make = $_POST['make'];
    $norms = $_POST['norms'];
    $feedback = $_POST['feedback'] ?? '';
    // Validate the input fields
    $errors = [];

    if (empty($id))
        $errors[] = 'ID is required.';
    if (empty($scheduleNo))
        $errors[] = 'Schedule No is required.';
    if (empty($logsheetNo))
        $errors[] = 'Logsheet No is required.';
    if (empty($needFuel))
        $errors[] = 'Please select fuel reruired choice box.';
    if ($needFuel === 'yes') {
        if (empty($RkmOperated))
            $errors[] = 'KM Operated is required.';
        if (empty($Rhsd))
            $errors[] = 'HSD is required.';
    } 
    if ($make == 'Leyland' && $norms == 'BS-6') {
        if (empty($feedback))
            $errors[] = 'feed back is required.';
    }
    if (empty($driverDefect))
        $errors[] = 'Driver Defect is required.';

    // Check if remark is required
    if ($driverDefect !== '1' && empty($remark))
        $errors[] = 'Remark is required when Driver Defect is not 1.';

    // Return errors if any
    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit;
    }

    // If defect type is 1, set remark to NULL
    if ($driverDefect === '1') {
        $remark = null;
    }
    if ($make != 'Leyland' && $norms != 'BS-6') {
        $feedback = 0;
    }
    // Fetch details from sch_veh_out based on id
    $sqlFetch = "SELECT sch_no, driver_token_no_1, vehicle_no, driver_1_pf, driver_token_no_2, driver_2_pf, conductor_token_no, conductor_pf_no, departed_date, dep_time, arr_date, arr_time, schedule_status
                 FROM sch_veh_out
                 WHERE id = ? AND schedule_status in ('2','6') AND division_id = ? AND depot_id = ?";

    $stmtFetch = $db->prepare($sqlFetch);
    $stmtFetch->bind_param("iii", $id, $division_id, $depot_id);
    $stmtFetch->execute();
    $result = $stmtFetch->get_result();

    if ($result->num_rows === 0) {
        // No rows found
        echo json_encode(['status' => 'success', 'message' => 'Data already found']);
        $stmtFetch->close();
        exit;
    }

    // Fetch the data from the result
    $row = $result->fetch_assoc();
    $scheduleNo = $row['sch_no'];
    $driverToken1 = $row['driver_token_no_1'];
    $vehicleNo = $row['vehicle_no'];
    $driver1Pf = $row['driver_1_pf'];
    $driverToken2 = $row['driver_token_no_2'];
    $driver2Pf = $row['driver_2_pf'];
    $conductorToken = $row['conductor_token_no'];
    $conductorPf = $row['conductor_pf_no'];
    $departedDate = $row['departed_date'];
    $departedTime = $row['dep_time'];
    $arrDate = $row['arr_date'];
    $arrTime = $row['arr_time'];
    $status_ofsch = $row['schedule_status'];
    if($status_ofsch == 2){
        $set_status = '3';
    }elseif($status_ofsch == 6){
        $set_status = '7';
    }
    // Prepare and execute the SQL query to insert data into schedule_defect_data
    $sqlInsert = "INSERT INTO sch_veh_in 
                  (schedule_no, sch_out_id, logsheet_no,need_fuel, km_operated, hsd, kmpl, driver_defect, driver_remark, division_id, depot_id, feedback_id) 
                  VALUES 
                  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->bind_param(
        "ssssssssssss",
        $scheduleNo,
        $id,
        $logsheetNo,
        $needFuel,
        $RkmOperated,
        $Rhsd,
        $Rkmpl,
        $driverDefect,
        $remark,
        $division_id,
        $depot_id,
        $feedback
    );

    if ($stmtInsert->execute()) {
        // If the insert is successful, update the schedule_status in sch_veh_out
        $sqlUpdate = "UPDATE sch_veh_out SET schedule_status = ? WHERE id = ? AND division_id = ? AND depot_id = ?";
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->bind_param("iiii", $set_status, $id, $division_id, $depot_id);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmtInsert->error]);
    }


    $stmtFetch->close();
    $stmtInsert->close();
    $db->close();
} else {
    header('Location: ../pages/login.php');
    exit;
}
