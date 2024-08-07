<?php
include '../includes/connection.php';
include '../includes/update_sidebar.php';

$member_id = $_SESSION['MEMBER_ID'];

$query = "SELECT PASSWORD FROM users WHERE ID = '$member_id'";
$result = mysqli_query($db, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $password = $row['PASSWORD'];

    // Check if the password is only numbers, only characters, or lacks special characters
    if (ctype_digit($password) || ctype_alpha($password) || !preg_match('/[^\w]/', $password)) {
        // Password is numeric, alphabetic, or lacks special characters
        echo "<script type='text/javascript'>
                $(document).ready(function() {
                    $('#passwordWarningModal').modal('show');
                });
              </script>";
    } else {
        // Password contains special characters, redirect to processlogin.php
        echo "<script type='text/javascript'>
                window.location.href = 'processlogin.php';
              </script>";
    }
}
?>

<!-- Password Warning Modal -->
<div class="modal fade" id="passwordWarningModal" tabindex="-1" role="dialog" aria-labelledby="passwordWarningModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-center">
                <h5 class="modal-title" id="passwordWarningModalLabel">Password Update Required</h5>
            </div>
            <div class="modal-body text-center">
                The given password is common. Please update your password.
            </div>
            <div class="modal-footer d-flex justify-content-center">
                <button type="button" class="btn btn-primary" id="openSettingsModal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Include Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


<script type="text/javascript">
    $(document).ready(function () {
        var settingsModalOpened = false;

        $('#openSettingsModal').on('click', function () {
            settingsModalOpened = true;
            $('#passwordWarningModal').modal('hide');
            $('#settingsModal').modal('show');
        });

        $('#passwordWarningModal').on('hidden.bs.modal', function () {
            if (!settingsModalOpened) {
                location.reload();
            }
        });

        $('#settingsModal').on('hidden.bs.modal', function () {
            location.reload();
        });
    });
</script>





<?php include '../includes/footer.php'; ?>