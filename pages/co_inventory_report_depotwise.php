<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && ($_SESSION['JOB_TITLE'] == 'CME_CO')) {

    $query = "SELECT 
        l.division_id,
        l.division AS division_name,
        l.depot AS depot_name,
        COUNT(DISTINCT br.bus_number) AS vehicle_held,
        COUNT(DISTINCT bi.bus_number) AS inventory_submitted
    FROM location l
    LEFT JOIN bus_registration br 
        ON l.depot_id = br.depot_name AND l.division_id = br.division_name
    LEFT JOIN bus_inventory bi 
        ON l.depot_id = bi.depot_id AND l.division_id = bi.division_id
    WHERE l.division_id NOT IN (0) 
    GROUP BY l.division_id, l.depot_id
    ORDER BY l.division_id, l.depot_id";

    $result = mysqli_query($db, $query);
?>

<div class="container1">
    <h2 class="mt-4 mb-4">Bus Inventory Report</h2>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Sl No</th>
                <th>Division</th>
                <th>Depot</th>
                <th>Vehicle Held</th>
                <th>Inventory Submitted</th>
                <th>Difference</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sl = 1;
            $prev_division = '';
            $div_total_held = 0;
            $div_total_inventory = 0;
            $div_total_diff = 0;
            $overall_held = 0;
            $overall_inventory = 0;
            $overall_diff = 0;

            while ($row = mysqli_fetch_assoc($result)) {
                $curr_division = $row['division_name'];
                $depot_name = $row['depot_name'];
                $vehicle_held = $row['vehicle_held'];
                $inventory_submitted = $row['inventory_submitted'];
                $isExcludedDepot = in_array(strtoupper($depot_name), ['DIVISION', 'YDG']);
                $difference = $isExcludedDepot ? 'N/A' : ($vehicle_held - $inventory_submitted);
            
                // Check if division changed
                if ($prev_division !== '' && $curr_division !== $prev_division) {
                    echo "<tr style='font-weight: bold; background-color: #f2f2f2;'>
                            <td colspan='3'>Total for {$prev_division}</td>
                            <td>{$div_total_held}</td>
                            <td>{$div_total_inventory}</td>
                            <td>{$div_total_diff}</td>
                          </tr>";
                    $div_total_held = $div_total_inventory = $div_total_diff = 0;
                }
            
                echo "<tr>
                        <td>{$sl}</td>
                        <td>{$curr_division}</td>
                        <td>{$depot_name}</td>
                        <td>{$vehicle_held}</td>
                        <td>{$inventory_submitted}</td>
                        <td>{$difference}</td>
                      </tr>";
            
                // Update totals if not excluded
                if (!$isExcludedDepot) {
                    $div_total_diff += $vehicle_held - $inventory_submitted;
                    $overall_diff += $vehicle_held - $inventory_submitted;
                }
            
                $div_total_held += $vehicle_held;
                $div_total_inventory += $inventory_submitted;
                $overall_held += $vehicle_held;
                $overall_inventory += $inventory_submitted;
            
                $prev_division = $curr_division;
                $sl++;
            }
            

            // Final division subtotal
            if ($prev_division !== '') {
                echo "<tr style='font-weight: bold; background-color: #f2f2f2;'>
                        <td colspan='3'>Total for {$prev_division}</td>
                        <td>{$div_total_held}</td>
                        <td>{$div_total_inventory}</td>
                        <td>{$div_total_diff}</td>
                      </tr>";
            }

            // Overall total row
            echo "<tr style='font-weight: bold; background-color: #d9edf7;'>
                    <td colspan='3'>Overall Total</td>
                    <td>{$overall_held}</td>
                    <td>{$overall_inventory}</td>
                    <td>{$overall_diff}</td>
                  </tr>";
            ?>
        </tbody>
    </table>
</div>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>
