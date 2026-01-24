<?php
// Include connection file and any necessary dependencies
include 'ad_nav.php';


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
<style>
    .hidden {
        display: none;
    }
</style>
<!-- Container for the search form and table -->
<div class="container mt-5">
    <h2>Search by Date</h2>
    <!-- Search form -->
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" style="text-align: center;">
        <div class="form-group" style="display: inline-block;">
            <label for="searchDate">Select Off-road Date:</label>
            <input type="date" id="searchDate" class="form-control" name="searchDate" style="width: 200px;" required>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Table to display off_road_data -->
    <table id="dataTable">
        <thead>
            <tr>
                <!-- Table headers -->
                <th class='hidden'>ID</th>
                <th>Bus Number</th>
                <th>Make</th>
                <th>Emission Norms</th>
                <th>Off Road Date</th>
                <th>Off Road Location</th>
                <th>Parts Required</th>
                <th>Remarks</th>
                <th class='hidden'>Username</th>
                <th>Division</th>
                <th>Depot</th>
                <th class='hidden'>Submission Datetime</th>
                <th>Status</th>
                <th>DWS Remark</th>
                <th>No. of Days Offroad</th>
                <th class='hidden'>DWS Last Update</th>
                <th>On Road Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Output table data
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td class='hidden'>" . $row['id'] . "</td>";
                echo "<td>" . $row['bus_number'] . "</td>";
                echo "<td>" . $row['make'] . "</td>";
                echo "<td>" . $row['emission_norms'] . "</td>";
                echo "<td>" . $row['off_road_date'] . "</td>";
                echo "<td>" . $row['off_road_location'] . "</td>";
                echo "<td>" . $row['parts_required'] . "</td>";
                echo "<td>" . $row['remarks'] . "</td>";
                echo "<td class='hidden'>" . $row['username'] . "</td>";
                echo "<td>" . $row['division_name'] . "</td>";
                echo "<td>" . $row['depot_name'] . "</td>";
                echo "<td class='hidden'>" . $row['submission_datetime'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>" . $row['dws_remark'] . "</td>";
                echo "<td>" . $row['no_of_days_offroad'] . "</td>";
                echo "<td class='hidden'>" . $row['dws_last_update'] . "</td>";
                echo "<td>" . $row['on_road_date'] . "</td>";
                // Action buttons for edit and delete
                echo "<td>
                        <button type='button' class='btn btn-primary btn-sm editBtn' data-toggle='modal' data-target='#editModal1' data-id='" . $row['id'] . "'>Edit</button>
                        <button type='button' name='delete_btn' class='btn btn-danger btn-sm delete_btn'>Delete</button>
                    </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="editModal1" tabindex="-1" role="dialog" aria-labelledby="editModal1Label"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModal1Label">Edit Bus Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>
            <div class="modal-body">
                <form id="editForm">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="editDivision">Division:</label>
                                <input type="text" class="form-control" id="editDivision" name="division" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="editDepot">Depot:</label>
                                <input type="text" class="form-control" id="editDepot" name="depot" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="editBusNumber">Bus Number:</label>
                                <input type="text" class="form-control" id="editBusNumber" name="bus_number" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="editMake">Make:</label>
                                <input type="text" class="form-control" id="editMake" name="make" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="editEmissionNorms">Emission Norms:</label>
                                <input type="text" class="form-control" id="editEmissionNorms" name="emission_norms" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="editOffRoadDate">Off-Road Date:</label>
                                <input type="date" class="form-control" id="editOffRoadDate" name="off_road_date" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="editOffRoadLocation">Off-Road Location:</label>
                                <input type="text" class="form-control" id="editOffRoadLocation" name="off_road_location" readonly>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="editPartsRequired">Parts Required:</label>
                                <input type="text" class="form-control" id="editPartsRequired" name="parts_required" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="editRemarks">Remarks:</label>
                                <textarea class="form-control" id="editRemarks" name="editRemarks"></textarea>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="editStatus">Status:</label>
                                <select class="form-control" id="editStatus" name="editStatus">
                                    <option value="off_road">Off-Road</option>
                                    <option value="on_road">On-Road</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="editDwsRemark">DWS Remark:</label>
                                <input type="text" class="form-control" id="editDwsRemark" name="editDwsRemark">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="editNoOfDaysOffroad">No of Days Off-Road:</label>
                                <input type="text" class="form-control" id="editNoOfDaysOffroad" name="no_of_days_offroad" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="editOnRoadDate">On-Road Date:</label>
                                <input type="datetime-local" class="form-control" id="editOnRoadDate" name="on_road_date" readonly>
                            </div>
                        </div>
                        <div class="col"></div>
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
    $(document).ready(function() {
        $('.editBtn').click(function() {
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
            $('#editModal1').modal('show');
        });
        $('.delete_btn').click(function() {
            var id = $(this).closest('tr').find('td:eq(0)').text();

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a ajax jquery to delete the record
                    $.ajax({
                        url: "../includes/backend_data.php",
                        type: "POST",
                        data: {
                            delete_id: id,
                            action: 'deleteOffRoadData'
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status === "success") {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'There was an error deleting the record.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        // Submit the form data to the server for updating
        $('#editForm').submit(function(e) {
            e.preventDefault();

            //validate form data
            var remarks = $('#editRemarks').val();
            var status = $('#editStatus').val();
            var dwsRemark = $('#editDwsRemark').val();
            if (remarks === '' || status === '' || dwsRemark === '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Fields',
                    text: 'Please fill all the required fields.',
                    confirmButtonText: 'OK'
                });

                return;
            }

            // Get form data
            var formData = $(this).serialize();
            //append action to formData
            formData += '&action=updateOffRoadData';
            // Send form data to update script using AJAX
            $.ajax({
                url: "../includes/backend_data.php",
                type: "POST",
                data: formData,
                dataType: "json",

                success: function(response) {

                    if (response.status === "success") {

                        Swal.fire({
                            icon: "success",
                            title: "Updated!",
                            text: response.message,
                            showConfirmButton: true,
                            confirmButtonText: "OK"
                        }).then((result) => {

                            if (result.isConfirmed) {
                                // Close modal first
                                $("#editModal1").modal("hide");
                                // Reload page after OK click
                                location.reload();
                            }
                        });
                    } else {

                        Swal.fire({
                            icon: "error",
                            title: "Error!",
                            text: response.message
                        });
                    }
                },

                error: function() {
                    Swal.fire({
                        icon: "error",
                        title: "Server Error",
                        text: "Something went wrong. Please try again."
                    });
                }
            });
        });
    });
</script>

<?php
include 'ad_footer.php';
?>