<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

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
    <?php } elseif ($Aa == 'DIVISION') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Head Office Page");
            window.location = "division.php";
        </script>
    <?php } elseif ($Aa == 'RWY') {
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to RWY Page");
            window.location = "rwy.php";
        </script>
    <?php } elseif ($_SESSION['TYPE'] == 'HEAD-OFFICE') {
    // Check the job title of the user
    if ($_SESSION['JOB_TITLE'] == 'CO_STORE') {
      ?>
      <script type="text/javascript">
        // Redirect to depot_clerk.php if the job title is Clerk
        alert("Restricted Page! You will be redirected to Stores Page");
        window.location = "index.php";
      </script>
      <?php
    }
  }
}

// Fetch all divisions
$divisionQuery = "SELECT DISTINCT l.division AS division_name, k.division as division FROM kmpl_data k JOIN location l ON k.division = l.division_id order by k.division";
$divisionResult = mysqli_query($db, $divisionQuery);

?>

<style>
    #dataEntryModal {
        display: none;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .container,
        .container * {
            visibility: visible;
        }

        .container {
            width: 95%;
            text-align: right;
            position: absolute;
            top: 0;
            left: 0;
        }
    }
</style>
<button class="btn btn-primary"><a href="main_depotwise_kmpl.php" style="color: white;">Depot wise KMPL -></a></button><br><br>
<div class="container">
    <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br>
        <div style="display: flex; justify-content: space-between;">
            <h2 style="text-align:left; padding: 2%; margin: 0;">CENTRAL OFFICE </h2>
            <h2 style="text-align:center; padding: 2%; margin: 0;">
                <?php echo $_SESSION['DEPOT']; ?>
            </h2>
            <h2 style="text-align:right; padding: 2%; margin: 0;">Month: <?php echo date('F'); ?></h2>
        </div>
    <?php
    // Loop through each division
    while ($divisionRow = mysqli_fetch_assoc($divisionResult)) {
        $division = $divisionRow['division_name'];
        $divisionname = $divisionRow['division'];

        ?>
        <div class="row">
            <div class="col-lg-12">
                <!-- Combined Table: Daily Entry and Cumulative KMPL -->
                <table>
                <br>
                    <h5 style="text-align:center;">
                        <?php echo $division; ?> DIVISION KMPL
                    </h5>
                    <thead class="thead-dark">
                    <tr>
                        <th rowspan="2">Date</th>
                        <th colspan="3" style="text-align:center;">DAILY KMPL</th>
                        <th colspan="3" style="text-align:center;">CUMULATIVE KMPL</th>

                    </tr>
                    <tr>
                        <th>Gross KM</th>
                        <th>HSD</th>
                        <th>KMPL</th>
                        <th>Gross KM</th>
                        <th>HSD</th>
                        <th>KMPL</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Fetch data from the database for both daily and cumulative
                    $sql = "SELECT 
                            DATE(date) AS date, division,
                            SUM(total_km) AS total_total_km,
                            SUM(hsd) AS total_hsd
                        FROM kmpl_data
                        WHERE DATE(date) BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND CURDATE()
                            AND division = '$divisionname'
                        GROUP BY DATE(date), division";
                    $result = mysqli_query($db, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Calculate KMPL for daily data
                            $daily_kmpl = ($row['total_hsd'] != 0) ? round($row['total_total_km'] / $row['total_hsd'], 2) : 0;

                            // Fetch cumulative data for the same date
                            $cumulative_sql = "SELECT 
                                                SUM(total_km) AS total_km,
                                                SUM(hsd) AS hsd
                                            FROM kmpl_data
                                            WHERE DATE(date) <= '" . $row['date'] . "' 
                                                AND division = '$divisionname'";
                            $cumulative_result = mysqli_query($db, $cumulative_sql);
                            $cumulative_row = mysqli_fetch_assoc($cumulative_result);

                            // Calculate KMPL for cumulative data
                            $cumulative_kmpl = ($cumulative_row['hsd'] != 0) ? round($cumulative_row['total_km'] / $cumulative_row['hsd'], 2) : 0;

                            // Output combined row
                            echo "<tr>";
                            echo "<td>" . date('d/m/Y', strtotime($row['date'])) . "</td>";
                            echo "<td>" . $row['total_total_km'] . "</td>";
                            echo "<td>" . $row['total_hsd'] . "</td>";
                            echo "<td>" . number_format($daily_kmpl, 2) . "</td>";
                            echo "<td>" . $cumulative_row['total_km'] . "</td>";
                            echo "<td>" . $cumulative_row['hsd'] . "</td>";
                            echo "<td>" . number_format($cumulative_kmpl, 2) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='11'>No data available</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
    }
    ?><br><br>
    <h2 style="display: inline-block; width: 24%;text-align:center;">JTO</h2>
        <h2 style="display: inline-block; width: 24%; text-align:left;">DME</h2>
        <h2 style="display: inline-block; width: 24%; text-align:left;">DY CME</h2>
        <h2 style="display: inline-block; width: 24%; text-align:left; padding-right: 20px;">CME</h2>
</div>
<div class="text-center mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
</div>

<?php include '../includes/footer.php'; ?>

