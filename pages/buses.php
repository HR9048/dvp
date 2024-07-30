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

// Fetch all rows from the database for the table and for JavaScript
$query = 'SELECT br.bus_number, l.division AS division_name, l.depot AS depot_name, br.make, br.emission_norms, br.doc, br.wheel_base, br.chassis_number, br.bus_category, br.bus_sub_category, br.seating_capacity, br.bus_body_builder 
FROM bus_registration br 
INNER JOIN location l ON br.division_name = l.division_id AND br.depot_name = l.depot_id';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

?>
<div class="container-fluid">
    <h4 class="m-2 font-weight-bold text-primary" style="display: inline-block;">Buses</h4>
    <button class="btn btn-primary ml-3"><a href="main_bus_transfer.php"
        style="color: white; text-decoration: none;">Bus Transfer</a></button>
    <button class="btn btn-primary ml-3"><a href="main_rwy_alllocation.php" style="color: white; text-decoration: none;">RWY Bus Allocation</a></button>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="container1">
                <table id="dataTable3">
                    <thead>
                        <tr>
                            <th style="width:5%">Serial Number</th>
                            <th>Bus Number</th>
                            <th>Division Name</th>
                            <th>Depot Name</th>
                            <th>Make</th>
                            <th>Emission norms</th>
                            <th>DOC</th>
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
                        $serial_number = 1;
                        foreach ($rows as $row) {
                            echo '<tr>';
                            echo '<td>' . $serial_number++ . '</td>';
                            echo '<td>' . $row['bus_number'] . '</td>';
                            echo '<td>' . $row['division_name'] . '</td>';
                            echo '<td>' . $row['depot_name'] . '</td>';
                            echo '<td>' . $row['make'] . '</td>';
                            echo '<td>' . $row['emission_norms'] . '</td>';
                            echo '<td>' . date('d/m/Y', strtotime($row['doc'])) . '</td>';
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
            <button class="btn btn-success" id="downloadExcel1">Download Excel</button>
        </div>
    </div>
</div>




<?php include '../includes/footer.php'; ?>
