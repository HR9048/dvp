<?php
$servername = "localhost"; 
$username = "root"; 
$password = "kkrtcsystem";
$port = 33306; // MySQL custom port
$database = "india_data"; 
// Create connection using the custom MySQL port
$conn = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Load JSON File
$jsonData = file_get_contents("../../data.json");
$data = json_decode($jsonData, true);

// Insert Data
foreach ($data as $stateData) {
    $stateName = $stateData['state'];

    // Insert or Get State ID
    $stmt = $conn->prepare("INSERT IGNORE INTO states (state_name) VALUES (?)");
    $stmt->bind_param("s", $stateName);
    $stmt->execute();
    $stateId = $conn->insert_id ?: $conn->query("SELECT id FROM states WHERE state_name = '$stateName'")->fetch_assoc()['id'];

    foreach ($stateData['districts'] as $districtData) {
        $districtName = $districtData['district'];

        // Insert or Get District ID
        $stmt = $conn->prepare("INSERT IGNORE INTO districts (state_id, district_name) VALUES (?, ?)");
        $stmt->bind_param("is", $stateId, $districtName);
        $stmt->execute();
        $districtId = $conn->insert_id ?: $conn->query("SELECT id FROM districts WHERE state_id = $stateId AND district_name = '$districtName'")->fetch_assoc()['id'];

        foreach ($districtData['subDistricts'] as $subDistrictData) {
            $subDistrictName = $subDistrictData['subDistrict'];

            // Insert or Get Sub-District ID
            $stmt = $conn->prepare("INSERT IGNORE INTO sub_districts (district_id, sub_district_name) VALUES (?, ?)");
            $stmt->bind_param("is", $districtId, $subDistrictName);
            $stmt->execute();
            $subDistrictId = $conn->insert_id ?: $conn->query("SELECT id FROM sub_districts WHERE district_id = $districtId AND sub_district_name = '$subDistrictName'")->fetch_assoc()['id'];

            foreach ($subDistrictData['villages'] as $villageName) {
                // Insert Village
                $stmt = $conn->prepare("INSERT IGNORE INTO villages (sub_district_id, village_name) VALUES (?, ?)");
                $stmt->bind_param("is", $subDistrictId, $villageName);
                $stmt->execute();
            }
        }
    }
}

echo "Data inserted successfully!";
$conn->close();
?>
