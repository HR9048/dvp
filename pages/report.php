<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_date'])) {
        $selected_date = $_POST['selected_date'];
        $selected_like_items = $_POST['like_items'];

        // Fetch data based on selected date, division, and depot
        $sql = "SELECT 
    o.*, 
    l.division AS division_name, 
    l.depot AS depot_name,
    DATEDIFF('$selected_date', o.off_road_date) AS days_off_road 
FROM off_road_data o
JOIN location l ON o.depot = l.depot_id";

        // Filter out the records where the selected date is between the off road and on road dates
        $sql .= " WHERE ('$selected_date' BETWEEN o.off_road_date AND IFNULL(o.on_road_date, CURDATE()))";

        // Add filter for Like Items in parts required
        if ($selected_like_items != "All") {
            $sql .= " AND o.parts_required LIKE '%$selected_like_items%'";
        }

        // Append ORDER BY clause to order the table by division, off_road_location, and depot
        $sql .= " ORDER BY l.division_id, o.off_road_location ASC, l.depot_id ASC";


        $result = $db->query($sql);
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
                            value="<?php echo $selected_date; ?>" required>
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
                        </select>
                    </div>
                </div>
                <div class="form-group text-center"> <!-- Added text-center class here -->
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Sl No</th>
                            <th>Divn (Sl No)</th>
                            <th>Division</th>
                            <th>Depot</th>
                            <th>Bus Number</th>
                            <th>Make</th>
                            <th>Emission Norms</th>
                            <th>Off Road From Date</th>
                            <th>Number of days off-road</th>
                            <th>Off Road Location</th>
                            <th>Parts Required</th>
                            <th>Remarks</th>
                            <th>DWS Remarks</th>
                            <th>On road date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Initialize serial number counters
                        $bus_serial_number = 1;
                        $division_serial_number = 1;
                        $current_division = null; // Track the current division
                
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
                                    // Output bus serial number only for the first row of the current bus number
                                    if ($first_row) {
                                        echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>$bus_serial_number</td>";
                                        // Output division serial number only if division has changed
                                        if ($row['division'] != $current_division) {
                                            $current_division = $row['division'];
                                            $division_serial_number = 1; // Reset division serial number
                                        }
                                        echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>$division_serial_number</td>";
                                        echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['division_name'] . "</td>";
                                        echo "<td rowspan='" . $bus_number_rowspans_count[$bus_number] . "'>" . $row['depot_name'] . "</td>";
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
                                    $on_road_date = $row['on_road_date'];
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
                                    if (empty($on_road_date)) {
                                        echo "<td>Off Road</td>";
                                    } else {
                                        // Convert datetime string to date format
                                        $on_road_date_only = date('Y-m-d', strtotime($on_road_date));

                                        if ($on_road_date_only == $selected_date) {
                                            echo "<td>$on_road_date_only</td>";
                                        } else {
                                            echo "<td>Off Road</td>";
                                        }
                                    }

                                    echo "</tr>";
                                }
                            }
                            // Increment the bus serial number only if there were rows for the current bus number
                            if ($row_count > 0) {
                                $bus_serial_number++;
                                // Increment division serial number for each new division
                                $division_serial_number++;
                            }
                            // Reset the result pointer to the beginning for the next bus number
                            mysqli_data_seek($result, 0);
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="text-center mt-3">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
        </div>

        <?php
    } else {
        // Show the date and like items selection form
        ?>
        <div class="container-fluid custom-container">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="selectedDate">Select Date:</label>
                        <input type="date" id="selectedDate" name="selected_date" class="form-control" required>
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

} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>