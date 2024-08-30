<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h4 class="m-2 font-weight-bold text-primary">Employee&nbsp;<a href="#" data-toggle="modal"
                    data-target="#employeeModal" type="button" class="btn btn-primary bg-gradient-primary"
                    style="border-radius: 0px;"><i class="fas fa-fw fa-plus"></i></a></h4>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = 'SELECT PF_ID, FIRST_NAME, LAST_NAME, j.JOB_TITLE FROM employee e JOIN job j ON e.JOB_ID=j.JOB_ID';
                        $result = mysqli_query($db, $query) or die(mysqli_error($db));
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<tr>';
                            echo '<td>' . $row['FIRST_NAME'] . '</td>';
                            echo '<td>' . $row['LAST_NAME'] . '</td>';
                            echo '<td>' . $row['JOB_TITLE'] . '</td>';
                            echo '<td align="right">
                            <div class="btn-group">
                            <a type="button" class="btn btn-primary bg-gradient-primary" data-toggle="modal" data-target="#detailsModal' . $row['PF_ID'] . '"> <i class="fas fa-fw fa-list-alt"></i> Details </a>
                                                       
                            </div>
                        </td>';
                            echo '</tr>';
                        }
                        // emp_edit.php?action=edit&id=' . $row['PF_ID'] . '
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        // Fetch employee details and create modal for each employee
        $query = 'SELECT PF_ID, FIRST_NAME, LAST_NAME,GENDER, EMAIL, PHONE_NUMBER, j.JOB_TITLE,  l.DIVISION, l.DEPOT 
          FROM employee e JOIN location l ON e.LOCATION_ID = l.LOCATION_ID JOIN job j ON j.JOB_ID=e.JOB_ID';
        $result = mysqli_query($db, $query) or die(mysqli_error($db));
        while ($row = mysqli_fetch_assoc($result)) {
            $zz = $row['PF_ID'];
            $i = $row['FIRST_NAME'];
            $ii = $row['LAST_NAME'];
            $iii = $row['GENDER'];
            $a = $row['EMAIL'];
            $b = $row['PHONE_NUMBER'];
            $c = $row['JOB_TITLE'];
            $f = $row['DIVISION'];
            $g = $row['DEPOT'];
            ?>
            <!-- Details Modal -->
            <div class="modal fade" id="detailsModal<?= $zz ?>" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="detailsModalLabel">Employee Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Full Name:</label>
                                <div class="col-sm-8">
                                    <?= $i . ' ' . $ii ?>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Gender:</label>
                                <div class="col-sm-8">
                                    <?= $iii ?>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Email:</label>
                                <div class="col-sm-8">
                                    <?= $a ?>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Contact #:</label>
                                <div class="col-sm-8">
                                    <?= $b ?>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Role:</label>
                                <div class="col-sm-8">
                                    <?= $c ?>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Address:</label>
                                <div class="col-sm-8">
                                    <?= $g . ', ' . $f ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Modal -->
        <div class="modal fade" id="employeeModal" tabindex="-1" role="dialog" aria-labelledby="employeeModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="employeeModalLabel">Add Employee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Add employee form here -->
                        <!-- Example: -->
                        <form role="form" method="post" action="emp_transac.php?action=add"
                            onsubmit="return validateForm()">
                            <div class="form-group">
                                <input class="form-control" placeholder="First Name" name="firstname" required>
                            </div><br>
                            <div class="form-group">
                                <input class="form-control" placeholder="Last Name" name="lastname" required>
                            </div><br>
                            <div class="form-group">
                                <select class='form-control' name='gender' required>
                                    <option value="" disabled selected hidden>Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div><br>
                            <div class="form-group">
                                <input class="form-control" type="email" placeholder="Email" name="email" required>
                            </div><br>
                            <div class="form-group">
                                <input class="form-control" placeholder="Phone Number" name="phonenumber" pattern="\d{10}"
                                    required>
                            </div><br>
                            <div class="form-group">
                                <select class="form-control" id="designation" name="designation" required>
                                    <option value="">Select Designation</option>
                                </select>
                            </div><br>
                            <div class="form-group">
                                <select class="form-control" id="division" name="division" required>
                                    <option value="">Select Division</option>
                                </select>
                            </div><br>
                            <div class="form-group">
                                <select class="form-control" id="depot" name="depot" required>
                                    <option value="">Select Depot</option>
                                </select>
                            </div>
                            <hr>
                            <button type="submit" class="btn btn-success btn-block"><i
                                    class="fa fa-check fa-fw"></i>Save</button>
                            <button type="reset" class="btn btn-danger btn-block"><i
                                    class="fa fa-times fa-fw"></i>Reset</button>
                        </form>

                        <script>
                            function validateForm() {
                                let form = document.forms[0];
                                let email = form["email"].value;
                                let phoneNumber = form["phonenumber"].value;

                                // Email validation
                                let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                if (!emailPattern.test(email)) {
                                    alert("Please enter a valid email address.");
                                    return false;
                                }

                                // Phone number validation (exactly 10 digits)
                                let phonePattern = /^\d{10}$/;
                                if (!phonePattern.test(phoneNumber)) {
                                    alert("Please enter a valid 10-digit phone number.");
                                    return false;
                                }

                                return true;
                            }
                        </script>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>


<script>
    function fetchBusCategory() {


        // Fetch bus categories on page load
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




        $('#division').change(function () {
            var Division = $(this).val();
            $.ajax({
                url: '../includes/data_fetch.php?action=fetchDepot',
                method: 'POST',
                data: { division: Division },
                success: function (data) {
                    // Assuming `data` is the HTML string containing <option> elements
                    var $data = $(data);
                    var filteredData = $data.filter(function () {
                        return $(this).val().toUpperCase() !== 'DIVISION';
                    });
                    $('#depot').html(filteredData);
                }
            });
        });

    }
    // Function to fetch body builder
    function fetchBodyBuilder() {
        $.ajax({
            url: '../includes/data_fetch.php',
            type: 'GET',
            data: { action: 'fetchDesignation' },
            success: function (response) {
                var Designation = JSON.parse(response);
                $.each(Designation, function (index, value) {
                    $('#designation').append('<option value="' + value + '">' + value + '</option>');
                });
            }
        });
    }

    // Call the functions to fetch data on page load
    $(document).ready(function () {
        fetchBodyBuilder();
        fetchBusCategory();

    });
</script>