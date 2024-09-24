<?php
// ramp_off_road_submit.php

// Include database connection file
include '../includes/connection.php'; // Adjust the path as needed
include '../pages/session.php';
confirm_logged_in();
// Set content type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data from the request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Check if data is received
    if (isset($data['rowsData']) && !empty($data['rowsData'])) {
        $rowsData = $data['rowsData'];

        // Extract the sch_out_id from the first row
        $sch_out_id = $rowsData[0]['sch_out_id1'] ?? null;

        if ($sch_out_id) {
            // Run the update query for sch_veh_out
            $update_stmt = $db->prepare("UPDATE sch_veh_out SET schedule_status = 5 WHERE id = ? AND schedule_status = 4");

            if ($update_stmt === false) {
                echo json_encode(['status' => 'error', 'message' => 'Error preparing update SQL statement.']);
                exit;
            }

            // Bind parameter for update
            $update_stmt->bind_param('i', $sch_out_id);
            $update_stmt->execute();
            $affected_rows = $update_stmt->affected_rows;
            $update_stmt->close();

            if ($affected_rows > 0) {
                // Prepare SQL statement for checking existing off-road data
                $check_stmt = $db->prepare("SELECT bus_number FROM off_road_data WHERE bus_number = ? AND status = 'off_road'");

                if ($check_stmt === false) {
                    echo json_encode(['status' => 'error', 'message' => 'Error preparing check SQL statement.']);
                    exit;
                }

                // Use the bus number from the first row for the check
                $bus_number = $rowsData[0]['busNumber'] ?? null;
                $check_stmt->bind_param('s', $bus_number);
                $check_stmt->execute();
                $resultCheck = $check_stmt->get_result();

                if ($resultCheck->num_rows > 0) {
                    // Vehicle already off-road, so return an error message
                    echo json_encode(['status' => 'error', 'message' => "Vehicle $bus_number is already marked as off-road. Please refresh the page."]);
                    $check_stmt->close();
                    exit;
                }

                // Close the check statement
                $check_stmt->close();

                // Prepare SQL statement for inserting data
                $insert_stmt = $db->prepare("INSERT INTO off_road_data (bus_number, make, emission_norms, off_road_date, off_road_location, parts_required, remarks, username, division, depot, submission_datetime, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if ($insert_stmt === false) {
                    echo json_encode(['status' => 'error', 'message' => 'Error preparing insert SQL statement.']);
                    exit;
                }

                $success = true;
                date_default_timezone_set('Asia/Kolkata');
                $submission_datetime = date('Y-m-d H:i:s');
                $status = 'off_road'; // Default status

                // Bind parameters and insert data
                foreach ($rowsData as $row) {
                    $make = $row['make'] ?? null;
                    $emission_norms = $row['emissionNorms'] ?? null;
                    $off_road_date = $row['offRoadFromDate'] ?? null;
                    $off_road_location = $row['offRoadLocation'] ?? null;
                    $parts_required = $row['partsRequired'] ?? null;
                    $remarks = $row['remarks'] ?? null;
                    $username = $_SESSION['USERNAME'];
                    $division = $_SESSION['DIVISION_ID'];
                    $depot = $_SESSION['DEPOT_ID'];

                    $insert_stmt->bind_param(
                        'ssssssssssss',
                        $bus_number,
                        $make,
                        $emission_norms,
                        $off_road_date,
                        $off_road_location,
                        $parts_required,
                        $remarks,
                        $username,
                        $division,
                        $depot,
                        $submission_datetime,
                        $status
                    );

                    if (!$insert_stmt->execute()) {
                        $success = false;
                        break;
                    }
                }

                // Close the insert statement
                $insert_stmt->close();

                if ($success) {
                    echo json_encode(['status' => 'success', 'message' => 'Data submitted successfully. Vehicle added to off-road and schedule status updated successfully.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error inserting data into the database.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Schedule status already updated.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No valid sch_out_id found.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

// Close the database connection
$db->close();
?>
