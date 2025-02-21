<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM')) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve form data
        $sch_dep_time = $_POST['sch_dep_time'];
        $sch_arr_time = $_POST['sch_arr_time'];
        $sch_key_no = $_POST['sch_key_no'];
        $scheduleId = $_POST['id'];
        $busNumber1 = $_POST['bus_number_1'];
        $busMake1 = $_POST['make1'];
        $busEmissionNorms1 = $_POST['emission_norms1'];
        $divisionId = $_SESSION['DIVISION_ID'];
        $depotId = $_SESSION['DEPOT_ID'];

        // Check if bus number 2 details are present, otherwise set them to NULL
        $busNumber2 = !empty($_POST['bus_number_2']) ? $_POST['bus_number_2'] : NULL;
        $busMake2 = !empty($_POST['make2']) ? $_POST['make2'] : NULL;
        $busEmissionNorms2 = !empty($_POST['emission_norms2']) ? $_POST['emission_norms2'] : NULL;

        // Check if additional bus details are present, otherwise set them to NULL
        $additionalBusNumber = !empty($_POST['bus_number_3']) ? $_POST['bus_number_3'] : NULL;
        $additionalBusMake = !empty($_POST['make3']) ? $_POST['make3'] : NULL;
        $additionalBusEmissionNorms = !empty($_POST['emission_norms3']) ? $_POST['emission_norms3'] : NULL;


            // Retrieve current data from schedule_master for this schedule
//$existingSql = "SELECT bus_number_1, bus_make_1, bus_emission_norms_1, bus_number_2, bus_make_2, bus_emission_norms_2,
//additional_bus_number, additional_bus_make, additional_bus_emission_norms
//FROM schedule_master WHERE id = ?";
//$stmt = $db->prepare($existingSql);
//$stmt->bind_param("i", $scheduleId);
//$stmt->execute();
//$result = $stmt->get_result();
//$currentData = $result->fetch_assoc();
//$stmt->close();                
        // Check for duplicate bus numbers, excluding the current schedule (using $scheduleId)
        $checkCurrentSql = "SELECT bus_number_1, bus_number_2, service_type_id 
        FROM schedule_master
        WHERE ((bus_number_1 = ? OR bus_number_2 = ?) OR (bus_number_1 = ? OR bus_number_2 = ?))
        AND id NOT IN ($scheduleId)"; // Exclude current schedule
    
    $stmt = $db->prepare($checkCurrentSql);
    $stmt->bind_param("ssss", $busNumber1, $busNumber2, $busNumber2, $busNumber1);
    $stmt->execute();
    $checkCurrentResult = $stmt->get_result();
    
    $duplicates = [];
    
    while ($row = $checkCurrentResult->fetch_assoc()) {
        $serviceTypeId = $row['service_type_id'];
    
        // Check for duplicates based on bus_number_1 and bus_number_2
        if (($row['bus_number_1'] === $busNumber1 || $row['bus_number_2'] === $busNumber1)) {
            if ($serviceTypeId == 1) {
                $duplicates[] = $busNumber1; // For service type 1, add to duplicates
            } else if ($serviceTypeId == 2 || $serviceTypeId == 3 || $serviceTypeId == 4) {
                // For service type 2, 3, or 4, count occurrences and add only if more than 2
                $duplicates[] = $busNumber1;
            }
        }
    
        if (($row['bus_number_1'] === $busNumber2 || $row['bus_number_2'] === $busNumber2)) {
            if ($serviceTypeId == 1) {
                $duplicates[] = $busNumber2; // For service type 1, add to duplicates
            } else if ($serviceTypeId == 2 || $serviceTypeId == 3 || $serviceTypeId == 4) {
                // For service type 2, 3, or 4, count occurrences and add only if more than 2
                $duplicates[] = $busNumber2;
            }
        }
    }
    // Check for duplicates based on the count of occurrences
    if (!empty($duplicates)) {
        // Count occurrences of each bus number
        $duplicatesCount = array_count_values($duplicates);
        $errorMessage = '';
    
        foreach ($duplicatesCount as $bus => $count) {
            if ($serviceTypeId == 1 && $count >= 1) {
                // For service type 1, if count > 1, show error
                $errorMessage .= "$bus has dublicate entry";
            }
            if (($serviceTypeId == 2 || $serviceTypeId == 3 || $serviceTypeId == 4) && $count >= 2) {
                // For service type 2,3,4, if count > 2, show error
                $errorMessage .= "$bus has dublicate entry";
            }
        }
    
        // If there are any duplicate bus numbers, show an alert message
        if ($errorMessage != '') {
            echo "<script>alert('$errorMessage Please enter another bus number.'); window.history.back();</script>";
            $stmt->close();
            exit; // Stop further execution if there are duplicates
        }
    }
    
    $stmt->close();
    
            
                

        // Check if bus number 2 details are present, otherwise set them to NULL
        $busNumber2 = !empty($_POST['bus_number_2']) ? $_POST['bus_number_2'] : NULL;
        $busMake2 = !empty($_POST['make2']) ? $_POST['make2'] : NULL;
        $busEmissionNorms2 = !empty($_POST['emission_norms2']) ? $_POST['emission_norms2'] : NULL;

        // Check if additional bus details are present, otherwise set them to NULL
        $additionalBusNumber = !empty($_POST['bus_number_3']) ? $_POST['bus_number_3'] : NULL;
        $additionalBusMake = !empty($_POST['make3']) ? $_POST['make3'] : NULL;
        $additionalBusEmissionNorms = !empty($_POST['emission_norms3']) ? $_POST['emission_norms3'] : NULL;

        // Retrieve current data from schedule_master for this schedule
        $existingSql = "SELECT bus_number_1, bus_make_1, bus_emission_norms_1, bus_number_2, bus_make_2, bus_emission_norms_2,
                        additional_bus_number, additional_bus_make, additional_bus_emission_norms
                        FROM schedule_master WHERE id = ?";
        $stmt = $db->prepare($existingSql);
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentData = $result->fetch_assoc();
        $stmt->close();

        // Prepare the update query for schedule_master table
        $updateFields = [];

        // Check if bus number 1, make, and emission norms differ
        if ($busNumber1 !== $currentData['bus_number_1'] || $busMake1 !== $currentData['bus_make_1'] || $busEmissionNorms1 !== $currentData['bus_emission_norms_1']) {
            $updateFields[] = "bus_number_1 = '$busNumber1', bus_make_1 = '$busMake1', bus_emission_norms_1 = '$busEmissionNorms1'";
        }

        // Check if bus number 2, make, and emission norms differ
        if ($busNumber2 !== $currentData['bus_number_2'] || $busMake2 !== $currentData['bus_make_2'] || $busEmissionNorms2 !== $currentData['bus_emission_norms_2']) {
            $updateFields[] = "bus_number_2 = " . ($busNumber2 !== NULL ? "'$busNumber2'" : "NULL") . ",
                               bus_make_2 = " . ($busMake2 !== NULL ? "'$busMake2'" : "NULL") . ",
                               bus_emission_norms_2 = " . ($busEmissionNorms2 !== NULL ? "'$busEmissionNorms2'" : "NULL");
        }

        // Check if additional bus details differ
        if ($additionalBusNumber !== $currentData['additional_bus_number'] || $additionalBusMake !== $currentData['additional_bus_make'] || $additionalBusEmissionNorms !== $currentData['additional_bus_emission_norms']) {
            $updateFields[] = "additional_bus_number = " . ($additionalBusNumber !== NULL ? "'$additionalBusNumber'" : "NULL") . ",
                               additional_bus_make = " . ($additionalBusMake !== NULL ? "'$additionalBusMake'" : "NULL") . ",
                               additional_bus_emission_norms = " . ($additionalBusEmissionNorms !== NULL ? "'$additionalBusEmissionNorms'" : "NULL");
        }

        // Update schedule_master if any fields have changed
        if (!empty($updateFields)) {
            $updateSql = "UPDATE schedule_master SET " . implode(", ", $updateFields) . " WHERE id = $scheduleId";
            if (!mysqli_query($db, $updateSql)) {
                echo "<script>alert('Error updating schedule: " . mysqli_error($db) . "');</script>";
                exit;
            }
        }




$created_by = $_SESSION['USERNAME'];
$current1 =  $currentData['bus_number_1'];
$current2 =  $currentData['bus_number_2'];
$current3 =  $currentData['additional_bus_number'];

// Prepare the update and insert queries
$updateBusFixSql = "UPDATE bus_fix_data SET to_date = NOW() WHERE sch_key_no = ? AND division_id = ? AND depot_id = ? AND bus_number = ? AND to_date IS NULL";
$insertBusFixSql = "INSERT INTO bus_fix_data (sch_key_no, division_id, depot_id, bus_number, bus_make, bus_emission_norms, additional, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
// Function to update `to_date` for old records


function updateBusData($db, $updateBusFixSql, $sch_key_no, $divisionId, $depotId, $current1) {
    if (!empty($current1)) {
        $stmt = $db->prepare($updateBusFixSql);
        $stmt->bind_param('siis', $sch_key_no, $divisionId, $depotId, $current1);
        $stmt->execute();
        $stmt->close();
    }
}
function updateBusData1($db, $updateBusFixSql, $sch_key_no, $divisionId, $depotId, $current2) {
    if (!empty($current2)) {
        $stmt = $db->prepare($updateBusFixSql);
        $stmt->bind_param('siis', $sch_key_no, $divisionId, $depotId, $current2);
        $stmt->execute();
        $stmt->close();
    }
}
function updateBusData2($db, $updateBusFixSql, $sch_key_no, $divisionId, $depotId, $current3) {
    if (!empty($current3)) {
        $stmt = $db->prepare($updateBusFixSql);
        $stmt->bind_param('siis', $sch_key_no, $divisionId, $depotId, $current3);
        $stmt->execute();
        $stmt->close();
    }
}
// Function to insert new bus data
function insertBusData($db, $insertBusFixSql, $sch_key_no, $divisionId, $depotId, $busNumber1, $busMake1, $busEmissionNorms1, $additional, $created_by) {
    if (!empty($busNumber1)) {
        $stmt = $db->prepare($insertBusFixSql);
        $stmt->bind_param('siisssis', $sch_key_no, $divisionId, $depotId, $busNumber1, $busMake1, $busEmissionNorms1, $additional, $created_by);
        $stmt->execute();
        $stmt->close();
    }
}
function insertBusData1($db, $insertBusFixSql, $sch_key_no, $divisionId, $depotId, $busNumber2, $busMake2, $busEmissionNorms2, $additional, $created_by) {
    
    if (!empty($busNumber2)) {
        $stmt = $db->prepare($insertBusFixSql);
        $stmt->bind_param('siisssis', $sch_key_no, $divisionId, $depotId, $busNumber2, $busMake2, $busEmissionNorms2, $additional, $created_by);
        $stmt->execute();
        $stmt->close();
    }
    
}
function insertBusData2($db, $insertBusFixSql, $sch_key_no, $divisionId, $depotId, $additionalBusNumber, $additionalBusMake, $additionalBusEmissionNorms, $additional, $created_by) {
    if (!empty($additionalBusNumber)) {
        $stmt = $db->prepare($insertBusFixSql);
        $stmt->bind_param('siisssis', $sch_key_no, $divisionId, $depotId, $additionalBusNumber, $additionalBusMake, $additionalBusEmissionNorms, $additional, $created_by);
        $stmt->execute();
        $stmt->close();
    }
}
// Check and update busNumber1, then insert new busNumber1
if ($busNumber1 != NULL) {
    if ($busNumber1 !== $current1) {
        updateBusData($db, $updateBusFixSql, $sch_key_no, $divisionId, $depotId, $current1);
        insertBusData($db, $insertBusFixSql, $sch_key_no, $divisionId, $depotId, $busNumber1, $busMake1, $busEmissionNorms1, 0, $_SESSION['USERNAME']);
        ?>
<script>
    console.log("Current Bus Number 1: <?php echo $current1; ?>");
    console.log("EWntered Bus Number 1: <?php echo $busNumber1; ?>");

</script>
<?php
    }
    if ($current1 === $busNumber1 ) {
    }
}

// Check and update busNumber2, then insert new busNumber2
if ($busNumber2 != NULL) {
    if ($current2 !== $busNumber2 ) {
        updateBusData1($db, $updateBusFixSql, $sch_key_no, $divisionId, $depotId, $current2);
        insertBusData1($db, $insertBusFixSql, $sch_key_no, $divisionId, $depotId, $busNumber2, $busMake2, $busEmissionNorms2, 0, $_SESSION['USERNAME']);
    }
    if ($current2 === $busNumber2 ) {
    } 
}

// Check and update additionalBusNumber, then insert new additionalBusNumber
if ($additionalBusNumber != NULL) {
    if ($current3 !== $additionalBusNumber) {
        updateBusData2($db, $updateBusFixSql, $sch_key_no, $divisionId, $depotId, $current3);
        insertBusData2($db, $insertBusFixSql, $sch_key_no, $divisionId, $depotId, $additionalBusNumber, $additionalBusMake, $additionalBusEmissionNorms, 1, $_SESSION['USERNAME']);

    }
    if ( $current3 === $additionalBusNumber) {
    }
}

echo "<script>
alert('Schedule updated and data inserted successfully');
window.location.href = 'depot_schedule_b.php';
</script>";

}

?>
    <?php
    // Prepare and execute the query to count schedules
    $sql_count = "SELECT COUNT(*) AS schedule_count
FROM schedule_master
WHERE division_id = ? AND depot_id = ? and status='1'";

    $stmt = $db->prepare($sql_count);
    $stmt->bind_param("ii", $_SESSION['DIVISION_ID'], $_SESSION['DEPOT_ID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Get the count of schedules
    $schedule_count = $row['schedule_count'];



    // Prepare and execute the query to get sch_count values
    $sql = "SELECT sch_count
            FROM schedule_master
            WHERE division_id = ? AND depot_id = ? and status='1'";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $_SESSION['DIVISION_ID'], $_SESSION['DEPOT_ID']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize counters
    $total_count = 0;

    // Process the result set
    while ($row = $result->fetch_assoc()) {
        // Check the value of sch_count and adjust the total count accordingly
        $sch_count = $row['sch_count'];
        if ($sch_count == 1) {
            $total_count += 1;
        } elseif ($sch_count == 2) {
            $total_count += 2;
        }
        // If there are other cases, handle them as needed
        // else {
        //     $total_count += $sch_count; // Adjust as needed
        // }
    }


    // Close the connection
    $stmt->close();
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
        <h4>Depot: <?php echo $_SESSION['DEPOT']; ?></h4>
        <h4 class="center">SCHEDULE MASTER</h4>
        <h4 class="center">Schedule Counts: <?php echo $total_count; ?></h4>
        <h4 class="center">Departure Counts: <?php echo $schedule_count; ?></h4>
    </div>
    <table id="dataTable4">
        <thead>
            <tr>
                <th class="hide">ID</th>
                <th>Sch NO</th>
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
        AND skm.depot_id = '" . $_SESSION['DEPOT_ID'] . "'
        and skm.status = 1";

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
                    echo '<div style="white-space: nowrap;">';
                    echo '<button class="btn btn-warning update-details">Update</button>&nbsp;';
                    echo '<button class="btn btn-primary view-details">Details</button>';
                    echo '</div>';
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
                                                                    </div>
                                                                    <input type="hidden" id="number_of_buses" name="number_of_buses" value="${details.number_of_buses}">`;

                        // Populate bus details for buses 1 and 2
                        var busFieldsHtml = '';
                        for (var i = 1; i <= details.number_of_buses; i++) {
                            busFieldsHtml += `
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="bus_number${i}">Bus ${i} Number</label>
                        <input type="text" id="bus_number_${i}" name="bus_number_${i}" class="form-control" value="${details['bus_number_' + i] || ''}" required oninput="this.value = this.value.toUpperCase()" onChange="searchBus(${i})">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="make${i}">Bus ${i} Make</label>
                        <input type="text" id="make${i}" name="make${i}" class="form-control" value="${details['bus_make_' + i] || ''}" readonly>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="emission_norms${i}">Bus ${i} Emission Norms</label>
                        <input type="text" id="emission_norms${i}" name="emission_norms${i}" class="form-control" value="${details['bus_emission_norms_' + i] || ''}" readonly>
                    </div>
                </div>
            </div>`;
                        }

                        // Check and populate additional bus details if available
                        if (details.additional_bus_number) {
                            // Automatically check the checkbox if additional bus is found
                            scheduleFieldsHtml += `
            <div class="form-group">
                <label for="agree">Have Additional Bus:</label>
                <input type="checkbox" id="agree" name="agree" value="yes" checked>
            </div>`;

                            busFieldsHtml += `
            <div class="row additional-bus-fields">
                <div class="col">
                    <div class="form-group">
                        <label for="additional_bus_number">Additional Bus Number</label>
                        <input type="text" id="bus_number_3" name="bus_number_3" class="form-control" value="${details.additional_bus_number}" required oninput="this.value = this.value.toUpperCase()" onChange="searchBus(3)">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="make3">Additional Bus Make</label>
                        <input type="text" id="make3" name="make3" class="form-control" value="${details.additional_bus_make}" readonly>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="emission_norms3">Additional Bus Emission Norms</label>
                        <input type="text" id="emission_norms3" name="emission_norms3" class="form-control" value="${details.additional_bus_emission_norms}" readonly>
                    </div>
                </div>
            </div>`;
                        } else {
                            // Add checkbox if no additional bus
                            scheduleFieldsHtml += `
            <div class="form-group">
                <label for="agree">Have Additional Bus:</label>
                <input type="checkbox" id="agree" name="agree" value="yes">
            </div>`;
                        }

                        // Populate the form with schedule fields
                        $('#scheduleFields').html(scheduleFieldsHtml);
                        $('#busFields').html(busFieldsHtml);
                        $('#updateModal').modal('show');

                        // Handle the additional bus checkbox event
                        $('#agree').change(function () {
                            if (this.checked) {
                                // Append additional bus fields
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
                                // Remove additional bus fields if unchecked
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
                dataType: 'json',
                success: function (response) {
                    if (response.make !== undefined && response.make !== null) {
                        // Populate form fields with fetched data
                        $('#make' + index).val(response.make);
                        $('#bus_number_' + index).val(busNumber);
                        $('#emission_norms' + index).val(response.emission_norms);

                        // Check if the bus number is already allocated in schedule_master
                        $.ajax({
                            url: '../database/check_bus_allocation.php',
                            type: 'POST',
                            data: { busNumber: busNumber },
                            dataType: 'json',
                            success: function (checkResponse) {
                                if (checkResponse.exists) {
                                    // Show confirmation modal
                                    $('#confirmationModal').modal('show');
                                    $('#confirmationMessage').html(
                                        'The bus number ' + busNumber + ' is already allocated to schedule ' + checkResponse.sch_key_no + '. Do you want to reallocate this bus number?'
                                    );

                                    // Handle Yes button click
                                    $('#confirmYes').on('click', function () {
                                        $.ajax({
                                            url: '../database/reallocate_bus.php',
                                            type: 'POST',
                                            data: {
                                                busNumber: busNumber,
                                                oldSchKeyNo: checkResponse.sch_key_no
                                            },
                                            dataType: 'json', // Ensure correct data type
                                            success: function (reallocateResponse) {
                                                if (reallocateResponse.success) {
                                                    alert('Bus number reallocated successfully.');
                                                    // Optionally, update the UI or perform other actions
                                                } else {
                                                    alert('Error reallocating bus number: ' + (reallocateResponse.error || 'Unknown error'));
                                                }
                                                $('#confirmationModal').modal('hide');
                                            },
                                            error: function (xhr, status, error) {
                                                console.error('Error:', xhr.responseText); // Log response text for debugging
                                                alert('Error reallocating bus number.');
                                                $('#confirmationModal').modal('hide');
                                            }
                                        });

                                    });

                                    // Handle No button click
                                    $('#confirmNo').on('click', function () {
                                        // Clear fields if not reallocating
                                        $('#make' + index).val('');
                                        $('#bus_number_' + index).val('');
                                        $('#emission_norms' + index).val('');
                                        $('#confirmationModal').modal('hide');
                                    });
                                }
                            },
                            error: function () {
                                alert('Error checking bus allocation.');
                                $('#make' + index).val('');
                                $('#bus_number_' + index).val('');
                                $('#emission_norms' + index).val('');
                            }
                        });
                    } else {
                        // Clear fields if bus number not found
                        $('#make' + index).val('');
                        $('#bus_number_' + index).val('');
                        $('#emission_norms' + index).val('');
                    }
                },
                error: function (xhr, status, error) {
                    if (xhr.status === 403) {
                        alert(xhr.responseJSON.error);
                    } else {
                        alert('Error: Bus not Registered in KKRTC.');
                    }
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
    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="confirmationMessage"></p>
                    <p style="color:red">Note: If toy click Yes then the vehicle will remove from the previous alloted
                        schedule to this schedule.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="confirmYes">Yes</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="confirmNo">No</button>
                </div>
            </div>
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
    <script>
        $(document).ready(function () {
            $('#dataTable4').DataTable({
                "paging": true, // Enable pagination
                "lengthChange": true, // Enable the row count dropdown
                "searching": true, // Enable search functionality
                "ordering": true, // Enable sorting
                "info": true, // Show table information summary
                "autoWidth": true, // Automatically adjust column widths
                "order": [[4, 'asc']] // Default ordering based on the 5th column (index 4), 'asc' means ascending
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