<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && ($_SESSION['JOB_TITLE'] == 'CME_CO')) {
?>

    <h2>Bus kmpl Report</h2>
    <form id="dateForm">
        <label for="selected_date">Select Date:</label>
        <input type="date" id="selected_date" name="selected_date" required>
        <button type="submit">Generate Report</button>
    </form>

    <div id="tableContainer">
        <!-- The generated table will be inserted here -->
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $("#dateForm").submit(function (e) {
                e.preventDefault(); // Prevent form submission

                var selected_date = $("#selected_date").val();
                if (selected_date === "") {
                    alert("Please select a date");
                    return;
                }

                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: { action: "fetchvehiclekmpldataentereddetails", selected_date: selected_date },
                    success: function (response) {
                        $("#tableContainer").html(response); // Insert the table HTML
                    },
                    error: function () {
                        alert("Error fetching data.");
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
