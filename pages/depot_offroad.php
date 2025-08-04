<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech') {
    // Allow access
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];
    $today = date('Y-m-d');

?>



    <style>
        /* Custom CSS styles */
        .modal-content1 {
            margin: auto;
            width: 40%;
            padding: 20px;
            background-color: white;
        }

        /* Apply block display to each checkbox and label pair */
        .form-check input[type="checkbox"],
        .form-check label {
            display: block;
        }

        /* Add margin-bottom to create space between each checkbox and label pair */
        .form-check label {
            margin-bottom: 5px;
            /* Adjust the margin as needed */
        }
    </style>
    <?php
    // Fetch off_road_data from the database based on session division and depot name
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];

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
                        <div class="form-group" id="edit_partsRequired_group">
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
                            window.location.href = 'depot_offroad.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = 'depot_offroad.php';
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
                        window.location.href = 'depot_offroad.php';
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

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>