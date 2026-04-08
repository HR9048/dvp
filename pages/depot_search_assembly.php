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
        <h2 class="mt-4">Search Assemblies</h2>

        <form id="searchForm">

            <!-- Search Field -->
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label class="form-label">Select Assembly Type</label>
                        <select class="form-control" id="assembly_type" name="assembly_type" required>
                            <option value="" disabled selected>Select Assembly Type</option>
                            <option value="alternator">Alternator</option>
                            <option value="battery">Battery</option>
                            <option value="engine">Engine</option>
                            <option value="fip_hpp">Fip Hpp</option>
                            <option value="gear_box">Gear box</option>
                            <option value="rear_axle">Rear-Axle</option>
                            <option value="starter">Starter</option>
                        </select>
                    </div>
                </div>
                <div class="col">

                </div>
            </div>
            <div class="row align-items-center">

                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label">Enter Card Number</label>
                        <input type="text" class="form-control" id="card_number" name="card_number"
                            oninput="this.value = this.value.toUpperCase();">
                    </div>
                </div>

                <!-- OR Text -->
                <div class="col-md-2 text-center">
                    <label class="form-label d-block">&nbsp;</label>
                    <strong>OR</strong>
                </div>

                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label">Enter Serial Number</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number"
                            oninput="this.value = this.value.toUpperCase();">
                    </div>
                </div>

            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <div id="results" class="mt-4"></div>
    </div>

    <script>
        $("#searchForm").on("submit", function(e) {
            e.preventDefault();

            let type = $("#assembly_type").val();
            let serial = $("#card_number").val().trim();
            let assembly = $("#serial_number").val().trim();

            // ✅ Validation (at least one required)
            if (!serial && !assembly) {
                Swal.fire("Error", "Enter Serial OR Assembly Number", "error");
                return;
            }

            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                dataType: "html",
                data: {
                    action: "search_assembly_for_view",
                    assembly_type: type,
                    card_number: serial,
                    serial_number: assembly
                },
                beforeSend: function() {
                    $("#results").html("<div class='text-center'>🔄 Searching...</div>");
                },
                success: function(res) {
                    $("#results").html(res);
                },
                error: function() {
                    $("#results").html("<div class='text-danger'>❌ Error fetching data</div>");
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