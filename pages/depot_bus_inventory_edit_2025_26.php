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
    <div class="container mt-4" style="width:80%;">
        <h4>Bus Details Form</h4>
        <form id='updateInventoryForm'>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="bus_number" class="form-label">Select Bus Number</label>
                    <select class="form-control" id="bus_number" name="bus_number" required>
                        <option value="">-- Select Bus Number --</option>
                        <?php
                        if ($_SESSION['TYPE'] == 'DEPOT') {
                            $q = mysqli_query($db, "SELECT bus_number 
FROM (
    -- From bus_registration_2025_26
    SELECT bus_number 
    FROM bus_registration_2025_26 
    WHERE division_name = '$division_id' 
      AND depot_name = '$depot_id'
) AS all_buses
WHERE EXISTS (
    SELECT 1 
    FROM `bus_inventory_2025_26` 
    WHERE `bus_inventory_2025_26`.bus_number = all_buses.bus_number 
      AND inventory_date = '2026-03-31' 
      AND deleted != 1
);");
                        } elseif ($_SESSION['TYPE'] == 'DIVISION') {
                            $q = mysqli_query($db, "SELECT bus_number FROM bus_registration_2025_26 WHERE division_name = '$division_id'  AND EXISTS (SELECT 1 FROM `bus_inventory_2025_26` WHERE `bus_inventory_2025_26`.bus_number = bus_registration_2025_26.bus_number AND inventory_date = '2025-03-31' and deleted !=1);");
                        } elseif ($_SESSION['TYPE'] == 'RWY') {
                            $q = mysqli_query($db, "SELECT bus_number FROM bus_registration_2025_26 WHERE EXISTS (SELECT 1 FROM `bus_inventory_2025_26` WHERE `bus_inventory_2025_26`.bus_number = bus_registration_2025_26.bus_number AND inventory_date = '2025-03-31' and deleted !=1);");
                        }
                        while ($row = mysqli_fetch_assoc($q)) {
                            echo '<option value="' . $row['bus_number'] . '">' . $row['bus_number'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div id="fetcheddata"></div>
        </form>
    </div>

    <script>
        $(document).ready(function() {

            // Initialize Select2
            $('#bus_number').select2({
                placeholder: "-- Select Bus Number --",
                allowClear: true,
                width: '100%'
            });

            // Change event
            $('#bus_number').on('change', function() {

                let busNumber = $(this).val();

                // Clear previous data
                $('#fetcheddata').html('');

                if (!busNumber) return;

                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: {
                        bus_number: busNumber,
                        action: 'fetch_bus_inventory_details_for_edit'
                    },
                    dataType: 'json',

                    beforeSend: function() {
                        $('#fetcheddata').html('<div class="text-center">Loading...</div>');
                    },

                    success: function(response) {

                        if (response.status === 'success') {

                            $('#fetcheddata').html(response.html);

                            // ✅ Apply only to assembly dropdowns
                            $('#fetcheddata select.select2').select2({
                                width: '100%',
                                placeholder: "Search assembly...",
                                allowClear: true
                            });

                        } else {

                            $('#fetcheddata').html(
                                '<div class="alert alert-danger">' + response.message + '</div>'
                            );

                        }
                    },

                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);

                        $('#fetcheddata').html(
                            '<div class="alert alert-danger">Error fetching data</div>'
                        );
                    }
                });

            });

        });
        // submit event for dynamically generated form
        $(document).on("submit", "#updateInventoryForm", function(e) {
            e.preventDefault();

            let form = $(this);

            // 🔥 CONFIRMATION POPUP
            Swal.fire({
                title: "Are you sure?",
                text: "This update is allowed only once. After submission, no further changes will be permitted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Submit",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33"
            }).then((result) => {

                if (!result.isConfirmed) return; // ❌ stop if cancelled

                let formData = form.serialize() + '&action=update_bus_inventory_2025_26';

                // 🔥 Debug log
                let formArray = form.serializeArray();


                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',

                    beforeSend: function() {
                        $('#update_assembly_list')
                            .prop('disabled', true)
                            .text('Updating...');
                    },

                    success: function(response) {

                        $('#update_assembly_list')
                            .prop('disabled', false)
                            .text('Update');

                        if (response.status === 'success') {

                            Swal.fire("Success", response.message, "success")
                                .then(() => {
                                    location.reload();
                                });

                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },

                    error: function(xhr, status, error) {

                        $('#update_assembly_list')
                            .prop('disabled', false)
                            .text('Update');

                        Swal.fire("Error", "AJAX error: " + error, "error");
                    }
                });

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