<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');
header("Content-Type: application/json");
ini_set('max_execution_time', 0);
include 'includes/connection.php';

// Check if API key is provided
$headers = getallheaders();
if (!isset($headers['X-API-KEY'])) {
    die(json_encode(["message" => "API Key missing"]));
}

$api_key = $headers['X-API-KEY'];
//$api_key = '20170472417';

// Verify API key in the database
$stmt = $db->prepare("SELECT id FROM api_keys WHERE api_key = ?");
$stmt->bind_param('s', $api_key);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die(json_encode(["message" => "Invalid API Key"]));
    exit;
} else {
    date_default_timezone_set('Asia/Kolkata');
    $week_start = date('Y-m-d', strtotime('monday this week'));
    echo "Starting Weekly Maintenance Generation...\n";
    /* ================= GET ALL DEPOTS ================= */
    $depots = $db->query("SELECT `division_id`, `depot_id` FROM `location` WHERE `division_id` not in (0,10) and DEPOT != 'DIVISION'");

    while ($depot = $depots->fetch_assoc()) {

        $division_id = $depot['division_id'];
        $depot_id    = $depot['depot_id'];

        //first check data present for the week, if not then only generate 
        $check = $db->query("SELECT id FROM weekly_maintenance_schedule WHERE division_id='$division_id' AND depot_id='$depot_id' AND week_start='$week_start'");
        if ($check->num_rows > 0) {
            echo "Schedule already exists for Division: $division_id | Depot: $depot_id \n";
            continue;
        }

        echo "Processing Division: $division_id | Depot: $depot_id \n";

        /* DELETE OLD WEEK DATA */
        $db->query("
        DELETE FROM weekly_maintenance_schedule
        WHERE division_id='$division_id'
        AND depot_id='$depot_id'
        AND week_start='$week_start'
    ");

        /* GET ACTIVE BUSES */
        $buses = $db->query("
        SELECT bus_number
        FROM bus_registration
        WHERE division_name='$division_id'
        AND depot_name='$depot_id'
        ORDER BY bus_number ASC
    ");

        $day = 1;

        while ($bus = $buses->fetch_assoc()) {

            $primary = $day;
            $backup  = ($day == 6) ? 1 : $day + 1;

            $bus_number = $bus['bus_number'];

            $db->query("
            INSERT INTO weekly_maintenance_schedule
            (bus_number, primary_day, backup_day, division_id, depot_id, week_start)
            VALUES
            ('$bus_number','$primary','$backup','$division_id','$depot_id','$week_start')
        ");
            $db->query("
            INSERT INTO weekly_maintenance_schedule_backup
            (bus_number, primary_day, backup_day, division_id, depot_id, week_start)
            VALUES
            ('$bus_number','$primary','$backup','$division_id','$depot_id','$week_start')
        ");

            $day++;
            if ($day > 6) $day = 1;
        }

        echo "Completed for Depot: $depot_id \n";
    }

    echo "Weekly Schedule Generated Successfully.\n";
}
