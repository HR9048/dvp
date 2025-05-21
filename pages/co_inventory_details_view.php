<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ( ($_SESSION['JOB_TITLE'] == 'CME_CO') || ($_SESSION['JOB_TITLE'] == 'WM') ) {
    ?>

    <style>
        .container1 {
            transform: none !important;
            direction: ltr !important;
            text-align: left !important;

        }

        .container1 {
            transform: scaleX(-1);
            /* BAD */
            direction: rtl;
            /* Also can cause mirror-like behavior */
        }
    </style>
    <!-- create a new depot bus inventory view page -->
    <div class="container">
        <table id="dataTable">
            <thead>
                <tr>
                    <th colspan="8" class="text-center">Depot Bus Inventory</th>
                </tr>
                <tr>
                    <th>Vehicle Card No</th>
                    <th>Engine Card No</th>
                    <th>Gear Box Card No</th>
                    <th>FIP/HPP Card No</th>
                    <th>Alternator Card No</th>
                    <th>Starter Card No</th>
                    <th>Battery Card No</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT 
    bi.bus_number,l.depot,
    em.engine_card_number AS engine_no,
    gm.gear_box_card_number AS gear_box_no,
    fm.fip_hpp_card_number AS fip_hpp_no,
    am.alternator_card_number AS alternator_no,
    sm.starter_card_number AS starter_no,
    CONCAT_WS(', ', bm1.battery_card_number, bm2.battery_card_number) AS battery_no,
    bi.id
FROM bus_inventory bi
LEFT JOIN engine_master em ON bi.engine_id = em.id
LEFT JOIN gearbox_master gm ON bi.gearbox_id = gm.id
LEFT JOIN fip_hpp_master fm ON bi.fiphpp_id = fm.id
LEFT JOIN alternator_master am ON bi.alternator_id = am.id
LEFT JOIN starter_master sm ON bi.starter_id = sm.id
LEFT JOIN battery_master bm1 ON bi.battery_1_id = bm1.id
LEFT JOIN battery_master bm2 ON bi.battery_2_id = bm2.id
LEFT JOIN location l on bi.depot_id = l.depot_id";
                $result = mysqli_query($db, $query);
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['bus_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['engine_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['gear_box_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['fip_hpp_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['alternator_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['starter_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['battery_no']) . "</td>";
                        echo "<td><button class='btn btn-primary btn-sm' onclick=\"viewDetails(" . $row['id'] . ")\">View Details</button></td>";
                        // Add more columns as needed
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function viewDetails(id) {
            $.ajax({
                url: '../includes/backend_data.php',
                type: 'POST',
                data: {
                    action: 'get_bus_details_for_inventory_view',
                    id: id
                },
                success: function(response) {
                    $('#container1').html(response);
                    $('#detailsModal').modal('show');
                },
                error: function() {
                    alert("Error fetching details.");
                }
            });
        }
    </script>
    <!-- Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 100%; width: 100%; margin: 0;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Bus Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="container1" style="text-align: left; direction: ltr;">

                    <div class="modal-body" id="container1">
                        <!-- Fetched content will be injected here -->
                    </div>

                </div>
                <div class="modal-footer" style="justify-content: space-between;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
            </div>
        </div>
    </div>


    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>