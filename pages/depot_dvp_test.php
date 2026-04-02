<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    // Check if session variables are set
    if (!isset($_SESSION['DIVISION']) || !isset($_SESSION['DEPOT'])) {
        die("Error: Division or depot information not found in session.");
    }
    // Retrieve division and depot from session variables
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];
?>

    <style>
        body {
            background: linear-gradient(to right, #e3f2fd, #f9f9f9);
            font-family: 'Poppins', sans-serif;
        }

        .container {
            margin-top: 50px;
            max-width: 90%;
        }

        .step {
            width: 33%;
            text-align: center;
            position: relative;
        }

        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50%;
            width: 100%;
            height: 4px;
            background-color: #dee2e6;
            transform: translateY(-50%);
            z-index: 0;
        }

        .step:last-child::after {
            display: none;
        }

        .circle {
            width: 40px;
            height: 40px;
            background-color: #dee2e6;
            color: #6c757d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            font-weight: 500;
            transition: 0.3s;
        }

        .step.active .circle,
        .step.completed .circle {
            background-color: #0d6efd;
            color: white;
        }

        .step.completed .circle {
            background-color: #198754;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            background: white;
            padding: 30px;
            animation: fadeIn 0.5s ease-in-out;
        }

        .btn {
            border-radius: 25px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>


    <div class="container">
        <!-- Step Progress -->
        <div class="d-flex justify-content-between mb-4">
            <div class="step active" id="indicator1">
                <div class="circle">1</div>
                <small>Offroad</small>
            </div>
            <div class="step" id="indicator2">
                <div class="circle">2</div>
                <small>Breakdown</small>
            </div>
            <div class="step" id="indicator3">
                <div class="circle">3</div>
                <small>DVP</small>
            </div>
        </div>

        <!-- Step 1 -->
        <div class="card" id="step1">
            <?php
            $query = "SELECT id, bus_number, make, emission_norms, off_road_date, off_road_location, parts_required, remarks FROM off_road_data WHERE division = '$division' AND depot = '$depot' AND status='off_road'";
            $result = mysqli_query($db, $query) or die(mysqli_error($db));

            // Initialize variables for rowspan logic
            $bus_number_rowspans = [];

            // Group data by bus number
            while ($row = mysqli_fetch_assoc($result)) {
                $bus_number = $row['bus_number'];
                if (!isset($bus_number_rowspans[$bus_number])) {
                    $bus_number_rowspans[$bus_number] = 0;
                }
                $bus_number_rowspans[$bus_number]++;
            }

            // Reset the result pointer to the beginning
            mysqli_data_seek($result, 0);

            ?>
            <H2>Offroad Vehicles Data</H2>
            <table>
                <thead>
                    <tr>
                        <th>Bus Number</th>
                        <th>Make</th>
                        <th>Emission Norms</th>
                        <th>Off Road From Date</th>
                        <th>Number of days offroad</th>
                        <th>Off Road Location</th>
                        <th>Parts Required</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Loop through each bus number
                    foreach ($bus_number_rowspans as $bus_number => $rowspan_count) {
                        // Loop through each row of the result set for the current bus number
                        $first_row = true; // Flag to indicate if it's the first row for the current bus number
                        while ($row = mysqli_fetch_assoc($result)) {
                            if ($row['bus_number'] == $bus_number) {
                                // Output data in table rows
                                echo "<tr>";
                                if ($first_row) {
                                    // Output rowspan for the first row of the current bus number
                                    echo "<td rowspan='$rowspan_count'>" . $row['bus_number'] . "</td>";
                                    echo "<td rowspan='$rowspan_count'>" . $row['make'] . "</td>";
                                    echo "<td rowspan='$rowspan_count'>" . $row['emission_norms'] . "</td>";
                                    $first_row = false;
                                }
                                // Extract data from the row
                                $offRoadFromDate = $row['off_road_date'];
                                $offRoadLocation = $row['off_road_location'];
                                $partsRequired = $row['parts_required'];
                                $remarks = $row['remarks'];

                                // Calculate the number of days off-road
                                $offRoadDate = new DateTime($offRoadFromDate);
                                $today = new DateTime();
                                $daysOffRoad = $today->diff($offRoadDate)->days;

                                // Output the data in table rows
                                echo "<td>" . date('d/m/y', strtotime($offRoadFromDate)) . "</td>";
                                echo "<td>$daysOffRoad</td>";
                                echo "<td>$offRoadLocation</td>";
                                echo "<td>$partsRequired</td>";
                                echo "<td>$remarks</td>";
                                echo "<td>";
                                echo "<div class='btn-group' role='group'>";
                                echo "<button class='btn btn-success' onclick=\"updateStatus('{$row['id']}', '{$row['bus_number']}')\">OnRoad</button>&nbsp;";
                                echo "<button class='btn btn-primary' onclick=\"editRow('{$row['id']}', '{$row['bus_number']}','{$row['make']}','{$row['emission_norms']}', '{$offRoadLocation}')\"><i class='fas fa-edit'></i></button>";
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                        // Reset the result pointer to the beginning for the next bus number
                        mysqli_data_seek($result, 0);
                    }
                    ?>
                </tbody>
            </table>

            <!-- Modal for editing specific fields -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit Bus Details</h5>
                            <!-- Correct the data-dismiss attribute here -->
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cancelEdit()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Form content goes here -->
                            <form id="editBusForm">
                                <div class="form-group">
                                    <label for="edit_bus_number">Bus Number:</label>
                                    <input type="text" id="edit_bus_number" class="form-control" readonly>
                                </div>
                                <div class="form-group" style="display:none">
                                    <input type="text" id="edit_make" class="form-control" readonly>
                                </div>
                                <div class="form-group" style="display:none">
                                    <input type="text" id="edit_emission_norms" class="form-control" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="edit_offRoadLocation">Off Road Location:</label>
                                    <input type="text" id="edit_offRoadLocation" class="form-control" readonly>
                                </div>
                                <div class="form-group" id="edit_partsRequired_group" style="text-align:justify;">
                                    <label>Parts Required:</label>
                                    <select class="form-control" id="edit_partsRequired" name="edit_partsRequired" required>
                                        <!-- Options here -->
                                    </select>
                                    <!-- Checkboxes will be dynamically added here -->
                                </div>
                                <div class="form-group">
                                    <label for="edit_remarks">Remarks:</label>
                                    <input type="text" id="edit_remarks" class="form-control">
                                </div>

                                <div class="modal-footer">
                                    <!-- Add the Cancel button here -->
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                        onclick="cancelEdit()">Cancel</button>
                                    <!-- Add the Update button here -->
                                    <button type="button" class="btn btn-primary" onclick="insertNewRow()">Update New Part</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Function to handle checkbox selection for partsRequired
                $(document).on('change', '#edit_partsRequired_group input[type="checkbox"]', function() {
                    var checked = $(this).prop('checked');
                    // If this checkbox is checked, deselect all other checkboxes
                    if (checked) {
                        $('#edit_partsRequired_group input[type="checkbox"]').not(this).prop('checked', false);
                    }
                });
                // Function to insert a new row
                function insertNewRow() {
                    var busNumber = $('#edit_bus_number').val();
                    var make = $('#edit_make').val();
                    var emissionNorms = $('#edit_emission_norms').val();
                    var offRoadLocation = $('#edit_offRoadLocation').val();
                    var remarks = $('#edit_remarks').val();

                    // Capture selected checkboxes for partsRequired
                    var partsRequired = [];
                    $('#edit_partsRequired_group input[type="checkbox"]:checked').each(function() {
                        partsRequired.push($(this).val()); // Add checked checkbox value to array
                    });

                    // Convert array of selected parts to comma-separated string
                    var partsRequiredString = partsRequired.join(', ');

                    // Check if all fields are filled out
                    if (busNumber && make && emissionNorms && offRoadLocation && remarks && partsRequired.length > 0) {
                        // Send data to server using AJAX
                        $.ajax({
                            url: 'depot_offroad_upate.php', // Replace with your server-side script URL
                            type: 'POST',
                            data: {
                                busNumber: busNumber,
                                make: make,
                                emissionNorms: emissionNorms,
                                offRoadLocation: offRoadLocation,
                                partsRequired: partsRequiredString,
                                remarks: remarks
                            },
                            success: function(response) {
                                // Handle success response
                                // For example, reload the page or update UI
                                location.reload(); // Reload the page after successful insertion
                            },
                            error: function(xhr, status, error) {
                                // Handle error
                                console.error(error);
                                alert('Failed to insert new row. Please try again.');
                            }
                        });
                    } else {
                        // Alert user if any field is empty
                        alert('All fields are required. Please fill out all fields.');
                    }
                }




                // Function to edit specific fields
                function editRow(id, busNumber, make, norms, offRoadLocation, partsRequired, remarks) {
                    // Fill in the form fields with present data

                    $.ajax({
                        url: '../includes/backend_data.php',
                        method: 'POST',
                        data: {
                            action: 'checkBusOffroadinrwy',
                            busNumber: busNumber
                        },
                        success: function(response) {
                            if (response === 'offroad') {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Not Allowed',
                                    text: 'This bus current Status is off-road in RWY. Editing is not allowed. You can update the status once RWY releases the bus.',
                                });
                            } else {
                                $('#edit_bus_number').val(busNumber);
                                $('#edit_offRoadLocation').val(offRoadLocation);
                                $('#edit_remarks').val(remarks);
                                $('#edit_make').val(make);
                                $('#edit_emission_norms').val(norms);

                                // Clear previous options
                                $('#edit_partsRequired_group').empty();

                                // Fetch parts required options based on off road location
                                $.ajax({
                                    url: '../includes/data_fetch.php?action=fetchReason',
                                    method: 'POST',
                                    data: {
                                        offRoadLocation: offRoadLocation
                                    },
                                    success: function(data) {
                                        // Append options with line breaks
                                        $('#edit_partsRequired_group').html(data.replace(/,/g, '<br>'));

                                        // Check checkboxes based on partsRequired data
                                        if (partsRequired) {
                                            var partsRequiredArray = partsRequired.split(', ');
                                            partsRequiredArray.forEach(function(part) {
                                                $('#edit_partsRequired_group input[value="' + part + '"]').prop('checked', true);
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error fetching parts required:', error);
                                        // Optionally, you can display an error message to the user.
                                    }
                                });

                                // Show the modal
                                $('#editModal').modal('show');
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to verify off-road status. Please try again.',
                            });
                        }
                    });
                }

                function cancelEdit() {
                    $('#editModal').modal('hide'); // Hide the modal using jQuery
                }
            </script>

            <div class="container-fluid mt-5">

                <!-- Add Bus button -->
                <button id="addBtn" class="btn btn-primary mb-3" onclick="openForm()">Add Off Road</button>

                <!-- Bus table -->
                <table id="busTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Bus Number</th>
                            <th>Make</th>
                            <th>Norms</th>
                            <th>Off Road from Date</th>
                            <th>Off Road Location</th>
                            <th>Parts Required</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table rows will be added dynamically using JavaScript -->
                    </tbody>
                </table>

                <!-- Submit button -->
                <div class="text-center">
                    <button id="submitBtn" class="btn btn-primary" style="width: 10%;">Submit</button>
                </div><br><br>
                <div class="text-end" style="text-align: end;">
                    <button type="button" class="btn btn-primary" id="save1"> Next <i class="fa-solid fa-forward fa-lg"></i></button>
                </div>
            </div>
            <!-- Loading Modal -->
            <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden"></span>
                            </div>
                            <p>Submitting, please wait...</p>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.getElementById('submitBtn').addEventListener('click', function() {
                    // Disable the submit button to prevent multiple clicks
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.disabled = true;

                    // Show the loading modal
                    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
                    loadingModal.show();

                    // Simulate form submission process (replace with your actual form submission logic)
                    setTimeout(() => {
                        // Hide the loading modal once submission is complete
                        loadingModal.hide();

                        // Enable the submit button if needed (optional)
                        submitBtn.disabled = false;
                    }, 3000); // Adjust the timeout duration to match your form submission process
                });
            </script>
            <!-- Modal for adding/editing bus -->
            <div id="myModal1" class="modal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitle1">Add Off-Road Details</h5>
                            <button type="button" class="close" onclick="closeForm()" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <nav class="navbar navbar-light bg-light">
                                <div id="searchBar" style="display: flex; align-items: center;">
                                    <input type="text" id="busSearch" type="search" class="form-control mr-sm-2"
                                        placeholder="Search Bus Number">
                                    <button type="button" class="btn btn-outline-success my-2 my-sm-0"
                                        onclick="searchBus()">Search</button>
                                </div>
                            </nav>
                            <form id="busForm">
                                <div class="form-group">
                                    <input type="text" id="bus_number" class="form-control" name="busNumber"
                                        placeholder="Bus Number" required readonly>
                                </div>
                                <div class="form-group">
                                    <input type="text" id="make" class="form-control" name="make" placeholder="Make" readonly>
                                </div>
                                <div class="form-group">
                                    <input type="text" id="emission_norms" class="form-control" name="emission_norms"
                                        placeholder="Norms" readonly>
                                </div>
                                <div class="form-group">
                                    <input type="date" id="date" class="form-control" name="date"
                                        value="<?php echo date('Y-m-d'); ?>" readonly>
                                </div>

                                <div class="form-group">
                                    <select class="form-control" id="offRoadLocation" name="offRoadLocation">
                                        <option value="">Select Off Road Location</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <div class="partsRequired" id="partsRequired" name="partsRequired">
                                        <!-- Checkboxes will be dynamically added here -->
                                    </div>
                                </div>
                                <div id="remarksContainer"></div> <!-- Container for dynamic remarks fields -->
                                <button type="button" class="btn btn-primary" onclick="addOrUpdateBus()">Add Off-Road</button>
                                <button type="button" class="btn btn-secondary" onclick="closeForm()">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Function to fetch off-road location options
                function fetchOffroadLocation() {
                    // Fetch bus categories on page load
                    $.ajax({
                        url: '../includes/data_fetch.php',
                        type: 'GET',
                        data: {
                            action: 'fetchOffroadLocation'
                        },
                        success: function(response) {
                            var Location = JSON.parse(response);
                            $.each(Location, function(index, value) {
                                $('#offRoadLocation').append('<option value="' + value + '">' + value + '</option>');
                            });
                        }
                    });

                    $('#offRoadLocation').change(function() {
                        var Location = $(this).val();
                        $.ajax({
                            url: '../includes/data_fetch.php?action=fetchReason',
                            method: 'POST',
                            data: {
                                offRoadLocation: Location
                            },
                            success: function(data) {
                                // Clear previous options
                                $('#partsRequired').empty();
                                // Append options with line breaks
                                $('#partsRequired').html(data.replace(/,/g, '<br>'));
                                // Clear previous remarks and generate new ones
                                $('#remarksContainer').empty();
                                $('input[name="partsRequired[]"]').change(function() {
                                    generateRemarks();
                                });
                            }
                        });
                    });
                }

                // Function to generate remarks textarea fields based on selected checkboxes
                function generateRemarks() {
                    var remarksContainer = $('#remarksContainer');
                    remarksContainer.empty();
                    $('input[name="partsRequired[]"]:checked').each(function() {
                        var remark = $(this).val();
                        remarksContainer.append('<textarea class="form-control remark" placeholder="Remark for ' + remark + '"></textarea><br>');
                    });
                }


                // Call the function to fetch data on page load
                $(document).ready(function() {
                    fetchOffroadLocation();
                });
                // Function to search for bus
                function searchBus() {
                    var busNumber = $('#busSearch').val();

                    // AJAX request to fetch data
                    $.ajax({
                        url: 'dvp_bus_search.php',
                        type: 'POST',
                        data: {
                            busNumber: busNumber
                        },
                        dataType: 'json', // Specify the expected data type as JSON
                        success: function(response) {
                            // Populate form fields with fetched data
                            $('#bus_number').val(response.bus_number);
                            $('#make').val(response.make);
                            $('#emission_norms').val(response.emission_norms);
                        },
                        error: function(xhr, status, error) {
                            // Display error message
                            if (xhr.status === 403) {
                                alert(xhr.responseJSON.error);
                            } else {
                                alert('Error Bus not Registered in KKRTC.');
                            }
                        }
                    });
                }
                // Function to handle Enter key press in search input field
                $('#busSearch').keypress(function(event) {
                    // Check if the Enter key was pressed
                    if (event.which == 13) {
                        // Prevent default form submission behavior
                        event.preventDefault();
                        // Trigger search function
                        searchBus();
                    }
                });
            </script>


            <script>
                var editRowIndex = -1;

                // Open add/edit bus form
                function openForm() {
                    $('#modalTitle1').text("Add Bus");
                    $('#busForm')[0].reset();
                    editRowIndex = -1;
                    $('#myModal1').modal('show');
                }

                // Close add/edit bus form
                function closeForm() {
                    $('#myModal1').modal('hide');
                }

                // Add or update bus information
                function addOrUpdateBus() {
                    var busNumber = $('#bus_number').val().trim();
                    var make = $('#make').val().trim();
                    var emissionNorms = $('#emission_norms').val().trim();
                    var date = $('#date').val().trim();
                    var offRoadLocation = $('#offRoadLocation').val().trim();

                    // Collect Parts Required data
                    var partsRequired = [];
                    $('input[name="partsRequired[]"]:checked').each(function() {
                        partsRequired.push($(this).val());
                    });

                    // Validate all fields
                    if (busNumber === "" || make === "" || emissionNorms === "" || date === "" || offRoadLocation === "") {
                        alert("Please fill in all fields before adding another row.");
                        return;
                    }

                    // Validate Parts Required field
                    if (partsRequired.length === 0) {
                        alert("Please select at least one option for Parts Required before adding another row.");
                        return;
                    }

                    // Collect remarks from textareas
                    var remarks = [];
                    var anyEmptyRemarks = false; // Flag to track if any remarks are empty
                    $('.remark').each(function() {
                        var remark = $(this).val().trim();
                        if (remark === "") {
                            anyEmptyRemarks = true; // Set flag if any remark is empty
                            return;
                        }
                        remarks.push(remark);
                    });

                    // Check if any remarks are empty
                    if (anyEmptyRemarks) {
                        alert("Please fill in remarks for all selected parts before adding another row.");
                        return;
                    }

                    // Split remarks by line breaks
                    var remarkLines = remarks.join('\n');

                    // Add each row to the table
                    for (var i = 0; i < partsRequired.length; i++) {
                        var formData = {
                            busNumber: busNumber,
                            make: make,
                            emissionNorms: emissionNorms,
                            offRoadFromDate: date,
                            offRoadLocation: offRoadLocation,
                            partsRequired: partsRequired[i],
                            remarks: remarks[i] // Use remarks from corresponding textarea
                        };
                        addBus(formData);
                    }
                }




                // Add bus to table
                function addBus(formData) {
                    var table = document.getElementById("busTable").getElementsByTagName('tbody')[0];
                    var newRow = table.insertRow(table.rows.length);

                    for (var key in formData) {
                        var cell = newRow.insertCell();
                        cell.innerHTML = formData[key];
                    }

                    var actionCell = newRow.insertCell();
                    actionCell.innerHTML = '<button type="button" class="btn btn-danger btn-sm" onclick="removeBus(' + newRow.rowIndex + ')"><i class="fas fa-trash-alt"></i></button>';
                    // actionCell.innerHTML = '<button type="button" class="btn btn-warning btn-sm" onclick="editBus(' + newRow.rowIndex + ')"><i class="fas fa-edit"></i></button>&nbsp;' +
                    //     '<button type="button" class="btn btn-danger btn-sm" onclick="removeBus(' + newRow.rowIndex + ')"><i class="fas fa-trash-alt"></i></button>'; 
                    closeForm();
                }


                // Edit bus information
                function editBus(rowIndex) {
                    $('#modalTitle1').text("Edit Bus");
                    var table = document.getElementById("busTable");
                    var row = table.rows[rowIndex];
                    $('#bus_number').val(row.cells[0].innerHTML);
                    $('#make').val(row.cells[1].innerHTML);
                    $('#emission_norms').val(row.cells[2].innerHTML);
                    $('#date').val(row.cells[3].innerHTML);
                    $('#offRoadLocation').val(row.cells[4].innerHTML);
                    $('#partsRequired').val(row.cells[5].innerHTML);
                    $('#remarks').val(row.cells[6].innerHTML);
                    editRowIndex = rowIndex;
                    $('#myModal1').modal('show');
                }

                // Update bus information
                function updateBus(formData) {
                    var table = document.getElementById("busTable");
                    var row = table.rows[editRowIndex];

                    for (var i = 0; i < row.cells.length - 1; i++) {
                        row.cells[i].innerHTML = formData[Object.keys(formData)[i]];
                    }

                    editRowIndex = -1;
                    closeForm();
                }

                // Remove bus from table
                function removeBus(rowIndex) {
                    document.getElementById("busTable").deleteRow(rowIndex);
                }

                // Function to handle form submission
                $('#submitBtn').click(function(e) {
                    e.preventDefault();

                    // Check if there are any rows in the table
                    if ($('#busTable tbody tr').length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Data Found',
                            text: 'Please add data to the table before submitting.'
                        });
                        return; // Stop further processing
                    }


                    // Collect table data
                    var tableData = [];
                    $('#busTable tbody tr').each(function() {
                        var rowData = {};
                        $(this).find('td').each(function() {
                            // Get the column name from the table header
                            var columnName = $(this).closest('table').find('thead th').eq($(this).index()).text();
                            // Store data in the rowData object with the column name as the key
                            rowData[columnName] = $(this).text();
                        });
                        // Push the rowData object to the tableData array
                        tableData.push(rowData);
                    });

                    // Send table data to submit_data.php
                    $.ajax({
                        url: 'off_road_submit.php',
                        type: 'POST',
                        data: {
                            tableData: tableData
                        },
                        success: function(response) {
                            var res = JSON.parse(response);

                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: res.message,
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: res.message,
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error submitting data:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Submission Failed',
                                text: 'Something went wrong while submitting the data.',
                                footer: error,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        }
                    });


                });

                function updateStatus(id, busNumber) {
                    console.log("ID:", id);
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you want to mark bus " + busNumber + " as On Road?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, update it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: 'update_status.php',
                                type: 'POST',
                                data: {
                                    id: id
                                },
                                success: function(response) {
                                    if (response === 'success') {
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'Bus status updated successfully.',
                                            icon: 'success'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Not allowed',
                                            text: 'Failed to update status. ' + response,
                                            icon: 'warning'
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'AJAX request failed.',
                                        icon: 'error'
                                    });
                                }
                            });
                        }
                    });
                }
            </script>
        </div>

        <!-- Step 2 -->
        <div class="card" id="step2" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Breakdown Details (Yesterday)</h5>

                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#bdModal">
                    + Add Breakdown
                </button>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table id="bd_table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bus No</th>
                            <th>Route</th>
                            <th>Location</th>
                            <th>Cause</th>
                            <th>Reason</th>
                            <th>KM After Docking</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        $yesterday = date('Y-m-d', strtotime('-1 day'));

                        $query = "SELECT b.bd_date, b.bus_number, b.route_number, b.bd_location,
                                 c.cause, c.reason, b.km_after_docking
                          FROM bd_datas b
                          LEFT JOIN bd_cause c ON b.cause = c.cause_id and b.reason = c.reason_id
                          WHERE b.bd_date = ? AND b.depot_id = ? AND b.division_id = ?";

                        if ($stmt = $db->prepare($query)) {
                            $stmt->bind_param("sss", $yesterday, $depot, $division);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                <td>{$row['bd_date']}</td>
                                <td>{$row['bus_number']}</td>
                                <td>{$row['route_number']}</td>
                                <td>{$row['bd_location']}</td>
                                <td>{$row['cause']}</td>
                                <td>{$row['reason']}</td>
                                <td>{$row['km_after_docking']}</td>
                                <td>
                                    <button class='btn' onclick='deleteBreakdown(\"{$row['bus_number']}\", \"{$row['bd_date']}\", this)'>
                                        <i class='fa-solid fa-trash fa-xl' style='color: rgb(248, 3, 3);'></i>
                                    </button>
                                </td>
                            </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No breakdown data for yesterday</td></tr>";
                            }

                            $stmt->close();
                        }
                        ?>

                    </tbody>
                </table>
            </div>
            <div class="modal fade" id="bdModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">

                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Add Breakdown</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <!-- YOUR FORM -->
                            <form method="POST" id="bd_form">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label for=bd_date>BreakDown Date:</label>
                                            <input type="date" class="form-control" id="bd_date" name="bd_date" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <label for="bd_bus_number">Bus Number:</label>
                                            <select class="form-control" id="bd_bus_number" name="bd_bus_number" required>
                                                <option value="" disabled selected>Select Bus Number</option>
                                                <?php
                                                // Fetch bus numbers from the database
                                                $query = "SELECT BUS_NUMBER FROM bus_registration WHERE depot_name = ? AND division_name = ?";

                                                if ($stmt = $db->prepare($query)) {
                                                    $stmt->bind_param("ss", $depot, $division);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();

                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . htmlspecialchars($row['BUS_NUMBER']) . "'>" . htmlspecialchars($row['BUS_NUMBER']) . "</option>";
                                                    }

                                                    $stmt->close();
                                                } else {
                                                    echo "<option value='' disabled>Error fetching bus numbers</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label for="route_number">Route Number:</label>
                                            <input type="text" class="form-control" id="route_number" name="route_number" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <label for="bd_location">BreakDown Location:</label>
                                            <input type="text" class="form-control" id="bd_location" name="bd_location" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label for="cause">Cause:</label>
                                            <select class="form-control" id="cause" name="cause" required>
                                                <option value="" disabled selected>Select Cause</option>
                                                <?php
                                                // Fetch causes from the database
                                                $query = "SELECT distinct(cause_id), cause FROM bd_cause";
                                                if ($stmt = $db->prepare($query)) {
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . $row['cause_id'] . "'>" . htmlspecialchars($row['cause']) . "</option>";
                                                    }
                                                    $stmt->close();
                                                } else {
                                                    echo "<option value='' disabled>Error fetching causes</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <label for="reason">Reason:</label>
                                            <select class="form-control" id="reason" name="reason" required>
                                                <option value="" disabled selected>Select Reason</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label for="km_after_docking">KM After Docking:</label>
                                            <input type="number" class="form-control" id="km_after_docking" name="km_after_docking"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col"></div>
                                </div>

                                <button type="submit" class="btn btn-primary">Submit</button>
                            </form>

                        </div>

                    </div>
                </div>
            </div><br><br>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" id="back2">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-primary" id="save2">
                    <i class="bi bi-save"></i> Save & Continue
                </button>
            </div>
            <script>
                $(document).ready(function() {
                    $('#bd_bus_number').select2({
                        placeholder: "Select Bus Number",
                        width: '100%'
                    });
                    $('#reason').select2({
                        placeholder: "Select Reason",
                        width: '100%'
                    });
                });

                $(document).ready(function() {
                    $('#cause').on('change', function() {
                        var cause_id = $(this).val();
                        var action = 'fetch_bd_reason';

                        if (cause_id) {
                            $.ajax({
                                url: '../includes/backend_data.php',
                                type: 'POST',
                                data: {
                                    cause_id: cause_id,
                                    action: action
                                },
                                success: function(data) {
                                    $('#reason').html(data);
                                },
                                error: function() {
                                    alert('Error retrieving reasons.');
                                }
                            });
                        } else {
                            $('#reason').html('<option value="">Select Reason</option>');
                        }
                    });
                });
                document.getElementById('bd_date').addEventListener('change', function() {
                    var bdDate = this.value;
                    if (!bdDate) return;

                    var selectedDate = new Date(bdDate);
                    selectedDate.setHours(0, 0, 0, 0); // Normalize selected date (remove time)

                    var minDate = new Date('2025-04-01');
                    minDate.setHours(0, 0, 0, 0); // Normalize min date

                    var today = new Date();
                    today.setHours(0, 0, 0, 0); // Normalize today's date

                    if (selectedDate < minDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Date Too Early',
                            text: 'The selected date cannot be earlier than 01-04-2025.',
                        });
                        this.value = '';
                    } else if (selectedDate > today) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Date In Future',
                            text: 'The selected date cannot be in the future.',
                        });
                        this.value = '';
                    }
                });

                $(document).ready(function() {
                    $('#bd_form').on('submit', function(e) {
                        e.preventDefault();

                        var bdDate = $('#bd_date').val();
                        var busNumber = $('#bd_bus_number').val();
                        var routeNumber = $('#route_number').val();
                        var bdLocation = $('#bd_location').val();
                        var cause = $('#cause').val();
                        var reason = $('#reason').val();
                        var kmAfterDocking = $('#km_after_docking').val();

                        // Validate bdDate
                        var selectedDate = new Date(bdDate);
                        selectedDate.setHours(0, 0, 0, 0);
                        var minDate = new Date('2025-04-01');
                        minDate.setHours(0, 0, 0, 0);
                        var today = new Date();
                        today.setHours(0, 0, 0, 0);

                        if (selectedDate < minDate || selectedDate > today) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid Date',
                                text: 'Invalide date selected please select a valid date.',
                            });
                            return;
                        }

                        // All fields required validation
                        let missingFields = [];

                        if (!busNumber) missingFields.push("Bus Number");
                        if (!cause) missingFields.push("Cause");
                        if (!reason) missingFields.push("Reason");
                        if (!kmAfterDocking) missingFields.push("KM After Docking");
                        if (!routeNumber) missingFields.push("Route Number");
                        if (!bdLocation) missingFields.push("Breakdown Location");

                        if (missingFields.length > 0) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Missing Data',
                                html: "Please fill the following fields:<br><b>" + missingFields.join(", ") + "</b>"
                            });
                            return;
                        }

                        $.ajax({
                            url: '../includes/backend_data.php',
                            type: 'POST',
                            data: {
                                bd_date: bdDate,
                                bus_number: busNumber,
                                route_number: routeNumber,
                                bd_location: bdLocation,
                                cause: cause,
                                reason: reason,
                                km_after_docking: kmAfterDocking,
                                action: 'insert_bd_data'
                            },
                            dataType: 'json', // Expect JSON response
                            success: function(response) {
                                if (response.success) {

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.success,
                                    }).then(() => {

                                        // 👉 Get values from form
                                        let bd_date = $("#bd_date").val();
                                        let bd_bus_number = $("#bd_bus_number").val();
                                        let route_number = $("#route_number").val();
                                        let bd_location = $("#bd_location").val();
                                        let cause = $("#cause option:selected").text();
                                        let reason = $("#reason option:selected").text();
                                        let km = $("#km_after_docking").val();

                                        // 👉 Create row
                                        let newRow = `<tr><td>${bd_date}</td><td>${bd_bus_number}</td><td>${route_number}</td><td>${bd_location}</td><td>${cause}</td><td>${reason}</td><td>${km}</td><td><button class='btn' onclick='deleteBreakdown("${bd_bus_number}", "${bd_date}", this)'><i class='fa-solid fa-trash fa-xl' style='color:red;'></i></button></td></tr>`;

                                        let tbody = $("#step2 table tbody");

                                        // Remove "no data" row if exists
                                        tbody.find("td[colspan]").parent().remove();

                                        // Append row
                                        tbody.append(newRow);

                                        // Reset form
                                        $("#bd_form")[0].reset();

                                        // Close modal
                                        let modalEl = document.getElementById('bdModal');
                                        let modal = bootstrap.Modal.getInstance(modalEl);

                                        if (modal) {
                                            modal.hide();
                                        }

                                        // cleanup (important)
                                        document.body.classList.remove('modal-open');
                                        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

                                    });

                                } else if (response.error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.error,
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'AJAX Error',
                                    text: 'An error occurred while inserting data: ' + error
                                });
                            }
                        });

                    });
                });

                function deleteBreakdown(busNumber, bdDate, btn) {

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Do you want to delete the breakdown record for bus ${busNumber} on ${bdDate}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {

                        if (result.isConfirmed) {

                            $.ajax({
                                url: '../includes/backend_data.php',
                                type: 'POST',
                                data: {
                                    bus_number: busNumber,
                                    bd_date: bdDate,
                                    action: 'delete_bd_record'
                                },
                                dataType: 'json', // ✅ IMPORTANT
                                success: function(response) {

                                    if (response.status === 'success') {

                                        Swal.fire({
                                            title: 'Deleted!',
                                            text: response.message,
                                            icon: 'success'
                                        });

                                        // ✅ Remove row without reload
                                        $(btn).closest("tr").remove();

                                        // ✅ If table becomes empty → show message
                                        if ($("#bd_table tbody tr").length === 0) {
                                            $("#bd_table tbody").html(
                                                "<tr><td colspan='8' class='text-center'>No breakdown data for yesterday</td></tr>"
                                            );
                                        }

                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: response.message,
                                            icon: 'error'
                                        });
                                    }

                                },
                                error: function() {
                                    Swal.fire({
                                        title: 'AJAX Error',
                                        text: 'An error occurred while deleting the record.',
                                        icon: 'error'
                                    });
                                }
                            });
                        }
                    });
                }
            </script>
        </div>

        <!-- Step 3 -->
        <div class="card" id="step3" style="display:none;">
            <form id="dvpFormData">
                <?php
                $query = "SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN max_off_road_location = 'Depot' AND max_parts_required <> 'Work under Progress' THEN 1 ELSE 0 END) AS depot_count,
            SUM(CASE WHEN max_off_road_location = 'Depot' AND max_parts_required = 'Work under Progress' THEN 1 ELSE 0 END) AS wup_count,
            SUM(CASE WHEN max_off_road_location = 'DWS' THEN 1 ELSE 0 END) AS dws_count,
            SUM(CASE WHEN max_off_road_location = 'RWY' THEN 1 ELSE 0 END) AS rwy_count,
            SUM(CASE WHEN max_off_road_location = 'Police Station' THEN 1 ELSE 0 END) AS police_count,
            SUM(CASE WHEN max_off_road_location = 'Authorized Dealer' THEN 1 ELSE 0 END) AS authorized_dealer
          FROM (
            SELECT bus_number, MAX(off_road_location) AS max_off_road_location, MAX(parts_required) AS max_parts_required
            FROM off_road_data
            WHERE status = 'off_road' AND division = '$division' AND depot = '$depot'
            GROUP BY bus_number
          ) AS max_locations";




                $result = mysqli_query($db, $query);

                // Check if query executed successfully
                if (!$result) {
                    die("Error: " . mysqli_error($db));
                }

                // Fetch the result
                $row = mysqli_fetch_assoc($result);

                // Assign counts to variables, handle the case when counts are null
                $depotCount = isset($row['depot_count']) ? $row['depot_count'] : 0;
                $wupCount = isset($row['wup_count']) ? $row['wup_count'] : 0;
                $dwsCount = isset($row['dws_count']) ? $row['dws_count'] : 0;
                $rwyCount = isset($row['rwy_count']) ? $row['rwy_count'] : 0;
                $policecount = isset($row['police_count']) ? $row['police_count'] : 0;
                $authorizeddealer = isset($row['authorized_dealer']) ? $row['authorized_dealer'] : 0;


                // Free the result set
                mysqli_free_result($result);

                // Query to retrieve the count of vehicles
                $query = "SELECT COUNT(*) AS vehicle_count FROM bus_registration WHERE division_name = '$division' AND depot_name = '$depot'";
                $result = mysqli_query($db, $query);

                // Check if query executed successfully
                if (!$result) {
                    die("Error: " . mysqli_error($db));
                }

                // Fetch the result
                $row = mysqli_fetch_assoc($result);

                // Assign vehicle count to a variable
                $vehicleCount1 = isset($row['vehicle_count']) ? $row['vehicle_count'] : 0;
                $vehicleCount = $vehicleCount1 - $rwyCount; // Exclude RWY vehicles from the total count
                // Free the result set
                mysqli_free_result($result);
                ?>
                <h4 class="text-center">Daily Vehicle Position</h4>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="width:100px;color: red;">Date:</span>
                        </div>
                        <input type="date" id="date" class="form-control" name="date" max="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo date('Y-m-d'); ?>" readonly style="color: red;">
                    </div>
                </div>
                <div class="form-row">

                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Number of Schdules:</span>
                            </div>
                            <input type="number" class="form-control" id="schdules" name="schdules"
                                oninput="calculateDifference()" required>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Number of Vehicles(RWY Excluding):</span>
                            </div>
                            <input type="number" class="form-control" id="vehicles" name="vehicles"
                                oninput="calculateDifference()" value="<?php echo $vehicleCount; ?>" style="color: red;"
                                readonly>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Number of Spare Vehicles(RWY Excluding):</span>
                            </div>
                            <input type="number" class="form-control" id="spare" name="spare" oninput="calculateDifference()"
                                readonly style="color: red;">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Spare Vehicles Percentage(RWY
                                    Excluding):</span>
                            </div>
                            <input type="number" class="form-control" id="spareP" name="spareP" oninput="calculateDifference()"
                                readonly style="color: red;">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicles Off Road At RWY:</span>
                            </div>
                            <input type="number" class="form-control" id="ORRWY" name="ORRWY" oninput="calculateDifference()"
                                value="<?php echo $rwyCount; ?>" readonly style="color: red;">
                        </div>
                    </div>
                </div>
                <center>
                    <h4>Other Off Roads</h4>
                </center>
                <div class="form-row">


                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicles Off Road At Depot:</span>
                            </div>
                            <input type="number" class="form-control" id="ORDepot" name="ORDepot"
                                oninput="calculateDifference()" value="<?php echo $depotCount; ?>" readonly style="color: red;">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicles Off Road At DWS:</span>
                            </div>
                            <input type="number" class="form-control" id="ORDWS" name="ORDWS" oninput="calculateDifference()"
                                value="<?php echo $dwsCount; ?>" readonly style="color: red;">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Number of Docking:</span>
                            </div>
                            <input type="number" class="form-control" id="docking" name="docking"
                                oninput="calculateDifference()" required>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Vehicles Withdrawn for Fair:</span>
                            </div>
                            <input type="number" class="form-control" id="wup" name="wup" oninput="calculateDifference()"
                                required>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Vehicles on CC/Extra Operation:</span>
                            </div>
                            <input type="number" class="form-control" id="CC" name="CC" oninput="calculateDifference()"
                                required>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Vehicles not Arrived to Depot:</span>
                            </div>
                            <input type="number" class="form-control" id="notdepot" name="notdepot"
                                oninput="calculateDifference()" required>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicle Work Under Progress at Depot:</span>
                            </div>
                            <input type="number" class="form-control" id="wup1" name="wup1" oninput="calculateDifference()" value="<?php echo $wupCount; ?>"
                                readonly style="color: red;">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Vehicles loan given to other Depot/Training Center:</span>
                            </div>
                            <input type="number" class="form-control" id="loan" name="loan" oninput="calculateDifference()"
                                required>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicles Held at Authorized Dealer :</span>
                            </div>
                            <input type="number" class="form-control" id="Dealer" name="Dealer" oninput="calculateDifference()"
                                value="<?php echo $authorizeddealer; ?>" readonly style="color: red;">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicles at Police Station:</span>
                            </div>
                            <input type="number" class="form-control" id="Police" name="Police" oninput="calculateDifference()"
                                value="<?php echo $policecount; ?>" readonly style="color: red;">
                        </div>
                    </div>

                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicle not availavle for operation:</span>
                            </div>
                            <input type="number" class="form-control" id="ORTotal" name="ORTotal"
                                oninput="calculateDifference()" readonly style="color: red;">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicle availavle for operation:</span>
                            </div>
                            <input type="number" class="form-control" id="available" name="available"
                                oninput="calculateDifference()" readonly style="color: red;">
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="color: red;">Vehicle Excess or Shortage:</span>
                            </div>
                            <input type="number" class="form-control" id="E/S" name="E/S" oninput="calculateDifference()"
                                readonly style="color: red;">
                        </div>
                    </div>
                </div>

            </form>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" id="back3">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-success" id="save3">
                    <i class="bi bi-check2-circle"></i> Save & Finish
                </button>
            </div>
            <script>
                function calculateDifference() {
                    // Get the values of Number of Schedules and Number of Vehicles
                    var schedules = parseInt(document.getElementById("schdules").value);
                    var vehicles = parseInt(document.getElementById("vehicles").value);
                    var docking = parseInt(document.getElementById("docking").value);
                    var wup = parseInt(document.getElementById("wup").value);
                    var wup1 = parseInt(document.getElementById("wup1").value);
                    var ORDepot = parseInt(document.getElementById("ORDepot").value);
                    var ORDWS = parseInt(document.getElementById("ORDWS").value);
                    var ORRWY = parseInt(document.getElementById("ORRWY").value);
                    var CC = parseInt(document.getElementById("CC").value);
                    var Police = parseInt(document.getElementById("Police").value);
                    var Dealer = parseInt(document.getElementById("Dealer").value);
                    var notdepot = parseInt(document.getElementById("notdepot").value);
                    var loan = parseInt(document.getElementById("loan").value);

                    // Create an array to store all input fields and their corresponding IDs
                    var inputs = [{
                            value: schedules,
                            id: "schdules"
                        },
                        {
                            value: vehicles,
                            id: "vehicles"
                        },
                        {
                            value: docking,
                            id: "docking"
                        },
                        {
                            value: wup,
                            id: "wup"
                        },
                        {
                            value: wup1,
                            id: "wup1"
                        },
                        {
                            value: ORDepot,
                            id: "ORDepot"
                        },
                        {
                            value: ORDWS,
                            id: "ORDWS"
                        },
                        {
                            value: ORRWY,
                            id: "ORRWY"
                        },
                        {
                            value: CC,
                            id: "CC"
                        },
                        {
                            value: Police,
                            id: "Police"
                        },
                        {
                            value: Dealer,
                            id: "Dealer"
                        },
                        {
                            value: notdepot,
                            id: "notdepot"
                        },
                        {
                            value: loan,
                            id: "loan"
                        }
                    ];

                    // Check if any input is negative
                    for (var i = 0; i < inputs.length; i++) {
                        if (inputs[i].value < 0) {
                            // Alert the user
                            alert("Please Enter a value Greater then 0");

                            // Set the value of the corresponding input field to null
                            document.getElementById(inputs[i].id).value = '';

                            return; // Stop the calculation if a negative value is detected
                        }
                    }
                    // Calculate the difference to find the number of spare vehicles
                    var spare = (vehicles - schedules);

                    // Update the Number of Spare Vehicles field
                    document.getElementById("spare").value = spare;

                    // Calculate the Spare Vehicles Percentage
                    var sparePercentage = (spare * 100 / schedules).toFixed(2);

                    // Update the Spare Vehicles Percentage field
                    document.getElementById("spareP").value = sparePercentage;

                    // Calculate the difference to find the number of Off road total vehicles
                    var ORTotal = (docking + wup + ORDepot + ORDWS + CC + Police + notdepot + Dealer + loan + wup1);

                    // Update the Number of off road Vehicles field
                    document.getElementById("ORTotal").value = ORTotal;

                    // Calculate the difference to find the number of vehicle available for operation vehicles
                    var available = (vehicles - ORTotal);

                    // Update the Number of vehicle available for opertion Vehicles field
                    document.getElementById("available").value = available;

                    // Calculate the difference to find the number of total Access or shortage vehicles
                    var AS = (spare - ORTotal);

                    // Update the Number of access or shortage of Vehicles field
                    document.getElementById("E/S").value = AS;
                }
            </script>
        </div>
    </div>

    <script>
        $(function() {

            let formData = {}; // To store all steps data temporarily

            // Step indicator update
            function updateStepIndicator(current) {
                $(".step").removeClass("active completed");
                for (let i = 1; i <= 3; i++) {
                    if (i < current) $("#indicator" + i).addClass("completed");
                    else if (i === current) $("#indicator" + i).addClass("active");
                }
            }

            // Navigation buttons
            $("#back2").click(() => {
                $("#step2").hide();
                $("#step1").fadeIn();
                updateStepIndicator(1);
            });
            $("#back3").click(() => {
                $("#step3").hide();
                $("#step2").fadeIn();
                updateStepIndicator(2);
            });

            // Step 1 Save
            $("#save1").click(function() {

                let rowCount = $("#busTable tbody tr").length;

                // Check if table has data
                if (rowCount > 0) {
                    Swal.fire({
                        icon: "warning",
                        title: "Pending Data",
                        text: "Please click SUBMIT to save Off Road details before proceeding."
                    });
                    return;
                }

                // If no rows → allow next step
                formData.offroad = $("#offroadFormData").serializeArray();

                Swal.fire("Saved!", "Offroad entries saved. Moving to next step.", "success");

                $("#step1").hide();
                $("#step2").fadeIn();
                updateStepIndicator(2);

            });

            // Step 2 Save
            $("#save2").click(function() {

                // ✅ Ignore "No data" row
                let rowCount = $("#bd_table tbody tr").not(":has(td[colspan])").length;

                // 👉 Case 1: No breakdown rows
                if (rowCount === 0) {

                    Swal.fire({
                        icon: "question",
                        title: "No Breakdown?",
                        text: "No breakdown recorded for yesterday. Do you want to continue?",
                        showCancelButton: true,
                        confirmButtonText: "Yes, Continue",
                        cancelButtonText: "No, Add Details"
                    }).then((result) => {

                        if (result.isConfirmed) {

                            Swal.fire("Saved!", "No breakdown recorded.", "success");

                            $("#step2").hide();
                            $("#step3").fadeIn();
                            updateStepIndicator(3);

                        }

                    });

                    return;
                }

                // 👉 Case 2: Table has real data
                Swal.fire("Saved!", "Breakdown records saved successfully.", "success");

                $("#step2").hide();
                $("#step3").fadeIn();
                updateStepIndicator(3);

            });

            // Step 3 Save & Finish
            $("#save3").click(function() {

                let form = $("#dvpFormData")[0];

                // ✅ Step 1: Validate form
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // ✅ Step 2: Ask confirmation
                Swal.fire({
                    icon: "warning",
                    title: "Final Submit?",
                    html: "Once submitted, <b>data cannot be edited</b>.<br>Do you want to continue?",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Submit",
                    cancelButtonText: "Cancel"
                }).then((result) => {

                    if (result.isConfirmed) {

                        // ✅ Prepare data
                        formData = $("#dvpFormData").serializeArray();



                        $.ajax({
                            type: 'POST',
                            url: '../database/save_dvp.php',
                            data: formData,
                            dataType: 'json', // Expect JSON response
                            success: function(response) {
                                // Display appropriate message
                                if (response.status === 'success') {
                                    alert(response.message); // Alert success message
                                    location.reload(); // Reload the page
                                } else {
                                    alert(response.message); // Alert error message
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log(xhr.responseText); // Log error to console
                                alert('An error occurred while processing your request.'); // Alert error message
                            }
                        });

                    }

                    // ❌ If cancelled → do nothing

                });

            });

        });
    </script>



<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>