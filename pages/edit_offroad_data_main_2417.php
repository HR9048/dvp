<?php
// Include connection file and any necessary dependencies
include '../includes/connection.php';
include '../includes/sidebar.php';

// Check if the request method is POST and if delete_id is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    // Function to delete a record
    function deleteRecord($id, $db)
    {
        $sql = "DELETE FROM off_road_data WHERE id = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Call the deleteRecord function
    deleteRecord($id, $db);
}

// Query to fetch data from users table
$query = 'SELECT ID, t.TYPE
            FROM users u
            JOIN type t ON t.TYPE_ID=u.TYPE_ID WHERE ID = ' . $_SESSION['MEMBER_ID'] . '';
$result = mysqli_query($db, $query) or die(mysqli_error($db));

// Check the user type and redirect accordingly
while ($row = mysqli_fetch_assoc($result)) {
    $userType = $row['TYPE'];

    if ($userType == 'DIVISION') {
        // Redirect to Division page
        ?>
        <script type="text/javascript">
            alert("Restricted Page! You will be redirected to Division Page");
            window.location = "division.php";
        </script>
    <?php } elseif ($userType == 'DEPOT') {
        // Redirect to Head Office page
        ?>
        <script type="text/javascript">
            alert("Restricted Page! You will be redirected to Head Office Page");
            window.location = "index.php";
        </script>
    <?php } elseif ($userType == 'RWY') {
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

// Check if the request method is POST and if searchDate is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['searchDate'])) {
    $searchDate = $_POST['searchDate'];

    // Query to search data by date
    $query = "SELECT o.*, 
    loc.division AS division_name, 
    loc.depot AS depot_name 
FROM off_road_data o 
INNER JOIN location loc ON o.depot = loc.depot_id 
WHERE o.off_road_date = '$searchDate'";
$result = mysqli_query($db, $query);

    // Check if there's an error in the query
    if (!$result) {
        echo "Error: " . $query . "<br>" . mysqli_error($db);
    }
}
?>

<!-- Container for the search form and table -->
<div class="container mt-5">
    <h2>Search by Date</h2>
    <!-- Search form -->
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" style="text-align: center;">
        <div class="form-group" style="display: inline-block;">
            <label for="searchDate">Select Date:</label>
            <input type="date" id="searchDate" class="form-control" name="searchDate" style="width: 200px;" required>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Table to display off_road_data -->
    <table id="dataTable">
        <thead>
            <tr>
                <!-- Table headers -->
                <th>ID</th>
                <th>Bus Number</th>
                <th>Make</th>
                <th>Emission Norms</th>
                <th>Off Road Date</th>
                <th>Off Road Location</th>
                <th>Parts Required</th>
                <th>Remarks</th>
                <th>Username</th>
                <th>Division</th>
                <th>Depot</th>
                <th>Submission Datetime</th>
                <th>Status</th>
                <th>DWS Remark</th>
                <th>No. of Days Offroad</th>
                <th>DWS Last Update</th>
                <th>On Road Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Output table data
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['bus_number'] . "</td>";
                echo "<td>" . $row['make'] . "</td>";
                echo "<td>" . $row['emission_norms'] . "</td>";
                echo "<td>" . $row['off_road_date'] . "</td>";
                echo "<td>" . $row['off_road_location'] . "</td>";
                echo "<td>" . $row['parts_required'] . "</td>";
                echo "<td>" . $row['remarks'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['division_name'] . "</td>";
                echo "<td>" . $row['depot_name'] . "</td>";
                echo "<td>" . $row['submission_datetime'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>" . $row['dws_remark'] . "</td>";
                echo "<td>" . $row['no_of_days_offroad'] . "</td>";
                echo "<td>" . $row['dws_last_update'] . "</td>";
                echo "<td>" . $row['on_road_date'] . "</td>";
                // Action buttons for edit and delete
                echo "<td>
                        <button type='button' class='btn btn-primary btn-sm editBtn' data-toggle='modal' data-target='#editModal' data-id='" . $row['id'] . "'>Edit</button>
                        <form action='' method='POST' style='display:inline-block'>
                            <input type='hidden' name='delete_id' value='" . $row['id'] . "'>
                            <button type='submit' name='delete_btn' class='btn btn-danger btn-sm'>Delete</button>
                        </form>
                    </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Off-Road Data</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <div class="form-group">
                        <label for="editBusNumber">Bus Number:</label>
                        <input type="text" class="form-control" id="editBusNumber" name="bus_number">
                    </div>
                    <div class="form-group">
                        <label for="editMake">Make:</label>
                        <input type="text" class="form-control" id="editMake" name="make">
                    </div>
                    <div class="form-group">
                        <label for="editEmissionNorms">Emission Norms:</label>
                        <input type="text" class="form-control" id="editEmissionNorms" name="emission_norms">
                    </div>
                    <div class="form-group">
                        <label for="editOffRoadDate">Off-Road Date:</label>
                        <input type="date" class="form-control" id="editOffRoadDate" name="off_road_date">
                    </div>
                    <div class="form-group">
                        <label for="editOffRoadLocation">Off-Road Location:</label>
                        <input type="text" class="form-control" id="editOffRoadLocation" name="off_road_location">
                    </div>
                    <div class="form-group">
                        <label for="editPartsRequired">Parts Required:</label>
                        <input type="text" class="form-control" id="editPartsRequired" name="parts_required">
                    </div>
                    <div class="form-group">
                        <label for="editRemarks">Remarks:</label>
                        <textarea class="form-control" id="editRemarks" name="remarks"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editUsername">Username:</label>
                        <input type="text" class="form-control" id="editUsername" name="username">
                    </div>
                    <div class="form-group">
                        <label for="editDivision">Division:</label>
                        <input type="text" class="form-control" id="editDivision" name="division">
                    </div>
                    <div class="form-group">
                        <label for="editDepot">Depot:</label>
                        <input type="text" class="form-control" id="editDepot" name="depot">
                    </div>
                    <div class="form-group">
                        <label for="editSubmissionDatetime">Submission Datetime:</label>
                        <input type="text" class="form-control" id="editSubmissionDatetime" name="submission_datetime">
                    </div>
                    <div class="form-group">
                        <label for="editStatus">Status:</label>
                        <input type="text" class="form-control" id="editStatus" name="status">
                    </div>
                    <div class="form-group">
                        <label for="editDwsRemark">DWS Remark:</label>
                        <input type="text" class="form-control" id="editDwsRemark" name="dws_remark">
                    </div>
                    <div class="form-group">
                        <label for="editNoOfDaysOffroad">No of Days Off-Road:</label>
                        <input type="text" class="form-control" id="editNoOfDaysOffroad" name="no_of_days_offroad">
                    </div>
                    <div class="form-group">
                        <label for="editDwsLastUpdate">DWS Last Update:</label>
                        <input type="date" class="form-control" id="editDwsLastUpdate" name="dws_last_update">
                    </div>
                    <div class="form-group">
                        <label for="editOnRoadDate">On-Road Date:</label>
                        <input type="date" class="form-control" id="editOnRoadDate" name="on_road_date">
                    </div>
                    <!-- Hidden field to store the ID -->
                    <input type="hidden" id="editId" name="editId">

                    <!-- Buttons for saving and closing the modal -->
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
    $('.editBtn').click(function () {
        // Get the data from the selected row
        var id = $(this).data('id');
        var busNumber = $(this).closest('tr').find('td:eq(1)').text();
        var make = $(this).closest('tr').find('td:eq(2)').text();
        var emissionNorms = $(this).closest('tr').find('td:eq(3)').text();
        var offRoadDate = $(this).closest('tr').find('td:eq(4)').text();
        var offRoadLocation = $(this).closest('tr').find('td:eq(5)').text();
        var partsRequired = $(this).closest('tr').find('td:eq(6)').text();
        var remarks = $(this).closest('tr').find('td:eq(7)').text();
        var username = $(this).closest('tr').find('td:eq(8)').text();
        var division = $(this).closest('tr').find('td:eq(9)').text();
        var depot = $(this).closest('tr').find('td:eq(10)').text();
        var submissionDatetime = $(this).closest('tr').find('td:eq(11)').text();
        var status = $(this).closest('tr').find('td:eq(12)').text();
        var dwsRemark = $(this).closest('tr').find('td:eq(13)').text();
        var noOfDaysOffroad = $(this).closest('tr').find('td:eq(14)').text();
        var dwsLastUpdate = $(this).closest('tr').find('td:eq(15)').text();
        var onRoadDate = $(this).closest('tr').find('td:eq(16)').text();

        // Set the data to the form fields in the modal
        $('#editId').val(id);
        $('#editBusNumber').val(busNumber);
        $('#editMake').val(make);
        $('#editEmissionNorms').val(emissionNorms);
        $('#editOffRoadDate').val(offRoadDate);
        $('#editOffRoadLocation').val(offRoadLocation);
        $('#editPartsRequired').val(partsRequired);
        $('#editRemarks').val(remarks);
        $('#editUsername').val(username);
        $('#editDivision').val(division);
        $('#editDepot').val(depot);
        $('#editSubmissionDatetime').val(submissionDatetime);
        $('#editStatus').val(status);
        $('#editDwsRemark').val(dwsRemark);
        $('#editNoOfDaysOffroad').val(noOfDaysOffroad);
        $('#editDwsLastUpdate').val(dwsLastUpdate);
        $('#editOnRoadDate').val(onRoadDate);

        // Show the modal
        $('#editModal').modal('show');
    });

    // Submit the form data to the server for updating
    $('#editForm').submit(function (e) {
        e.preventDefault();
        // Get form data
        var formData = $(this).serialize();
        // Send form data to update script using AJAX
        $.ajax({
            url: 'update_off_road_data.php', // Update PHP script path
            type: 'POST',
            data: formData,
            success: function (response) {
                alert(response); // Show success message
                $('#editModal').modal('hide'); // Hide the modal after successful update
                location.reload(); // Refresh the page to reflect changes
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText); // Log error message
            }
        });
    });
});

</script>

<?php include '../includes/footer.php'; ?>