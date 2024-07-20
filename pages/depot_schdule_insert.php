<?php
include '../includes/connection.php';
include 'session.php';

// Validate and sanitize input data
$division_id = $_SESSION['DIVISION_ID'];
$depot_id = $_SESSION['DEPOT_ID'];
$sch_key_no = isset($_POST['sch_key_no']) ? $_POST['sch_key_no'] : null;
$sch_abbr = isset($_POST['sch_abbr']) ? $_POST['sch_abbr'] : null;
$sch_km = isset($_POST['sch_km']) ? floatval($_POST['sch_km']) : null; // Ensure KM is a float
$sch_dep_time = isset($_POST['sch_dep_time']) ? $_POST['sch_dep_time'] : null;
$sch_arr_time = isset($_POST['sch_arr_time']) ? $_POST['sch_arr_time'] : null;
$service_class_id = isset($_POST['service_class_id']) ? intval($_POST['service_class_id']) : null; // Ensure ID is an integer
$service_type_id = isset($_POST['service_type_id']) ? intval($_POST['service_type_id']) : null; // Ensure ID is an integer
$sch_count = isset($_POST['sch_count']) ? intval($_POST['sch_count']) : null; // Ensure count is an integer
$bus_number = isset($_POST['bus_number']) ? $_POST['bus_number'] : null;
$driver_token_1 = isset($_POST['driver_token_1']) ? $_POST['driver_token_1'] : null;
$driver_token_2 = isset($_POST['driver_token_2']) ? $_POST['driver_token_2'] : null;
$pf_no_d1 = isset($_POST['pf_no_d1']) ? $_POST['pf_no_d1'] : null;
$pf_no_d2 = isset($_POST['pf_no_d2']) ? $_POST['pf_no_d2'] : null;
$emp_name1 = isset($_POST['driver_1_name']) ? $_POST['driver_1_name'] : null;
$emp_name2 = isset($_POST['driver_2_name']) ? $_POST['driver_2_name'] : null;

// Check required fields
if ($sch_key_no === null || $sch_abbr === null || $sch_dep_time === null || $sch_arr_time === null || $driver_token_1 === null || $pf_no_d1 === null || $emp_name1 === null) {
    echo "Error: Required fields are missing.";
    exit;
}

// Determine if there are two drivers
$has_second_driver = $driver_token_2 !== null && $pf_no_d2 !== null && $emp_name2 !== null;

// Prepare SQL based on the number of drivers
if ($has_second_driver) {
    $stmt = $db->prepare("INSERT INTO schedule_key_master (division_id, depot_id, sch_key_no, sch_abbr, sch_km, sch_dep_time, sch_arr_time, service_class_id, service_type_id, sch_count, bus_number, driver_token_1, driver_token_2, pf_no_d1, pf_no_d2, emp_name1, emp_name2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($db->error));
    }

    $bind = $stmt->bind_param("iissdssiiisssssss", $division_id, $depot_id, $sch_key_no, $sch_abbr, $sch_km, $sch_dep_time, $sch_arr_time, $service_class_id, $service_type_id, $sch_count, $bus_number, $driver_token_1, $driver_token_2, $pf_no_d1, $pf_no_d2, $emp_name1, $emp_name2);
    if ($bind === false) {
        die('Bind failed: ' . htmlspecialchars($stmt->error));
    }
} else {
    $stmt = $db->prepare("INSERT INTO schedule_key_master (division_id, depot_id, sch_key_no, sch_abbr, sch_km, sch_dep_time, sch_arr_time, service_class_id, service_type_id, sch_count, bus_number, driver_token_1, pf_no_d1, emp_name1) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($db->error));
    }

    $bind = $stmt->bind_param("iissdssiiissss", $division_id, $depot_id, $sch_key_no, $sch_abbr, $sch_km, $sch_dep_time, $sch_arr_time, $service_class_id, $service_type_id, $sch_count, $bus_number, $driver_token_1, $pf_no_d1, $emp_name1);
    if ($bind === false) {
        die('Bind failed: ' . htmlspecialchars($stmt->error));
    }
}

// Execute the statement
if ($stmt->execute()) {
    echo "New record created successfully";
} else {
    echo "Error: " . htmlspecialchars($stmt->error);
}

// Close the statement and connection
$stmt->close();
$db->close();
?>
