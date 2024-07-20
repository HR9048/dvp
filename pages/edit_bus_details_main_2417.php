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

// If redirection did not occur, continue with the rest of the page
if (!$redirected) {
    ?>
    <style>
        .hidden {
    display: none;
}

    </style>
    <div class="container-fluid">
        <h4 class="m-2 font-weight-bold text-primary">Buses</h4>
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <style>
                        /* Custom CSS for table column lines */
                        #dataTable th,
                        #dataTable td {
                            border: 2px solid #dee2e6;
                            /* Adjust the thickness and color as needed */
                        }
                    </style>
                    <table id="dataTable">
                        <thead>
                            <tr>
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
                                <th>Action</th> <!-- New column for actions -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = 'SELECT b.bus_number, l.division, l.depot, b.make, b.emission_norms, b.doc, b.wheel_base, b.division_name,b.depot_name,
b.chassis_number, b.bus_category, b.bus_sub_category, b.seating_capacity, b.bus_body_builder 
FROM bus_registration b
INNER JOIN location l ON b.depot_name = l.depot_id';
                            $result = mysqli_query($db, $query) or die(mysqli_error($db));
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<tr>';
                                echo '<td>' . $row['bus_number'] . '</td>';
                                echo '<td>' . $row['division'] . '</td>';
                                echo '<td>' . $row['depot'] . '</td>';
                                echo '<td>' . $row['make'] . '</td>';
                                echo '<td>' . $row['emission_norms'] . '</td>';
                                echo '<td>' . $row['doc'] . '</td>';
                                echo '<td>' . $row['wheel_base'] . '</td>';
                                echo '<td>' . $row['chassis_number'] . '</td>';
                                echo '<td>' . $row['bus_category'] . '</td>';
                                echo '<td>' . $row['bus_sub_category'] . '</td>';
                                echo '<td>' . $row['seating_capacity'] . '</td>';
                                echo '<td>' . $row['bus_body_builder'] . '</td>';
                                echo '<td><button class="btn btn-primary editBtn" data-busnumber="' . $row['bus_number'] . '">Edit</button></td>'; // Edit button with data attribute
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
}

include '../includes/footer.php';
?>

<!-- Modal for editing bus details -->
<div class="modal fade" id="editBusModal" tabindex="-1" role="dialog" aria-labelledby="editBusModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBusModalLabel">Edit Bus Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form for editing bus details -->
                <form id="editBusForm">
                    <!-- Input fields for bus details -->
                    <div class="form-group">
                        <label for="busNumber">Bus Number</label>
                        <input type="text" class="form-control" id="busNumber" name="busNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="newBusNumber">New Bus Number</label>
                        <input type="text" class="form-control" id="newBusNumber" name="newBusNumber" required>
                    </div>

                    <div class="form-group">
                        <label for="divisionName">Division Name</label>
                        <input type="text" class="form-control" id="divisionName" name="divisionName" required>
                    </div>
                    <div class="form-group">
                        <label for="depotName">Depot Name</label>
                        <input type="text" class="form-control" id="depotName" name="depotName" required>
                    </div>
                    <div class="form-group">
                        <label for="make">Make</label>
                        <input type="text" class="form-control" id="make" name="make" required>
                    </div>
                    <div class="form-group">
                        <label for="emissionNorms">Emission Norms</label>
                        <input type="text" class="form-control" id="emissionNorms" name="emissionNorms" required>
                    </div>
                    <div class="form-group">
                        <label for="doc">DOC</label>
                        <input type="date" class="form-control" id="doc" name="doc" required>
                    </div>
                    <div class="form-group">
                        <label for="wheelBase">Wheel Base</label>
                        <input type="text" class="form-control" id="wheelBase" name="wheelBase" required>
                    </div>
                    <div class="form-group">
                        <label for="chassisNumber">Chassis Number</label>
                        <input type="text" class="form-control" id="chassisNumber" name="chassisNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="busCategory">Bus Category</label>
                        <input type="text" class="form-control" id="busCategory" name="busCategory" required>
                    </div>
                    <div class="form-group">
                        <label for="busSubCategory">Bus Sub Category</label>
                        <input type="text" class="form-control" id="busSubCategory" name="busSubCategory" required>
                    </div>
                    <div class="form-group">
                        <label for="seatingCapacity">Seating Capacity</label>
                        <input type="number" class="form-control" id="seatingCapacity" name="seatingCapacity" required>
                    </div>
                    <div class="form-group">
                        <label for="busBodyBuilder">Bus Body Builder</label>
                        <input type="text" class="form-control" id="busBodyBuilder" name="busBodyBuilder" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateBusBtn">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Function to open the modal and populate data
        $('#dataTable tbody').on('click', 'tr', function () {
            // Get the bus details from the clicked row
            var busNumber = $(this).find('td:eq(0)').text();
            var divisionName = $(this).find('td:eq(1)').text();
            var depotName = $(this).find('td:eq(2)').text();
            var make = $(this).find('td:eq(3)').text();
            var emissionNorms = $(this).find('td:eq(4)').text();
            var doc = $(this).find('td:eq(5)').text();
            var wheelBase = $(this).find('td:eq(6)').text();
            var chassisNumber = $(this).find('td:eq(7)').text();
            var busCategory = $(this).find('td:eq(8)').text();
            var busSubCategory = $(this).find('td:eq(9)').text();
            var seatingCapacity = $(this).find('td:eq(10)').text();
            var busBodyBuilder = $(this).find('td:eq(11)').text();
            var divisionID = $(this).find('td:eq(13)').text();
            var depotID = $(this).find('td:eq(14)').text();


            // Populate the modal fields with the bus details
            $('#busNumber').val(busNumber);
            $('#newBusNumber').val(busNumber);
            $('#divisionName').val(divisionName);
            $('#depotName').val(depotName);
            $('#make').val(make);
            $('#emissionNorms').val(emissionNorms);
            $('#doc').val(doc);
            $('#wheelBase').val(wheelBase);
            $('#chassisNumber').val(chassisNumber);
            $('#busCategory').val(busCategory);
            $('#busSubCategory').val(busSubCategory);
            $('#seatingCapacity').val(seatingCapacity);
            $('#busBodyBuilder').val(busBodyBuilder);
            $('#divisionID').val(divisionID);
            $('#depotID').val(depotID);


            // Show the modal
            $('#editBusModal').modal('show');
        });

        // Function to handle bus update
        $('#updateBusBtn').click(function () {
            // Collect updated bus details from the modal
            var busNumber = $('#busNumber').val();
            var newBusNumber = $('#newBusNumber').val();
            var division = $('#divisionName').val();
            var depot = $('#depotName').val();
            var make = $('#make').val();
            var emissionNorms = $('#emissionNorms').val();
            var doc = $('#doc').val();
            var wheelBase = $('#wheelBase').val();
            var chassisNumber = $('#chassisNumber').val();
            var busCategory = $('#busCategory').val();
            var busSubCategory = $('#busSubCategory').val();
            var seatingCapacity = $('#seatingCapacity').val();
            var busBodyBuilder = $('#busBodyBuilder').val();
            var divisionID = $('#divisionID').val();
            var depotID = $('#depotID').val();


            // AJAX call to update the bus details in the database
            $.ajax({
                url: 'master_update_bus_details.php',
                method: 'POST',
                data: {
                    busNumber: busNumber,
                    newBusNumber: newBusNumber, // Add this line
                    division: division,
                    depot: depot,
                    make: make,
                    emissionNorms: emissionNorms,
                    doc: doc,
                    wheelBase: wheelBase,
                    chassisNumber: chassisNumber,
                    busCategory: busCategory,
                    busSubCategory: busSubCategory,
                    seatingCapacity: seatingCapacity,
                    busBodyBuilder: busBodyBuilder,
                    divisionID: divisionID,
                    depotID: depotID
                },

                success: function (response) {
                    // Close the modal
                    $('#editBusModal').modal('hide');
                    // Reload the page to reflect the changes
                    location.reload();
                },
                error: function () {
                    alert('Error occurred while updating bus details.');
                }
            });
        });
    });
</script>