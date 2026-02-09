<?php
include 'ad_nav.php';

/* ✅ Fetch Depots */
$sql = "
SELECT division, depot, division_id, depot_id
FROM location
WHERE division_id NOT IN ('0','10')
  AND depot != 'DIVISION'
ORDER BY division_id, depot_id
";

$result = $db->query($sql);


/* ✅ Fetch Existing Restriction Days */
$restrictionData = [];

$rQuery = "
SELECT depot_id, me26_update_days
FROM program_restrictions
";

$rRes = $db->query($rQuery);

while ($row = $rRes->fetch_assoc()) {
    $restrictionData[$row['depot_id']] = $row['me26_update_days'];
}
?>

<div class="container mt-4">
    <h3 class="text-center mb-3">
        Depot ME 26 Restriction Days (Edit Date Only)
    </h3>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Division</th>
                <th>Depot</th>
                <th>Restriction Days (Auto)</th>
                <th>Select Date</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($d = $result->fetch_assoc()):

                $depotId    = $d['depot_id'];
                $divisionId = $d['division_id'];

                /* ✅ Days from DB */
                $days = $restrictionData[$depotId] ?? 0;

                /* ✅ Auto calculate date = Today - Days */
                $dateValue = "";
                if ($days > 0) {
                    $dateValue = date("Y-m-d", strtotime("-$days days"));
                }
            ?>
                <tr>
                    <td><?= $d['division']; ?></td>
                    <td><?= $d['depot']; ?></td>

                    <!-- ✅ Days Readonly -->
                    <td>
                        <input type="number"
                            class="form-control daysField"
                            value="<?= $days; ?>"
                            readonly
                            data-depot="<?= $depotId; ?>">
                    </td>

                    <!-- ✅ Editable Date Input -->
                    <td>
                        <input type="date"
                            class="form-control dateField"
                            value="<?= $dateValue; ?>"
                            data-depot="<?= $depotId; ?>"
                            data-division="<?= $divisionId; ?>">
                    </td>

                    <!-- ✅ Update Button -->
                    <td>
                        <button class="btn btn-primary updateBtn"
                            data-depot="<?= $depotId; ?>"
                            data-division="<?= $divisionId; ?>">
                            Update
                        </button>
                    </td>
                </tr>

            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    /* ✅ When Date Changes → Calculate Days Automatically */
    $(".dateField").on("change", function() {

        let depotId = $(this).data("depot");

        let selectedDate = new Date($(this).val());
        let today = new Date();

        // Calculate difference in days
        let diffTime = today - selectedDate;
        let diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        // Future date not allowed
        if (diffDays < 0) {
            Swal.fire("❌ Invalid!", "Future date not allowed!", "error");
            $(this).val("");
            return;
        }

        // Set calculated days in readonly input
        $(".daysField[data-depot='" + depotId + "']").val(diffDays);
    });


    /* ✅ Update Button Click → Save Days to DB */
    $(".updateBtn").click(function() {

        let depotId = $(this).data("depot");
        let divisionId = $(this).data("division");

        let daysVal = $(".daysField[data-depot='" + depotId + "']").val();

        if (daysVal === "") {
            Swal.fire("❌ Error!", "Please select a date first!", "error");
            return;
        }
        if (isNaN(daysVal) || daysVal < 5) {
            Swal.fire("❌ Error!", "Invalid days value! Days must be at least 5.", "error");
            return;
        }

        $.ajax({
            url: "../includes/backend_data.php",
            type: "POST",
            data: {
                depot_id: depotId,
                division_id: divisionId,
                me26_restriction_days: daysVal,
                action: "update_me26_restriction_days"
            },
            dataType: "json",

            success: function(res) {

                if (res.status === "success") {

                    Swal.fire({
                        title: "✅ Updated!",
                        text: res.message,
                        icon: "success",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.reload();
                    });

                } else {

                    Swal.fire({
                        title: "❌ Error!",
                        text: res.message,
                        icon: "error",
                        confirmButtonText: "OK"
                    });
                }
            },

            error: function() {
                Swal.fire("❌ Server Error!", "Something went wrong!", "error");
            }
        });

    });
</script>

<?php include 'ad_footer.php'; ?>