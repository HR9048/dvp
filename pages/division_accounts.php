<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME') {
    // Allow access
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h4 class="m-2 font-weight-bold text-primary">Division Account</h4>
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
                        $query = "SELECT u.ID, e.FIRST_NAME, e.LAST_NAME, u.USERNAME, t.TYPE
                    FROM users u
                    JOIN employee e ON e.PF_ID = u.PF_ID
                    JOIN type t ON t.TYPE_ID = u.TYPE_ID
                    JOIN location l ON e.LOCATION_ID = l.LOCATION_ID
                    WHERE u.TYPE_ID = 2 AND l.DIVISION = '{$_SESSION['DIVISION']}'";

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
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h4 class="m-2 font-weight-bold text-primary">Depot Accounts</h4>
        </div>
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
                        $query = "SELECT u.ID, e.FIRST_NAME, e.LAST_NAME, u.USERNAME, t.TYPE
                    FROM users u
                    JOIN employee e ON e.PF_ID = u.PF_ID
                    JOIN type t ON t.TYPE_ID = u.TYPE_ID
                    JOIN location l ON e.LOCATION_ID = l.LOCATION_ID
                    WHERE u.TYPE_ID = 3 AND l.DIVISION = '{$_SESSION['DIVISION']}' AND e.LAST_NAME NOT IN ('TI', 'SECURITY')";

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
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="userDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModal">User Details</h5>
                    <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeModalButton">
                    <span aria-hidden="true">&times;</span>
                </button> -->
                </div>
                <div class="modal-body">
                    <div id="userDetailsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="closeModalButton"
                        data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            function hideModal() {
                $('#userDetailsModal').modal('hide');
            }

            $('#userDetailsModal').on('hidden.bs.modal', function () {
                $('.modal-backdrop').remove();
            });

            $('#closeModalButton').on('click', function () {
                hideModal();
            });
        });

        function getUserDetails(userId) {
            $.ajax({
                url: 'us_searchfrm.php',
                type: 'GET',
                data: { id: userId },
                success: function (response) {
                    $('#userDetailsContent').html(response);
                    $('#userDetailsModal').modal('show');
                },
                error: function (xhr, status, error) {
                    alert('Error fetching user details: ' + error);
                }
            });
        }


    </script>

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
                                <input type="password" class="form-control" placeholder="Password" name="password1" required
                                    id="passwordInput1">
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
        document.addEventListener("DOMContentLoaded", function () {
            var passwordInput = document.getElementById("passwordInput1");
            var togglePassword = document.getElementById("togglePassword1");

            togglePassword.addEventListener("click", function () {
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
    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>