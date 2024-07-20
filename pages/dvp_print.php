
<?php
error_reporting(E_ERROR | E_PARSE);
include '../includes/connection.php';
include '../includes/depot_sidebar.php';

$query = 'SELECT ID, t.TYPE
            FROM users u
            JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DIVISION') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
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

     .container5,
     .container5 * {
         visibility: visible;
     }

     .container5 {
         width: 75%;
         text-align: right;
         position: absolute;
         top: 0;
         left: 0;
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
        <h2 style="text-align:left; padding: 2%; margin: 0;">
            <?php echo $_SESSION['DIVISION']; ?>
        </h2>
        <h2 style="text-align:center; padding: 2%; margin: 0;">
            <?php echo $_SESSION['DEPOT']; ?>
        </h2>
        <h2 style="text-align:right; padding: 2%; margin: 0;">
            <?php echo $formatted_date; ?>
        </h2>
    </div>


    <?php
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
        'loan' => 'Vehicles loan given to other Depot/Training Center',
        'wup' => 'Vehicles Withdrawn for Fair',
        'Police' => 'Vehicles at Police Station',
        'notdepot' => 'Vehicles Not Arrived to Depot',
        'Dealer' => 'Vehicles Held at Dealer Point',
        'ORTotal' => '<span style="font-weight:bold;">Total Vehicles not Available for Operation</span>',
        'available' => '<span style="font-weight:bold;">Total Vehicles available for Operation',
        'ES' => '<span style="font-weight:bold;">Vehicles Excess/Shortage',
        // Add more custom headings as needed
    );

    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve selected date
        $selectedDate = $_POST['selected_date'];
    }

    // Retrieve username and designation from session data
    $username = $_SESSION['USERNAME'];
    $designation = $_SESSION['JOB_TITLE'];
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];

    // Retrieve data from the database based on session variables and selected date
    $sql = "SELECT  schedules, vehicles, spare, spareP, docking, wup, ORDepot, ORDWS, ORRWY, CC,loan, Police, notdepot, ORTotal, available, ES FROM dvp_data WHERE division = '$division' AND depot = '$depot' AND date = '$selectedDate'";

    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        // Output data in one column
        echo "<table>";
        echo "<tr><th>Particulars</th><th style='text-align:right;'>DVP data</th></tr>";

        // Output data of each row
        $row = $result->fetch_assoc();
        foreach ($row as $column => $value) {
            // Use custom headings if available, otherwise use column name
            $heading = isset($customHeadings[$column]) ? $customHeadings[$column] : $column;
            echo "<tr>";
            echo "<td>$heading</td>";
            echo "<td style='text-align:right;'>$value</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "No results found.";
    }

    $db->close();
    ?>
<br><br><br><br><br>
    <div style="display: flex; justify-content: space-between;">
        <h2 style="text-align:left; padding: 2%; margin: 0;">JA</h2>
        <h2 style="text-align:center; padding: 2%; margin: 0;">CM/AWS</h2>
        <h2 style="text-align:right; padding: 2%; margin: 0;">DM</h2>
    </div>

</div>

<!-- Print button -->
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <a href="dvp_print_pdf.php?selected_date=<?php echo $_POST['selected_date']; ?>" class="btn btn-success">Download PDF</a>
</div>


<?php
include '../includes/footer.php';
?>