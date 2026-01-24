<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>
    <div class="container" style="width: 70%;">
        <form id="emergencyProgramForm">
            <h2>Emergency Program</h2>
            <div class="row">
                <div class="col-md-6 form-group">
                    <div class="form-group">
                        <label for="bus_number">Bus Number:</label>
                        <select name="bus_number" id="bus_number" class="form-control" required>
                            <option value="">Select Bus Number</option>
                            <?php
                            $query = "SELECT bus_number FROM bus_registration WHERE depot_name = ? AND division_name = ?";
                            if ($stmt = $db->prepare($query)) {
                                $stmt->bind_param("ss", $depot_id, $division_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['bus_number']) . "'>" . htmlspecialchars($row['bus_number']) . "</option>";
                                }
                                $stmt->close();
                            } else {
                                echo "<option value=''>Error fetching bus numbers</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <div class="form-group">
                        <label for="program_type">Program Type:</label>
                        <select name="program_type" id="program_type" class="form-control" required>
                            <option value="">Select Program Type</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <div class="form-group">
                        <label for="program_date">Program Date:</label>
                        <input type="date" id="program_date" name="program_date" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <div class="form-group">
                        <label for="reason">Reason for Early Program:</label>
                        <textarea id="reason" name="reason" class="form-control" required></textarea>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>


    <script>
        //add select2 for busnumber and program type
        $(document).ready(function() {
            $('#bus_number').select2({
                placeholder: "Select Bus Number",
                allowClear: true
            });
            $('#program_type').select2({
                placeholder: "Select Program Type",
                allowClear: true
            });
        });
        //on select of bus number, fetch program types from backend_data.php file use ajax and jquery
        $(document).ready(function() {
            $('#bus_number').change(function() {
                var bus_number = $(this).val();
                if (bus_number) {
                    $.ajax({
                        type: 'POST',
                        url: '../includes/backend_data.php',
                        data: {
                            bus_number: bus_number,
                            action: 'program_types_for_emergency_program'
                        },
                        success: function(html) {
                            $('#program_type').html(html);
                        }
                    });
                } else {
                    $('#program_type').html('<option value="">Select Bus Number First</option>');
                }
            });
        });

        // on select of program date, check if the date is not in future and not more than 5 days from today
        $(document).ready(function() {
            $('#program_date').change(function() {
                var program_date = new Date($(this).val());
                var today = new Date();
                var diffTime = Math.abs(today - program_date);
                var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if (program_date > today) {
                    Swal.fire("Not Allowed", "Program date cannot be in the future.", "warning");
                    $(this).val('');
                } else if (diffDays > 5) {
                    Swal.fire("Not Allowed", "Program date must be within the last 4 days.", "warning");
                    $(this).val('');
                }
            });
        });

        // Handle form submission using AJAX first check the km on the selected date from the vehicle_kmpl show the kmpl if the user confirms then submit the form
        $(document).ready(function() {
            $('#emergencyProgramForm').on('submit', function(e) {
                e.preventDefault();
                var bus_number = $('#bus_number').val();
                var program_type = $('#program_type').val();
                var program_date = $('#program_date').val();
                var reason = $('#reason').val();
                const $submitBtn = $("#emergencyProgramForm button[type='submit']");

                const format_program_date = new Date(program_date).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });

                if (!bus_number || !program_type || !program_date || !reason) {
                    Swal.fire("Error", "Please fill all fields.", "error");
                    return;
                }

                //if program date is in feature or less then 30 dats from today show sweet alert error
                const today = new Date();
                const selectedDate = new Date(program_date);
                const diffTime = Math.abs(selectedDate - today);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if (selectedDate > today) {
                    Swal.fire("Error", "Program date cannot be in the future.", "warning");
                    return;
                }
                if (diffDays > 5) {
                    Swal.fire("Error", "Program date must be within the last 5 days.", "warning");
                    return;
                }
                // Disable the submit button
                $submitBtn.prop("disabled", true).text("Submitting...");

                // Step 1: Fetch program KM via AJAX
                $.ajax({
                    url: "../includes/backend_data.php",
                    method: "POST",
                    data: {
                        action: "get_program_km_for_bus",
                        bus_number: bus_number,
                        program_type: program_type,
                        program_date: program_date
                    },
                    dataType: "json",
                    success: function(response) {
                        if (!response.success) {
                            Swal.fire("Error", response.message || "Failed to fetch KM data.", "warning");
                            $submitBtn.prop("disabled", false).text("Submit");
                            return;
                        }

                        const program_completed_km = response.program_km;

                        // Step 2: Show confirmation dialog
                        Swal.fire({
                            title: 'Confirm Update',
                            html: `Do you want to save <strong>${program_completed_km} KM</strong> for <strong>${program_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</strong> on Date: <strong>${format_program_date}</strong> For<br>Bus Number: <strong>${bus_number}</strong>`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Save it!',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Step 3: Submit final data
                                $.ajax({
                                    url: "../includes/backend_data.php",
                                    method: "POST",
                                    data: {
                                        action: "save_program_data",
                                        bus_number: bus_number,
                                        program_type: program_type,
                                        program_completed_km: program_completed_km,
                                        program_date: program_date,
                                        reason: reason
                                    },
                                    success: function(response) {
                                        Swal.fire("Success", response, "success").then(() => {
                                            location.reload();
                                        });
                                    },
                                    error: function() {
                                        Swal.fire("Error", "An error occurred while saving data.", "error");
                                    },
                                    complete: function() {
                                        $submitBtn.prop("disabled", false).text("Submit");
                                    }
                                });
                            } else {
                                $submitBtn.prop("disabled", false).text("Submit");
                            }
                        });
                    },
                    error: function() {
                        Swal.fire("Error", "Failed to fetch program KM.", "error");
                        $submitBtn.prop("disabled", false).text("Submit");
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