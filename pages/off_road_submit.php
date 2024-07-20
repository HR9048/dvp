<?php
include '../includes/connection.php';
include 'session.php';

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_SESSION['USERNAME'];
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];
    date_default_timezone_set('Asia/Kolkata'); // Set the timezone to Indian Standard Time (IST)
    $submissionDateTime = date('Y-m-d H:i:s'); // Get the current date and time in Bangalore (IST)
    
    
    // Prepare and execute INSERT statement for each row of table data
    $tableData = $_POST['tableData'];
    foreach ($tableData as $rowData) {
        $busNumber = $rowData['Bus Number'];
        $make = $rowData['Make'];
        $norms = $rowData['Norms'];
        $date = $rowData['Off Road from Date'];
        $offRoadLocation = $rowData['Off Road Location'];
        $partsRequired = $rowData['Parts Required'];
        $remarks = $rowData['Remarks'];

        // Insert data into first table
        $sql1 = "INSERT INTO off_road_data (bus_number, make, emission_norms, off_road_date, off_road_location, parts_required, remarks, username, division, depot, submission_datetime, status)
                VALUES ('$busNumber', '$make', '$norms', '$date', '$offRoadLocation', '$partsRequired', '$remarks', '$username', '$division', '$depot', '$submissionDateTime', 'off_road')";
        if ($db->query($sql1) === FALSE) {
            echo "Error: " . $sql1 . "<br>" . $db->error;
        }
        
        // Insert data into second table (backup table)
        $sql2 = "INSERT INTO backup_off_road_data (bus_number, make, emission_norms, off_road_date, off_road_location, parts_required, remarks, username, division, depot, submission_datetime, status)
                VALUES ('$busNumber', '$make', '$norms', '$date', '$offRoadLocation', '$partsRequired', '$remarks', '$username', '$division', '$depot', '$submissionDateTime', 'off_road')";
        if ($db->query($sql2) === FALSE) {
            echo "Error: " . $sql2 . "<br>" . $db->error;
        }
    }

    // Close database connection
    $db->close();
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

?>
