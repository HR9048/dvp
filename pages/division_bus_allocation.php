<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME1') {
    // Allow access
    ?>

    <style>
        .hidden-column {
            display: none;
        }
    </style>

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
                                <th class="hidden-column">Division ID</th>
                                <th>Make</th>
                                <th>Emission norms</th>
                                <th>Wheel Base</th>
                                <th>Chassis Number</th>
                                <th>Bus Category</th>
                                <th>Bus Sub Category</th>
                                <th>Seating Capacity</th>
                                <th>Bus Body Builder</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = 'SELECT DISTINCT l.division_id as divisionid, l.division AS division_name, br.make, br.emission_norms, br.wheel_base, br.chassis_number, br.bus_category, br.bus_sub_category, br.seating_capacity, br.bus_body_builder
                        FROM rwy_bus_allocation br 
                        INNER JOIN location l ON br.division = l.division_id
                        LEFT JOIN bus_registration b ON br.chassis_number = b.chassis_number
                        WHERE l.division_id = ' . $_SESSION['DIVISION_ID'] . '
                        AND (b.chassis_number IS NULL OR b.chassis_number = "")';
                            $result = mysqli_query($db, $query) or die(mysqli_error($db));
                            $serial_number = 1; // Initialize serial number counter
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<tr>';
                                echo '<td>' . $serial_number++ . '</td>'; // Increment serial number for each row
                                echo '<td>' . $row['division_name'] . '</td>';
                                echo '<td class="hidden-column">' . $row['divisionid'] . '</td>';
                                echo '<td>' . $row['make'] . '</td>';
                                echo '<td>' . $row['emission_norms'] . '</td>';
                                echo '<td>' . $row['wheel_base'] . '</td>';
                                echo '<td>' . $row['chassis_number'] . '</td>';
                                echo '<td>' . $row['bus_category'] . '</td>';
                                echo '<td>' . $row['bus_sub_category'] . '</td>';
                                echo '<td>' . $row['seating_capacity'] . '</td>';
                                echo '<td>' . $row['bus_body_builder'] . '</td>';
                                echo '<td><button type="button" class="btn btn-primary receive-btn">Receive</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function () {
            // Handle click event on receive button
            $('.receive-btn').click(function () {
                // Show the receive modal
                $('#receiveModal').modal('show');
            });
        });
    </script>

    <!-- Modal -->
    <div class="modal fade" id="receiveModal" tabindex="-1" role="dialog" aria-labelledby="receiveModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiveModalLabel">Receive Bus</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="receiveForm">
                        <div class="form-group">
                            <label for="busNumber">Bus Number</label>
                            <input type="text" class="form-control" id="bus_number" name="bus_number"
                                pattern="[K][A]\d{2}[F]\d{4}" title="Enter a valid bus number" required
                                oninput="this.value = this.value.toUpperCase()">
                            <small class="form-text text-muted">Example: KA--F----</small>
                        </div>
                        <div class="form-group">
                            <label for="allotedDepot">Alloted Depot</label>
                            <select class="form-control" id="depot" name="depot" required>
                                <option value="">Select Depot</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="doc">DOC</label>
                            <input type="date" class="form-control" id="doc" name="doc" required
                                max="<?php echo date('Y-m-d'); ?>" oninput="checkDate(this)">
                            <script type="text/javascript">
                                function checkDate(input) {
                                    var today = new Date();
                                    var inputDate = new Date(input.value);

                                    if (inputDate > today) {
                                        input.value = today.toISOString().slice(0, 10);
                                    }
                                }
                            </script>
                        </div>
                        <div class="form-group">
                            <label for="division">Division</label>
                            <input type="text" class="form-control" id="division" name="division" readonly>
                        </div>
                        <div class="form-group">
                            <label for="divisionid">Division ID</label>
                            <input type="text" class="form-control" id="divisionid" name="divisionid" readonly>
                        </div>
                        <div class="form-group">
                            <label for="make">Make</label>
                            <input type="text" class="form-control" id="make" name="make" readonly>
                        </div>
                        <div class="form-group">
                            <label for="emissionNorms">Emission Norms</label>
                            <input type="text" class="form-control" id="emissionNorms" name="emissionNorms" readonly>
                        </div>
                        <div class="form-group">
                            <label for="wheelBase">Wheel Base</label>
                            <input type="text" class="form-control" id="wheelBase" name="wheelBase" readonly>
                        </div>
                        <div class="form-group">
                            <label for="chassisNumber">Chassis Number</label>
                            <input type="text" class="form-control" id="chassisNumber" name="chassisNumber" readonly>
                        </div>
                        <div class="form-group">
                            <label for="busCategory">Bus Category</label>
                            <input type="text" class="form-control" id="busCategory" name="busCategory" readonly>
                        </div>
                        <div class="form-group">
                            <label for="busSubCategory">Bus Sub Category</label>
                            <input type="text" class="form-control" id="busSubCategory" name="busSubCategory" readonly>
                        </div>
                        <div class="form-group">
                            <label for="seatingCapacity">Seating Capacity</label>
                            <input type="text" class="form-control" id="seatingCapacity" name="seatingCapacity" readonly>
                        </div>
                        <div class="form-group">
                            <label for="busBodyBuilder">Bus Body Builder</label>
                            <input type="text" class="form-control" id="busBodyBuilder" name="busBodyBuilder" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            // Function to fetch depots based on the division ID
            function fetchDepots(divisionId) {
                $.ajax({
                    url: '../includes/data_fetch.php?action=fetchDepot',
                    method: 'POST',
                    data: { division: divisionId },
                    success: function (data) {

                        // Parse the received data into jQuery elements
                        var $data = $(data);

                        // Filter out the option where the displayed text is 'DIVISION'
                        var filteredData = $data.filter(function () {
                            var text = $(this).text().trim();
                            return text.toUpperCase() !== 'DIVISION';
                        });

                        // Debug the filtered options
                        filteredData.each(function () {
                        });

                        // Update the depot select element with the filtered data
                        $('#depot').html(filteredData);
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching depots:', error); // Debugging information
                    }
                });
            }

            // Handle click event on receive button
            $('.receive-btn').click(function () {
                // Get the row associated with the clicked button
                var row = $(this).closest('tr');

                // Extract data from the row
                var division = row.find('td:eq(1)').text();
                var division_id = row.find('td:eq(2)').text();
                var make = row.find('td:eq(3)').text();
                var emissionNorms = row.find('td:eq(4)').text();
                var wheelBase = row.find('td:eq(5)').text();
                var chassisNumber = row.find('td:eq(6)').text();
                var busCategory = row.find('td:eq(7)').text();
                var busSubCategory = row.find('td:eq(8)').text();
                var seatingCapacity = row.find('td:eq(9)').text();
                var busBodyBuilder = row.find('td:eq(10)').text();

                // Populate the form fields in the modal with the extracted data
                $('#receiveModal #division').val(division);
                $('#receiveModal #divisionid').val(division_id);
                $('#receiveModal #make').val(make);
                $('#receiveModal #emissionNorms').val(emissionNorms);
                $('#receiveModal #wheelBase').val(wheelBase);
                $('#receiveModal #chassisNumber').val(chassisNumber);
                $('#receiveModal #busCategory').val(busCategory);
                $('#receiveModal #busSubCategory').val(busSubCategory);
                $('#receiveModal #seatingCapacity').val(seatingCapacity);
                $('#receiveModal #busBodyBuilder').val(busBodyBuilder);

                // Fetch depots after setting the division ID
                fetchDepots(division_id);

                // Show the receive modal
                $('#receiveModal').modal('show');
            });

            // Fetch depots for the existing division value when the modal is shown
            $('#receiveModal').on('show.bs.modal', function () {
                var existingDivisionId = $('#divisionid').val(); // Corrected to use divisionid
                if (existingDivisionId) {
                    fetchDepots(existingDivisionId);
                }
            });
        });
    </script>
    <script type="text/javascript">
        $(document).ready(function () {
            // Handle form submission
            $('#receiveForm').submit(function (event) {
                // Prevent default form submission
                event.preventDefault();

                // Check if DOC date is empty or not selected
                var docDate = $('#doc').val();
                if (!docDate) {
                    alert("Please select a DOC date.");
                    return;
                }

                // Check if any form field is empty
                var formValid = true;
                $(this).find(':input').each(function () {
                    if ($(this).prop('required') && !$(this).val()) {
                        alert($(this).attr('name') + " is required.");
                        formValid = false;
                        return false; // Exit loop early
                    }
                });

                if (!formValid) {
                    return;
                }

                // Serialize form data
                var formData = $(this).serialize();

                $.ajax({
                    url: 'submit_bus.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json', // Specify JSON data type
                    success: function (response) {
                        if (!response.error) {
                            // Success: Redirect to the success page
                            alert(response.message);
                            window.location.href = "division_bus_allocation.php";
                        } else {
                            // Error: Show error message
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        // Handle other types of errors
                        alert('An error occurred. Please try again later.');
                    }
                });

            });
        });
    </script>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>