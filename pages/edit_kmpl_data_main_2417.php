<?php
include '../includes/connection.php';
include '../includes/sidebar.php';

// Function to delete a record
function deleteRecord($id, $db)
{
    $sql = "DELETE FROM kmpl_data WHERE id = ?";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {
?>
    <div class="container mt-5">
        <h2 class="mb-4">Select Date Range</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="startDate">Start Date:</label>
                <input type="date" id="startDate" class="form-control" name="startDate" required>
            </div>
            <div class="form-group">
                <label for="endDate">End Date:</label>
                <input type="date" id="endDate" class="form-control" name="endDate" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        <?php
        // Include your database connection file

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $startDate = $_POST['startDate'];
            $endDate = $_POST['endDate'];

            $query = "SELECT k.*, 
        loc.division AS division_name, 
        loc.depot AS depot_name 
 FROM kmpl_data k 
 INNER JOIN location loc ON k.depot = loc.depot_id 
 WHERE k.date BETWEEN '$startDate' AND '$endDate'";
            $result = mysqli_query($db, $query);

            if ($result) {
        ?>
                <div class="mt-5">
                    <h2 class="mb-4">KMPL Data from
                        <?php echo $startDate; ?> to
                        <?php echo $endDate; ?>
                    </h2>
                    <table id="dataTable">
                        <thead>
                            <tr>
                                <!-- Add table headers -->
                                <th>ID</th>
                                <th>Gross KM</th>
                                <th>HSD</th>
                                <th>KMPL</th>
                                <th>Username</th>
                                <th>Division</th>
                                <th>Depot</th>
                                <th>Submitted Datetime</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Output table data
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                // Output table data
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . $row['total_km'] . "</td>";
                                echo "<td>" . $row['hsd'] . "</td>";
                                echo "<td>" . $row['kmpl'] . "</td>";
                                echo "<td>" . $row['username'] . "</td>";
                                echo "<td>" . $row['division_name'] . "</td>";
                                echo "<td>" . $row['depot_name'] . "</td>";
                                echo "<td>" . $row['submitted_datetime'] . "</td>";
                                echo "<td>" . $row['date'] . "</td>";
                                // Add buttons for edit and delete
                                echo "<td><button type='button' class='btn btn-primary btn-sm editBtn' data-toggle='modal' data-target='#editModal' data-id='" . $row['id'] . "'>Edit</button> <form action='' method='POST' style='display:inline-block'><input type='hidden' name='delete_id' value='" . $row['id'] . "'><button type='submit' name='delete_btn' class='btn btn-danger btn-sm'>Delete</button></form></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

        <?php
                // Check if delete button is clicked
                if (isset($_POST['delete_btn'])) {
                    $delete_id = $_POST['delete_id'];
                    deleteRecord($delete_id, $db);
                    echo "<script>alert('Record deleted successfully!'); window.location.href = 'edit_kmpl_data_main_2417.php';</script>";
                }
            } else {
                echo "Error: " . $query . "<br>" . mysqli_error($db);
            }

            mysqli_close($db);
        }
        ?>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit KMPL Data</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editForm">
                            <div class="form-group">
                                <label for="effective_km">Date</label>
                                <input type="text" class="form-control" id="date" name="date">
                            </div>
                            <div class="form-group">
                                <label for="total_km">Total KM:</label>
                                <input type="text" class="form-control" id="total_km" name="total_km">
                            </div>
                            <div class="form-group">
                                <label for="hsd">HSD:</label>
                                <input type="text" class="form-control" id="hsd" name="hsd">
                            </div>
                            <div class="form-group">
                                <label for="kmpl">KMPL:</label>
                                <input type="text" class="form-control" id="kmpl" name="kmpl">
                            </div>
                            <!-- Add a hidden input field to store the ID of the selected row -->
                            <input type="hidden" id="editId" name="editId">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                    </div>
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

    <!-- Add your custom JavaScript code -->
    <script>
        $(document).ready(function() {
            $('.editBtn').click(function() {
                // Get the data from the selected row
                var id = $(this).closest('tr').find('td:eq(0)').text();
                var total_km = $(this).closest('tr').find('td:eq(1)').text();
                var hsd = $(this).closest('tr').find('td:eq(2)').text();
                var kmpl = $(this).closest('tr').find('td:eq(3)').text();
                var date = $(this).closest('tr').find('td:eq(8)').text();

                // Set the data to the form fields in the modal
                $('#editId').val(id);
                $('#total_km').val(total_km);
                $('#hsd').val(hsd);
                $('#kmpl').val(kmpl);
                $('#date').val(date);

            });

            $('#editForm').submit(function(e) {
                e.preventDefault();
                fetch('update_main_kmpl_data.php', {
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

        $(document).ready(function() {
            function calculateKMPL() {
                var km = parseFloat($('#total_km').val());
                var hsd = parseFloat($('#hsd').val());

                if (!isNaN(km) && !isNaN(hsd) && hsd !== 0) {
                    var kmpl = km / hsd;
                    $('#kmpl').val(kmpl.toFixed(2));
                } else {
                    $('#kmpl').val('');
                }
            }

            $('#total_km, #hsd').on('input', function() {
                calculateKMPL();
            });
        });
    </script>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>