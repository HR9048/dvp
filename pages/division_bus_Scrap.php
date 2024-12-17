<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'DC') {
    // Allow access
    ?>

    <style>
        /* Style for the red color */
        .red {
            color: red;
        }
    </style>
    <marquee behavior="scroll" direction="left">
        <h4 class="red">You are attending to Scrap vehicles from your Division</h4>
    </marquee>
    <!-- HTML form with elements -->
    <div class="container-fluid mt-4" style="max-width: 90%;">
        <div class="border p-6">

            <form method="post">
                <h2 style="text-align:center;">BUS SCRAP</h2>
                <nav class="navbar navbar-light bg-light">
                    <div id="searchBar" class="d-flex align-items-center">
                        <input type="text" id="busSearch" class="form-control mr-sm-2" placeholder="Search Bus Number">
                        <button type="button" class="btn btn-outline-success my-2 my-sm-0"
                            onclick="searchBus()">Search</button>
                    </div>
                </nav>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="vehicle_no">Bus Number:</label>
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
                            <label for="norms">Emission Norms:</label>
                            <input type="text" class="form-control" id="emission_norms" name="emission_norms" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="from_depot">Depot:</label>
                            <input type="text" class="form-control" id="depot" name="depot" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="from_depot">Division:</label>
                            <input type="text" class="form-control" id="division" name="division" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="transfer_order_no">Scrap Order No:</label>
                            <input type="text" class="form-control" id="transfer_order_no" name="transfer_order_no"
                                required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="order_date">Scrap Date:</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" required>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields -->
                <input type="hidden" id="doc" name="doc">
                <input type="hidden" id="wheel_base" name="wheel_base">
                <input type="hidden" id="chassis_number" name="chassis_number">
                <input type="hidden" id="bus_category" name="bus_category">
                <input type="hidden" id="bus_sub_category" name="bus_sub_category">
                <input type="hidden" id="seating_capacity" name="seating_capacity">
                <input type="hidden" id="bus_body_builder" name="bus_body_builder">
                <input type="hidden" id="bus_username" name="bus_username">
                <input type="hidden" id="bus_submit_datetime" name="bus_submit_datetime">
                <input type="hidden" id="depotID" name="depotID">
                <input type="hidden" id="divisionID" name="divisionID">

                <div class="text-center"> <!-- Wrap the button in a div with text-center class -->
                    <button type="button" class="btn btn-primary" onclick="validateAndSubmit()">Submit</button>
                    <script>
                        function validateAndSubmit() {
                            // Get all the form input elements
                            var formInputs = document.querySelectorAll('input[type="text"], input[type="date"]');

                            // Flag to track if any field is empty
                            var allFieldsFilled = true;

                            // Iterate through each form input element
                            formInputs.forEach(function (input) {
                                // Check if the input value is empty
                                if (input.value.trim() === "") {
                                    // If any field is empty, set the flag to false and break out of the loop
                                    allFieldsFilled = false;
                                    return;
                                }
                            });

                            // If any field is empty, display an alert to the user
                            if (!allFieldsFilled) {
                                alert("Please fill in all form fields.");
                            } else {
                                // If all fields are filled, proceed to open the confirmation modal
                                openConfirmationModal();
                            }
                        }
                    </script>

                </div>
            </form>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document"> <!-- Add modal-dialog-centered class -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Scrap Submission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="cancelButton">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Please verify the form details before submitting:</p>
                    <div id="formDetails"></div> <!-- This will display the form details -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelButton">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitFormData()">Submit</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        function openConfirmationModal() {
            // Get form values
            var busNumber = document.getElementById('bus_number').value;
            var make = document.getElementById('make').value;
            var emissionNorms = document.getElementById('emission_norms').value;
            var depot = document.getElementById('depot').value;
            var division = document.getElementById('division').value;
            var scrapOrderNo = document.getElementById('transfer_order_no').value;
            var scrapDate = document.getElementById('order_date').value;
            var depotID = document.getElementById('depotID').value;
            var divisionID = document.getElementById('divisionID').value;

            // Populate modal with form data
            document.getElementById('formDetails').innerHTML =
                "<p><strong>Bus Number:</strong> " + busNumber + "</p>" +
                "<p><strong>Make:</strong> " + make + "</p>" +
                "<p><strong>Emission Norms:</strong> " + emissionNorms + "</p>" +
                "<p><strong>Depot:</strong> " + depot + "</p>" +
                "<p><strong>Division:</strong> " + division + "</p>" +
                "<p><strong>Scrap Order No:</strong> " + scrapOrderNo + "</p>" +
                "<p><strong>Scrap Date:</strong> " + scrapDate + "</p>";

            $('#confirmationModal').modal('show');
        }

        function submitFormData() {
            // Submit form data
            document.getElementById("scrapForm").submit();
        }

        $(document).ready(function () {
            // Intercept form submission
            $('#scrapForm').submit(function (event) {
                event.preventDefault(); // Prevent the default form submission

                // Open the confirmation modal
                openConfirmationModal();
            });

            // Handle cancel button click in modal
            $('#cancelButton').click(function () {
                // Close the modal
                $('#confirmationModal').modal('hide');

                // Show alert
                alert('Scrap canceled.');

                // Redirect to division_buses.php
                window.location.href = 'division_buses.php';
            });
        });
    </script>
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>
<script>
    function searchBus() {
        var busNumber = $('#busSearch').val();

        // AJAX request to fetch data
        $.ajax({
            url: 'division_bus_transfer_serch.php',
            type: 'POST',
            data: { busNumber: busNumber },
            dataType: 'json', // Specify the expected data type as JSON
            // In the success function of your AJAX request
            success: function (response) {
                // Populate all data fields with fetched data
                $('#bus_number').val(response.bus_number);
                $('#division').val(response.division_name);
                $('#divisionID').val(response.divisionID);
                $('#depot').val(response.depot_name);
                $('#depotID').val(response.depotID);
                $('#make').val(response.make);
                $('#emission_norms').val(response.emission_norms);
                $('#doc').val(response.doc);
                $('#wheel_base').val(response.wheel_base);
                $('#chassis_number').val(response.chassis_number);
                $('#bus_category').val(response.bus_category);
                $('#bus_sub_category').val(response.bus_sub_category);
                $('#seating_capacity').val(response.seating_capacity);
                $('#bus_body_builder').val(response.bus_body_builder);
                $('#bus_username').val(response.username);
                $('#bus_submit_datetime').val(response.submit_datetime);

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
                    $('#division').append('<option value="' + value + '">' + value + '</option>');
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
        var confirmTransfer = confirm('Are you sure you want to make the vehicle scrap?');

        // If user confirms
        if (confirmTransfer) {
            // Serialize the form data
            var formData = $('form').serialize();
            console.log("Form Data:", formData);
            // AJAX request to submit form data
            $.ajax({
                url: 'division_bus_scrap_submit.php',
                type: 'POST',
                data: formData,
                dataType: 'json', // Expect JSON response
                success: function (response) {
                    console.log(response); // Log the response for debugging
                    if (response.status === 'success') {
                        // Handle success response
                        alert('Vehicle scrap Successfully!');
                        location.reload();
                    } else {
                        // Handle error response
                        alert('An error occurred: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    // Handle AJAX error
                    alert('An error occurred while submitting the form: ' + error);
                }
            });
        } else {
            // Exit if user cancels the transfer
            alert('Scrap canceled.');
            window.location.href = 'division_buses.php';

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