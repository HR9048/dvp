<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/division_sidebar.php';
include_once 'session.php';
$query = 'SELECT ID, t.TYPE
            FROM users u
            JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DEPOT') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Depot Page");
            window.location = "../includes/depot_verify.php";
        </script>
    <?php } elseif ($Aa == 'HEAD-OFFICE') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Head Office Page");
            window.location = "index.php";
        </script>
    <?php } elseif ($Aa == 'RWY') {
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to RWY Page");
            window.location = "rwy.php";
        </script>
    <?php } 

}

?>
<style>
        @media print {
            body * {
                visibility: hidden;
            }
            .container1, .container1 * {
                visibility: visible;
            }
            .container1 {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
        }
    </style>



<form action="" method="POST" class="form-inline">
    <label for="selected_date" class="mr-2">Select Date:</label>
    <input type="date" id="selected_date" name="selected_date" max="<?php echo date('Y-m-d'); ?>"
        class="form-control mr-2">
    <button class="btn btn-primary" type="submit">Show Data</button>
</form><br>
<?php $formatted_date = date('d/m/Y', strtotime($_POST['selected_date'])); ?>
<div class="container1">
<h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br>
    <div style="display: flex; justify-content: space-between;">
        <h2 style="text-align:left; padding: 2%; margin: 0;">DIVISION:<?php echo $_SESSION['DIVISION']; ?>
        </h2>
        <h2 style="text-align:center; padding: 2%; margin: 0;">DVP</h2>
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


    // Fetch data from the database based on session variables and selected date
    $sql = "SELECT d.*, l.depot AS depotName
            FROM dvp_data d
            INNER JOIN location l ON d.depot = l.depot_id
            WHERE d.division = '{$_SESSION['DIVISION_ID']}' AND d.date = '$selectedDate' order by l.depot_id";

    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        // Output data in multiple columns
        echo "<table>";

        // Calculate total schedules and total spare vehicles
        $totalSchedules = 0;
        $totalSpare = 0;

        // Initialize array to store depot names
        $depotNames = array();

        // Fetch depot names and store them in an array
        while ($row = $result->fetch_assoc()) {
            $depotName = $row['depotName'];
            $depotNames[$depotName] = $depotName;
            // Update total schedules and total spare vehicles
            $totalSchedules += $row['schedules'];
            $totalSpare += $row['spare'];
            $totalORRWY += $row['ORRWY'];
        }

        // Output table headers
        echo "<tr><th style='text-align: left;'>Particulars</th>";
        foreach ($depotNames as $depotName) {
            echo "<th>$depotName</th>";
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
            'loan' => 'Vehicles loan given to other Depot',
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

            // Initialize total for each row
            $total = 0;

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
                $percentage = ($totalSchedules > 0) ? number_format((($totalSpare-$totalORRWY) * 100 / $totalSchedules), 2) : 0;
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
    <br><br><br><br><br>
    <div style="display: flex; justify-content: space-between;">
        <h2 style="text-align:left; padding: 2%; margin: 0;">JA</h2>
        <h2 style="text-align:center; padding: 2%; margin: 0;">DWS</h2>
        <h2 style="text-align:right; padding: 2%; margin: 0;">DME</h2>
    </div>
</div>

<!-- Print button -->
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
</div>
<?php
include '../includes/footer.php';
?>