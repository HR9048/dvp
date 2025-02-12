<?php
header('Content-Type: application/json');
include '../includes/connection.php';
include '../pages/session.php';
// Query to get all division and depot combinations
$query = "SELECT kmpl_division, kmpl_depot FROM location where division_id='{$_SESSION['DIVISION_ID']}'";
$result = mysqli_query($db, $query);

if (!$result) {
    die(json_encode(["error" => "Error fetching division and depot data: " . mysqli_error($db)]));
}

// Prepare to store all the combined data
$allData = [];

// Array to hold cURL handles
$curlHandles = [];
$multiCurl = curl_multi_init(); // Initialize multi-cURL handle

// Loop through each division and depot, and prepare the cURL requests
while ($row = mysqli_fetch_assoc($result)) {
    $division = $row['kmpl_division'];
    $depot = $row['kmpl_depot'];

    // Prepare API URL with division and depot
    $apiUrl = 'http://localhost:8880/dvp/includes/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);

    // Initialize individual cURL session
    $ch = curl_init($apiUrl);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
    curl_setopt($ch, CURLOPT_HTTPGET, true);        // Use GET method

    // Add the handle to the multi-cURL handle
    curl_multi_add_handle($multiCurl, $ch);

    // Store the cURL handle to reference it later
    $curlHandles[] = $ch;
}

// Execute all cURL requests in parallel
$running = null;
do {
    curl_multi_exec($multiCurl, $running);
    curl_multi_select($multiCurl);
} while ($running > 0);

// Collect the responses and merge the data
foreach ($curlHandles as $ch) {
    $response = curl_multi_getcontent($ch); // Get the content from each handle

    // Decode JSON response
    $data = json_decode($response, true);

    // Check if data exists
    if (isset($data['data']) && is_array($data['data'])) {
        // Merge the current API response data into the $allData array
        $allData = array_merge($allData, $data['data']);
    }

    // Remove the handle from the multi-cURL handler and close it
    curl_multi_remove_handle($multiCurl, $ch);
    curl_close($ch);
}

// Close the multi-cURL handle
curl_multi_close($multiCurl);

// Now you have all the data combined in $allData
if (empty($allData)) {
    echo json_encode(['drivers' => 0, 'conductors' => 0, 'dcc' => 0]);
    exit;
} else {
    // Filter and count the 'DRIVER', 'CONDUCTOR', and 'DCC' employees
    $totalDriverCount = count(array_filter($allData, fn($item) => $item['EMP_DESGN_AT_APPOINTMENT'] === 'DRIVER'));
    $totalConductorCount = count(array_filter($allData, fn($item) => $item['EMP_DESGN_AT_APPOINTMENT'] === 'CONDUCTOR'));
    $totalDCCCount = count(array_filter($allData, fn($item) => $item['EMP_DESGN_AT_APPOINTMENT'] === 'DRIVER-CUM-CONDUCTOR'));

    echo json_encode([
        'drivers' => $totalDriverCount,
        'conductors' => $totalConductorCount,
        'dcc' => $totalDCCCount,
    ]);
}
?>
