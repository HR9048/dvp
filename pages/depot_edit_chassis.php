<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page!'); window.location='logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech')) {

    $depot_id = $_SESSION['DEPOT_ID'];
?>

    <div class="container">
        <h2 class="mt-4">Edit Chassis Number</h2>

        <form id="chassisForm">

            <!-- Bus Number -->
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label class="form-label">Bus Number</label>
                        <select class="form-select" id="bus_number" name="bus_number" required>
                            <option value="" disabled selected>Select Bus Number</option>
                            <?php
                            $sql = "SELECT bus_number FROM bus_registration_2025_26 WHERE depot_name='$depot_id'";
                            $result = mysqli_query($db, $sql);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<option value='{$row['bus_number']}'>{$row['bus_number']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                </div>
                <div class="col"></div>
            </div>

            <!-- Old Chassis (Readonly) -->
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label class="form-label">Old Chassis No</label>
                        <input type="text" class="form-control" id="old_chassis" readonly>
                    </div>
                </div>
                <div class="col">
                    <!-- New Chassis -->
                    <div class="mb-3">
                        <label class="form-label">New Chassis No</label>
                        <input type="text" class="form-control" id="new_chassis" name="new_chassis" required oninput="this.value = this.value.toUpperCase();">
                    </div>
                </div>
            </div>




            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>


    <script>
        $(document).ready(function() {

            $('#bus_number').select2({
                placeholder: "🔍 Select Bus Number",
                allowClear: true,
                width: '100%'
            });

        });
        $("#bus_number").on("change", function() {

            let bus = $(this).val();

            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                dataType: "json", // ✅ VERY IMPORTANT
                data: {
                    bus_number: bus,
                    action: 'fetch_chassis_for_edit'
                },
                success: function(res) {

                    if (res.status === "success") {
                        $("#old_chassis").val(res.chassis_number);
                        $("#new_chassis").val(res.chassis_number);
                    } else {
                        $("#old_chassis").val('');
                        $("#new_chassis").val('');
                        alert(res.message);
                    }

                },
                error: function() {
                    alert("Server error while fetching chassis");
                }
            });

        });

        $("#chassisForm").on("submit", function(e) {
            e.preventDefault();

            let bus_number = $("#bus_number").val();
            let new_chassis = $("#new_chassis").val().trim();
            let old_chassis = $("#old_chassis").val().trim();

            if (!bus_number) {
                Swal.fire("Error", "Select Bus Number", "error");
                return;
            }

            if (!new_chassis) {
                Swal.fire("Error", "Enter New Chassis Number", "error");
                return;
            }

            if (new_chassis === old_chassis) {
                Swal.fire("Warning", "New chassis is same as old chassis", "warning");
                return;
            }

            if (new_chassis.length < 10) {
                Swal.fire("Error", "Chassis number must be at least 10 characters", "error");
                return;
            }

            if (new_chassis.length > 20) {
                Swal.fire("Error", "Chassis number must not exceed 20 characters", "error");
                return;
            }

            // ✅ Optional: only alphanumeric (recommended)
            if (!/^[A-Za-z0-9]+$/.test(new_chassis)) {
                Swal.fire("Error", "Chassis must contain only letters and numbers", "error");
                return;
            }

            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                dataType: "json",
                data: {
                    action: "update_chassis_number",
                    bus_number: bus_number,
                    new_chassis: new_chassis
                },
                beforeSend: function() {
                    Swal.fire({
                        title: "Updating...",
                        text: "Please wait",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(res) {

                    if (res.status === "success") {

                        Swal.fire({
                            title: "Success",
                            text: res.message,
                            icon: "success",
                            confirmButtonText: "OK"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload(); // ✅ reload only on click
                            }
                        });

                    } else {
                        Swal.fire("Error", res.message, "error");
                    }

                },
                error: function() {
                    Swal.fire("Error", "Server error", "error");
                }
            });

        });
    </script>

<?php
} else {
    echo "<script>alert('Restricted Page!'); window.location='processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>