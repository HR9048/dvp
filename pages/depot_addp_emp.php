<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE']) || !isset($_SESSION['DEPOT_ID']) || !isset($_SESSION['DIVISION_ID']) || !isset($_SESSION['KMPL_DEPOT']) || !isset($_SESSION['KMPL_DIVISION'])) {
    echo "<script type='text/javascript'>alert('Session Expired! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $tokenNumber = $_POST['tokenNumber'];
        $pfNumber = $_POST['pfNumber'];
        $name = $_POST['name'];
        $designation = $_POST['designation'];

        // Check if the PF number already exists
        $checkQuery = "SELECT COUNT(*) FROM private_employee WHERE EMP_PF_NUMBER = ?";
        $stmtCheck = $db->prepare($checkQuery);
        $stmtCheck->bind_param("s", $pfNumber);
        $stmtCheck->execute();
        $stmtCheck->bind_result($count);
        $stmtCheck->fetch();
        $stmtCheck->close();

        // If the PF number already exists, alert the user
        if ($count > 0) {
            echo "<script type='text/javascript'>
                alert('Error: PF Number already exists. Please use a unique PF Number.');
                window.history.back();
              </script>";
        } else {
            // Prepare the SQL insert statement
            $query = "INSERT INTO private_employee (EMP_PF_NUMBER, Division, Depot, EMP_NAME, EMP_DESGN_AT_APPOINTMENT, token_number, division_id, depot_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            if ($stmt = $db->prepare($query)) {
                // Replace these values with the appropriate division and depot
                $division = $_SESSION['KMPL_DIVISION'];
                $depot = $_SESSION['KMPL_DEPOT'];
                $division_id = $_SESSION['DIVISION_ID'];
                $depot_id = $_SESSION['DEPOT_ID'];

                // Bind parameters
                $stmt->bind_param("ssssssii", $pfNumber, $division, $depot, $name, $designation, $tokenNumber, $division_id, $depot_id);

                // Execute the statement
                if ($stmt->execute()) {
                    echo "<script type='text/javascript'>
                    alert('Employee details submitted successfully.');
                    window.location.href = window.location.href; // Forces a hard refresh
                  </script>";
                } else {
                    echo "<script type='text/javascript'>
                    alert('Error submitting details: " . $stmt->error . "');
                  </script>";
                }

                // Close the statement
                $stmt->close();
            } else {
                echo "<script type='text/javascript'>
                alert('Error preparing statement: " . $db->error . "');
              </script>";
            }
        }
    }

    ?>

    <div class="container"
        style="width:60%; background-color: #fdfdfd; padding: 20px; border: 1px solid #ddd; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); border-radius: 8px; font-family: 'times new roman', serif;">
        <h2 class="text-center">Add Private Employee Details</h2>
        <form id="detailsForm" method="POST" action="">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="tokenNumber">Token Number/DL No:</label>
                        <input type="text" class="form-control" id="tokenNumber" name="tokenNumber" required
                            style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();">
                        <div class="invalid-feedback">Token Number is required and must be numeric or Charector.</div>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="pfNumber">PF Number:</label>
                        <input type="text" class="form-control" id="pfNumber" name="pfNumber" required
                            style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();">
                        <div class="invalid-feedback">PF Number is required and must be numeric or charectors.</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Name is required.</div>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="designation">Designation:</label>
                        <select class="form-control" name="designation" id="designation">
                            <option value="">Select a Designation</option>
                            <option value="DRIVER">Driver</option>
                            <option value="CONDUCTOR">Conductor</option>
                            <option value="DRIVER-CUM-CONDUCTOR">DCC</option>
                        </select>
                        <div class="invalid-feedback">Designation is required.</div>
                    </div>
                </div>
            </div>


            <button type="button" class="btn btn-primary" onclick="validateForm()">Submit</button>
            <!-- Updated button type -->
        </form>
    </div>
    <script>
        function validateForm() {
            var form = document.getElementById('detailsForm');
            var tokenNumber = document.getElementById('tokenNumber').value;
            var pfNumber = document.getElementById('pfNumber').value;
            var name = document.getElementById('name').value;
            var designation = document.getElementById('designation').value;

            var isValid = true;

            // Basic validation checks
            if (tokenNumber.trim() === "" || !/^[a-zA-Z0-9]+$/.test(tokenNumber)) {
                document.getElementById('tokenNumber').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('tokenNumber').classList.remove('is-invalid');
            }

            if (pfNumber.trim() === "" || !/^[a-zA-Z0-9]+$/.test(pfNumber)) {
                document.getElementById('pfNumber').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('pfNumber').classList.remove('is-invalid');
            }

            if (name.trim() === "") {
                document.getElementById('name').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('name').classList.remove('is-invalid');
            }

            if (designation.trim() === "") {
                document.getElementById('designation').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('designation').classList.remove('is-invalid');
            }

            if (isValid) {
                // If the form is valid, populate the modal with data
                document.getElementById('confirmTokenNumber').textContent = tokenNumber;
                document.getElementById('confirmPfNumber').textContent = pfNumber;
                document.getElementById('confirmName').textContent = name;
                document.getElementById('confirmDesignation').textContent = designation;

                // Show the confirmation modal
                var confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                confirmationModal.show();
            }

            // Prevent form from submitting immediately
            return false;
        }

        function submitForm() {
            // Use AJAX to submit form data without reloading the page
            var formData = new FormData(document.getElementById('detailsForm'));

            fetch('', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.text())
                .then(data => {
                    alert('Employee details submitted successfully.');
                    window.location.href = 'depot_scheduel_actinact.php'; // Redirect after successful submission
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to submit the form.');
                });
        }
    </script>


    <!-- Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Details</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Please verify the entered details:</p>
                    <ul>
                        <li><strong>Token Number/DL No:</strong> <span id="confirmTokenNumber"></span></li>
                        <li><strong>PF Number:</strong> <span id="confirmPfNumber"></span></li>
                        <li><strong>Name:</strong> <span id="confirmName"></span></li>
                        <li><strong>Designation:</strong> <span id="confirmDesignation"></span></li>
                    </ul>
                    <p>Please confirm to submit the details.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Edit</button>
                    <button type="button" class="btn btn-primary" onclick="submitForm()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function submitForm() {
            // Programmatically submit the form
            document.getElementById('detailsForm').submit();
        }
    </script>


    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>