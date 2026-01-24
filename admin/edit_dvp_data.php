<?php
include 'ad_nav.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! YouR session is experied please Login'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'HEAD-OFFICE' && $_SESSION['JOB_TITLE'] == 'CME_CO') {

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
    <style>
        .modal-70 {
            max-width: 70% !important;
        }

        .hidden {
            display: none;
        }
    </style>
    <div class="container2">
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
                    <th class='hidden'>ID</th>
                    <th>Date</th>
                    <th>Schedules</th>
                    <th>Vehicles</th>
                    <th>Spare</th>
                    <th>SpareP</th>
                    <th>Docking</th>
                    <th>Fair</th>
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
                    <th class='hidden'>Username</th>
                    <th class='hidden'>Designation</th>
                    <th class='hidden'>Submission Datetime</th>
                    <th>Division</th>
                    <th>Depot</th>
                    <th class="hidden">WUP</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Output table data
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td  class='hidden'>" . $row['id'] . "</td>";
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
                    echo "<td class='hidden'>" . $row['username'] . "</td>";
                    echo "<td class='hidden'>" . $row['designation'] . "</td>";
                    echo "<td class='hidden'>" . $row['submission_datetime'] . "</td>";
                    echo "<td>" . $row['division_name'] . "</td>";
                    echo "<td>" . $row['depot_name'] . "</td>";
                    echo "<td class='hidden'>" . $row['wup1'] . "</td>";
                    echo "<td><button type='button' class='btn btn-primary btn-sm editBtn' data-toggle='modal' data-target='#editModal' data-id='" . $row['id'] . "'>Edit</button> <form action='' method='POST' style='display:inline-block'>
                <button type='button' class='btn btn-danger btn-sm delete-btn' data-id='" . $row['id'] . "'>Delete</button>

              </form></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-70" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit DVP Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <div class="row">

                            <div class="col">
                                <div class="form-group">
                                    <label for="editDepot">Depot:</label>
                                    <input type="text" class="form-control" id="editDepot" name="depot" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editSchedules">date:</label>
                                    <input type="date" class="form-control" id="editdate" name="date" readonly>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="editId" name="editId">
                        <div class="row">

                            <div class="col">
                                <div class="form-group">
                                    <label for="editSchedules">Number of Schdules:</label>
                                    <input type="number" class="form-control" id="editSchedules" name="schedules" oninput="calculateDifference()">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editVehicles">Number of Vehicles(RWY Excluding):</label>
                                    <input type="number" class="form-control" id="editVehicles" name="vehicles" oninput="calculateDifference()">
                                </div>
                            </div>
                        </div>
                        <div class="row">

                            <div class="col">
                                <div class="form-group">
                                    <label for="editSpare">Number of Spare Vehicles(RWY Excluding):</label>
                                    <input type="number" class="form-control" id="editSpare" name="spare" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editORRWY">Vehicles Off Road At RWY:</label>
                                    <input type="number" class="form-control" id="editORRWY" name="ORRWY" oninput="calculateDifference()">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="editSpareP">Spare Vehicles Percentage(RWY Excluding):</label>
                                    <input type="number" class="form-control" id="editSpareP" name="spareP" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editDocking">Number of Docking:</label>
                                    <input type="number" class="form-control" id="editDocking" name="docking" oninput="calculateDifference()">
                                </div>
                            </div>
                        </div>

                        <div class="row">

                            <div class="col">
                                <div class="form-group">
                                    <label for="editORDepot">Vehicles Off Road At Depot:</label>
                                    <input type="number" class="form-control" id="editORDepot" name="ORDepot" oninput="calculateDifference()">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editORDWS">Vehicles Off Road At DWS:</label>
                                    <input type="number" class="form-control" id="editORDWS" name="ORDWS" oninput="calculateDifference()">
                                </div>
                            </div>
                        </div>
                        <div class="row">

                            <div class="col">
                                <div class="form-group">
                                    <label for="editWUP">Vehicles Withdrawn for Fair:</label>
                                    <input type="number" class="form-control" id="editWUP" name="wup" oninput="calculateDifference()">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editUsername">Vehicle Work Under Progress at Depot:</label>
                                    <input type="number" class="form-control" id="editWUP1" name="editWUP1" oninput="calculateDifference()">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="editDealer">Vehicles Held at Authorized Dealer :</label>
                                    <input type="number" class="form-control" id="editDealer" name="dealer" oninput="calculateDifference()">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editCC">Vehicles on CC/Extra Operation:</label>
                                    <input type="number" class="form-control" id="editCC" name="CC" oninput="calculateDifference()">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="editCC">Vehicles loan given to other Depot/Training Center:</label>
                                    <input type="number" class="form-control" id="editloan" name="loan" oninput="calculateDifference()">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editPolice">Vehicles at Police Station:</label>
                                    <input type="number" class="form-control" id="editPolice" name="Police" oninput="calculateDifference()">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="editNotDepot">Vehicles not Arrived to Depot:</label>
                                    <input type="number" class="form-control" id="editNotDepot" name="notdepot" oninput="calculateDifference()">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editORTotal">Vehicle not availavle for operation:</label>
                                    <input type="number" class="form-control" id="editORTotal" name="ORTotal" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="editAvailable">Vehicle availavle for operation:</label>
                                    <input type="number" class="form-control" id="editAvailable" name="available" readonly>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="editES">Vehicle Excess or Shortage:</label>
                                    <input type="number" class="form-control" id="editES" name="ES" readonly>
                                </div>
                            </div>
                        </div>


                        <!-- add update and cancel buttons -->
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                var wup1 = $(this).closest('tr').find('td:eq(24)').text();

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
                $('#editWUP1').val(wup1);
            });
        });

        function calculateDifference() {
            // Get the values of Number of Schedules and Number of Vehicles
            var schedules = parseInt(document.getElementById("editSchedules").value);
            var vehicles = parseInt(document.getElementById("editVehicles").value);
            var docking = parseInt(document.getElementById("editDocking").value);
            var wup = parseInt(document.getElementById("editWUP").value);
            var wup1 = parseInt(document.getElementById("editWUP1").value);
            var ORDepot = parseInt(document.getElementById("editORDepot").value);
            var ORDWS = parseInt(document.getElementById("editORDWS").value);
            var ORRWY = parseInt(document.getElementById("editORRWY").value);
            var CC = parseInt(document.getElementById("editCC").value);
            var Police = parseInt(document.getElementById("editPolice").value);
            var Dealer = parseInt(document.getElementById("editDealer").value);
            var notdepot = parseInt(document.getElementById("editNotDepot").value);
            var loan = parseInt(document.getElementById("editloan").value);

            // Create an array to store all input fields and their corresponding IDs
            var inputs = [{
                    value: schedules,
                    id: "editSchedules"
                },
                {
                    value: vehicles,
                    id: "editVehicles"
                },
                {
                    value: docking,
                    id: "editDocking"
                },
                {
                    value: wup,
                    id: "editWUP"
                },
                {
                    value: wup1,
                    id: "editWUP1"
                },
                {
                    value: ORDepot,
                    id: "editORDepot"
                },
                {
                    value: ORDWS,
                    id: "editORDWS"
                },
                {
                    value: ORRWY,
                    id: "editORRWY"
                },
                {
                    value: CC,
                    id: "editCC"
                },
                {
                    value: Police,
                    id: "editPolice"
                },
                {
                    value: Dealer,
                    id: "editDealer"
                },
                {
                    value: notdepot,
                    id: "editNotDepot"
                },
                {
                    value: loan,
                    id: "editloan"
                }
            ];

            // Check if any input is negative
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].value < 0) {
                    // Alert the user
                    alert("Please Enter a value Greater then 0");

                    // Set the value of the corresponding input field to null
                    document.getElementById(inputs[i].id).value = '';

                    return; // Stop the calculation if a negative value is detected
                }
            }
            // Calculate the difference to find the number of spare vehicles
            var spare = (vehicles - schedules);

            // Update the Number of Spare Vehicles field
            document.getElementById("editSpare").value = spare;

            // Calculate the Spare Vehicles Percentage
            var sparePercentage = (spare * 100 / schedules).toFixed(2);

            // Update the Spare Vehicles Percentage field
            document.getElementById("editSpareP").value = sparePercentage;

            // Calculate the difference to find the number of Off road total vehicles
            var ORTotal = (docking + wup + ORDepot + ORDWS + CC + Police + notdepot + Dealer + loan + wup1);

            // Update the Number of off road Vehicles field
            document.getElementById("editORTotal").value = ORTotal;

            // Calculate the difference to find the number of vehicle available for operation vehicles
            var available = (vehicles - ORTotal);

            // Update the Number of vehicle available for opertion Vehicles field
            document.getElementById("editAvailable").value = available;

            // Calculate the difference to find the number of total Access or shortage vehicles
            var AS = (spare - ORTotal);

            // Update the Number of access or shortage of Vehicles field
            document.getElementById("editES").value = AS;
        }
        $(document).on('click', '.delete-btn', function() {

            const id = $(this).data('id');
            const row = $('#row_' + id);

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: '../includes/backend_data.php',
                        type: 'POST',
                        data: {
                            id: id,
                            action: 'deletedvprow'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire(
                                    'Deleted!',
                                    'The record has been deleted.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message || 'An error occurred while deleting the record.',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'Server error. Please try again later.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
        $('#editForm').on('submit', function(e) {
            e.preventDefault();

            let missingFields = [];
            let firstInvalid = null;

            // Reset previous errors
            $('#editForm .is-invalid').removeClass('is-invalid');

            $('#editForm input[type="number"], #editForm input[type="date"]').each(function() {
                if ($(this).val().trim() === '') {

                    // Get label text
                    let label = $(this).closest('.form-group').find('label').text().trim();
                    label = label !== '' ? label : $(this).attr('name');

                    missingFields.push(label);

                    $(this).addClass('is-invalid');

                    if (!firstInvalid) {
                        firstInvalid = this;
                    }
                }
            });

            // ❌ Validation failed
            if (missingFields.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Required Fields',
                    html: `
                <p>Please fill the following fields:</p>
                <ul style="text-align:left">
                    ${missingFields.map(f => `<li>${f}</li>`).join('')}
                </ul>
            `
                }).then(() => {
                    // Scroll to first missing field
                    $('html, body').animate({
                        scrollTop: $(firstInvalid).offset().top - 120
                    }, 500);
                });

                return;
            }

            // ✅ All fields filled → submit via AJAX
            let formData = $(this).serialize() + '&action=updatedvpdata';
            console.log(formData);

            $.ajax({
                url: '../includes/backend_data.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    Swal.fire({
                        title: 'Saving...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function(response) {
                    Swal.close();

                    if (response.status === 'success') {
                        Swal.fire('Success', response.message, 'success')
                            .then(() => {
                                $('#editModal').modal('hide');
                                location.reload();
                            });
                    } else if (response.status === 'warning') {
                        Swal.fire('Warning', response.message, 'warning');
                    } else {
                        Swal.fire('Error', response.message || 'Update failed', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'AJAX Error',
                        html: `<pre>${xhr.responseText}</pre>`
                    });
                }
            });

        });
    </script>
<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include 'ad_footer.php';
?>