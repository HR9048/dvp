<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

// Check user role
if ($_SESSION['TYPE'] == 'DIVISION' || $_SESSION['JOB_TITLE'] == 'ASO(Stat)') {

?>
<div class="container" style="max-width: 500px;">
    <h2>Upload PDF Report</h2>
    <form id="uploadForm" enctype="multipart/form-data" >
        <input type="hidden" name="action" value="operationalstatisticsupload">
        <div class="form-group">
            <label for="selected_date">Select Date:</label>
            <input type="date" id="selected_date" name="selected_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="depot_id">Select Depot:</label>
            <select id="depot_id" name="depot_id" class="form-control" required>
                <option value="">Select Depot</option>
                <?php
                $query = "SELECT depot_id, depot FROM location WHERE division_id = '" . $_SESSION['DIVISION_ID'] . "' and depot != 'DIVISION'";
                $result = mysqli_query($db, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<option value='{$row['depot_id']}'>{$row['depot']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="pdf_file">Upload PDF (Max: 1MB):</label>
            <input type="file" id="pdf_file" name="pdf_file" class="form-control" accept="application/pdf" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
</div>

<script>
$(document).ready(function() {
    $("#uploadForm").on("submit", function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append("action", "operationalstatisticsupload");

        $.ajax({
            url: "../includes/backend_data.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                var res = JSON.parse(response);
                if (res.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: res.message
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: res.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Something went wrong. Please try again!"
                });
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