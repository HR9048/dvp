<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' ||$_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $scheduleId = $_POST['id'];
    $busNumber1 = $_POST['bus_number_1'];
    $busMake1 = $_POST['make1'];
    $busEmissionNorms1 = $_POST['emission_norms1'];
    // Check if bus number 2 details are present, otherwise set them to NULL
    $busNumber2 = !empty($_POST['bus_number_2']) ? $_POST['bus_number_2'] : NULL;
    $busMake2 = !empty($_POST['make2']) ? $_POST['make2'] : NULL;
    $busEmissionNorms2 = !empty($_POST['emission_norms2']) ? $_POST['emission_norms2'] : NULL;

    // Check if additional bus details are present, otherwise set them to NULL
    $additionalBusNumber = !empty($_POST['bus_number_3']) ? $_POST['bus_number_3'] : NULL;
    $additionalBusMake = !empty($_POST['make3']) ? $_POST['make3'] : NULL;
    $additionalBusEmissionNorms = !empty($_POST['emission_norms3']) ? $_POST['emission_norms3'] : NULL;


    // Update query for schedule_master table
    $sql = "UPDATE schedule_master SET 
                bus_number_1 = '$busNumber1',
                bus_make_1 = '$busMake1',
                bus_emission_norms_1 = '$busEmissionNorms1',
                bus_number_2 = " . ($busNumber2 !== NULL ? "'$busNumber2'" : "NULL") . ",
        bus_make_2 = " . ($busMake2 !== NULL ? "'$busMake2'" : "NULL") . ",
        bus_emission_norms_2 = " . ($busEmissionNorms2 !== NULL ? "'$busEmissionNorms2'" : "NULL") . ",
        additional_bus_number = " . ($additionalBusNumber !== NULL ? "'$additionalBusNumber'" : "NULL") . ",
        additional_bus_make = " . ($additionalBusMake !== NULL ? "'$additionalBusMake'" : "NULL") . ",
        additional_bus_emission_norms = " . ($additionalBusEmissionNorms !== NULL ? "'$additionalBusEmissionNorms'" : "NULL") . "
            WHERE id = $scheduleId";

    if (mysqli_query($db, $sql)) {
        echo "<script>
        alert('Schedule updated successfully');
        window.location.href = 'depot_schedule_b.php';
      </script>";
    } else {
        echo "<script>alert('Error updating schedule: " . $db->error . "');</script>";
    }
}
?>
<style>
    .hide {
        display: none;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-container h2 {
        margin: 0;
    }

    .header-container .center {
        text-align: center;
        flex-grow: 1;
    }
</style>
<div class="header-container">
    <h2>Depot: <?php echo $_SESSION['DEPOT']; ?></h2>
    <h2 class="center">SCHEDULE MASTER(BUS)</h2>
</div>
<table id="dataTable">
    <thead>
        <tr>
            <th class="hide">ID</th>
            <th>Key</th>
            <th>Description</th>
            <th>Sch Km</th>
            <th>Sch Dep Time</th>
            <th>Sch Arr Time</th>
            <th>Service Class</th>
            <th>Service Type</th>
            <th>Allotted Bus</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT 
        skm.*, 
        loc.division, 
        loc.depot, 
        sc.name AS service_class_name, 
        st.type AS service_type_name,
        skm.ID as ID
    FROM 
        schedule_master skm 
    JOIN 
        location loc 
        ON skm.division_id = loc.division_id 
        AND skm.depot_id = loc.depot_id
    LEFT JOIN 
        service_class sc 
        ON skm.service_class_id = sc.id
    LEFT JOIN 
        schedule_type st 
        ON skm.service_type_id = st.id
    WHERE 
        skm.division_id = '" . $_SESSION['DIVISION_ID'] . "' 
        AND skm.depot_id = '" . $_SESSION['DEPOT_ID'] . "'";

        $result = $db->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Combine bus numbers, driver tokens, and half-reliever tokens
                $bus_numbers = [$row['bus_number_1'], $row['bus_number_2']];

                echo '<tr data-id="' . $row['ID'] . '">
                <td class="hide">' . $row['ID'] . '</td>
                <td>' . $row['sch_key_no'] . '</td>
                <td>' . $row['sch_abbr'] . '</td>
                <td>' . $row['sch_km'] . '</td>
                <td>' . $row['sch_dep_time'] . '</td>
                <td>' . $row['sch_arr_time'] . '</td>
                <td>' . $row['service_class_name'] . '</td>
                <td>' . $row['service_type_name'] . '</td>
                <td>';
                foreach ($bus_numbers as $bus_number) {
                    if (!empty($bus_number)) {
                        echo $bus_number . '<br>';
                    }
                }
                echo '</td>
                <td>';
                if (empty($row['bus_number_1']) && empty($row['bus_number_2'])) {
                    echo '<button class="btn btn-warning update-details">Update</button>';
                } else {
                    echo '<button class="btn btn-primary view-details">Details</button>';
                }
                echo '</td>
            </tr>';
            }
        } else {
            echo '<tr><td colspan="12">No results found</td></tr>';
        }
        ?>
    </tbody>
</table>
<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Schedule Update</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="updateForm" method="post">
                    <input type="hidden" id="scheduleId" name="id">
                    <div id="scheduleFields"></div>
                    <div id="busFields"></div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="close" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Handle Update button click
        $('.update-details').on('click', function () {
            var scheduleId = $(this).closest('tr').data('id');
            $('#scheduleId').val(scheduleId);

            $.ajax({
                url: 'get_schedule_details.php',
                type: 'GET',
                data: { id: scheduleId },
                success: function (response) {
                    var details = JSON.parse(response);
                    var scheduleFieldsHtml = `
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="sch_key_no">Schedule Key Number</label>
                                <input type="text" class="form-control" id="sch_key_no" name="sch_key_no" value="${details.sch_key_no}" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="sch_abbr">Schedule Abbreviation</label>
                                <input type="text" class="form-control" id="sch_abbr" name="sch_abbr" value="${details.sch_abbr}" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="sch_km">Schedule KM</label>
                                <input type="text" class="form-control" id="sch_km" name="sch_km" value="${details.sch_km}" readonly>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="number_of_buses" name="number_of_buses" value="${details.number_of_buses}">
                    
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="sch_dep_time">Departure Time</label>
                                <input type="text" class="form-control" id="sch_dep_time" name="sch_dep_time" value="${details.sch_dep_time}" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="sch_arr_time">Arrival Time</label>
                                <input type="text" class="form-control" id="sch_arr_time" name="sch_arr_time" value="${details.sch_arr_time}" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="service_class_name">Service Class</label>
                                <input type="text" class="form-control" id="service_class_name" name="service_class_name" value="${details.service_class_name}" readonly>
                            </div>
                        </div>
                    </div>`;

                    var busFieldsHtml = '';
                    for (var i = 1; i <= details.number_of_buses; i++) {
                        busFieldsHtml += `
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="bus_number${i}">Bus ${i} Number</label>
                                    <input type="text" id="bus_number_${i}" name="bus_number_${i}" class="form-control" required oninput="this.value = this.value.toUpperCase()" onChange="searchBus(${i})">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="make${i}">Bus ${i} Make</label>
                                    <input type="text" id="make${i}" name="make${i}" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="emission_norms${i}">Bus ${i} Emission Norms</label>
                                    <input type="text" id="emission_norms${i}" name="emission_norms${i}" class="form-control" readonly>
                                </div>
                            </div>
                        </div>`;
                    }

                    // Append checkbox for additional bus
                    scheduleFieldsHtml += `
                    <div class="form-group">
                        <label for="agree">Have Additional Bus:</label>
                        <input type="checkbox" id="agree" name="agree" value="yes">
                    </div>`;

                    $('#scheduleFields').html(scheduleFieldsHtml);
                    $('#busFields').html(busFieldsHtml);
                    $('#updateModal').modal('show');

                    // Listen for checkbox change event
                    $('#agree').change(function () {
                        if (this.checked) {
                            // Append another set of bus fields for additional bus
                            var additionalBusHtml = `
                            <div class="row additional-bus-fields">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="bus_number_3">Additional Bus Number</label>
                                        <input type="text" id="bus_number_3" name="bus_number_3" class="form-control" required oninput="this.value = this.value.toUpperCase()" onChange="searchBus(3)">
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="make3">Additional Bus Make</label>
                                        <input type="text" id="make3" name="make3" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="emission_norms3">Additional Bus Emission Norms</label>
                                        <input type="text" id="emission_norms3" name="emission_norms3" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>`;
                            $('#busFields').append(additionalBusHtml);
                        } else {
                            // Remove additional bus fields if checkbox is unchecked
                            $('#busFields .additional-bus-fields').remove();
                        }
                    });
                },
                error: function (xhr, status, error) {
                    console.log('AJAX Error:', error);
                }
            });
        });
    });
    // Function to search for bus
    function searchBus(index) {
        var busNumber = $('#bus_number_' + index).val();

        // Check for duplicate bus numbers
        var busNumbers = [];
        $('input[id^="bus_number_"]').each(function () {
            if ($(this).val()) {
                busNumbers.push($(this).val());
            }
        });

        var duplicateBusNumber = busNumbers.filter((item, pos) => busNumbers.indexOf(item) !== pos);
        if (duplicateBusNumber.length > 0) {
            alert('Duplicate entry for bus number: ' + duplicateBusNumber[0]);
            $('#bus_number_' + index).val('');
            $('#make' + index).val('');
            $('#emission_norms' + index).val('');
            return;
        }

        $.ajax({
            url: 'dvp_bus_search1.php',
            type: 'POST',
            data: { busNumber: busNumber },
            dataType: 'json', // Specify the expected data type as JSON
            success: function (response) {
                if (response.make !== undefined && response.make !== null) {
                    // Populate form fields with fetched data
                    $('#make' + index).val(response.make);
                    $('#bus_number_' + index).val(busNumber);
                    $('#emission_norms' + index).val(response.emission_norms);
                } else {
                    // Clear the make and bus number fields if bus number not found
                    $('#make' + index).val('');
                    $('#bus_number_' + index).val('');
                    $('#emission_norms' + index).val('');
                }
            },
            error: function (xhr, status, error) {
                // Display error message
                if (xhr.status === 403) {
                    alert(xhr.responseJSON.error);
                } else {
                    alert('Error: Bus not Registered in KKRTC.');
                }
                // Clear the make and bus number fields if search failed
                $('#make' + index).val('');
                $('#bus_number_' + index).val('');
                $('#emission_norms' + index).val('');
            }
        });
    }
    $(document).ready(function () {
        // Close modal when clicking the close button
        $('#updateModal .close').on('click', function () {
            $('#updateModal').modal('hide');
        });

        // Close modal when clicking outside the modal
        $('#updateModal').on('click', function (event) {
            if ($(event.target).is('#updateModal')) {
                $('#updateModal').modal('hide');
            }
        });
    });



</script>

<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Schedule <span id="scheduleKeyNumber"></span> Details
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Details will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        // Close modal when clicking the close button
        $('#detailsModal .close').on('click', function () {
            $('#detailsModal').modal('hide');
        });

        // Close modal when clicking outside the modal
        $('#detailsModal').on('click', function (event) {
            if ($(event.target).is('#detailsModal')) {
                $('#detailsModal').modal('hide');
            }
        });
    });
    $(document).ready(function () {
        // Close modal when clicking the close button
        $('#detailsModal .modal-footer .btn-secondary').on('click', function () {
            $('#detailsModal').modal('hide');
        });
    });
    // JavaScript code provided earlier goes here
    $(document).ready(function () {
        $('.view-details').on('click', function () {
            var scheduleId = $(this).closest('tr').data('id');

            $.ajax({
                url: 'get_schedule_details.php',
                type: 'GET',
                data: { id: scheduleId },
                success: function (response) {
                    var details = JSON.parse(response);
                    $('#scheduleKeyNumber').text(details.sch_key_no);

                    var detailsHtml = '<table class="table table-bordered table-striped"><tbody>';

                    const excludedFields = ['id', 'division_id', 'depot_id', 'submitted_datetime', 'username'];
                    const fieldOrder = [
                        'sch_key_no', 'sch_abbr', 'sch_km', 'sch_dep_time', 'sch_arr_time', 'sch_count',
                        'service_class_name', 'service_type_name', 'number_of_buses',
                        'bus_number_1', 'bus_make_1', 'bus_emission_norms_1',
                        'bus_number_2', 'bus_make_2', 'bus_emission_norms_2',
                        'additional_bus_number', 'additional_bus_make', 'additional_bus_emission_norms',
                    ];
                    const fieldNames = {
                        'sch_key_no': 'Schedule Key Number',
                        'sch_abbr': 'Schedule Abbreviation',
                        'sch_km': 'Schedule KM',
                        'sch_dep_time': 'Departure Time',
                        'sch_arr_time': 'Arrival Time',
                        'sch_count': 'Schedule Count',
                        'service_class_name': 'Service Class',
                        'service_type_name': 'Service Type',
                        'number_of_buses': 'Number of Buses',
                        'bus_number_1': 'Bus 1 Number',
                        'bus_make_1': 'Bus 1 Make',
                        'bus_emission_norms_1': 'Bus 1 Emission Norms',
                        'bus_number_2': 'Bus 2 Number',
                        'bus_make_2': 'Bus 2 Make',
                        'bus_emission_norms_2': 'Bus 2 Emission Norms',
                        'additional_bus_number': 'Additional Bus Number',
                        'additional_bus_make': 'Additional Bus Make',
                        'additional_bus_emission_norms': 'Additional Bus Emission Norms',
                    };

                    let count = 0;
                    detailsHtml += '<tr>';
                    fieldOrder.forEach(function (key) {
                        if (details[key] && !excludedFields.includes(key)) {
                            if (count === 3) {
                                detailsHtml += '</tr><tr>';
                                count = 0;
                            }
                            var displayName = fieldNames[key] || key.replace(/_/g, ' ').toUpperCase();
                            detailsHtml += '<td><strong>' + displayName + ':</strong> ' + details[key] + '</td>';
                            count++;
                        }
                    });
                    detailsHtml += '</tr></tbody></table>';

                    $('#detailsModal .modal-body').html(detailsHtml);
                    $('#detailsModal').modal('show');
                },
                error: function (xhr, status, error) {
                    console.log('AJAX Error:', error);
                }
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