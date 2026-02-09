<?php
include 'ad_nav.php';
?>

<style>
    .dashboard-wrapper {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dashboard-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border-radius: 12px;
    }

    .dashboard-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        background: #f12804ff;
    }

    .dashboard-card h5 {
        font-weight: 600;
        letter-spacing: 0.5px;
    }
</style>

<!-- CONTENT -->
<div class="container-fluid dashboard-wrapper">

    <div class="row text-center w-100 justify-content-center">

        <!-- DVP -->
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='edit_dvp_data.php'">
                <div class="card-body py-5">
                    <h5 class="text-primary">DVP</h5>
                </div>
            </div>
        </div>

        <!-- DIVISIONS -->
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='edit_kmpl_data.php'">
                <div class="card-body py-5">
                    <h5 class="text-success">KMPL</h5>
                </div>
            </div>
        </div>

        <!-- DEPOTS -->
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='edit_bus_details.php'">
                <div class="card-body py-5">
                    <h5 class="text-warning">Buses</h5>
                </div>
            </div>
        </div>

        <!-- BUSES -->
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='edit_offroad_data.php'">
                <div class="card-body py-5">
                    <h5 class="text-danger">Off-Road</h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='program_done_date.php'">
                <div class="card-body py-5">
                    <h5 class="text-primary">program Done Date Restriction</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='me26_restriction_days.php'">
                <div class="card-body py-5">
                    <h5 class="text-success">ME 26 Restriction Days</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='vehicle_logsheet_restriction.php'">
                <div class="card-body py-5">
                    <h5 class="text-warning">Vehicle Logsheet Restriction</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='emergency_program_restriction.php'">
                <div class="card-body py-5">
                    <h5 class="text-danger">Emergency Program Restriction</h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='program_done_date_edit.php'">
                <div class="card-body py-5">
                    <h5 class="text-primary">Program Done Date Edit</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='me26_restriction_days.php'">
                <div class="card-body py-5">
                    <h5 class="text-success">ME 26 Restriction Days</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='vehicle_logsheet_restriction.php'">
                <div class="card-body py-5">
                    <h5 class="text-warning">Vehicle Logsheet Restriction</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card shadow-sm"
                 onclick="location.href='emergency_program_restriction.php'">
                <div class="card-body py-5">
                    <h5 class="text-danger">Emergency Program Restriction</h5>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include 'ad_footer.php'; ?>