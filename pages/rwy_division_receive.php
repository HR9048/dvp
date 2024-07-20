<?php
include '../includes/connection.php';
include '../includes/rwy_top.php';

$query = 'SELECT ID, t.TYPE FROM users u JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
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
    <?php } elseif ($Aa == 'DIVISION') {
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
        </script>
    <?php } elseif ($Aa == 'HEAD-OFFICE') {
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Central office Page");
            window.location = "division.php";
        </script>
    <?php }
}





// Fetching latest details of vehicles that are off-road and belong to the user's division
$sql = "SELECT 
        o.*, 
        l.division AS division_name, 
        l.depot AS depot_name,
        DATEDIFF(CURDATE(), o.off_road_date) AS days_off_road
    FROM off_road_data o
    JOIN location l ON o.depot = l.depot_id
    WHERE o.status = 'off_road'
        AND o.off_road_location = 'RWY'
        AND NOT EXISTS (
            SELECT 1
            FROM (
                SELECT r.bus_number, r.status, r.on_road_date
                FROM rwy_offroad r
                JOIN (
                    SELECT bus_number, MAX(ID) AS max_id
                    FROM rwy_offroad
                    GROUP BY bus_number
                ) latest ON r.bus_number = latest.bus_number AND r.ID = latest.max_id
                WHERE r.status = 'off_road'
                   OR (r.status = 'on_road' AND r.on_road_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY))
            ) latest_status
            WHERE latest_status.bus_number = o.bus_number
        )
    ORDER BY l.division_id, l.depot_id, o.off_road_date ASC";


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
<table>
    <thead>
        <tr>
            <th>Sl. No</th>
            <th>Division</th>
            <th>Depot</th>
            <th>Bus Number</th>
            <th>Make</th>
            <th>Emission Norms</th>
            <th>Off Road From Date</th>
            <th>Number of days off-road</th>
            <th>Off Road Location</th>
            <th>Parts Required</th>
            <th>Remarks</th>
            <th>DWS Remarks</th>
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
                $offRoadFromDate = $row['off_road_date'];
                $offRoadLocation = $row['off_road_location'];
                $partsRequired = $row['parts_required'];
                $remarks = $row['remarks'];
                $dws_remarks = $row['dws_remark'];

                // Calculate the number of days off-road
                $offRoadDate = new DateTime($offRoadFromDate);
                $today = new DateTime();
                $daysOffRoad = $today->diff($offRoadDate)->days;

                // Output the data in table rows
                echo "<td>" . date('d/m/y', strtotime($offRoadFromDate)) . "</td>";
                echo "<td>$daysOffRoad</td>";
                echo "<td>$offRoadLocation</td>";
                echo "<td>$partsRequired</td>";
                echo "<td>$remarks</td>";
                echo "<td>$dws_remarks</td>";
                echo "<td class='hide' >" . $row['division'] . "</td>";
                echo "<td class='hide' >" . $row['depot'] . "</td>";
                // Output the "Receive" button in the first row of the current bus number
                if ($key === 0) {
                    echo "<td rowspan='" . count($rows) . "'><button class=\"btn btn-success receive-btn\" data-toggle=\"modal\" data-target=\"#receiveModal\">Receive</button></td>";
                }

                echo "</tr>";
            }

            // Increment the serial number
            $serial_number++;
        }
        ?>
    </tbody>
</table>



<!-- Receive Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1" role="dialog" aria-labelledby="receiveModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiveModalLabel">Receive Vehicle</h5>
            </div>
            <div class="modal-body">
                <form id="receiveForm">
                    <input type="text" name="busNumberInput" id="busNumberInput" readonly>
                    <input type="text" name="divisionInput" id="divisionInput" readonly>
                    <input type="text" name="depotInput" id="depotInput" readonly>
                    <input type="hidden" name="divisionIDInput" id="divisionIDInput" readonly>
                    <input type="hidden" name="depotIDInput" id="depotIDInput" readonly>
                    <input type="text" name="makeInput" id="makeInput" readonly>
                    <input type="text" name="emissionNormsInput" id="emissionNormsInput" readonly>
                    <input type="text" name="offRoadFromDateInput" id="offRoadFromDateInput" readonly>
                    <div class="form-group">
                        <label for="receivedDate">Received Date:</label>
                        <input type="date" class="form-control" id="receivedDate" name="receivedDate" Required>
                    </div>
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="receiveButton">Receive Bus</button>
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
</script>
<script>
    $(document).ready(function () {
        // Add event listener to "Receive" button
        $('.receive-btn').on('click', function () {
            // Get the row data
            var rowData = $(this).closest('tr').find('td');

            // Update the hidden input fields in the form with the row data
            $('#busNumberInput').val($(rowData[3]).text());
            $('#divisionInput').val($(rowData[1]).text());
            $('#depotInput').val($(rowData[2]).text());
            $('#divisionIDInput').val($(rowData[12]).text());
            $('#depotIDInput').val($(rowData[13]).text());
            $('#makeInput').val($(rowData[4]).text());
            $('#emissionNormsInput').val($(rowData[5]).text());
            $('#offRoadFromDateInput').val($(rowData[6]).text());

            // Show the modal
            $('#receiveModal').modal('show');
        });

        // Add event listener to "Receive Bus" button
        $('#receiveButton').on('click', function () {
            // Check if all form fields are filled out
            var receivedDate = $('#receivedDate').val();
            var workReason = $('#workReason').val();

            if (!receivedDate) {
                alert("Error: Please select Received date.");
                return;
            }

            if (!workReason) {
                alert("Error: Please select Work reason.");
                return;
            }

            // If all checks pass, proceed with AJAX request
            $.ajax({
                type: 'POST',
                url: 'rwy_receive_bus_submission.php',
                data: $('#receiveForm').serialize(), // Serialize the form data
                success: function (response) {
                    // Parse the response JSON
                    var data = JSON.parse(response);
                    if (data.success) {
                        // Show success message in alert
                        alert("Data inserted successfully.");
                        // Refresh the page
                        location.reload();
                    } else {
                        // Show error message in alert
                        alert("Error: " + data.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error(error);
                    // Show error message in alert
                    alert("Error: Unable to process request. Please try again later.");
                }
            });
        });
    });
</script>



<?php include '../includes/footer.php'; ?>