<?php
require ('../pages/session.php');
require_once '../includes/connection.php'; // Include the db.php file to access the database connection

confirm_logged_in();

function fetchDivision()
{
    global $db;
    $query = "SELECT DISTINCT DIVISION, division_id FROM location";
    $result = $db->query($query);
    $divisions = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $divisions[] = array(
                'DIVISION' => $row['DIVISION'],
                'division_id' => $row['division_id']
            );
        }
    }
    return $divisions;
}
function fetchDivision1()
{
    global $db;
    $query = "SELECT DISTINCT DIVISION FROM location";
    $result = $db->query($query);
    $Division = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $Division[] = $row['DIVISION'];
        }
    }
    return $Division;
}

function fetchDepot()
{
    global $db;
    if (isset($_POST['division']) && !empty($_POST['division'])) {
        // Sanitize the input to prevent SQL injection
        $Division = $_POST['division'];
        $query = "SELECT depot_id, DEPOT FROM location WHERE division_id = ?";

        // Prepare the statement
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $Division);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<option value='' disabled selected>Select Depot</option>";

        // Check if there are any results
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                if ($row['DEPOT'] !== 'DIVISION') {
                    // Output each option with depot_id as value and DEPOT as the visible text
                    echo "<option value='" . $row['depot_id'] . "'>" . $row['DEPOT'] . "</option>";
                }
            }
        } else {
            echo "<option value=''>No depot found</option>";
        }
    }
}
function fetchDepot1()
{
    global $db;
    if (isset($_POST['division']) && !empty($_POST['division'])) {
        // Sanitize the input to prevent SQL injection
        $Division = $_POST['division'];
        $query = "SELECT DEPOT,depot_id FROM location WHERE division = ?";

        // Prepare the statement
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $Division);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<option value='' disabled selected>Select Depot</option>";

        // Check if there are any results
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                if ($row['DEPOT'] !== 'DIVISION') {
                    // Output each option with depot_id as value and DEPOT as the visible text
                    echo "<option value='" . $row['depot_id'] . "'>" . $row['DEPOT'] . "</option>";
                }
            }
        } else {
            echo "<option value=''>No depot found</option>";
        }
    }
}
// Function to fetch makes
function fetchMakes()
{
    global $db;
    $query = "SELECT * FROM makes";
    $result = $db->query($query);
    $makes = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $makes[] = $row['make'];
        }
    }
    return $makes;
}

// Function to fetch emission norms
function fetchEmissionNorms()
{
    global $db;
    $query = "SELECT * FROM norms";
    $result = $db->query($query);
    $emissionNorms = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $emissionNorms[] = $row['emission_norms'];
        }
    }
    return $emissionNorms;

}

// Function to fetch wheel base
function fetchWheelBase()
{
    global $db;
    $query = "SELECT * FROM wheelbase";
    $result = $db->query($query);
    $wheelBase = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $wheelBase[] = $row['wheel_base'];
        }
    }
    return $wheelBase;
}

// Function to fetch body builder
function fetchBodyBuilder()
{
    global $db;
    $query = "SELECT * FROM bus_body_builder";
    $result = $db->query($query);
    $bodyBuilders = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bodyBuilders[] = $row['body_type'];
        }
    }
    return $bodyBuilders;
}
function fetchDesignation()
{
    global $db;
    $query = "SELECT JOB_TITLE FROM job";
    $result = $db->query($query);
    $Designation = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $Designation[] = $row['JOB_TITLE'];
        }
    }
    return $Designation;
}
function fetchBusCategory()
{
    global $db;
    $query = "SELECT DISTINCT bus_category FROM bus_seat_category";
    $result = $db->query($query);
    $busCategory = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $busCategory[] = $row['bus_category'];
        }
    }
    return $busCategory;
}

function fetchBusSubCategory()
{
    global $db;
    if (isset($_POST['bus_category']) && !empty($_POST['bus_category'])) {
        // Sanitize the input to prevent SQL injection
        $busCategory = $_POST['bus_category'];
        $query = "SELECT bus_sub_category FROM bus_seat_category WHERE bus_category = '$busCategory'";
        echo "<option value='' disabled selected>Select Bus Sub Category</option>";
        $result = $db->query($query);

        // Check if there are any results
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['bus_sub_category'] . "'>" . $row['bus_sub_category'] . "</option>";
            }
        } else {
            echo "<option value=''>No bus sub-categories found</option>";
        }
    }
}
function fetchOffroadLocation()
{
    global $db;
    $query = "SELECT DISTINCT location_id, location_name FROM off_road_location";
    $result = $db->query($query);
    $Location = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $Location[] = $row['location_name'];
        }
    }
    return $Location;
}
function fetchReason()
{
    global $db;
    if (isset($_POST['offRoadLocation']) && !empty($_POST['offRoadLocation'])) {
        // Sanitize the input to prevent SQL injection
        $Reason = $_POST['offRoadLocation'];
        $query = "SELECT r.reason_name
        FROM reason r
        INNER JOIN off_road_location o ON r.location_id = o.location_id
        WHERE o.location_name = '$Reason'";

        $result = $db->query($query);

        // Check if there are any results
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                // Check if the reason name is not null or empty
                if ($row['reason_name'] !== null && $row['reason_name'] !== '') {
                    // Echo each option as a checkbox wrapped in a div
                    echo '<div class="form-check my-1">';
                    echo '<input class="form-check-input" type="checkbox" name="partsRequired[]" value="' . $row['reason_name'] . '">';
                    echo '<label class="form-check-label d-block">' . $row['reason_name'] . '</label>';
                    echo '</div>';
                    echo '</div>';
                }
            }
        }
    }
}

function ServiceClass()
{
    global $db;
    $query = "SELECT id, name FROM service_class";
    $result = $db->query($query);
    $service = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $service[] = array(
                'id' => $row['id'],
                'name' => $row['name']
            );
        }
    }
    return $service;
}
function ScheduleType()
{
    global $db;
    $query = "SELECT id,type FROM schedule_type";
    $result = $db->query($query);
    $schedule = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schedule[] = array(
                'id' => $row['id'],
                'type' => $row['type']
            );
        }
    }
    return $schedule;
}
function cameradefecttype()
{
    global $db;
    $query = "SELECT id, defect_name FROM depot_camera_defect_type";
    $result = $db->query($query);
    $defect = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $defect[] = array(
                'id' => $row['id'],
                'defect_name' => $row['defect_name']
            );
        }
    }
    return $defect;
}

function fetchSchedule()
{
    global $db;
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];
    $todays_date = date('Y-m-d');

    $query = "SELECT sm.sch_key_no 
    FROM schedule_master sm
    LEFT JOIN sch_veh_out svo
    ON sm.sch_key_no = svo.sch_no 
    AND svo.division_id = '$division_id' 
    AND svo.depot_id = '$depot_id' 
    AND svo.departed_date = '$todays_date'
    WHERE sm.division_id = '$division_id' 
    AND sm.depot_id = '$depot_id'
    AND sm.status='1'
    AND svo.sch_no IS NULL order by sm.sch_dep_time ASC";
    $result = $db->query($query);
    $schno = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schno[] = $row['sch_key_no'];
        }
    }
    return $schno;
}

function fetchScheduleIn()
{
    global $db;
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    $query = "SELECT DISTINCT  svo.sch_no 
              FROM schedule_master sm
              INNER JOIN sch_veh_out svo
              ON sm.sch_key_no = svo.sch_no 
              AND svo.division_id = '$division_id' 
              AND svo.depot_id = '$depot_id' 
              AND svo.schedule_status = 1
              WHERE sm.division_id = '$division_id' 
              AND sm.depot_id = '$depot_id'
              ORDER BY sm.sch_arr_time ASC";
    
    $result = $db->query($query);
    $schno = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schno[] = $row['sch_no'];
        }
    }
    return $schno;
}
function driverdefecttype()
{
    global $db;
    $query = "SELECT id, defect_name FROM driver_defect";
    $result = $db->query($query);
    $defect = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $defect[] = array(
                'id' => $row['id'],
                'defect_name' => $row['defect_name']
            );
        }
    }
    return $defect;
}
function rampdefecttype()
{
    global $db;
    $query = "SELECT id, defect_name FROM ramp_defect";
    $result = $db->query($query);
    $defect = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $defect[] = array(
                'id' => $row['id'],
                'defect_name' => $row['defect_name']
            );
        }
    }
    return $defect;
}
// Check if an action is specified in the request
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Call the appropriate function based on the action
    switch ($action) {
        case 'fetchMakes':
            echo json_encode(fetchMakes());
            break;
        case 'fetchEmissionNorms':
            echo json_encode(fetchEmissionNorms());
            break;
        case 'fetchWheelBase':
            echo json_encode(fetchWheelBase());
            break;
        case 'fetchBodyBuilder':
            echo json_encode(fetchBodyBuilder());
            break;
        case 'fetchBusCategory':
            echo json_encode(fetchBusCategory());
            break;
        case 'fetchBusSubCategory':
            echo json_encode(fetchBusSubCategory());
            break;
        case 'fetchOffroadLocation':
            echo json_encode(fetchOffroadLocation());
            break;
        case 'fetchReason':
            echo json_encode(fetchReason());
            break;
        case 'fetchDivision':
            echo json_encode(fetchDivision());
            break;
        case 'fetchDepot':
            echo json_encode(fetchDepot());
            break;
        case 'fetchDesignation':
            echo json_encode(fetchDesignation());
            break;
        case 'fetchDepot1':
            echo json_encode(fetchDepot1());
            break;
        case 'fetchDivision1':
            echo json_encode(fetchDivision1());
            break;
        case 'ServiceClass':
            echo json_encode(ServiceClass());
            break;
        case 'ScheduleType':
            echo json_encode(ScheduleType());
            break;
        case 'cameradefecttype':
            echo json_encode(cameradefecttype());
            break;
        case 'fetchSchedule':
            echo json_encode(fetchSchedule());
            break;
        case 'fetchScheduleIn':
            echo json_encode(fetchScheduleIn());
            break;
        case 'driverdefecttype':
            echo json_encode(driverdefecttype());
            break;
        case 'rampdefecttype':
            echo json_encode(rampdefecttype());
            break;
        default:
            echo json_encode(array('error' => 'Invalid action'));
            break;
    }
}

?>