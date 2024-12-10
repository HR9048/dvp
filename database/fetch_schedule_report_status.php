<?php
include '../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $division = $_POST['division'];
    $depot = $_POST['depot'];

    $query = "SELECT *
              FROM sch_veh_out 
              WHERE division_id = ? AND depot_id = ? and schedule_status in ('1','2','3','4','6','7','8','9')";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $division, $depot);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<table border="1" id="dataTable10">
                <thead>
                    <tr>
                        <th>Schedule No</th>
                        <th>Vehicle No</th>
                        <th>Driver Token No 1</th>
                        <th>Driver 1 Name</th>
                        <th>Driver Token No 2</th>
                        <th>Driver 2 Name</th>
                        <th>Conductor Token No</th>
                        <th>Conductor Name</th>
                        <th>Departure Date</th>
                        <th>Departure Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
        
        while ($row = $result->fetch_assoc()) {
            // Check the schedule status and modify the message accordingly
            $status_message = '';
            switch ($row['schedule_status']) {
                case '1':
                    $status_message = 'Schedule Not Returned';
                    break;
                case '2':
                    $status_message = 'Schedule Not Received by Bunk';
                    break;
                case '3':
                    $status_message = 'Schedule not Received by ramp';
                    break;
                case '4':
                    $status_message = 'Ramp defect not Cleared';
                    break;
                case '6':
                    $status_message = 'Incomplete Schedule not received by Bunk';
                    break;
                case '7':
                    $status_message = 'Incomplete Schedule not received by Ramp';
                    break;
                case '8':
                    $status_message = 'Incomplete Schedule Ramp defect not Cleared';
                    break;
                case '9':
                    $status_message = 'Status ' . $row['schedule_status']; // Keep default status if it's other than 1 or 2
                    break;
                default:
                    $status_message = 'Unknown Status';
            }

            echo '<tr>
                    <td>' . $row['sch_no'] . '</td>
                    <td>' . $row['vehicle_no'] . '</td>
                    <td>' . $row['driver_token_no_1'] . '</td>
                    <td>' . $row['driver_1_name'] . '</td>
                    <td>' . $row['driver_token_no_2'] . '</td>
                    <td>' . $row['driver_2_name'] . '</td>
                    <td>' . $row['conductor_token_no'] . '</td>
                    <td>' . $row['conductor_name'] . '</td>
                    <td>' . (new DateTime($row['departed_date']))->format('d-m-Y') . '</td>
                    <td>' . $row['dep_time'] . '</td>
                    <td>' . $status_message . '</td>
                  </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No records found.</p>';
    }
}
?>
<script>
  $(document).ready(function() {
    $('#dataTable10').DataTable({
      "paging": true, // Enable pagination
      "lengthChange": true, // Enable the row count dropdown
      "searching": true, // Enable search functionality
      "ordering": true, // Enable sorting
      "info": true, // Show table information summary
      "autoWidth": true // Automatically adjust column widths
    });
  });
</script>
