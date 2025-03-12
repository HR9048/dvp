<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'T_INSPECTOR')) {
    $division = $_SESSION['KMPL_DIVISION'];
    $depot = $_SESSION['KMPL_DEPOT'];
    date_default_timezone_set('Asia/Kolkata');
    ?>
    <style>
        .select2-results__option[aria-disabled="true"] {
            background-color: #FFE800 !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        input {
            width: 100%;
            padding: 5px;
        }

        .frozen {
            background-color: #f2f2f2;
            cursor: not-allowed;
        }

        .action-buttons button {
            padding: 5px 10px;
            margin: 2px;
        }
    </style>

    <div class="container mt-5">
        <h4 class="text-center">Add Crew Deputation</h4>
        <table id="crewTable">
            <thead>
                <tr>
                    <th>Token Number</th>
                    <th>Deputation Designation</th>
                    <th>Deputation Division</th>
                    <th>Deputation Depot</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Input Row -->
                <tr>
                    <td>
                        <select id="crewToken" name="crewToken" class="form-control select2" required>
                            <!-- Dynamic options -->
                        </select>
                    </td>
                    <td>
                        <select id="designation" name="designation" class="form-control" required>
                            <option value="">Select Designation</option>
                            <option value="DRIVER">Driver</option>
                            <option value="CONDUCTOR">Conductor</option>
                            <option value="DRIVER-CUM-CONDUCTOR">Driver-Cum-Conductor</option>
                        </select>
                    </td>
                    <td>
                        <select id="toDivision" name="toDivision" class="form-control" required>
                            <!-- Dynamic options -->
                        </select>
                    </td>
                    <td>
                        <select id="toDepot" name="toDepot" class="form-control" required>
                            <option value="">Select Depot</option>
                        </select>
                    </td>
                    <td><input type="date" id="fromDate" name="fromDate"></td>
                    <td><input type="date" id="toDate" name="toDate"></td>
                    <td>
                        <a onclick="addNewRow()"><i class="fa-solid fa-square-plus fa-2xl" style="color: #0d7000;"></i></a>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type="button" id="submitData" class="btn btn-success mt-3">Submit</button>
    </div>


    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Initialize Select2 with a placeholder
        $("#crewToken").select2({
            placeholder: "Select a Crew Token",
            allowClear: true,
        });

        const division = "<?php echo $division; ?>";
        const depot = "<?php echo $depot; ?>";
        let allDrivers = [];
        let allConductors = [];
        let conductorDataFetched = false;

        // Fetch driver data
        $.get("../includes/data.php", { division: division, depot: depot }, function (response) {
            allDrivers = response.data || [];
            fetchCrewTokens();
        });

        // Fetch conductor data
        $.get("../database/private_emp_api.php", { division: division, depot: depot }, function (response) {
            allConductors = response.data || [];
            conductorDataFetched = true;
            fetchCrewTokens();
        }).fail(function () {
            conductorDataFetched = true;
            fetchCrewTokens();
        });

        // Fetch crew tokens
        async function fetchCrewTokens() {
            if (allDrivers.length === 0 || !conductorDataFetched) return;

            // Clear existing options
            $('#crewToken').empty();

            // Add placeholder option
            $('#crewToken').append(new Option("", "", true, true)).trigger('change');

            const combinedOptions = allConductors.length > 0 ? [...allDrivers, ...allConductors] : allDrivers;
            combinedOptions.forEach(option => {
                const text = `${option.token_number} (${option.EMP_NAME})`;
                const id = option.EMP_PF_NUMBER;
                $('#crewToken').append(new Option(text, id, false, false));
            });
        }

        $(document).ready(function () {
            // Populate divisions on page load
            fetchDivisions();

            // Handle division change to fetch corresponding depots
            $("#toDivision").on("change", function () {
                const divisionName = $(this).val(); // Fetch the division name
                if (divisionName) {
                    fetchDepots(divisionName); // Pass division name
                } else {
                    $("#toDepot").empty().append('<option value="">Select Depot</option>');
                }
            });
        });

        function fetchDivisions() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'POST',
                data: { action: 'fetchdivisioncrew' },
                success: function (response) {
                    const divisions = JSON.parse(response);
                    $("#toDivision").empty().append('<option value="">Select Division</option>');
                    divisions.forEach(division => {
                        $("#toDivision").append(
                            `<option value="${division.division}">${division.division}</option>`
                        );
                    });
                },
                error: function () {
                    alert('Failed to fetch divisions');
                }
            });
        }

        function fetchDepots(divisionName) {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'POST',
                data: { action: 'fetchdepotcrew', division: divisionName }, // Send division name
                success: function (response) {
                    const depots = JSON.parse(response);
                    $("#toDepot").empty().append('<option value="">Select Depot</option>');
                    depots.forEach(depot => {
                        $("#toDepot").append(
                            `<option value="${depot.depot}">${depot.depot}</option>`
                        );
                    });
                },
                error: function () {
                    alert('Failed to fetch depots');
                }
            });
        }

        $(document).ready(function () {
            // Event listener for From Date
            $("#fromDate").on("change", function () {
                const fromDate = new Date($(this).val());
                const toDate = new Date($("#toDate").val());
                const today = new Date();
                today.setHours(0, 0, 0, 0); // Reset time for accurate comparison

                // Check if From Date is less than today's date
                if (fromDate < today) {
                    Swal.fire({
                        icon: "warning",
                        title: "Invalid Date",
                        text: "From Date cannot be less than today's date.",
                    }).then(() => {
                        $(this).val(""); // Reset From Date
                    });
                    return;
                }

                // Check if From Date is greater than To Date (if To Date is selected)
                if ($("#toDate").val() !== "" && fromDate > toDate) {
                    Swal.fire({
                        icon: "error",
                        title: "Invalid Date Range",
                        text: "From Date cannot be greater than To Date.",
                    }).then(() => {
                        $(this).val(""); // Reset From Date
                    });
                }
            });

            // Event listener for To Date
            $("#toDate").on("change", function () {
                const fromDate = new Date($("#fromDate").val());
                const toDate = new Date($(this).val());

                // Check if From Date is selected
                if ($("#fromDate").val() === "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Missing From Date",
                        text: "Please select a valid From Date first.",
                    }).then(() => {
                        $(this).val(""); // Reset To Date
                    });
                    return;
                }

                // Check if To Date is less than From Date
                if (toDate < fromDate) {
                    Swal.fire({
                        icon: "error",
                        title: "Invalid Date Range",
                        text: "To Date cannot be less than From Date.",
                    }).then(() => {
                        $(this).val(""); // Reset To Date
                    });
                }
            });
        });

        function addNewRow() {
            // Get current row data
            const crewToken = document.getElementById('crewToken').value;
            const designation = document.getElementById('designation').value;
            const toDivision = document.getElementById('toDivision').value;
            const toDepot = document.getElementById('toDepot').value;
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;

            // Validate if all fields are filled
            if (!crewToken || !designation || !toDivision || !toDepot || !fromDate || !toDate) {
                alert("Please fill in all fields before adding new crews deptation.");
                return;
            }

            // Get the table body where rows will be added
            const table = document.getElementById('crewTable').getElementsByTagName('tbody')[0];

            // Add the current row's data as a new frozen row
            const newRow = table.insertRow(table.rows.length - 1); // Insert new row before the last input row

            // Fill in the new row with the frozen data
            newRow.innerHTML = `
                            <td class="frozen">${crewToken}</td>
                            <td class="frozen">${designation}</td>
                            <td class="frozen">${toDivision}</td>
                            <td class="frozen">${toDepot}</td>
                            <td class="frozen">${fromDate}</td>
                            <td class="frozen">${toDate}</td>
                            <td class="action-buttons">
                                <button class="btn btn-danger" onclick="deleteRow(this)">Delete</button>
                            </td>
                        `;

            // Reset the input fields for the next entry (keep the input fields in the last row)
            $('#crewToken').val(null).trigger('change');  // Reset the Select2 vehicleNo field
            document.getElementById('designation').value = '';
            document.getElementById('toDivision').value = '';
            document.getElementById('toDepot').value = '';
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
        }

        function deleteRow(button) {
            // Find the parent row of the clicked button and remove it
            const row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
        }
        $("#submitData").on("click", function () {
            const rows = [];
            const tableRows = $("#crewTable tbody tr");

            // Validate the last row (input row with select and text fields)
            const lastRow = tableRows.last();
            const crewToken = lastRow.find("td:eq(0) select").val();
            const designation = lastRow.find("td:eq(1) select").val();
            const toDivision = lastRow.find("td:eq(2) select").val();
            const toDepot = lastRow.find("td:eq(3) select").val();
            const fromDate = lastRow.find("td:eq(4) input").val();
            const toDate = lastRow.find("td:eq(5) input").val();
            let missingFields = [];

            // Check if any of the fields in the last row are empty and add to the missingFields array
            if (!crewToken) missingFields.push("Crew Token");
            if (!designation) missingFields.push("Designation");
            if (!toDivision) missingFields.push("To Division");
            if (!toDepot) missingFields.push("To Depot");
            if (!fromDate) missingFields.push("From Date");
            if (!toDate) missingFields.push("To Date");

            // If there are missing fields, show an error with a list of them
            if (missingFields.length > 0) {
                Swal.fire({
                    icon: "error",
                    title: "Incomplete Data",
                    text: "Please fill in the following fields in the last row: " + missingFields.join(", "),
                });
                return;
            }

            // If the last row is valid, collect all rows except the last input row
            tableRows.each(function (index) {
                const row = $(this).find("td");

                // Skip the last row (since it contains input/select fields)
                if (index === tableRows.length - 1) return;

                const rowData = {
                    crewToken: $(row[0]).text(),
                    designation: $(row[1]).text(),
                    toDivision: $(row[2]).text(),
                    toDepot: $(row[3]).text(),
                    fromDate: $(row[4]).text(),
                    toDate: $(row[5]).text(),
                };
                rows.push(rowData);
            });

            // Add the validated last row to the rows array
            rows.push({
                crewToken: crewToken,
                designation: designation,
                toDivision: toDivision,
                toDepot: toDepot,
                fromDate: fromDate,
                toDate: toDate,
            });

            if (rows.length === 0) {
                Swal.fire({
                    icon: "warning",
                    title: "No Data to Submit",
                    text: "Please add at least one row before submission.",
                });
                return;
            }

            $.ajax({
                url: "../includes/backend_data.php", // Replace with the URL of your server-side script
                type: "POST",
                data: {
                    action: "depotcrewdeputationsubmit", // Action to be handled on the server-side
                    tableData: JSON.stringify(rows), // Sending rows as a JSON string
                },
                success: function (response) {
                    console.log("Response from server: ", response); // Log response for debugging
                    if (response.includes("error")) {
                        Swal.fire({
                            icon: "error",
                            title: "Submission Failed",
                            text: response, // Show the error message if response contains "error"
                        });
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Data Submitted Successfully",
                            text: response, // Success message
                        }).then(() => {
                            // Reload the page after the success message is acknowledged
                            location.reload();
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.log("AJAX Error: ", error);
                    Swal.fire({
                        icon: "error",
                        title: "Submission Failed",
                        text: "There was an error while submitting the data.",
                    });
                }
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