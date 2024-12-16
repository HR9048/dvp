<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
?>
<style>
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
            right: 0;
        }
    }
</style>

<button class="btn btn-primary"><a href="main_divisionwise_kmpl.php" style="color: white;">Division wise KMPL
        -></a></button>
<div class="container-fluid mt-5">
    <div class="container">
        <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br>
        <div style="display: flex; justify-content: space-between;">
            <h2 style="text-align:left; padding: 2%; margin: 0;">CENTRAL OFFICE </h2>
            <h2 style="text-align:center; padding: 2%; margin: 0;">
                <?php echo $_SESSION['DEPOT']; ?>
            </h2>
            <h2 style="text-align:right; padding: 2%; margin: 0;">Month: <?php echo date('F'); ?></h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Date</th>
                    <th COLspan="3" style="text-align:center;">DAILY KMPL</th>
                    <th COLspan="3" style="text-align:center;">CUMULATIVE KMPL</th>

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
                // Fetch data from the database and populate Table
                if ($db) {
                    // Add condition to filter data based on session division and depot name
                    $sql = "SELECT DATE(date) AS date,
                            SUM(total_km) AS total_total_km,
                            SUM(hsd) AS total_hsd
                            FROM kmpl_data
                            WHERE DATE(date) BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND CURDATE()
                            GROUP BY DATE(date)";
                    $result = mysqli_query($db, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        $cumulative_total_km = 0;
                        $cumulative_hsd = 0;
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Calculate KMPL
                            $daily_kmpl = ($row['total_hsd'] != 0) ? round($row['total_total_km'] / $row['total_hsd'], 2) : 0;

                            echo "<tr>";
                            echo "<td>" . date('d/m/Y', strtotime($row['date'])) . "</td>";
                            echo "<td>" . $row['total_total_km'] . "</td>";
                            echo "<td>" . $row['total_hsd'] . "</td>";
                            echo "<td>" . number_format($daily_kmpl, 2) . "</td>";

                            // Calculate cumulative KMPL
                            $cumulative_total_km += $row['total_total_km'];
                            $cumulative_hsd += $row['total_hsd'];
                            $cumulative_kmpl = ($cumulative_hsd != 0) ? round($cumulative_total_km / $cumulative_hsd, 2) : 0;

                            echo "<td>" . $cumulative_total_km . "</td>";
                            echo "<td>" . $cumulative_hsd . "</td>";
                            echo "<td>" . number_format($cumulative_kmpl, 2) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='11'>No data available</td></tr>";
                    }
                }
                ?>
            </tbody>
        </table>
        <br><br>
        <h2 style="display: inline-block; width: 24%;text-align:center;">JTO</h2>
        <h2 style="display: inline-block; width: 24%; text-align:left;">DME</h2>
        <h2 style="display: inline-block; width: 24%; text-align:left;">DY CME</h2>
        <h2 style="display: inline-block; width: 24%; text-align:left; padding-right: 20px;">CME</h2>
    </div>
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
