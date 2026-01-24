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
    <!-- Load Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom fonts for this template -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- custom script.js file -->

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
                            if (!$(target).is('.dropdown-toggle') && !$(target).parents().is(
                                    '.dropdown-menu')) {
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
                    <link rel="stylesheet"
                        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

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

                            h4 {
                                font-size: 10px;
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

                    <?php
                    date_default_timezone_set('Asia/Kolkata');
                    $current_date = date('Y-m-d');
                    $current_time = date('d-m-Y');
                    $current_time1 = date('H:i');
                    $selected_date_month = date('M', strtotime($current_date));
                    ?>




                    <!-- /.container-fluid -->

                    <div class="accordion" id="accordionExample">
                        <!-- Accordion Item 1 -->
                        <!--<div class="card">
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
                    </div>-->
                        <!-- Accordion Item 2 -->
                        <div class="card">
                            <div class="card-header" id="headingFive">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#collapseFive" aria-expanded="false"
                                        aria-controls="collapseFive">
                                        Live Schedule Details <i class="fas fa-chevron-down float-right"></i>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseFive" class="collapse" aria-labelledby="headingFive"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center my-3">
                                        <h4 class="mb-0" id="departure-heading">
                                            DEPARTURES AS ON <span id="current-time"><?= $current_time ?></span>
                                            <?php if ($current_date === date('Y-m-d')) : ?>
                                                @ <span id="current-time1"><?= $current_time1 ?></span>
                                            <?php endif; ?>
                                        </h4>
                                        <input style="font-size: 12px;" type="date" id="date-selector"
                                            class="form-control w-auto" max="<?= date('Y-m-d'); ?>"
                                            value="<?= date('Y-m-d'); ?>">
                                    </div>

                                    <div class="table-container">
                                        <table class="table table-bordered">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Division</th>
                                                    <th style="width:17%">Depot</th>
                                                    <th>Sch Dep</th>
                                                    <th>Act Dep</th>
                                                    <th>Pending</th>
                                                    <th>Late Dep</th>
                                                    <th>Reg %</th>
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
                                    <div class="modal fade" id="difference-modal" tabindex="-1"
                                        aria-labelledby="difference-modal-title" aria-hidden="true">
                                        <div class="modal-dialog custom-modal">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="difference-modal-title">Difference
                                                        Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped" id="difference-modal-body">
                                                            
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Accordion Item 2 -->
                        <div class="card">
                            <div class="card-header" id="headingTwo">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false"
                                        aria-controls="collapseTwo">
                                        Off-Road <i class="fas fa-chevron-down float-right"></i>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <div id="loadingMessage" style="display: none;">Loading Off-Road data, please
                                        wait...</div>
                                    <div id="offRoadDataTable" class="table-responsive"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Accordion Item 3 -->
                        <div class="card">
                            <div class="card-header" id="headingSeven">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#collapseSeven" aria-expanded="false"
                                        aria-controls="collapseSeven">
                                        Break Down Details <i class="fas fa-chevron-down float-right"></i>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseSeven" class="collapse" aria-labelledby="headingSeven"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center my-3">
                                        <h4 class="mb-0" id="bd-heading">
                                            Break Down As on <span id="current-datetime"><?= $current_time ?></span>

                                        </h4>
                                        <input style="font-size: 12px;" type="date" id="bd-date-selector"
                                            class="form-control w-auto" max="<?= date('Y-m-d', strtotime('-1 day')); ?>"
                                            value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>">
                                    </div>

                                    <div class="table-container">
                                        <table class="table table-bordered">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Division</th>
                                                    <th style="width:17%">Depot</th>
                                                    <th>Day BD</th>
                                                    <th id="bd-date-month"><?= $selected_date_month ?> BD</th>
                                                    <th>Cum BD</th>
                                                </tr>
                                            </thead>

                                            <tbody id="bd-report-body">
                                                <!-- Data will be loaded here -->
                                            </tbody>
                                            <tfoot id="bd-overall-total-row">
                                                <!-- Overall total will be added here -->
                                            </tfoot>
                                        </table>
                                        <div id="loadingMessagebd" style="display: none;">Loading Off-Road data, please
                                            wait...</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Item 4 -->
                        <div class="card">
                            <div class="card-header" id="headingThree">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#collapseThree" aria-expanded="false"
                                        aria-controls="collapseThree">
                                        KMPL <i class="fas fa-chevron-down float-right"></i>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                                data-parent="#accordionExample" data-loaded="false">
                                <div class="card-body">
                                    <div id="loadingMessage1" style="display: none;">Loading KMPL data, please wait...
                                    </div>
                                    <div id="kmpl-content" class="table-responsive"></div>
                                </div>
                            </div>
                        </div>


                        <div class="card">
                            <div class="card-header" id="headingFour">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#collapseFour" aria-expanded="false"
                                        aria-controls="collapseFour">
                                        Commercial Stall Data <i class="fas fa-chevron-down float-right"></i>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseFour" class="collapse" aria-labelledby="headingFour"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <div id="loadingMessagecommerial" style="display: none;">Loading Commertial data,
                                        please wait...</div>
                                    <div id="commertialTable" class="table-responsive"></div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingEight">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#collapseEight" aria-expanded="false"
                                        aria-controls="collapseEight">
                                        Program Details <i class="fas fa-chevron-down float-right"></i>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseEight" class="collapse" aria-labelledby="headingEight"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center my-3">
                                        <h4 class="mb-0" id="bd-heading">
                                            Program Report
                                        </h4>
                                        <input style="font-size: 12px;" type="date" id="program-date-selector"
                                            class="form-control w-auto" max="<?= date('Y-m-d'); ?>"
                                            value="<?php echo date('Y-m-d'); ?>">
                                    </div>

                                    <div id="loadingMessageprogram" style="display: none;">Loading Program data,
                                        please wait...</div>
                                    <div id="ProgramTable" class="table-responsive"></div>
                                </div>
                            </div>
                        </div>


                        <!--<div class="card">
                            <div class="card-header" id="headingSix">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#collapseSix" aria-expanded="false"
                                        aria-controls="collapseSix">
                                        Overall Dashboard <i class="fas fa-chevron-down float-right"></i>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseSix" class="collapse" aria-labelledby="headingSix"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <form id="overallDashboardForm">
                                        <label for="startDate">Date</label>
                                        <input type="date" id="startDate" name="startDate">
                                        <label for="division_id">Division</label>
                                        <select id="division_id" name="division_id">
                                            <option value="All">All</option>
                                        </select>

                                        <label for="depot_id">Depot</label>
                                        <select id="depot_id" name="depot_id">
                                            <option value="All">All</option>
                                        </select>
                                        <button type="button" class="btn btn-primary">Fetch Data</button>

                                    </form>
                                    <label for="clearFilters">Clear Filters</label>
                                    <button type="button" class="btn btn-secondary" id="clearFilters">Clear</button>
                                    <div id="overallDashboard" class="table-responsive"></div>
                                </div>
                            </div>
                        </div>-->
                    </div>
                </div>
                <script>

                </script>

                <!-- Include Bootstrap JavaScript -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"
                    crossorigin="anonymous"></script>
                <!-- Modal for PDF -->
                <div class="modal fade" id="operationalStatisticsMod" tabindex="-1" role="dialog"
                    aria-labelledby="modalTitle" aria-hidden="true">
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
                                    PDF preview may not work in some mobile browsers. <a id="openPdfDirectly" href="#"
                                        target="_blank">Click here to view</a>.
                                </p>
                            </div>
                            <a id="downloadBtn" href="#" class="btn btn-primary" download>Download PDF</a>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Vehicle KMPL Details Modal -->
                <div class="modal fade" id="kmplDetailsModal" tabindex="-1" role="dialog"
                    aria-labelledby="kmplDetailsLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="kmplDetailsLabel">Vehicle KMPL Details</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" id="kmplDetailsBody">
                                <!-- Data will be inserted here dynamically -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bootstrap Modal for Late Departures -->
                <div class="modal fade" id="late-modal" tabindex="-1" aria-labelledby="late-modal-title"
                    aria-hidden="true">
                    <div class="modal-dialog custom-modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="late-modal-title">Late Departures Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="late-modal-body">
                                        
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
                    <div class="modal-dialog modal-xl" role="document">
                        <!-- Added modal-xl for large width -->
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
                <div id="BDModal" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-xl" role="document">
                        <!-- Added modal-xl for large width -->
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><span id="modalBDDepotName"></span></h5> <!-- Added span -->
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
                <div class="modal fade" id="schedule-details-modal" tabindex="-1"
                    aria-labelledby="schedule-details-modal-title" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-fullscreen-md-down">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="schedule-details-modal-title">
                                    Sch No: <span id="modal-sch-no"></span> |
                                    Description: <span id="modal-description"></span> |
                                    Service Class: <span id="modal-service-class"></span> |
                                    Sch Dep Time: <span id="modal-sch-dep-time"></span>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
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
                                                <th>Reason for Late Dep</th>
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
                    <div class="modal-dialog modal-xl" role="document">
                        <!-- Full-width modal -->
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

                </script>


            </div>
            <footer id="sticky-footer" class="flex-shrink-0 py-4">
                <div class="text-center">
                    <span>© Copyright 2024 KKRTC | All Rights Reserved</span>
                </div>
            </footer>
        </div>
    </div>
    <!-- End of Footer -->
    <!-- Scroll to Top Button-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js" crossorigin="anonymous">
    </script>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js">
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script src="script.js"></script>
</body>

</html>