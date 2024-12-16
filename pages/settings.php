<?php
include '../includes/connection.php';
include '../includes/sidebar.php';
$query = 'SELECT ID, t.TYPE
            FROM users u
            JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

while ($row = mysqli_fetch_assoc($result)) {
  $Aa = $row['TYPE'];

  if ($Aa == 'DIVISION') {
    ?>
    <script type="text/javascript">
      //then it will be redirected
      alert("Restricted Page! You will be redirected to POS");
      window.location = "division.php";
    </script>
    <?php
  }
}

// JOB SELECT OPTION TAB
$sql = "SELECT DISTINCT TYPE, TYPE_ID FROM type";
$result = mysqli_query($db, $sql) or die("Bad SQL: $sql");

$opt = "<select class='form-control' name='type'>";
while ($row = mysqli_fetch_assoc($result)) {
  $opt .= "<option value='" . $row['TYPE_ID'] . "'>" . $row['TYPE'] . "</option>";
}

$opt .= "</select>";

$query = "SELECT ID, e.FIRST_NAME, e.LAST_NAME, e.GENDER, USERNAME, PASSWORD, e.EMAIL, PHONE_NUMBER, j.JOB_TITLE,  t.TYPE, l.DIVISION, l.DEPOT
                      FROM users u
                      join employee e on u.PF_ID = e.PF_ID
                      join job j on e.JOB_ID=j.JOB_ID
                      join location l on e.LOCATION_ID=l.LOCATION_ID
                      join type t on u.TYPE_ID=t.TYPE_ID
                      WHERE ID =" . $_SESSION['MEMBER_ID'];
$result = mysqli_query($db, $query) or die(mysqli_error($db));
while ($row = mysqli_fetch_array($result)) {
  $zz = $row['ID'];
  $a = $row['FIRST_NAME'];
  $b = $row['LAST_NAME'];
  $c = $row['GENDER'];
  $d = $row['USERNAME'];
  $e = $row['PASSWORD'];
  $f = $row['EMAIL'];
  $g = $row['PHONE_NUMBER'];
  $h = $row['JOB_TITLE'];
  $j = $row['DIVISION'];
  $k = $row['DEPOT'];
  $l = $row['TYPE'];
}
$id = $_GET['id'];
?>

<div class="card shadow mb-4 col-xs-12 col-md-12 border-bottom-primary">
  <div class="card-header py-3">
    <h4 class="m-2 font-weight-bold text-primary">Edit Account Info</h4>
  </div>
  <div class="card-body">


    <form role="form" method="post" action="settings_edit.php">
      <input type="hidden" name="id" value="<?php echo $zz; ?>" />

      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          First Name:
        </div>
        <div class="col-sm-9">
          <input class="form-control" placeholder="First Name" name="firstname" value="<?php echo $a; ?>" required>
        </div>
      </div>
      <br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Last Name:
        </div>
        <div class="col-sm-9">
          <input class="form-control" placeholder="Last Name" name="lastname" value="<?php echo $b; ?>" required>
        </div>
      </div><br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Gender:
        </div>
        <div class="col-sm-9">
          <select class='form-control' name='gender' required>
            <option value="" disabled selected hidden>Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>
      </div><br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Username:
        </div>
        <div class="col-sm-9">
          <input class="form-control" placeholder="Username" name="username" value="<?php echo $d; ?>" required>
        </div>
      </div><br>
      <div class="form-group row text-left text-primary">
    <div class="col-sm-3" style="padding-top: 5px;">
        Password:
    </div>
    <div class="col-sm-9">
        <div class="input-group">
            <input type="password" class="form-control" placeholder="Password" name="password" value="<?php echo $E; ?>"
                required id="passwordInput">
            <div class="input-group-append">
                <span class="input-group-text" id="togglePassword">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                </span>
            </div>
        </div>
    </div>
</div><br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Email:
        </div>
        <div class="col-sm-9">
          <input class="form-control" placeholder="Email" name="email" value="<?php echo $f; ?>" required>
        </div>
      </div><br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Contact #:
        </div>
        <div class="col-sm-9">
          <input class="form-control" placeholder="Contact #" name="phone" value="<?php echo $g; ?>" required>
        </div>
      </div><br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Role:
        </div>
        <div class="col-sm-9">
          <input class="form-control" placeholder="Role" name="role" value="<?php echo $h; ?>" readonly>
        </div>
      </div><br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Division:
        </div>
        <div class="col-sm-9">
          <input class="form-control" placeholder="DIVISION" name="DIVISION" value="<?php echo $j; ?>" required>
        </div>
      </div><br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Depot:
        </div>
        <div class="col-sm-9">
          <input class="form-control" placeholder="DEPOT" name="DEPOT" value="<?php echo $k; ?>" required>
        </div>
      </div><br>
      <div class="form-group row text-left text-primary">
        <div class="col-sm-3" style="padding-top: 5px;">
          Account Type:
        </div><br>
        <div class="col-sm-9">
          <input class="form-control" placeholder="Account Type" name="type" value="<?php echo $l; ?>" readonly>
        </div>
      </div>
      <hr>
      <center>
        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-edit fa-fw"></i>Update</button>

      </center>

    </form>
  </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-AWtX8fW/6IvPxBL3Nziik6szU/FK8HPGrKi4xkprM0+g9SZnIkE/z/K38+aW9r+Zyl7I6Z39VN9MlYv0w6r0wA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<script>
    // Get the password input and the eye icon
    var passwordInput = document.getElementById("passwordInput");
    var togglePassword = document.getElementById("togglePassword");

    // Toggle password visibility when the eye icon is clicked
    togglePassword.addEventListener("click", function () {
        // Toggle the type attribute of the password input
        var type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);

        // Change the eye icon accordingly
        if (type === "password") {
            togglePassword.innerHTML = '<i class="fa fa-eye-slash" aria-hidden="true"></i>';
        } else {
            togglePassword.innerHTML = '<i class="fa fa-eye" aria-hidden="true"></i>';
        }
    });
</script>

<?php
include '../includes/footer.php';
?>