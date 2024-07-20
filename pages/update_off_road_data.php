<?php
// Include connection file
include '../includes/connection.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $id = $_POST['editId'];
    $busNumber = !empty($_POST['bus_number']) ? $_POST['bus_number'] : null;
    $make = !empty($_POST['make']) ? $_POST['make'] : null;
    $emissionNorms = !empty($_POST['emission_norms']) ? $_POST['emission_norms'] : null;
    $offRoadDate = !empty($_POST['off_road_date']) ? $_POST['off_road_date'] : null;
    $offRoadLocation = !empty($_POST['off_road_location']) ? $_POST['off_road_location'] : null;
    $partsRequired = !empty($_POST['parts_required']) ? $_POST['parts_required'] : null;
    $remarks = !empty($_POST['remarks']) ? $_POST['remarks'] : null;
    $username = !empty($_POST['username']) ? $_POST['username'] : null;
    $division = !empty($_POST['division']) ? $_POST['division'] : null;
    $depot = !empty($_POST['depot']) ? $_POST['depot'] : null;
    $submissionDatetime = !empty($_POST['submission_datetime']) ? $_POST['submission_datetime'] : null;
    $status = !empty($_POST['status']) ? $_POST['status'] : null;
    $dwsRemark = !empty($_POST['dws_remark']) ? $_POST['dws_remark'] : null;
    $noOfDaysOffroad = !empty($_POST['no_of_days_offroad']) ? $_POST['no_of_days_offroad'] : null;
    $dwsLastUpdate = !empty($_POST['dws_last_update']) ? $_POST['dws_last_update'] : null;
    $onRoadDate = !empty($_POST['on_road_date']) ? $_POST['on_road_date'] : null;

    // Prepare update query
    $sql = "UPDATE off_road_data SET 
                bus_number = ?, 
                make = ?, 
                emission_norms = ?, 
                off_road_date = ?, 
                off_road_location = ?, 
                parts_required = ?, 
                remarks = ?, 
                username = ?, 
                submission_datetime = ?, 
                status = ?, 
                dws_remark = ?, 
                no_of_days_offroad = ?, 
                dws_last_update = ?, 
                on_road_date = ? 
            WHERE id = ?";
    
    // Prepare and bind parameters
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssssssi", 
        $busNumber, 
        $make, 
        $emissionNorms, 
        $offRoadDate, 
        $offRoadLocation, 
        $partsRequired, 
        $remarks, 
        $username,
        $submissionDatetime, 
        $status, 
        $dwsRemark, 
        $noOfDaysOffroad, 
        $dwsLastUpdate, 
        $onRoadDate,
        $id
    );

    // Execute the update statement
    if (mysqli_stmt_execute($stmt)) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . mysqli_error($db);
    }

    // Close statement
    mysqli_stmt_close($stmt);
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}


// Close connection
mysqli_close($db);
?>
