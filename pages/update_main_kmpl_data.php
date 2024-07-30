<?php
// Include connection file
include '../includes/connection.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from form
    $id = $_POST['editId'];
    $total_km = $_POST['total_km'];
    $hsd = $_POST['hsd'];
    $kmpl = $_POST['kmpl'];
    $date = $_POST['date'];

    // Prepare and execute SQL statement
    $sql = "UPDATE kmpl_data SET  total_km=?, hsd=?, kmpl=?, date=? WHERE id=?";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "iidsi", $total_km, $hsd, $kmpl, $date, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Data updated successfully
        echo "Data updated successfully!";
    } else {
        // Error occurred
        echo "Error: " . mysqli_error($db);
    }

    // Close statement and connection
    mysqli_stmt_close($stmt);
    mysqli_close($db);
}else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}

?>
