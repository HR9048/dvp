<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech')) {
    $division = $_SESSION['DIVISION_ID'];
    $depot = $_SESSION['DEPOT_ID'];

    // Query the database to fetch bus numbers based on depot_name and division_name
    $query = "SELECT bus_number FROM bus_registration WHERE depot_name =  ? AND division_name =  ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $depot, $division);
    $stmt->execute();
    $result = $stmt->get_result();

    // Store the bus numbers in an array
    $bus_numbers = [];
    while ($row = $result->fetch_assoc()) {
        $bus_numbers[] = $row['bus_number'];
    }
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
                    <th>Vehicle Number</th>
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
                        <select id="vehicleNo" name="vehicleNo" class="form-control select2" required>
                            <option value="">Select Vehicle</option> <!-- Default option -->
                            <?php
                            // Loop through the fetched bus numbers and populate the options
                            foreach ($bus_numbers as $bus_number) {
                                echo "<option value=\"$bus_number\">$bus_number</option>";
                            }
                            ?>
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
        $(document).ready(function () {
            // Initialize Select2 for vehicle numbers
            $("#vehicleNo").select2({
                placeholder: "Select a Vehicle No",
                allowClear: true,
            });
        });


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
            const vehicleNo = document.getElementById('vehicleNo').value;
            const toDivision = document.getElementById('toDivision').value;
            const toDepot = document.getElementById('toDepot').value;
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;

            // Validate if all fields are filled
            if (!vehicleNo || !toDivision || !toDepot || !fromDate || !toDate) {
                alert("Please fill in all fields before adding new crews deptation.");
                return;
            }

            // Get the table body where rows will be added
            const table = document.getElementById('crewTable').getElementsByTagName('tbody')[0];

            // Add the current row's data as a new frozen row
            const newRow = table.insertRow(table.rows.length - 1); // Insert new row before the last input row

            // Fill in the new row with the frozen data
            newRow.innerHTML = `
            <td class="frozen">${vehicleNo}</td>
            <td class="frozen">${toDivision}</td>
            <td class="frozen">${toDepot}</td>
            <td class="frozen">${fromDate}</td>
            <td class="frozen">${toDate}</td>
            <td class="action-buttons">
                <button class="btn btn-danger" onclick="deleteRow(this)">Delete</button>
            </td>
        `;

            // Reset the input fields for the next entry (keep the input fields in the last row)
            $('#vehicleNo').val(null).trigger('change');  // Reset the Select2 vehicleNo field
            document.getElementById('toDivision').value = '';  // Reset the Division select input
            document.getElementById('toDepot').value = '';    // Reset the Depot select input
            document.getElementById('fromDate').value = '';   // Reset the From Date input
            document.getElementById('toDate').value = '';     // Reset the To Date input
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
            const vehicleNo = lastRow.find("td:eq(0) select").val();
            const toDivision = lastRow.find("td:eq(1) select").val();
            const toDepot = lastRow.find("td:eq(2) select").val();
            const fromDate = lastRow.find("td:eq(3) input").val();
            const toDate = lastRow.find("td:eq(4) input").val();
            let missingFields = [];

            // Check if any of the fields in the last row are empty and add to the missingFields array
            if (!vehicleNo) missingFields.push("vehicle No");
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
                    vehicleNo: $(row[0]).text(),
                    toDivision: $(row[1]).text(),
                    toDepot: $(row[2]).text(),
                    fromDate: $(row[3]).text(),
                    toDate: $(row[4]).text(),
                };
                rows.push(rowData);
            });

            // Add the validated last row to the rows array
            rows.push({
                vehicleNo: vehicleNo,
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
                    action: "depotvehicledeputationsubmit", // Action to be handled on the server-side
                    tableData: JSON.stringify(rows), // Sending rows as a JSON string
                },
                success: function (response) {
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