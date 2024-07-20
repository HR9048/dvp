<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

// Fetch user type and handle session
$query = 'SELECT ID, t.TYPE FROM users u JOIN type t ON t.TYPE_ID = u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'];
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DEPOT') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to Depot Page');
                window.location = '../includes/depot_verify.php';
              </script>";
    } elseif ($Aa == 'HEAD-OFFICE') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to Division Page');
                window.location = 'index.php';
              </script>";
    } elseif ($Aa == 'RWY') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to RWY Page');
                window.location = 'rwy.php';
              </script>";
    } elseif ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CO_STORE') {
        echo "<script type='text/javascript'>
                alert('Restricted Page! You will be redirected to Stores Page');
                window.location = 'index.php';
              </script>";
    }
}

// Get today's and yesterday's dates
$todayDate = date('Y-m-d');
$yesterdayDate = date('Y-m-d', strtotime("-1 days"));

// Get the division ID from the session
$divisionId = $_SESSION['DIVISION_ID'];
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
                <th>Depot</th>
                <th>DVP</th>
                <th>KMPL</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Join location table to get depot names, ordered by depot_id
            $depotQuery = "SELECT DISTINCT loc.depot, loc.depot_id AS depotID
                           FROM dvp_data d 
                           INNER JOIN location loc ON d.depot = loc.depot_id 
                           WHERE d.division = '$divisionId' 
                           ORDER BY loc.depot_id";
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

                // Set color and font weight based on data presence
                $dataPresentColor = ($dataPresent === "Yes") ? "green" : "red";
                $dataPresentFontWeight = ($dataPresent === "Yes") ? "bold" : "normal";

                // Fetch KMPL data for yesterday's date for the current division and depot
                $kmplQuery = "SELECT COUNT(*) AS count 
                              FROM kmpl_data k
                              INNER JOIN location loc ON k.depot = loc.depot_id 
                              WHERE k.division = '$divisionId' AND loc.depot = '$depotName' AND date = '$yesterdayDate'";
                $kmplResult = mysqli_query($db, $kmplQuery) or die(mysqli_error($db));
                $kmplRow = mysqli_fetch_assoc($kmplResult);
                $kmplData = ($kmplRow['count'] > 0) ? "Yes" : "No";

                // Set color and font weight based on KMPL data presence
                $kmplColor = ($kmplData === "Yes") ? "green" : "red";
                $kmplFontWeight = ($kmplData === "Yes") ? "bold" : "normal";
                ?>
                <tr>
                    <td><?php echo $serialNumber; ?></td>
                    <td><?php echo $depotName; ?></td>
                    <td style="color: <?php echo $dataPresentColor; ?>; font-weight: <?php echo $dataPresentFontWeight; ?>">
                        <?php echo $dataPresent; ?></td>
                    <td style="color: <?php echo $kmplColor; ?>; font-weight: <?php echo $kmplFontWeight; ?>">
                        <?php echo $kmplData; ?></td>
                </tr>
                <?php

                // Increment the serial number
                $serialNumber++;
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
include '../includes/footer.php';
?>
