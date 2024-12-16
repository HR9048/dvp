<?php
include '../includes/connection.php';
include '../includes/depot_top.php';
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE']) || !isset($_SESSION['DEPOT_ID']) || !isset($_SESSION['DIVISION_ID']) || !isset($_SESSION['KMPL_DEPOT']) || !isset($_SESSION['KMPL_DIVISION'])) {
    echo "<script type='text/javascript'>alert('Session Experied! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'T_INSPECTOR') {
    $division = $_SESSION['DIVISION_ID'];
                $depot = $_SESSION['DEPOT_ID'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['employeeId'])) {
        $employeeId = $_POST['employeeId'];
    
        // Your SQL query to release the employee (you might want to set status to '0' or similar)
        $query = "UPDATE private_employee SET status='0' WHERE id=? and division_id = ? and depot_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iii", $employeeId, $division, $depot);
        
        if ($stmt->execute()) {
            echo "<script>alert('Employee released successfully.'); window.location = 'depot_manp_emp.php';</script>";
        } else {
            echo "<script>alert('Error releasing employee.'); window.location = 'your_previous_page.php';</script>";
        }
    
        $stmt->close();
    }
    ?>
    <style>
        .hide {
            display: none;
        }
    </style>
    <div class="container mt-5">
        <h2 class="text-center">Private Employee List</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="hide">ID</th>
                    <th>PF Number</th>
                    <th>Division</th>
                    <th>Depot</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Token Number</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

                <?php
                $division = $_SESSION['DIVISION_ID'];
                $depot = $_SESSION['DEPOT_ID'];

                // Prepare the SQL statement
                $stmt = $db->prepare("SELECT * FROM private_employee WHERE Division_id=? AND Depot_id=? AND status='1'");
                if (!$stmt) {
                    die("Prepare failed: " . mysqli_error($db));
                }
                // Bind the parameters
                $stmt->bind_param("ii", $division, $depot);
                $stmt->execute();
                $result = $stmt->get_result();
                if (!$result) {
                    die("Query failed: " . mysqli_error($db));
                }
                while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="hide"><?php echo $row['id']; ?></td>
                        <td><?php echo $row['EMP_PF_NUMBER']; ?></td>
                        <td><?php echo $row['Division']; ?></td>
                        <td><?php echo $row['Depot']; ?></td>
                        <td><?php echo $row['EMP_NAME']; ?></td>
                        <td><?php echo $row['EMP_DESGN_AT_APPOINTMENT']; ?></td>
                        <td><?php echo $row['token_number']; ?></td>
                        <td>
    <button type="submit" 
            class="btn btn-danger" 
            onclick="confirmRelease('<?php echo $row['id']; ?>', '<?php echo $row['EMP_NAME']; ?>')">
        Release
    </button>
</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script>
function confirmRelease(employeeId, employeeName) {
    const confirmed = confirm(`Are you sure you want to release ${employeeName}?`);
    if (confirmed) {
        // Create a form programmatically
        const form = document.createElement('form');
        form.method = 'POST';
        // Create an input to hold the employee ID
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'employeeId';
        input.value = employeeId;
        form.appendChild(input);

        // Append the form to the body and submit it
        document.body.appendChild(form);
        form.submit();
    }
}
</script>


    <?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'login.php';</script>";
    exit;
}
include '../includes/footer.php';
?>