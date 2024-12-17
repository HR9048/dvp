<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
  echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
  exit;
}
if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'ASO(Stat)' || $_SESSION['JOB_TITLE'] == 'DC' || $_SESSION['JOB_TITLE'] == 'DTO') {
  // Allow access
  ?>


  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h4 class="m-2 font-weight-bold text-primary" style="display: inline-block;">Buses</h4>
      <!-- <h4 class="m-2 font-weight-bold text-primary" style="display: inline-block;">Buses&nbsp;<a href="division_bus_allocation.php" type="button"
                class="btn btn-primary bg-gradient-primary" style="border-radius: 0px;"><i
                    class="fas fa-fw fa-plus"></i></a></h4> -->
      <?php if ($_SESSION['TYPE'] == 'DIVISION' && $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'DC') { ?>
        <button class="btn btn-primary ml-3"><a href="division_bus_transfer.php"
            style="color: white; text-decoration: none;">Bus Transfer</a></button>
        <!-- Button trigger modal -->
        <button class="btn btn-primary ml-3" data-toggle="modal" data-target="#confirmationModal">Bus Scrap</button>
        <!--<button class="btn btn-primary ml-3" data-toggle="modal" data-target="#confirmationModal1">Bus chassis convert</button>-->
      <?php } ?>
      <!-- Modal -->
      <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="confirmationModalLabel">Confirmation</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              Are you sure you want to scrap the vehicle?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
              <button type="button" class="btn btn-primary" onclick="confirmScrap()">Yes</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="confirmationModal1" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="confirmationModalLabel">Confirmation</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              Are you sure you want to convert the Chassis of the vehicle?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
              <button type="button" class="btn btn-primary" onclick="confirmchassis()">Yes</button>
            </div>
          </div>
        </div>
      </div>
      <script>
        function confirmScrap() {
          window.location.href = "division_bus_Scrap.php";
          $('#confirmationModal').modal('hide');
        }
        function confirmchassis() {
          window.location.href = "division_bus_chassis_convert.php";
          $('#confirmationModal1').modal('hide');
        }
      </script>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table id="dataTable3">
          <thead>
            <tr>
              <th>Sl No.</th>
              <th>Bus Number</th>
              <th>Division Name</th>
              <th>Depot Name</th>
              <th>Make</th>
              <th>Emission norms</th>
              <th>DOC</th>
              <th>Wheel Base</th>
              <th>Chassis Number</th>
              <th>Bus Category</th>
              <th>Bus Sub Category</th>
              <th>Seating Capacity</th>
              <th>Bus Body Builder</th>
              <!-- <th>Action</th> -->
            </tr>
          </thead>
          <tbody>
            <?php
            // Prepare the SQL query with a JOIN operation
            $query = 'SELECT 
br.*,
l1.division AS division_name,
l2.depot AS depot_name
FROM 
bus_registration br
INNER JOIN 
(SELECT DISTINCT division, division_id FROM location) AS l1 ON br.division_name = l1.division_id
INNER JOIN 
(SELECT DISTINCT depot, depot_id FROM location) AS l2 ON br.depot_name = l2.depot_id
WHERE 
br.division_name = ?
GROUP BY 
br.bus_number order by depot_id;'; // Assuming you want to filter by division_id, adjust accordingly if needed
          
            // Prepare the statement
            $stmt = $db->prepare($query);

            // Bind the division ID from the session variable to the statement
            $stmt->bind_param("i", $_SESSION['DIVISION_ID']); // Assuming division_id is an integer, adjust if needed
          
            // Execute the statement
            $stmt->execute();

            // Get the result
            $result = $stmt->get_result();

            // Check if there are any rows returned
            if ($result->num_rows > 0) {
              // Initialize counter variable
              $counter = 1;

              // Fetch and display the results as needed
              while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $counter++ . '</td>'; // Output and increment counter
                echo '<td>' . $row['bus_number'] . '</td>';
                echo '<td>' . $row['division_name'] . '</td>';
                echo '<td>' . $row['depot_name'] . '</td>';
                echo '<td>' . $row['make'] . '</td>';
                echo '<td>' . $row['emission_norms'] . '</td>';
                echo '<td>' . date('d/m/Y', strtotime($row['doc'])) . '</td>';
                echo '<td>' . $row['wheel_base'] . '</td>';
                echo '<td>' . $row['chassis_number'] . '</td>';
                echo '<td>' . $row['bus_category'] . '</td>';
                echo '<td>' . $row['bus_sub_category'] . '</td>';
                echo '<td>' . $row['seating_capacity'] . '</td>';
                echo '<td>' . $row['bus_body_builder'] . '</td>';
                // Add any additional columns or actions as needed
                echo '</tr>';
              }
            } else {
              // If no rows are returned, display a message
              echo '<tr><td colspan="13">No data available</td></tr>';
            }
            ?>
          </tbody>
          <tfoot>
            <tr>
              <th></th>
              <th>Bus Number</th>
              <th>Division Name</th>
              <th>Depot Name</th>
              <th>Make</th>
              <th>Emission norms</th>
              <th>DOC</th>
              <th>Wheel Base</th>
              <th>Chassis Number</th>
              <th>Bus Category</th>
              <th>Bus Sub Category</th>
              <th>Seating Capacity</th>
              <th>Bus Body Builder</th>
            </tr>
          </tfoot>
        </table>
      </div>
      <button class="btn btn-success" id="downloadExcel1">Download Excel</button>
    </div>
  </div>

  <?php
} else {
  echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
  exit;
}
include '../includes/footer.php';
?>