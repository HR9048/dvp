<?php
include '../includes/connection.php';
include '../includes/depot_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_date'])) {
        $selected_date = $_POST['selected_date'];
        $selected_like_items = $_POST['like_items'];

        // Fetch data based on selected date, division, and depot
        $sql = "SELECT *, 
            DATEDIFF('$selected_date', off_road_date) AS days_off_road 
            FROM off_road_data 
            WHERE division = '{$_SESSION['DIVISION_ID']}' 
            AND depot = '{$_SESSION['DEPOT_ID']}'";

        // Filter out the records where the selected date is between the off road and on road dates
        $sql .= " AND ('$selected_date' BETWEEN off_road_date AND IFNULL(on_road_date, CURDATE()))";

        // Add filter for Like Items in parts required
        if ($selected_like_items != "All") {
            $sql .= " AND parts_required LIKE '%$selected_like_items%'";
        }

        $result = $db->query($sql);

        // Display the filtered data
        ?>
        <style>
            @media print {
                body * {
                    visibility: hidden;
                }

                .table,
                .table * {
                    visibility: visible;
                }

                .table {
                    width: 95%;
                    text-align: right;
                    position: absolute;
                    top: 0;
                    left: 0;
                }

                /* Set font size to 10px for all elements */
                body {
                    font-size: 10px;
                }
            }
        </style>
        <div class="container-fluid custom-container">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="selectedDate">Select Date:</label>
                        <input type="date" id="selectedDate" name="selected_date" class="form-control"
                            max="<?php echo date('Y-m-d'); ?>" value="<?php echo $selected_date; ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="likeItems">Select Like Items:</label>
                        <select id="likeItems" name="like_items" class="form-control">
                            <option value="All" <?php echo (!isset($_POST['like_items']) || ($_POST['like_items'] == 'All')) ? 'selected' : ''; ?>>All</option>
                            <?php
                            // Fetching reason names from the reason table
                            $reasonSql = "SELECT * FROM reason";
                            $reasonResult = $db->query($reasonSql);

                            if ($reasonResult->num_rows > 0) {
                                while ($row = $reasonResult->fetch_assoc()) {
                                    $selected = (isset($_POST['like_items']) && $_POST['like_items'] == $row['reason_name']) ? ' selected' : '';
                                    echo "<option value='" . $row['reason_name'] . "'$selected>" . $row['reason_name'] . "</option>";
                                }
                            } else {
                                echo "<option>No reasons found</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group text-center"> <!-- Added text-center class here -->
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
            <?php

            // Initialize variables for rowspan logic
            $bus_numbers = [];
            $bus_number_rowspans_count = [];

            // Group data by bus number
            while ($row = mysqli_fetch_assoc($result)) {
                $bus_number = $row['bus_number'];
                if (!in_array($bus_number, $bus_numbers)) {
                    $bus_numbers[] = $bus_number;
                }
                if (!isset($bus_number_rowspans_count[$bus_number])) {
                    $bus_number_rowspans_count[$bus_number] = 0;
                }
                $bus_number_rowspans_count[$bus_number]++;
            }
            mysqli_data_seek($result, 0); // Reset the result pointer to the beginning
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Serial No</th>
                        <th>Bus Number</th>
                        <th>Make</th>
                        <th>Emission Norms</th>
                        <th>Off Road Date</th>
                        <th>No. of Days Off Road</th>
                        <th>Off Road Location</th>
                        <th>Parts Required</th>
                        <th>Remarks</th>
                        <th>DWS Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Initialize serial number counter
                    $serial_number = 1;

                    // Loop through each bus number
                    foreach ($bus_numbers as $bus_number) {
                        // Flag to indicate if it's the first row for the current bus number
                        $first_row = true;
                        // Count the number of rows for the current bus number
                        $row_count = 0;
                        // Loop through each row of the result set for the current bus number
                        while ($row = mysqli_fetch_assoc($result)) {
                            if ($row['bus_number'] == $bus_number) {
                                // Increment row count for the current bus number
                                $row_count++;
                                // Output data in table rows
                                echo "<tr>";
                                // Output serial number only for the first row of the current bus number
                                if ($first_row) {
                                    echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>$serial_number</td>";
                                    echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['bus_number'] . "</td>";
                                    echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['make'] . "</td>";
                                    echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['emission_norms'] . "</td>";
                                    $first_row = false;
                                }
                                // Extract data from the row
                                $offRoadFromDate = $row['off_road_date'];
                                $offRoadLocation = $row['off_road_location'];
                                $partsRequired = $row['parts_required'];
                                $remarks = $row['remarks'];
                                $dws_remarks = $row['dws_remark'];

                                // Calculate the number of days off-road
                                $offRoadDate = new DateTime($offRoadFromDate);
                                $today = new DateTime();
                                $daysOffRoad = $today->diff($offRoadDate)->days;

                                // Output the data in table rows
                                echo "<td>" . date('d/m/y', strtotime($offRoadFromDate)) . "</td>";
                                echo "<td>$daysOffRoad</td>";
                                echo "<td>$offRoadLocation</td>";
                                echo "<td>$partsRequired</td>";
                                echo "<td>$remarks</td>";
                                echo "<td>$dws_remarks</td>";
                                echo "</tr>";
                            }
                        }
                        // Increment the serial number only if there were rows for the current bus number
                        if ($row_count > 0) {
                            $serial_number++;
                        }
                        // Reset the result pointer to the beginning for the next bus number
                        mysqli_data_seek($result, 0);
                    }
                    ?>

                </tbody>
            </table>
            <div class="text-center mt-3">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
        </div>
        <?php
    } else {
        // Show the date and like items selection form
        ?>
        <button><a href="depot_offroad_fromto.php">Report From-To Date</a></button>
        <div class="container-fluid custom-container">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="selectedDate">Select Date:</label>
                        <input type="date" id="selectedDate" name="selected_date" class="form-control"
                            max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="likeItems">Select Like Items:</label>
                        <select id="likeItems" name="like_items" class="form-control">
                            <option value="All">All</option>
                            <?php
                            // Fetching reason names from the reason table
                            $reasonSql = "SELECT * FROM reason";
                            $reasonResult = $db->query($reasonSql);

                            if ($reasonResult->num_rows > 0) {
                                while ($row = $reasonResult->fetch_assoc()) {
                                    $selected = isset($_POST['reason']) && $_POST['reason'] == $row['reason_name'] ? ' selected' : '';
                                    echo "<option value='" . $row['reason_name'] . "'$selected>" . $row['reason_name'] . "</option>";
                                }
                            } else {
                                echo "<option>No reasons found</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group text-center"> <!-- Added text-center class here -->
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
        <?php
    }
?>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>