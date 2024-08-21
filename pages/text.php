<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Page</title>
</head>
<body>

    <label for="arr_time">Arrival Time</label>
    <input class="form-control" type="time" id="arr_time" name="arr_time" required>
    <input type="text" id="sch_arr_time" name="sch_arr_time" value="17:30">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        alert('DOM fully loaded and parsed');

        var arrTimeInput = document.getElementById('arr_time');
        if (arrTimeInput) {
            console.log('Arrival Time input found:', arrTimeInput);

            arrTimeInput.addEventListener('change', function() {
                alert('Arrival Time changed');
            });
        } else {
            console.log('Arrival Time input NOT found');
        }
    });
    </script>

</body>
</html>
