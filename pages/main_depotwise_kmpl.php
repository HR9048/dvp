<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["date"])) {
        // Get the selected date
        $selectedDate = $_POST["date"];
        // Get the first date of the selected month
        $firstDateOfMonth = date("Y-m-01", strtotime($selectedDate));

        // Define preferred order of divisions
        $preferredDivisions = array(
            "1" => "KLB1",
            "2" => "KLB2",
            "3" => "YDG",
            "4" => "BDR",
            "5" => "RCH",
            "6" => "KPL",
            "7" => "BLR",
            "8" => "HSP",
            "9" => "VJP",
        );

        // Fetch daily wise KMPL data for the selected date and cumulative data
        $query = "SELECT 
            k.division,
            l.depot as depot_name,
            SUM(CASE WHEN k.date = '$selectedDate' THEN k.total_km ELSE 0 END) AS daily_total_km,
            SUM(CASE WHEN k.date = '$selectedDate' THEN k.hsd ELSE 0 END) AS daily_hsd,
            SUM(CASE WHEN k.date = '$selectedDate' THEN k.kmpl ELSE 0 END) AS daily_kmpl,
            SUM(CASE WHEN k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate' THEN k.total_km ELSE 0 END) AS total_total_km,
            SUM(CASE WHEN k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate' THEN k.hsd ELSE 0 END) AS total_hsd,
            SUM(CASE WHEN k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate' THEN k.kmpl ELSE 0 END) AS total_kmpl
        FROM 
            kmpl_data k
        JOIN 
            location l ON k.depot = l.depot_id
        WHERE 
            k.date BETWEEN '$firstDateOfMonth' AND '$selectedDate'
        GROUP BY 
            k.division, 
            depot_name 
        ORDER BY 
            FIELD(k.division, '" . implode("', '", array_keys($preferredDivisions)) . "'), 
            l.depot_id";
        $result = mysqli_query($db, $query) or die(mysqli_error($db));

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
                    width: 100%;
                    text-align: right;
                    position: absolute;
                    top: 0;
                    left: 0;
                }
            }

            th,
            td {
                border: 2px solid black;
                /* Apply border to table cells */
                padding: 10px;
                text-align: center;
                font-size: 15px;
                font-weight: bold;
                /* Add bold to all elements */
                height: 1px;
                /* Reduce the height of rows */
            }

            .container p {
                margin: 0;
            }

            .text-center {
                text-align: center;
            }

            .mt-3 {
                margin-top: 1rem;
            }

            .btn {
                display: inline-block;
                padding: 0.375rem 0.75rem;
                font-size: 1rem;
                line-height: 1.5;
                border-radius: 0.25rem;
                transition: color 0.15s;
            }

            .btn-primary {
                color: #fff;
                background-color: #007bff;
                border-color: #007bff;
            }
        </style>

        <div class="container1" style="text-align:center">
            <form action="" method="post">
                <label for="date">Select Date:</label>
                <input type="date" id="date" name="date">
                <button type="submit">Submit</button>
            </form>
        </div>
        <div class="container">
            <h1 style="text-align:center;">Kalyana Karnataka Road Transport Corporation (KKRTC)</h1><br><br>
            <h2 style="display: inline-block; width: 30%; text-align:left;">Central Office</h2>
            <h2 style="display: inline-block; width: 30%; text-align:center;">KALABURAGI</h2>
            <h2 style="display: inline-block; width: 30%; text-align:right;">
                <?php
                $formattedDate = date('d/m/Y', strtotime($selectedDate));
                echo $formattedDate;
                ?>
            </h2>

            <table>
                <thead>
                    <tr>
                        <th style="min-width: 20px;" rowspan="2">SL NO</th>
                        <th style="text-align:center;" rowspan="2">DIVISION</th>
                        <th style="text-align:center;" rowspan="2">DEPOT</th>
                        <th colspan="3">Daily wise KM</th>
                        <th colspan="3">Cumulative KMPL</th>
                    </tr>
                    <tr>
                        <th>Total KM</th>
                        <th style="min-width: 50px;">HSD</th>
                        <th style="min-width: 50px;">KMPL</th>
                        <th>Total KM</th>
                        <th style="min-width: 50px;">HSD</th>
                        <th style="min-width: 50px;">KMPL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $index = 1;
                    $divisionTotal = array('total_km' => 0, 'hsd' => 0, 'kmpl' => 0);
                    $cumulativeTotal = array('total_km' => 0, 'hsd' => 0, 'kmpl' => 0);
                    $divisionCumulativeTotal = array();
                    $currentDivision = null;

                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($currentDivision !== $row['division']) {
                            // Display division-wise total
                            if ($currentDivision !== null) {
                                // Calculate KMPL for division total
                                if ($divisionTotal['hsd'] != 0) {
                                    // Check if hsd is not zero before performing division
                                    $divisionTotal['kmpl'] = number_format($divisionTotal['total_km'] / $divisionTotal['hsd'], 2);
                                } else {
                                    // Set KMPL to 0 if hsd is zero to avoid division by zero error
                                    $divisionTotal['kmpl'] = 0;
                                }
                                ?>
                                <tr>
                                    <!-- Division total data -->
                                    <?php
                                    // Fetch division name from location table
                                    $divisionQuery = "SELECT division FROM location WHERE division_id = '$currentDivision'";
                                    $divisionResult = mysqli_query($db, $divisionQuery) or die(mysqli_error($db));
                                    $divisionRow = mysqli_fetch_assoc($divisionResult);
                                    $divisionName = isset($divisionRow['division']) ? $divisionRow['division'] : $currentDivision;
                                    ?>
                                    <td colspan="3">Total <?php echo $divisionName; ?></td>
                                    <td><?php echo $divisionTotal['total_km']; ?></td>
                                    <td><?php echo $divisionTotal['hsd']; ?></td>
                                    <td><?php echo $divisionTotal['kmpl']; ?></td>
                                    <!-- Cumulative data for division total -->
                                    <td><?php echo $divisionCumulativeTotal[$currentDivision]['total_km']; ?></td>
                                    <td><?php echo $divisionCumulativeTotal[$currentDivision]['hsd']; ?></td>
                                    <td><?php echo number_format($divisionCumulativeTotal[$currentDivision]['total_km'] / $divisionCumulativeTotal[$currentDivision]['hsd'], 2); ?>
                                    </td>
                                </tr>
                                <?php
                            }

                            // Reset division-wise total for the new division
                            $divisionTotal = array('total_km' => 0, 'hsd' => 0, 'kmpl' => 0);
                            $currentDivision = $row['division'];
                        }

                        // Update division-wise total
                        $divisionTotal['total_km'] += $row['daily_total_km'];
                        $divisionTotal['hsd'] += $row['daily_hsd'];

                        // Update cumulative total
                        $cumulativeTotal['total_km'] += $row['daily_total_km'];
                        $cumulativeTotal['hsd'] += $row['daily_hsd'];

                        // Store division cumulative total
                        if (!isset($divisionCumulativeTotal[$row['division']])) {
                            $divisionCumulativeTotal[$row['division']] = array(
                                'total_km' => $row['total_total_km'],
                                'hsd' => $row['total_hsd']
                            );
                        } else {
                            $divisionCumulativeTotal[$row['division']]['total_km'] += $row['total_total_km'];
                            $divisionCumulativeTotal[$row['division']]['hsd'] += $row['total_hsd'];
                        }

                        ?>
                        <tr>
                            <!-- Daily data rows -->
                            <td style="text-align: center;"><?php echo $index; ?></td>
                            <td><?php echo isset($preferredDivisions[$row['division']]) ? $preferredDivisions[$row['division']] : $row['division']; ?>
                            </td>
                            <td><?php echo $row['depot_name']; ?></td>
                            <td style="text-align: center;">
                                <?php echo isset($row['daily_total_km']) ? $row['daily_total_km'] : 0; ?>
                            </td>
                            <td style="text-align: center;"><?php echo isset($row['daily_hsd']) ? $row['daily_hsd'] : 0; ?></td>
                            <td style="text-align: center;"><?php echo isset($row['daily_kmpl']) ? $row['daily_kmpl'] : 0; ?></td>
                            <td style="text-align: center;">
                                <?php echo isset($row['total_total_km']) ? $row['total_total_km'] : 0; ?>
                            </td>
                            <td style="text-align: center;"><?php echo isset($row['total_hsd']) ? $row['total_hsd'] : 0; ?></td>
                            <td style="text-align: center;">
                                <?php echo isset($row['daily_total_km']) && isset($row['total_hsd']) && $row['total_hsd'] != 0 ? number_format($row['total_total_km'] / $row['total_hsd'], 2) : 0; ?>
                            </td>
                        </tr>

                        <?php
                        $index++;
                    }

                    // Display last division-wise total and cumulative total
                    if ($currentDivision !== null) {
                        // Calculate KMPL for division total
                        if ($divisionTotal['hsd'] != 0) {
                            // Check if hsd is not zero before performing division
                            $divisionTotal['kmpl'] = number_format($divisionTotal['total_km'] / $divisionTotal['hsd'], 2);
                        } else {
                            // Set KMPL to 0 if hsd is zero to avoid division by zero error
                            $divisionTotal['kmpl'] = 0;
                        }
                        ?>
                        <tr>
                            <!-- Division total data -->
                            <?php
                            // Fetch division name from location table
                            $divisionQuery = "SELECT division FROM location WHERE division_id = '$currentDivision'";
                            $divisionResult = mysqli_query($db, $divisionQuery) or die(mysqli_error($db));
                            $divisionRow = mysqli_fetch_assoc($divisionResult);
                            $divisionName = isset($divisionRow['division']) ? $divisionRow['division'] : $currentDivision;
                            ?>
                            <td colspan="3">Total <?php echo $divisionName; ?></td>
                            <td><?php echo $divisionTotal['total_km']; ?></td>
                            <td><?php echo $divisionTotal['hsd']; ?></td>
                            <td><?php echo $divisionTotal['kmpl']; ?></td>
                            <td><?php echo $divisionCumulativeTotal[$currentDivision]['total_km']; ?></td>
                            <td><?php echo $divisionCumulativeTotal[$currentDivision]['hsd']; ?></td>
                            <td><?php echo number_format($divisionCumulativeTotal[$currentDivision]['total_km'] / $divisionCumulativeTotal[$currentDivision]['hsd'], 2); ?>
                            </td>
                        </tr>

                        <?php
                    }

                    // Calculate KMPL for cumulative total
                    // Calculate KMPL for cumulative grand total
                    if ($cumulativeTotal['hsd'] != 0) {
                        // Check if hsd is not zero before performing division
                        $cumulativeTotal['kmpl'] = number_format($cumulativeTotal['total_km'] / $cumulativeTotal['hsd'], 2);
                    } else {
                        // Set KMPL to 0 if hsd is zero to avoid division by zero error
                        $cumulativeTotal['kmpl'] = 0;
                    }



                    $cumulativeGrandTotal = array(
                        'total_km' => 0,
                        'hsd' => 0,
                        'kmpl' => 0
                    );

                    // Calculate cumulative grand total values
                    foreach ($divisionCumulativeTotal as $divisionTotals) {
                        $cumulativeGrandTotal['total_km'] += $divisionTotals['total_km'];
                        $cumulativeGrandTotal['hsd'] += $divisionTotals['hsd'];
                    }

                    // Calculate KMPL for cumulative grand total
                    if ($cumulativeGrandTotal['hsd'] != 0) {
                        $cumulativeGrandTotal['kmpl'] = number_format($cumulativeGrandTotal['total_km'] / $cumulativeGrandTotal['hsd'], 2);
                    } else {
                        $cumulativeGrandTotal['kmpl'] = 0;
                    }


                    ?>

                    <tr>
                        <!-- Cumulative total data -->
                        <td colspan="3">Corporation Total</td>
                        <td><?php echo $cumulativeTotal['total_km']; ?></td>
                        <td><?php echo $cumulativeTotal['hsd']; ?></td>
                        <td><?php echo $cumulativeTotal['kmpl']; ?></td>
                        <!-- Add cumulative grand total for the last 5 columns -->
                        <td><?php echo $cumulativeGrandTotal['total_km']; ?></td>
                        <td><?php echo $cumulativeGrandTotal['hsd']; ?></td>
                        <td><?php echo $cumulativeGrandTotal['kmpl']; ?></td>
                    </tr>

                </tbody>
            </table>




            <br><br><br>
            <h4 style="display: inline-block; width: 24%; text-align:center;">JTO</h4>
            <h4 style="display: inline-block; width: 24%; text-align:center;">DME</h4>
            <h4 style="display: inline-block; width: 24%;text-align:center;">DY CME</h4>
            <h4 style="display: inline-block; width: 24%; text-align:right; padding-right:200px">CME</h4>
        </div>
        <div class="text-center mt-3">
            <button class="btn btn-primary" onclick="window.print()">Print</button>
            <!-- Download Excel button -->
            <button class="btn btn-success" id="downloadExcel">Download Excel</button>
            <!-- Download Text button -->
            <button class="btn btn-danger" id="downloadText">Download Text</button>
            <a href="main_depotwise_kmpl_pdf.php?date=<?php echo $_POST['date']; ?>" class="btn btn-success">Download PDF</a>
        </div>
        <!-- Include xlsx.full.min.js library -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>

        <?php
        // Assuming $selectedDate is already defined somewhere
        $formattedDate = date('d/m/Y', strtotime($selectedDate));
        ?>

        <script>
            document.getElementById('downloadExcel').addEventListener('click', function () {
                // Get container1 HTML content
                var htmlContent = document.querySelector('.container').outerHTML;

                // Convert HTML to workbook
                var workbook = XLSX.utils.table_to_book(document.querySelector('.container'));

                // Save workbook as Excel file with the PHP formatted date and "KMPL" appended to the file name
                XLSX.writeFile(workbook, '<?php echo $formattedDate; ?>_KMPL.xlsx');
            });


            document.getElementById('downloadText').addEventListener('click', function () {
                // Get today's date

                // Get all table data as a 2D array
                var tableData = Array.from(document.querySelectorAll('table')).map(function (table) {
                    return Array.from(table.querySelectorAll('tr')).map(function (row) {
                        return Array.from(row.querySelectorAll('td, th')).map(function (cell) {
                            return cell.innerText;
                        }).join('\t');
                    }).join('\n');
                }).join('\n\n');

                // Create a Blob containing the table data
                var blob = new Blob([tableData], {
                    type: 'text/plain;charset=utf-8'
                });

                // Create a link element to download the Blob
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = '<?php echo $formattedDate; ?>_KMPL.txt';

                // Hide the link and append it to the body
                link.style.display = 'none';
                document.body.appendChild(link);

                // Trigger a click event to download the text file
                link.click();

                // Remove the link from the body
                document.body.removeChild(link);
            });
        </script>
        <?php
    } else {
        // If the form is not submitted or date is not set, show the form
        ?>
        <div class="container">
            <form action="" method="post">
                <label for="date">Select Date:</label>
                <input type="date" id="date" name="date">
                <button type="submit">Submit</button>
            </form>
        </div>
        <?php
    }
?>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>