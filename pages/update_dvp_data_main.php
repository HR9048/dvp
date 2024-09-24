<?php
include '../includes/connection.php';
include 'session.php';
confirm_logged_in();
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editId'])) {
    // Retrieve the data from the form
    $id = $_POST['editId'];
    $date = $_POST['date'];
    $schedules = $_POST['schedules'];
    $vehicles = $_POST['vehicles'];
    $spare = $_POST['spare'];
    $spareP = $_POST['spareP'];
    $docking = $_POST['docking'];
    $wup = $_POST['wup'];
    $ORDepot = $_POST['ORDepot'];
    $ORDWS = $_POST['ORDWS'];
    $ORRWY = $_POST['ORRWY'];
    $dealer = $_POST['dealer'];
    $CC = $_POST['CC'];
    $loan = $_POST['loan'];
    $Police = $_POST['Police'];
    $notdepot = $_POST['notdepot'];
    $ORTotal = $_POST['ORTotal'];
    $available = $_POST['available'];
    $ES = $_POST['ES'];
    $username = $_POST['username'];
    $designation = $_POST['designation'];
    $submission_datetime = $_POST['submission_datetime'];
    $division = $_POST['division'];
    $depot = $_POST['depot'];

    // Prepare and execute the update query
    $query = "UPDATE dvp_data SET 
                date = '$date', 
                schedules = '$schedules', 
                vehicles = '$vehicles', 
                spare = '$spare', 
                spareP = '$spareP', 
                docking = '$docking', 
                wup = '$wup', 
                ORDepot = '$ORDepot', 
                ORDWS = '$ORDWS', 
                ORRWY = '$ORRWY', 
                dealer = '$dealer', 
                CC = '$CC',
                loan = '$loan',  
                Police = '$Police', 
                notdepot = '$notdepot', 
                ORTotal = '$ORTotal', 
                available = '$available', 
                ES = '$ES', 
                username = '$username', 
                designation = '$designation', 
                submission_datetime = '$submission_datetime'
                WHERE id = '$id'";

    if (mysqli_query($db, $query)) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . mysqli_error($db);
    }
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}
?>
