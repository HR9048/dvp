<?php

header('Content-Type: application/json');

include 'connection.php'; // Ensure the $db connection is defined

if (!$db) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

if ($_GET['action'] === 'fetchThumbsUpStatus') {
    $query = "SELECT id, thumbs, name, percentage FROM feedback";
    $result = mysqli_query($db, $query);

    if (!$result) {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($db)]);
        exit;
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

?>