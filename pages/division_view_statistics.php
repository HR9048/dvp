<?php
include '../includes/connection.php';
include '../includes/division_sidebar.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

// Check user role
if ($_SESSION['TYPE'] == 'DIVISION' || $_SESSION['JOB_TITLE'] == 'ASO(Stat)') {
    $division_id = $_SESSION['DIVISION_ID'];
?>
    <div class="container">
        <h2>Uploaded Operational Statistics Files</h2>
        <table border="1" id="dataTable">
            <thead>
                <tr>
                    <th>Depot</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

                <?php
                // Fetch uploaded files from the database
                $query = "SELECT os.*, l.depot 
FROM operational_statistics os
INNER JOIN location l ON os.depot_id = l.depot_id
WHERE os.division_id = '$division_id'
ORDER BY os.date DESC;
";
                $result = mysqli_query($db, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                        <td>" . htmlspecialchars($row['depot']) . "</td>
                        <td>" . date("d-m-Y", strtotime($row['date'])) . "</td>
                        <td>
                            <a href='../../uploads/" . urlencode($row['file_name']) . "' target='_blank'>View</a> |
                            <a href='../../uploads/" . urlencode($row['file_name']) . "' download>Download</a>
                        </td>
                      </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center;'>No files uploaded yet.</td></tr>";
                }
                ?>

            </tbody>
        </table>
    </div>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>