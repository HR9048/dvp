<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech') {
    // Allow access
// Check if session variables are set
    if (!isset($_SESSION['DIVISION_ID']) || !isset($_SESSION['DEPOT_ID'])) {
        // Redirect or handle accordingly if session variables are not set
        echo "<script>alert('Session variable not found. Please login again.'); window.location.href = 'logout.php';</script>";
        exit;
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_attended'])) {
        // Retrieve form data
        $id = $_POST['id'];

        // Update the status to 0 and set status_updated_datetime to current datetime
        $query = "UPDATE depot_camera_defect SET status = 0, status_updated_datetime = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Reload the page to reflect the changes
        echo "<script>alert('Vehicle attendance data updated successfully'); window.location.href = 'depot_camera_defect.php';</script>";
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve form data
        $busNumber = $_POST['bus_number'];
        $doc = $_POST['doc'];
        $make = $_POST['make'];
        $emissionNorms = $_POST['emission_norms'];
        $defectType = $_POST['busType'];
        $remark = $_POST['remark'];

        // Check for empty fields
        if (empty($busNumber) || empty($doc) || empty($make) || empty($emissionNorms) || empty($defectType) || empty($remark)) {
            echo "<script>alert('Form fields are missing'); window.history.back();</script>";
            exit;
        }

        // Check if the bus number already exists
        $checkQuery = "SELECT COUNT(*) AS count FROM depot_camera_defect WHERE bus_number = ? and status=1";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param("s", $busNumber);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            echo "<script>alert('Bus number defect already submitted. Please update other bus numbers.'); window.location.href = 'depot_camera_defect.php';</script>";
            exit;
        }

        // Get current date and time
        $submittedDateTime = date('Y-m-d H:i:s');

        // Get username, division_id, and depot_id from session
        $username = $_SESSION['USERNAME'];
        $division = $_SESSION['DIVISION_ID'];
        $depot = $_SESSION['DEPOT_ID'];
        $status = 1;
        // Insert data into depot_camera_defect table
        $query = "INSERT INTO depot_camera_defect (division_id, depot_id, bus_number, doc, make, emission_norms, defect_type_id, defect_notice_date, status, submitted_date_time, submitted_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssssssssss", $division, $depot, $busNumber, $doc, $make, $emissionNorms, $defectType, $remark, $status, $submittedDateTime, $username);

        // Execute the query
        if ($stmt->execute()) {
            // Success: Reload the page
            echo "<script>alert('Defect Added successfully'); window.location.href = window.location.href;</script>";
            exit;
        } else {
            // Error: Display error message or handle accordingly
            echo "Error: " . $db->error;
        }

        // Close statement
        $stmt->close();
    }
    ?>
    <center>
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#defectModal">
            Add Defect
        </button>
    </center>

    <div class="container1">
        <h2 style="text-align: center;">Defective Buses Report</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Retrieve division and depot IDs from session
                $divisionId = $_SESSION['DIVISION_ID'];
                $depotId = $_SESSION['DEPOT_ID'];

                // Array to store rows
                $data = [];

                // Query to fetch the number of distinct Leyland BS-6 buses from the bus_registration table
                $queryLeylandBuses = "SELECT COUNT(DISTINCT bus_number) AS num_leyland_buses
                  FROM bus_registration
                  WHERE division_name = $divisionId AND depot_name = $depotId
                  AND make = 'Leyland' AND emission_norms = 'BS-6'";

                $resultLeylandBuses = mysqli_query($db, $queryLeylandBuses) or die(mysqli_error($db));
                $rowLeylandBuses = mysqli_fetch_assoc($resultLeylandBuses);
                $numLeylandBuses = $rowLeylandBuses['num_leyland_buses'];

                // Store Leyland BS-6 buses data in the array
                $data['Leyland BS-6 Buses'] = $numLeylandBuses;

                // Query to fetch data of distinct defective buses for the specific division and depot
                $queryDefectiveBuses = "SELECT dt.defect_name, COUNT(DISTINCT dcd.bus_number) AS num_defects
                    FROM depot_camera_defect dcd
                    JOIN depot_camera_defect_type dt ON dcd.defect_type_id = dt.id
                    WHERE dcd.division_id = $divisionId AND dcd.depot_id = $depotId and status=1
                    GROUP BY dt.defect_name";

                $resultDefectiveBuses = mysqli_query($db, $queryDefectiveBuses) or die(mysqli_error($db));

                // Store defective buses data in the array
                while ($row = mysqli_fetch_assoc($resultDefectiveBuses)) {
                    $data[$row['defect_name']] = $row['num_defects'];
                }

                // Output data row by row
                foreach ($data as $category => $count) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($category) . "</td>";
                    echo "<td>" . htmlspecialchars($count) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>




        <br>
        <style>
            .hide {
                display: none;
            }
        </style>

        <h2 style="text-align: center;">Defect Records</h2>
        <table>
            <thead>
                <tr>
                    <td class="hide">ID</td>
                    <th>Depot</th>
                    <th>Bus Number</th>
                    <th>DOC</th>
                    <th>Make</th>
                    <th>Emission Norms</th>
                    <th>Defect Type</th>
                    <th>Defect notice Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch data from database
                $query = "SELECT dcd.id, dcd.bus_number, dcd.doc, dcd.make, dcd.emission_norms, dt.defect_name, dcd.defect_notice_date, l.division, l.depot
        FROM depot_camera_defect dcd
        JOIN depot_camera_defect_type dt ON dcd.defect_type_id = dt.id
        JOIN location l ON dcd.division_id = l.division_id AND dcd.depot_id = l.depot_id 
        WHERE dcd.division_id = " . $_SESSION['DIVISION_ID'] . " AND dcd.depot_id = " . $_SESSION['DEPOT_ID'] . " and dcd.status=1
        ORDER BY dcd.submitted_date_time DESC";
                $result = mysqli_query($db, $query) or die(mysqli_error($db));

                while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td class="hide"><?php echo $row['id']; ?></td>
                        <td><?php echo $row['depot']; ?></td>
                        <td><?php echo $row['bus_number']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['doc'])); ?></td>
                        <td><?php echo $row['make']; ?></td>
                        <td><?php echo $row['emission_norms']; ?></td>
                        <td><?php echo $row['defect_name']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['defect_notice_date'])); ?></td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-success" name="mark_attended">attended</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>
    <div class="text-center mt-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="defectModal" tabindex="-1" aria-labelledby="defectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="defectModalLabel">Add Defect</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Defect Form -->
                    <form id="busForm" method="post" action="">
                        <nav class="navbar navbar-light bg-light">
                            <div id="searchBar" style="display: flex; align-items: center;">
                                <input type="text" id="busSearch" type="search" class="form-control mr-sm-2"
                                    placeholder="Search Bus Number">
                                <button type="button" class="btn btn-outline-success my-2 my-sm-0"
                                    onclick="searchBus()">Search</button>
                            </div>
                        </nav>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="bus_number" class="form-label">Bus Number:</label>
                                    <input type="text" id="bus_number" name="bus_number" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="depot" class="form-label">Depot:</label>
                                    <input type="text" id="depot" name="depot" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="make" class="form-label">Make:</label>
                                    <input type="text" id="make" name="make" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="emission_norms" class="form-label">Emission Norms:</label>
                                    <input type="text" id="emission_norms" name="emission_norms" class="form-control"
                                        readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="doc" class="form-label">Date of Commissioning (DOC):</label>
                                    <input type="date" id="doc" name="doc" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="busType" class="form-label">Bus Defect Type:</label>
                                    <select id="busType" name="busType" class="form-select" required>
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="remark" class="form-label">Defect Noticed date:</label>
                            <input type="date" id="remark" name="remark" class="form-control" required>
                            <script>
                                // JavaScript to set the max attribute of the date input to today's date
                                document.addEventListener('DOMContentLoaded', (event) => {
                                    var today = new Date().toISOString().split('T')[0];
                                    document.getElementById('remark').setAttribute('max', today);
                                });

                                // JavaScript to check if selected date is greater than today's date
                                document.getElementById('remark').addEventListener('change', function () {
                                    var selectedDate = this.value;
                                    var today = new Date().toISOString().split('T')[0];

                                    if (selectedDate > today) {
                                        this.value = today; // Set input value to today's date
                                    }
                                });
                            </script>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
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
                        $('#depot').val(response.depot);
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
                    $('#busType').empty();

                    // Add default "Select" option
                    $('#busType').append('<option value="">Select</option>');

                    // Add fetched options
                    $.each(service, function (index, value) {
                        $('#busType').append('<option value="' + value.id + '">' + value.defect_name + '</option>');
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
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>