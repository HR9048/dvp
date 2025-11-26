<?php
include '../includes/connection.php';
function calculateProgramKm($db, $id, $bus_number, $program_type, $selected_date, $depot_id) {
    // Determine program start date by depot

    if (in_array($depot_id, ['1', '8', '12', '13', '14', '15'])) {
        $programstart_date = '2025-08-01';
    } else {
        $programstart_date = '2025-10-01';
    }


    // Step 1: Get the last program entry (if any)
    $last_km_query = "
    SELECT program_completed_km, program_date 
    FROM program_data 
    WHERE bus_number = '$bus_number' 
    AND program_type = '$program_type' 
    AND id < '$id'
    ORDER BY program_date DESC 
    LIMIT 1
";
    $last_km_result = mysqli_query($db, $last_km_query);
    


    if ($row = mysqli_fetch_assoc($last_km_result)) {
        $last_km = (float)$row['program_completed_km'];
        $last_date = $row['program_date'];
        echo "Last KM: ".$last_km." on Date: ".$last_date;
    }

    // Step 2: Calculate KM from vehicle_kmpl
    if ($last_date == null) {
        $from_date = $programstart_date;
    }


    if ($last_date !== null) {
        $from_date = date('Y-m-d', strtotime($last_date . ' +1 day'));
    }

    $km_query = "
    SELECT SUM(km_operated) AS total_km 
    FROM vehicle_kmpl 
    WHERE bus_number = '$bus_number' 
    AND date >= '$from_date' 
    AND date <= '$selected_date'
    AND deleted != '1'
";
    $km_result = mysqli_query($db, $km_query);
    $total_km = 0;

    if ($km_row = mysqli_fetch_assoc($km_result)) {
        $total_km = (float)$km_row['total_km'];
        echo "Total KM from vehicle_kmpl: ".$total_km;
    }
    if ($last_date == null) {
        $estimated_km = $last_km + $total_km;
        echo "estimated_km if last_date null: ".$estimated_km;
    } else {
        $estimated_km =  $total_km;
        echo "estimated_km if last_date not null: ".$estimated_km;
    }

    return $estimated_km;
}

//call the function with sample data



?>
