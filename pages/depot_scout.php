<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/depot_top.php';

// Restrict access based on user type and job title
$query = 'SELECT ID, t.TYPE
          FROM users u
          JOIN type t ON t.TYPE_ID=u.TYPE_ID
          WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DIVISION') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to Division Page');
                window.location = 'division.php';
              </script>";
    } elseif ($Aa == 'HEAD-OFFICE') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to Head Office Page');
                window.location = 'index.php';
              </script>";
    } elseif ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to Mech Page');
                window.location = '../includes/depot_verify.php';
              </script>";
    }
}
?>

    <style>
        .container-custom {
            width: 70%;
        }
    </style>

<div class="container container-custom mt-5">
    <h1>Enter Schedule Key Number</h1>
    <form method="post" action="">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="sch_key_no">Schedule Key Number:</label>
                <input type="text" class="form-control" id="sch_key_no" name="sch_key_no" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Fetch Details</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Fetch schedule key number
        $sch_key_no = mysqli_real_escape_string($db, $_POST['sch_key_no']);
        $division_id = $_SESSION['DIVISION_ID'];
        $depot_id = $_SESSION['DEPOT_ID'];

        // Query to fetch schedule details
        $query = "SELECT * FROM schedule_master WHERE sch_key_no = ? AND division_id = ? AND depot_id = ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "sii", $sch_key_no, $division_id, $depot_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            echo '<h2 class="mt-4">Schedule Details</h2>';
            echo '<form method="post" action="update_schedule.php">';

            // Display bus details
            echo '<div class="form-row">';
            echo '<div class="form-group col-md-6">';
            echo '<label for="bus_details">Bus Details:</label>';
            echo '<select class="form-control" id="bus_details" name="bus_details">';
            echo '<option value="">Select a bus</option>';
            for ($i = 1; $i <= $row['number_of_buses']; $i++) {
                $bus_num_key = "bus_number_$i";
                $bus_make_key = "bus_make_$i";
                $bus_emission_key = "bus_emission_norms_$i";
                if ($row[$bus_num_key] !== null) {
                    echo '<option value="' . $row[$bus_num_key] . '">' . $row[$bus_num_key] . ' (' . $row[$bus_make_key] . ', ' . $row[$bus_emission_key] . ')</option>';
                }
            }
            echo '</select>';
            echo '</div>';

            // Display driver details
            echo '<div class="form-group col-md-6">';
            echo '<label for="driver_details">Driver Details:</label>';
            echo '<select class="form-control" id="driver_details" name="driver_details">';
            echo '<option value="">Select drivers</option>';
            for ($i = 1; $i <= 3; $i++) {
                $driver_token_key = "driver_token_1_$i";
                $driver_pf_key = "driver_pf_1_$i";
                $driver_name_key = "driver_name_1_$i";
                if ($row[$driver_token_key] !== null) {
                    echo '<option value="' . $row[$driver_token_key] . '">' . $row[$driver_token_key] . ' (' . $row[$driver_pf_key] . ', ' . $row[$driver_name_key] . ')</option>';
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $driver_token_key = "driver_token_2_$i";
                $driver_pf_key = "driver_pf_2_$i";
                $driver_name_key = "driver_name_2_$i";
                if ($row[$driver_token_key] !== null) {
                    echo '<option value="' . $row[$driver_token_key] . '">' . $row[$driver_token_key] . ' (' . $row[$driver_pf_key] . ', ' . $row[$driver_name_key] . ')</option>';
                }
            }
            for ($i = 1; $i <= 2; $i++) {
                $half_releiver_token_key = "half_releiver_token_$i";
                $half_releiver_pf_key = "half_releiver_pf_$i";
                $half_releiver_name_key = "half_releiver_name_$i";
                if ($row[$half_releiver_token_key] !== null) {
                    echo '<option value="' . $row[$half_releiver_token_key] . '">' . $row[$half_releiver_token_key] . ' (' . $row[$half_releiver_pf_key] . ', ' . $row[$half_releiver_name_key] . ') (Half Reliever)</option>';
                }
            }
            echo '</select>';
            echo '</div>';
            echo '</div>';

            echo '<div class="form-row">';
            $count = 0;
            foreach ($row as $key => $value) {
                if ($value !== null && !in_array($key, ['id', 'submitted_datetime', 'number_of_buses', 'bus_make_1', 'bus_make_2', 'bus_emission_norms_1', 'bus_emission_norms_2', 'driver_token_1_1', 'driver_pf_1_1', 'driver_name_1_1', 'driver_token_1_2', 'driver_pf_1_2', 'driver_name_1_2', 'driver_token_1_3', 'driver_pf_1_3', 'driver_name_1_3', 'driver_token_2_1', 'driver_pf_2_1', 'driver_name_2_1', 'driver_token_2_2', 'driver_pf_2_2', 'driver_name_2_2', 'driver_token_2_3', 'driver_pf_2_3', 'driver_name_2_3', 'half_releiver_token_1', 'half_releiver_pf_1', 'half_releiver_name_1', 'half_releiver_token_2', 'half_releiver_pf_2', 'half_releiver_name_2', 'division_id', 'depot_id', 'bus_number_1', 'bus_number_2'])) {
                    echo '<div class="form-group col-md-6">';
                    echo '<label for="' . $key . '">' . ucfirst(str_replace('_', ' ', $key)) . ':</label>';
                    echo '<input type="text" class="form-control" id="' . $key . '" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
                    echo '</div>';
                    $count++;
                    if ($count % 2 == 0) {
                        echo '</div><div class="form-row">';
                    }
                }
            }
            echo '</div>';
            echo '<button type="submit" class="btn btn-success">Add Schedule to Route</button>';
            echo '</form>';
        } else {
            echo '<p class="alert alert-danger mt-4">No schedule found for the given key number.</p>';
        }
        mysqli_stmt_close($stmt);
        mysqli_close($db);
    }
    ?>
</div>


<?php include '../includes/footer.php'; ?>
