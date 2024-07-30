<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=1">
    <title>Home</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
    <div class="container" style="width:40%">
        <form action="submit_late_arrival.php" method="POST" class="mt-4">
            <div class="form-group">
                <label for="sch_no">Sch No</label>
                <input type="text" class="form-control" id="sch_no" name="sch_no" required
                    oninput="this.value = this.value.toUpperCase()">
            </div>
            <label>Multiple Select</label>
            <select id="vehicle_no" name="vehicle_no" class="vehicle_no" placeholder="Native Select">
                <option value="1">HTML</option>
                <option value="2">CSS</option>
                <option value="3">JavaScript</option>
                <option value="4">Python</option>
                <option value="5">JAVA</option>
                <option value="6">PHP</option>
            </select>
            <select class="js-example-basic-single" name="state">
                <option value="AL">Alabama</option>
                <option value="WY">Wyoming</option>
            </select>
            <div id="scheduleDetails">
                <!-- Fields will be populated here dynamically using JavaScript -->
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script type="text/javascript" src="../includes/virtual-select.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.vehicle_no').select2();
            $('.js-example-basic-single').select2();
        });
    </script>
</body>
</html>
