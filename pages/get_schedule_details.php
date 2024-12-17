<?php
include '../includes/connection.php'; // Your database connection file
include '../pages/session.php';
confirm_logged_in();
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! Your session is experied please Login'); window.location = '../pages/logout.php';</script>";
    exit;
}
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT 
    skm.*, 
    loc.division, 
    loc.depot, 
    sc.name AS service_class_name, 
    st.type AS service_type_name,
    -- Separate columns for driver 171status
    MAX(CASE WHEN cfd.designation = 'Driver' AND cfd.crew_pf = skm.driver_pf_1 THEN cfd.`171status` END) AS `Driver_1_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Driver' AND cfd.crew_pf = skm.driver_pf_2 THEN cfd.`171status` END) AS `Driver_2_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Driver' AND cfd.crew_pf = skm.driver_pf_3 THEN cfd.`171status` END) AS `Driver_3_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Driver' AND cfd.crew_pf = skm.driver_pf_4 THEN cfd.`171status` END) AS `Driver_4_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Driver' AND cfd.crew_pf = skm.driver_pf_5 THEN cfd.`171status` END) AS `Driver_5_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Driver' AND cfd.crew_pf = skm.driver_pf_6 THEN cfd.`171status` END) AS `Driver_6_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Driver' AND cfd.crew_pf = skm.offreliverdriver_pf_1 THEN cfd.`171status` END) AS `Driver_1_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Driver' AND cfd.crew_pf = skm.offreliverdriver_pf_2 THEN cfd.`171status` END) AS `offreliverDriver_2_171_Status`,
    -- Separate columns for conductor 171status
    MAX(CASE WHEN cfd.designation = 'Conductor' AND cfd.crew_pf = skm.conductor_pf_1 THEN cfd.`171status` END) AS `Conductor_1_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Conductor' AND cfd.crew_pf = skm.conductor_pf_2 THEN cfd.`171status` END) AS `Conductor_2_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Conductor' AND cfd.crew_pf = skm.conductor_pf_3 THEN cfd.`171status` END) AS `Conductor_3_171_Status`,
    MAX(CASE WHEN cfd.designation = 'Conductor' AND cfd.crew_pf = skm.offreliverconductor_pf_1 THEN cfd.`171status` END) AS `offreliverConductor_1_171_Status`,
    skm.ID AS ID,
    (
        CASE 
            WHEN skm.driver_token_1 IS NOT NULL AND skm.driver_token_1 <> '' THEN 1 ELSE 0 END +
        CASE 
            WHEN skm.driver_token_2 IS NOT NULL AND skm.driver_token_2 <> '' THEN 1 ELSE 0 END +
        CASE 
            WHEN skm.driver_token_3 IS NOT NULL AND skm.driver_token_3 <> '' THEN 1 ELSE 0 END +
        CASE 
            WHEN skm.driver_token_4 IS NOT NULL AND skm.driver_token_4 <> '' THEN 1 ELSE 0 END +
        CASE 
            WHEN skm.driver_token_5 IS NOT NULL AND skm.driver_token_5 <> '' THEN 1 ELSE 0 END +
        CASE 
            WHEN skm.driver_token_6 IS NOT NULL AND skm.driver_token_6 <> '' THEN 1 ELSE 0 END 
    ) AS driver_count,
    (
        CASE 
            WHEN skm.conductor_token_1 IS NOT NULL AND skm.conductor_token_1 <> '' THEN 1 ELSE 0 END +
        CASE 
            WHEN skm.conductor_token_2 IS NOT NULL AND skm.conductor_token_2 <> '' THEN 1 ELSE 0 END +
        CASE 
            WHEN skm.conductor_token_3 IS NOT NULL AND skm.conductor_token_3 <> '' THEN 1 ELSE 0 END 
    ) AS conductor_count,
    (
        CASE 
            WHEN skm.offreliverdriver_token_1 IS NOT NULL AND skm.offreliverdriver_token_1 <> '' THEN 1 ELSE 0 END +
        CASE 
            WHEN skm.offreliverdriver_token_2 IS NOT NULL AND skm.offreliverdriver_token_2 <> '' THEN 1 ELSE 0 END
    ) AS offreliverdriver_count,
    (
        CASE 
            WHEN skm.offreliverconductor_token_1 IS NOT NULL AND skm.offreliverconductor_token_1 <> '' THEN 1 ELSE 0 END
    ) AS offreliverconductor_count
FROM 
    schedule_master skm 
JOIN 
    location loc 
    ON skm.division_id = loc.division_id 
    AND skm.depot_id = loc.depot_id
LEFT JOIN 
    service_class sc 
    ON skm.service_class_id = sc.id
LEFT JOIN 
    schedule_type st 
    ON skm.service_type_id = st.id
LEFT JOIN 
    crew_fix_data cfd 
    ON cfd.division_id = skm.division_id 
    AND cfd.depot_id = skm.depot_id 
    AND cfd.sch_key_no = skm.sch_key_no 
    AND cfd.to_date IS NULL
WHERE 
    skm.ID = $id";

    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        $details = $result->fetch_assoc();
        echo json_encode($details);
    } else {
        echo json_encode(['error' => 'No details found']);
    }
} else {
    // Redirect to login.php if accessed directly without POST data
    header("Location: login.php");
    exit;
}
?>