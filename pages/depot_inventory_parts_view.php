<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM') {
    // Allow access
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
?>
<div class="container mt-4">
    <h2 class="text-center">Inventory Items List</h2>
    
    <!-- Category Selection -->
    <div class="d-flex justify-content-center mb-3">
        <label>Select Category:</label>
        <select style="max-width: 300px;" id="categoryFilter" class="form-control">
            <option value="">Select</option>
            <option value="engine">Engine</option>
            <option value="gearbox">Gearbox</option>
            <option value="battery">Battery</option>
            <!--<option value="tyre">Tyre</option>-->
            <option value="starter">Starter</option>
            <option value="alternator">Alternator</option>
            <option value="rear_axle">Rear Axle</option>
        </select>
    </div>

   <div id="inventoryTable"></div>
</div>

<script>
$(document).ready(function () {
    // Function to fetch inventory data
    function fetchInventory(category) {
        $.ajax({
            url: "../includes/backend_data.php",
            type: "POST",
            data: {
                action: "fetch_inventory_basic_data",
                category: category
            },
            success: function (response) {
                $("#inventoryTable").html(response);
            }
        });
}


    // Load all inventory items on page load
    fetchInventory("all");

    // Fetch data on category change
    $("#categoryFilter").change(function () {
        let selectedCategory = $(this).val();
        fetchInventory(selectedCategory);
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