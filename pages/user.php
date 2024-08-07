<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

$query = 'SELECT ID, t.TYPE FROM users u JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
    $Aa = $row['TYPE'];

    if ($Aa == 'DEPOT') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Depot Page");
            window.location = "../includes/depot_verify.php";
        </script>
    <?php } elseif ($Aa == 'DIVISION') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
        </script>
    <?php } elseif ($_SESSION['TYPE'] == 'DEPOT') { ?>

        <script type="text/javascript">
            alert("You've updated your account successfully.");
            window.location = "../includes/depot_verify.php";
        </script><?php
    } elseif ($Aa == 'RWY') {
        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to RWY Page");
            window.location = "rwy.php";
        </script>
    <?php } elseif ($_SESSION['TYPE'] == 'HEAD-OFFICE') {
        // Check the job title of the user
        if ($_SESSION['JOB_TITLE'] == 'CO_STORE') {
            ?>
            <script type="text/javascript">
                // Redirect to depot_clerk.php if the job title is Clerk
                alert("Restricted Page! You will be redirected to Stores Page");
                window.location = "index.php";
            </script>
            <?php
        }
    }
}
?>
<!-- ADMIN TABLE -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h4 class="m-2 font-weight-bold text-primary">Admin Account(s)</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = 'SELECT ID, FIRST_NAME,LAST_NAME,USERNAME, t.TYPE
              FROM users u
              JOIN employee e ON e.PF_ID=u.PF_ID
              JOIN type t ON t.TYPE_ID=u.TYPE_ID
              WHERE u.TYPE_ID=1';
                    $result = mysqli_query($db, $query) or die(mysqli_error($db));

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo '<tr>';
                        echo '<td>' . $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'] . '</td>';
                        echo '<td>' . $row['USERNAME'] . '</td>';
                        echo '<td>' . $row['TYPE'] . '</td>';
                        echo '<td align="left"> 
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary bg-gradient-primary" onclick="getUserDetails(' . $row['ID'] . ')"><i class="fas fa-fw fa-list-alt"></i> Details</button>
                            </div>
                            <div class="btn-group ml-2"> <!-- Add margin to create space -->
                                <button type="button" class="btn btn-warning bg-gradient-warning btn-block edit-btn" style="border-radius: 0px;" data-id="' . $row['ID'] . '"><i class="fas fa-fw fa-edit"></i></button>
                            </div>                                               
                        </td>';
                        echo '</tr> ';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h4 class="m-2 font-weight-bold text-primary">RWY Accounts&nbsp;
            <a href="#" data-toggle="modal" data-target="#supplierModal" type="button"
                class="btn btn-primary bg-gradient-primary" data-type-id="4" style="border-radius: 0px;"><i
                    class="fas fa-fw fa-plus"></i></a>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = 'SELECT ID, FIRST_NAME,LAST_NAME,USERNAME, t.TYPE
              FROM users u
              JOIN employee e ON e.PF_ID=u.PF_ID
              JOIN type t ON t.TYPE_ID=u.TYPE_ID
              WHERE u.TYPE_ID=4';
                    $result = mysqli_query($db, $query) or die(mysqli_error($db));

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo '<tr>';
                        echo '<td>' . $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'] . '</td>';
                        echo '<td>' . $row['USERNAME'] . '</td>';
                        echo '<td>' . $row['TYPE'] . '</td>';
                        echo '<td align="left"> 
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary bg-gradient-primary" onclick="getUserDetails(' . $row['ID'] . ')"><i class="fas fa-fw fa-list-alt"></i> Details</button>
                            </div>
                            <div class="btn-group ml-2"> <!-- Add margin to create space -->
                                <button type="button" class="btn btn-warning bg-gradient-warning btn-block edit-btn" style="border-radius: 0px;" data-id="' . $row['ID'] . '"><i class="fas fa-fw fa-edit"></i></button>
                            </div>                                               
                        </td>';
                        echo '</tr> ';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>




<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h4 class="m-2 font-weight-bold text-primary">Division Accounts&nbsp;
            <a href="#" data-toggle="modal" data-target="#supplierModal" type="button"
                class="btn btn-primary bg-gradient-primary" data-type-id="2" style="border-radius: 0px;"><i
                    class="fas fa-fw fa-plus"></i></a>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = 'SELECT ID, FIRST_NAME,LAST_NAME,USERNAME, t.TYPE
              FROM users u
              JOIN employee e ON e.PF_ID=u.PF_ID
              JOIN type t ON t.TYPE_ID=u.TYPE_ID
              WHERE u.TYPE_ID=2';
                    $result = mysqli_query($db, $query) or die(mysqli_error($db));

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo '<tr>';
                        echo '<td>' . $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'] . '</td>';
                        echo '<td>' . $row['USERNAME'] . '</td>';
                        echo '<td>' . $row['TYPE'] . '</td>';
                        echo '<td align="left"> 
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary bg-gradient-primary" onclick="getUserDetails(' . $row['ID'] . ')"><i class="fas fa-fw fa-list-alt"></i> Details</button>
                            </div>
                            <div class="btn-group ml-2"> <!-- Add margin to create space -->
                                <button type="button" class="btn btn-warning bg-gradient-warning btn-block edit-btn" style="border-radius: 0px;" data-id="' . $row['ID'] . '"><i class="fas fa-fw fa-edit"></i></button>
                            </div>                                               
                        </td>';
                        echo '</tr> ';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="card-header py-3">
            <h4 class="m-2 font-weight-bold text-primary">Depot Accounts&nbsp;
                <a href="#" data-toggle="modal" data-target="#supplierModal" type="button"
                    class="btn btn-primary bg-gradient-primary" data-type-id="3" style="border-radius: 0px;"><i
                        class="fas fa-fw fa-plus"></i></a>
                </a>
            </h4>
        </div>

    </div>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = 'SELECT ID, FIRST_NAME,LAST_NAME,USERNAME, t.TYPE
              FROM users u
              JOIN employee e ON e.PF_ID=u.PF_ID
              JOIN type t ON t.TYPE_ID=u.TYPE_ID
              WHERE u.TYPE_ID=3';
                    $result = mysqli_query($db, $query) or die(mysqli_error($db));

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo '<tr>';
                        echo '<td>' . $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'] . '</td>';
                        echo '<td>' . $row['USERNAME'] . '</td>';
                        echo '<td>' . $row['TYPE'] . '</td>';
                        echo '<td align="left"> 
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary bg-gradient-primary" onclick="getUserDetails(' . $row['ID'] . ')"><i class="fas fa-fw fa-list-alt"></i> Details</button>
                            </div>
                            <div class="btn-group ml-2"> <!-- Add margin to create space -->
                                <button type="button" class="btn btn-warning bg-gradient-warning btn-block edit-btn" style="border-radius: 0px;" data-id="' . $row['ID'] . '"><i class="fas fa-fw fa-edit"></i></button>
                            </div>                                               
                        </td>';
                        echo '</tr> ';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<?php

$sql = "SELECT e.PF_ID, e.FIRST_NAME, e.LAST_NAME, j.JOB_TITLE
FROM employee e
JOIN job j ON j.JOB_ID = e.JOB_ID
LEFT JOIN users u ON u.PF_ID = e.PF_ID
WHERE u.PF_ID IS NULL
ORDER BY e.LAST_NAME ASC";
$res = mysqli_query($db, $sql) or die("Bad SQL: $sql");

$opt = "<select class='form-control' name='empid' required>
        <option value='' disabled selected hidden>Select Employee</option>";
while ($row = mysqli_fetch_assoc($res)) {
    $opt .= "<option value='" . $row['PF_ID'] . "'>" . $row['LAST_NAME'] . ', ' . $row['FIRST_NAME'] . ' - ' . $row['JOB_TITLE'] . "</option>";
}
$opt .= "</select>";
?>

<!-- User Account Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add User Account</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form role="form" method="post" action="us_transac.php?action=add">
                    <!-- Add a hidden input field for TYPE_ID -->
                    <input type="hidden" id="typeIdInput" name="type_id">
                    <div class="form-group">
                        <?php echo $opt; ?>
                    </div>
                    <div class="form-group">
                        <input class="form-control" placeholder="Username" name="username" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" placeholder="Password" name="password" required>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-success"><i class="fa fa-check fa-fw"></i>Save</button>
                    <button type="reset" class="btn btn-danger"><i class="fa fa-times fa-fw"></i>Reset</button>
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Add event listener to the add button to capture data-type-id attribute value
    $(document).ready(function () {
        $('.btn-primary[data-target="#supplierModal"]').click(function () {
            var typeId = $(this).data('type-id');
            $('#typeIdInput').val(typeId);
        });
    });
</script>
<div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="userDetailsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- User details will be displayed here -->
                <!-- You can populate this section dynamically using JavaScript -->
                <div id="userDetailsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                    onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    function closeModal() {
        $('#userDetailsModal').modal('hide');
    }
</script>
<script>
    function getUserDetails(userId) {
        // AJAX request to fetch user details
        $.ajax({
            url: 'us_searchfrm.php',
            type: 'GET',
            data: { id: userId },
            success: function (response) {
                // Populate modal content with user details
                $('#userDetailsContent').html(response);
                // Show the modal
                $('#userDetailsModal').modal('show');
            },
            error: function (xhr, status, error) {
                alert('Error fetching user details: ' + error);
            }
        });
    }
</script>


<!-- Your edit modal -->
<div class="modal" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal1()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <input type="text" class="form-control" id="gender" name="gender" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" readonly>
                    </div>
                    <div class="form-group">
                        <label for="passwordInput1">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" placeholder="Password" name="password1" required id="passwordInput1">
                            <div class="input-group-append">
                                <span class="input-group-text" id="togglePassword1">
                                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="jobtitle">Job Title</label>
                        <input type="text" class="form-control" id="jobtitle" name="jobtitle" readonly>
                    </div>
                    <div class="form-group">
                        <label for="division">Division</label>
                        <input type="text" class="form-control" id="division" name="division" readonly>
                    </div>
                    <div class="form-group">
                        <label for="depot">Depot</label>
                        <input type="text" class="form-control" id="depot" name="depot" readonly>
                    </div>
                    <!-- Add other fields similarly -->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                    onclick="closeModal1()">Close</button>
                <button type="button" class="btn btn-warning" style="border-radius: 0px;">Update</button>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var passwordInput = document.getElementById("passwordInput1");
        var togglePassword = document.getElementById("togglePassword1");

        togglePassword.addEventListener("click", function() {
            var type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            togglePassword.innerHTML = type === "password" ? '<i class="fa fa-eye-slash" aria-hidden="true"></i>' : '<i class="fa fa-eye" aria-hidden="true"></i>';
        });
    });
</script>

<script>
    $(document).ready(function () {
        $('.edit-btn').click(function () {
            var id = $(this).data('id');
            fetchAndOpenModal(id);
        });
    });

    function fetchAndOpenModal(id) {
        $.ajax({
            url: 'us_edit.php',
            method: 'GET',
            data: { id: id },
            success: function (response) {
                var data = JSON.parse(response);
                // Update the form fields with fetched data
                $('#firstname').val(data.FIRST_NAME);
                $('#lastname').val(data.LAST_NAME);
                $('#gender').val(data.GENDER);
                $('#username').val(data.USERNAME);
                $('#passwordInput1').val(data.PASSWORD); // Update with correct ID
                $('#email').val(data.EMAIL);
                $('#phone').val(data.PHONE_NUMBER);
                $('#jobtitle').val(data.JOB_TITLE);
                $('#division').val(data.DIVISION);
                $('#depot').val(data.DEPOT);
                // Show the modal after data is fetched and updated
                $('#editModal').modal('show');
            },
            error: function (xhr, status, error) {
                console.error(error);
            }
        });
    }
</script>
<script>
    function closeModal1() {
        $('#editModal').modal('hide');
    }
</script>
<?php include '../includes/footer.php'; ?>