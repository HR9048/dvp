<?php
include '../includes/connection.php';
include 'session.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script>alert('Restricted Page! Your session has expired. Please login again.'); window.location='logout.php';</script>";
    exit;
}

if ($_SESSION['TYPE'] == 'HEAD-OFFICE'
    && $_SESSION['JOB_TITLE'] == 'CME_CO'
    && $_SESSION['MEMBER_ID'] == '1') {
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Selection</title>
  <link rel="icon" href="../images/logo1.jpeg">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            min-height: 100vh;
        }
        .card {
            border-radius: 12px;
            border: none;
        }
        .card-header {
            background: #4e73df;
            color: #fff;
            font-size: 18px;
        }
        .form-control {
            height: 45px;
            font-size: 15px;
        }
        .btn-primary {
            background: #4e73df;
            border: none;
            height: 45px;
            font-size: 16px;
        }
        .btn-primary:hover {
            background: #2e59d9;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-md-5">

            <div class="card shadow-lg">
                <div class="card-header text-center font-weight-bold">
                    <i class="fas fa-user-shield mr-2"></i> Select User Type
                </div>

                <div class="card-body text-center">
                    <p class="mb-4 text-muted">Please choose how you want to continue</p>

                    <select id="userType" class="form-control mb-4">
                        <option value="">-- Select User Type --</option>
                        <option value="central">Central Office</option>
                        <option value="admin">Super Admin</option>
                    </select>

                    <button class="btn btn-primary btn-block" onclick="redirectUser()">
                        <i class="fas fa-arrow-right mr-1"></i> Continue
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function redirectUser() {
    const userType = document.getElementById('userType').value;

    if (userType === 'central') {
        window.location.href = 'index.php';
    } else if (userType === 'admin') {
        window.location.href = '../admin/admin_pannel.php';
    } else {
        alert('Please select a user type');
    }
}
</script>

</body>
</html>

<?php
} else {
    echo "<script>alert('Restricted Page! You will be redirected.'); window.location='login.php';</script>";
    exit;
}
?>
