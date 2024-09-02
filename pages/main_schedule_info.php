<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    ?>
    <style>
        .nav-link.custom-size {
            font-size: 1.25rem;
            /* Increase font size */
            padding: 0.75rem 1.25rem;
            /* Increase padding */
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
            <style>
                .hide {
                    display: none;
                }
            </style>
            <br>
            <table id="dataTable">
                <thead>
                    <tr>
                        <th class="hide">ID</th>
                        <th>Division1</th>
                        <th>Depot</th>
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
        order by loc.division_id,loc.depot_id,sch_dep_time";

                    $result = $db->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Combine bus numbers, driver tokens, and half-reliever tokens
                            $bus_numbers = [$row['bus_number_1'], $row['bus_number_2']];

                            echo '<tr data-id="' . $row['ID'] . '">
                <td class="hide">' . $row['ID'] . '</td>
                <td>' . $row['division'] . '</td>
                <td>' . $row['depot'] . '</td>
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

                            echo '<button class="btn btn-primary view-details_bus">Details</button>';

                            echo '</td>
            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="12">No results found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab"><br>
            <table id="dataTable1">
                <thead>
                    <tr>
                        <th class="hide">ID</th>
                        <th>Division</th>
                        <th>Depot</th>
                        <th>Key</th>
                        <th>Description</th>
                        <th>Sch Km</th>
                        <th>Sch Dep Time</th>
                        <th>Sch Arr Time</th>
                        <th>Service Class</th>
                        <th>Service Type</th>
                        <th>Allotted Driver</th>
                        <th>Allotted Conductor</th>
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
    order by loc.division_id,loc.depot_id,sch_dep_time";

                    $result = $db->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Combine bus numbers, driver tokens, and half-reliever tokens
                            $driver_tokens = [$row['driver_token_1'], $row['driver_token_2'], $row['driver_token_3'], $row['driver_token_4'], $row['driver_token_5'], $row['driver_token_6']];
                            $conductor_tokens = [$row['conductor_token_1'], $row['conductor_token_2'], $row['conductor_token_3']];

                            // Check if all conductor tokens are null or empty
                            if (($row['single_crew'] === 'yes')) {
                                $conductor_tokens = ['Single Crew Operation'];
                            }
                            echo '<tr data-id="' . $row['ID'] . '">
                <td class="hide">' . $row['ID'] . '</td>
                <td>' . $row['division'] . '</td>
                <td>' . $row['depot'] . '</td>
                <td>' . $row['sch_key_no'] . '</td>
                <td>' . $row['sch_abbr'] . '</td>
                <td>' . $row['sch_km'] . '</td>
                <td>' . $row['sch_dep_time'] . '</td>
                <td>' . $row['sch_arr_time'] . '</td>
                <td>' . $row['service_class_name'] . '</td>
                <td>' . $row['service_type_name'] . '</td>
                <td>';
                            foreach ($driver_tokens as $driver_token) {
                                if (!empty($driver_token)) {
                                    echo $driver_token . '<br>';
                                }
                            }
                            echo '</td>
                <td>';
                            foreach ($conductor_tokens as $conductor_token) {
                                if (!empty($conductor_token)) {
                                    echo $conductor_token . '<br>';
                                }
                            }
                            echo '</td>
                <td>';

                            echo '<button class="btn btn-primary view-details">Details</button>';

                            echo '</td>
            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="11">No results found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
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
            $('.view-details_bus').on('click', function () {
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
                            'service_class_name', 'service_type_name', 'single_crew',
                            'driver_token_1', 'driver_pf_1', 'driver_name_1',
                            'driver_token_2', 'driver_pf_2', 'driver_name_2',
                            'driver_token_3', 'driver_pf_3', 'driver_name_3',
                            'driver_token_4', 'driver_pf_4', 'driver_name_4',
                            'driver_token_5', 'driver_pf_5', 'driver_name_5',
                            'driver_token_6', 'driver_pf_6', 'driver_name_6',
                            'conductor_token_1', 'conductor_pf_1', 'conductor_name_1',
                            'conductor_token_2', 'conductor_pf_2', 'conductor_name_2',
                            'conductor_token_3', 'conductor_pf_3', 'conductor_name_3',


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
                            'single_crew': 'Single Crew',
                            'driver_token_1': 'Driver 1 Token',
                            'driver_pf_1': 'Driver 1 PF',
                            'driver_name_1': 'Driver 1 Name',
                            'driver_token_2': 'Driver 2 Token',
                            'driver_pf_2': 'Driver 2 PF',
                            'driver_name_2': 'Driver 2 Name',
                            'driver_token_3': 'Driver 3 Token',
                            'driver_pf_3': 'Driver 3 PF',
                            'driver_name_3': 'Driver 3 Name',
                            'driver_token_4': 'Driver 4 Token',
                            'driver_pf_4': 'Driver 4 PF',
                            'driver_name_4': 'Driver 4 Name',
                            'driver_token_5': 'Driver 5 Token',
                            'driver_pf_5': 'Driver 5 PF',
                            'driver_name_5': 'Driver 5 Name',
                            'driver_token_6': 'Driver 6 Token',
                            'driver_pf_6': 'Driver 6 PF',
                            'driver_name_6': 'Driver 6 Name',
                            'conductor_token_1': 'Conductor 1 Token',
                            'conductor_pf_1': 'Conductor 1 PF',
                            'conductor_name_1': 'Conductor 1 Name',
                            'conductor_token_2': 'Conductor 2 Token',
                            'conductor_pf_2': 'Conductor 2 PF',
                            'conductor_name_2': 'Conductor 2 Name',
                            'conductor_token_3': 'Conductor 3 Token',
                            'conductor_pf_3': 'Conductor 3 PF',
                            'conductor_name_3': 'Conductor 3 Name',
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
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>