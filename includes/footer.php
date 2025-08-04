</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->
<!-- Include jQuery library -->

<!-- Include Bootstrap JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"
  crossorigin="anonymous"></script>


<footer id="sticky-footer" class="flex-shrink-0 py-4">
  <div class="text-center">
    <span>© Copyright 2024 KKRTC | All Rights Reserved</span>
  </div>
</footer>
</div>
</div>
<script>
   
  function checkSession() {
    console.log('Checking session...');
    fetch('session1.php') // Calls the session checker
      .then(response => response.json())
      .then(data => {
        if (data.status === 'expired') {
          alert("Session expired or missing necessary information. You will be logged out. Please Login Again.");
          window.location.href = 'logout.php';
        }
      })
      .catch(error => console.error('Session check error:', error));
  }

  // Check session every second
  setInterval(checkSession, 60000);
</script>

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



<!-- DataTable initialization -->
<script>
  $(document).ready(function() {
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
  $(document).ready(function() {
    var table = $('#dataTable3').DataTable({
      "paging": true,
      "lengthChange": true,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": true,
      "columnDefs": [{
        "orderable": false,
        "targets": 0
      }],
      "order": [],
      initComplete: function() {
        // Add a text input to each footer cell
        this.api().columns().every(function() {
          var column = this;
          var input = $('<input type="text" placeholder="Search" style="width: 100%;"/>')
            .appendTo($(column.footer()).empty())
            .on('keyup change', function() {
              if (column.search() !== this.value) {
                column
                  .search(this.value)
                  .draw();
              }
            });
        });
      }
    });

    // Update serial numbers after each draw event (including filtering)
    table.on('draw', function() {
      var info = table.page.info();
      table.column(0, {
        search: 'applied',
        order: 'applied',
        page: 'applied'
      }).nodes().each(function(cell, i) {
        cell.innerHTML = i + 1 + info.start;
      });
    });

    // Excel download functionality
    $('#downloadExcel1').on('click', function() {
      var today = new Date();
      var dd = String(today.getDate()).padStart(2, '0');
      var mm = String(today.getMonth() + 1).padStart(2, '0');
      var yyyy = today.getFullYear();
      today = dd + '-' + mm + '-' + yyyy;

      // Use DataTables API to get filtered data
      var filteredData = table.rows({
        filter: 'applied'
      }).data().toArray();

      // Create a new workbook
      var wb = XLSX.utils.book_new();
      var ws_data = [
        ['Serial Number', 'Bus Number', 'Division Name', 'Depot Name', 'Make', 'Emission norms', 'DOC', 'Wheel Base', 'Chassis Number', 'Bus Category', 'Bus Sub Category', 'Seating Capacity', 'Bus Body Builder']
      ];

      filteredData.forEach(function(row, index) {
        ws_data.push([
          index + 1,
          row[1],
          row[2],
          row[3],
          row[4],
          row[5],
          row[6],
          row[7],
          row[8],
          row[9],
          row[10],
          row[11],
          row[12]
        ]);
      });

      var ws = XLSX.utils.aoa_to_sheet(ws_data);
      XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

      // Get filter values to use in the file name
      var filterValues = [];
      table.columns().every(function() {
        var value = this.search();
        if (value) {
          filterValues.push(value);
        }
      });

      // Create file name based on filters
      var fileName = filterValues.slice(0, 2).join('_');
      if (filterValues.length > 2) {
        fileName += '_' + filterValues.slice(2).join('_');
      }

      // Append today's date to the file name
      fileName = (fileName ? fileName + '_' : '') + today + '_bus_master.xlsx';

      // Save the workbook as an Excel file with the dynamic file name
      XLSX.writeFile(wb, fileName);
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
  // Check if the user dropdown exists, then add click event listener
  var userDropdown = document.getElementById('userDropdown');
  if (userDropdown) {
    userDropdown.addEventListener('click', function(event) {
      // Prevent the default action (redirecting to another page)
      event.preventDefault();
      // Toggle the visibility of the dropdown menu
      var dropdownMenu = this.nextElementSibling;
      dropdownMenu.classList.toggle('show');
    });
  }

  // Check if the logout link exists, then add click event listener
  var logoutLink = document.querySelector('[data-target="#logoutModal"]');
  if (logoutLink) {
    logoutLink.addEventListener('click', function(event) {
      // Prevent the default action (redirecting to another page)
      event.preventDefault();
      // Show the logout modal
      $('#logoutModal').modal('show');
    });
  }

  // Overlay toggle functions
  function on() {
    document.getElementById("overlay").style.display = "block";
  }

  function off() {
    document.getElementById("overlay").style.display = "none";
  }
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>

</html>

<?php
include('connection.php');
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

<!-- User Edit Info Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit User Info</h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="settingsForm" role="form" method="post" action="settings_edit.php">
          <input type="hidden" name="id" value="<?php echo $zz; ?>" />

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
              <input class="form-control" placeholder="Username" name="username" value="<?php echo $d; ?>" readonly autocomplete="username">
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Password:
            </div>
            <div class="col-sm-9">
              <div class="input-group">

                <input class="form-control" type="password" id="passwordInput" placeholder="Password" name="password"
                  pattern="^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*])(?!.*\s).{6,}$"
                  title="Must contain at least one number, one letter, one special character (@/#/%/*/!/&), no spaces, and at least 6 or more characters"
                  required autocomplete="current-password">

                <div class="input-group-append">
                  <span class="input-group-text" id="togglePassword">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                  </span>
                </div>
              </div>
              <div id="passwordRequirements">
                <div id="letter" class="requirement invalid">At least one letter</div>
                <div id="number" class="requirement invalid">At least one number</div>
                <div id="special" class="requirement invalid">At least one special character (@/#/%/*/!/&)</div>
                <div id="length" class="requirement invalid">At least 6 characters</div>
              </div>

            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Email:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="Email" name="email" value="<?php echo $f; ?>" required
                type="email">
            </div>
          </div>
          <div class="form-group row text-left text-primary">
            <div class="col-sm-3" style="padding-top: 5px;">
              Contact #:
            </div>
            <div class="col-sm-9">
              <input class="form-control" placeholder="Contact #" name="phone" value="<?php echo $g; ?>" required
                pattern="\d{10}" title="Please enter a valid 10-digit phone number">
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
  document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    const passwordRequirements = document.getElementById('passwordRequirements');
    const letter = document.getElementById('letter');
    const number = document.getElementById('number');
    const special = document.getElementById('special');
    const length = document.getElementById('length');
    const noSpaces = document.createElement('div');

    noSpaces.id = "noSpaces";
    noSpaces.className = "requirement invalid";
    noSpaces.textContent = "No spaces allowed";
    passwordRequirements.appendChild(noSpaces);

    togglePassword.addEventListener("click", function() {
      var type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      togglePassword.innerHTML = type === "password" ? '<i class="fa fa-eye-slash" aria-hidden="true"></i>' : '<i class="fa fa-eye" aria-hidden="true"></i>';
    });

    passwordInput.addEventListener('input', function() {
      const value = passwordInput.value;
      letter.classList.toggle('valid', /[a-zA-Z]/.test(value));
      letter.classList.toggle('invalid', !/[a-zA-Z]/.test(value));
      number.classList.toggle('valid', /\d/.test(value));
      number.classList.toggle('invalid', !/\d/.test(value));
      special.classList.toggle('valid', /[!@#$%^&*]/.test(value));
      special.classList.toggle('invalid', !/[!@#$%^&*]/.test(value));
      length.classList.toggle('valid', value.length >= 6);
      length.classList.toggle('invalid', value.length < 6);
      noSpaces.classList.toggle('valid', !/\s/.test(value));
      noSpaces.classList.toggle('invalid', /\s/.test(value));

      passwordRequirements.style.display = 'block';
    });

    passwordInput.addEventListener('focus', function() {
      passwordRequirements.style.display = 'block';
    });

    passwordInput.addEventListener('blur', function() {
      if (passwordInput.value === '') {
        passwordRequirements.style.display = 'none';
      }
    });

    window.addEventListener('click', function(event) {
      if (event.target !== passwordInput) {
        passwordRequirements.style.display = 'none';
      }
    });

    const form = document.getElementById('settingsForm');
    form.addEventListener('submit', function(event) {
      const email = form.querySelector('input[name="email"]').value;
      const phone = form.querySelector('input[name="phone"]').value;

      if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        alert('Please enter a valid email address.');
        event.preventDefault();
      }

      if (!phone.match(/^\d{10}$/)) {
        alert('Please enter a valid 10-digit phone number.');
        event.preventDefault();
      }
    });
  });
</script>
<!-- Include Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
  #passwordRequirements {
    display: none;
    /* Initially hidden */
  }

  .requirement {
    margin-bottom: 5px;
    font-size: 14px;
  }

  .requirement.valid::before {
    content: "\f00c";
    /* Font Awesome check icon */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    color: green;
    margin-right: 5px;
  }

  .requirement.invalid::before {
    content: "\f00d";
    /* Font Awesome times icon */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    color: red;
    margin-right: 5px;
  }

  .requirement.valid {
    color: green;
  }

  .requirement.invalid {
    color: red;
  }
</style>