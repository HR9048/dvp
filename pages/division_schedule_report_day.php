<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'ASO(Stat)' || $_SESSION['JOB_TITLE'] == 'DC' || $_SESSION['JOB_TITLE'] == 'DTO') {
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

        <!-- Hidden Division Field -->
        <input type="hidden" id="division" name="division" value="<?php echo $_SESSION['DIVISION_ID']; ?>">

        <!-- Depot Dropdown -->
        <label for="depot">Select Depot:</label>
        <select id="depot" name="depot" required>
            <option value="">Select Depot</option>
            <?php
            // Fetch depots from the database based on the session division_id
            $divisionId = $_SESSION['DIVISION_ID'];
            $sql = "SELECT depot_id, depot FROM location WHERE division_id = '$divisionId' AND depot != 'DIVISION'";
            $result = $db->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . $row['depot_id'] . '">' . $row['depot'] . '</option>';
                }
            }
            ?>
        </select>

        <button class="btn btn-primary" type="submit">Generate Report</button>
        <button class="btn btn-success" type="button" onclick="window.print()">Print</button>
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



    </script>


    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>