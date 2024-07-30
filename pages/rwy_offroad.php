<?php
include '../includes/connection.php';
include '../includes/rwy_top.php';

$query = 'SELECT ID, t.TYPE FROM users u JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $userType = $row['TYPE'];

    if ($userType == 'DEPOT') {
        ?>
        <script type="text/javascript">
            alert("Restricted Page! You will be redirected to Depot Page");
            window.location = "../includes/depot_verify.php";
        </script>
        <?php
    } elseif ($userType == 'DIVISION') {
        ?>
        <script type="text/javascript">
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
        </script>
        <?php
    } elseif ($userType == 'HEAD-OFFICE') {
        ?>
        <script type="text/javascript">
            alert("Restricted Page! You will be redirected to Central office Page");
            window.location = "division.php";
        </script>
        <?php
    }
}

// Fetching latest details of vehicles that are off-road and belong to the user's division
$sql = "SELECT r.*, 
l.division AS division_name, 
l.depot AS depot_name,
IFNULL(r.no_of_days, DATEDIFF(CURDATE(), r.received_date)) AS days_off_road
FROM rwy_offroad r
JOIN location l ON r.division = l.division_id AND r.depot = l.depot_id
WHERE r.status = 'off_road'
ORDER BY FIELD(l.division, 'KALABURAGI-1', 'KALABURAGI-2', 'YADAGIRI', 'BIDAR', 'RAICHURU', 'BALLARI', 'KOPPALA', 'HOSAPETE', 'VIJAYAPURA'), l.depot, r.received_date ASC";

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
    .hide {
        display: none;
    }
</style>
<div class="container1">
    <table>
        <thead>
            <tr>
                <th>Sl. No</th>
                <th>Division</th>
                <th>Depot</th>
                <th>Bus Number</th>
                <th>Make</th>
                <th>Emission Norms</th>
                <th>Received Date</th>
                <th>Number of days</th>
                <th>Work Reason</th>
                <th>Work Status</th>
                <th>Remarks</th>
                <th>Action</th>
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
                        echo "<td rowspan='" . count($rows) . "'>" . $row['division_name'] . "</td>";
                        echo "<td rowspan='" . count($rows) . "'>" . $row['depot_name'] . "</td>";
                        echo "<td rowspan='" . count($rows) . "'>" . $row['bus_number'] . "</td>";
                        echo "<td rowspan='" . count($rows) . "'>" . $row['make'] . "</td>";
                        echo "<td rowspan='" . count($rows) . "'>" . $row['emission_norms'] . "</td>";
                    }

                    // Extract data from the row
                    $offRoadFromDate = $row['received_date'];
                    $partsRequired = $row['work_reason'];
                    $workstatus = $row['work_status'];
                    $remarks = $row['remarks'];
                    $daysOffRoad = $row['no_of_days'];
                    if ($daysOffRoad === null) {
                        $offRoadDate = new DateTime($offRoadFromDate);
                        $today = new DateTime();
                        $daysOffRoad = $today->diff($offRoadDate)->days;
                    }

                    // Output the data in table rows
                    echo "<td>" . date('d/m/y', strtotime($offRoadFromDate)) . "</td>";
                    echo "<td>$daysOffRoad</td>";
                    echo "<td>$partsRequired</td>";
                    echo "<td>$workstatus</td>";
                    echo "<td>$remarks</td>";
                    echo "<td class='hide' >" . $row['division'] . "</td>";
                    echo "<td class='hide' >" . $row['depot'] . "</td>";
                    // Output the "Receive" button in the first row of the current bus number
                    if ($key === 0) {
                        // Assuming $rows is the array of rows you are iterating through
            
                        // If there is only one row and its work status is "Proposal for scrap", show the button
                        if (count($rows) === 1 && $rows[0]['work_status'] === "Proposal for scrap") {
                            $max_id = $rows[0]['id']; // Maximum ID
                            $max_work_status = "Proposal for scrap"; // Work status of the maximum ID
                        } else {
                            // Get the maximum ID for this bus number
                            $max_id = $rows[0]['id'];
                            $max_work_status = ""; // Initialize max work status variable
                            foreach ($rows as $r) {
                                if ($r['id'] > $max_id) {
                                    $max_id = $r['id'];
                                    $max_work_status = $r['work_status']; // Get the work status of the maximum ID
                                }
                            }
                        }

                        // Output the buttons
                        echo "<td rowspan='" . count($rows) . "'>";
                        echo "<div style='width: 100%;'>";

                        // Button 1: Update
                        echo "<div style='margin-bottom: 5px;'>";
                        echo "<button class=\"btn btn-primary receive-btn\" style='width: 100%; height: 40px;' data-toggle=\"modal\" data-target=\"#receiveModal\" data-id=\"" . $max_id . "\">Update</button>";
                        echo "</div>";

                        // Button 2: On Road
                        echo "<div style='margin-bottom: 5px;'>";
                        echo "<form method='POST' action=''>";
                        echo "<input type='hidden' name='bus_number' value='" . $rows[0]['bus_number'] . "'>";
                        echo "<input type='hidden' name='max_id' value='" . $max_id . "'>"; // Add max_id as a hidden input
                        echo "<button type='submit' onclick=\"return confirm('Are you sure you want to set bus " . $rows[0]['bus_number'] . " on road?');\" name='on_road_btn' class='btn btn-success' style='width: 100%; height: 40px;'>On_Road</button>";
                        echo "</form>"; // End form
                        echo "</div>";

                        // Button 3: Scrap (if max work status is "Proposal for scrap")
                        if ($max_work_status == "Proposal for scrap") {
                            echo "<div style='margin-bottom: 5px;'>";
                            echo "<form method='POST' action=''>";
                            echo "<input type='hidden' name='bus_number' value='" . $rows[0]['bus_number'] . "'>";
                            echo "<input type='hidden' name='max_id' value='" . $max_id . "'>"; // Add max_id as a hidden input
                            echo "<button type='submit' onclick=\"return confirm('Are you sure you want to scrap bus " . $rows[0]['bus_number'] . "?');\" name='scrap_btn' class='btn btn-danger' style='width: 100%; height: 40px;'>Scrap</button>";
                            echo "</form>"; // End form
                            echo "</div>";
                        }

                        echo "</div>"; // End div with width: 100%
                        echo "</td>";



                    }

                    echo "</tr>";
                }

                // Increment the serial number
                $serial_number++;
            }
            ?>
        </tbody>
    </table>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['on_road_btn'])) {
        $busNumber = $_POST['bus_number'];

        // Update the status of the bus to "on_road" and set the on-road date as today's date
        $today = date('Y-m-d'); // Get today's date
    
        $sql = "UPDATE rwy_offroad SET status = 'on_road', on_road_date = '$today' WHERE bus_number = ? AND status='off_road'";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "s", $busNumber);
        mysqli_stmt_execute($stmt);

        // Check if the update was successful
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            // Get the max ID
            $maxIdSql = "SELECT MAX(id) AS max_id FROM rwy_offroad WHERE bus_number = ?";
            $maxIdStmt = mysqli_prepare($db, $maxIdSql);
            mysqli_stmt_bind_param($maxIdStmt, "s", $busNumber);
            mysqli_stmt_execute($maxIdStmt);
            mysqli_stmt_bind_result($maxIdStmt, $maxId);
            mysqli_stmt_fetch($maxIdStmt);
            mysqli_stmt_close($maxIdStmt);

            // Update no_of_days for the row with the max ID
            $receiveDateSql = "SELECT received_date FROM rwy_offroad WHERE id = ?";
            $receiveDateStmt = mysqli_prepare($db, $receiveDateSql);
            mysqli_stmt_bind_param($receiveDateStmt, "i", $maxId);
            mysqli_stmt_execute($receiveDateStmt);
            mysqli_stmt_bind_result($receiveDateStmt, $receiveDate);
            mysqli_stmt_fetch($receiveDateStmt);
            mysqli_stmt_close($receiveDateStmt);

            // Calculate the difference in days between receive date and today's date
            $daysOffRoad = (strtotime($today) - strtotime($receiveDate)) / (60 * 60 * 24);

            // Update no_of_days
            $updateNoOfDaysSql = "UPDATE rwy_offroad SET no_of_days = ? WHERE id = ?";
            $updateNoOfDaysStmt = mysqli_prepare($db, $updateNoOfDaysSql);
            mysqli_stmt_bind_param($updateNoOfDaysStmt, "ii", $daysOffRoad, $maxId);
            mysqli_stmt_execute($updateNoOfDaysStmt);

            if (mysqli_stmt_affected_rows($updateNoOfDaysStmt) > 0) {
                echo "<script>alert('Status updated successfully.'); window.location.href = window.location.href;</script>";
                exit();
            } else {
                echo "Error updating no_of_days: " . mysqli_error($db);
            }
            mysqli_stmt_close($updateNoOfDaysStmt);
        } else {
            echo "Error: " . mysqli_error($db);
        }

        mysqli_stmt_close($stmt);
    }


    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['scrap_btn'])) {
        $busNumber = $_POST['bus_number'];
    
        // Update the status of the bus to "scrap" and set the on-road date as today's date
        $today = date('Y-m-d'); // Get today's date
    
        $sql = "UPDATE rwy_offroad SET status = 'scrap', on_road_date = '$today' WHERE bus_number = ? AND status='off_road'";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "s", $busNumber);
        mysqli_stmt_execute($stmt);
    
        // Check if the update was successful
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            // Get the max ID
            $maxIdSql = "SELECT MAX(id) AS max_id FROM rwy_offroad WHERE bus_number = ?";
            $maxIdStmt = mysqli_prepare($db, $maxIdSql);
            mysqli_stmt_bind_param($maxIdStmt, "s", $busNumber);
            mysqli_stmt_execute($maxIdStmt);
            mysqli_stmt_bind_result($maxIdStmt, $maxId);
            mysqli_stmt_fetch($maxIdStmt);
            mysqli_stmt_close($maxIdStmt);
    
            // Update no_of_days for the row with the max ID
            $receiveDateSql = "SELECT received_date FROM rwy_offroad WHERE id = ?";
            $receiveDateStmt = mysqli_prepare($db, $receiveDateSql);
            mysqli_stmt_bind_param($receiveDateStmt, "i", $maxId);
            mysqli_stmt_execute($receiveDateStmt);
            mysqli_stmt_bind_result($receiveDateStmt, $receiveDate);
            mysqli_stmt_fetch($receiveDateStmt);
            mysqli_stmt_close($receiveDateStmt);
    
            // Calculate the difference in days between receive date and today's date
            $daysOffRoad = (strtotime($today) - strtotime($receiveDate)) / (60 * 60 * 24);
    
            // Update no_of_days
            $updateNoOfDaysSql = "UPDATE rwy_offroad SET no_of_days = ? WHERE id = ?";
            $updateNoOfDaysStmt = mysqli_prepare($db, $updateNoOfDaysSql);
            mysqli_stmt_bind_param($updateNoOfDaysStmt, "ii", $daysOffRoad, $maxId);
            mysqli_stmt_execute($updateNoOfDaysStmt);
    
            if (mysqli_stmt_affected_rows($updateNoOfDaysStmt) > 0) {
                echo "<script>alert('Status updated successfully.'); window.location.href = window.location.href;</script>";
                exit();
            } else {
                echo "Error updating no_of_days: " . mysqli_error($db);
            }
            mysqli_stmt_close($updateNoOfDaysStmt);
        } else {
            echo "Error: " . mysqli_error($db);
        }
    
        mysqli_stmt_close($stmt);
    }
    
    ?>
    <br><br>
    <h2 style="text-align:center;">RWY Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Division</th>
                <th>In Yard</th>
                <th>Dismantling</th>
                <th>Proposal for Scrap</th>
                <th>Structure</th>
                <th>Paneling</th>
                <th>Waiting for Spares</th>
                <th>Pre Final</th>
                <th>Final</th>
                <th>Sent to Firm for Repair</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php

            // Fetch data from database
            $sql = "SELECT 
            l.division AS Division,
            SUM(CASE WHEN t1.work_status = 'In Yard' THEN 1 ELSE 0 END) AS `In Yard`,
            SUM(CASE WHEN t1.work_status = 'Dismantling' THEN 1 ELSE 0 END) AS Dismantling,
            SUM(CASE WHEN t1.work_status = 'Proposal for scrap' THEN 1 ELSE 0 END) AS `Proposal for Scrap`,
            SUM(CASE WHEN t1.work_status = 'Structure' THEN 1 ELSE 0 END) AS Structure,
            SUM(CASE WHEN t1.work_status = 'Paneling' THEN 1 ELSE 0 END) AS Paneling,
            SUM(CASE WHEN t1.work_status = 'Waiting for spares from Division' THEN 1 ELSE 0 END) AS `Waiting for spares from Division`,
            SUM(CASE WHEN t1.work_status = 'Pre Final' THEN 1 ELSE 0 END) AS `Pre Final`,
            SUM(CASE WHEN t1.work_status = 'Final' THEN 1 ELSE 0 END) AS Final,
            SUM(CASE WHEN t1.work_status = 'Sent to firm for repair' THEN 1 ELSE 0 END) AS `Sent to firm for repair`
        FROM 
            rwy_offroad AS t1
        JOIN 
            (SELECT DISTINCT division, division_id FROM location) AS l ON t1.division = l.division_id
        WHERE 
            t1.status = 'off_road' AND
            t1.id IN (SELECT MAX(id) FROM rwy_offroad AS t2 WHERE t2.bus_number = t1.bus_number GROUP BY t2.bus_number)
        GROUP BY 
            l.division_id";



            $result = $db->query($sql);

            // Display data in table
            $totalArray = ['In Yard' => 0, 'Dismantling' => 0, 'Proposal for Scrap' => 0, 'Structure' => 0, 'Paneling' => 0, 'Waiting for spares from Division' => 0, 'Pre Final' => 0, 'Final' => 0, 'Sent to firm for repair' => 0];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['Division'] . "</td>";
                    echo "<td>" . $row['In Yard'] . "</td>";
                    echo "<td>" . $row['Dismantling'] . "</td>";
                    echo "<td>" . $row['Proposal for Scrap'] . "</td>";
                    echo "<td>" . $row['Structure'] . "</td>";
                    echo "<td>" . $row['Paneling'] . "</td>";
                    echo "<td>" . $row['Waiting for spares from Division'] . "</td>";
                    echo "<td>" . $row['Pre Final'] . "</td>";
                    echo "<td>" . $row['Final'] . "</td>";
                    echo "<td>" . $row['Sent to firm for repair'] . "</td>";
                    // Calculate total
                    $total = $row['In Yard'] + $row['Dismantling'] + $row['Proposal for Scrap'] + $row['Structure'] + $row['Paneling'] + $row['Waiting for spares from Division'] + $row['Pre Final'] + $row['Final'] + $row['Sent to firm for repair'];
                    echo "<td><b>$total</b></td>";
                    echo "</tr>";
                    // Add to total array
                    $totalArray['In Yard'] += $row['In Yard'];
                    $totalArray['Dismantling'] += $row['Dismantling'];
                    $totalArray['Proposal for Scrap'] += $row['Proposal for Scrap'];
                    $totalArray['Structure'] += $row['Structure'];
                    $totalArray['Paneling'] += $row['Paneling'];
                    $totalArray['Waiting for spares from Division'] += $row['Waiting for spares from Division'];
                    $totalArray['Pre Final'] += $row['Pre Final'];
                    $totalArray['Final'] += $row['Final'];
                    $totalArray['Sent to firm for repair'] += $row['Sent to firm for repair'];
                }
            } else {
                echo "<tr><td colspan='11'>No vehicles in RWY</td></tr>";
            }

            // Add Corporation row
            echo "<tr>";
            echo "<td><b>Corporation</b></td>";
            echo "<td><b>{$totalArray['In Yard']}</b></td>";
            echo "<td><b>{$totalArray['Dismantling']}</b></td>";
            echo "<td><b>{$totalArray['Proposal for Scrap']}</b></td>";
            echo "<td><b>{$totalArray['Structure']}</b></td>";
            echo "<td><b>{$totalArray['Paneling']}</b></td>";
            echo "<td><b>{$totalArray['Waiting for spares from Division']}</b></td>";
            echo "<td><b>{$totalArray['Pre Final']}</b></td>";
            echo "<td><b>{$totalArray['Final']}</b></td>";
            echo "<td><b>{$totalArray['Sent to firm for repair']}</b></td>";
            // Calculate total for corporation
            $totalCorporation = $totalArray['In Yard'] + $totalArray['Dismantling'] + $totalArray['Proposal for Scrap'] + $totalArray['Structure'] + $totalArray['Paneling'] + $totalArray['Waiting for spares from Division'] + $totalArray['Pre Final'] + $totalArray['Final'] + $totalArray['Sent to firm for repair'];
            echo "<td><b>$totalCorporation</b></td>";
            echo "</tr>";

            ?>
        </tbody>
    </table>

</div>
<!-- Print button -->
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
</div>


<!-- Receive Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1" role="dialog" aria-labelledby="receiveModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
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
                    <input type="text" name="divisionInput" id="divisionInput" readonly>
                    <input type="text" name="depotInput" id="depotInput" readonly>
                    <input type="hidden" name="divisionIDInput" id="divisionIDInput" readonly>
                    <input type="hidden" name="depotIDInput" id="depotIDInput" readonly>
                    <input type="text" name="makeInput" id="makeInput" readonly>
                    <input type="text" name="emissionNormsInput" id="emissionNormsInput" readonly>
                    <input type="text" name="receiveDateInput" id="receiveDateInput" readonly>
                    <input type="text" name="workreasonInput" id="workreasonInput" readonly>
                    <div class="form-group">
                        <label for="workReason">Work Status:</label>
                        <select class="form-control" id="workStatus" name="workStatus" required>
                            <option value="">Select</option>
                            <option value="In Yard">In Yard</option>
                            <option value="Dismantling">Dismantling</option>
                            <option value="Proposal for scrap">Proposal for Scrap</option>
                            <option value="Structure">Structure</option>
                            <option value="Paneling">Paneling</option>
                            <option value="Waiting for spares from Division">Waiting for spares from Division</option>
                            <option value="Pre Final">Pre Final</option>
                            <option value="Final">Final</option>
                            <option value="Sent to firm for repair">Sent to firm for repair</option>
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
        // Add event listener to "Receive" button
        $('.receive-btn').on('click', function () {
            // Get the row data
            var rowData = $(this).closest('tr').find('td');

            // Update the hidden input fields in the form with the row data
            $('#busNumberInput').val($(rowData[3]).text());
            $('#divisionInput').val($(rowData[1]).text());
            $('#depotInput').val($(rowData[2]).text());
            $('#divisionIDInput').val($(rowData[11]).text());
            $('#depotIDInput').val($(rowData[12]).text());
            $('#makeInput').val($(rowData[4]).text());
            $('#emissionNormsInput').val($(rowData[5]).text());
            $('#receiveDateInput').val($(rowData[6]).text());
            $('#workreasonInput').val($(rowData[8]).text());

            // Get the ID of the row
            var id = $(this).data('id');

            // Show the modal
            $('#receiveModal').modal('show');

            // Set the ID value in the hidden input field
            $('#idInput').val(id);
        });

        // Add event listener to "Update" button
        $('#updateButton').on('click', function () {
            // Get form data
            var workStatus = $('#workStatus').val().trim();
            var remarks = $('#remarks').val().trim();
            var id = $('#idInput').val(); // Get the id value

            // Check if work status or remarks is empty
            if (!workStatus || !remarks) {
                alert("Work Status and Remarks are required.");
                return; // Exit function
            }

            // If everything is okay, proceed with the AJAX request
            var formData = $('#receiveForm').serialize() + '&id=' + id; // Include the id in the data
            $.ajax({
                type: 'POST',
                url: 'rwy_update_status.php',
                data: formData,
                success: function (response) {
                    if (response.trim() === "success") {
                        alert("Status updated successfully.");
                        location.reload(); // Reload the page
                    } else {
                        alert("Error updating status. Please try again. Error: " + response);
                        console.log(response);
                    }
                },
                error: function (xhr, status, error) {
                    alert("Error updating status. Please try again. Error: " + error);
                }
            });
        });

    });
</script>


<?php $db->close();
include '../includes/footer.php'; ?>