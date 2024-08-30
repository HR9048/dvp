<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech') {
    // Allow access

    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h4 class="m-2 font-weight-bold text-primary">Buses&nbsp;<a href="register1.php" type="button"
                    class="btn btn-primary bg-gradient-primary" style="border-radius: 0px;"><i
                        class="fas fa-fw fa-plus"></i></a></h4>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable3">
                    <thead>
                        <tr>
                            <th class="serial-no">Sl No.</th>
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
                            <!-- <th>Action</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Prepare the SQL query with a WHERE condition
                        $query = 'SELECT bus_number, division_name, depot_name, make, emission_norms, doc, wheel_base, chassis_number, bus_category, bus_sub_category, seating_capacity, bus_body_builder FROM bus_registration WHERE depot_name = ? AND division_name = ?';

                        // Prepare the statement
                        $stmt = $db->prepare($query);

                        // Bind the depot value and division name from the session variables to the statement
                        $stmt->bind_param("ss", $_SESSION['DEPOT_ID'], $_SESSION['DIVISION_ID']);

                        // Execute the statement
                        $stmt->execute();

                        // Get the result
                        $result = $stmt->get_result();

                        // Initialize counter variable
                        $counter = 1;

                        // Fetch and display the results as needed
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $counter++ . '</td>'; // Output and increment counter
                            echo '<td>' . $row['bus_number'] . '</td>';
                            echo '<td>' . $_SESSION['DIVISION'] . '</td>';
                            echo '<td>' . $_SESSION['DEPOT'] . '</td>';
                            echo '<td>' . $row['make'] . '</td>';
                            echo '<td>' . $row['emission_norms'] . '</td>';
                            echo '<td>' . date('d/m/Y', strtotime($row['doc'])) . '</td>';
                            echo '<td>' . $row['wheel_base'] . '</td>';
                            echo '<td>' . $row['chassis_number'] . '</td>';
                            echo '<td>' . $row['bus_category'] . '</td>';
                            echo '<td>' . $row['bus_sub_category'] . '</td>';
                            echo '<td>' . $row['seating_capacity'] . '</td>';
                            echo '<td>' . $row['bus_body_builder'] . '</td>';
                            // Add any additional columns or actions as needed
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

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>