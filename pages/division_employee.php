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
            <h4 class="m-2 font-weight-bold text-primary">Employee</h4>
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
                        $query = "SELECT e.PF_ID, e.FIRST_NAME, e.LAST_NAME, j.JOB_TITLE
                FROM employee e
                JOIN job j ON e.JOB_ID = j.JOB_ID
                JOIN location l ON e.LOCATION_ID = l.LOCATION_ID
                WHERE l.DIVISION = '{$_SESSION['DIVISION']}'";

                        $result = mysqli_query($db, $query) or die(mysqli_error($db));

                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<tr>';
                            echo '<td>' . $row['FIRST_NAME'] . '</td>';
                            echo '<td>' . $row['LAST_NAME'] . '</td>';
                            echo '<td>' . $row['JOB_TITLE'] . '</td>';
                            echo '<td align="right">
                            <div class="btn-group">
                            <a type="button" class="btn btn-primary bg-gradient-primary" data-toggle="modal" data-target="#detailsModal' . $row['PF_ID'] . '"><i class="fas fa-fw fa-list-alt"></i> Details</a>                                <div class="btn-group">
                                    <a type="button" class="btn btn-primary bg-gradient-primary dropdown no-arrow" data-toggle="dropdown" style="color:white;">... <span class="caret"></span></a>
                                    <ul class="dropdown-menu text-center" role="menu">
                                        <li>
                                            <a type="button" class="btn btn-warning bg-gradient-warning btn-block" style="border-radius: 0px;" href="">
                                                <i class="fas fa-fw fa-edit"></i> Edit
                                            </a>
                                        </li>
                                    </ul>
                                </div>
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

    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>