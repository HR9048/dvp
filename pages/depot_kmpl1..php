<?php
include '../includes/connection.php';
include '../includes/depot_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Bunk' || $_SESSION['JOB_TITLE'] == 'DM') {

    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    // Allow access
    ?>
    <style>
        .nav-link.custom-size {
            font-size: 1.25rem;
            /* Increase font size */
            padding: 0.75rem 1.25rem;
            /* Increase padding */
        }

        .hide {
            display: none;
        }
    </style>
    <h2 class="text-center">SCHEDULE MASTER</h2>
    <nav>
        <div class="nav nav-tabs justify-content-center" id="nav-tab" role="tablist">
            <button class="nav-link active custom-size" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home"
                type="button" role="tab" aria-controls="nav-home" aria-selected="true">Schedule Bus</button>
            <button class="nav-link custom-size" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Schedule Crew</button>
        </div>
    </nav>
    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">

        </div>
        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab"><br>
            <table>
                <thead>
                    <tr>
                        <th class="hide">ID</th>
                        <th>Schedule No</th>
                        <th>Vehicle No</th>
                        <th>Driver Token</th>
                        <th>Conductor Token No</th>
                        <th>Arrival Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    $sql = "SELECT * FROM sch_veh_out WHERE division_id='$division_id' and depot_id='$depot_id' and schedule_status = 2 order by arr_time ASC";
                    $result = $db->query($sql);

                    if ($result->num_rows > 0) {
                        // Output data of each row
                        while ($row = $result->fetch_assoc()) {
                            $driver_token = $row["driver_token_no_1"];
                            if (!empty($row["driver_token_no_2"])) {
                                $driver_token .= ", " . $row["driver_token_no_2"];
                            }

                            $conductor_token = !empty($row["conductor_token_no"]) ? $row["conductor_token_no"] : "Single Crew";

                            echo "<tr>
                        <td class='hide'>" . $row["id"] . "</td>
                        <td>" . $row["sch_no"] . "</td>
                        <td>" . $row["vehicle_no"] . "</td>
                        <td>" . $driver_token . "</td>
                        <td>" . $conductor_token . "</td>
                        <td>" . date('H:i', strtotime($row["arr_time"])) . "</td>
                        <td><button class='btn btn-primary' onclick='openModal(this)'>Receive</button></td>
                      </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No results found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

        </div>
    </div>




    <!-- Modal -->
    <div class="modal fade" id="dataModal" tabindex="-1" role="dialog" aria-labelledby="dataModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataModalLabel">Vehicle Schedule Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <input type="hidden" class="form-control" id="id" name="id" readonly>

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
                                    <label for="arrivalTime">Arrival Time</label>
                                    <input type="text" class="form-control" id="arrivalTime" name="arrivalTime" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="logsheetNo">Logsheet No</label>
                                    <input type="number" class="form-control" id="logsheetNo" name="logsheetNo" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="RkmOperated">KM Operated</label>
                                    <input type="number" class="form-control" id="RkmOperated" name="RkmOperated" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="Rhsd">HSD</label>
                                    <input type="number" class="form-control" id="Rhsd" name="Rhsd" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label for="Rkmpl">KMPL</label>
                                <input type="text" class="form-control" id="Rkmpl" name="Rkmpl" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="driverDefect">Driver Defect</label>
                                    <select class="form-control" id="driverDefect" name="driverDefect" required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                            <div class="col" id="remarkContainer" style="display: none;">
                                <div class="form-group">
                                    <label for="remark">Remark</label>
                                    <textarea class="form-control" id="remark" name="remark"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="button" style="text-align:center;">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(button) {
            // Get the row data
            var row = $(button).closest('tr');
            var id = row.find('td:eq(0)').text();
            var scheduleNo = row.find('td:eq(1)').text();
            var vehicleNo = row.find('td:eq(2)').text();
            var driverToken = row.find('td:eq(3)').text();
            var conductorToken = row.find('td:eq(4)').text();
            var arrivalTime = row.find('td:eq(5)').text();

            // Set the modal input values
            $('#id').val(id);
            $('#scheduleNo').val(scheduleNo);
            $('#vehicleNo').val(vehicleNo);
            $('#driverToken').val(driverToken);
            $('#conductorToken').val(conductorToken);
            $('#arrivalTime').val(arrivalTime);

            // Open the modal
            $('#dataModal').modal('show');

            // Function to populate defect types
            function driverdefecttype() {
                $.ajax({
                    url: '../includes/data_fetch.php',
                    type: 'GET',
                    data: { action: 'driverdefecttype' },
                    success: function (response) {
                        var service = JSON.parse(response);

                        // Clear existing options
                        $('#driverDefect').empty();

                        // Add default "Select" option
                        $('#driverDefect').append('<option value="">Select</option>');

                        // Add fetched options
                        $.each(service, function (index, value) {
                            $('#driverDefect').append('<option value="' + value.id + '">' + value.defect_name + '</option>');
                        });

                        // Trigger change event to set initial state
                        $('#driverDefect').trigger('change');
                    }
                });
            }
            driverdefecttype();
        }

        // Handle the change event for driver defect select field
        $('#driverDefect').on('change', function () {
            var selectedValue = $(this).val();
            if (selectedValue != '1') {
                $('#remarkContainer').show();
                $('#remark').prop('required', true);
            } else {
                $('#remarkContainer').hide();
                $('#remark').prop('required', false);
            }
        });

        // Ensure remark container is hidden on modal open
        $('#dataModal').on('shown.bs.modal', function () {
            $('#remarkContainer').hide();
            $('#remark').prop('required', false);
        });

        // Calculate Rkmpl when KM Operated or Rhsd changes
        $('#RkmOperated, #Rhsd').on('input', function () {
            var RkmOperated = parseFloat($('#RkmOperated').val()) || 0;
            var Rhsd = parseFloat($('#Rhsd').val()) || 0;

            // Calculate Rkmpl and set the value
            if (Rhsd > 0) {
                var Rkmpl = RkmOperated / Rhsd;
                $('#Rkmpl').val(Rkmpl.toFixed(2));
            } else {
                $('#Rkmpl').val('');
            }
        });
    </script>
    <script>
        $(document).ready(function () {
            // Handle form submission
            $('form').on('submit', function (event) {
                event.preventDefault(); // Prevent the default form submission

                // Validate the form fields
                var isValid = true;
                var id = $('#id').val();
                var logsheetNo = $('#logsheetNo').val();
                var RkmOperated = $('#RkmOperated').val();
                var Rhsd = $('#Rhsd').val();
                var driverDefect = $('#driverDefect').val();
                var remark = $('#remark').val();

                if (!id) {
                    isValid = false;
                    alert('Something went wrong. Please refresh the page and try again.');
                }
                if (!logsheetNo) {
                    isValid = false;
                    alert('Logsheet No is required.');
                }
                if (!RkmOperated) {
                    isValid = false;
                    alert('KM Operated is required.');
                }
                if (!Rhsd) {
                    isValid = false;
                    alert('HSD is required.');
                }
                if (!driverDefect) {
                    isValid = false;
                    alert('Driver Defect is required.');
                }
                if (driverDefect !== '1' && !remark) {
                    isValid = false;
                    alert('Remark is required when Driver Defect is not 1.');
                }

                if (isValid) {
                    // Gather form data
                    var formData = $(this).serialize();

                    // Submit the form data using AJAX
                    $.ajax({
                        url: 'depot_driver_defect_insert.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json', // Expecting JSON response
                        success: function (response) {
                            if (response.status === 'success') {
                                // Redirect to depot_kmpl.php on success
                                alert('recored updated successfully');
                                window.location.href = 'depot_kmpl1..php';
                            } else {
                                // Show error message if any
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            // Handle errors
                            alert('An error occurred: ' + error);
                        }
                    });
                }
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