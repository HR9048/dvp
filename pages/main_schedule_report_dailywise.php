<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    ?>
    <style>
        .hide {
            display: none;
        }

        th,
        td {
            border: 1px solid black;
            text-align: left;
            font-size: 16px;
            padding: 5px !important;
            /* Add padding to table cells */
        }

        th {
            background-color: #f2f2f2;
        }

        .dataTable th,
        .dataTable td {
            padding: 1px !important;
            /* Override DataTables' default padding */
        }

        .btn {
            padding-top: 0px;
            padding-bottom: 0px;
        }
    </style>
    <form id="dateForm">
        <label for="date">Select Date:</label>
        <input type="date" id="date" name="date" required max="<?php echo date('Y-m-d'); ?>">
       
        <label for="division">Select Division:</label>
            <select  id="division" name="division" required>
                <option value="">Select Division</option>
            </select>
        <label for="depot">Select Depot:</label>
            <select  id="depot" name="depot" required>
                <option value="">Select Depot</option>
            </select>
        <button class="btn btn-primary" type="submit">Generate Report</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>
    </form>




    <div class="container1">
        <div id="reportTable"></div>
    </div>
    <script>
        document.getElementById('dateForm').addEventListener('submit', function (e) {
            e.preventDefault();

            var selectedDate = document.getElementById('date').value;
            var divisionId = document.getElementById('division').value // Division ID from session
            var depotId = document.getElementById('depot').value;       // Depot selected from the form

            fetch('../database/fetch_depotwise_crew_report_d.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    date: selectedDate,
                    division: divisionId,
                    depot: depotId
                })
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw new Error(text); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.html.includes('Error')) {
                        throw new Error('Server Error: ' + data.html);
                    }
                    document.getElementById('reportTable').innerHTML = data.html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('reportTable').innerHTML = 'An error occurred while fetching the report.';
                });
        });

        function fetchBusCategory() {


            // Fetch bus categories on page load
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchDivision' },
                success: function (response) {
                    var divisions = JSON.parse(response);
                    $.each(divisions, function (index, division) {
                        // Exclude divisions named "HEAD-OFFICE" or "RWY"
                        if (division.DIVISION !== 'HEAD-OFFICE' && division.DIVISION !== 'RWY') {
                            $('#division').append('<option value="' + division.division_id + '">' + division.DIVISION + '</option>');
                        }
                    });
                }
            });




            $('#division').change(function () {
                var Division = $(this).val();
                $.ajax({
                    url: '../includes/data_fetch.php?action=fetchDepot',
                    method: 'POST',
                    data: { division: Division },
                    success: function (data) {
                        // Assuming `data` is the HTML string containing <option> elements
                        var $data = $(data);
                        var filteredData = $data.filter(function () {
                            return $(this).val().toUpperCase() !== 'DIVISION';
                        });
                        $('#depot').html(filteredData);
                    }
                });
            });

        }


        // Call the functions to fetch data on page load
        $(document).ready(function () {
            fetchBusCategory();

        });
    </script>


    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>