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
        <button class="btn btn-primary ml-3"><a href="main_rwy_alllocation.php"
                style="color: white; text-decoration: none;">RWY Bus Allocation</a></button>
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="container1">
                    <table id="dataTable3">
                        <thead>
                            <tr>
                                <th style="width:5%">Sl No</th>
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
                        <tfoot>
                            <tr>
                                <th></th>
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
                        </tfoot>
                    </table>
                </div>
                <button class="btn btn-success" id="downloadExcel1">Download Excel</button>
            </div>
        </div>
    </div>



    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>