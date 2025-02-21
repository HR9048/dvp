<?php
include 'includes/connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>KKRTC - Sch-Live</title>
    <link rel="icon" href="images/logo1.jpeg">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="pages/style.css">

    <!-- Include jQuery library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Bootstrap JavaScript -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <!-- Bootstrap core CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
        integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

    <!-- Custom fonts for this template -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
</head>
</head>

<body id="page-top">

    <div id="wrapper">

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content" style="max-width: 100%; overflow-x: hidden;">
                <div class="fixed-top">
                    <div
                        style="display: flex; align-items: center; justify-content: center; background-color: #3c97bf; padding: 0px 0px;">
                        <div style="margin-right: 10px; margin-left: auto;padding-left:20px">
                            <img src="images/kkrtclogo.png" alt="alternatetext" width="45" height="45">
                        </div>
                        <div class="navcenter" style="text-align: center; flex: 1;">
                            <h6 style="color: white; margin: 0;"><b>ಕಲ್ಯಾಣ ಕರ್ನಾಟಕ ರಸ್ತೆ ಸಾರಿಗೆ ನಿಗಮ</b></h6>
                            <p style="color: white; margin: 0;">ಅನುಸೂಚಿ ಕಾರ್ಯಚರಣೆ ಸ್ಥಿತಿಗತಿ</p>
                        </div>
                    </div>

                </div>
                <br><br>
                <!-- End of Topbar -->
                <script>
                    $(document).ready(function() {
                        // Close dropdown when clicking outside
                        $(document).click(function(e) {
                            var target = e.target;
                            if (!$(target).is('.dropdown-toggle') && !$(target).parents().is('.dropdown-menu')) {
                                $('.dropdown-menu').removeClass('show');
                            }
                        });

                        // Open dropdown when clicking dropdown toggle
                        $('.dropdown-toggle').click(function() {
                            var dropdownMenu = $(this).next('.dropdown-menu');
                            $('.dropdown-menu').not(dropdownMenu).removeClass('show');
                            dropdownMenu.toggleClass('show');
                        });
                    });
                </script>
                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Bootstrap CSS -->
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

                    <style>
                        /* Default Table Styling */
                        .table-container {
                            overflow-x: auto;
                            max-width: 100%;
                            width: 100%;
                        }

                        th,
                        td {
                            border: 1px solid black;
                            padding: 0px;
                            text-align: center;
                            cursor: pointer;
                            word-break: break-word;
                            white-space: normal;
                            font-size: 16px;
                            /* Default font size for desktop */
                        }

                        th {
                            background-color: #f2f2f2;
                        }

                        /* Highlight Division & Overall Total Rows */
                        .division-total {
                            font-weight: bold;
                            background-color: #dff0d8;
                        }

                        .overall-total {
                            font-weight: bold;
                            background-color: #f7c6c7;
                        }

                        /* Full-Screen Modal */
                        .custom-modal {
                            max-width: 90vw !important;
                            /* 90% width on desktop */
                            margin: auto;
                        }

                        /* Ensure table inside modal is scrollable */
                        .table-responsive {
                            overflow-x: auto;
                            -webkit-overflow-scrolling: touch;
                        }

                        /* Mobile View Adjustments */
                        @media (max-width: 768px) {

                            /* Full-Screen Modal on Mobile */
                            .custom-modal {
                                width: 100vw !important;
                                /* Full width */
                                height: 100vh !important;
                                /* Full height */
                                margin: 0;
                            }

                            /* Make modal content fill the screen */
                            .modal-content {
                                height: 100vh;
                                display: flex;
                                flex-direction: column;
                            }

                            /* Allow body to scroll */
                            .modal-body {
                                flex-grow: 1;
                                overflow-y: auto;
                            }

                            .my-3 {
                                margin-top: 0rem !important;
                                margin-bottom: 0rem !important;
                            }

                            /* Reduce font size for mobile */
                            th,
                            td {
                                font-size: 10px;
                                /* Smaller font on mobile */
                                padding: 0px !important;
                                margin-top: 0px !important;
                            }

                            h2 {
                                font-size: 12px;
                                /* Smaller font on mobile */
                            }

                            /* Adjust modal title font size */
                            .modal-title {
                                font-size: 14px;
                            }

                            /* Reduce padding for better spacing */
                            .modal-body {
                                padding: 8px;
                            }

                            .modal-footer {
                                padding: 8px;
                            }

                            .desc-column {
                                width: 25%;
                                word-wrap: break-word;
                            }
                        }


                        /* Desktop View - Modal Covers 90% Width */
                        .custom-modal {
                            max-width: 100vw !important;
                            /* 90% of viewport width */
                            margin: auto;
                        }

                        /* Fix Description Column Width (20% of Modal) */
                        .desc-column {
                            width: 20%;
                            word-wrap: break-word;
                        }

                        /* Ensure Table Stays Inside Modal */
                        .table-responsive {
                            overflow-x: auto;
                            -webkit-overflow-scrolling: touch;
                        }

                        /* Center the modal and make it Full-Screen on Mobile */
                        @media (max-width: 768px) {
                            .custom-modal {
                                width: 100vw !important;
                                /* Full screen width */
                                height: 100vh !important;
                                /* Full screen height */
                                margin: 0;
                            }

                            /* Adjust modal content */
                            .modal-content {
                                height: auto;
                                /* Full height */
                                display: flex;
                                flex-direction: column;
                            }

                            /* Ensure the modal body scrolls instead of cutting off content */
                            .modal-body {
                                flex-grow: 1;
                                overflow-y: auto;
                            }

                            /* Table is fully responsive */
                            .table {
                                width: 100%;
                                table-layout: auto;
                                word-wrap: break-word;
                            }
                        }

                        .late-time {
                            color: red;
                            font-weight: bold;
                        }

                        /* Light Green Background for Division Total Row */
                        .division-total td {
                            background-color: #d4edda !important;
                            /* Light Green */
                            font-weight: bold;
                            color: #155724;
                            /* Dark Green Text */
                        }

                        /* Light Red Background for Corporation Total Row */
                        .overall-total td {
                            background-color: #f8d7da !important;
                            /* Light Red */
                            font-weight: bold;
                            color: #721c24;
                            /* Dark Red Text */
                        }

                        /* Clickable Fields Styling */
                        .clickable {
                            cursor: pointer;
                            text-decoration: underline;
                            color: #007bff;
                            /* Blue */
                        }

                        .clickable:hover {
                            color: #0056b3;
                            /* Darker Blue */
                        }
                    </style>

                    <h2 class="text-center my-3">LIVE DEPARTURES REPORT ON <span id="current-time"></span> @ <span id="current-time1"></span></h2>

                    <div class="table-container">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Division</th>
                                    <th style="width:17%">Depot</th>
                                    <th>Sche Dep</th>
                                    <th>Act Dep</th>
                                    <th>Diff</th>
                                    <th>Late Dep</th>
                                </tr>
                            </thead>
                            <tbody id="report-body">
                                <!-- Data will be loaded here -->
                            </tbody>
                            <tfoot id="overall-total-row">
                                <!-- Overall total will be added here -->
                            </tfoot>
                        </table>
                    </div>
                    <!-- Bootstrap Modal for Difference -->
                    <div class="modal fade" id="difference-modal" tabindex="-1" aria-labelledby="difference-modal-title" aria-hidden="true">
                        <div class="modal-dialog custom-modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="difference-modal-title">Difference Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Sl.No</th>
                                                    <th>Sch No</th>
                                                    <th class="desc-column">Description</th>
                                                    <th>Service Class</th>
                                                    <th>Sch Dep Time</th>
                                                </tr>
                                            </thead>
                                            <tbody id="difference-modal-body">
                                                <tr>
                                                    <td colspan="5" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bootstrap Modal for Late Departures -->
                    <div class="modal fade" id="late-modal" tabindex="-1" aria-labelledby="late-modal-title" aria-hidden="true">
                        <div class="modal-dialog custom-modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="late-modal-title">Late Departures Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Sl.No</th>
                                                    <th>Sch No</th>
                                                    <th style="display:none;">division</th>
                                                    <th style="display:none;">depot</th>
                                                    <th class="desc-column">Description</th>
                                                    <th>Service Class</th>
                                                    <th>Sch Dep Time</th>
                                                    <th>Act Dep Time</th>
                                                    <th>Late By</th>
                                                </tr>
                                            </thead>
                                            <tbody id="late-modal-body">
                                                <tr>
                                                    <td colspan="7" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div id="scheduleModal" class="modal fade" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-xl" role="document"> <!-- Added modal-xl for large width -->
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><span id="modalDepotName"></span> |
                                        Schedules: <span id="modalScheduleCount">0</span> |
                                        Departures: <span id="modalDepartureCount">0</span>
                                    </h5> <!-- Added span -->
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body text-center">
                                    <!-- Data will be inserted here via AJAX -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Details Modal -->
                    <div class="modal fade" id="schedule-details-modal" tabindex="-1" aria-labelledby="schedule-details-modal-title" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-fullscreen-md-down">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="schedule-details-modal-title">
                                        Sch No: <span id="modal-sch-no"></span> |
                                        Description: <span id="modal-description"></span> |
                                        Service Class: <span id="modal-service-class"></span> |
                                        Sch Dep Time: <span id="modal-sch-dep-time"></span>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Loading message -->
                                    <div id="loading-message" class="text-center">
                                        <p>Loading schedule details...</p>
                                    </div>

                                    <!-- Data Table (Hidden Initially) -->
                                    <div class="table-responsive">
                                        <table class="table table-striped d-none" id="schedule-details-table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Dep Time</th>
                                                    <th>Time Diff</th>
                                                    <th>Driver Fixed</th>
                                                    <th>Vehicle Fixed</th>
                                                </tr>
                                            </thead>
                                            <tbody id="schedule-details-modal-body">
                                                <!-- Data will be injected here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="offroadModal" class="modal fade" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-xl" role="document"> <!-- Full-width modal -->
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Off-Road Vehicles</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body text-center">
                                    <!-- Data will be inserted here via AJAX -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        function fetchLiveDepartures() {
                            $.ajax({
                                url: "database/fetch_live_departures.php",
                                method: "GET",
                                dataType: "json",
                                success: function(response) {
                                    $("#current-time").text(response.time);
                                    $("#current-time1").text(response.time1);

                                    let tableContent = "";
                                    let lastDivision = "";
                                    let divisionData = {
                                        total_schedules: 0,
                                        actual_schedules: 0,
                                        difference: 0,
                                        late_departures: 0
                                    };
                                    let overallTotal = {
                                        total_schedules: 0,
                                        actual_schedules: 0,
                                        difference: 0,
                                        late_departures: 0
                                    };

                                    response.data.forEach((row, index) => {
                                        if (lastDivision && lastDivision !== row.division) {
                                            tableContent += `<tr class="division-total">
                        <td colspan="2" class="clickable" onclick="opendepotschdetails('${lastDivision}', 'Division')">${lastDivision} Total</td>
                        <td>${divisionData.total_schedules}</td>
                        <td>${divisionData.actual_schedules}</td>
                        <td class="clickable" onclick="openModal('${lastDivision}', 'difference', 'Division')">${divisionData.difference}</td>
                        <td class="clickable" onclick="openModal('${lastDivision}', 'late', 'Division')">${divisionData.late_departures}</td>
                    </tr>`;
                                            divisionData = {
                                                total_schedules: 0,
                                                actual_schedules: 0,
                                                difference: 0,
                                                late_departures: 0
                                            };
                                        }

                                        tableContent += `<tr>
                    <td>${row.division}</td>
                    <td class="clickable" onclick="opendepotschdetails('${row.depot}', 'Depot')">${row.depot}</td>
                    <td>${row.total_schedules}</td>
                    <td>${row.actual_schedules}</td>
                    <td class="clickable" onclick="openModal('${row.depot}', 'difference', 'Depot')">${row.difference}</td>
                    <td class="clickable" onclick="openModal('${row.depot}', 'late', 'Depot')">${row.late_departures}</td>
                </tr>`;

                                        divisionData.total_schedules += parseInt(row.total_schedules);
                                        divisionData.actual_schedules += parseInt(row.actual_schedules);
                                        divisionData.difference += parseInt(row.difference);
                                        divisionData.late_departures += parseInt(row.late_departures);

                                        overallTotal.total_schedules += parseInt(row.total_schedules);
                                        overallTotal.actual_schedules += parseInt(row.actual_schedules);
                                        overallTotal.difference += parseInt(row.difference);
                                        overallTotal.late_departures += parseInt(row.late_departures);

                                        lastDivision = row.division;
                                    });

                                    tableContent += `<tr class="division-total">
                <td colspan="2" class="clickable" onclick="opendepotschdetails('${lastDivision}', 'Division')">${lastDivision} Total</td>
                <td>${divisionData.total_schedules}</td>
                <td>${divisionData.actual_schedules}</td>
                <td class="clickable" onclick="openModal('${lastDivision}', 'difference', 'Division')">${divisionData.difference}</td>
                <td class="clickable" onclick="openModal('${lastDivision}', 'late', 'Division')">${divisionData.late_departures}</td>
            </tr>`;

                                    $("#overall-total-row").html(`<tr class="overall-total">
                <td colspan="2">Corporation Total</td>
                <td>${overallTotal.total_schedules}</td>
                <td>${overallTotal.actual_schedules}</td>
                <td>${overallTotal.difference}</td>
                <td>${overallTotal.late_departures}</td>
            </tr>`);

                                    $("#report-body").html(tableContent);
                                }
                            });
                        }

                        function openModal(id, type, location) {
                            let modalId = type === 'difference' ? '#difference-modal' : '#late-modal';
                            let modalBodyId = type === 'difference' ? '#difference-modal-body' : '#late-modal-body';

                            $(modalId + " .modal-title").text(`${type === 'difference' ? "Difference" : "Late Departures"} Details for ${id}`);
                            $(modalBodyId).html("<tr><td colspan='8' class='text-center'>Loading...</td></tr>");
                            
                            $.ajax({
                                url: "database/fetch_schedule_details.php",
                                method: "POST",
                                data: {
                                    id: id,
                                    type: type,
                                    location: location
                                },
                                dataType: "json",
                                success: function(data) {
                                    let content = "";
                                    if (data.length === 0) {
                                        content = "<tr><td colspan='8' class='text-center'>No data available</td></tr>";
                                    } else {
                                        content = data.map((row, index) => {
                                            if (type === 'difference') {
                                                return `<tr>
                            <td>${index + 1}</td>
                            <td>${row.sch_key_no}</td>
                            <td>${row.sch_abbr}</td>
                            <td>${row.name}</td>
                            <td>${row.sch_dep_time}</td>
                        </tr>`;
                                            } else {
                                                let lateTime = formatLateTime(row.late_by);
                                                return `<tr>
                            <td>${index + 1}</td>
                            <td onclick="fetchScheduleDetails('${row.sch_key_no}', '${row.division_id}', '${row.depot_id}', '${row.sch_abbr}', '${row.name}', '${row.sch_dep_time}')">${row.sch_key_no}</td>
                            <td style="display:none;">${row.division_id}</td>
                            <td style="display:none;">${row.depot_id}</td>
                            <td>${row.sch_abbr}</td>
                            <td>${row.name}</td>
                            <td>${row.sch_dep_time}</td>
                            <td>${row.act_dep_time}</td>
                            <td>${lateTime}</td>
                        </tr>`;
                                            }
                                        }).join("");
                                    }
                                    $(modalBodyId).html(content);
                                }
                            });

                            $(modalId).modal("show");
                        }

                        function fetchScheduleDetails(schNo, divisionId, depotId, description, serviceClass, schDepTime) {
                            // Open modal immediately and show loading message
                            $('#modal-sch-no').text(schNo);
                            $('#modal-description').text(description);
                            $('#modal-service-class').text(serviceClass);
                            $('#modal-sch-dep-time').text(schDepTime);
                            $('#schedule-details-modal').modal('show');
                            $('#loading-message').show();
                            $('#schedule-details-table').addClass('d-none');

                            $.ajax({
                                url: 'database/fetch_late_departure_details.php', // Your PHP script
                                type: 'POST',
                                data: {
                                    sch_no: schNo,
                                    division_id: divisionId,
                                    depot_id: depotId
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        let html = '';

                                        response.data.forEach((row) => {
                                            let dateParts = row.date.split('-'); // Assuming format is YYYY-MM-DD
                                            let formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                                            let lateByText = formatLateBy(row.late_by);

                                            html += `<tr>
                        <td>${formattedDate}</td>
                        <td>${row.dep_time}</td>
                        <td>${lateByText}</td>
                        <td>${row.driver_fixed == 0 ? '✔️' : '❌'}</td>
                        <td>${row.vehicle_fixed == 0 ? '✔️' : '❌'}</td>
                    </tr>`;
                                        });

                                        $('#schedule-details-modal-body').html(html);
                                        $('#loading-message').hide();
                                        $('#schedule-details-table').removeClass('d-none');
                                    } else {
                                        $('#schedule-details-modal-body').html('<tr><td colspan="5" class="text-center">No records found.</td></tr>');
                                        $('#loading-message').hide();
                                        $('#schedule-details-table').removeClass('d-none');
                                    }
                                },
                                error: function() {
                                    alert('Error fetching schedule details.');
                                }
                            });
                        }

                        function formatLateTime(minutes) {
                            let h = Math.floor(minutes / 60);
                            let m = minutes % 60;
                            return h > 0 ? `${h}h${m}m` : `${m}m`;
                        }

                        function formatLateBy(minutes) {
                            let absMinutes = Math.abs(minutes);
                            let hours = Math.floor(absMinutes / 60);
                            let mins = absMinutes % 60;
                            let formattedTime = `${hours}h ${mins}m`;

                            if (minutes > 30) {
                                return `<span class="late-time">${formattedTime}</span>`; // Late
                            } else {
                                return `On Time`;
                            }
                        }

                        function opendepotschdetails(name, type) {
                            let headerLabel = (type === "Depot") ? "Depot: " + name : "Division: " + name;

                            // Update the modal header and reset counts
                            $("#modalDepotName").text(headerLabel);
                            
                            $("#modalScheduleCount").text("Loading...");
                            $("#modalDepartureCount").text("Loading...");

                            // Show the modal first with a loading message
                            $("#scheduleModal .modal-body").html("<p class='text-center'>Loading Schedules data, please wait...</p>");
                            $("#scheduleModal").modal("show");

                            $.ajax({
                                url: "database/sch_live_fetch_all_schedule.php",
                                type: "POST",
                                data: {
                                    name: name,
                                    type: type
                                },
                                dataType: "json",
                                success: function(response) {
                                    if (response.status === "success") {
                                        $("#scheduleModal .modal-body").html(response.html);

                                        // Update the modal header with schedule & departure counts
                                        $("#modalScheduleCount").text(response.schedule_count);
                                        $("#modalDepartureCount").text(response.departure_count);
                                    } else {
                                        $("#scheduleModal .modal-body").html("<p class='text-center text-danger'>No data found.</p>");
                                        $("#modalScheduleCount").text(0);
                                        $("#modalDepartureCount").text(0);
                                    }
                                },
                                error: function() {
                                    $("#scheduleModal .modal-body").html("<p class='text-center text-danger'>Error fetching data.</p>");
                                    $("#modalScheduleCount").text(0);
                                    $("#modalDepartureCount").text(0);
                                }
                            });
                        }



                        setInterval(fetchLiveDepartures, 5000);
                        fetchLiveDepartures();
                    </script>


                </div>
                <!-- /.container-fluid -->

                <div class="accordion" id="accordionExample">
                    <!-- Accordion Item 1 -->
                    <div class="card">
                        <div class="card-header" id="headingOne">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    Operational Statistics <i class="fas fa-chevron-down float-right"></i>
                                </button>
                            </h2>
                        </div>
                        <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample">
                            <div class="card-body">
                                <label for="division">Division:</label>
                                <select id="division" name="division" required>
                                    <option value="">Select</option>
                                </select>

                                <label for="depot">Depot:</label>
                                <select id="depot" name="depot" required>
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Accordion Item 2 -->
                    <div class="card">
                        <div class="card-header" id="headingTwo">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Off-Road <i class="fas fa-chevron-down float-right"></i>
                                </button>
                            </h2>
                        </div>
                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
                            <div class="card-body">
                                <div id="loadingMessage" style="display: none;">Loading Off-Road data, please wait...</div>
                                <div id="offRoadDataTable" class="table-responsive"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 3 -->
                    <div class="card">
                        <div class="card-header" id="headingThree">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    KMPL <i class="fas fa-chevron-down float-right"></i>
                                </button>
                            </h2>
                        </div>
                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample" data-loaded="false">
                            <div class="card-body">
                                <div id="kmpl-content">Loading KMPL data, Please Wait...</div>
                            </div>
                        </div>
                    </div>


                    <!-- Accordion Item 4 -->
                    <div class="card">
                        <div class="card-header" id="headingFour">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Commercial Data <i class="fas fa-chevron-down float-right"></i>
                                </button>
                            </h2>
                        </div>
                        <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordionExample">
                            <div class="card-body">
                                This is the content of the third accordion item.
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Item 5 -->
                    <div class="card">
                        <div class="card-header" id="headingFive">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Commercial Stall <i class="fas fa-chevron-down float-right"></i>
                                </button>
                            </h2>
                        </div>
                        <div id="collapseFive" class="collapse" aria-labelledby="headingFive" data-parent="#accordionExample">
                            <div class="card-body">
                                This is the content of the third accordion item.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of Main Content -->
            <!-- Include jQuery library -->

            <!-- Include Bootstrap JavaScript -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"
                crossorigin="anonymous"></script>
            <!-- Modal for PDF -->
            <div class="modal fade" id="operationalStatisticsMod" tabindex="-1" role="dialog" aria-labelledby="modalTitle"
                aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Operational Statistics</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <iframe id="pdfViewer" src="" width="100%" height="500px"></iframe>
                            <p id="mobileWarning" style="display: none; text-align: center; color: red;">
                                PDF preview may not work in some mobile browsers. <a id="openPdfDirectly" href="#" target="_blank">Click here to view</a>.
                            </p>
                        </div>
                        <a id="downloadBtn" href="#" class="btn btn-primary" download>Download PDF</a>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    function fetchOperationalStatistics() {
                        var divisionId = $("#division").val();
                        var depotId = $("#depot").val();

                        if (divisionId !== "" && depotId !== "") {
                            $.ajax({
                                url: "includes/backend_data.php",
                                type: "POST",
                                data: {
                                    action: "fetchLatestFile",
                                    division: divisionId,
                                    depot: depotId
                                },
                                dataType: "json", // Ensure the response is treated as JSON
                                success: function(response) {
                                    if (!response || response.file === "no_file") {
                                        Swal.fire({
                                            icon: "error",
                                            title: "No File Found",
                                            text: "No operational statistics file found for this selection."
                                        });
                                        // Reset division and depot selection
                                        $("#division").val("");
                                        $("#depot").html('<option value="">Depot</option>');
                                        return;
                                    }

                                    let fileName = response.file.trim();
                                    let reportDate = response.date || "";
                                    let formattedDate = "";

                                    if (reportDate) {
                                        let dateParts = reportDate.split("-");
                                        formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                                    }

                                    let filePath = "../uploads/" + fileName;
                                    let filePath2 = "uploads/" + fileName;
                                    let googleDocsUrl = "https://docs.google.com/gview?embedded=true&url=http://117.203.105.106:8880/" + encodeURIComponent(filePath2);

                                    let divisionText = $("#division option:selected").text();
                                    let depotText = $("#depot option:selected").text();

                                    $(".modal-title").text(`Operational Statistics Details for Division: ${divisionText}, Depot: ${depotText} on Date: ${formattedDate}`);

                                    if (/Android|iPhone|iPad/i.test(navigator.userAgent)) {
                                        $("#pdfViewer").attr("src", googleDocsUrl);
                                    } else {
                                        $("#pdfViewer").attr("src", filePath);
                                    }

                                    $("#downloadBtn").attr("href", filePath);
                                    $("#operationalStatisticsMod").modal("show");
                                },
                                error: function(xhr, status, error) {
                                    console.error("AJAX Error:", error);
                                    Swal.fire({
                                        icon: "error",
                                        title: "Error",
                                        text: "Something went wrong. Please try again!"
                                    });
                                }
                            });
                        }
                    }

                    $("#division, #depot").change(fetchOperationalStatistics);

                    $("#operationalStatisticsMod").on("hidden.bs.modal", function() {
                        $("#division").val("");
                        $("#depot").html('<option value="">Depot</option>');
                        $("#pdfViewer").attr("src", "");
                        $("#downloadBtn").attr("href", "#");
                        $("#mobileWarning").hide();
                    });
                });
            </script>

            <script>
                function fetchBusCategory() {
                    $.ajax({
                        url: 'includes/data_fetch.php',
                        type: 'GET',
                        data: {
                            action: 'fetchDivision'
                        },
                        success: function(response) {
                            var divisions = JSON.parse(response);
                            $.each(divisions, function(index, division) {
                                if (division.DIVISION !== 'HEAD-OFFICE' && division.DIVISION !== 'RWY') {
                                    $('#division').append('<option value="' + division.division_id + '">' + division
                                        .DIVISION + '</option>');
                                }
                            });
                        }
                    });

                    $('#division').change(function() {
                        var Division = $(this).val();
                        $.ajax({
                            url: 'includes/data_fetch.php?action=fetchDepot',
                            method: 'POST',
                            data: {
                                division: Division
                            },
                            success: function(data) {
                                // Update the depot dropdown with fetched data
                                $('#depot').html(data);

                                // Hide the option with text 'DIVISION'
                                $('#depot option').each(function() {
                                    if ($(this).text().trim() === 'DIVISION') {
                                        $(this).hide();
                                    }
                                });
                            }
                        });
                    });
                }
                $(document).ready(function() {
                    fetchBusCategory();
                });
            </script>
            <script>
                $(document).ready(function() {
                    $('#collapseTwo').on('show.bs.collapse', function() {
                        fetchOffRoadData();
                    });

                    function fetchOffRoadData() {
                        $("#loadingMessage").show();
                        $("#offRoadDataTable").html(""); // Clear previous content

                        $.ajax({
                            url: "database/sch_live_fetch_offroad_data.php", // PHP script to fetch depot-wise data
                            type: "GET",
                            dataType: "html",
                            success: function(response) {
                                $("#loadingMessage").hide();
                                $("#offRoadDataTable").html(response);
                            },
                            error: function() {
                                $("#loadingMessage").hide();
                                $("#offRoadDataTable").html("<p>Error loading data.</p>");
                            }
                        });
                    }
                });

                function fetchoffroadDetails(id, name, type) {
                    // Show modal with loading message
                    $("#offroadModal .modal-body").html("<p class='text-center'>Loading...</p>");
                    $("#offroadModal").modal("show");

                    $.ajax({
                        url: "database/sch_live_fetch_depot_offroad_data.php", // PHP script to fetch data
                        type: "POST",
                        data: {
                            id: id,
                            name: name,
                            type: type
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status === "success") {
                                $("#offroadModal .modal-body").html(response.html);
                            } else {
                                $("#offroadModal .modal-body").html("<p class='text-center text-danger'>No off-road data found.</p>");
                            }
                        },
                        error: function() {
                            $("#offroadModal .modal-body").html("<p class='text-center text-danger'>Error fetching data.</p>");
                        }
                    });
                }

                $(document).ready(function() {
                    $('#collapseThree').on('shown.bs.collapse', function() {
                        let $contentDiv = $('#kmpl-content');

                        // Check if data is already loaded
                        if ($(this).attr('data-loaded') === "false") {
                            $.ajax({
                                url: 'database/sch_live_fetch_kmpl_data.php', // PHP script to fetch KMPL data
                                type: 'POST',
                                data: {
                                    date: 'yesterday'
                                }, // Send yesterday's date
                                dataType: 'html',
                                success: function(response) {
                                    $contentDiv.html(response); // Insert fetched data into div
                                    $('#collapseThree').attr('data-loaded', "true"); // Mark as loaded
                                },
                                error: function() {
                                    $contentDiv.html('<p>Error fetching data.</p>');
                                }
                            });
                        }
                    });
                });
            </script>

            <footer id="sticky-footer" class="flex-shrink-0 py-4">
                <div class="text-center">
                    <span>© Copyright 2024 KKRTC | All Rights Reserved</span>
                </div>
            </footer>
        </div>
    </div>
    <!-- End of Footer -->
    <!-- Scroll to Top Button-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js" crossorigin="anonymous"></script>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>





    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script type="text/javascript" charset="utf8"
        src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>






    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>

</html>