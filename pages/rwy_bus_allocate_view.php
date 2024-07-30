<?php
include '../includes/connection.php';
include '../includes/rwy_sidebar.php';

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
    <?php } elseif ($Aa == 'HAD-OFFICE') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to RWY Page");
            window.location = "rwy.php";
        </script>
    <?php }elseif ($Aa == 'RWY') {
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

?>

<div class="container-fluid">
    <h4 class="m-2 font-weight-bold text-primary">Buses</h4>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
            <table id="dataTable" >
    <thead>
        <tr>
            <th style="width:2%">Serial Number</th>
            <th>Division Name</th>
            <th>Make</th>
            <th>Emission norms</th>
            <th>Wheel Base</th>
            <th>Chassis Number</th>
            <th>Bus Category</th>
            <th>Bus Sub Category</th>
            <th>Seating Capacity</th>
            <th>Bus Body Builder</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $query = 'SELECT DISTINCT(l.division) AS division_name, br.make, br.emission_norms, br.wheel_base, br.chassis_number, br.bus_category, br.bus_sub_category, br.seating_capacity, br.bus_body_builder 
        FROM rwy_bus_allocation br 
        INNER JOIN location l ON br.division = l.division_id';
        $result = mysqli_query($db, $query) or die(mysqli_error($db));
        $serial_number = 1; // Initialize serial number counter
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . $serial_number++ . '</td>'; // Increment serial number for each row
            echo '<td>' . $row['division_name'] . '</td>';
            echo '<td>' . $row['make'] . '</td>';
            echo '<td>' . $row['emission_norms'] . '</td>';
            echo '<td>' . $row['wheel_base'] . '</td>';
            echo '<td>' . $row['chassis_number'] . '</td>';
            echo '<td>' . $row['bus_category'] . '</td>';
            echo '<td>' . $row['bus_sub_category'] . '</td>';
            echo '<td>' . $row['seating_capacity'] . '</td>';
            echo '<td>' . $row['bus_body_builder'] . '</td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>