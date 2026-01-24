<?php
include 'ad_nav.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {

    // If redirection did not occur, continue with the rest of the page
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
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include 'ad_footer.php';
?>

<!-- Modal for editing bus details -->
<div class="modal fade" id="editBusModal" tabindex="-1" role="dialog" aria-labelledby="editBusModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBusModalLabel">Edit Bus Details</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form for editing bus details -->
                <form id="editBusForm">
                    <!-- Input fields for bus details -->
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="busNumber">Bus Number</label>
                                <input type="text" class="form-control" id="busNumber" name="busNumber" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="newBusNumber">New Bus Number</label>
                                <input type="text" class="form-control" id="newBusNumber" name="newBusNumber"
                                    pattern="[K][A]\d{2}[F]\d{4}" title="Enter a valid bus number" required
                                    oninput="this.value = this.value.toUpperCase()">
                                <small class="form-text text-muted">Example: KA--F----</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="divisionName">Division Name</label>
                                <input type="text" class="form-control" id="divisionName" name="divisionName" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="depotName">Depot Name</label>
                                <input type="text" class="form-control" id="depotName" name="depotName" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="make">Make</label>
                                <select class="form-control" id="make" name="make" required>
                                    <?php
                                    $sqlformake = "SELECT make FROM makes ORDER BY make ASC";
                                    $resultformake = mysqli_query($db, $sqlformake);
                                    while ($rowmake = mysqli_fetch_assoc($resultformake)) {
                                        echo '<option value="' . $rowmake['make'] . '">' . $rowmake['make'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="emissionNorms">Emission Norms</label>
                                <select class="form-control" id="emissionNorms" name="emissionNorms" required>
                                    <?php
                                    $sqlforen = "SELECT emission_norms FROM norms ORDER BY emission_norms ASC";
                                    $resultforen = mysqli_query($db, $sqlforen);
                                    while ($rowen = mysqli_fetch_assoc($resultforen)) {
                                        echo '<option value="' . $rowen['emission_norms'] . '">' . $rowen['emission_norms'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="doc">DOC</label>
                                <input type="date" class="form-control" id="doc" name="doc" required max="<?php echo date('Y-m-d')  ?>" min="<?php echo date('Y-m-d', strtotime('-15 years')); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="wheelBase">Wheel Base</label>
                                <select class="form-control" id="wheelBase" name="wheelBase" required>
                                    <?php
                                    $sqlforwb = "SELECT wheel_base FROM wheelbase ORDER BY wheel_base ASC";
                                    $resultforwb = mysqli_query($db, $sqlforwb);
                                    while ($rowwb = mysqli_fetch_assoc($resultforwb)) {
                                        echo '<option value="' . $rowwb['wheel_base'] . '">' . $rowwb['wheel_base'] . '</option>';
                                    }
                                    $categories = [];

                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="chassisNumber">Chassis Number</label>
                                <input type="text" class="form-control" id="chassisNumber" name="chassisNumber"
                                    pattern="[A-Z0-9]{10,20}" title="Enter a valid chassis number" required
                                    oninput="this.value = this.value.toUpperCase()">
                                <small id="note" class="form-text text-muted" style="display: none;">Note: Enter 17 character
                                    Chassis number</small>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="busCategory">Bus Category</label>
                                <select class="form-control" id="busCategory" name="busCategory" required>
                                    <option value="">-- Select Bus Category --</option>

                                    <?php

                                    $catResult = mysqli_query($db, "SELECT DISTINCT bus_category, bus_sub_category FROM bus_seat_category ORDER BY bus_category, bus_sub_category");

                                    while ($row = mysqli_fetch_assoc($catResult)) {
                                        $categories[$row['bus_category']][] = $row['bus_sub_category'];
                                    }
                                    foreach ($categories as $category => $subs): ?>
                                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                    <?php endforeach; ?>

                                </select>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label for="busSubCategory">Bus Sub Category</label>
                                <select class="form-control" id="busSubCategory" name="busSubCategory" required>
                                    <option value="">-- Select Bus Sub Category --</option>
                                    <!-- Filled by JS -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="col">
                                <div class="form-group">
                                    <label for="seatingCapacity">Seating Capacity</label>
                                    <input type="number" class="form-control" id="seatingCapacity" name="seatingCapacity" required>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="busBodyBuilder">Bus Body Builder</label>
                                <select class="form-control" id="busBodyBuilder" name="busBodyBuilder" required>
                                    <?php
                                    $sqlforbbb = "SELECT `body_type` FROM bus_body_builder ORDER BY `body_type` ASC";
                                    $resultforbbb = mysqli_query($db, $sqlforbbb);
                                    while ($rowbbb = mysqli_fetch_assoc($resultforbbb)) {
                                        echo '<option value="' . $rowbbb['body_type'] . '">' . $rowbbb['body_type'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateBusBtn">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    const categoryMap = <?= json_encode($categories); ?>;

    // GLOBAL variable
    let currentSubCategory = '';

    $('#busCategory').on('change', function() {
        const category = $(this).val();
        const subSelect = $('#busSubCategory');

        subSelect.html('<option value="">-- Select Bus Sub Category --</option>');

        if (categoryMap[category]) {
            categoryMap[category].forEach(sub => {
                subSelect.append(
                    `<option value="${sub}">${sub}</option>`
                );
            });
        }
    });

    // 🔁 Load sub-category on page load (edit mode)
    $('#busCategory').trigger('change');

    $(document).ready(function() {
        // Function to open the modal and populate data
        $('#dataTable tbody').on('click', 'tr', function() {
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

            $('#busCategory').val(busCategory).trigger('change');
            currentSubCategory = busSubCategory;
            $('#busSubCategory').val(busSubCategory);
            // Show the modal
            $('#editBusModal').modal('show');
        });

        // Function to handle bus update
        $('#updateBusBtn').click(function() {
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

            var fields = [{
                    id: 'newBusNumber',
                    label: 'New Bus Number'
                },
                {
                    id: 'divisionName',
                    label: 'Division'
                },
                {
                    id: 'depotName',
                    label: 'Depot'
                },
                {
                    id: 'make',
                    label: 'Make'
                },
                {
                    id: 'emissionNorms',
                    label: 'Emission Norms'
                },
                {
                    id: 'doc',
                    label: 'Registration Date'
                },
                {
                    id: 'wheelBase',
                    label: 'Wheel Base'
                },
                {
                    id: 'chassisNumber',
                    label: 'Chassis Number'
                },
                {
                    id: 'busCategory',
                    label: 'Bus Category'
                },
                {
                    id: 'busSubCategory',
                    label: 'Bus Sub Category'
                },
                {
                    id: 'seatingCapacity',
                    label: 'Seating Capacity'
                },
                {
                    id: 'busBodyBuilder',
                    label: 'Bus Body Builder'
                }
            ];

            let missing = [];

            // Remove old error highlights
            $('.is-invalid').removeClass('is-invalid');

            fields.forEach(field => {
                const value = $('#' + field.id).val();

                if (!value || value.trim() === '') {
                    missing.push(field.label);
                    $('#' + field.id).addClass('is-invalid');
                }
            });

            if (missing.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Required Fields',
                    html: `
            <p>Please fill the following fields:</p>
            <ul style="text-align:left">
                ${missing.map(f => `<li>${f}</li>`).join('')}
            </ul>
        `
                });
                return;
            }
            //validate the bus bus number format
            const busNumberPattern = /^[K][A]\d{2}[F]\d{4}$/;
            if (!busNumberPattern.test(newBusNumber)) {
                //show error in sweetalrt
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Bus Number',
                    text: 'Please enter a valid bus number in the format KA--F----.'
                });
                $('#newBusNumber').addClass('is-invalid');
                return;
            }
            //validate DOC is not in future and not older than 15 years
            const docDate = new Date(doc);
            const today = new Date();
            const pastDate = new Date();
            pastDate.setFullYear(today.getFullYear() - 25);
            if (docDate > today || docDate < pastDate) {
                //show error in sweetalrt
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Registration Date',
                    text: 'Please enter a valid registration date not in the future and not older than 15 years.'
                });
                $('#doc').addClass('is-invalid');
                return;
            }

            //validate chassis number length
            if (chassisNumber.length < 10 || chassisNumber.length > 20) {
                //show error in sweetalrt
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Chassis Number',
                    text: 'Please enter a valid chassis number between 10 to 20 characters.'
                });
                $('#chassisNumber').addClass('is-invalid');
                return;
            }



            Swal.fire({
                title: 'Confirm Update',
                text: 'Are you sure you want to update the bus details?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Update',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: '../includes/backend_data.php',
                        method: 'POST',
                        dataType: 'json', // 🔴 IMPORTANT
                        data: {
                            busNumber: busNumber,
                            newBusNumber: newBusNumber,
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
                            depotID: depotID,
                            action: 'updateBusDetails'
                        },

                        beforeSend: function() {
                            Swal.fire({
                                title: 'Updating...',
                                text: 'Please wait',
                                allowOutsideClick: false,
                                didOpen: () => Swal.showLoading()
                            });
                        },

                        success: function(response) {
                            Swal.close();

                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Updated Successfully',
                                    text: response.message || 'Bus details updated'
                                }).then(() => {
                                    $('#editBusModal').modal('hide');
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Update Failed',
                                    response.message || 'Something went wrong',
                                    'error'
                                );
                            }
                        },

                        error: function(xhr) {
                            Swal.close();
                            Swal.fire(
                                'Server Error',
                                xhr.responseText || 'Unable to update bus details',
                                'error'
                            );
                        }
                    });
                }
            });

        });
    });
</script>