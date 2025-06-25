<?php
$servername = "localhost"; 
$username = "root"; 
$password = "kkrtcsystem";
$port = 33306; // MySQL custom port
$database = "india_data1"; 
// Create connection using the custom MySQL port
$conn = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 


// Load JSON file
$jsonData = file_get_contents("../../data.json");
$data = json_decode($jsonData, true);

$conn->begin_transaction();  // Start transaction for faster insert

try {
    foreach ($data as $stateData) {
        $stateName = $conn->real_escape_string($stateData['state']);

        // Insert state (if not exists)
        $conn->query("INSERT INTO states (name) VALUES ('$stateName') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $state_id = $conn->insert_id;

        foreach ($stateData['districts'] as $districtData) {
            $districtName = $conn->real_escape_string($districtData['district']);

            // Insert district
            $conn->query("INSERT INTO districts (name, state_id) VALUES ('$districtName', $state_id) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
            $district_id = $conn->insert_id;

            foreach ($districtData['subDistricts'] as $subDistrictData) {
                $subDistrictName = $conn->real_escape_string($subDistrictData['subDistrict']);

                // Insert sub-district
                $conn->query("INSERT INTO sub_districts (name, district_id) VALUES ('$subDistrictName', $district_id) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                $sub_district_id = $conn->insert_id;

                foreach ($subDistrictData['villages'] as $villageName) {
                    $villageName = $conn->real_escape_string($villageName);

                    // Insert village
                    $conn->query("INSERT INTO villages (name, sub_district_id) VALUES ('$villageName', $sub_district_id) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                }
            }
        }
    }

    $conn->commit(); // Commit transaction
    echo "Data inserted successfully!";
} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
