<?php
include '../includes/connection.php';
include 'session.php';

$errors = [];
$processedBuses = [];

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_SESSION['USERNAME'];
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];
    date_default_timezone_set('Asia/Kolkata'); 
    $submissionDateTime = date('Y-m-d H:i:s');
    
    // Prepare and execute INSERT statement for each row of table data
    $tableData = $_POST['tableData'];
    foreach ($tableData as $rowData) {
        $busNumber = $rowData['Bus Number'];

        // Skip if the bus number has already been processed
        if (in_array($busNumber, $processedBuses)) {
            continue;
        }

        // Mark this bus number as processed
        $processedBuses[] = $busNumber;

        // Check if the vehicle is already marked as off-road
        $sqlCheck = "SELECT * FROM off_road_data WHERE bus_number = '$busNumber' AND status = 'off_road'";
        $resultCheck = $db->query($sqlCheck);

        if ($resultCheck->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => "Vehicle $busNumber already marked as off-road."]);
            exit;
        }

        // Close the result set after checking
        $resultCheck->close();

        // Proceed to insert all rows for this bus number
        foreach ($tableData as $row) {
            if ($row['Bus Number'] == $busNumber) {
                $make = $row['Make'];
                $norms = $row['Norms'];
                $date = $row['Off Road from Date'];
                $offRoadLocation = $row['Off Road Location'];
                $partsRequired = $row['Parts Required'];
                $remarks = $row['Remarks'];

                // Insert data into the off_road_data table
                $sql1 = "INSERT INTO off_road_data (bus_number, make, emission_norms, off_road_date, off_road_location, parts_required, remarks, username, division, depot, submission_datetime, status)
                        VALUES ('$busNumber', '$make', '$norms', '$date', '$offRoadLocation', '$partsRequired', '$remarks', '$username', '$division', '$depot', '$submissionDateTime', 'off_road')";
                if ($db->query($sql1) === FALSE) {
                    echo json_encode(['status' => 'error', 'message' => "Error inserting data for bus $busNumber: " . $db->error]);
                    exit;
                }
            }
        }
    }

    // If everything is successful
    echo json_encode(['status' => 'success', 'message' => 'Data submitted successfully.']);

    // Close database connection
    $db->close();
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}
?>
