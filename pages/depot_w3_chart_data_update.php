<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM')) {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id1 = $_SESSION['KMPL_DIVISION'];
    $depot_id1 = $_SESSION['KMPL_DEPOT'];
?>
    <style>
        .table-container {
            max-width: 100%;
            overflow-x: auto;
        }

        table {
            width: 60%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        th,
        td {
            text-align: left;
            padding: 8px;
            white-space: nowrap;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .hidden {
            display: none;
        }

        #loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            justify-content: center;
            align-items: center;
            font-size: 20px;
            font-weight: bold;
        }
    </style>
    <div id="loading">
        Loading, please wait...
    </div>

    <div id="page-content">
        <form id="busReportForm" method="POST" onsubmit="return validateAndSubmit1();">
            <label for="reportDate">Select Date:</label>
            <input type="date" name="report_date" id="reportDate" required>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <div id="loading" style="display: none;">
        <p>Loading, please wait...</p>
    </div>

    <script>
        document.getElementById('reportDate').addEventListener('change', function () {
    const selectedDate = new Date(this.value);
    const minDate = new Date('2025-08-01');

    if (selectedDate < minDate) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Date',
            text: 'Please select a date on or after 01-08-2025.',
            confirmButtonColor: '#3085d6'
        });

        // Optional: Clear the invalid date
        this.value = '';
    }
});
        function validateAndSubmit1() {
            let reportDate = document.getElementById('reportDate').value;
            if (!reportDate) {
                Swal.fire('Error', 'Please select a date.', 'error');
                return false;
            }

            let selectedDate = new Date(reportDate);
            let today = new Date();
            let yesterday = new Date();
            let fourDaysAgo = new Date();

            yesterday.setDate(today.getDate());
            fourDaysAgo.setDate(today.getDate() - 10);

            // Convert dates to 'YYYY-MM-DD' format for accurate comparison
            let selectedDateString = selectedDate.toISOString().split('T')[0];
            let yesterdayString = yesterday.toISOString().split('T')[0];
            let fourDaysAgoString = fourDaysAgo.toISOString().split('T')[0];

            if (selectedDateString > yesterdayString || selectedDateString < fourDaysAgoString) {
                Swal.fire('Date Outside Allowed Range',
                    `Date must be between ${fourDaysAgo.toLocaleDateString('en-GB')} and ${yesterday.toLocaleDateString('en-GB')}.`,
                    'error'
                );
                return false; // Prevent submission
            }

            // If date is valid, show loading screen and hide content
            document.getElementById("loading").style.display = "flex";
            document.getElementById("page-content").style.display = "none";

            return true; // Allow form submission
        }

        window.onload = function() {
            document.getElementById("loading").style.display = "none";
            document.getElementById("page-content").style.display = "block";
        };
        $(document).ready(function() {
            $('.operation-select').select2();
        });
    </script>

    <form method="post">
        <div id="w3reportTable" style="margin-top: 20px;">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_date'])) {
                $report_date = $_POST['report_date'];
                $division_id = $_SESSION['DIVISION_ID'];
                $depot_id = $_SESSION['DEPOT_ID'];
                echo '<script>document.getElementById("loading").style.display = "flex";</script>';
                echo '<script>document.getElementById("page-content").style.display = "none";</script>';
                ob_flush();
                flush();
                // Run your script here (fetch data, process, etc.)
                sleep(3); // Simulating processing time

                // Fetch bus numbers
                $bus_data_fetch_query = "SELECT br.bus_number FROM bus_registration br WHERE br.division_name = '$division_id' AND br.depot_name = '$depot_id' UNION
                SELECT vd.bus_number FROM vehicle_deputation vd LEFT JOIN bus_registration br ON vd.bus_number = br.bus_number WHERE vd.t_division_id = '$division_id' AND vd.t_depot_id = '$depot_id' AND vd.tr_date = '$report_date' AND vd.status NOT IN (1) AND vd.deleted = 0";
                $bus_data_fetch_result = $db->query($bus_data_fetch_query);


                $w3chartquery = "SELECT * FROM W3_chart_data WHERE division_id = '$division_id' AND depot_id = '$depot_id' AND report_date = '$report_date' and deleted = '0'";
                $w3chartResult = $db->query($w3chartquery);

                $w3Data = [];
                while ($w3Row = $w3chartResult->fetch_assoc()) {
                    $w3Data[$w3Row['bus_number']] = $w3Row;
                }

                // Fetch all vehicle_kmpl data for that date and depot/division
                $kmpl_query = "SELECT bus_number FROM vehicle_kmpl WHERE division_id = '$division_id' AND depot_id = '$depot_id' AND date = '$report_date' AND deleted = '0'";

                $kmpl_result = $db->query($kmpl_query);
                $kmplData = [];

                while ($row = $kmpl_result->fetch_assoc()) {
                    $kmplData[$row['bus_number']] = true;
                }

                $formatted_date = date("d-m-Y", strtotime($report_date));
                if ($bus_data_fetch_result && $bus_data_fetch_result->num_rows > 0) {
                    echo '<div class="table-container"><h1 class="text-center">Division: ' . $_SESSION['DIVISION'] . '  Depot: ' . $_SESSION['DEPOT'] . ' W3 Chart Data Entry for Date: ' . htmlspecialchars($formatted_date) . '</h1>';
                    echo '<table id="w3_chart_data_table" border="1" cellpadding="5" cellspacing="0">';
                    echo '<tr>
                        <th>Sl. No.</th>
                        <th>Bus Number</th>
                        <th>Operation Type</th>
                        <th class="hidden">Division</th>
                        <th class="hidden">Depot</th>
                        <th class="hidden">ID</th>
                        <th>Action</th>
                      </tr>';

                    $sl_no = 1;


                    while ($busRow = $bus_data_fetch_result->fetch_assoc()) {
                        $bus_number = $busRow['bus_number'];

                        // Fetch vehicle_kmpl data for this bus (if exists)
                        $existingData = $w3Data[$bus_number] ?? null;

                        echo '<tr>';
                        echo '<td>' . $sl_no++ . '</td>';
                        echo '<td>' . htmlspecialchars($bus_number) . '</td>';

                        // Route number select
                        echo '<td>';
                        echo '<select class="operation-select">';
                        echo '<option style="width:100%;" value="">Select</option>'; // Default option

                        $fixedOptions = ['KM Added','Night Out', 'Extra', 'Off-Road', 'DWS', 'RWY', 'RTO', 'BD', 'CC', 'Spare', 'Fair/Jatra', 'Police Station', 'Other Depot', 'Not Arrived', 'Docking', 'Others'];
                        foreach ($fixedOptions as $opt) {
                            $selected = '';

                            if (!empty($existingData['operation_type'])) {
                                $selected = ($existingData['operation_type'] == $opt) ? 'selected' : '';
                            } elseif (isset($kmplData[$bus_number]) && $opt === 'KM Added') {
                                $selected = 'selected';
                            }

                            echo '<option value="' . htmlspecialchars($opt) . '" ' . $selected . '>' . htmlspecialchars($opt) . '</option>';
                        }
                        echo '</select>';
                        echo '</td>';




                        echo '<td class="hidden">' . $division_id . '</td>';
                        echo '<td class="hidden">' . $depot_id . '</td>';
                        echo '<td class="hidden">' . ($existingData['id'] ?? '') . '</td>';

                        echo '<td>';
                        if (!empty($existingData['id'])) {
                            echo ' <button style="width:30%;" type="button" class="delete-btn btn btn-danger" onclick="deleteRow(' . $existingData['id'] . ')">
                                    <i class="fas fa-trash"></i></button> &nbsp;<button style="width:65%;" type="button" class="update-btn btn btn-primary">Update</button></td>';
                        } else {
                            echo ' <button style="width:100%;" type="button" class="update-btn btn btn-primary">Update</button></td>';
                        }
                        echo '</tr>';
                    }

                    echo '</table><br>';
                    echo '<div class="text-center my-3">';
                    echo '<button id="submitBtn" class="btn btn-success">Submit</button>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<p>No buses available for the selected depot and division.</p>';
                }
            }
            ?>
        </div>
    </form>
    <script>
        function deleteRow(w3Id) {
            Swal.fire({
                title: "Are you sure?",
                text: "This action cannot be undone and page will refresh!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "../includes/backend_data.php", // Ensure correct file path
                        type: "POST",
                        data: {
                            action: "delete_w3_single", // Passing action
                            w3_delete_id: w3Id
                        },
                        dataType: "json",
                        success: function(response) {

                            if (response.status === "success") {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: response.message,
                                    icon: "success",
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload(); // Reload the page after deletion
                                });
                            } else {
                                Swal.fire("Error!", response.message, "error");
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire("Error!", "Something went wrong: " + error, "error");
                        }
                    });
                }
            });
        }
        $(document).ready(function() {
            $("#w3_chart_data_table").on("click", ".update-btn", function() {
                let button = $(this);
                let row = $(this).closest("tr"); // Get clicked row

                button.prop("disabled", true).text("Updating...");

                function getCellValue(cellSelector, isSelect = false) {
                    let element = row.find(cellSelector);
                    if (isSelect) {
                        return element.length ? element.val()?.trim() || "" : "";
                    } else {
                        return element.find("input").length ?
                            element.find("input").val()?.trim() || "" :
                            element.text().trim();
                    }
                }

                let id = getCellValue("td:nth-child(6)");
                let busNumber = getCellValue("td:nth-child(2)");
                let operationtype = row.find("td:nth-child(3) select option:selected").val()?.trim() || "";
                let division_id = getCellValue("td:nth-child(4)");
                let depot_id = getCellValue("td:nth-child(5)");
                const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>";

                if (!reportDate) {
                    alert("Report date is missing. Please select a valid date.");
                    button.prop("disabled", false).text("Update"); // Re-enable button
                    return;
                }

                let missingFields = [];
                if (!busNumber) missingFields.push("Bus Number");
                if (!operationtype) missingFields.push("Operation Type");


                if (missingFields.length > 0) {
                    Swal.fire({
                        icon: "warning",
                        title: "Validation Error",
                        html: `<b>The following fields are missing:</b><br><br>
                       <ul style="text-align:left;">
                           ${missingFields.map((field) => `<li>${field}</li>`).join("")}
                       </ul>`,
                    });
                    button.prop("disabled", false).text("Update"); // Re-enable button
                    return;
                }

                // ✅ AJAX Request
                $.ajax({
                    url: "../includes/backend_data.php",
                    type: "POST",
                    data: {
                        action: "insertupdatesinglevehiclekmpl",
                        id: id,
                        bus_number: busNumber,
                        operation_type: operationtype,
                        reportDate: reportDate,
                        division_id: division_id,
                        depot_id: depot_id
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === "success") {
                            row.find("td:nth-child(6)").text(response.id);

                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                        button.prop("disabled", false).text("Update"); // Re-enable button
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'AJAX Error',
                            text: "Check console for details"
                        });
                        button.prop("disabled", false).text("Update"); // Re-enable button
                    }
                });
            });
        });

        $(document).ready(function() {
            $("#submitBtn").click(function(event) {
                event.preventDefault(); // Prevents page reload
                let button = $(this);
                button.prop("disabled", true).text("Submitting..."); // Disable and change text
                validateAndSubmit(button); // Pass the button reference
            });
        });

        function validateAndSubmit(button) {
            const rows = document.querySelectorAll('#w3reportTable tr'); // Get all rows
            let validRows = []; // To store valid row data
            let hasData = false; // Flag to check if any row has data
            const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>";

            // Loop through each row except the last one (total row)
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const inputs = row.querySelectorAll('input, select');

                // Extract values from the row
                const operation_type = row.querySelector('td:nth-child(3) select').value?.trim() || "";
                const division_id = row.querySelector('td:nth-child(4)').innerText;
                const depot_id = row.querySelector('td:nth-child(5)').innerText;
                const id = row.querySelector('td:nth-child(6)').innerText;
                const reportDate = "<?php echo isset($report_date) ? $report_date : ''; ?>";
                if (!reportDate) {
                    alert("Report date is missing. Please select a valid date.");
                    button.prop("disabled", false).text("Submit");
                    return;
                }

                // Check if any required field is filled
                if (operation_type) {
                    hasData = true;

                    let missingFields = [];

                    if (!operation_type) missingFields.push("Operation Type");
                    if (!division_id) missingFields.push("Division ID");
                    if (!depot_id) missingFields.push("Depot ID");
                    //if (!id) missingFields.push("ID");
                    // If any required field is missing, show error
                    if (missingFields.length > 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Incomplete Row',
                            html: `Row ${i} is incomplete.<br> Missing Fields: <b>${missingFields.join(", ")}</b>`,
                        });
                        button.prop("disabled", false).text("Submit"); // Re-enable button
                        return;
                    }



                    // Push valid row data
                    validRows.push({
                        report_date: reportDate,
                        bus_number: row.querySelector('td:nth-child(2)').innerText,
                        operation_type: operation_type,
                        division_id: division_id,
                        depot_id: depot_id,
                        id: id || null
                    });
                }
            }




            if (!hasData) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Data',
                    text: 'No valid data entered in the table.'
                });
                button.prop("disabled", false).text("Submit"); // Re-enable button
                return;
            }
            // Proceed with form submission (AJAX or other method)

            // If all rows are valid, submit via AJAX
            submitData(validRows, button);
        }

        function submitData(data, button) {


            console.log("Submitting data:", data);

            // Prepare flat POST data
            const postData = {
                action: 'insertvehiclew3data',
                ...data
            };

            $.ajax({
                url: '../includes/backend_data.php',
                method: 'POST',
                data: postData, // Not JSON.stringify
                success: function(response) {
                    try {
                        const jsonResponse = typeof response === "string" ? JSON.parse(response) : response;

                        if (jsonResponse.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'KMPL Data Added successfully!',
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error: ' + jsonResponse.message,
                            });
                            button.prop("disabled", false).text("Submit");
                            console.error('Error response:', jsonResponse);
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Invalid server response. Please try again.',
                        });
                        console.error('Invalid JSON:', response);
                        button.prop("disabled", false).text("Submit");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: 'Request failed. Please check your connection.',
                    });
                    console.error('AJAX error:', textStatus, errorThrown);
                    button.prop("disabled", false).text("Submit");
                }
            });
        }
    </script>


<?php
} else {
    echo "<script>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php'; ?>