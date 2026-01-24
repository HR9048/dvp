<?php
include '../includes/connection.php';
include '../pages/session.php';

/* ---------- SECURITY CHECK ---------- */
if (
    !isset($_SESSION['MEMBER_ID']) ||
    $_SESSION['MEMBER_ID'] !== 1
) {
    echo "<script>
        alert('Access Denied! Super Admin only.');
        window.location = '../pages/logout.php';
    </script>";
    exit;
}

$adminName = $_SESSION['FIRST_NAME'] . ' ' . $_SESSION['LAST_NAME'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>KKRTC DVP Admin</title>
    <link rel="icon" href="../images/logo1.jpeg">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap 4.6 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <!-- jQuery (FULL, not slim) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>

    <!-- Bootstrap 4.6 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- XLSX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../pages/style.css">


    <style>
        body {
            background: #f8f9fc;
        }

        .sidebar {
            min-height: 100vh;
            background: #4e73df;
            color: #fff;
        }

        .sidebar a {
            color: #fff;
            display: block;
            padding: 12px 20px;
            text-decoration: none;
        }

        .sidebar a:hover {
            background: #2e59d9;
            text-decoration: none;
        }

        .topbar {
            background: #fff;
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            font-size: 30px;
            opacity: 0.7;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">



            <!-- MAIN CONTENT -->
            <div class="col-md-12 p-0">

                <!-- TOP BAR -->
                <div class="topbar d-flex justify-content-between">
                    <a href="admin_pannel.php"><h5>Home</h5></a>

                    <div class="d-flex align-items-center">

                        <!-- USER TYPE SELECT -->
                        <form method="post" id="userTypeForm" class="mr-3 mb-0">
                            <select name="userType" id="usertype"
                                class="form-control form-control-sm"
                                onchange="handleUserTypeChange()">
                                <option value="admin">Super Admin</option>
                                <option value="central">Central Office</option>
                            </select>
                        </form>

                        <!-- ICON + NAME -->
                        <span class="text-muted d-flex align-items-center">
                            <i class="fas fa-user-shield mr-1"></i>
                            <?= htmlspecialchars($adminName) ?> &nbsp;
                        </span>
                        <button class="btn btn-danger"><a href="../pages/logout.php">logout</a></button>
                        
                    </div>
                </div>