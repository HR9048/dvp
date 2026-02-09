<?php include 'ad_nav.php'; ?>

<div class="container mt-4">
    <h3 class="text-center">Vehicle Program Details</h3>

    <!-- ✅ Center Form -->
    <form id="vehicleForm" class="text-center mt-4">

        <label class="fw-bold">Enter Vehicle Number:</label>

        <!-- ✅ Small Center Input -->
        <div class="d-flex justify-content-center">
            <input type="text"
                name="vehicle_no"
                id="vehicle_no"
                class="form-control w-25 text-center" oninput="this.value = this.value.toUpperCase();"
                required>
        </div>

        <small class="text-muted d-block mt-1">
            Format: KA01F1234 (KA--F----)
        </small>

        <br>

        <!-- ✅ Center Button -->
        <button type="submit" class="btn btn-primary px-4">
            Submit
        </button>

    </form>

    <hr>

    <!-- Output Result -->
    <div id="resultBox"></div>
</div>



<script>
    $(document).ready(function() {

        // ✅ Vehicle Search Form Submit
        $(document).on("submit", "#vehicleForm", function(e) {
            e.preventDefault();

            let vehicleNo = $("#vehicle_no").val().trim();

            let pattern = /^KA[0-9]{2}[A-Z]{1}[0-9]{4}$/;

            if (!pattern.test(vehicleNo)) {
                alert("Invalid Vehicle Number Format! Example: KA01F1234");
                return;
            }

            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: {
                    action: "fetchVehicleProgramdataforedit",
                    vehicle_no: vehicleNo
                },
                success: function(response) {
                    $("#resultBox").html(response);
                }
            });

        });


        // ✅ Edit Button Click Event (Dynamic AJAX Content)
        $(document).on("click", ".edit-program-btn", function() {

            // Get values from button data attributes
            let programId = $(this).data("id");
            let programDate = $(this).data("program_date");
            let vehicleNo = $(this).data("vehicle_no");
            let programType = $(this).data("program_type");
            let division_id = $(this).data("division_id");
            let depot_id = $(this).data("depot_id");

            // Set modal input fields
            $("#programId").val(programId);
            $("#currentProgramDate").val(programDate);
            $("#newProgramDate").val(programDate);
            $("#vehicleNo").val(vehicleNo);
            $("#programType").val(programType);
            $("#division_id").val(division_id);
            $("#depot_id").val(depot_id);
            // ✅ Open Modal
            $("#editModal").modal("show");
        });




    });
</script>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Program Done Date</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProgramDateForm">
                    <input type="hidden" id="programId" name="programId">
                    <input type="hidden" id="programType" name="programType">
                    <input type="hidden" id="division_id" name="division_id">
                    <input type="hidden" id="depot_id" name="depot_id">
                    <div class="mb-3">
                        <label for="vehicleNo" class="form-label">Vehicle Number:</label>
                        <input type="text" class="form-control" id="vehicleNo" name="vehicleNo" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="currentProgramDate" class="form-label">Current Program Done Date:</label>
                        <input type="date" class="form-control" id="currentProgramDate" name="currentProgramDate" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="newProgramDate" class="form-label">New Program Done Date:</label>
                        <input type="date" class="form-control" id="newProgramDate" name="newProgramDate" required>
                    </div>
                    <button class="btn btn-primary" onclick="submitEditProgramData()">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function submitEditProgramData() {
        event.preventDefault();

        var bus_number = $('#vehicleNo').val();
        var program_type = $('#programType').val();
        var program_date = $('#newProgramDate').val();
        var reason = $('#reason').val();
        var rowId = $('#programId').val();
        var division_id = $('#division_id').val();
        var depot_id = $('#depot_id').val();
        const $submitBtn = $("#emergencyProgramForm button[type='submit']");

        const format_program_date = new Date(program_date).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });

        if (!rowId || !bus_number || !program_type || !program_date || !division_id || !depot_id) {
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

        // Disable the submit button
        $submitBtn.prop("disabled", true).text("Submitting...");

        // Step 1: Fetch program KM via AJAX
        $.ajax({
            url: "../includes/backend_data.php",
            method: "POST",
            data: {
                action: "get_program_km_for_bus_admin",
                bus_number: bus_number,
                program_type: program_type,
                program_date: program_date,
                rowId: rowId,
                division_id: division_id,
                depot_id: depot_id
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
                            dataType: "json", // ✅ IMPORTANT
                            data: {
                                action: "save_program_data_admin",
                                bus_number: bus_number,
                                program_type: program_type,
                                program_completed_km: program_completed_km,
                                program_date: program_date,
                                rowId: rowId
                            },
                            success: function(response) {

                                if (response.success) {
                                    Swal.fire("Success", response.message, "success").then(() => {

                                        // ✅ Close Modal
                                        $("#editModal").modal("hide");

                                        // ✅ Reload only table (Better than full reload)
                                        $("#vehicleForm").submit();
                                    });

                                } else {
                                    Swal.fire("Error", response.message, "error");
                                }
                            },
                            error: function(xhr, status, error) {

                                Swal.fire("Error", "AJAX Error: " + error, "error");
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

    }
    // ✅ Delete Button Click Event
    $(document).on("click", ".delete-program-btn", function() {

        let programId = $(this).data("id");
        let busNumber = $(this).data("bus_number");
        let programType = $(this).data("program_type");
        let programDate = $(this).data("program_date");

        // Format Program Type for Display
        let formattedProgram = programType.replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());

        let formattedDate = new Date(programDate);
        programDate = formattedDate.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });

        Swal.fire({
            title: "Confirm Delete?",
            html: `
            <b>Bus Number:</b> ${busNumber} <br>
            <b>Program Type:</b> ${formattedProgram} <br>
            <b>Program Date:</b> ${programDate} <br><br>
            This record will be permanently deleted!
        `,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel",
            confirmButtonColor: "#d33"
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    dataType: "json", // ✅ IMPORTANT
                    data: {
                        action: "deleteProgramData",
                        id: programId
                    },
                    success: function(response) {

                        if (response.success) {

                            Swal.fire("Deleted!", response.message, "success").then(() => {
                                // ✅ Reload Table Automatically
                                $("#vehicleForm").submit();
                            });

                        } else {
                            Swal.fire("Error", response.message, "error");
                        }

                    },
                    error: function(xhr, status, error) {
                        Swal.fire("Error", "AJAX Error: " + error, "error");
                    }
                });


            }
        });

    });
</script>
<?php include 'ad_footer.php'; ?>