<?php
  require_once('session.php');
  confirm_logged_in();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Registration</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>


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
</head>

<body>
    <div class="container">
        <h2 class="text-center">Bus Registration Form</h2>
        <small class="text-danger">* Indicates Required field</small>
        <form action="submit_bus33.php" method="POST">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="division">Division:<span class="text-danger">*</span></label>
                        <select class="form-control" id="division" name="division" required>
                            <!-- <option value="">Select Division</option> Division options will be populated dynamically -->
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group"><!-- Depot select -->
                        <label for="depot">Depot:<span class="text-danger">*</span></label>
                        <select class="form-control" id="depot" name="depot" required>
                            <!-- <option value="">Select Depot</option>Depot options will be populated dynamically -->
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="bus_number">Bus Number:<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bus_number" name="bus_number"
                            pattern="[K][A]\d{2}[F,G]\d{4}" title="Enter a valid bus number" required
                            oninput="this.value = this.value.toUpperCase()">
                        <small class="form-text text-muted">Example: KA--F----</small>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="seating_type">Make:<span class="text-danger">*</span></label>
                        <select class="form-control" id="make" name="make" required>
                            <option value="">Select Make</option>
                        </select>
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
                        <label for="doc">DOC:<span class="text-danger">*</span></label>
                        <input type="text" class="form-control datapicker" id="datepicker" name="doc" readonly 
                            placeholder="Select Date" autocomplete="off" required>
                        <div id="datepicker-container" required></div>
                    </div>
                    <script>
                        $(document).ready(function () {// Load date picker from external file
                            $("#datepicker-container").load("../database/datepicker.php");
                        });
                    </script>
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
                    <div class="form-group">
                        <label for="bus_body_builder">Bus Body Builder:<span class="text-danger">*</span></label>
                        <select class="form-control" id="bus_body_builder" name="bus_body_builder" required>
                            <option value="">Select Body Builder</option>
                        </select>
                    </div>
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
        $(document).ready(function () {
            // AJAX request to fetch division name and depot name
            $.ajax({
                url: '../database/data_fetch.php',
                type: 'GET',
                data: { action: 'getUserInfo' },
                success: function (response) {
                    var data = JSON.parse(response);
                    $('#division').append('<option value="' + data.division_name + '">' + data.division_name + '</option>');
                    $('#depot').append('<option value="' + data.depot_name + '">' + data.depot_name + '</option>');
                }
            });
        });

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

        // Function to fetch emission norms
        function fetchEmissionNorms() {
            $.ajax({
                url: '../database/data_fetch.php',
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
                url: '../database/data_fetch.php',
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
                url: '../database/data_fetch.php',
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
                url: '../database/data_fetch.php',
                type: 'GET',
                data:{action: 'fetchBusCategory'},
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
                    url: '../database/data_fetch.php?action=fetchBusSubCategory',
                    method: 'POST',
                    data: { bus_category: busCategory },
                    success: function (data) {
                        $('#bus_sub_category').html(data);
                    }
                });
            });
        }
        

        // Call the functions to fetch data on page load
        $(document).ready(function () {
            fetchMakes();
            fetchEmissionNorms();
            fetchWheelBase();
            fetchBodyBuilder();
            fetchBusCategory();
            
        });
        $(document).ready(function () {
            // Function to update validation pattern and show/hide note based on make and emission norms
            function updateValidationPatternAndNote() {
                var make = $("#make").val();
                var emissionNorms = $("#emission_norms").val();
                var pattern;
                if (make === "Corona" || (emissionNorms === "BS 2" || emissionNorms === "BS 3")) {
                    pattern = "[A-Z0-9]{10,17}";
                    $("#note").hide(); // Hide note if make is "Corona" or emission norms are "BS_2" or "BS_3"
                } else {
                    pattern = "[A-Z0-9]{17}";
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
</body>

</html>