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

// Get today's date
$todayDate = date('Y-m-d');

// Get yesterday's date
$yesterdayDate = date('Y-m-d', strtotime("-1 days"));

// Define the order of division names
$divisionOrder = array(
    "0" => "HEAD-OFFICE",
    "1" => "KALABURAGI-1",
    "2" => "KALABURAGI-2",
    "3" => "YADAGIRI",
    "4" => "BIDAR",
    "5" => "RAICHURU",
    "6" => "KOPPALA",
    "7" => "BALLARI",
    "8" => "HOSAPETE",
    "9" => "VIJAYAPURA",
);

// Initialize serial number
$serialNumber = 1;

?>
<div class="container1">
    <!-- HTML table to display the fetched data -->
    <h2 style="text-align:center;">DVP Submission <?php echo $todayDate; ?></h2>
    <table>
        <thead>
            <tr>
                <th>Serial No</th>
                <th>Division</th>
                <th>Depot</th>
                <th>DVP</th>
                <th>KMPL</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Loop through the division order and fetch data for each division
            foreach ($divisionOrder as $divisionId => $divisionName) {
                // Join location table to get depot names
                $depotQuery = "SELECT DISTINCT loc.depot , loc.depot_id as depotID
                               FROM dvp_data d 
                               INNER JOIN location loc ON d.depot = loc.depot_id 
                               WHERE d.division = '$divisionId' ORDER BY loc.depot";
                $depotResult = mysqli_query($db, $depotQuery) or die(mysqli_error($db));

                // Fetch the distinct depot names for the current division
                $depotNames = array();
                while ($rowDepot = mysqli_fetch_assoc($depotResult)) {
                    $depotNames[] = $rowDepot['depot'];
                }

                // Display the division name and depot names
                foreach ($depotNames as $depotName) {
                    // Check if data for today's date is present in the database
                    $dataPresentQuery = "SELECT COUNT(*) AS count 
                    FROM dvp_data d 
                    INNER JOIN location loc ON d.depot = loc.depot_id 
                    WHERE d.division = '$divisionId' AND loc.depot = '$depotName' AND date = '$todayDate'";
                   $dataPresentResult = mysqli_query($db, $dataPresentQuery) or die(mysqli_error($db));
                    $dataPresentRow = mysqli_fetch_assoc($dataPresentResult);
                    $dataPresent = ($dataPresentRow['count'] > 0) ? "Yes" : "No";
                    // Set color based on data presence
                    $dataPresentColor = ($dataPresent === "Yes") ? "green" : "red";
                    // Set font weight to bold for "Yes" text
                    $dataPresentFontWeight = ($dataPresent === "Yes") ? "bold" : "normal";

                    // Fetch kmpl data for yesterday's date for the current division and depot
                    $kmplQuery = "SELECT COUNT(*) AS count 
                    FROM kmpl_data k
                    INNER JOIN location loc ON k.depot = loc.depot_id 
                    WHERE k.division = '$divisionId' AND loc.depot = '$depotName' AND date = '$yesterdayDate'";
                   $dataPresentResult = mysqli_query($db, $kmplQuery) or die(mysqli_error($db));
                    $kmplRow = mysqli_fetch_assoc($dataPresentResult);
                    $kmplData = ($kmplRow['count'] > 0) ? "Yes" : "No";
                    // Set color based on data presence
                    $kmplColor = ($kmplData === "Yes") ? "green" : "red";
                    // Set font weight to bold for "Yes" text
                    $kmplFontWeight = ($kmplData === "Yes") ? "bold" : "normal";
                    ?>
                    <tr>
                        <td><?php echo $serialNumber; ?></td>
                        <td class="hidden"><?php echo $divisionName; ?></td>
                        <td><?php echo $depotName; ?></td>
                        <!-- Apply inline style to set text color and font weight for data presence -->
                        <td style="color: <?php echo $dataPresentColor; ?>; font-weight: <?php echo $dataPresentFontWeight; ?>">
                            <?php echo $dataPresent; ?></td>
                        <!-- Apply inline style to set text color and font weight for kmpl data -->
                        <td style="color: <?php echo $kmplColor; ?>; font-weight: <?php echo $kmplFontWeight; ?>">
                            <?php echo $kmplData; ?></td>
                    </tr>
                    <?php

                    // Increment the serial number
                    $serialNumber++;
                }
            }
            ?>
        </tbody>
    </table>
</div>
<!-- Print button -->
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
</div>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>