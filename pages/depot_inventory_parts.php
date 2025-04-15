<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'WM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>
    <style>
        .custom-container {
            width: 50%;
            min-width: 300px;
        }
    </style>
    <div class="container d-flex justify-content-center mt-4">
        <div class="card shadow-lg custom-container">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Add Assembly Details</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="part_name" class="form-label">Select Part:</label>
                    <select id="part_name" name="part_name" class="form-control">
                        <option value="">-- Select Part --</option>
                        <option value="Engine">Engine</option>
                        <option value="gear_box">Gear Box</option>
                        <option value="fip_hpp">FIP/HPP</option>
                        <option value="starter">Starter</option>
                        <option value="alternator">Alternator</option>
                        <option value="rear_axle">Rear Axle</option>
                        <option value="battery">Battery</option>
                        <option value="tyre">Tyre</option>
                    </select>
                </div>

                <!-- Section to display fetched form -->
                <div id="formContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
    <!-- Section to display fetched form -->

    <script>
        $(document).ready(function() {
            $('#part_name').change(function() {

                var part_name = $(this).val();
                if (part_name !== "") {
                    $.ajax({
                        url: "../includes/backend_data.php",
                        type: "POST",
                        data: {
                            action: "inventorypartsdetailsfetch",
                            part_name: part_name
                        },
                        success: function(response) {
                            $("#formContainer").html(response);
                        }
                    });
                } else {
                    $("#formContainer").html('');
                }
            });

        });

        function validateAndFormatInput(inputField) {
            let value = inputField.value;

            // Allow only letters and numbers, remove spaces and special characters
            value = value.replace(/[^a-zA-Z0-9]/g, '');

            // Convert letters to uppercase
            inputField.value = value.toUpperCase();
        }
    </script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>