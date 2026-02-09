<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

// Allow access only for specific roles
if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Bunk' || $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'Mech')) {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>


    <label for="date">Select Date:</label>
    <input type="date" id="date" name="date">

    <input type="hidden" id="division" name="division" value="<?php echo $_SESSION['DIVISION_ID']; ?>">
    <input type="hidden" id="depot" name="depot" value="<?php echo $_SESSION['DEPOT_ID']; ?>">

    <button class="btn btn-primary" id="submit">Submit</button>
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <button class="btn btn-success" id="downloadExcel">Download Excel</button>


    <!-- Loading symbol -->
    <div id="loading" style="display: none; text-align: center; margin-top: 20px;">
        <i class="fa fa-spinner fa-spin fa-3x"></i>
        <p>Fetching Data...</p>
    </div>
    <div class="container1">
        <div id="kmplReportTable"></div>
    </div>
    <?php 
    
    ?>
    <script>
        $(document).ready(function() {
            $('#submit').click(function() {
                var date = $('#date').val();
                var division = $('#division').val();
                var depot = $('#depot').val();

                if (!date) {
                    alert("Please select a date.");
                    return;
                }

                // Show loading symbol and hide previous data
                $("#loading").show();
                $("#kmplReportTable").hide();

                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: {
                        action: "fetch_kmpl_report_day",
                        date: date,
                        division: division,
                        depot: depot
                    },
                    success: function(response) {
                        // Hide loading and show the fetched data
                        $("#loading").hide();
                        $("#kmplReportTable").html(response).show();
                    },
                    error: function() {
                        $("#loading").hide();
                        $("#kmplReportTable").html("<p class='text-danger text-center'>Error fetching data. Please try again.</p>").show();
                    }
                });
            });

            document.getElementById('downloadExcel').addEventListener('click', function() {
                // Get the HTML table element
                var table = document.querySelector('.container1');

                // Convert table to workbook
                var workbook = XLSX.utils.table_to_book(table, {
                    raw: true
                });

                // Get the first worksheet
                var worksheet = workbook.Sheets[workbook.SheetNames[0]];

                // Loop through all cells in the worksheet
                for (var cell in worksheet) {
                    if (worksheet.hasOwnProperty(cell) && cell[0] !== '!') {
                        var cellValue = worksheet[cell].v;

                        // ✅ Detect if it's a date in YYYY-MM-DD format
                        if (/^\d{4}-\d{2}-\d{2}$/.test(cellValue)) {
                            // Reformat to dd-mm-yyyy
                            var parts = cellValue.split("-");
                            var formattedDate = parts[2] + "-" + parts[1] + "-" + parts[0];

                            worksheet[cell].v = formattedDate; // Update cell value
                            worksheet[cell].t = 's'; // Force text format
                        }

                        // ✅ Prevent number conversion for text
                        if (typeof cellValue === 'string' && !isNaN(cellValue)) {
                            worksheet[cell].t = 's'; // Force text type for numeric strings
                        }
                    }
                }

                // Export Excel file with current date in file name
                XLSX.writeFile(workbook, 'kmpl_report_day_wise.xlsx');
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