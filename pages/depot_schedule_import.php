<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    // Allow access

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
            $status='1';
            // Determine schedule count based on service type id
            if ($service_type_id == 1 || $service_type_id == 2) {
                $sch_count = 1;
            } elseif ($service_type_id == 3 || $service_type_id == 4  ) {
                $sch_count = 2;
            } else {
                $sch_count = 0; // Default value if service type id is not 1-5
            }
            if ($service_type_id == 1|| $service_type_id == 2) {
                $number_of_buses = 1;
            } elseif ($service_type_id == 3 || $service_type_id == 4 ) {
                $number_of_buses = 2;
            } else {
                $number_of_buses = 0; // Default value if service type id is not 1-5
            }

            $query = "INSERT INTO schedule_master (division_id, depot_id, sch_key_no, sch_abbr, sch_km, sch_dep_time, sch_arr_time, sch_count,number_of_buses, service_class_id, service_type_id,status) VALUES ('$division_id', '$depot_id', '$sch_key_no', '$sch_abbr', '$sch_km', '$sch_dep_time', '$sch_arr_time', '$sch_count', '$number_of_buses', '$service_class_id', '$service_type_id',$status)";
            mysqli_query($db, $query) or die(mysqli_error($db));
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

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>