<?php
include '../includes/connection.php';
include '../includes/depot_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
// Check if session variables are set
    if (!isset($_SESSION['DIVISION']) || !isset($_SESSION['DEPOT'])) {
        die("Error: Division or depot information not found in session.");
    }
    // Retrieve division and depot from session variables
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];

    // Query to retrieve off-road vehicle counts for depot, DWS, and RWY
    $query = "SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN max_off_road_location = 'Depot' THEN 1 ELSE 0 END) AS depot_count,
            SUM(CASE WHEN max_off_road_location = 'DWS' THEN 1 ELSE 0 END) AS dws_count,
            SUM(CASE WHEN max_off_road_location = 'RWY' THEN 1 ELSE 0 END) AS rwy_count,
            SUM(CASE WHEN max_off_road_location = 'Police Station' THEN 1 ELSE 0 END) AS police_count,
            SUM(CASE WHEN max_off_road_location = 'Authorized Dealer' THEN 1 ELSE 0 END) AS authorized_dealer
          FROM (
            SELECT bus_number, MAX(off_road_location) AS max_off_road_location
            FROM off_road_data
            WHERE status = 'off_road' AND division = '$division' AND depot = '$depot'
            GROUP BY bus_number
          ) AS max_locations";




    $result = mysqli_query($db, $query);

    // Check if query executed successfully
    if (!$result) {
        die("Error: " . mysqli_error($db));
    }

    // Fetch the result
    $row = mysqli_fetch_assoc($result);

    // Assign counts to variables, handle the case when counts are null
    $depotCount = isset($row['depot_count']) ? $row['depot_count'] : 0;
    $dwsCount = isset($row['dws_count']) ? $row['dws_count'] : 0;
    $rwyCount = isset($row['rwy_count']) ? $row['rwy_count'] : 0;
    $policecount = isset($row['police_count']) ? $row['police_count'] : 0;
    $authorizeddealer = isset($row['authorized_dealer']) ? $row['authorized_dealer'] : 0;

    // Free the result set
    mysqli_free_result($result);

    // Query to retrieve the count of vehicles
    $query = "SELECT COUNT(*) AS vehicle_count FROM bus_registration WHERE division_name = '$division' AND depot_name = '$depot'";
    $result = mysqli_query($db, $query);

    // Check if query executed successfully
    if (!$result) {
        die("Error: " . mysqli_error($db));
    }

    // Fetch the result
    $row = mysqli_fetch_assoc($result);

    // Assign vehicle count to a variable
    $vehicleCount = isset($row['vehicle_count']) ? $row['vehicle_count'] : 0;

    // Free the result set
    mysqli_free_result($result);

    ?>


    <!-- Rest of your HTML form -->

    <style>
        /* For small devices (phones) */
        @media only screen and (max-width: 500px) {
            .input-group-text {
                width: 220px;
            }
        }

        /* For medium devices (tablets) */
        @media only screen and (min-width: 501px) and (max-width: 992px) {
            .input-group-text {
                width: 230px;
            }
        }

        /* For large devices (desktops) */
        @media only screen and (min-width: 993px) {
            .input-group-text {
                width: 350px;
                text-align: left;
                /* Adjust alignment as needed */
            }
        }

        .center-button {
            display: flex;
            justify-content: center;
        }
    </style>

    <div class="container" style="width: 100%;">
        <form id="dvpForm" action="save_dvp.php" method="post">
            <div class="form-group col-md-6">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" style="width:100px;color: red;">Date:</span>
                    </div>
                    <input type="date" id="date" class="form-control" name="date" max="<?php echo date('Y-m-d'); ?>"
                        value="<?php echo date('Y-m-d'); ?>" readonly style="color: red;">
                </div>
            </div>
            <div class="form-row">

                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Number of Schdules:</span>
                        </div>
                        <input type="number" class="form-control" id="schdules" name="schdules"
                            oninput="calculateDifference()" required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Number of Vehicles(RWY Including):</span>
                        </div>
                        <input type="number" class="form-control" id="vehicles" name="vehicles"
                            oninput="calculateDifference()" value="<?php echo $vehicleCount; ?>" style="color: red;"
                            readonly>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Number of Spare Vehicles(RWY
                                Including):</span>
                        </div>
                        <input type="number" class="form-control" id="spare" name="spare" oninput="calculateDifference()"
                            readonly style="color: red;">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Spare Vehicles Percentage(RWY
                                Excluding):</span>
                        </div>
                        <input type="number" class="form-control" id="spareP" name="spareP" oninput="calculateDifference()"
                            readonly style="color: red;">
                    </div>
                </div>
            </div>
            <center>
                <h4>Other Off Roads</h4>
            </center>
            <div class="form-row">


                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Vehicles Off Road At Depot:</span>
                        </div>
                        <input type="number" class="form-control" id="ORDepot" name="ORDepot"
                            oninput="calculateDifference()" value="<?php echo $depotCount; ?>" readonly style="color: red;">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Vehicles Off Road At DWS:</span>
                        </div>
                        <input type="number" class="form-control" id="ORDWS" name="ORDWS" oninput="calculateDifference()"
                            value="<?php echo $dwsCount; ?>" readonly style="color: red;">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Vehicles Off Road At RWY:</span>
                        </div>
                        <input type="number" class="form-control" id="ORRWY" name="ORRWY" oninput="calculateDifference()"
                            value="<?php echo $rwyCount; ?>" readonly style="color: red;">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Number of Docking:</span>
                        </div>
                        <input type="number" class="form-control" id="docking" name="docking"
                            oninput="calculateDifference()" required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Vehicles Withdrawn for Fair:</span>
                        </div>
                        <input type="number" class="form-control" id="wup" name="wup" oninput="calculateDifference()"
                            required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Vehicles on CC/Extra Operation:</span>
                        </div>
                        <input type="number" class="form-control" id="CC" name="CC" oninput="calculateDifference()"
                            required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Vehicles not Arrived to Depot:</span>
                        </div>
                        <input type="number" class="form-control" id="notdepot" name="notdepot"
                            oninput="calculateDifference()" required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Vehicles loan given to other Depot/Training Center:</span>
                        </div>
                        <input type="number" class="form-control" id="loan" name="loan" oninput="calculateDifference()"
                            required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Vehicles Held at Authorized Dealer :</span>
                        </div>
                        <input type="number" class="form-control" id="Dealer" name="Dealer" oninput="calculateDifference()"
                            value="<?php echo $authorizeddealer; ?>" readonly style="color: red;">
                    </div>
                </div>

                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Vehicles at Police Station:</span>
                        </div>
                        <input type="number" class="form-control" id="Police" name="Police" oninput="calculateDifference()"
                            value="<?php echo $policecount; ?>" readonly style="color: red;">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Vehicle not availavle for operation:</span>
                        </div>
                        <input type="number" class="form-control" id="ORTotal" name="ORTotal"
                            oninput="calculateDifference()" readonly style="color: red;">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Vehicle availavle for operation:</span>
                        </div>
                        <input type="number" class="form-control" id="available" name="available"
                            oninput="calculateDifference()" readonly style="color: red;">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="color: red;">Vehicle Excess or Shortage:</span>
                        </div>
                        <input type="number" class="form-control" id="E/S" name="E/S" oninput="calculateDifference()"
                            readonly style="color: red;">
                    </div>
                </div>
                <!-- Add other input groups similarly -->
            </div>
            <div class="center-button">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>

    <script>
        function calculateDifference() {
            // Get the values of Number of Schedules and Number of Vehicles
            var schedules = parseInt(document.getElementById("schdules").value);
            var vehicles = parseInt(document.getElementById("vehicles").value);
            var docking = parseInt(document.getElementById("docking").value);
            var wup = parseInt(document.getElementById("wup").value);
            var ORDepot = parseInt(document.getElementById("ORDepot").value);
            var ORDWS = parseInt(document.getElementById("ORDWS").value);
            var ORRWY = parseInt(document.getElementById("ORRWY").value);
            var CC = parseInt(document.getElementById("CC").value);
            var Police = parseInt(document.getElementById("Police").value);
            var Dealer = parseInt(document.getElementById("Dealer").value);
            var notdepot = parseInt(document.getElementById("notdepot").value);
            var loan = parseInt(document.getElementById("loan").value);

            // Calculate the difference to find the number of spare vehicles
            var spare = (vehicles - schedules);

            // Update the Number of Spare Vehicles field
            document.getElementById("spare").value = spare;

            // Calculate the Spare Vehicles Percentage
            var sparePercentage = ((spare - ORRWY) * 100 / schedules).toFixed(2);

            // Update the Spare Vehicles Percentage field
            document.getElementById("spareP").value = sparePercentage;

            // Calculate the difference to find the number of Off road total vehicles
            var ORTotal = (docking + wup + ORDepot + ORDWS + ORRWY + CC + Police + notdepot + Dealer + loan);

            // Update the Number of off road Vehicles field
            document.getElementById("ORTotal").value = ORTotal;

            // Calculate the difference to find the number of vehicle available for operation vehicles
            var available = (vehicles - ORTotal);

            // Update the Number of vehicle available for opertion Vehicles field
            document.getElementById("available").value = available;

            // Calculate the difference to find the number of total Access or shortage vehicles
            var AS = (spare - ORTotal);

            // Update the Number of access or shortage of Vehicles field
            document.getElementById("E/S").value = AS;
        }
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            // Submit form using AJAX
            $('#dvpForm').submit(function (e) {
                e.preventDefault(); // Prevent default form submission

                // Get form data
                var formData = $(this).serialize();

                // Send AJAX request
                $.ajax({
                    type: 'POST',
                    url: 'save_dvp.php',
                    data: formData,
                    dataType: 'json', // Expect JSON response
                    success: function (response) {
                        // Display appropriate message
                        if (response.status === 'success') {
                            alert(response.message); // Alert success message
                            location.reload(); // Reload the page
                        } else {
                            alert(response.message); // Alert error message
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText); // Log error to console
                        alert('An error occurred while processing your request.'); // Alert error message
                    }
                });
            });
        });
    </script>

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>