<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && ($_SESSION['JOB_TITLE'] == 'CME_CO')) {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    ?>

    <h6>Select Data for KMPL Monthly Report</h6>
    <form id="scheduleForm">
        <?php
        $currentDate = new DateTime();
        $currentYear = $currentDate->format("Y");
        $currentMonth = $currentDate->format("n");
        $startYear = 2025;
        $startMonth = 2;

        // Generate year range
        $year_range = range($startYear, $currentYear);
        ?>

        <label for="year">Year:</label>
        <select id="year" name="year" onchange="updateMonths()" required>
            <option value="">Select</option>
            <?php
            foreach ($year_range as $year_val) {
                $selected = ($year_val == $currentYear) ? '' : '';
                echo '<option ' . $selected . ' value ="' . $year_val . '">' . $year_val . '</option>';
            }
            ?>
        </select>

        <label for="month">Month:</label>
        <select id="month" name="month" required>
            <option value="">Select</option>
            <?php
            for ($i = $startMonth; $i <= $currentMonth; $i++) {
                $month_name = date("F", mktime(0, 0, 0, $i, 1));
                $selected = ($i == $currentMonth) ? 'selected' : '';
                echo '<option ' . $selected . ' value="' . $i . '">' . $month_name . '</option>';
            }
            ?>
        </select>
        <label for="division">Select Division:</label>
        <select id="division" name="division" >
            <option value="">Select Division</option>
        </select>

        <label for="depot">Select Depot:</label>
        <select id="depot" name="depot" >
            <option value="">Select Depot</option>
        </select>
        <label for="make">Make:</label>
        <select id="make" name="make">
            <option value="">Select</option>
            <?php
            $query = "SELECT DISTINCT make FROM makes ORDER BY make ASC";
            $result = $db->query($query);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($row['make']) . "'>" . htmlspecialchars($row['make']) . "</option>";
                }
            }
            ?>
        </select>
        <label for="emission_norms">Norms:</label>
        <select id="emission_norms" name="emission_norms">
            <option value="">Select</option>
            <?php
            $query = "SELECT DISTINCT emission_norms FROM norms";
            $result = $db->query($query);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($row['emission_norms']) . "'>" . htmlspecialchars($row['emission_norms']) . "</option>";
                }
            }
            ?>
        </select>
        <label for="sch_no">Schedule No:</label>
        <select id="sch_no" name="sch_no" >
            <option value="">Select Schedule</option>
        </select>

        <label for="bus_number">Bus Number:</label>
        <select id="bus_number" name="bus_number" >
            <option value="">Select Bus</option>
        </select>

        <label for="driver_token">Driver Token:</label>
        <select id="driver_token" name="driver_token" >
            <option value="">Select Driver</option>
        </select>
        <button class="btn btn-primary" type="submit">Submit</button>
        <button class="btn btn-success" onclick="window.print()">Print</button>

    </form>
    <div class="container1">
        <div id="reportContainer"></div>
    </div>
    <script>
        function fetchBusCategory() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: {
                    action: 'fetchDivision'
                },
                success: function (response) {
                    var divisions = JSON.parse(response);
                    $.each(divisions, function (index, division) {
                        if (division.DIVISION !== 'HEAD-OFFICE' && division.DIVISION !== 'RWY') {
                            $('#division').append('<option value="' + division.division_id + '">' + division
                                .DIVISION + '</option>');
                        }
                    });
                }
            });

            $('#division').change(function () {
                var Division = $(this).val();
                $.ajax({
                    url: '../includes/data_fetch.php?action=fetchDepot',
                    method: 'POST',
                    data: {
                        division: Division
                    },
                    success: function (data) {
                        // Update the depot dropdown with fetched data
                        $('#depot').html(data);

                        // Hide the option with text 'DIVISION'
                        $('#depot option').each(function () {
                            if ($(this).text().trim() === 'DIVISION') {
                                $(this).hide();
                            }
                        });
                    }
                });
            });
        }
        $(document).ready(function () {
            fetchBusCategory();
        });
        function updateMonths() {
            // Get the selected year
            const yearSelect = document.getElementById("year");
            const monthSelect = document.getElementById("month");
            const selectedYear = parseInt(yearSelect.value);

            // Clear existing options in the month dropdown
            monthSelect.innerHTML = "";

            // Add a default "Select" option
            const defaultOption = document.createElement("option");
            defaultOption.value = "";
            defaultOption.textContent = "Select Month";
            defaultOption.selected = true;
            defaultOption.disabled = true;
            monthSelect.appendChild(defaultOption);

            // Define start year, start month, and current year/month
            const startYear = 2025;
            const startMonth = 2;
            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1; // Month is zero-based

            let start = 1; // Default start month
            let end = 12; // Default end month

            // Adjust start and end months based on the selected year
            if (selectedYear === startYear) {
                start = startMonth; // Start from December 2023
            }
            if (selectedYear === currentYear) {
                end = currentMonth; // End at the current month
            }

            // Populate the month dropdown
            for (let i = start; i <= end; i++) {
                const monthName = new Date(2000, i - 1, 1).toLocaleString("default", {
                    month: "long"
                });
                const option = document.createElement("option");
                option.value = i;
                option.textContent = monthName;
                monthSelect.appendChild(option);
            }
        }
    </script>


    <script>
        $(document).ready(function () {
            // Initialize Select2 on page load
            $('#sch_no, #bus_number, #driver_token').select2();

            $('#depot').change(function () {
                var depotId = $(this).val();
                $('#sch_no, #bus_number, #driver_token').html('<option value="">Select</option>'); // Reset fields

                if (depotId) {
                    fetchScheduleNos(depotId);
                    fetchBusNumbers(depotId);
                    fetchDriverTokens(depotId);
                }
            });

            function fetchScheduleNos(depotId) {
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: { action: 'fetchScheduleNos', depot_id: depotId },
                    success: function (response) {
                        $('#sch_no').html(response);
                    }
                });
            }

            function fetchBusNumbers(depotId) {
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: { action: 'fetchBusNumbers', depot_id: depotId },
                    success: function (response) {
                        $('#bus_number').html(response);
                    }
                });
            }

            function fetchDriverTokens(depotId, reportDate) {
                $.ajax({
                    url: '../includes/backend_data.php',
                    type: 'POST',
                    data: { action: 'getDepotDetails', depot_id: depotId },
                    success: function (response) {
                        var depotDetails = JSON.parse(response);

                        if (depotDetails.kmpl_division && depotDetails.kmpl_depot) {
                            callApis(depotDetails.kmpl_division, depotDetails.kmpl_depot, reportDate);
                        } else {
                            console.error("Missing kmpl_division or kmpl_depot");
                        }
                    }
                });
            }

            function callApis(kmplDivision, kmplDepot, reportDate) {
                var apiUrl1 = `http://localhost:8880/dvp/includes/data.php?division=${kmplDivision}&depot=${kmplDepot}`;
                var apiUrl2 = `http://localhost:8880/dvp/database/private_emp_api.php?division=${kmplDivision}&depot=${kmplDepot}`;

                $.when(
                    $.get(apiUrl1),
                    $.get(apiUrl2)
                ).done(function (response1, response2) {


                    // Initialize empty arrays for the data
                    var data1 = [];
                    var data2 = [];

                    // Handle API 1 Response
                    try {
                        if (response1[0] && response1[0].data) {
                            data1 = response1[0].data ?? [];
                        } else {
                            console.error("API 1 Response does not contain expected 'data' field");
                        }
                    } catch (error) {
                        console.error("Error parsing API 1:", error);
                    }

                    // Handle API 2 Response
                    try {
                        if (response2[0] && response2[0].data) {
                            data2 = response2[0].data ?? [];
                        } else {
                            console.error("API 2 Response does not contain expected 'data' field");
                        }
                    } catch (error) {
                        console.error("Error parsing API 2:", error);
                    }

                    // Combine the data from both APIs
                    var combinedData = [...data1, ...data2];

                    // Populate the driver token dropdown
                    $('#driver_token').html('<option value="">Select Driver</option>');
                    combinedData.forEach(driver => {
                        // Assuming 'token_number' exists in the driver object
                        $('#driver_token').append(`<option value="${driver.EMP_PF_NUMBER}">${driver.token_number}-(${driver.EMP_NAME})</option>`);
                    });

                }).fail(function (jqXHR, textStatus, errorThrown) {
                    console.error("API Call Failed:", textStatus, errorThrown);
                });
            }



        });

    </script>
    <script>
        $(document).ready(function () {
            $('#scheduleForm').on('submit', function (e) {
                e.preventDefault();
                const month = $('#month').val();
                const year = $('#year').val();
                const division_id = $('#division').val();
                const depot_id = $('#depot').val();
                const make = $('#make').val();
                const emission_norms = $('#emission_norms').val();
                const sch_no = $('#sch_no').val();
                const bus_number = $('#bus_number').val();
                const driver_token = $('#driver_token').val();
                $.ajax({
                    type: 'POST',
                    url: '../database/monthly_kmpl_report.php',
                    data: JSON.stringify({
                        month: month,
                        year: year,
                        division_id: division_id,
                        depot_id: depot_id,
                        make: make,
                        emission_norms: emission_norms,
                        sch_no: sch_no, 
                        bus_number: bus_number, 
                        driver_token: driver_token 
                    }),
                    contentType: 'application/json',
                    success: function (response) {
                        try {
                            const data = JSON.parse(response);
                            $('#reportContainer').html(data.html);
                        } catch (error) {
                            console.error('Failed to parse JSON:', error);
                            $('#reportContainer').html('<p>Error parsing response.</p>');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        $('#reportContainer').html('<p>Error loading report.</p>');
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