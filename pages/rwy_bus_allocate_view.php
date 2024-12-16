<?php
include '../includes/connection.php';
include '../includes/rwy_sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'RWY' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    // Allow access
    ?>


    <div class="container-fluid">
        <h4 class="m-2 font-weight-bold text-primary">Buses</h4>
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dataTable">
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

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>