<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header("Content-Type: application/json");

// Database connection details
$host = "localhost";
$user = "root";
$pass = "kkrtcsystem";
$port = 33306;

// Connect to `lms2021` for `employee` table
$db1 = new mysqli($host, $user, $pass, "lms2021", $port);
if ($db1->connect_error) {
    die(json_encode(["message" => "Database1 Connection failed: " . $db1->connect_error]));
}

// Connect to `kkrtcdvp_data` for `private_employee` and `crew_deputation` tables
$db2 = new mysqli($host, $user, $pass, "kkrtcdvp_data", $port);
if ($db2->connect_error) {
    die(json_encode(["message" => "Database2 Connection failed: " . $db2->connect_error]));
}

// Check if `pf_no` is set
if (!isset($_GET['pf_no'])) {
    echo json_encode(["message" => "PF number required"]);
    exit;
}

$pf_no = $_GET['pf_no'];
$prev_date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");

// Fetch data from `employee` (lms2021 database)
$sql1 = "SELECT EMP_PF_NUMBER, EMP_NAME, token_number, EMP_DESGN_AT_APPOINTMENT, Division, Depot
         FROM employee WHERE EMP_PF_NUMBER = ?";
$stmt1 = $db1->prepare($sql1);
$stmt1->bind_param("s", $pf_no);
$stmt1->execute();
$result1 = $stmt1->get_result();
$data1 = $result1->fetch_all(MYSQLI_ASSOC);
$stmt1->close();

// Fetch data from `private_employee` (kkrtcdvp_data database)
$sql2 = "SELECT EMP_PF_NUMBER, EMP_NAME, token_number, EMP_DESGN_AT_APPOINTMENT, Division, Depot
         FROM private_employee WHERE EMP_PF_NUMBER = ? AND status = '1'";
$stmt2 = $db2->prepare($sql2);
$stmt2->bind_param("s", $pf_no);
$stmt2->execute();
$result2 = $stmt2->get_result();
$data2 = $result2->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Fetch data from `crew_deputation` (kkrtcdvp_data database) for previous day
$sql3 = "SELECT EMP_PF_NUMBER, EMP_NAME, token_number, EMP_DESGN_AT_APPOINTMENT, t_Division AS Division, t_Depot AS Depot
         FROM crew_deputation WHERE EMP_PF_NUMBER = ? AND tr_date = ? AND deleted = '0'";
$stmt3 = $db2->prepare($sql3);
$stmt3->bind_param("ss", $pf_no, $prev_date);
$stmt3->execute();
$result3 = $stmt3->get_result();
$data3 = $result3->fetch_all(MYSQLI_ASSOC);
$stmt3->close();

// Combine all data
$merged_data = array_merge($data1, $data2, $data3);

// Return JSON response
echo json_encode(["data" => $merged_data]);

// Close database connections
$db1->close();
$db2->close();
?>
