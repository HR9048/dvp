</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->
<!-- Include jQuery library -->

<!-- Include Bootstrap JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"
  crossorigin="anonymous"></script>

<footer id="footer" class="sticky-footer bg-white">
  <div class="footer">
    <div class="text-center">
      <span>Copyright © 2024 KKRTC</span>
    </div>
  </div>
</footer>
</div>
</div>
</div>
<!-- End of Footer -->

<!-- Scroll to Top Button-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js" crossorigin="anonymous"></script>
<a class="scroll-to-top rounded" href="#page-top">
  <i class="fas fa-angle-up"></i>
</a>
<!-- Include jQuery library -->
<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel"
  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">Ready to Leave?</h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close" onclick="closeModal()">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Are you sure you want to log out,
        <?php echo $_SESSION['FIRST_NAME']; ?>?
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal" onclick="closeModal()">Cancel</button>
        <a class="btn btn-primary" href="logout.php">Logout</a>
      </div>
    </div>
  </div>
</div>
<script>
  // Function to close the modal and remove the backdrop
  function closeModal() {
    $('#editModal').modal('hide'); // Hide the modal using jQuery
    $('.modal-backdrop').remove(); // Remove the modal backdrop
  }

  // Function to cancel the edit
  function cancelEdit() {
    closeModal(); // Close the modal and remove the backdrop
  }
</script>


<!-- <script type="text/javascript">
    // Set the session timeout duration in milliseconds (5 minutes = 300,000 milliseconds)
    var sessionTimeout = 900000; // 5 minutes
    // Function to redirect to logout.php when session times out
    function sessionTimeoutRedirect() {
      alert('Session timed out. Please login again.');
      window.location.href = 'logout.php'; // Redirect to logout.php
    }
    // Set a timer to execute the sessionTimeoutRedirect function after sessionTimeout duration
    setTimeout(sessionTimeoutRedirect, sessionTimeout);
  </script> -->



<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<script type="text/javascript" charset="utf8"
  src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<!-- <style>
  /* Adjust the search bar */
  .dataTables_filter {
    margin-bottom: 20px;
    /* Add margin at the bottom to create space */
  }

  /* Ensure table headers have a border at the top */
  #dataTable thead th {
    border-top: 1px solid black;
    /* Add border at the top */
  }
</style> -->

<!-- DataTable initialization -->
<script>
  $(document).ready(function () {
    $('#dataTable').DataTable({
      "paging": true, // Enable pagination
      "lengthChange": true, // Enable the row count dropdown
      "searching": true, // Enable search functionality
      "ordering": true, // Enable sorting
      "info": true, // Show table information summary
      "autoWidth": true // Automatically adjust column widths
    });
    $('#dataTable1').DataTable({
      "paging": true, // Enable pagination
      "lengthChange": true, // Enable the row count dropdown
      "searching": true, // Enable search functionality
      "ordering": true, // Enable sorting
      "info": true, // Show table information summary
      "autoWidth": true // Automatically adjust column widths
    });
  });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
<script>
  $(document).ready(function () {
    var table = $('#dataTable3').DataTable({
      "paging": true, // Enable pagination
      "lengthChange": true, // Enable the row count dropdown
      "searching": true, // Enable search functionality
      "ordering": true, // Enable sorting
      "info": true, // Show table information summary
      "autoWidth": true, // Automatically adjust column widths
      "columnDefs": [
        {
          "orderable": false,
          "targets": 0
        } // Make serial number column non-orderable
      ],
      "order": [] // Disable initial ordering
    });

    // Update serial numbers after each draw event (including filtering)
    table.on('draw', function () {
      var info = table.page.info();
      table.column(0, { search: 'applied', order: 'applied', page: 'applied' }).nodes().each(function (cell, i) {
        cell.innerHTML = i + 1 + info.start;
      });
    });

    // Excel download functionality
    $('#downloadExcel1').on('click', function () {
      var today = new Date();
      var dd = String(today.getDate()).padStart(2, '0');
      var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
      var yyyy = today.getFullYear();
      today = dd + '-' + mm + '-' + yyyy;

      // Use DataTables API to get filtered data
      var filteredData = table.rows({ filter: 'applied' }).data().toArray();

      // Create a new workbook
      var wb = XLSX.utils.book_new();
      var ws_data = [['Serial Number', 'Bus Number', 'Division Name', 'Depot Name', 'Make', 'Emission norms', 'DOC', 'Wheel Base', 'Chassis Number', 'Bus Category', 'Bus Sub Category', 'Seating Capacity', 'Bus Body Builder']];
      
      filteredData.forEach(function(row, index) {
        ws_data.push([
          index + 1, // Serial Number starting from 1 for filtered rows
          row[1], // Bus Number
          row[2], // Division Name
          row[3], // Depot Name
          row[4], // Make
          row[5], // Emission norms
          row[6], // DOC
          row[7], // Wheel Base
          row[8], // Chassis Number
          row[9], // Bus Category
          row[10], // Bus Sub Category
          row[11], // Seating Capacity
          row[12]  // Bus Body Builder
        ]);
      });

      var ws = XLSX.utils.aoa_to_sheet(ws_data);
      XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

      // Save the workbook as an Excel file with today's date
      XLSX.writeFile(wb, today + 'bus_master.xlsx');
    });
  });
</script>




<!-- Page level plugins -->
<!-- <script src="../datatables/jquery.dataTables.min.js"></script>
  <script src="../datatables/dataTables.bootstrap4.min.js"></script> -->

<!-- Page level custom scripts -->
<!-- <script src="../datatables/datatables-demo.js"></script> -->

<!-- PROFILE OVERLAY NA MODAL -->
<div id="overlay" onclick="off()">
  <div id="text">I'm
    <?php echo $_SESSION['FIRST_NAME'] . ' ' . $_SESSION['LAST_NAME']; ?><BR>
    From:
    <?php echo $_SESSION['DIVISION'] . ' ' . $_SESSION['DEPOT']; ?>
    Designation:
    <?php echo $_SESSION['JOB_TITLE']; ?>
  </div>
</div>


<script>
  // Add click event listener to the user dropdown link
  document.getElementById('userDropdown').addEventListener('click', function (event) {
    // Prevent the default action (redirecting to another page)
    event.preventDefault();
    // Toggle the visibility of the dropdown menu
    var dropdownMenu = this.nextElementSibling;
    dropdownMenu.classList.toggle('show');
  });
  // Add click event listener to the logout link
  document.querySelector('[data-target="#logoutModal"]').addEventListener('click', function (event) {
    // Prevent the default action (redirecting to another page)
    event.preventDefault();
    // Show the logout modal
    $('#logoutModal').modal('show');
  });


  function on() {
    document.getElementById("overlay").style.display = "block";
  }

  function off() {
    document.getElementById("overlay").style.display = "none";
  }


</script>

</body>

</html>

<?php
include ('connection.php');
// JOB SELECT OPTION TAB
$sql = "SELECT DISTINCT TYPE, TYPE_ID FROM type";
$result = mysqli_query($db, $sql) or die("Bad SQL: $sql");

$opt = "<select class='form-control' name='type'>";
while ($row = mysqli_fetch_assoc($result)) {
  $opt .= "<option value='" . $row['TYPE_ID'] . "'>" . $row['TYPE'] . "</option>";
}

$opt .= "</select>";

$query = "SELECT ID, e.PF_ID, e.FIRST_NAME, e.LAST_NAME, e.GENDER, USERNAME, PASSWORD, e.EMAIL, PHONE_NUMBER, j.JOB_TITLE, t.TYPE, l.DIVISION, l.DEPOT
                      FROM users u
                      join employee e on u.PF_ID = e.PF_ID
                      join job j on e.JOB_ID=j.JOB_ID
                      join location l on e.LOCATION_ID=l.LOCATION_ID
                      join type t on u.TYPE_ID=t.TYPE_ID
                      WHERE ID =" . $_SESSION['MEMBER_ID'];
$result = mysqli_query($db, $query) or die(mysqli_error($db));
while ($row = mysqli_fetch_array($result)) {
  $zz = $row['ID'];
  // $l=$row['PF_ID'];
  $a = $row['FIRST_NAME'];
  $b = $row['LAST_NAME'];
  $c = $row['GENDER'];
  $d = $row['USERNAME'];
  $e = $row['PASSWORD'];
  $f = $row['EMAIL'];
  $g = $row['PHONE_NUMBER'];
  $h = $row['JOB_TITLE'];
  $i = $row['DIVISION'];
  $j = $row['DEPOT'];
  $k = $row['TYPE'];
}
?>

<!-- User Edit Info Modal-->
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit User Info</h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
        <form role="form" method="post" action="settings_edit.php">
          <input type="hidden" name="id" value="<?php echo $zz; ?>" />

          <!-- <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              PF ID:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="PF_ID" name="PF_ID"  required>
            </div>
          </div> -->
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              First Name:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="First Name" name="firstname" value="<?php echo $a; ?>" required>
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Last Name:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="Last Name" name="lastname" value="<?php echo $b; ?>" required>
            </div>
          </div>
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
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Username:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="Username" name="username" value="<?php echo $d; ?>" readonly>
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Password:
            </div>
            <div class="col-sm-9">
              <div class="input-group">
                <input type="password" class="form-control" placeholder="Password" name="password"
                  value="<?php echo $e; ?>" required id="passwordInput">
                <div class="input-group-append">
                  <span class="input-group-text" id="togglePassword">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Email:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="Email" name="email" value="<?php echo $f; ?>" required>
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Contact #:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="Contact #" name="phone" value="<?php echo $g; ?>" required>
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Role:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="Role" name="role" value="<?php echo $h; ?>" readonly>
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              DIVISION:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="DIVISION" name="DIVISION" value="<?php echo $i; ?>" readonly>
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              DEPOT:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="DEPOT" name="DEPOT" value="<?php echo $j; ?>" readonly>
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Account Type:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="Account Type" name="type" value="<?php echo $k; ?>" readonly>
            </div>
          </div>
          <hr>
          <button type="submit" class="btn btn-success"><i class="fa fa-check fa-fw"></i>Save</button>
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>

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