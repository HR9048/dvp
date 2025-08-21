<?php
require("../includes/connection.php");

// Get form data
$startDate  = $_POST['startDate'] ?? '';
$divisionId = $_POST['division_id'] ?? 'All';
$depotId    = $_POST['depot_id'] ?? 'All';

// Validation
if (empty($startDate)) {
    echo "<p style='color:red;'>Start Date is required.</p>";
    exit;
}

// ------------------ CARD DATA ------------------
$whereSchedule = " WHERE status != '0'";
$whereOperated = " WHERE departed_date = ?";
$paramsSchedule = [];
$paramsOperated = [$startDate];
$typesSchedule = "";
$typesOperated = "s";

if ($divisionId !== "All") {
    $whereSchedule .= " AND division_id = ?";
    $paramsSchedule[] = $divisionId;
    $typesSchedule .= "i";

    $whereOperated .= " AND division_id = ?";
    $paramsOperated[] = $divisionId;
    $typesOperated .= "i";
}
if ($depotId !== "All") {
    $whereSchedule .= " AND depot_id = ?";
    $paramsSchedule[] = $depotId;
    $typesSchedule .= "i";

    $whereOperated .= " AND depot_id = ?";
    $paramsOperated[] = $depotId;
    $typesOperated .= "i";
}

// Total Schedules
$queryTotal = "SELECT COUNT(*) as total_schedules FROM schedule_master $whereSchedule";
$stmt = $db->prepare($queryTotal);
if (!empty($paramsSchedule)) $stmt->bind_param($typesSchedule, ...$paramsSchedule);
$stmt->execute();
$totalSchedules = $stmt->get_result()->fetch_assoc()['total_schedules'] ?? 0;
$stmt->close();

// Operated Schedules
$queryOperated = "SELECT COUNT(*) as operated_schedules FROM sch_veh_out $whereOperated";
$stmt = $db->prepare($queryOperated);
$stmt->bind_param($typesOperated, ...$paramsOperated);
$stmt->execute();
$operatedSchedules = $stmt->get_result()->fetch_assoc()['operated_schedules'] ?? 0;
$stmt->close();

// Full cancellations
$fullCancellations = $totalSchedules - $operatedSchedules;
$cancellationPercent = ($totalSchedules > 0) ? round(($fullCancellations / $totalSchedules) * 100, 2) : 0;

// ------------------ GRAPH DATA (Last 7 Days) ------------------
$graphData = [];

// Build last 7 days from startDate
$dateList = [];
for ($i = 6; $i >= 0; $i--) {
    $dateList[] = date('Y-m-d', strtotime($startDate . " -$i day"));
}

foreach ($dateList as $d) {
    // Total schedules for that day (same always, since schedule_master doesn’t have date filter)
    $queryT = "SELECT COUNT(*) as total_schedules FROM schedule_master $whereSchedule";
    $stmtT = $db->prepare($queryT);
    if (!empty($paramsSchedule)) $stmtT->bind_param($typesSchedule, ...$paramsSchedule);
    $stmtT->execute();
    $total = $stmtT->get_result()->fetch_assoc()['total_schedules'] ?? 0;
    $stmtT->close();

    // Operated schedules for that day
    $queryO = "SELECT COUNT(*) as operated_schedules FROM sch_veh_out WHERE departed_date = ?";
    $paramsO = [$d];
    $typesO = "s";
    if ($divisionId !== "All") {
        $queryO .= " AND division_id = ?";
        $paramsO[] = $divisionId;
        $typesO .= "i";
    }
    if ($depotId !== "All") {
        $queryO .= " AND depot_id = ?";
        $paramsO[] = $depotId;
        $typesO .= "i";
    }

    $stmtO = $db->prepare($queryO);
    $stmtO->bind_param($typesO, ...$paramsO);
    $stmtO->execute();
    $operated = $stmtO->get_result()->fetch_assoc()['operated_schedules'] ?? 0;
    $stmtO->close();

    $cancelled = $total - $operated;
    $percent = ($total > 0) ? round(($cancelled / $total) * 100, 2) : 0;

    $graphData[] = [
        "date" => $d,
        "total" => $total,
        "operated" => $operated,
        "cancelled" => $cancelled,
        "percent" => $percent
    ];
}

echo "<h6>Operational Statistics Dashboard</h6>";

echo "<div class='row'>";
echo "<div class='col'><div class='card' style='width: 8rem;'>
  <div class='card-body'>
    <h5 class='card-title'>Total Schedules</h5>
    <p class='card-text'>$totalSchedules</p>
    
  </div>
</div>

  </div>
  <div class='col'><div class='card' style='width: 8rem;'>
  <div class='card-body'>
    <h5 class='card-title'>Operated Schedules</h5>
    <p class='card-text'>$operatedSchedules</p>

  </div>
</div>

  </div>
</div><br>";
echo "<div class='row'>";
echo "<div class='col'><div class='card' style='width: 8rem;'>
  <div class='card-body'>
    <h5 class='card-title'>Cancellation</h5>
    <p class='card-text'>$fullCancellations</p>

  </div>
</div>

  </div>
  <div class='col'><div class='card' style='width: 8rem;'>
  <div class='card-body'>
    <h5 class='card-title'>Cancellation %</h5>
    <p class='card-text'>$cancellationPercent %</p>

  </div>
</div>

  </div>
</div>";
// Graph placeholder
echo "<div class='card p-3 mt-3'>
        <h6>Cancellation % (Last 7 Days)</h6>
        <canvas id='cancelGraph'></canvas>
      </div>";

// Pass graph data as JSON for JS
echo "<script>
        var graphData = " . json_encode($graphData) . ";

        var ctx = document.getElementById('cancelGraph').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: graphData.map(d => d.date),
                datasets: [{
                    label: 'Cancellation %',
                    data: graphData.map(d => d.percent),
                    borderColor: 'red',
                    fill: false,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Cancellation %' }
                    },
                    x: {
                        title: { display: true, text: 'Date' }
                    }
                }
            }
        });
      </script>";
?>
