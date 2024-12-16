<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO' || $_SESSION['JOB_TITLE'] == 'CO_STORE') {
    ?>

    <style>
        @media print {

            /* Set font size to 20px for all elements */
            body {
                font-size: 10px;
            }
        }

        .container1 {
            overflow-x: auto;
            /* Add horizontal scroll for small screens */
        }

        .container1 table {
            width: auto;
            /* Adjust width based on content */
            border-collapse: collapse;
        }

        .container1 th,
        .container1 td {
            border: 2px solid black;
            padding: 8px;
            text-align: left;
            white-space: nowrap;
            /* Prevent text wrapping */
        }

        .container1 th {
            background-color: #f2f2f2;
        }

        .container1 table tr td:last-child,
        .container1 table tr th:last-child {
            min-width: 100px;
            /* Adjust minimum width of the last column */
        }
    </style>



    <?php $formatted_date = date('d/m/Y', strtotime($_POST['selected_date'])); ?>
    <div class="container-fluid">
        <form class="d-flex" action="" method="POST" style="width: 40%; text-align: left;">
            <input type="date" id="selected_date" name="selected_date" max="<?php echo date('Y-m-d'); ?>"
                class="form-control me-2">
            <button class="btn btn-outline-success" style="width: 40%;" type="submit">Show Data</button>
        </form>
    </div>
    <div class="container1">

        <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br>
        <div style="display: flex; justify-content: space-between;">
            <h2 style="text-align:left; padding: 2%; margin: 0;">CENTRAL OFFICE </h2>
            <h2 style="text-align:center; padding: 2%; margin: 0;">
                <?php echo $_SESSION['DEPOT']; ?>
            </h2>
            <h2 style="text-align:right; padding: 2%; margin: 0;">
                <?php echo $formatted_date; ?>
            </h2>
        </div>
        <?php
        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Retrieve selected date
            $selectedDate = $_POST['selected_date'];
        }

        // Retrieve data from the database based on session variables and selected date
        $sql = "SELECT d.*, l.depot
            FROM dvp_data d
            INNER JOIN location l ON d.depot = l.depot_id
            WHERE d.date = '$selectedDate'
            order by l.depot_id";
        $result = $db->query($sql);

        if ($result->num_rows > 0) {
            // Output data in multiple columns
            echo "<table>";
            // Calculate total schedules and total spare vehicles
            $totalSchedules = 0;
            $totalSpare = 0;

            // Fetch depot names and store them in an array
            $depotNames = array();
            while ($row = $result->fetch_assoc()) {
                $depotNames[] = $row['depot'];
                // Update total schedules and total spare vehicles
                $totalSchedules += $row['schedules'];
                $totalSpare += $row['spare'];
                $totalORRWY += $row['ORRWY'];
            }

            // Output table headers
            echo "<tr><th>Particulars</th>";
            foreach ($depotNames as $depotName) {
                echo "<th style='text-align: center;'>$depotName</th>";
            }
            echo "<th>Total</th>"; // Add Total column header
            echo "</tr>";

            // Define custom column headings
            $customHeadings = array(
                'schedules' => 'Number of Schedules',
                'vehicles' => 'Number Of Vehicles(Including RWY)',
                'spare' => 'Number of Spare Vehicles(Including RWY)',
                'spareP' => 'Percentage of Spare Vehicles(Excluding RWY)',
                'docking' => 'Vehicles stopped for Docking',
                'ORDepot' => 'Vehicles Off Road at Depot',
                'ORDWS' => 'Vehicles Off Road at DWS',
                'ORRWY' => 'Vehicles Off Road at RWY',
                'CC' => 'Vehicles Withdrawn for CC',
                'wup1' => 'Vehicles Work Under Progress at Depot',
                'loan' => 'Vehicles loan given to other depot',
                'wup' => 'Vehicles Withdrawn for Fair',
                'Police' => 'Vehicles at Police Station',
                'notdepot' => 'Vehicles Not Arrived to Depot',
                'Dealer' => 'Vehicles Held at Dealer Point',
                'ORTotal' => '<span style="font-weight:bold;">Total Vehicles not Available for Operation</span>',
                'available' => '<span style="font-weight:bold;">Total Vehicles available for Operation</span>',
                'ES' => '<span style="font-weight:bold;">Vehicles Excess/Shortage</span>',
                // Add more custom headings as needed
            );

            // Output data of each row
            foreach ($customHeadings as $column => $heading) {
                echo "<tr>";
                echo "<td>$heading</td>";

                $total = 0; // Initialize total for each row
    
                // Output data for each depot and calculate total
                $result->data_seek(0); // Reset result pointer
                while ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $value) {
                        if ($key === $column) {
                            // Check if the column is 'ORTotal' or 'available' and apply inline style for bold
                            $cellStyle = ($column === 'ORTotal' || $column === 'available' || $column === 'ES') ? 'font-weight:bold;text-align:right;' : 'text-align:right;';
                            echo "<td style='$cellStyle'>$value</td>";
                            $total += $value; // Add value to total
                        }
                    }
                }

                // If column is 'spareP', calculate and output the percentage with two decimal places
                if ($column === 'spareP') {
                    $percentage = ($totalSchedules > 0) ? number_format((($totalSpare - $totalORRWY) * 100 / $totalSchedules), 2) : 0;
                    echo "<td style='font-weight:bold;text-align:right;'>$percentage%</td>";
                } else {
                    // Output total for the row
                    echo "<td style='font-weight:bold;text-align:right;'>$total</td>";
                }

                echo "</tr>";
            }

            echo "</table>";

        } else {
            ?>
            <br>
            <?php
            echo "No results found.";
        }

        $db->close();
        ?>

        <br><br>
        <br><br>
        <h2 style="display: inline-block; width: 24%;">JTO</h2>
        <h2 style="display: inline-block; width: 24%;">DME</h2>
        <h2 style="display: inline-block; width: 24%;">DY CME</h2>
        <h2 style="display: inline-block; width: 24%;">CME</h2>
    </div>

    <!-- Print button -->
    <div class="text-center mt-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>