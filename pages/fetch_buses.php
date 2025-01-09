<?php
include '../includes/connection.php';
include '../pages/session.php';
confirm_logged_in();

// Ensure the user is logged in
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is expired, please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}

// Set timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');

// Fetch session variables
$depot_id = $_SESSION['DEPOT_ID'];
$division_id = $_SESSION['DIVISION_ID'];
$todays_date = date('Y-m-d');

// Query to fetch buses based on the specified conditions
$query = "
    SELECT br.bus_number, 'regular' AS source
    FROM bus_registration br
    LEFT JOIN sch_veh_out vso 
        ON br.bus_number = vso.vehicle_no 
        AND vso.schedule_status IN (1)
    LEFT JOIN off_road_data or_status 
        ON br.bus_number = or_status.bus_number 
        AND or_status.status = 'off_road'
    LEFT JOIN vehicle_deputation vd
        ON br.bus_number = vd.bus_number 
        AND vd.tr_date = '$todays_date'
        AND vd.status in ('2','3')
    WHERE br.depot_name = $depot_id
      AND br.division_name = $division_id
      AND vso.vehicle_no IS NULL
      AND or_status.bus_number IS NULL
      AND (
          (vd.f_division_id IS NULL OR vd.f_division_id != '$division_id' OR vd.f_depot_id != '$depot_id')
      )
    UNION
    SELECT vd.bus_number, 'deputed' AS source
    FROM vehicle_deputation vd
    LEFT JOIN sch_veh_out vso 
        ON vd.bus_number = vso.vehicle_no 
        AND vso.schedule_status = 1
    WHERE vd.tr_date = '$todays_date'
      AND vd.t_division_id = '$division_id'
      AND vd.t_depot_id = '$depot_id'
      AND vd.status = '2'
      AND vso.vehicle_no IS NULL
";


// Execute the query
$result = mysqli_query($db, $query) or die(mysqli_error($db));

// Prepare the list of buses with ID and Text for Select option
$buses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bus_number = $row['bus_number'];
    if ($row['source'] === 'deputed') {
        // For deputed vehicle, append (deputed) to the bus number for the text
        $buses[] = [
            'id' => $bus_number,        // Use bus_number as the id
            'text' => $bus_number . " (deputed)"  // Append (deputed) for the text
        ];
    } else {
        // For regular vehicle, just use bus_number for the text
        $buses[] = [
            'id' => $bus_number,        // Use bus_number as the id
            'text' => $bus_number       // Only bus_number for the text
        ];
    }
}

// Return the result as JSON for the Select dropdown
header('Content-Type: application/json');
echo json_encode($buses);
?>
