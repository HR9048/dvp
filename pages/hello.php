<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Time Picker Example</title>
  <!-- Include ClockPicker CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/clockpicker/dist/jquery-clockpicker.min.css">
</head>
<body>

  <div class="container">
    <h1 class="text-center">Time Picker Example</h1>
    <div class="form-group">
      <label for="sch_dep_time">Schedule Departure Time:</label>
      <input type="text" id="sch_dep_time" name="sch_dep_time" class="form-control" readonly>
    </div>
    <div class="form-group">
      <label for="sch_arr_time">Schedule Arrival Time:</label>
      <input type="text" id="sch_arr_time" name="sch_arr_time" class="form-control" readonly>
    </div>
  </div>

  <!-- Include jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Include ClockPicker JS -->
  <script src="https://cdn.jsdelivr.net/npm/clockpicker/dist/jquery-clockpicker.min.js"></script>
  
  <script>
    $(document).ready(function(){
      // Initialize ClockPicker for the input fields
      $('#sch_dep_time, #sch_arr_time').clockpicker({
        autoclose: true,
        twelvehour: true // Display time in 12-hour format
      });
    });
  </script>
</body>
</html>
