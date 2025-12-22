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
    <style>
        .modal-70 {
            max-width: 70% !important;
        }
        .hidden{
            display:none;
        }
    </style>

    <!-- Button to open the Add Tech Tool modal -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#techtoolAddModal">
        Add Tech Tool
    </button>

    <div class="container1">
        <h2 class="text-center">View Tech Tool Details</h2>
        <div class="table-responsive">
            <table id="techToolTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th class="hidden">ID</th>
                        <th>Make</th>
                        <th>Model</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>Mechanic Name</th>
                        <th>Mechanic Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch tech tool details from the database
                    $query = "SELECT id, make, model, serial_number, status, mechanic_name, mechanic_contact FROM tech_tools_details WHERE division_id = ? AND depot_id = ?";
                    $stmt = mysqli_prepare($db, $query);
                    mysqli_stmt_bind_param($stmt, 'ii', $division_id, $depot_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    //if result is greater than 0 then fetch data
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td class='hidden'>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['make']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['model']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['serial_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['mechanic_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['mechanic_contact']) . "</td>";
                            echo "<td><button class='btn btn-sm btn-success' onclick='openUpdateModal(" . htmlspecialchars($row['id']) . ")'>Update</button></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No tech tool details found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Add Tech Tool Modal -->
    <div class="modal fade" id="techtoolAddModal" tabindex="-1" aria-labelledby="techtoolAddLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-70">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="techtoolAddLabel">Add Tech Tool</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="techtoolAddForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="tech_tool_name" class="form-label">Tech Tool Make</label>
                                    <select class="form-control" id="tech_tool_make" name="tech_tool_make">
                                        <option value="" selected disabled>Select Make</option>
                                        <option value="Tata">Tata</option>
                                        <option value="Tata-Midi">Tata-Midi</option>
                                        <option value="Leyland">Leyland</option>
                                        <option value="Eicher">Eicher</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="tech_tool_model" class="form-label">Tech Tool Model</label>
                                    <select class="form-control" id="tech_tool_model" name="tech_tool_model">
                                        <option value="" selected disabled>Select Model</option>
                                        <option value="BS-3">BS-3</option>
                                        <option value="BS-4">BS-4</option>
                                        <option value="BS-6">BS-6</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="tool_serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="tool_serial_number" name="tool_serial_number" oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase();">
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="tool_status" class="form-label">Status</label>
                                    <select class="form-control" id="tool_status" name="tool_status">
                                        <option value="" selected disabled>Select Status</option>
                                        <option value="Working">Working</option>
                                        <option value="Not Working">Not Working</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="tool_mechanic_name">Tool Mechanic Name</label>
                                    <input type="text" class="form-control" id="tool_mechanic_name" name="tool_mechanic_name" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g,'').toUpperCase();">
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="tool_mechanic_contact">Tool Mechanic Contact</label>
                                    <input type="text" class="form-control" id="tool_mechanic_contact" name="tool_mechanic_contact" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,10);">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Tool</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <div class="modal fade" id="techtoolUpdateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-70">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Tech Tool</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="techtoolUpdateForm">
                    <div class="modal-body">

                        <input type="hidden" id="update_id" name="id">
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label">Tool Make</label>
                                    <Select class="form-control" id="update_tech_tool_make">
                                        <option value="" selected disabled>Select Make</option>
                                        <option value="Tata">Tata</option>
                                        <option value="Tata-Midi">Tata-Midi</option>
                                        <option value="Leyland">Leyland</option>
                                        <option value="Eicher">Eicher</option>
                                    </Select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label">Tool Model</label>
                                    <select class="form-control" id="update_tech_tool_model">
                                        <option value="" selected disabled>Select Model</option>
                                        <option value="BS-3">BS-3</option>
                                        <option value="BS-4">BS-4</option>
                                        <option value="BS-6">BS-6</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="update_tool_serial_number" oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase();">
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-control" id="update_tool_status">
                                        <option value="Working">Working</option>
                                        <option value="Not Working">Not Working</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label">Mechanic Name</label>
                                    <input type="text" class="form-control" id="update_tool_mechanic_name" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g,'').toUpperCase();">
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label">Mechanic Contact</label>
                                    <input type="text" class="form-control" id="update_tool_mechanic_contact" oninput="this.value = this.value.replace(/[^0-9]/g,'');" maxlength="10">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        // Handle form submission
        document.getElementById('techtoolAddForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent normal submit

            let missing = [];

            let tech_tool_make = document.getElementById('tech_tool_make').value.trim();
            let tech_tool_model = document.getElementById('tech_tool_model').value.trim();
            let tool_serial_number = document.getElementById('tool_serial_number').value.trim();
            let tool_status = document.getElementById('tool_status').value.trim();
            let tool_mechanic_name = document.getElementById('tool_mechanic_name').value.trim();
            let tool_mechanic_contact = document.getElementById('tool_mechanic_contact').value.trim();

            if (!tech_tool_make) missing.push("Tech Tool Make");
            if (!tech_tool_model) missing.push("Tech Tool Model");
            if (!tool_serial_number) missing.push("Tool Serial Number");
            if (!tool_status) missing.push("Tool Status");
            if (!tool_mechanic_name) missing.push("Mechanic Name");
            if (!tool_mechanic_contact) missing.push("Mechanic Contact");

            // If any missing fields show sweetalert
            if (missing.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Fields',
                    html: "<b>Please fill the following fields:</b><br><br>• " + missing.join("<br>• "),
                    confirmButtonColor: "#3085d6"
                });
                return;
            }
            //validate contact number length
            if (tool_mechanic_contact.length != 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Contact Number',
                    text: 'Mechanic Contact number must be exactly 10 digits long.',
                    confirmButtonColor: "#3085d6"
                });
                return;
            }
            //if first number is less then 6 then invalid number
            if (parseInt(tool_mechanic_contact.charAt(0)) < 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Contact Number',
                    text: 'Invalid Mechanic Contact number. Please enter a valid 10-digit mobile number.',
                    confirmButtonColor: "#3085d6"
                });
                return;
            }

            // If all fields filled, call ajax
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "addtechtooldetails",
                    tech_tool_make: tech_tool_make,
                    tech_tool_model: tech_tool_model,
                    tool_serial_number: tool_serial_number,
                    tool_status: tool_status,
                    tool_mechanic_name: tool_mechanic_name,
                    tool_mechanic_contact: tool_mechanic_contact
                },
                success: function(response) {
                    let res = JSON.parse(response);

                    // If backend returns error
                    if (res.status === "error") {
                        if (res.missing_fields) {
                            Swal.fire({
                                icon: "warning",
                                title: "Missing Fields",
                                html: "<b>Please fill the following fields:</b><br><br>• " + res.missing_fields.join("<br>• ")
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: res.message
                            });
                        }
                        return; // stop success flow
                    }

                    // If status = success
                    Swal.fire({
                        icon: "success",
                        title: "Saved!",
                        text: "Tech tool details added successfully."
                    }).then(() => {
                        location.reload(); // Reload page to show new data
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: "Something went wrong while saving data!"
                    });
                }
            });

        });

        function openUpdateModal(id) {
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "get_tech_tool_details",
                    id: id
                },
                success: function(response) {
                    let res = JSON.parse(response);

                    if (res.status === "success") {
                        $("#update_id").val(res.data.id);
                        $("#update_tech_tool_make").val(res.data.make);
                        $("#update_tech_tool_model").val(res.data.model);
                        $("#update_tool_serial_number").val(res.data.serial_number);
                        $("#update_tool_status").val(res.data.status);
                        $("#update_tool_mechanic_name").val(res.data.mechanic_name);
                        $("#update_tool_mechanic_contact").val(res.data.mechanic_contact);

                        let modal = new bootstrap.Modal(document.getElementById('techtoolUpdateModal'));
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error!'
                    });
                }
            });
        }
        $("#techtoolUpdateForm").on("submit", function(e) {
            e.preventDefault();
            let missing = [];
            let update_id = $("#update_id").val().trim();
            let update_tech_tool_make = $("#update_tech_tool_make").val().trim();
            let update_tech_tool_model = $("#update_tech_tool_model").val().trim();
            let update_tool_serial_number = $("#update_tool_serial_number").val().trim();
            let update_tool_status = $("#update_tool_status").val().trim();
            let update_tool_mechanic_name = $("#update_tool_mechanic_name").val().trim();
            let update_tool_mechanic_contact = $("#update_tool_mechanic_contact").val().trim();

            if (!update_id) missing.push("Tech Tool ID");
            if (!update_tech_tool_make) missing.push("Tech Tool Make");
            if (!update_tech_tool_model) missing.push("Tech Tool Model");
            if (!update_tool_serial_number) missing.push("Tool Serial Number");
            if (!update_tool_status) missing.push("Tool Status");
            if (!update_tool_mechanic_name) missing.push("Mechanic Name");
            if (!update_tool_mechanic_contact) missing.push("Mechanic Contact");
            // If any missing fields show sweetalert
            if (missing.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Fields',
                    html: "<b>Please fill the following fields:</b><br><br>• " + missing.join("<br>• "),
                    confirmButtonColor: "#3085d6"
                });
                return;
            }
            //validate contact number length
            if (update_tool_mechanic_contact.length != 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Contact Number',
                    text: 'Mechanic Contact number must be exactly 10 digits long.',
                    confirmButtonColor: "#3085d6"
                });
                return;
            }
            //if first number is less then 6 then invalid number
            if (parseInt(update_tool_mechanic_contact.charAt(0)) < 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Contact Number',
                    text: 'Invalid Mechanic Contact number. Please enter a valid 10-digit mobile number.',
                    confirmButtonColor: "#3085d6"
                });
                return;
            }

            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "update_tech_tool_details",
                    id: update_id,
                    tech_tool_make: update_tech_tool_make,
                    tech_tool_model: update_tech_tool_model,
                    tool_serial_number: update_tool_serial_number,
                    tool_status: update_tool_status,
                    tool_mechanic_name: update_tool_mechanic_name,
                    tool_mechanic_contact: update_tool_mechanic_contact

                },
                success: function(response) {
                    let res = JSON.parse(response);

                    // If backend returns error
                    if (res.status === "error") {
                        if (res.missing_fields) {
                            Swal.fire({
                                icon: "warning",
                                title: "Missing Fields",
                                html: "<b>Please fill the following fields:</b><br><br>• " + res.missing_fields.join("<br>• ")
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: res.message
                            });
                        }
                        return; // stop success flow
                    }

                    // If status = success
                    Swal.fire({
                        icon: "success",
                        title: "Saved!",
                        text: "Tech tool details added successfully."
                    }).then(() => {
                        location.reload(); // Reload page to show new data
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: "Something went wrong while saving data!"
                    });
                }
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