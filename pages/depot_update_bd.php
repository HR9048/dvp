<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>
    <h6>Update old Break Down Report Data</h6>
    <!-- create a table fetch the data from bd_datas table where bd_location or route_number or km_after_docking is null give update button for those rows -->
    <div class="container1">
        <div id="reportContainer">
            <table class="table table-bordered" id="bdTable">
                <thead>
                    <tr>
                        <th>Serial No</th>
                        <th>Division</th>
                        <th>Depot</th>
                        <th>BD Date</th>
                        <th>Bus Number</th>
                        <th>BD Location</th>
                        <th>Route Number</th>
                        <th>KM After Docking</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch data from bd_datas table where bd_location or route_number or km_after_docking is null
                    $query = "SELECT bd.*, l.kmpl_division as division, l.depot FROM bd_datas bd
                              LEFT JOIN location l ON bd.depot_id = l.depot_id AND bd.division_id = l.division_id
                              WHERE (bd.bd_location IS NULL OR bd.route_number IS NULL OR bd.km_after_docking IS NULL)
                              AND bd.division_id = ? AND bd.depot_id = ?
                              ORDER BY bd.bd_date ASC";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("ii", $division_id, $depot_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $serial_number = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $serial_number++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['division']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['depot']) . "</td>";
                        echo "<td>" . date('d-m-Y', strtotime($row['bd_date'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['bus_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['bd_location'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row['route_number'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row['km_after_docking'] ?? 'N/A') . "</td>";
                        // for update button on click call a function updateBDData with parameter id of the row
                        echo "<td><button class='btn btn-primary' onclick='updateBDData(" . $row['id'] . ")'>Update</button></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        function updateBDData(id) {
            $.ajax({
                type: 'POST',
                url: '../includes/backend_data.php',
                data: {
                    id: id,
                    action: 'fetchBDDataById'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var data = response.data;
                        $('#updateModal').remove();

                        var modalHtml = `
                    <div class="modal" id="updateModal" tabindex="-1">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Update Break Down Data</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <form id="updateForm">
                              <input type="hidden" name="id" value="` + data.id + `">
                              <div class="row">
                              <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bus_number" class="form-label">Bus Number</label>
                                    <input type="text" class="form-control" id="bus_number" name="bus_number" value="` + data.bus_number + `" readonly>
                                </div>
                                </div>
                                <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bd_date" class="form-label">BD Date</label>
                                    <input type="text" class="form-control" id="bd_date" name="bd_date" value="` + data.bd_date + `" readonly>
                                </div>
                                </div>
                                </div>
                                <div class="row">
                                <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cause" class="form-label">Cause</label>
                                    <input type="text" class="form-control" id="cause" name="cause" value="` + data.cause + `" readonly>
                                </div>
                                </div>
                                <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reason" class="form-label">Reason</label>
                                    <input type="text" class="form-control" id="reason" name="reason" value="` + data.reason + `" readonly>
                                </div>
                                </div>
                                </div>
                              <div class="mb-3">
                                <label for="bd_location" class="form-label">BD Location</label>
                                <input type="text" class="form-control" id="bd_location" name="bd_location" value="` + (data.bd_location || '') + `" required>
                              </div>
                              <div class="mb-3">
                                <label for="route_number" class="form-label">Route Number</label>
                                <input type="text" class="form-control" id="route_number" name="route_number" value="` + (data.route_number || '') + `" required>
                              </div>
                              <div class="mb-3">
                                <label for="km_after_docking" class="form-label">KM After Docking</label>
                                <input type="number" class="form-control" id="km_after_docking" name="km_after_docking" value="` + (data.km_after_docking || '') + `" required>
                              </div>
                                <button type="button" class="btn btn-primary" onclick="saveupdatedBDData()">Save Changes</button>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                  `;
                        // Append modal HTML to body
                        $('body').append(modalHtml);
                        // Show modal
                        var modal = new bootstrap.Modal(document.getElementById('updateModal'));
                        modal.show();
                    }
                }
            });

        }

        function saveupdatedBDData() {
            // validate the form data before sending to backend
            var bd_location = $('#bd_location').val().trim();
            var route_number = $('#route_number').val().trim();
            var km_after_docking = $('#km_after_docking').val().trim();
            if (bd_location === '' || route_number === '' || km_after_docking === '') {
                Swal.fire({
                    icon: 'error',
                    title: 'All fields are required',
                    showConfirmButton: true,
                });
                return;
            }


            var formData = $('#updateForm').serialize() + '&action=updateBDData';
            $.ajax({
                type: 'POST',
                url: '../includes/backend_data.php',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Break Down Data updated successfully',
                            showConfirmButton: true,
                        }).then((result) => {
                            if (result.isConfirmed || result.isDismissed) {
                                // Close modal
                                $('#updateModal').modal('hide');
                                // Remove modal from DOM
                                $('#updateModal').remove();
                                // Reload the page
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error updating Break Down Data',
                            text: response.message || '',
                            showConfirmButton: false,
                            showCloseButton: true
                        });
                    }
                }

            });
        }
    </script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>