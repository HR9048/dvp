<?php
include 'includes/connection.php';
// 1. Get program types dynamically
$program_types = [];
$column_query = "SHOW COLUMNS FROM program_master";
$column_result = mysqli_query($db, $column_query);
$exclude_columns = ['id', 'make', 'model', 'model_type', 'created_at', 'updated_at'];

while ($column = mysqli_fetch_assoc($column_result)) {
    if (!in_array($column['Field'], $exclude_columns)) {
        $program_types[] = $column['Field'];
    }
}

// Air suspension categories
$air_suspension_bus_category_array = [
    'Rajahamsa', 'Corona Sleeper AC', 'Sleeper AC', 
    'Regular Sleeper Non AC', 'Amoghavarsha Sleeper Non AC', 'Kalyana Ratha'
];

// 2. Get all divisions & depots
$divisions_query = "SELECT DISTINCT br.division_name as division_id, l.DIVISION as division_name FROM bus_registration br left join location l on br.division_name = l.division_id ORDER BY br.division_name";
$divisions_result = mysqli_query($db, $divisions_query);

$overall_total = ['total' => 0, 'updated' => 0, 'pending' => 0];

echo "<h2>Maintenance Program Data Status (All Divisions)</h2>";

while ($division = mysqli_fetch_assoc($divisions_result)) {
    $division_id = $division['division_id'];
    $division_name = $division['division_name'];

    echo "<h3>Division: $division_name</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Depot</th>
                <th>Total Buses</th>
                <th>Updated</th>
                <th>Pending</th>
            </tr>";

    // Division totals
    $division_total = ['total' => 0, 'updated' => 0, 'pending' => 0];

    // Depots in this division
    $depots_query = "SELECT DISTINCT br.depot_name as depot_id, l.DEPOT as depot_name 
                     FROM bus_registration br 
                     LEFT JOIN location l ON br.depot_name = l.DEPOT_ID 
                     WHERE br.division_name = $division_id 
                     ORDER BY br.depot_name ASC";
    $depots_result = mysqli_query($db, $depots_query);

    while ($depot = mysqli_fetch_assoc($depots_result)) {
        $depot_id = $depot['depot_id'];
        $depot_name = $depot['depot_name'];

        // Depot totals
        $depot_total = ['total' => 0, 'updated' => 0, 'pending' => 0];

        // Get buses for this depot
        $bus_query = "SELECT br.bus_number, br.make, br.emission_norms, br.model_type, br.bus_sub_category 
                      FROM bus_registration br
                      WHERE br.depot_name = $depot_id AND br.division_name = $division_id";
        $bus_result = mysqli_query($db, $bus_query);

        while ($bus = mysqli_fetch_assoc($bus_result)) {
            $depot_total['total']++;

            $bus_number = $bus['bus_number'];
            $make = $bus['make'];
            $model = $bus['model_type'];
            $emission = $bus['emission_norms'];

            // Fetch applicable programs for bus
            $prog_val_query = "SELECT * FROM program_master 
                               WHERE make = '$make' AND model = '$emission' AND model_type = '$model' LIMIT 1";
            $prog_val_result = mysqli_query($db, $prog_val_query);
            $prog_val_row = mysqli_fetch_assoc($prog_val_result);

            // Fetch filled programs
            $program_query = "SELECT program_type FROM program_data WHERE bus_number = '$bus_number'";
            $program_result = mysqli_query($db, $program_query);

            $filled_programs = [];
            while ($row = mysqli_fetch_assoc($program_result)) {
                $filled_programs[] = $row['program_type'];
            }

            $is_pending = false;
            if ($prog_val_row) {
                foreach ($program_types as $ptype) {
                    if ($ptype === 'air_suspension_check' && 
                        !in_array($bus['bus_sub_category'], $air_suspension_bus_category_array)) {
                        continue; // skip this check
                    }

                    if (!is_null($prog_val_row[$ptype]) && !in_array($ptype, $filled_programs)) {
                        $is_pending = true;
                        break;
                    }
                }
            }

            if ($is_pending) {
                $depot_total['pending']++;
            } else {
                $depot_total['updated']++;
            }
        }

        // Add depot totals to division
        $division_total['total'] += $depot_total['total'];
        $division_total['updated'] += $depot_total['updated'];
        $division_total['pending'] += $depot_total['pending'];

        // Add to overall totals
        $overall_total['total'] += $depot_total['total'];
        $overall_total['updated'] += $depot_total['updated'];
        $overall_total['pending'] += $depot_total['pending'];

        echo "<tr>
                <td>$depot_name</td>
                <td>{$depot_total['total']}</td>
                <td>{$depot_total['updated']}</td>
                <td>{$depot_total['pending']}</td>
              </tr>";
    }

    // Division summary row
    echo "<tr style='font-weight:bold; background:#f0f0f0'>
            <td>Division Total</td>
            <td>{$division_total['total']}</td>
            <td>{$division_total['updated']}</td>
            <td>{$division_total['pending']}</td>
          </tr>";

    echo "</table><br>";
}

// Overall summary row
echo "<h3>Overall Summary (All Divisions)</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>
        <tr style='font-weight:bold; background:#d0d0d0'>
            <td>Total Buses</td>
            <td>Updated</td>
            <td>Pending</td>
        </tr>
        <tr>
            <td>{$overall_total['total']}</td>
            <td>{$overall_total['updated']}</td>
            <td>{$overall_total['pending']}</td>
        </tr>
      </table>";
?>
