<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'DEPOT' && ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM')) {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    /*$bus_query1 = "SELECT bus_number FROM bus_registration WHERE depot_name = $depot_id AND division_name = $division_id AND model_type IS NULL";
    $bus_result1 = mysqli_query($db, $bus_query1);

    if (mysqli_num_rows($bus_result1) > 1) {
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Data',
                text: 'The required data for the bus type is not updated yet. Please update the data first.',
                confirmButtonText: 'Go to Update Page'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'depot_bus_type_update.php';
                }
            });
        </script>";
        exit;
    }*/

    // Fetch program types dynamically
    $program_types = [];
    $program_column_flags = [];
    $column_query = "SHOW COLUMNS FROM program_master";
    $column_result = mysqli_query($db, $column_query);
    $exclude_columns = ['id', 'make', 'model', 'model_type', 'created_at', 'updated_at'];

    while ($column = mysqli_fetch_assoc($column_result)) {
        $field = $column['Field'];
        if (!in_array($field, $exclude_columns)) {
            $program_types[] = $field;
            $program_column_flags[$field] = false;
        }
    }

    $air_suspension_bus_category_array = ['Rajahamsa', 'Corona Sleeper AC', 'Sleeper AC', 'Regular Sleeper Non AC', 'Amoghavarsha Sleeper Non AC', 'Kalyana Ratha'];

    $bus_query = "SELECT br.bus_number, br.make, br.emission_norms, br.bus_progressive_km_31032025, br.model_type, bs.bus_type, bs.bus_category, br.bus_sub_category FROM bus_registration br left join bus_seat_category bs on bs.bus_sub_category= br.bus_sub_category WHERE br.depot_name = $depot_id AND br.division_name = $division_id";
    $bus_result = mysqli_query($db, $bus_query);

    $all_rows = [];
    while ($bus = mysqli_fetch_assoc($bus_result)) {
        $bus_number = $bus['bus_number'];
        $make = $bus['make'];
        $emission = $bus['emission_norms'];
        $model = $bus['model_type'];
        $bus_progressive_km = $bus['bus_progressive_km_31032025'];
        $bus_type = $bus['bus_type'];
        $bus_category = $bus['bus_category'];
        $bus_sub_category = $bus['bus_sub_category'];

        $program_query = "SELECT program_type, program_completed_km FROM program_data WHERE bus_number = '$bus_number'";
        $program_result = mysqli_query($db, $program_query);

        $program_data = [];
        while ($row = mysqli_fetch_assoc($program_result)) {
            $program_data[$row['program_type']] = $row['program_completed_km'];
        }

        $prog_val_query = "SELECT * FROM program_master WHERE make = '$make' AND model = '$emission' AND model_type = '$model' LIMIT 1";
        $prog_val_result = mysqli_query($db, $prog_val_query);
        $prog_val_row = mysqli_fetch_assoc($prog_val_result);

        foreach ($program_types as $type) {
            if ($prog_val_row && !is_null($prog_val_row[$type])) {
                $program_column_flags[$type] = true;
            }
        }

        $all_rows[] = [
            'bus_number' => $bus_number,
            'bus_progressive_km' => $bus_progressive_km,
            'bus_sub_category' => $bus_sub_category,
            'program_data' => $program_data,
            'prog_val_row' => $prog_val_row
        ];
    }

    $visible_program_types = array_filter($program_types, function ($type) use ($program_column_flags) {
        return $program_column_flags[$type];
    });
?>
    <style>
        .km-input {
            max-width: 100px;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 6px;
            text-align: center;
            font-size: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .km-input {
            width: 70px;
            font-size: 12px;
        }

        .date-input {
            width: 95px;
            font-size: 12px;
        }

        .sticky-header-table thead th {
            position: sticky;
            top: 0;
            background-color: #fff;
            /* or your preferred background */
            z-index: 2;
        }


        .table-wrapper {
            max-height: 500px;
            /* or adjust as needed */
            overflow-y: auto;
            border: 1px solid #ccc;
        }

        /* Sticky headers - both header rows */
        .sticky-header-table thead tr:first-child th {
            position: sticky;
            top: 0;
            background: #f8f8f8;
            z-index: 2;
        }

        .sticky-header-table thead tr:nth-child(2) th {
            position: sticky;
            top: 35px;
            /* Adjust this height based on your first row's height */
            background: #f8f8f8;
            z-index: 1;
        }

        /* Basic table styles */
        .sticky-header-table {
            border-collapse: collapse;
            width: 100%;
        }

        .sticky-header-table th,
        .sticky-header-table td {
            border: 1px solid #444;
            padding: 6px;
            text-align: center;
            font-size: 10px;
            white-space: nowrap;
        }
    </style>
    <h2>Bus Program KM as on 31-07-2025</h2>
    <div class="table-wrapper">
        <div class="container1">
            <table class="sticky-header-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Bus Number</th>
                        <!--<th>Bus Progressive KM<br>as on 31-03-25</th>-->
                        <?php foreach ($visible_program_types as $type): ?>
                            <th><?= ucwords(str_replace("_", " ", $type)) ?></th>
                        <?php endforeach; ?>

                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sn = 1;
                    foreach ($all_rows as $row_data) {
                        $bus_number = $row_data['bus_number'];
                        $bus_progressive_km = $row_data['bus_progressive_km'];
                        $bus_sub_category = $row_data['bus_sub_category']; // ← Get per-bus subcategory
                        $program_data = $row_data['program_data'];
                        $prog_val_row = $row_data['prog_val_row'];

                        echo "<tr id='row-$bus_number'>";
                        echo "<td>" . $sn++ . "</td>";
                        echo "<td>" . htmlspecialchars($bus_number) . "</td>";
                        //echo "<td>" . htmlspecialchars($bus_progressive_km) . "</td>";

                        foreach ($visible_program_types as $type) {
                            // Handle air_suspension program type with strict subcategory match
                            if ($type === 'air_suspension_check') {
                                // If bus_sub_category is in allowed array, show input or value
                                if (in_array($bus_sub_category, $air_suspension_bus_category_array)) {
                                    if (!$prog_val_row || is_null($prog_val_row[$type])) {
                                        echo "<td>NA</td>";
                                    } elseif (isset($program_data[$type])) {
                                        echo "<td>" . htmlspecialchars($program_data[$type]) . "</td>";
                                    } else {
                                        echo "<td><input type='number' class='km-input' name='{$type}_km[{$bus_number}]' placeholder='KM'></td>";
                                    }
                                } else {
                                    // Not in array → Always show NA
                                    echo "<td>NA</td>";
                                }
                            } else {
                                // All other program types
                                if (!$prog_val_row || is_null($prog_val_row[$type])) {
                                    echo "<td>NA</td>";
                                } elseif (isset($program_data[$type])) {
                                    echo "<td>" . htmlspecialchars($program_data[$type]) . "</td>";
                                } else {
                                    echo "<td><input type='number' class='km-input' name='{$type}_km[{$bus_number}]' placeholder='KM'></td>";
                                }
                            }
                        }
                        echo "<td><button style='background-color: blue; color: white; padding: 4px 10px; border: none; border-radius: 4px; cursor: pointer;' onclick=\"updateProgramData('$bus_number', document.getElementById('row-$bus_number'))\">Update</button></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div><br>
<button class="btn btn-success" id="downloadExcel">Download Excel</button>

    <script>
        function updateProgramData(busNumber, rowElement) {
            const data = {
                action: 'update_program_data',
                bus_number: busNumber,
                programs: {}
            };

            let isValid = true;
            let errorMessages = [];

            // Collect all KM inputs in the row
            $(rowElement).find("input[type='number']").each(function() {
                const nameAttr = $(this).attr('name');
                const value = $(this).val()?.trim();

                // Example name format: 'docking_km[KA01AA0001]'
                const match = nameAttr?.match(/^(.+?)_km\[(.+?)\]$/);
                if (match && value) {
                    const programType = match[1]; // e.g., 'docking_km'
                    data.programs[programType] = value;
                }
            });

            // Optional validation if needed
            if (Object.keys(data.programs).length === 0) {
                isValid = false;
                errorMessages.push("No KM data entered.");
            }

            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: errorMessages.join('\n'),
                });
                return;
            }

            // Log data being sent
            //console.log("Sending data to server:", data);

            // AJAX POST to backend
            $.ajax({
                url: '../includes/program_data_update.php',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    console.error("AJAX Error:", xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Update failed. Check console for details.'
                    });
                }
            });
        }
        document.getElementById('downloadExcel').addEventListener('click', function () {
    // Select the actual table inside the container
    var originalTable = document.querySelector('.container1 table');

    if (!originalTable) {
        alert("Table not found inside .container1");
        return;
    }

    // Clone the table
    var clonedTable = originalTable.cloneNode(true);

    // Remove the last column (cell) from each row
    Array.from(clonedTable.rows).forEach(function (row) {
        if (row.cells.length > 0) {
            row.deleteCell(row.cells.length - 1);
        }
    });

    // Convert to workbook and export
    var workbook = XLSX.utils.table_to_book(clonedTable);
    XLSX.writeFile(workbook, 'Last-maintenance-km-update.xlsx');
});


    </script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>