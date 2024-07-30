<?php
// Include your database connection file here
include '../includes/connection.php';

// Function to update all tables
function updateTables($db) {
    // Retrieve division and depot data from location table
    $query = "SELECT * FROM location";
    $result = mysqli_query($db, $query);

    // Check if query executed successfully
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $divisionId = $row['division_id'];
            $depotId = $row['depot_id'];
            $depot = $row['DEPOT'];
            

            // Define the array of tables
            $tables = array("backup_off_road_data", "bd_data", "bus_registration", "bus_scrap_data", "bus_transfer_data", "dvp_data", "kmpl_data", "off_road_data","rwy_offroad");
            
            // Update each table
            foreach ($tables as $tableName) {
                // Construct and execute the update query
                if ($tableName === 'bus_registration') {
                    $sql = "UPDATE $tableName SET depot_name = '$depotId' WHERE division_name = '$divisionId' and depot_name='$depot'";
                }else if ($tableName === 'bus_transfer_data') {
                    $sql = "UPDATE $tableName SET from_depot = '$depotId' WHERE division = '$divisionId' AND from_depot='$depot'";
                    mysqli_query($db, $sql);

                    $sql = "UPDATE $tableName SET to_depot = '$depotId' WHERE division = '$divisionId' AND to_depot='$depot'";
                } else {
                    $sql = "UPDATE $tableName SET depot = '$depotId' WHERE division = '$divisionId' and depot='$depot'";
                }
                mysqli_query($db, $sql);
            }
        }
        // Move the echo statement outside of the loop
        echo "Depot updated successfully in all tables.";
    } else {
        echo "Error retrieving data from location table: " . mysqli_error($db);
    }
}

// Check if the button is clicked
if (isset($_POST['update'])) {
    updateTables($db);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Depot</title>
</head>
<body>
    <h2>Update Depot in All Tables</h2>
    <form method="post">
        <button type="submit" name="update">Update Depot</button>
    </form>
</body>
</html>
