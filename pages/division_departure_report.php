<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session has expired. Please login.'); window.location = 'logout.php';</script>";
    exit;
}
date_default_timezone_set('Asia/Kolkata');

if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'ASO(Stat)' || $_SESSION['JOB_TITLE'] == 'DC' || $_SESSION['JOB_TITLE'] == 'DTO') {
    $division_id= $_SESSION['DIVISION_ID'];
    ?>
<style>
.hide {
    display: none;
}

th,
td {
    border: 1px solid black;
    text-align: left;
    font-size: 15px;
    padding: 1px !important;
}

th {
    background-color: #f2f2f2;
}

.dataTable th,
.dataTable td {
    padding: 1px !important;
}

.btn {
    padding-top: 0px;
    padding-bottom: 0px;
}

table {
    margin: 20px auto;
    width: 90%;
    border-collapse: collapse;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:nth-child(odd) {
    background-color: #ffffff;
}

tr:hover {
    background-color: #f1f1f1;
}
</style>

<form method="POST">
    <label for="selected_date">Select Date:</label>
    <input type="date" name="selected_date" id="selected_date" required>
    <button class="btn btn-primary" type="submit">Fetch Departure Report</button>
</form>

<div class="container1">
    <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_date'])) {
            $selected_date = date('Y-m-d', strtotime($_POST['selected_date'])); // 'YYYY-MM-DD' format
            $is_today = ($selected_date === date('Y-m-d')); // Check if the selected date is today
            $current_time = date('H:i'); // Get the current time in HH:MM format for the header

            // Query to fetch all divisions and depots
            $location_query = "SELECT division_id, depot_id, division, depot FROM location WHERE division_id= $division_id AND DEPOT != 'DIVISION'";
            $location_result = $db->query($location_query);

            // Initialize totals
            $overall_active_schedules = 0;
            $overall_total_departures = 0;
            $overall_total_arrival = 0;
            $overall_present_time_departures = 0; // Track overall present time departures
            $overall_present_time_arrivals = 0;
            echo "<h3 class='text-center'>Departures and Arrival Report Date: " . date('d-m-Y', strtotime($selected_date)) . "</h3>";

            echo "<table border='1'>
                <tr>
                    <th>Division</th>
                    <th>Depot</th>
                    <th>Active Schedules</th>";
            // Conditionally display Present Time Departure Count column only if selected date is today
            if ($is_today) {
                echo "<th>Present Time ($current_time) Departure</th>"; // Header with the current time
            }
            echo "<th>Total Departures</th>";
            if ($is_today) {
                echo "<th>Present Time ($current_time) Arrival</th>"; // Header with the current time
            }
                  echo"<th>Total Arrivals</th>
                </tr>";

            // Process each division and depot
            $current_division_id = null;
            $current_division_name = null;
            $division_active_schedules = 0;
            $division_total_departures = 0;
            $division_total_arrival = 0;
            $division_present_time_departures = 0; // Track division's present time departures
            $division_present_time_arrivals = 0;
            while ($location_row = $location_result->fetch_assoc()) {
                $division_id = $location_row['division_id'];
                $depot_id = $location_row['depot_id'];
                $division_name = $location_row['division'];
                $depot_name = $location_row['depot'];

                // New division detected, reset division totals and display division summary row
                if ($current_division_id !== null && $current_division_id !== $division_id) {
                    // Display division totals
                    echo "<tr style='font-weight: bold;'>
                        <td colspan='2'>Total for $current_division_name</td>
                        <td>$division_active_schedules</td>";
                    if ($is_today) {
                        echo "<td>$division_present_time_departures</td>";
                    }
                    echo "<td>$division_total_departures</td>";
                    if ($is_today) {
                        echo "<td>$division_present_time_arrivals</td>";
                    }
                     echo"<td>$division_total_arrival</td>
                    </tr>";

                    // Reset division totals
                    $division_active_schedules = 0;
                    $division_total_departures = 0;
                    $division_total_arrival = 0;
                    $division_present_time_departures = 0;
                    $division_present_time_arrivals = 0;
                }
                $current_division_id = $division_id;
                $current_division_name = $division_name;

                // Query schedules from schedule_master
                $schedule_query = "SELECT sm.sch_key_no, sm.division_id, sm.depot_id, sm.sch_dep_time, sm.sch_arr_time
                FROM schedule_master sm
                LEFT JOIN sch_actinact sai 
                  ON sm.sch_key_no = sai.sch_key_no 
                  AND sm.division_id = sai.division_id 
                  AND sm.depot_id = sai.depot_id
                WHERE sm.division_id = ? 
                  AND sm.depot_id = ? 
                  AND NOT EXISTS (
                      SELECT 1
                      FROM sch_actinact sai_sub
                      WHERE sai_sub.sch_key_no = sm.sch_key_no
                        AND sai_sub.division_id = sm.division_id
                        AND sai_sub.depot_id = sm.depot_id
                        AND sai_sub.inact_to IS NULL
                  )
                  AND (sai.inact_to IS NOT NULL OR sai.sch_key_no IS NULL);";
                $stmt = $db->prepare($schedule_query);
                $stmt->bind_param('ii', $division_id, $depot_id);
                $stmt->execute();
                $schedule_result = $stmt->get_result();

                $schedules = [];
                while ($row = $schedule_result->fetch_assoc()) {
                    $schedules[] = $row;
                }

                $total_active_schedules = 0; // Initialize counter for active schedules
                $present_time_departure_count = 0; // Initialize counter for present time departures
                $present_time_arrival_count = 0;
                // Process each schedule
                foreach ($schedules as $schedule) {
                    $sch_key_no = $schedule['sch_key_no'];
                    $division_id = $schedule['division_id'];
                    $depot_id = $schedule['depot_id'];
                    $sch_dep_time = $schedule['sch_dep_time'];
                    $sch_arr_time = $schedule['sch_arr_time'];
                    // Query sch_actinact for this schedule
                    $inact_query = "SELECT inact_from, inact_to FROM sch_actinact 
                    WHERE sch_key_no = ? AND division_id = ? AND depot_id = ?";
                    $stmt = $db->prepare($inact_query);
                    $stmt->bind_param('sii', $sch_key_no, $division_id, $depot_id);
                    $stmt->execute();
                    $inact_result = $stmt->get_result();

                    if ($inact_result->num_rows > 0) {
                        // Schedule found in sch_actinact
                        $inactive_data = $inact_result->fetch_assoc();
                        $inact_from = $inactive_data['inact_from'];
                        $inact_to = $inactive_data['inact_to'];

                        // Check if selected date is between inact_from and inact_to
                        if (
                            $selected_date > date('Y-m-d', strtotime($inact_from)) &&
                            $selected_date < date('Y-m-d', strtotime($inact_to))
                        ) {
                            continue; // Exclude this schedule
                        }

                        // If selected_date matches inact_from or inact_to, apply timing checks
                        if ($selected_date === date('Y-m-d', strtotime($inact_from))) {
                            $inact_from_time = date('H:i:s', strtotime($inact_from));
                            if ($inact_from_time > '11:00:00') {
                                continue; // Exclude if time is after 11:00 AM
                            }
                        }

                        if ($selected_date === date('Y-m-d', strtotime($inact_to))) {
                            $inact_to_time = date('H:i:s', strtotime($inact_to));
                            if ($inact_to_time < '16:00:00') {
                                continue; // Exclude if time is before 4:00 PM
                            }
                        }
                    }

                    // If schedule is active, count it
                    $total_active_schedules++;

                    // If today, count schedules whose departure time is <= current time
                    if ($is_today) {
                        $current_time = date('H:i:s'); // Get the current time in HH:MM:SS format
                        if ($sch_dep_time <= $current_time) {
                            $present_time_departure_count++;
                        }
                    }
                    if ($is_today) {
                        $current_time = date('H:i:s'); // Get the current time in HH:MM:SS format
                        if ($sch_arr_time <= $current_time) {
                            $present_time_arrival_count++;
                        }
                    }
                }

              // Departure data query (remains the same)
$departure_query = "SELECT COUNT(svo.sch_no) AS total_departures
                    FROM sch_veh_out svo
                    WHERE svo.division_id = ? 
                      AND svo.depot_id = ? 
                      AND svo.departed_date = ?";

$stmt2 = $db->prepare($departure_query);
$stmt2->bind_param('iis', $division_id, $depot_id, $selected_date); // Corrected the bind_param type
$stmt2->execute();
$result2 = $stmt2->get_result();
$row2 = $result2->fetch_assoc();
$total_departures = $row2['total_departures'];

// Arrival data query
$arrival_query = "SELECT COUNT(svo.sch_no) AS total_arrival
                  FROM sch_veh_out svo
                  WHERE svo.division_id = ? 
                    AND svo.depot_id = ? 
                    AND svo.arr_date = ?";

$stmt3 = $db->prepare($arrival_query);
$stmt3->bind_param('iis', $division_id, $depot_id, $selected_date); // Corrected the bind_param type
$stmt3->execute();
$result3 = $stmt3->get_result();
$row3 = $result3->fetch_assoc();
$total_arrival = $row3['total_arrival'];

                // Update division totals
                $division_active_schedules += $total_active_schedules;
                $division_total_departures += $total_departures;
                $division_total_arrival += $total_arrival;
                $division_present_time_departures += $present_time_departure_count;
                $division_present_time_arrivals += $present_time_arrival_count;
                // Update overall totals
                $overall_active_schedules += $total_active_schedules;
                $overall_total_departures += $total_departures;
                $overall_total_arrival += $total_arrival;
                $overall_present_time_departures += $present_time_departure_count;
                $overall_present_time_arrivals += $present_time_arrival_count;
                // Output data for each depot
                echo "<tr>
                    <td>$division_name</td>
                    <td>$depot_name</td>
                    <td>$total_active_schedules</td>";
                if ($is_today) {
                    echo "<td>$present_time_departure_count</td>"; // Show present time departure count
                }
                echo "<td>$total_departures</td>";
                if ($is_today) {
                    echo "<td>$present_time_arrival_count</td>";
                }
                    echo"<td>$total_arrival</td></tr>";
            }

            // Display final division totals
            if ($current_division_id !== null) {
                echo "<tr style='font-weight: bold;'>
                    <td colspan='2'>Total for $current_division_name</td>
                    <td>$division_active_schedules</td>";
                if ($is_today) {
                    echo "<td>$division_present_time_departures</td>";
                }
                echo "<td>$division_total_departures</td>";
                if ($is_today) {
                    echo "<td>$division_present_time_arrivals</td>";
                }
                echo "<td>$division_total_arrival</td>
                </tr>";
            }

           

            echo "</table>";
        }
        ?>
</div>

<?php
    include '../includes/footer.php';
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
?>