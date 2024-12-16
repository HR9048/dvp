<?php
session_start();

if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No language provided']);
}
?>
