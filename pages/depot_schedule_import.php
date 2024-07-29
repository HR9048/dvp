<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

$query = 'SELECT ID, t.TYPE FROM users u JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

$redirected = false; // Flag to track if redirection occurred

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DEPOT' && !$redirected) {
        $redirected = true; // Set flag to true
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Depot Page");
            window.location = "../includes/depot_verify.php";
        </script>
    <?php } elseif ($Aa == 'DIVISION' && !$redirected) {
        $redirected = true; // Set flag to true
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
        </script>
    <?php } elseif ($Aa == 'RWY') {
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to RWY Page");
            window.location = "rwy.php";
        </script>
    <?php } elseif ($_SESSION['TYPE'] == 'HEAD-OFFICE') {
        // Check the job title of the user
        if ($_SESSION['JOB_TITLE'] == 'CO_STORE') {
            ?>
            <script type="text/javascript">
                // Redirect to depot_clerk.php if the job title is Clerk
                alert("Restricted Page! You will be redirected to Stores Page");
                window.location = "index.php";
            </script>
            <?php
        }
    }
}

// CSV upload form
if (isset($_POST['submit'])) {
    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, "r");

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $division_id = $data[0];
        $depot_id = $data[1];
        $sch_key_no = $data[2];
        $sch_abbr = $data[3];
        $sch_km = $data[4];
        $sch_dep_time = substr($data[5], 0, 5); // Extract HH:MM
        $sch_arr_time = substr($data[6], 0, 5); // Extract HH:MM
        $service_class_id = $data[7];
        $service_type_id = $data[8];

        // Determine schedule count based on service type id
        if ($service_type_id == 1 || $service_type_id == 2) {
            $sch_count = 1;
        } elseif ($service_type_id == 3 || $service_type_id == 4 || $service_type_id == 5) {
            $sch_count = 2;
        } else {
            $sch_count = 0; // Default value if service type id is not 1-5
        }
        if ($service_type_id == 1 || $service_type_id == 2) {
            $number_of_buses = 1;
        } elseif ($service_type_id == 3 || $service_type_id == 4 || $service_type_id == 5) {
            $number_of_buses = 2;
        } else {
            $number_of_buses = 0; // Default value if service type id is not 1-5
        }

        $query = "INSERT INTO schedule_master (division_id, depot_id, sch_key_no, sch_abbr, sch_km, sch_dep_time, sch_arr_time, sch_count,number_of_buses, service_class_id, service_type_id) VALUES ('$division_id', '$depot_id', '$sch_key_no', '$sch_abbr', '$sch_km', '$sch_dep_time', '$sch_arr_time', '$sch_count', '$number_of_buses', '$service_class_id', '$service_type_id')";
        mysqli_query($db, $query) or die(mysqli_error($db));

        $query1 = "INSERT INTO schedule_master_import (division_id, depot_id, sch_key_no, sch_abbr, sch_km, sch_dep_time, sch_arr_time, sch_count,number_of_buses, service_class_id, service_type_id) VALUES ('$division_id', '$depot_id', '$sch_key_no', '$sch_abbr', '$sch_km', '$sch_dep_time', '$sch_arr_time', '$sch_count', '$number_of_buses',  '$service_class_id', '$service_type_id')";
        mysqli_query($db, $query1) or die(mysqli_error($db));
    }

    fclose($handle);
    echo '<script>alert("CSV file data successfully imported.");</script>';
    echo '<script>window.location.href = "depot_schedule_import.php";</script>';
    exit;
}
?>

<form method="post" enctype="multipart/form-data">
    <label>Select CSV File:</label>
    <input type="file" name="file" accept=".csv" required>
    <button type="submit" name="submit">Upload</button>
</form>

<?php include '../includes/footer.php'; ?>
