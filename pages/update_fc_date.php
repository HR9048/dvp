<?php
include '../includes/connection.php';

$id = $_POST['id'];
$fc_date = $_POST['fc_date'];

if (!empty($id) && !empty($fc_date)) {
    $stmt = $db->prepare("UPDATE bus_inventory SET date_of_fc = ? WHERE id = ?");
    $stmt->bind_param("si", $fc_date, $id);
    if ($stmt->execute()) {
        echo "FC Date updated successfully!";
    } else {
        echo "Update failed.";
    }
} else {
    echo "Invalid input.";
}
?>
