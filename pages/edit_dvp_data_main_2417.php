<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    // Function to delete a record
    function deleteRecord($id, $db)
    {
        $sql = "DELETE FROM dvp_data WHERE id = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Call the deleteRecord function
    deleteRecord($id, $db);
}


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
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
        </script>
    <?php } elseif ($Aa == 'DEPOT') {

        ?>
        <script type="text/javascript">
            //then it will be redirected
            alert("Restricted Page! You will be redirected to Head Office Page");
            window.location = "index.php";
        </script>
    <?php } elseif ($Aa == 'RWY') {
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['searchDate'])) {
    $searchDate = $_POST['searchDate'];

    $query = "SELECT d.*, 
    loc.division AS division_name, 
    loc.depot AS depot_name 
FROM dvp_data d 
INNER JOIN location loc ON d.depot = loc.depot_id 
WHERE d.date = '$searchDate'";
$result = mysqli_query($db, $query);

    if (!$result) {
        echo "Error: " . $query . "<br>" . mysqli_error($db);
    }
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Search by Date</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" style="text-align: center;">
        <div class="form-group" style="display: inline-block;">
            <label for="searchDate">Select Date:</label>
            <input type="date" id="searchDate" class="form-control" name="searchDate" style="width: 200px;" required>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <table id="dataTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Schedules</th>
                <th>Vehicles</th>
                <th>Spare</th>
                <th>SpareP</th>
                <th>Docking</th>
                <th>WUP</th>
                <th>ORDepot</th>
                <th>ORDWS</th>
                <th>ORRWY</th>
                <th>Dealer</th>
                <th>CC</th>
                <th>Loan</th>
                <th>Police</th>
                <th>notdepot</th>
                <th>ORTotal</th>
                <th>Available</th>
                <th>ES</th>
                <th>Username</th>
                <th>Designation</th>
                <th>Submission Datetime</th>
                <th>Division</th>
                <th>Depot</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Output table data
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['date'] . "</td>";
                echo "<td>" . $row['schedules'] . "</td>";
                echo "<td>" . $row['vehicles'] . "</td>";
                echo "<td>" . $row['spare'] . "</td>";
                echo "<td>" . $row['spareP'] . "</td>";
                echo "<td>" . $row['docking'] . "</td>";
                echo "<td>" . $row['wup'] . "</td>";
                echo "<td>" . $row['ORDepot'] . "</td>";
                echo "<td>" . $row['ORDWS'] . "</td>";
                echo "<td>" . $row['ORRWY'] . "</td>";
                echo "<td>" . $row['Dealer'] . "</td>";
                echo "<td>" . $row['CC'] . "</td>";
                echo "<td>" . $row['loan'] . "</td>";
                echo "<td>" . $row['Police'] . "</td>";
                echo "<td>" . $row['notdepot'] . "</td>";
                echo "<td>" . $row['ORTotal'] . "</td>";
                echo "<td>" . $row['available'] . "</td>";
                echo "<td>" . $row['ES'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['designation'] . "</td>";
                echo "<td>" . $row['submission_datetime'] . "</td>";
                echo "<td>" . $row['division_name'] . "</td>";
                echo "<td>" . $row['depot_name'] . "</td>";
                echo "<td><button type='button' class='btn btn-primary btn-sm editBtn' data-toggle='modal' data-target='#editModal' data-id='" . $row['id'] . "'>Edit</button> <form action='' method='POST' style='display:inline-block'>
                <input type='hidden' name='delete_id' value='" . $row['id'] . "'>
                <button type='submit' name='delete_btn' class='btn btn-danger btn-sm'>Delete</button>
              </form></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit DVP Data</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                <div class="form-group">
                        <label for="editSchedules">date:</label>
                        <input type="date" class="form-control" id="editdate" name="date">
                    </div>
                    <div class="form-group">
                        <label for="editSchedules">Schedules:</label>
                        <input type="text" class="form-control" id="editSchedules" name="schedules">
                    </div>
                    <div class="form-group">
                        <label for="editVehicles">Vehicles:</label>
                        <input type="text" class="form-control" id="editVehicles" name="vehicles">
                    </div>
                    <div class="form-group">
                        <label for="editSpare">Spare:</label>
                        <input type="text" class="form-control" id="editSpare" name="spare">
                    </div>
                    <div class="form-group">
                        <label for="editSpareP">SpareP:</label>
                        <input type="text" class="form-control" id="editSpareP" name="spareP">
                    </div>
                    <div class="form-group">
                        <label for="editDocking">Docking:</label>
                        <input type="text" class="form-control" id="editDocking" name="docking">
                    </div>
                    <div class="form-group">
                        <label for="editWUP">WUP:</label>
                        <input type="text" class="form-control" id="editWUP" name="wup">
                    </div>
                    <div class="form-group">
                        <label for="editORDepot">ORDepot:</label>
                        <input type="text" class="form-control" id="editORDepot" name="ORDepot">
                    </div>
                    <div class="form-group">
                        <label for="editORDWS">ORDWS:</label>
                        <input type="text" class="form-control" id="editORDWS" name="ORDWS">
                    </div>
                    <div class="form-group">
                        <label for="editORRWY">ORRWY:</label>
                        <input type="text" class="form-control" id="editORRWY" name="ORRWY">
                    </div>
                    <div class="form-group">
                        <label for="editDealer">Dealer:</label>
                        <input type="text" class="form-control" id="editDealer" name="dealer">
                    </div>
                    <div class="form-group">
                        <label for="editCC">CC:</label>
                        <input type="text" class="form-control" id="editCC" name="CC">
                    </div>
                    <div class="form-group">
                        <label for="editCC">loan:</label>
                        <input type="text" class="form-control" id="editloan" name="loan">
                    </div>
                    <div class="form-group">
                        <label for="editPolice">Police:</label>
                        <input type="text" class="form-control" id="editPolice" name="Police">
                    </div>
                    <div class="form-group">
                        <label for="editNotDepot">notdepot:</label>
                        <input type="text" class="form-control" id="editNotDepot" name="notdepot">
                    </div>
                    <div class="form-group">
                        <label for="editORTotal">ORTotal:</label>
                        <input type="text" class="form-control" id="editORTotal" name="ORTotal">
                    </div>
                    <div class="form-group">
                        <label for="editAvailable">Available:</label>
                        <input type="text" class="form-control" id="editAvailable" name="available">
                    </div>
                    <div class="form-group">
                        <label for="editES">ES:</label>
                        <input type="text" class="form-control" id="editES" name="ES">
                    </div>
                    <div class="form-group">
                        <label for="editUsername">Username:</label>
                        <input type="text" class="form-control" id="editUsername" name="username">
                    </div>
                    <div class="form-group">
                        <label for="editDesignation">Designation:</label>
                        <input type="text" class="form-control" id="editDesignation" name="designation">
                    </div>
                    <div class="form-group">
                        <label for="editSubmissionDatetime">Submission Datetime:</label>
                        <input type="text" class="form-control" id="editSubmissionDatetime" name="submission_datetime">
                    </div>
                    <div class="form-group">
                        <label for="editDivision">Division:</label>
                        <input type="text" class="form-control" id="editDivision" name="division">
                    </div>
                    <div class="form-group">
                        <label for="editDepot">Depot:</label>
                        <input type="text" class="form-control" id="editDepot" name="depot">
                    </div>
                    <input type="hidden" id="editId" name="editId">
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Include jQuery and Bootstrap JS -->
<!-- Include jQuery (full version) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


<script>
    $(document).ready(function () {
        $('.editBtn').click(function () {
            // Get the data from the selected row
            var id = $(this).data('id');
            var date = $(this).closest('tr').find('td:eq(1)').text();
            var schedules = $(this).closest('tr').find('td:eq(2)').text();
            var vehicles = $(this).closest('tr').find('td:eq(3)').text();
            var spare = $(this).closest('tr').find('td:eq(4)').text();
            var spareP = $(this).closest('tr').find('td:eq(5)').text();
            var docking = $(this).closest('tr').find('td:eq(6)').text();
            var wup = $(this).closest('tr').find('td:eq(7)').text();
            var ORDepot = $(this).closest('tr').find('td:eq(8)').text();
            var ORDWS = $(this).closest('tr').find('td:eq(9)').text();
            var ORRWY = $(this).closest('tr').find('td:eq(10)').text();
            var dealer = $(this).closest('tr').find('td:eq(11)').text();
            var CC = $(this).closest('tr').find('td:eq(12)').text();
            var loan = $(this).closest('tr').find('td:eq(13)').text();
            var Police = $(this).closest('tr').find('td:eq(14)').text();
            var notdepot = $(this).closest('tr').find('td:eq(15)').text();
            var ORTotal = $(this).closest('tr').find('td:eq(16)').text();
            var available = $(this).closest('tr').find('td:eq(17)').text();
            var ES = $(this).closest('tr').find('td:eq(18)').text();
            var username = $(this).closest('tr').find('td:eq(19)').text();
            var designation = $(this).closest('tr').find('td:eq(20)').text();
            var submission_datetime = $(this).closest('tr').find('td:eq(21)').text();
            var division = $(this).closest('tr').find('td:eq(22)').text();
            var depot = $(this).closest('tr').find('td:eq(23)').text();

            // Set the data to the form fields in the modal
            $('#editId').val(id);
            $('#editdate').val(date);
            $('#editSchedules').val(schedules);
            $('#editVehicles').val(vehicles);
            $('#editSpare').val(spare);
            $('#editSpareP').val(spareP);
            $('#editDocking').val(docking);
            $('#editWUP').val(wup);
            $('#editORDepot').val(ORDepot);
            $('#editORDWS').val(ORDWS);
            $('#editORRWY').val(ORRWY);
            $('#editDealer').val(dealer);
            $('#editCC').val(CC);
            $('#editloan').val(loan);
            $('#editPolice').val(Police);
            $('#editNotDepot').val(notdepot);
            $('#editORTotal').val(ORTotal);
            $('#editAvailable').val(available);
            $('#editES').val(ES);
            $('#editUsername').val(username);
            $('#editDesignation').val(designation);
            $('#editSubmissionDatetime').val(submission_datetime);
            $('#editDivision').val(division);
            $('#editDepot').val(depot);
        });

        $('#editForm').submit(function (e) {
            e.preventDefault();
            fetch('update_dvp_data_main.php', {
                method: 'POST',
                body: new FormData(this)
            })
                .then(response => response.text())
                .then(response => {
                    alert(response);
                    location.reload();
                })
                .catch(error => console.error('Error:', error));
        });

    });
</script>

<?php include '../includes/footer.php'; ?>