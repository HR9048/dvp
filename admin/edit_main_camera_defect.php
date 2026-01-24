<?php
ob_start(); // Start output buffering
// Your PHP code
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    // Function to delete a record
    function deleteRecord($id, $db)
    {
        $sql = "DELETE FROM depot_camera_defect WHERE id = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Handle delete request
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
        $id = $_POST['delete'];
        deleteRecord($id, $db);
        // Reload the page after deletion
        header("Location: edit_main_camera_defect.php");
        exit();
    }


    // Handle update request
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $division_id = $_POST['division_id'];
        $depot_id = $_POST['depot_id'];
        $bus_number = $_POST['bus_number'];
        $doc = $_POST['doc'];
        $make = $_POST['make'];
        $emission_norms = $_POST['emission_norms'];
        $defect_type_id = $_POST['defect_type_id'];
        $defect_notice_date = $_POST['defect_notice_date'];
        $submitted_date_time = $_POST['submitted_date_time'];
        $submitted_by = $_POST['submitted_by'];
        $status = $_POST['status'];
        $status_updated_datetime = $_POST['status_updated_datetime'];

        $sql = "UPDATE depot_camera_defect SET  bus_number=?, doc=?, make=?, emission_norms=?, defect_type_id=?, defect_notice_date=?, submitted_date_time=?, submitted_by=?,status=?, status_updated_datetime=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssssssssssi", $bus_number, $doc, $make, $emission_norms, $defect_type_id, $defect_notice_date, $submitted_date_time, $submitted_by, $status, $status_updated_datetime, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: edit_main_camera_defect.php");
        exit(); // Make sure to exit after redirecting to prevent further execution

    }

    // Fetch data
    $sql = "SELECT dc.*, l.kmpl_division AS division_name, l.depot AS depot_name 
        FROM depot_camera_defect dc 
        JOIN location l ON dc.depot_id = l.depot_id AND dc.division_id = l.division_id";
    $result = $db->query($sql);

    // Fetch all rows
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    ?>

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>


    <h2>Manage Records</h2>
    <table id="dataTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Division</th>
                <th>Depot</th>
                <th>Bus Number</th>
                <th>DOC</th>
                <th>Make</th>
                <th>Emission Norms</th>
                <th>Defect Type ID</th>
                <th>defect DAte</th>
                <th>Submitted Date Time</th>
                <th>Submitted By</th>
                <th>status</th>
                <th>status Update By</th>
                <th>Action</th>

            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['division_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['depot_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['bus_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['doc']); ?></td>
                    <td><?php echo htmlspecialchars($row['make']); ?></td>
                    <td><?php echo htmlspecialchars($row['emission_norms']); ?></td>
                    <td><?php echo htmlspecialchars($row['defect_type_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['defect_notice_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['submitted_date_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['submitted_by']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['status_updated_datetime']); ?></td>
                    <td>
                        <form method="post">
                            <!-- Hidden input field to store the ID -->
                            <input type="hidden" name="delete" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        <button class="btn btn-primary btn-sm"
                            onclick="openUpdateModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Update</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Add/Update Defect Modal -->
    <div class="modal fade" id="defectModal" tabindex="-1" aria-labelledby="defectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="defectModalLabel">Add/Update Defect</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Defect Form -->
                    <form id="busForm" method="post" action="">
                        <input type="hidden" id="id" name="id">


                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="bus_number" class="form-label">Bus Number:</label>
                                    <input type="text" id="bus_number" name="bus_number" class="form-control" required>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="make" class="form-label">Make:</label>
                                    <input type="text" id="make" name="make" class="form-control" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="emission_norms" class="form-label">Emission Norms:</label>
                                    <input type="text" id="emission_norms" name="emission_norms" class="form-control"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="doc" class="form-label">Date of Commissioning (DOC):</label>
                                    <input type="date" id="doc" name="doc" class="form-control" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="defect_type_id" class="form-label">Bus Defect Type:</label>
                                    <select id="defect_type_id" name="defect_type_id" class="form-select" required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="defect_notice_date" class="form-label">defect notice date:</label>
                            <input type="date" id="defect_notice_date" name="defect_notice_date" class="form-control"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="submitted_date_time" class="form-label">Submitted Date Time:</label>
                            <input type="datetime-local" id="submitted_date_time" name="submitted_date_time"
                                class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="submitted_by" class="form-label">Submitted By:</label>
                            <input type="text" id="submitted_by" name="submitted_by" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status:</label>
                            <input type="text" id="status" name="status" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="status_updated_datetime" class="form-label">Status updated date:</label>
                            <input type="datetime-local" id="status_updated_datetime" name="status_updated_datetime"
                                class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary" name="update">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to open the modal and populate form fields for updating
        function openUpdateModal(data) {
            $('#id').val(data.id);
            $('#division_id').val(data.division_id);
            $('#depot_id').val(data.depot_id);
            $('#bus_number').val(data.bus_number);
            $('#depot').val(data.depot);
            $('#doc').val(data.doc);
            $('#make').val(data.make);
            $('#emission_norms').val(data.emission_norms);
            $('#defect_type_id').val(data.defect_type_id);
            $('#defect_notice_date').val(data.defect_notice_date);
            $('#submitted_date_time').val(data.submitted_date_time);
            $('#submitted_by').val(data.submitted_by);
            $('#status').val(data.status);
            $('#status_updated_datetime').val(data.status_updated_datetime);
            $('#defectModal').modal('show');
        }

        // Function to search for bus
        function searchBus() {
            var busNumber = $('#busSearch').val();
            // AJAX request to fetch data
            $.ajax({
                url: 'dvp_bus_search1.php',
                type: 'POST',
                data: { busNumber: busNumber },
                dataType: 'json', // Specify the expected data type as JSON
                success: function (response) {
                    // Check if the make is Leyland and the emission norms are BS-6
                    if (response.make !== 'Leyland') {
                        alert('The bus does not have a make of Leyland.');
                        resetForm();
                    } else if (response.emission_norms !== 'BS-6') {
                        alert('The emission norms of the bus are not BS-6.');
                        resetForm();
                    } else {
                        // Populate form fields with fetched data
                        $('#bus_number').val(response.bus_number);
                        $('#depot').val(response.KMPL_depot);
                        $('#doc').val(response.doc);
                        $('#make').val(response.make);
                        $('#emission_norms').val(response.emission_norms);
                    }
                },
                error: function (xhr, status, error) {
                    // Display error message
                    if (xhr.status === 403) {
                        alert(xhr.responseJSON.error);
                    } else {
                        alert('Error: Bus not Registered in KKRTC.');
                        $('#bus_number').val('');
                        $('#depot').val('');
                        $('#doc').val('');
                        $('#make').val('');
                        $('#emission_norms').val('');
                    }
                }
            });
        }
        $('#busSearch').on('keyup', function (event) {
            // Check if Enter key is pressed (keyCode 13)
            if (event.keyCode === 13) {
                // Call searchBus function when Enter key is pressed
                searchBus(); // Pass the index parameter to searchBus
            }
        });

        // Also, bind the searchBus function to the change event of the bus number input field
        $('#busSearch').on('change', function () {
            // Call searchBus function when bus number input field value changes
            searchBus(); // Pass the index parameter to searchBus
        });

        // Function to reset form fields to null
        function resetForm() {
            $('#bus_number').val('');
            $('#depot').val('');
            $('#doc').val('');
            $('#make').val('');
            $('#emission_norms').val('');
        }

        // Function to populate defect types
        function cameradefecttype() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'cameradefecttype' },
                success: function (response) {
                    var service = JSON.parse(response);

                    // Clear existing options
                    $('#defect_type_id').empty();

                    // Add default "Select" option
                    $('#defect_type_id').append('<option value="">Select</option>');

                    // Add fetched options
                    $.each(service, function (index, value) {
                        $('#defect_type_id').append('<option value="' + value.id + '">' + value.defect_name + '</option>');
                    });
                }
            });
        }
        cameradefecttype();
    </script>

    <script>
        $(document).ready(function () {
            // Remove month and year parameters from the URL when the page loads
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    </script>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
ob_end_flush(); // Flush (send) the output buffer and turn off output buffering
?>