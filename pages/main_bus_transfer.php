<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    ?>
    <style>
        /* Style for the red color */
        .red {
            color: red;
        }
    </style>
    <marquee behavior="scroll" direction="left">
        <h4 class="red">You are attending to transfer vehicle from one division depot to another division depot within your
            Corporation</h4>
    </marquee>
    <!-- HTML form with elements -->
    <div class="container mt-4" style="width:80%;">
        <form method="post">
            <h2>BUS TRANSFER</h2>
            <nav class="navbar navbar-light bg-light">
                <div id="searchBar" class="d-flex align-items-center">
                    <input type="text" id="busSearch" class="form-control mr-sm-2" placeholder="Search Bus Number">
                    <button type="button" class="btn btn-outline-success my-2 my-sm-0" onclick="searchBus()">Search</button>
                </div>
            </nav>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="bus_number">Bus Number:</label>
                        <input type="text" class="form-control" id="bus_number" name="bus_number" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="make">Make:</label>
                        <input type="text" class="form-control" id="make" name="make" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="emission_norms">Emission Norms:</label>
                        <input type="text" class="form-control" id="emission_norms" name="norms" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="from_depot">From Division:</label>
                        <input type="text" class="form-control" id="from_division" name="from_division" readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="from_depot">From Depot:</label>
                        <input type="text" class="form-control" id="from_depot" name="from_depot" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="from_depot">To Division:</label>
                        <select class="form-control" id="division" name="division" required>
                            <option value="">Select Division</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="to_depot">To Depot:</label>
                        <select class="form-control" id="to_depot" name="to_depot" required>
                            <option value="">Select Depot</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="transfer_order_no">Transfer Order No:</label>
                        <input type="text" class="form-control" id="transfer_order_no" name="transfer_order_no" required>
                    </div>
                </div>
            </div>
            <input type="hidden" id="from_depot_id" name="from_depot_id">
            <input type="hidden" id="from_division_id" name="from_division_id">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="order_date">Order Date:</label>
                        <input type="date" class="form-control" id="order_date" name="order_date" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script>
        function searchBus() {
            var busNumber = $('#busSearch').val();

            // AJAX request to fetch data
            $.ajax({
                url: 'main_bus_transfer_serch.php',
                type: 'POST',
                data: { busNumber: busNumber },
                dataType: 'json', // Specify the expected data type as JSON
                success: function (response) {
                    // Populate form fields with fetched data
                    $('#bus_number').val(response.bus_number);
                    $('#make').val(response.make);
                    $('#emission_norms').val(response.emission_norms);
                    $('#from_depot').val(response.depot_name);
                    $('#from_depot_id').val(response.depotID);
                    $('#from_division').val(response.division_name);
                    $('#from_division_id').val(response.divisionID);

                    // Trigger change event on division dropdown to fetch depot options
                    $('#division').change();
                },
                error: function (xhr, status, error) {
                    // Display error message
                    if (xhr.status === 403) {
                        alert(xhr.responseJSON.error);
                    } else {
                        alert('Error: Bus not Registered in KKRTC.');
                    }
                }
            });
        }
        // Function to handle Enter key press in search input field
        $('#busSearch').keypress(function (event) {
            // Check if the Enter key was pressed
            if (event.which == 13) {
                // Prevent default form submission behavior
                event.preventDefault();
                // Trigger search function
                searchBus();
            }
        });
        function fetchDivision() {
            // Fetch bus categories on page load
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchDivision' },
                success: function (response) {
                    var divisions = JSON.parse(response);
                    $.each(divisions, function (index, value) {
                        if (value.division_id != 0 && value.division_id != 10) {
                            $('#division').append('<option value="' + value.division_id + '">' + value.DIVISION + '</option>');
                        }
                    });
                }
            });

            // Fetch bus sub-categories based on selected bus category
            $('#division').change(function () {
                var division = $(this).val();
                $.ajax({
                    url: '../includes/data_fetch.php?action=fetchDepot',
                    method: 'POST',
                    data: { division: division },
                    success: function (data) {
                        $('#to_depot').html(data);
                    }
                });
            });
        }

        // Call the function to fetch data on page load
        $(document).ready(function () {
            fetchDivision();
        });


        function submitFormData() {
            // Display confirmation dialog
            var confirmTransfer = confirm('Are you sure you want to transfer the bus?');

            // If user confirms
            if (confirmTransfer) {
                // Serialize the form data
                var formData = $('form').serialize();
                // AJAX request to submit form data
                $.ajax({
                    url: 'main_bus_transfer_submit.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json', // Expect JSON response
                    success: function (response) {
                        if (response.status === 'success') {
                            // Handle success response
                            alert('Vehicle Transfered Successfully!');
                            location.reload();
                        } else {
                            // Handle error response
                            alert('An error occurred: ' + response.message);
                            location.reload();
                        }
                    },
                    error: function (xhr, status, error) {
                        // Handle AJAX error
                        alert('An error occurred while submitting the form.');
                    }
                });
            } else {
                // Exit if user cancels the transfer
                alert('Transfer canceled.');
            }
        }

        // Event listener for form submission
        $('form').submit(function (event) {
            // Prevent default form submission behavior
            event.preventDefault();
            // Call the function to submit form data via AJAX
            submitFormData();
        });

    </script>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>