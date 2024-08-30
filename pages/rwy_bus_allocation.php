<?php
include '../includes/connection.php';
include '../includes/rwy_sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'RWY' && $_SESSION['JOB_TITLE'] == 'WM!') {
    // Allow access
    ?>

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <button><a href="rwy_bus_allocate_view.php">View Allocated buses</a></button>
    <!-- Your custom CSS -->
    <style>
        body {
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: auto;
        }

        .text-danger {
            color: red;
            margin-left: 5px;
            /* Add some spacing */
            font-size: 20px;
        }

        .form-group label {
            display: flex;
            align-items: center;
        }
    </style>
    <div class="container">
        <h2 class="text-center">Bus Registration Form</h2>
        <small class="text-danger">* Indicates Required field</small>
        <form action="submit_bus.php" method="POST">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="division">Division:<span class="text-danger">*</span></label>
                        <select class="form-control" id="division" name="division" required>
                            <option value="">Select Division</option>
                            <!-- Division options will be populated dynamically -->
                        </select>
                    </div>

                </div>
                <div class="col">
                    <div class="col">
                        <div class="form-group">
                            <label for="seating_type">Make:<span class="text-danger">*</span></label>
                            <select class="form-control" id="make" name="make" required>
                                <option value="">Select Make</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="seating_type">Emission norms:<span class="text-danger">*</span></label>
                        <select class="form-control" id="emission_norms" name="emission_norms" required>
                            <option value="">Select Emission norms</option>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="bus_body_builder">Bus Body Builder:<span class="text-danger">*</span></label>
                        <select class="form-control" id="bus_body_builder" name="bus_body_builder" required>
                            <option value="">Select Body Builder</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="wheel_base">Wheel Base:<span class="text-danger">*</span></label>
                        <select class="form-control" id="wheel_base" name="wheel_base" required>
                            <option value="">Select Wheel Base</option>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="chassis_number">Chassis Number:<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="chassis_number" name="chassis_number"
                            pattern="[A-Z0-9]{10,20}" title="Enter a valid chassis number" required
                            oninput="this.value = this.value.toUpperCase()">
                        <small id="note" class="form-text text-muted" style="display: none;">Note: Enter 17 character
                            Chassis number</small>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="bus_category">Bus Category:<span class="text-danger">*</span></label>
                        <select class="form-control" id="bus_category" name="bus_category" required>
                            <option value="">Select Bus Category</option>
                            <!-- Bus Category options will be populated dynamically -->
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group"><!-- Depot select -->
                        <label for="bus_sub_category">Bus Sub Category:<span class="text-danger">*</span></label>
                        <select class="form-control" id="bus_sub_category" name="bus_sub_category" required>
                            <option value="">Select Bus Sub Category</option>
                            <!-- Bus Sub Category options will be populated dynamically -->
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="seating_capacity">Seating/Berth Capacity:<span class="text-danger">*</span></label>
                        <select class="form-control" id="seating_capacity" name="seating_capacity" required>
                            <option value="">Select Seating Capacity</option>
                            <?php // Populate dropdown with numbers from 1 to 65
                                for ($i = 20; $i <= 65; $i++) {
                                    echo "<option value='$i'>$i</option>";
                                }
                                ?>
                        </select>
                        <small class="form-text text-muted">seating capacity=passenger+driver+conductor</small>
                    </div>
                </div>
                <div class="col">

                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        // Function to fetch makes
        function fetchMakes() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchMakes' },
                success: function (response) {
                    var makes = JSON.parse(response);
                    $.each(makes, function (index, value) {
                        $('#make').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }
        function fetchDivision() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchDivision' },
                success: function (response) {
                    var divisions = JSON.parse(response);
                    $.each(divisions, function (index, division) {
                        // Exclude divisions named "HEAD-OFFICE" or "RWY"
                        if (division.DIVISION !== 'HEAD-OFFICE' && division.DIVISION !== 'RWY') {
                            $('#division').append('<option value="' + division.division_id + '">' + division.DIVISION + '</option>');
                        }
                    });
                }
            });
        }



        // Function to fetch emission norms
        function fetchEmissionNorms() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchEmissionNorms' },
                success: function (response) {
                    var emissionNorms = JSON.parse(response);
                    $.each(emissionNorms, function (index, value) {
                        $('#emission_norms').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }

        // Function to fetch wheel base
        function fetchWheelBase() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchWheelBase' },
                success: function (response) {
                    var wheelBase = JSON.parse(response);
                    $.each(wheelBase, function (index, value) {
                        $('#wheel_base').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }

        // Function to fetch body builder
        function fetchBodyBuilder() {
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchBodyBuilder' },
                success: function (response) {
                    var bodyBuilders = JSON.parse(response);
                    $.each(bodyBuilders, function (index, value) {
                        $('#bus_body_builder').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });
        }
        function fetchBusCategory() {


            // Fetch bus categories on page load
            $.ajax({
                url: '../includes/data_fetch.php',
                type: 'GET',
                data: { action: 'fetchBusCategory' },
                success: function (response) {
                    var busCategory = JSON.parse(response);
                    $.each(busCategory, function (index, value) {
                        $('#bus_category').append('<option value="' + value + '">' + value + '</option>');
                    });
                }
            });



            //Fetch bus sub-categories based on selected bus category
            $('#bus_category').change(function () {
                var busCategory = $(this).val();
                $.ajax({
                    url: '../includes/data_fetch.php?action=fetchBusSubCategory',
                    method: 'POST',
                    data: { bus_category: busCategory },
                    success: function (data) {
                        $('#bus_sub_category').html(data);
                    }
                });
            });
        }


        $(document).ready(function () {
            // Fetch data functions
            fetchMakes();
            fetchDivision();
            fetchEmissionNorms();
            fetchWheelBase();
            fetchBodyBuilder();
            fetchBusCategory();

            // Handle form submission
            $('form').on('submit', function (e) {
                e.preventDefault(); // Prevent the default form submission

                // Collect form data
                var formData = {
                    division: $('#division').val(),
                    make: $('#make').val(),
                    emission_norms: $('#emission_norms').val(),
                    bus_body_builder: $('#bus_body_builder').val(),
                    wheel_base: $('#wheel_base').val(),
                    chassis_number: $('#chassis_number').val(),
                    bus_category: $('#bus_category').val(),
                    bus_sub_category: $('#bus_sub_category').val(),
                    seating_capacity: $('#seating_capacity').val()
                };

                // Validate form data
                for (var key in formData) {
                    if (formData[key] === "") {
                        alert("Please fill out all required fields.");
                        return;
                    }
                }

                $.ajax({
                    url: 'rwy_bus_insert.php',
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.status === 'success') {
                                alert(result.message);
                                window.location.reload(); // Reload the page or redirect as needed
                            } else {
                                alert(result.message); // Display error message in alert
                            }
                        } catch (e) {
                            alert('An unexpected error occurred. Please try again.');
                        }
                    },
                    error: function (xhr, status, error) {
                        alert("An error occurred while submitting the form: " + error);
                    }
                });
            });
        });

        $(document).ready(function () {
            // Function to update validation pattern and show/hide note based on make and emission norms
            function updateValidationPatternAndNote() {
                var make = $("#make").val();
                var emissionNorms = $("#emission_norms").val();
                var pattern;
                if (make === "Corona" || (emissionNorms === "BS-2" || emissionNorms === "BS-3")) {
                    pattern = "[A-Z0-9]{9,18}";
                    $("#note").hide(); // Hide note if make is "Corona" or emission norms are "BS_2" or "BS_3"
                } else {
                    pattern = "[A-Z0-9]{17,18}";
                    $("#note").show(); // Show note for other makes and emission norms
                }
                $("#chassis_number").attr("pattern", pattern);
            }
            // Call the function initially
            updateValidationPatternAndNote();
            // AJAX function to update validation pattern and show/hide note when make or emission norms change
            $("#make, #emission_norms").change(function () {
                updateValidationPatternAndNote();
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