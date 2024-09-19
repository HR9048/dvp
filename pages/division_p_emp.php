<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME') {
    $division_id=$_SESSION['DIVISION_ID'];
    ?>
    <div class="container">
        <h2 class="text-center">Details Form</h2>
        <form id="detailsForm" onsubmit="return validateForm()">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="tokenNumber">Token Number:</label>
                        <input type="text" class="form-control" id="tokenNumber" name="tokenNumber" required>
                        <div class="invalid-feedback">Token Number is required and must be numeric.</div>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="pfNumber">PF Number:</label>
                        <input type="text" class="form-control" id="pfNumber" name="pfNumber" required>
                        <div class="invalid-feedback">PF Number is required and must be numeric.</div>
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
                        <input type="text" class="form-control" id="designation" name="designation" required>
                        <div class="invalid-feedback">Designation is required.</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="depot">Depot:</label>
                        <select id="depot" name="depot" class="form-control" required>
                            <option value="">Select Depot</option>
                            <option value="Depot1">Depot1</option>
                            <option value="Depot2">Depot2</option>
                            <option value="Depot3">Depot3</option>
                        </select>
                        <div class="invalid-feedback">Please select a depot.</div>
                    </div>
                </div>
                <div class="col"></div>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    <script>
        function validateForm() {
            var form = document.getElementById('detailsForm');
            var tokenNumber = document.getElementById('tokenNumber').value;
            var pfNumber = document.getElementById('pfNumber').value;
            var name = document.getElementById('name').value;
            var designation = document.getElementById('designation').value;
            var depot = document.getElementById('depot').value;

            var isValid = true;

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

            if (depot === "") {
                document.getElementById('depot').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('depot').classList.remove('is-invalid');
            }

            return isValid;
        }
    </script>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>