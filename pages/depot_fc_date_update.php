<?php


include '../includes/connection.php';
include '../includes/depot_top.php';

// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'Mech') {
    $depot_id = $_SESSION['DEPOT_ID'];
    $division_id = $_SESSION['DIVISION_ID'];


    $query = "SELECT `id`, `bus_number`, `date_of_fc` FROM `bus_inventory` WHERE depot_id = ? AND division_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $depot_id, $division_id);
    $stmt->execute();
    $result = $stmt->get_result();
?>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Bus Number</th>
                <th>FC Date</th>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['bus_number']) ?></td>
                    <td><?= htmlspecialchars($row['date_of_fc']) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm editBtn"
                            data-id="<?= $row['id'] ?>"
                            data-bus="<?= $row['bus_number'] ?>"
                            data-fc="<?= $row['date_of_fc'] ?>">
                            Edit
                        </button>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <!-- Edit Modal -->
    <div class="modal fade" id="editFCModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="updateFcForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit FC Date</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">Bus Number</label>
                            <input type="text" id="edit_bus_number" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">FC Date</label>
                            <input type="date" id="edit_fc_date" name="fc_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Update</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.querySelectorAll('.editBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const bus = this.getAttribute('data-bus');
                const fc = this.getAttribute('data-fc');

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_bus_number').value = bus;
                document.getElementById('edit_fc_date').value = fc;

                new bootstrap.Modal(document.getElementById('editFCModal')).show();
            });
        });

        document.getElementById('updateFcForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const fcDateInput = document.getElementById('edit_fc_date').value;
            const fcDate = new Date(fcDateInput);

            const baseDate = new Date('2025-03-31');
            const minDate = new Date('2020-03-31');
            const maxDate = new Date('2030-03-31');

            if (fcDate < minDate || fcDate > maxDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid FC Date',
                    text: 'FC Date must be between 2020-03-31 and 2030-03-31',
                });
                return; // Stop form submission
            }

            const formData = new FormData(this);

            fetch('update_fc_date.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(data => {
                    Swal.fire("Updated!", data, "success").then(() => location.reload());
                })
                .catch(() => {
                    Swal.fire("Error", "Failed to update date", "error");
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