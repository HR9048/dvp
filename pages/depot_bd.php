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

    ?>

    <div class="container-fluid custom-container">
    <div class="text-center">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBusModal">Add BD</button>
        </div>
        <h2>Break Down Data </h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Bus Number</th>
                    <th>Make</th>
                    <th>Emission Norms</th>
                    <th>Route Number</th>
                    <th>BD Location</th>
                    <th>Reason</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch data from the database ordered by date
                $sql = "SELECT * FROM bd_data where division='{$_SESSION['DIVISION_ID']}' AND depot='{$_SESSION['DEPOT_ID']}' ORDER BY date ASC";
                $result = $db->query($sql);

                if ($result->num_rows > 0) {
                    // Output data of each row
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . date('d/m/Y', strtotime($row["date"])) . "</td>";
                        echo "<td>" . $row["bus_number"] . "</td>";
                        echo "<td>" . $row["make"] . "</td>";
                        echo "<td>" . $row["model"] . "</td>";
                        echo "<td>" . $row["route_number"] . "</td>";
                        echo "<td>" . $row["bd_location"] . "</td>";
                        echo "<td>" . $row["reason"] . "</td>";
                        echo "<td>" . $row["remark"] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No data available</td></tr>";
                }

                ?>
            </tbody>
        </table>
        
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addBusModal" tabindex="-1" role="dialog" aria-labelledby="addBusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBusModalLabel">Add Bus Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form to add bus data -->
                    <form id="addBusForm"> <!-- Added ID to form -->
                        <nav class="navbar navbar-light bg-light">
                            <div id="searchBar" style="display: flex; align-items: center;">
                                <input type="text" id="busSearch" class="form-control mr-sm-2"
                                    placeholder="Search Bus Number">
                                <button type="button" class="btn btn-outline-success my-2 my-sm-0"
                                    onclick="searchBus()">Search</button>
                            </div>
                        </nav>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>BD Date</label>
                                    <input type="date" name="bd_date" class="form-control"
                                        max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Bus Number</label>
                                    <input type="text" id="bus_number" name="bus_number" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" id="make" name="make" class="form-control" style="display:none;"
                                        readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" id="emission_norms" name="emission_norms" style="display:none;"
                                        class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Route Number</label>
                                    <input type="text" name="route_number" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>BD Location</label>
                                    <input type="text" name="bd_location" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Reason</label>
                                    <select name="reason" class="form-control" required>
                                        <option value="">Select Reason</option>
                                        <?php
                                        $reasonSql = "SELECT * FROM breakdown";
                                        $reasonResult = $db->query($reasonSql);

                                        if ($reasonResult->num_rows > 0) {
                                            while ($row = $reasonResult->fetch_assoc()) {
                                                echo "<option value='" . $row['bd_location'] . "'>" . $row['bd_location'] . "</option>";
                                            }
                                        } else {
                                            echo "<option>No reasons found</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Remark</label>
                                    <textarea name="remark" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary mr-2">Save</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        </div>

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
    </script>
    <script>
        $(document).ready(function () {
            // Function to validate form fields
            function validateForm() {
                var isValid = true;
                var errorMessage = '';

                // Check if required fields are empty
                if (!$('input[name="bd_date"]').val()) {
                    isValid = false;
                    errorMessage += 'BD Date is required.\n';
                }
                if (!$('input[name="bus_number"]').val()) {
                    isValid = false;
                    errorMessage += 'Bus Number is required.\n';
                }
                if (!$('input[name="route_number"]').val()) {
                    isValid = false;
                    errorMessage += 'Route Number is required.\n';
                }
                if (!$('input[name="bd_location"]').val()) {
                    isValid = false;
                    errorMessage += 'BD Location is required.\n';
                }
                if (!$('select[name="reason"]').val()) {
                    isValid = false;
                    errorMessage += 'Reason is required.\n';
                }

                // If not valid, show alert with error message
                if (!isValid) {
                    alert(errorMessage);
                }

                return isValid;
            }

            // AJAX form submission
            $('#addBusForm').submit(function (event) {
                event.preventDefault(); // Prevent default form submission

                // Validate form before submitting
                if (validateForm()) {
                    // Serialize form data
                    var formData = $(this).serialize();

                    // AJAX request
                    $.ajax({
                        type: 'POST',
                        url: 'depot_bd_submit_data.php',
                        data: formData,
                        dataType: 'json', // Expected data type
                        success: function (response) {
                            if (response.status == 'success') {
                                alert(response.message); // Show success message
                                window.location.href = 'depot_bd.php'; // Redirect to depot_bd.php
                            } else {
                                alert(response.message); // Show error message
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr.responseText); // Log any errors to console
                            alert('An error occurred. Please try again.'); // Show generic error message
                        }
                    });
                }
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