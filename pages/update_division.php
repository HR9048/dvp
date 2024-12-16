<?php include '../includes/connection.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Division Update</title>
</head>
<body>
    <h2>Division Update</h2>
    <form action="" method="post">
        <button type="submit" name="update" value="Update">Update Division</button>
    </form>

    <?php
    if (isset($_POST['update'])) {
        // Fetch current division names and their IDs from location table
        $sql = "SELECT division, division_id FROM location";
        $result = $db->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $current_division = $row['division'];
                $division_id = $row['division_id'];

                // List of tables to update
                $tables = array(
                    "backup_off_road_data",
                    "bd_data",
                    "bus_registration",
                    "bus_scrap_data",
                    "bus_transfer_data",
                    "dvp_data",
                    "kmpl_data",
                    "off_road_data",
                    "rwy_offroad"
                );

                // Update division in each table
                foreach ($tables as $table) {
                    if ($table === "bus_registration") {
                        $sql = "UPDATE $table SET division_name = '$division_id' WHERE division_name = '$current_division'";
                    } else {
                        $sql = "UPDATE $table SET division = '$division_id' WHERE division = '$current_division'";
                    }
                    if ($db->query($sql) === TRUE) {
                        echo "Records updated successfully in table: $table <br>";
                    } else {
                        echo "Error updating records in table: $table. Error: " . $db->error . "<br>";
                    }
                }
            }
        } else {
            echo "No divisions found in the location table.";
        }

        $db->close();
    }
    ?>
</body>
</html>
