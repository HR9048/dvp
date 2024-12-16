<?php
include '../pages/session.php';
// Check if the request is authorized
if (!isset($_SESSION['USERNAME'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Include database connection
require '../INCLUDES/connection.php'; // Replace with your actual DB connection script

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data with null coalescing to handle missing inputs
    function fetchEmployeeData($pfNumber)
    {
        $division = $_SESSION['KMPL_DIVISION'];
        $depot = $_SESSION['KMPL_DEPOT'];

        // Fetch data from the first API based on division and depot
        $url = 'http://192.168.1.32:50/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);
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
        $urlPrivate = 'http://192.168.1.32/transfer/dvp/database/private_emp_api.php?division=' . urlencode($division) . '&depot=' .
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
    $sch_out_id = $_POST['id'] ?? null;
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $arr_time = $_POST['arr_time'] ?? null;
    $reason = $_POST['reason'] ?? null;
    $other_reason = $_POST['otherreason'] ?? null;



    $change_vehicle = isset($_POST['change_vehicle']) ? 1 : 0;
    $change_driver = isset($_POST['change_driver']) ? 1 : 0;
    $change_driver2 = isset($_POST['change_driver2']) ? 1 : 0;
    $change_conductor = isset($_POST['change_conductor']) ? 1 : 0;

    // Initialize all vehicle and crew details to null
    $present_vehicle_no = null;
    $present_driver_1_pf_no = null;
    $present_driver_2_pf_no = null;
    $present_conductor_pf_no = null;

    $changed_vehicle_no = null;
    $changed_driver_1_pf_no = null;
    $changed_driver_2_pf_no = null;
    $changed_conductor_pf_no = null;

    // Assign data if the corresponding checkbox is checked
    if ($change_vehicle == 1) {
        $present_vehicle_no = $_POST['vehicle_no'] ?? null;
        $changed_vehicle_no = $_POST['bus_select'] ?? null;
    }
    if ($change_driver == 1) {
        $present_driver_1_pf_no = $_POST['driver_1_pf'];
        $Pdriver1Data = fetchEmployeeData($present_driver_1_pf_no);
        if (isset($Pdriver1Data['EMP_PF_NUMBER'], $Pdriver1Data['EMP_NAME'], $Pdriver1Data['token_number'])) {
            $pdriver1pfno = $Pdriver1Data['EMP_PF_NUMBER'];
            $pdriver1name = $Pdriver1Data['EMP_NAME'];
            $pdriver1token = $Pdriver1Data['token_number'];
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'API response does not contain the expected keys for present driver 1.'
            ]);
            exit;
        }
        $changed_driver_1_pf_no = $_POST['driver_1_select'];
        $Cdriver1Data = fetchEmployeeData($changed_driver_1_pf_no);
        if (isset($Cdriver1Data['EMP_PF_NUMBER'], $Cdriver1Data['EMP_NAME'], $Cdriver1Data['token_number'])) {
            $cdriver1pfno = $Cdriver1Data['EMP_PF_NUMBER'];
            $cdriver1name = $Cdriver1Data['EMP_NAME'];
            $cdriver1token = $Cdriver1Data['token_number'];
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: API response does not contain the expected keys for changed driver 1.'
            ]);
            exit;
        }
    } else {
        $pdriver1pfno = null;
        $pdriver1name = null;
        $pdriver1token = null;
        $cdriver1pfno = null;
        $cdriver1name = null;
        $cdriver1token = null;
    }
    if ($change_driver2 == 1) {
        $present_driver_2_pf_no = $_POST['driver_2_pf'];
        $Pdriver2Data = fetchEmployeeData($present_driver_2_pf_no);
        if (isset($Pdriver2Data['EMP_PF_NUMBER'], $Pdriver2Data['EMP_NAME'], $Pdriver2Data['token_number'])) {
            $pdriver2pfno = $Pdriver2Data['EMP_PF_NUMBER'];
            $pdriver2name = $Pdriver2Data['EMP_NAME'];
            $pdriver2token = $Pdriver2Data['token_number'];
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: API response does not contain the expected keys for present driver 2.'
            ]);
            exit;
        }
        $changed_driver_2_pf_no = $_POST['driver_2_select'];
        $Cdriver2Data = fetchEmployeeData($changed_driver_2_pf_no);
        if (isset($Cdriver2Data['EMP_PF_NUMBER'], $Cdriver2Data['EMP_NAME'], $Cdriver2Data['token_number'])) {
            $cdriver2pfno = $Cdriver2Data['EMP_PF_NUMBER'];
            $cdriver2name = $Cdriver2Data['EMP_NAME'];
            $cdriver2token = $Cdriver2Data['token_number'];
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: API response does not contain the expected keys for changed driver 2.'
            ]);
            exit;
        }
    } else {
        $pdriver2pfno = null;
        $pdriver2name = null;
        $pdriver2token = null;
        $cdriver2pfno = null;
        $cdriver2name = null;
        $cdriver2token = null;
    }
    if ($change_conductor == 1) {
        $present_conductor_pf_no = $_POST['conductor_pf_no'];
        $PconductorData = fetchEmployeeData($present_conductor_pf_no);
        if (isset($PconductorData['EMP_PF_NUMBER'], $PconductorData['EMP_NAME'], $PconductorData['token_number'])) {
            $pconductorpfno = $PconductorData['EMP_PF_NUMBER'];
            $pconductorname = $PconductorData['EMP_NAME'];
            $pconductortoken = $PconductorData['token_number'];
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: API response does not contain the expected keys for present Conductor.'
            ]);
            exit;
        }
        $changed_conductor_pf_no = $_POST['conductorselect'];
        $CconductorData = fetchEmployeeData($changed_conductor_pf_no);
        if (isset($CconductorData['EMP_PF_NUMBER'], $CconductorData['EMP_NAME'], $CconductorData['token_number'])) {
            $cconductorpfno = $CconductorData['EMP_PF_NUMBER'];
            $cconductorname = $CconductorData['EMP_NAME'];
            $cconductortoken = $CconductorData['token_number'];
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: API response does not contain the expected keys for changed Conductor.'
            ]);
            exit;
        }
    } else {
        $pconductorpfno = null;
        $pconductorname = null;
        $pconductortoken = null;
        $cconductorpfno = null;
        $cconductorname = null;
        $cconductortoken = null;
    }

    $missing_fields = [];

    if (!$sch_out_id) {
        $missing_fields[] = 'Schedule Out ID';
    }
    if (!$division_id) {
        $missing_fields[] = 'Division ID';
    }
    if (!$depot_id) {
        $missing_fields[] = 'Depot ID';
    }
    if (!$arr_time) {
        $missing_fields[] = 'Arrival Time';
    }
    if (!$reason) {
        $missing_fields[] = 'Reason';
    }

    if (!empty($missing_fields)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'The following fields are missing: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }


    // If "Other" reason is selected, ensure other_reason is provided
    if ($reason == 'Others' && !$other_reason) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide details for other reason.']);
        exit;
    }

    // Final reason handling
    $final_reason = $reason === 'Others' ? $other_reason : $reason;

    // Prepare the SQL query
    $query = "
    INSERT INTO sch_change_data (
        sch_out_id, division_id, depot_id, 
        present_vehicle_no, changed_vehicle_no, 
        present_driver_1_name, present_driver_1_token_no, present_driver_1_pf_no,
        changed_driver_1_name, changed_driver_1_token_no, changed_driver_1_pf_no,
        present_driver_2_name, present_driver_2_token_no, present_driver_2_pf_no,
        changed_driver_2_name, changed_driver_2_token_no, changed_driver_2_pf_no,
        present_conductor_name, present_conductor_token_no, present_conductor_pf_no,
        changed_conductor_name, changed_conductor_token_no, changed_conductor_pf_no,
        change_reason, changed_time, other_reason
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

    // Prepare and bind the statement
    if ($stmt = $db->prepare($query)) {
        $stmt->bind_param(
            'ssssssssssssssssssssssssss',
            $sch_out_id,
            $division_id,
            $depot_id,
            $present_vehicle_no,
            $changed_vehicle_no,
            $pdriver1name,
            $pdriver1token,
            $pdriver1pfno,
            $cdriver1name,
            $cdriver1token,
            $cdriver1pfno,
            $pdriver2name,
            $pdriver2token,
            $pdriver2pfno,
            $cdriver2name,
            $cdriver2token,
            $cdriver2pfno,
            $pconductorname,
            $pconductortoken,
            $pconductorpfno,
            $cconductorname,
            $cconductortoken,
            $cconductorpfno,
            $reason,
            $arr_time,
            $other_reason
        );

        // Execute the query
        // Execute the query
        if ($stmt->execute()) {
            // Prepare to update the sch_veh_out table based on the changes
            $updateFields = [];
            $updateParams = [];
            $updateTypes = '';

            if ($change_vehicle == 1) {
                $updateFields[] = "vehicle_no = ?";
                $updateParams[] = $changed_vehicle_no;
                $updateTypes .= 's';
            }

            if ($change_driver == 1) {
                $updateFields[] = "driver_1_name = ?";
                $updateFields[] = "driver_token_no_1 = ?";
                $updateFields[] = "driver_1_pf = ?";
                array_push($updateParams, $cdriver1name, $cdriver1token, $cdriver1pfno);
                $updateTypes .= 'sss';
            }

            if ($change_driver2 == 1) {
                $updateFields[] = "driver_2_name = ?";
                $updateFields[] = "driver_token_no_2 = ?";
                $updateFields[] = "driver_2_pf = ?";
                array_push($updateParams, $cdriver2name, $cdriver2token, $cdriver2pfno);
                $updateTypes .= 'sss';
            }

            if ($change_conductor == 1) {
                $updateFields[] = "conductor_name = ?";
                $updateFields[] = "conductor_token_no = ?";
                $updateFields[] = "conductor_pf_no = ?";
                array_push($updateParams, $cconductorname, $cconductortoken, $cconductorpfno);
                $updateTypes .= 'sss';
            }

            // Ensure there are fields to update
            if (!empty($updateFields)) {
                $updateQuery = "UPDATE sch_veh_out SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $updateParams[] = $sch_out_id; // Add the WHERE clause parameter
                $updateTypes .= 's'; // Add the type for the sch_out_id

                if ($updateStmt = $db->prepare($updateQuery)) {
                    $updateStmt->bind_param($updateTypes, ...$updateParams);

                    // Execute the update query
                    if ($updateStmt->execute()) {
                        echo json_encode(['status' => 'success', 'message' => 'Details Changed successfully.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to update sch_veh_out: ' . $updateStmt->error]);
                    }

                    $updateStmt->close();
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the update query: ' . $db->error]);
                }
            } else {
                echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully. No updates needed for sch_veh_out.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert data: ' . $stmt->error]);
        }


        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the query: ' . $db->error]);
    }

    $db->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}