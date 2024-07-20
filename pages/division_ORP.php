<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
$query = 'SELECT ID, t.TYPE
            FROM users u
            JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DEPOT') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Depot Page");
            window.location = "../includes/depot_verify.php";
        </script>
    <?php } elseif ($Aa == 'HEAD-OFFICE') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Head Office Page");
            window.location = "index.php";
        </script>
    <?php } elseif ($Aa == 'RWY') {
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to RWY Page");
            window.location = "rwy.php";
        </script>
    <?php }
}

// Assuming you have stored the division in the session
$session_division = $_SESSION['DIVISION_ID'];

// Fetching latest details of vehicles that are off-road and belong to the user's division
$sql = "SELECT ord.*, 
               loc.division AS depot_division, 
               loc.division_id AS divisionID, 
               loc.depot AS depot_name,
               loc.depot_id AS depotID,
               DATEDIFF(CURDATE(), ord.off_road_date) AS days_off_road 
        FROM off_road_data ord
        INNER JOIN location loc ON ord.depot = loc.depot_id
        WHERE ord.status = 'off_road' 
        AND ord.division = '$session_division' 
        ORDER BY loc.depot_id, ord.off_road_location ASC";
$result = mysqli_query($db, $sql) or die(mysqli_error($db));

// Initialize variables for rowspan logic
$bus_numbers = [];
$bus_number_rowspans_count = [];

// Group data by bus number
while ($row = mysqli_fetch_assoc($result)) {
    $bus_number = $row['bus_number'];
    if (!in_array($bus_number, $bus_numbers)) {
        $bus_numbers[] = $bus_number;
    }
    if (!isset($bus_number_rowspans_count[$bus_number])) {
        $bus_number_rowspans_count[$bus_number] = 0;
    }
    $bus_number_rowspans_count[$bus_number]++;
}
mysqli_data_seek($result, 0); // Reset the result pointer to the beginning
?>
<style>
        .hidden {
            display: none;
        }
    </style>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table id="myTable">
                    <thead>
                        <tr>
                            <th>Sl. No</th>
                            <th>Bus Number</th>
                            <th>Division</th>
                            <th>Depot</th>
                            <th>Make</th>
                            <th>Emission Norms</th>
                            <th>Off Road From Date</th>
                            <th>Number of days off-road</th>
                            <th>Off Road Location</th>
                            <th>Parts Required</th>
                            <th>Remarks</th>
                            <th>DWS Remarks</th>
                            <th>Action</th>
                            <th>Send to RWY</th>
                            <!-- Hidden columns -->
                            <th class="hidden">Division ID</th>
                            <th class="hidden">Depot ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Initialize serial number counter
                        $serial_number = 1;

                        // Loop through each bus number
                        foreach ($bus_numbers as $bus_number) {
                            // Fetch all rows for the current bus number
                            $rows = [];
                            mysqli_data_seek($result, 0); // Reset the result pointer
                            while ($row = mysqli_fetch_assoc($result)) {
                                if ($row['bus_number'] == $bus_number) {
                                    $rows[] = $row;
                                }
                            }

                            // Output data for each row
                            foreach ($rows as $key => $row) {
                                echo "<tr>";

                                // Output serial number only for the first row of the current bus number
                                if ($key === 0) {
                                    echo "<td rowspan='" . count($rows) . "'>$serial_number</td>";
                                    echo "<td rowspan='" . count($rows) . "'>" . $row['bus_number'] . "</td>";
                                    echo "<td rowspan='" . count($rows) . "'>" . $row['depot_division'] . "</td>";
                                    echo "<td rowspan='" . count($rows) . "'>" . $row['depot_name'] . "</td>";
                                    echo "<td rowspan='" . count($rows) . "'>" . $row['make'] . "</td>";
                                    echo "<td rowspan='" . count($rows) . "'>" . $row['emission_norms'] . "</td>";
                                }

                                // Extract data from the row
                                $offRoadFromDate = $row['off_road_date'];
                                $partsRequired = $row['parts_required'];
                                $remarks = $row['remarks'];
                                $dwsremarks = $row['dws_remark'];
                                $location = $row['off_road_location'];
                                $daysOffRoad = $row['no_of_days_offroad'];
                                if ($daysOffRoad === null) {
                                    $offRoadDate = new DateTime($offRoadFromDate);
                                    $today = new DateTime();
                                    $daysOffRoad = $today->diff($offRoadDate)->days;
                                }

                                // Output the data in table rows
                                echo "<td>" . date('d/m/y', strtotime($offRoadFromDate)) . "</td>";
                                echo "<td>$daysOffRoad</td>";
                                echo "<td>$location</td>";
                                echo "<td>$partsRequired</td>";
                                echo "<td>$remarks</td>";
                                if (date('Y-m-d', strtotime($row["dws_last_update"])) !== date('Y-m-d')) {
                                    echo "<td><textarea class='form-control dwsRemarkTextArea' placeholder='Enter DWS Remark' rows='3' id='dwsRemark_" . $row["id"] . "'>" . $row["dws_remark"] . "</textarea></td>";
                                    echo "<td><button class='updateBtn btn btn-primary btn-sm' data-entry-id='" . $row["id"] . "'>Update</button></td>";
                                } else {
                                    echo "<td>" . $row["dws_remark"] . "</td>";
                                    echo "<td><button class='btn btn-secondary btn-sm' disabled>Already Updated</button></td>";
                                }
                                if ($key === 0) {
                                    // Assuming $rows is the array of rows you are iterating through
                        
                                    // If there is only one row and its work status is "Proposal for scrap", show the button
                                    if (count($rows) === 1 && $rows[0]['off_road_location'] === "DWS") {
                                        $max_id = $rows[0]['id']; // Maximum ID
                                        $max_work_status = "DWS"; // Work status of the maximum ID
                                    } else {
                                        // Get the maximum ID for this bus number
                                        $max_id = $rows[0]['id'];
                                        $max_work_status = ""; // Initialize max work status variable
                                        foreach ($rows as $r) {
                                            if ($r['id'] > $max_id) {
                                                $max_id = $r['id'];
                                                $max_work_status = $r['off_road_location']; // Get the work status of the maximum ID
                                            }
                                        }
                                    }
                                    // Output Send to RWY button only if the maximum ID's off-road location is "DWS"
                                    if ($max_work_status === "DWS") {
                                        echo "<td rowspan='" . count($rows) . "'>";
                                        echo "<div style='width: 100%;'>";
                                        echo "<input type='hidden' name='bus_number' value='" . $row['bus_number'] . "'>";
                                        echo "<button data-bus-number='" . $row['bus_number'] . "' data-max-id='" . $max_id . "' class=\"btn btn-success receive-btn\" style='width: 100%; height: 40px;'>Sent_to_RWY</button>";



                                        echo "</div>"; // End div with width: 100%
                                        echo "</td>";
                                    } else {
                                        echo "<td rowspan='" . count($rows) . "'>";
                                        echo "<div style='width: 100%;'>";
                                    }
                                    echo "</div>"; // End div with width: 100%
                                    echo "</td>";
                                }
                                // Hidden columns
                                echo "<td class='hidden'>" . $row['divisionID'] . "</td>";
                                echo "<td class='hidden'>" . $row['depotID'] . "</td>";

                                echo "</tr>";
                            }

                            // Increment the serial number
                            $serial_number++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Receive Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1" role="dialog" aria-labelledby="receiveModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="width: 60%; height: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Vehicle Status</h5>
                <!-- <button type="button" id="closeModalButton" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button> -->
            </div>
            <div class="modal-body">
                <form id="receiveForm">
                    <input type="hidden" name="id" id="idInput" value=""> <!-- Change type to hidden -->
                    <input type="text" name="busNumberInput" id="busNumberInput" readonly>
                    <input type="hidden" name="divisionID" id="divisionID" readonly>
                    <input type="hidden" name="depotID" id="depotID" readonly>
                    <input type="text" name="divisionInput" id="divisionInput" readonly>
                    <input type="text" name="depotInput" id="depotInput" readonly>
                    <input type="text" name="makeInput" id="makeInput" readonly>
                    <input type="text" name="emissionNormsInput" id="emissionNormsInput" readonly>
                    <input type="text" name="offRoadFromDateInput" id="offRoadFromDateInput" readonly>
                    <input type="hidden" name="username" id="usernameInput"
                        value="<?php echo $_SESSION['USERNAME']; ?>">
                    <div class="form-group">
                        <label for="workReason">Work Reason:</label>
                        <select class="form-control" id="workReason" name="workReason" Required>
                            <option value="">Select</option>
                            <option value="HBR">HBR</option>
                            <option value="Accident Repair">Accident Repair</option>
                            <option value="Refurbishing">Refurbishing</option>
                            <option value="Body Change">Body Change</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="workReason">Remarks:</label>
                        <textarea class="form-control" name="remarks" id="remarks" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="closeModalButton"
                    data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateButton">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function () {
        // Function to hide the modal
        function hideModal() {
            $('#receiveModal').modal('hide');
        }

        // When the modal is hidden, remove the modal backdrop manually
        $('#receiveModal').on('hidden.bs.modal', function () {
            $('.modal-backdrop').remove();
        });

        // Add event listener to the Close button
        $('#closeModalButton').on('click', function () {
            hideModal(); // Call the function to hide the modal
        });
    });
    $(document).ready(function () {
        $('.receive-btn').on('click', function (event) {
            // Prevent default behavior
            event.preventDefault();

            // Get the data attributes
            var busNumber = $(this).data('bus-number');
            var maxId = $(this).data('max-id');

            // Check if the modal is already opened
            if ($('#receiveModal').hasClass('show')) {
                return;
            }

            // Confirm action
            var confirmAction = confirm('Are you sure you want to send bus ' + busNumber + ' RWY?');

            // If confirmed, open the modal
            if (confirmAction) {
                // Update the form fields with the row data
                var rowData = $(this).closest('tr').find('td');
                $('#busNumberInput').val($(rowData[1]).text());
                $('#divisionInput').val($(rowData[2]).text());
                $('#depotInput').val($(rowData[3]).text());
                $('#makeInput').val($(rowData[4]).text());
                $('#emissionNormsInput').val($(rowData[5]).text());
                $('#offRoadFromDateInput').val($(rowData[6]).text());
                $('#divisionID').val($(rowData[14]).text());
                $('#depotID').val($(rowData[15]).text());

                // Show the modal
                $('#receiveModal').modal('show');
                $('#idInput').val(maxId);
            }
        });
    });


    $(document).ready(function () {
        // Add event listener to "Update Status" button
        $('#updateButton').on('click', function () {
            // Get the form data
            var formData = $('#receiveForm').serialize();

            // Send the data via AJAX
            $.ajax({
                type: 'POST',
                url: 'division_insert_off_road_data.php', // Update the URL with your PHP script
                data: formData,
                success: function (response) {
                    // Handle the response
                    alert(response); // Alert the response for now, you can customize this
                    window.location.reload();
                }
            });
        });
    });

</script>
<script>
    // Get all update buttons
    var updateBtns = document.querySelectorAll(".updateBtn");

    // When the user clicks on the update button, insert the value into the database
    updateBtns.forEach(function (btn) {
        btn.addEventListener("click", function () {
            var entryId = this.getAttribute("data-entry-id");
            var inputField = document.querySelector("#dwsRemark_" + entryId);

            // Get the DWS Remark value from the input field
            var dwsRemarkValue = inputField.value.trim();

            // Perform AJAX request to update the DWS Remark in the database
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        // Parse JSON response
                        var response = JSON.parse(xhr.responseText);

                        // Display appropriate alert message based on response status
                        if (response.status === 'success') {
                            alert(response.message);
                            // Redirect to the same page
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }
                    } else {
                        // Display error message
                        alert('Error updating data. Please try again later.');
                    }
                }
            };
            xhr.open("POST", "update_entry.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("entryId=" + entryId + "&dwsRemark=" + encodeURIComponent(dwsRemarkValue));
        });
    });
</script>
<?php include '../includes/footer.php'; ?>