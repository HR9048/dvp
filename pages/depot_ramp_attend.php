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
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $sch_out_id = $_POST['sch_out_id'];

        // Run your SQL query to update the status
        $sql = "UPDATE sch_veh_out SET schedule_status = 0 WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $sch_out_id);
        if ($stmt->execute()) {
            // Redirect or reload the page after successful update
            echo "<script>
                            alert('Schedule updated successfully');
                            window.location.href = 'depot_ramp_attend.php';
                        </script>";
            exit();
        } else {
            echo "Error updating record: " . $db->error;
        }
    }

    ?>
    <div class="table-responsive">
        <table class="table2">
            <thead>
                <tr>
                    <th class="d-none">sch out ID</th>
                    <th class="d-none">sch in ID</th>
                    <th class="d-none">defect ID</th>
                    <th>Sch No</th>
                    <th>Vehicle No</th>
                    <th>Driver Token</th>
                    <th>Conductor Token</th>
                    <th>Arrival Time</th>
                    <th>Driver Defect noticed</th>
                    <th>Driver Remark</th>
                    <th>Ramp Defect</th>
                    <th>ramp remark</th>
                    <th>Action</th>
                    <th>Off-road</th>
                    <th class="d-none">remark</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT 
                svi.id AS sch_in_id,
                svi.*,         
                svo.*,
                dd.defect_name AS driver_defect_name,
                rd.defect_name AS ramp_defect_name
            FROM 
                sch_veh_in svi
            JOIN 
                sch_veh_out svo
            ON 
                svo.id = svi.sch_out_id
            LEFT JOIN 
                driver_defect dd
            ON 
                svi.driver_defect = dd.id
            LEFT JOIN 
                ramp_defect rd
            ON 
                svi.ramp_defect = rd.id
            WHERE 
                svo.division_id = '$division_id'
            AND 
                svo.depot_id = '$depot_id' 
            AND 
                svo.schedule_status = 4
            ORDER BY 
                svo.arr_time ASC;";
                $result = $db->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $driver_token = $row["driver_token_no_1"];
                        if (!empty($row["driver_token_no_2"])) {
                            $driver_token .= ", " . $row["driver_token_no_2"];
                        }

                        $conductor_token = !empty($row["conductor_token_no"]) ? $row["conductor_token_no"] : "Single Crew";

                        echo "<tr>
                                    <td class='d-none'>" . $row["sch_out_id"] . "</td>
                                    <td class='d-none'>" . $row["sch_in_id"] . "</td>
                                    <td class='d-none'>" . $row["driver_defect"] . "</td>
                                    <td>" . $row["sch_no"] . "</td>
                                    <td>" . $row["vehicle_no"] . "</td>
                                    <td>" . $driver_token . "</td>
                                    <td>" . $conductor_token . "</td>
                                    <td>" . date('H:i', strtotime($row["arr_time"])) . "</td>
                                    <td>" . $row["driver_defect_name"] . "</td>
                                    <td>" . $row["driver_remark"] . "</td>
                                    <td>" . $row["ramp_defect_name"] . "</td>
                                    <td>" . $row["ramp_remark"] . "</td>
                                    <td><button class='btn btn-success' onclick='openModal(this)'>Attend</button></td>
                                    <td><button class='btn btn-danger' onclick='openForm(this)'>Off-road</button></td>

                                  </tr>";
                    }
                } else {
                    echo "<tr><td colspan='11'>No Schedules Arrived</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    </div>
    </div>

    </div>
    <div class="modal fade" id="dataModal" tabindex="-1" role="dialog" aria-labelledby="dataModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataModalLabel">Route Return Form</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="rampDefectForm">
                        <input type="hidden" class="form-control" id="sch_out_id" name="sch_out_id" readonly>
                        <input type="hidden" class="form-control" id="sch_in_id" name="sch_in_id" readonly>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="scheduleNo">Schedule No</label>
                                    <input type="text" class="form-control" id="scheduleNo" name="scheduleno" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="vehicleNo">Vehicle No</label>
                                    <input type="text" class="form-control" id="vehicleNo" name="vehicleNo" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="driverToken">Driver Token</label>
                                    <input type="text" class="form-control" id="driverToken" name="driverToken" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="conductorToken">Conductor Token</label>
                                    <input type="text" class="form-control" id="conductorToken" name="conductorToken"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="defectname">Driver Defect Noticed</label>
                                    <input type="text" class="form-control" id="defectname" name="defectname" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="remark">Remark</label>
                                    <input type="text" class="form-control" id="remark" name="remark" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="ramp_defect">Ramp Defect</label>
                                    <input type="text" name="ramp_defect" class="form-control" id="ramp_defect" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="ramp_remark">Ramp Remark</label>
                                    <input type="text" class="form-control" id="ramp_remark" name="ramp_remark" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="button" style="text-align:center;">
                            <button type="button" class="btn btn-primary" id="attendButton">Attend</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for adding/editing bus -->
    <div id="myModal1" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle1">Add Off-Road Details</h5>
                    <button type="button" class="close" onclick="closeForm()" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="busSearch" type="search" class="form-control mr-sm-2"
                        placeholder="Search Bus Number">

                    <form id="busForm">
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <input type="text" id="bus_number" class="form-control" name="busNumber"
                                        placeholder="Bus Number" required readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <input type="text" id="make" class="form-control" name="make" placeholder="Make"
                                        readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <input type="text" id="emission_norms" class="form-control" name="emission_norms"
                                        placeholder="Norms" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <input type="date" id="date" class="form-control" name="date"
                                        value="<?php echo date('Y-m-d'); ?>" readonly>
                                </div>
                            </div>
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
<!-- Modal HTML -->
<div id="myModal4" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalTitle4" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle1">Add Off-Road Details</h5>
                <button type="button" class="close" onclick="closeForm()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <nav class="navbar navbar-light bg-light">
                    <div id="searchBar" style="display: flex; align-items: center;">
                        <input type="text" id="busSearch" class="form-control mr-sm-2" placeholder="Search Bus Number">
                        <button type="button" class="btn btn-outline-success my-2 my-sm-0" onclick="searchBus()">Search</button>
                    </div>
                </nav>

                <!-- Table to display bus details -->
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
            </div>
        </div>
    </div>
</div>

    <script>
        function openModal(button) {
            // Get the row data
            var row = $(button).closest('tr');
            var id = row.find('td:eq(0)').text();
            var scheduleNo = row.find('td:eq(3)').text();
            var vehicleNo = row.find('td:eq(4)').text();
            var driverToken = row.find('td:eq(5)').text();
            var conductorToken = row.find('td:eq(6)').text();
            var arrivalTime = row.find('td:eq(7)').text();
            var defectname = row.find('td:eq(8)').text();
            var defecttype = row.find('td:eq(2)').text();
            var remark = row.find('td:eq(9)').text();
            var sch_in_id = row.find('td:eq(1)').text();
            var rampdefecttype = row.find('td:eq(10)').text();
            var rampremark = row.find('td:eq(11)').text();
            // Set the modal input values
            $('#sch_out_id').val(id);
            $('#scheduleNo').val(scheduleNo);
            $('#vehicleNo').val(vehicleNo);
            $('#driverToken').val(driverToken);
            $('#conductorToken').val(conductorToken);
            $('#arrivalTime').val(arrivalTime);
            $('#defectname').val(defectname);
            $('#driverDefect').val(defecttype);
            $('#remark').val(remark);
            $('#sch_in_id').val(sch_in_id);
            $('#ramp_defect').val(rampdefecttype);
            $('#ramp_remark').val(rampremark);
            // Open the modal
            $('#dataModal').modal('show');
        }


        $('#attendButton').on('click', function () {
            var vehicleNo = $('#vehicleNo').val();
            var sch_out_id = $('#sch_out_id').val();

            // Show the confirmation dialog
            var confirmation = confirm('Are you sure you want to make the vehicle (' + vehicleNo + ') attend and available for the next route operation?');

            if (confirmation) {
                // Create a hidden form to submit the data
                var form = $('<form>', {
                    'action': '', // Current page URL, or specify the PHP page that handles the update
                    'method': 'POST'
                }).append($('<input>', {
                    'type': 'hidden',
                    'name': 'sch_out_id',
                    'value': sch_out_id
                })).append($('<input>', {
                    'type': 'hidden',
                    'name': 'update_status',
                    'value': true
                }));

                // Append the form to the body and submit it
                $('body').append(form);
                form.submit();
            }
        });



        $('.close').on('click', function (e) {
            e.preventDefault();
            // other code...
            $('#dataModal').modal('hide');

        });

    </script>

    <script>
      

        // Function to search for bus
        function searchBus() {
            var busNumber = $('#busSearch').val();

            // AJAX request to fetch data
            $.ajax({
                url: 'dvp_bus_search.php',
                type: 'POST',
                data: { busNumber: busNumber },
                dataType: 'json', // Specify the expected data type as JSON
                success: function (response) {
                    // Populate form fields with fetched data
                    $('#bus_number').val(response.bus_number);
                    $('#make').val(response.make);
                    $('#emission_norms').val(response.emission_norms);
                },
                error: function (xhr, status, error) {
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
        $('#busSearch').keypress(function (event) {
            // Check if the Enter key was pressed
            if (event.which == 13) {
                // Prevent default form submission behavior
                event.preventDefault();
                // Trigger search function
                searchBus();
            }
        });

        function fetchOffroadLocation() {
            // Fetch bus categories on page load
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchOffroadLocation' },
                success: function (response) {
                    var Location = JSON.parse(response);
                    $.each(Location, function (index, value) {
                        $('#offRoadLocation').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });

            $('#offRoadLocation').change(function () {
                var Location = $(this).val();
                $.ajax({
                    url: '../includes/data_fetch.php?action=fetchReason',
                    method: 'POST',
                    data: { offRoadLocation: Location },
                    success: function (data) {
                        // Clear previous options
                        $('#partsRequired').empty();
                        // Append options with line breaks
                        $('#partsRequired').html(data.replace(/,/g, '<br>'));
                        // Clear previous remarks and generate new ones
                        $('#remarksContainer').empty();
                        $('input[name="partsRequired[]"]').change(function () {
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
            $('input[name="partsRequired[]"]:checked').each(function () {
                var remark = $(this).val();
                remarksContainer.append('<textarea class="form-control remark" placeholder="Remark for ' + remark + '"></textarea><br>');
            });
        }


        // Call the function to fetch data on page load
        $(document).ready(function () {
            fetchOffroadLocation();
        });

        // Function to open the modal and initialize the bus search
function openForm(button) {
    // Get the row data
    var row = $(button).closest('tr');
    var vehicleNo = row.find('td:eq(4)').text(); // Adjust the index if necessary

    // Set the vehicle number in the search input field
    $('#busSearch').val(vehicleNo);

    // Call the searchBus function to perform the search and populate the table
    searchBus();

    // Show the modal
    $('#myModal1').modal('show');
}

// Function to close the modal
function closeForm() {
    // Hide the modal
    $('#myModal1').modal('hide');
    $('#myModal4').modal('hide');

    // Optionally, clear the form fields
    $('#busSearch').val('');
    $('#bus_number').val('');
    $('#make').val('');
    $('#emission_norms').val('');
    $('#date').val('');
    // Clear the table rows
    $('#busTable tbody').empty();
}

// Function to add or update bus information
function addOrUpdateBus() {
    var busNumber = $('#bus_number').val().trim();
    var make = $('#make').val().trim();
    var emissionNorms = $('#emission_norms').val().trim();
    var date = $('#date').val().trim();
    var offRoadLocation = $('#offRoadLocation').val().trim();

    // Collect Parts Required data
    var partsRequired = [];
    $('input[name="partsRequired[]"]:checked').each(function () {
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
    $('.remark').each(function () {
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

    // Hide the first modal and show the second modal
    $('#myModal1').modal('hide');
    $('#myModal4').modal('show');
}

// Function to add a bus to the table
function addBus(formData) {
    var table = document.getElementById("busTable").getElementsByTagName('tbody')[0];
    var newRow = table.insertRow(table.rows.length);

    for (var key in formData) {
        var cell = newRow.insertCell();
        cell.innerHTML = formData[key];
    }

    var actionCell = newRow.insertCell();
    actionCell.innerHTML = '<button type="button" class="btn btn-danger btn-sm" onclick="removeBus(' + newRow.rowIndex + ')"><i class="fas fa-trash-alt"></i></button>';
}

// Function to handle removing a bus from the table
function removeBus(rowIndex) {
    document.getElementById("busTable").deleteRow(rowIndex);
}

// Function to search for bus
function searchBus() {
    var busNumber = $('#busSearch').val();

    // AJAX request to fetch data
    $.ajax({
        url: 'dvp_bus_search.php',
        type: 'POST',
        data: { busNumber: busNumber },
        dataType: 'json', // Specify the expected data type as JSON
        success: function (response) {
            // Populate form fields with fetched data
            $('#bus_number').val(response.bus_number);
            $('#make').val(response.make);
            $('#emission_norms').val(response.emission_norms);
        },
        error: function (xhr, status, error) {
            // Display error message
            if (xhr.status === 403) {
                alert(xhr.responseJSON.error);
            } else {
                alert('Error Bus not Registered in KKRTC.');
            }
        }
    });
}

// Handle Enter key press in search input field
$('#busSearch').keypress(function (event) {
    if (event.which == 13) {
        event.preventDefault();
        searchBus();
    }
});

    </script>



    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>