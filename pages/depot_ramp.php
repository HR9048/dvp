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
                    <th>Action</th>
                    <th class="d-none">remark</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT 
                    svi.id AS sch_in_id,
                    svi.*,
                    svo.*,
                    dd.defect_name
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
                    WHERE 
                    svo.division_id = '$division_id'
                    AND svo.depot_id = '$depot_id' 
                    AND svo.schedule_status = 3
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
                                    <td>" . $row["defect_name"] . "</td>
                                    <td class='d-none'>" . $row["driver_remark"] . "</td>
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
                                    <label for="arrivalTime">Arrival Time</label>
                                    <input type="text" class="form-control" id="arrivalTime" name="arrivalTime" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="defectname">Driver Defect Noticed</label>
                                    <input type="text" class="form-control" id="defectname" name="defectname" readonly>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" class="form-control" id="driverDefect" name="driverDefect" readonly>
                        <div class="row">
                            <div class="col" id="remarkContainer" style="display: none;">
                                <div class="form-group">
                                    <label for="remark">Remark</label>
                                    <input type="text" class="form-control" id="remark" name="remark" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="ramp_defect">Ramp Defect Noticed</label>
                                    <select name="ramp_defect" class="form-control" id="ramp_defect"></select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="col" id="remarkContainer1" style="display: none;">
                                    <div class="form-group">
                                        <label for="ramp_remark">Ramp Remark</label>
                                        <textarea class="form-control" id="ramp_remark" name="ramp_remark"></textarea>
                                    </div>
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
            var scheduleNo = row.find('td:eq(3)').text();
            var vehicleNo = row.find('td:eq(4)').text();
            var driverToken = row.find('td:eq(5)').text();
            var conductorToken = row.find('td:eq(6)').text();
            var arrivalTime = row.find('td:eq(7)').text();
            var defectname = row.find('td:eq(8)').text();
            var defecttype = row.find('td:eq(2)').text();
            var remark = row.find('td:eq(9)').text();
            var sch_in_id = row.find('td:eq(1)').text();

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

            // Show the remark container if defect type is not 1
            if (defecttype != '1') {
                $('#remarkContainer').show();
                $('#remark').prop('required', true);
            } else {
                $('#remarkContainer').hide();
                $('#remark').prop('required', false);
            }
            function rampdefecttype() {
                $.ajax({
                    url: '../includes/data_fetch.php',
                    type: 'GET',
                    data: { action: 'rampdefecttype' },
                    success: function (response) {
                        var service = JSON.parse(response);

                        // Clear existing options
                        $('#ramp_defect').empty();

                        // Add default "Select" option
                        $('#ramp_defect').append('<option value="">Select</option>');

                        // Add fetched options
                        $.each(service, function (index, value) {
                            $('#ramp_defect').append('<option value="' + value.id + '">' + value.defect_name + '</option>');
                        });

                        // Trigger change event to set initial state
                        $('#ramp_defect').trigger('change');
                    }
                });
            }
            rampdefecttype();
            // Open the modal
            $('#dataModal').modal('show');
        }

        // Ensure remark container is hidden on modal open
        $('#dataModal').on('shown.bs.modal', function () {
            var defecttype = $('#driverDefect').val();
            if (defecttype != '1') {
                $('#remarkContainer').show();
                $('#remark').prop('required', true);
            } else {
                $('#remarkContainer').hide();
                $('#remark').prop('required', false);
            }
        });
        // Handle the change event for ramp defect select field
        $('#ramp_defect').on('change', function () {
            var selectedValue = $(this).val();
            if (selectedValue != '1') {
                $('#remarkContainer1').show();
                $('#ramp_remark').prop('required', true);
            } else {
                $('#remarkContainer1').hide();
                $('#ramp_remark').prop('required', false);
            }
        });

        // Ensure remark container is hidden on modal open
        $('#dataModal').on('shown.bs.modal', function () {
            $('#remarkContainer1').hide();
            $('#ramp_remark').prop('required', false);
        });

        $(document).ready(function () {
            // Form submission
            $('#rampDefectForm').on('submit', function (e) {
                e.preventDefault(); // Prevent the form from submitting traditionally

                // Validate the form
                if (!validateForm()) {
                    return false; // Stop if validation fails
                }

                // Serialize the form data
                var formData = $(this).serialize();

                // Send the AJAX request
                $.ajax({
                    url: '../database/depot_route_ramp_data.php', // Ensure this path is correct
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        console.log("Raw Response: ", response); // Log the raw response

                        try {
                            var result = JSON.parse(response);

                            if (result.status === 'success') {
                                alert('Data updated successfully!');
                                $('#rampDefectForm').modal('hide');
                                location.reload(); // Reload the page to see the updated data
                            } else {
                                alert('Failed to update data: ' + result.message);
                            }
                        } catch (e) {
                            console.error('JSON Parsing Error: ', e, response);
                            alert('An error occurred while processing the response.');
                        }
                    },
                    error: function () {
                        alert('An error occurred while updating the data.');
                    }
                });

            });

            // Function to validate the form
            function validateForm() {
                var isValid = true;

                // Check if ID is present
                if ($('#sch_out_id').val().trim() === '') {
                    alert('something went wrong. Please refresh the page and try again.');
                    isValid = false;
                }
                if ($('#sch_in_id').val().trim() === '') {
                    alert('something went wrong2. Please refresh the page and try again.');
                    isValid = false;
                }
                if ($('#ramp_defect').val().trim() === '') {
                    alert('Pleaste select a ramp defect type');
                    isValid = false;
                }
                // Check if ramp remark is required and not empty
                var rampDefect = $('#ramp_defect').val();
                if ($('#remarkContainer1').is(':visible') && rampDefect !== '1' && $('#ramp_remark').val().trim() === '') {
                    alert('Please enter a ramp remark.');
                    isValid = false;
                }

                return isValid;
            }
        });
        $('.close').on('click', function (e) {
            e.preventDefault();
            // other code...
            $('#dataModal').modal('hide');

        });


    </script>





    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>