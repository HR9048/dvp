<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE']) || !isset($_SESSION['DEPOT_ID']) || !isset($_SESSION['DIVISION_ID']) || !isset($_SESSION['KMPL_DEPOT']) || !isset($_SESSION['KMPL_DIVISION'])) {
    echo "<script type='text/javascript'>alert('Session Expired! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tokenNumber'])) {
        $tokenNumber = $_POST['tokenNumber'];
        $name = $_POST['name'];
        $designation = $_POST['designation'];
        $divisionId = $_SESSION['DIVISION_ID']; // Get division ID from session
        $depotId = $_SESSION['DEPOT_ID'];       // Get depot ID from session
        
        // Query to find the maximum existing PF number for the given division and depot
        $sql = "SELECT MAX(CAST(SUBSTRING(EMP_PF_NUMBER, LENGTH(EMP_PF_NUMBER) - 3, 4) AS UNSIGNED)) AS max_pf
                FROM private_employee
                WHERE EMP_PF_NUMBER LIKE 'P{$divisionId}{$depotId}%'";
        
        $result = $db->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            $maxPf = $row['max_pf'] ? $row['max_pf'] : 0; // Get the max number or default to 0
            $newPfNumber = str_pad($maxPf + 1, 4, '0', STR_PAD_LEFT); // Increment and pad to 4 digits
        } else {
            $newPfNumber = '0001'; // Default to 0001 if no PF found
        }
        
        // Generate the full PF number
        $pfNumber = 'P' . $divisionId . $depotId . $newPfNumber;
        // Check if the PF number already exists in the private_employee table
        $checkQuery = "SELECT COUNT(*) FROM private_employee WHERE EMP_PF_NUMBER = ?";
        $stmtCheck = $db->prepare($checkQuery);
        $stmtCheck->bind_param("s", $pfNumber);
        $stmtCheck->execute();
        $stmtCheck->bind_result($count);
        $stmtCheck->fetch();
        $stmtCheck->close();

        // If the PF number exists in the local table, alert the user
        if ($count > 0) {
            echo "<script type='text/javascript'>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'PF Number already exists in the private employee Details. Please use a unique PF Number.',
                confirmButtonText: 'Go Back'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
          </script>";

            exit; // Stop further execution
        }

        // Step 1: Fetch all divisions and depots from the location table
        $locationQuery = "SELECT kmpl_division, kmpl_depot FROM location";
        $result = $db->query($locationQuery);

        if ($result->num_rows > 0) {
            $divisionsDepots = [];
            while ($row = $result->fetch_assoc()) {
                $divisionsDepots[] = $row;
            }

            // Step 2: Use cURL multi to check the PF number against all divisions and depots
            $multiHandle = curl_multi_init();
            $curlHandles = [];

            foreach ($divisionsDepots as $location) {
                $division = $location['kmpl_division'];
                $depot = $location['kmpl_depot'];
                $url = 'http://localhost/data.php?division=' . urlencode($division) . '&depot=' . urlencode($depot);

                $curlHandle = curl_init($url);
                curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
                $curlHandles[] = $curlHandle;
                curl_multi_add_handle($multiHandle, $curlHandle);
            }

            // Execute all queries simultaneously
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
            } while ($running > 0);

            // Step 3: Check responses for PF number
            $pfFound = false;

            foreach ($curlHandles as $handle) {
                $response = curl_multi_getcontent($handle);
                $data = json_decode($response, true);

                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $employee) {
                        if ($employee['EMP_PF_NUMBER'] === $pfNumber) {
                            $pfFound = true; // PF number found
                            break 2; // Break out of both loops
                        }
                    }
                }
                curl_multi_remove_handle($multiHandle, $handle);
                curl_close($handle);
            }

            // Close the multi handle
            curl_multi_close($multiHandle);

            // Alert if PF number is found in the API
            if ($pfFound) {
                echo "<script type='text/javascript'>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'PF Number already exists in the LMS Database. Please use a unique PF Number.',
            confirmButtonText: 'Go Back'
        }).then((result) => {
            if (result.isConfirmed) {
                window.history.back();
            }
        });
      </script>";

                exit; // Stop further execution
            }
        }

        // Prepare the SQL insert statement for the new employee
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
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Private Employee details submitted successfully.'
            }).then(() => {
                window.location.href = window.location.href; // Forces a hard refresh
            });
          </script>";
            } else {
                echo "<script type='text/javascript'>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error submitting details: " . $stmt->error . "'
            });
          </script>";
            }

            // Close the statement
            $stmt->close();
        } else {
            echo "<script type='text/javascript'>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error preparing statement: " . $db->error . "'
        });
      </script>";
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
                        </select>
                        <div class="invalid-feedback">Designation is required.</div>
                    </div>
                </div>
                <!--<div class="col">
                    <div class="form-group">
                        <label for="pfNumber">PF Number:</label>
                        <input type="text" class="form-control" id="pfNumber" name="pfNumber" required
                            style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" readonly>
                        <div class="invalid-feedback">PF Number is required and must be numeric or charectors.</div>
                    </div>
                </div>-->
            </div>
            


            <button type="button" class="btn btn-primary" onclick="validateForm()">Submit</button>
            <!-- Updated button type -->
        </form>
    </div>
    <script>
        function validateForm() {
            var form = document.getElementById('detailsForm');
            var tokenNumber = document.getElementById('tokenNumber').value;
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