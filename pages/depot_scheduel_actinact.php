<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
$division = $_SESSION['DIVISION_ID'];
$depot = $_SESSION['DEPOT_ID'];
$user = $_SESSION['USERNAME'];
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR' || $_SESSION['JOB_TITLE'] == 'DM') {

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        $scheduleId = intval($_POST['schedule_id']);
        $scheduleNo = $_POST['sch_key_no'];
        $status = ($_POST['action'] == 'activate') ? 1 : 0;
        $currentDateTime = date('Y-m-d H:i:s'); // Current server datetime
        // Fetch existing data
        $selectSql = "SELECT * FROM schedule_master WHERE ID = ? AND division_id = ? AND depot_id = ?";
        $stmt = $db->prepare($selectSql);
        $stmt->bind_param('iii', $scheduleId, $division, $depot);
        $stmt->execute();
        $result = $stmt->get_result();
        $scheduleData = $result->fetch_assoc();
        $stmt->close();

        if ($scheduleData) {
            // Prepare SQL for updating bus_fix_data
            $updateBusFixSql = "UPDATE bus_fix_data 
                                    SET to_date = ? 
                                    WHERE bus_number = ? 
                                    AND depot_id = ? 
                                    AND division_id = ?
                                    and sch_key_no = ?";
            $busStmt = $db->prepare($updateBusFixSql);
            $busStmt->bind_param('ssiis', $currentDateTime, $scheduleData['bus_number_1'], $depot, $division, $scheduleNo);
            $busStmt->execute();
            $busStmt->close();

            $updateBus2FixSql = "UPDATE bus_fix_data 
            SET to_date = ? 
            WHERE bus_number = ? 
            AND depot_id = ? 
            AND division_id = ?
            and sch_key_no = ?";
            $bus2Stmt = $db->prepare($updateBus2FixSql);
            $bus2Stmt->bind_param('ssiis', $currentDateTime, $scheduleData['bus_number_2'], $depot, $division, $scheduleNo);
            $bus2Stmt->execute();
            $bus2Stmt->close();

            $updateBus3FixSql = "UPDATE bus_fix_data 
            SET to_date = ? 
            WHERE bus_number = ? 
            AND depot_id = ? 
            AND division_id = ?
            and sch_key_no = ?";
            $bus3Stmt = $db->prepare($updateBus3FixSql);
            $bus3Stmt->bind_param('ssiis', $currentDateTime, $scheduleData['additional_bus_number'], $depot, $division, $scheduleNo);
            $bus3Stmt->execute();
            $bus3Stmt->close();

            // Prepare SQL for updating crew_fix_data
            $updateCrewFixSql = "UPDATE crew_fix_data 
                                     SET to_date = ? 
                                     WHERE crew_pf = ? 
                                     AND depot_id = ? 
                                     AND division_id = ?
                                     and sch_key_no = ?";

            // Driver updates
            for ($i = 1; $i <= 6; $i++) {
                $driverPfKey = "driver_pf_$i";
                if (isset($scheduleData[$driverPfKey]) && $scheduleData[$driverPfKey] !== NULL) {
                    $crewStmt = $db->prepare($updateCrewFixSql);
                    $crewStmt->bind_param('ssiis', $currentDateTime, $scheduleData[$driverPfKey], $depot, $division, $scheduleNo);
                    $crewStmt->execute();
                    $crewStmt->close();
                }
            }

            // Conductor updates
            for ($i = 1; $i <= 3; $i++) {
                $conductorPfKey = "conductor_pf_$i";
                if (isset($scheduleData[$conductorPfKey]) && $scheduleData[$conductorPfKey] !== NULL) {
                    $crewStmt = $db->prepare($updateCrewFixSql);
                    $crewStmt->bind_param('ssiis', $currentDateTime, $scheduleData[$conductorPfKey], $depot, $division, $scheduleNo);
                    $crewStmt->execute();
                    $crewStmt->close();
                }
            }

            // Update schedule_master
            $updateScheduleSql = "UPDATE schedule_master 
                                      SET status = ?, 
                                          single_crew = NULL,
                                          bus_number_1 = NULL,
                                          bus_make_1 = NULL,
                                          bus_emission_norms_1 = NULL,
                                          bus_number_2 = NULL,
                                          bus_make_2 = NULL,
                                          bus_emission_norms_2 = NULL,
                                          additional_bus_number = NULL,
                                          additional_bus_make = NULL,
                                          additional_bus_emission_norms = NULL,
                                          driver_token_1 = NULL,
                                          driver_pf_1 = NULL,
                                          driver_name_1 = NULL,
                                          driver_token_2 = NULL,
                                          driver_pf_2 = NULL,
                                          driver_name_2 = NULL,
                                          driver_token_3 = NULL,
                                          driver_pf_3 = NULL,
                                          driver_name_3 = NULL,
                                          driver_token_4 = NULL,
                                          driver_pf_4 = NULL,
                                          driver_name_4 = NULL,
                                          driver_token_5 = NULL,
                                          driver_pf_5 = NULL,
                                          driver_name_5 = NULL,
                                          driver_token_6 = NULL,
                                          driver_pf_6 = NULL,
                                          driver_name_6 = NULL,
                                          half_releiver_token_1 = NULL,
                                          half_releiver_pf_1 = NULL,
                                          half_releiver_name_1 = NULL,
                                          half_releiver_token_2 = NULL,
                                          half_releiver_pf_2 = NULL,
                                          half_releiver_name_2 = NULL,
                                          conductor_token_1 = NULL,
                                          conductor_pf_1 = NULL,
                                          conductor_name_1 = NULL,
                                          conductor_token_2 = NULL,
                                          conductor_pf_2 = NULL,
                                          conductor_name_2 = NULL,
                                          conductor_token_3 = NULL,
                                          conductor_pf_3 = NULL,
                                          conductor_name_3 = NULL
                                      WHERE ID = ? 
                                      AND division_id = ? 
                                      AND depot_id = ?";
            $stmt = $db->prepare($updateScheduleSql);
            $stmt->bind_param('iiii', $status, $scheduleId, $division, $depot);
            $stmt->execute();
            $stmt->close();

            if ($status === 0) {
                $insertinactive = "INSERT INTO sch_actinact(sch_key_no, division_id, depot_id, created_by)VALUES (?, ?, ?, ?)";
                $inactivesch = $db->prepare($insertinactive);
                $inactivesch->bind_param('siis', $scheduleNo, $division, $depot, $user);
                $inactivesch->execute();
                $inactivesch->close();
            } elseif ($status === 1) {
                $updateinactive = "UPDATE sch_actinact set inact_to=? where sch_key_no=? and division_id = ? and depot_id = ?";
                $inactschup = $db->prepare($updateinactive);
                $inactschup->bind_param('ssii', $currentDateTime, $scheduleNo, $division, $depot);
                $inactschup->execute();
                $inactschup->close();
            }
            // Use JavaScript to show alert and redirect
            echo "<script type='text/javascript'>
                alert('Schedule updated successfully.');
                window.location = 'depot_scheduel_actinact.php';
              </script>";
            exit();
        } else {
            echo "<script type='text/javascript'>
                alert('Schedule not found.');
                window.location = 'depot_scheduel_actinact.php';
              </script>";
            exit();
        }
    }

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
    WHERE 
        skm.division_id = '" . $division . "' 
        AND skm.depot_id = '" . $depot . "'
    ORDER BY 
        skm.sch_dep_time";


            $result = $db->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Combine bus numbers, driver tokens, and half-reliever tokens
                    $driver_tokens = [$row['driver_token_1'], $row['driver_token_2'], $row['driver_token_3'], $row['driver_token_4'], $row['driver_token_5'], $row['driver_token_6']];
                    $conductor_tokens = [$row['conductor_token_1'], $row['conductor_token_2'], $row['conductor_token_3']];
                    // Determine button based on status
                    $actionButton = ($row['status'] == '1')
                        ? '<button class="btn btn-danger inactive-details">Inactive</button>'
                        : '<button class="btn btn-success active-details">Active</button>';

                    // Check if all conductor tokens are null or empty
                    if (($row['single_crew'] === 'yes')) {
                        $conductor_tokens = ['Single Crew Operation'];
                    }
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
                    echo '<div style="white-space: nowrap;">';
                    echo $actionButton; // Display the button based on status
                    echo '</div>';
                    echo '</td>
            </tr>';
                }
            } else {
                echo '<tr><td colspan="11">No results found</td></tr>';
            }
            ?>
        </tbody>
    </table>
    <!-- Modal HTML -->
    <div id="confirmationModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="close" onclick="closeModal12()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal12()">Cancel</button>
                <button type="button" id="confirmButton" class="btn btn-primary">Confirm</button>
            </div>
        </div>
    </div>
</div>
    <!-- Hidden Form for Action -->
    <form id="hiddenForm" method="POST" action="">
        <input type="hidden" name="schedule_id" id="hiddenScheduleId">
        <input type="hidden" name="action" id="hiddenAction">
        <input type="hidden" name="sch_key_no" id="hiddenSchKeyNo">
        <input type="hidden" name="division_id" value="<?php echo $division; ?>">
        <input type="hidden" name="depot_id" value="<?php echo $depot; ?>">
    </form>
    <script>
   function closeModal12() {
    $('#confirmationModal').modal('hide'); // Hide the modal using jQuery
    $('.modal-backdrop').remove(); // Remove the modal backdrop
  }

</script>
    <script>
       

        $(document).ready(function () {
            var actionUrl = ''; // URL to send the AJAX request to
            var scheduleId = ''; // ID of the schedule to update
            var actionType = ''; // Action type ('activate' or 'deactivate')
            var scheduleNo = ''; // Schedule number for confirmation

            // Handle button clicks
            $('#dataTable4').on('click', '.active-details, .inactive-details', function () {
                var button = $(this);
                scheduleId = button.closest('tr').data('id');
                actionType = button.hasClass('active-details') ? 'activate' : 'deactivate';
                scheduleNo = button.closest('tr').find('td:eq(1)').text(); // Get schedule number

                // Set the message based on the action type
                var message = actionType === 'activate' ?
                    `Are you sure you want to activate the schedule: ${scheduleNo}.` :
                    `Are you sure you want to deactivate the schedule: ${scheduleNo}.  On click Conform all the alloted details of crew and bus data will be set as Null`;

                $('#modalMessage').text(message);
                $('#hiddenScheduleId').val(scheduleId);
                $('#hiddenAction').val(actionType);
                $('#hiddenSchKeyNo').val(scheduleNo);
                $('#confirmationModal').modal('show');
            });

            // Confirm button click
            $('#confirmButton').on('click', function () {
                $('#hiddenForm').submit(); // Submit the hidden form
            });

            $('#dataTable4').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": true,
                "order": [[4, 'asc']]
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