<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Bunk' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    ?>
    <div class="table-responsive">
        <table class="table2">
            <thead>
                <tr>
                    <th class="d-none">ID</th>
                    <th>Sch No</th>
                    <th>Vehicle No</th>
                    <th>Driver Token</th>
                    <th>Conductor Token</th>
                    <th>Departure Date</th>
                    <th>Arrival Time</th>
                    <th>Action</th>
                    <th>make</th>
                    <th>norms</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $sql = "SELECT 
    svo.*, 
    br.make, 
    br.emission_norms 
FROM 
    sch_veh_out svo
JOIN 
    bus_registration br 
ON 
    svo.vehicle_no = br.bus_number 
WHERE 
    svo.division_id = '$division_id' 
AND 
    svo.depot_id = '$depot_id' 
AND 
    svo.schedule_status IN ('2', '6')
ORDER BY 
    svo.arr_time ASC";
                $result = $db->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $driver_token = $row["driver_token_no_1"];
                        if (!empty($row["driver_token_no_2"])) {
                            $driver_token .= ", " . $row["driver_token_no_2"];
                        }

                        $conductor_token = !empty($row["conductor_token_no"]) ? $row["conductor_token_no"] : "Single Crew";

                        echo "<tr>
                                    <td class='d-none'>" . $row["id"] . "</td>
                                    <td>" . $row["sch_no"] . "</td>
                                    <td>" . $row["vehicle_no"] . "</td>
                                    <td>" . $driver_token . "</td>
                                    <td>" . $conductor_token . "</td>
                                    <td>" . date('d-m-Y', strtotime($row["departed_date"])) . "</td>
                                    <td>" . date('H:i', strtotime($row["arr_time"])) . "</td>
                                     <td>";

                        // Check the schedule_status and display the corresponding button
                        if ($row["schedule_status"] == 2) {
                            echo "<button class='btn btn-primary' onclick='openDefectModal(this)'>Receive</button>";
                        } elseif ($row["schedule_status"] == 6) {
                            echo "<button class='btn btn-warning' onclick='openDefectModal(this)'>Defect Receive</button>";
                        }

                        echo "</td><td class='d-none1'>" . $row["make"] . "</td><td class='d-none1'>" . $row["emission_norms"] . "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No results found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <!-- Modal -->
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
                    <form id="driverDefectForm">
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
                            <div class="col">
                                <div class="form-group">
                                    <label for="Rkmpl">KMPL</label>
                                    <input type="text" class="form-control" id="Rkmpl" name="Rkmpl" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="driverDefect">Driver Defect Noticed</label>
                                    <select class="form-control" id="driverDefect" name="driverDefect" required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">

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
    <!-- Defect Receive Modal -->
    <div class="modal fade" id="defectReceiveModal" tabindex="-1" aria-labelledby="defectReceiveModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataModalLabel">Incomplete Route Return Form</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="earlydriverDefectForm">
                        <input type="hidden" class="form-control" id="id1" name="id1" readonly>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="scheduleNo">Schedule No</label>
                                    <input type="text" class="form-control" id="scheduleNo1" name="scheduleno1" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="vehicleNo">Vehicle No</label>
                                    <input type="text" class="form-control" id="vehicleNo1" name="vehicleNo1" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="driverToken">Driver Token</label>
                                    <input type="text" class="form-control" id="driverToken1" name="driverToken1" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="conductorToken">Conductor Token</label>
                                    <input type="text" class="form-control" id="conductorToken1" name="conductorToken1"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="arrivalTime">Arrival Time</label>
                                    <input type="text" class="form-control" id="arrivalTime1" name="arrivalTime1" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="logsheetNo">Logsheet No</label>
                                    <input type="number" class="form-control" id="logsheetNo1" name="logsheetNo1" required>
                                </div>
                            </div>
                        </div>

                        <!-- Need Fuel Checkbox -->
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label>Need Fuel</label>
                                    <div>
                                        <input type="radio" id="needFuelYes" name="needFuel" value="yes" required>
                                        <label for="needFuelYes">Yes</label>
                                        <input type="radio" id="needFuelNo" name="needFuel" value="no" required>
                                        <label for="needFuelNo">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                            <div class="form-group">
                                    <label for="feedback">Thumbs Up Status</label>
                                    <select class="form-control" id="feedback" name="feedback" required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="fuelFields" style="display: none;">
                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="RkmOperated">KM Operated</label>
                                        <input type="number" class="form-control" id="RkmOperated1" name="RkmOperated1">
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="Rhsd">HSD</label>
                                        <input type="number" class="form-control" id="Rhsd1" name="Rhsd1">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="Rkmpl">KMPL</label>
                                        <input type="text" class="form-control" id="Rkmpl1" name="Rkmpl1" readonly>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="driverDefect">Driver Defect Noticed</label>
                                    <select class="form-control" id="driverDefect1" name="driverDefect1" required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                            <div class="col" id="remarkContainer1" style="display: none;">
                                <div class="form-group">
                                    <label for="remark">Remark</label>
                                    <textarea class="form-control" id="remark1" name="remark1"></textarea>
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
        document.addEventListener("DOMContentLoaded", function () {
            const needFuelYes = document.getElementById("needFuelYes");
            const needFuelNo = document.getElementById("needFuelNo");
            const fuelFields = document.getElementById("fuelFields");
            const RkmOperated = document.getElementById("RkmOperated1");
            const Rhsd = document.getElementById("Rhsd1");

            needFuelYes.addEventListener("change", toggleFuelFields);
            needFuelNo.addEventListener("change", toggleFuelFields);

            function toggleFuelFields() {
                if (needFuelYes.checked) {
                    fuelFields.style.display = "block";
                    RkmOperated.required = true;
                    Rhsd.required = true;
                } else {
                    fuelFields.style.display = "none";
                    RkmOperated.required = false;
                    Rhsd.required = false;
                }
            }
        });
    </script>


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
            $('#driverDefectForm').on('submit', function (event) {
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
                        url: '../database/depot_driver_defect_insert.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json', // Expecting JSON response
                        success: function (response) {
                            if (response.status === 'success') {
                                // Redirect to depot_kmpl.php on success
                                alert('recored updated successfully');
                                window.location.href = 'depot_route_kmpl.php';
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

    <script>
        function openDefectModal(button) {
            // Get the row data
            var row = $(button).closest('tr');
            var id = row.find('td:eq(0)').text();
            var scheduleNo = row.find('td:eq(1)').text();
            var vehicleNo = row.find('td:eq(2)').text();
            var driverToken = row.find('td:eq(3)').text();
            var conductorToken = row.find('td:eq(4)').text();
            var arrivalTime = row.find('td:eq(6)').text();
            var make = row.find('td:eq(8)').text();
            var norms = row.find('td:eq(9)').text();


            // Set the modal input values
            $('#id1').val(id);
            $('#scheduleNo1').val(scheduleNo);
            $('#vehicleNo1').val(vehicleNo);
            $('#driverToken1').val(driverToken);
            $('#conductorToken1').val(conductorToken);
            $('#arrivalTime1').val(arrivalTime);

            // Open the modal
            $('#defectReceiveModal').modal('show');

            // Function to populate defect types
            function driverdefecttype() {
                $.ajax({
                    url: '../includes/data_fetch.php',
                    type: 'GET',
                    data: { action: 'driverdefecttype' },
                    success: function (response) {
                        var service = JSON.parse(response);

                        // Clear existing options
                        $('#driverDefect1').empty();

                        // Add default "Select" option
                        $('#driverDefect1').append('<option value="">Select</option>');

                        // Add fetched options
                        $.each(service, function (index, value) {
                            $('#driverDefect1').append('<option value="' + value.id + '">' + value.defect_name + '</option>');
                        });

                        // Trigger change event to set initial state
                        $('#driverDefect1').trigger('change');
                    }
                });
            }
            driverdefecttype();
        }

        // Handle the change event for driver defect select field
        $('#driverDefect1').on('change', function () {
            var selectedValue = $(this).val();
            if (selectedValue != '1') {
                $('#remarkContainer1').show();
                $('#remark1').prop('required', true);
            } else {
                $('#remarkContainer1').hide();
                $('#remark1').prop('required', false);
            }
        });

        // Ensure remark container is hidden on modal open
        $('#defectReceiveModal').on('shown.bs.modal', function () {
            $('#remarkContainer1').hide();
            $('#remark1').prop('required', false);
        });

        // Calculate Rkmpl when KM Operated or Rhsd changes
        $('#RkmOperated1, #Rhsd1').on('input', function () {
            var RkmOperated = parseFloat($('#RkmOperated1').val()) || 0;
            var Rhsd = parseFloat($('#Rhsd1').val()) || 0;

            // Calculate Rkmpl and set the value
            if (Rhsd > 0) {
                var Rkmpl1 = RkmOperated / Rhsd;
                $('#Rkmpl1').val(Rkmpl1.toFixed(2));
            } else {
                $('#Rkmpl1').val('');
            }
        });
    </script>
    <script>
        $(document).ready(function () {
            // Handle form submission
            $('#earlydriverDefectForm').on('submit', function (event) {
                event.preventDefault(); // Prevent the default form submission

                // Validate the form fields
                var isValid = true;
                var id = $('#id1').val();
                var logsheetNo = $('#logsheetNo1').val();
                var needFuel = $('input[name="needFuel"]:checked').val();
                var RkmOperated = $('#RkmOperated1').val();
                var Rhsd = $('#Rhsd1').val();
                var driverDefect = $('#driverDefect1').val();
                var remark = $('#remark1').val();

                if (!id) {
                    isValid = false;
                    alert('Something went wrong. Please refresh the page and try again.');
                }
                if (needFuel === 'yes') {
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
                }
                if (!driverDefect) {
                    isValid = false;
                    alert('Driver Defect is required.');
                }
                if (driverDefect !== '1' && !remark) {
                    isValid = false;
                    alert('Remark is required when Driver Defect is not None.');
                }

                if (isValid) {
                    // Gather form data
                    var formData = $(this).serialize();

                    // Submit the form data using AJAX
                    $.ajax({
                        url: '../database/depot_earlydriver_defect_insert.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json', // Expecting JSON response
                        success: function (response) {
                            if (response.status === 'success') {
                                // Redirect to depot_kmpl.php on success
                                alert('recored updated successfully');
                                window.location.href = 'depot_route_kmpl.php';
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