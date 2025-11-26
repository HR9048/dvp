<?php
ini_set('max_execution_time', 0); // Unlimited execution time
include '../includes/connection.php';
include 'get_program_km_for_bus.php';
date_default_timezone_set('Asia/Kolkata');

echo "<h3>Updating Program Completed KM...</h3>";

$count = 0;
$failed = 0;

// Fetch all program records from the last 2 months (adjust if needed)
$query = mysqli_query($db, "
    SELECT id, bus_number, program_type, program_date, division_id, depot_id
    FROM program_data 
    WHERE id = 1 and program_date is not null
    ORDER BY bus_number, program_type, program_date
");

while ($row = mysqli_fetch_assoc($query)) {
    $id = $row['id'];
    $bus_number = $row['bus_number'];
    $program_type = $row['program_type'];
    $program_date = $row['program_date'];
    $division_id = $row['division_id'];
    $depot_id = $row['depot_id'];

    // Calculate correct KM using depot_id
    $new_km = calculateProgramKm($db, $id, $bus_number, $program_type, $program_date, $depot_id);

    // Update only if km is valid (>=0)
    if ($new_km >= 0) {
        $update = mysqli_query($db, "
            UPDATE program_data 
            SET program_completed_km = '$new_km' 
            WHERE bus_number = '$bus_number' 
              AND program_type = '$program_type' 
              AND program_date = '$program_date'
        ");

        if ($update) {
            $count++;
            echo "✅ Updated: $bus_number | $program_type | $program_date → $new_km KM<br>";
        } else {
            $failed++;
            echo "❌ Failed to update: $bus_number | $program_type | $program_date<br>";
        }
    } else {
        $failed++;
        echo "⚠️ 0 KM found for: $bus_number | $program_type | $program_date<br>";
    }
}

echo "<hr><b>Update Complete:</b><br>";
echo "✔️ Updated: $count<br>";
echo "❌ Failed: $failed<br>";
?>
