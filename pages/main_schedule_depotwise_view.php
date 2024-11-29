<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is expired. Please login.'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {

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
        margin: 20px auto; /* Center the table horizontally */
        width: 70%; /* Set the maximum width to 70% */
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
<div class="container1">
<h4 class="text-center">Depot wise Schedule Master Updated Details</h4>
    <?php
    $sql = "
    SELECT 
        l.division AS division_name,
        l.depot AS depot_name,
        sm.depot_id,
        sm.division_id,
        COUNT(*) AS total_schedules,
        SUM(CASE WHEN sm.bus_number_1 IS NULL THEN 1 ELSE 0 END) AS bus_null_count,
        SUM(CASE WHEN sm.driver_token_1 IS NULL THEN 1 ELSE 0 END) AS driver_null_count
    FROM 
        schedule_master sm
    JOIN 
        location l ON sm.depot_id = l.depot_id
    WHERE 
        sm.status = 1
    GROUP BY 
        l.division, l.depot, sm.depot_id
    ORDER BY 
        l.division_id, l.depot_id;
    ";

    $result = $db->query($sql);

    // Initialize variables for tracking division and overall totals
    $division_totals = [];
    $overall_total_schedules = $overall_bus_null = $overall_driver_null = 0;

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th>Division</th>
                    <th>Depot</th>
                    <th>Total Schedules</th>
                    <th>Bus Details Not Updated</th>
                    <th>Crew Details not Updated</th>
                </tr>";

        $current_division = '';
        $division_total_schedules = $division_bus_null = $division_driver_null = 0;

        while ($row = $result->fetch_assoc()) {
            // Detect division change to display division totals
            if ($current_division !== '' && $current_division !== $row['division_name']) {
                // Display division total row
                echo "<tr style='font-weight:bold;'>
                        <td colspan='2'>Total for $current_division</td>
                        <td>$division_total_schedules</td>
                        <td>$division_bus_null</td>
                        <td>$division_driver_null</td>
                      </tr>";

                // Reset division totals
                $division_total_schedules = $division_bus_null = $division_driver_null = 0;
            }

            // Display individual depot data
            echo "<tr>
                    <td>{$row['division_name']}</td>
                    <td>{$row['depot_name']}</td>
                    <td>{$row['total_schedules']}</td>
                    <td>{$row['bus_null_count']}</td>
                    <td>{$row['driver_null_count']}</td>
                  </tr>";

            // Update division and overall totals
            $current_division = $row['division_name'];
            $division_total_schedules += $row['total_schedules'];
            $division_bus_null += $row['bus_null_count'];
            $division_driver_null += $row['driver_null_count'];

            $overall_total_schedules += $row['total_schedules'];
            $overall_bus_null += $row['bus_null_count'];
            $overall_driver_null += $row['driver_null_count'];
        }

        // Display final division total
        echo "<tr style='font-weight:bold;'>
                <td colspan='2'>Total for $current_division</td>
                <td>$division_total_schedules</td>
                <td>$division_bus_null</td>
                <td>$division_driver_null</td>
              </tr>";

        // Display overall total
        echo "<tr style='font-weight:bold; background-color:#f0f0f0;'>
                <td colspan='2'>Corporation Total</td>
                <td>$overall_total_schedules</td>
                <td>$overall_bus_null</td>
                <td>$overall_driver_null</td>
              </tr>";

        echo "</table>";
    } else {
        echo "No records found.";
    }
    ?>
    </div>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}

include '../includes/footer.php';
?>
