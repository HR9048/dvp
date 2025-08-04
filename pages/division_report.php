<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'DC') {
    // Allow access
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_date'])) {
    $selected_date = $_POST['selected_date'];
    $selected_like_items = $_POST['like_items'];

    $sql = "SELECT ord.*, 
    loc.depot AS depot_name, 
    DATEDIFF('$selected_date', ord.off_road_date) AS days_off_road 
FROM off_road_data ord
INNER JOIN location loc ON ord.depot = loc.depot_id
WHERE ord.division = '{$_SESSION['DIVISION_ID']}'
AND ('$selected_date' BETWEEN ord.off_road_date AND IFNULL(ord.on_road_date, CURDATE()))
order by ord.off_road_location, loc.depot_id ASC";


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

            .container2,
            .container2 * {
                visibility: visible;
            }

            .container2 {
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
                        $reasonSql = "SELECT distinct reason_name FROM reason";
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
        <div class="container2">
            <table>
                <thead>
                    <tr>
                        <th>Serial No</th>
                        <th>Bus Number</th>
                        <th>Depot</th>
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
                    if ($result->num_rows > 0) {
                        $serialNo = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $serialNo++ . "</td>";
                            echo "<td>" . $row['bus_number'] . "</td>";
                            echo "<td>" . $row['depot_name'] . "</td>";
                            echo "<td>" . $row['make'] . "</td>";
                            echo "<td>" . $row['emission_norms'] . "</td>";
                            echo "<td>" . $row['off_road_date'] . "</td>";
                            echo "<td>" . $row['days_off_road'] . "</td>";
                            echo "<td>" . $row['off_road_location'] . "</td>";
                            echo "<td>" . $row['parts_required'] . "</td>";
                            echo "<td>" . $row['remarks'] . "</td>";
                            echo "<td>" . $row['dws_remark'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='11'>No data found</td></tr>";
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
    <button><a href="division_offroad.php">View from date to to date report Click Here-></a></button>

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
                        $reasonSql = "SELECT distinct reason_name FROM reason";
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