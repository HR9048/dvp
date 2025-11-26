<?php
include 'includes/connection.php';
// SQL query to fetch division-wise counts (no overall total in query)
$query = "
    SELECT 
        l.division, 
        COUNT(am.id) AS record_count,
        l.division_id
    FROM alternator_master am
    JOIN (SELECT DISTINCT division_id, division FROM location) l 
        ON am.division_id = l.division_id
    GROUP BY l.division, l.division_id
    ORDER BY l.division_id;
";

// Execute the query
$result = $db->query($query);

// Initialize a variable to store the overall total
$totalRecords = 0;

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Start the table to display the results
    echo "<h2> Alternator Master</h2><table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Division Name</th>
                <th>Record Count</th>
            </tr>";
    
    // Loop through the result set and display division-wise counts
    while ($row = $result->fetch_assoc()) {
        // Add the record count to the total
        $totalRecords += $row['record_count'];
        
        // Display the division data
        echo "<tr>
                <td>" . htmlspecialchars($row['division']) . "</td>
                <td>" . $row['record_count'] . "</td>
              </tr>";
    }
    // Display the overall total at the end
    echo "<tr>
            <td><strong>Overall Total</strong></td>
            <td><strong>" . $totalRecords . "</strong></td>
          </tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
$query = "
    SELECT 
        l.division, 
        COUNT(am.id) AS record_count,
        l.division_id
    FROM engine_master am
    JOIN (SELECT DISTINCT division_id, division FROM location) l 
        ON am.division_id = l.division_id
    GROUP BY l.division, l.division_id
    ORDER BY l.division_id;
";

// Execute the query
$result = $db->query($query);

// Initialize a variable to store the overall total
$totalRecords = 0;

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Start the table to display the results
    echo "<h2> Engine Master</h2><table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Division Name</th>
                <th>Record Count</th>
            </tr>";
    
    // Loop through the result set and display division-wise counts
    while ($row = $result->fetch_assoc()) {
        // Add the record count to the total
        $totalRecords += $row['record_count'];
        
        // Display the division data
        echo "<tr>
                <td>" . htmlspecialchars($row['division']) . "</td>
                <td>" . $row['record_count'] . "</td>
              </tr>";
    }
    // Display the overall total at the end
    echo "<tr>
            <td><strong>Overall Total</strong></td>
            <td><strong>" . $totalRecords . "</strong></td>
          </tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
$query = "
    SELECT 
        l.division, 
        COUNT(am.id) AS record_count,
        l.division_id
    FROM gearbox_master am
    JOIN (SELECT DISTINCT division_id, division FROM location) l 
        ON am.division_id = l.division_id
    GROUP BY l.division, l.division_id
    ORDER BY l.division_id;
";

// Execute the query
$result = $db->query($query);

// Initialize a variable to store the overall total
$totalRecords = 0;

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Start the table to display the results
    echo "<h2> Gear Box Master</h2><table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Division Name</th>
                <th>Record Count</th>
            </tr>";
    
    // Loop through the result set and display division-wise counts
    while ($row = $result->fetch_assoc()) {
        // Add the record count to the total
        $totalRecords += $row['record_count'];
        
        // Display the division data
        echo "<tr>
                <td>" . htmlspecialchars($row['division']) . "</td>
                <td>" . $row['record_count'] . "</td>
              </tr>";
    }
    // Display the overall total at the end
    echo "<tr>
            <td><strong>Overall Total</strong></td>
            <td><strong>" . $totalRecords . "</strong></td>
          </tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
$query = "
    SELECT 
        l.division, 
        COUNT(am.id) AS record_count,
        l.division_id
    FROM fip_hpp_master am
    JOIN (SELECT DISTINCT division_id, division FROM location) l 
        ON am.division_id = l.division_id
    GROUP BY l.division, l.division_id
    ORDER BY l.division_id;
";

// Execute the query
$result = $db->query($query);

// Initialize a variable to store the overall total
$totalRecords = 0;

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Start the table to display the results
    echo "<h2> FIP/FPP Master</h2><table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Division Name</th>
                <th>Record Count</th>
            </tr>";
    
    // Loop through the result set and display division-wise counts
    while ($row = $result->fetch_assoc()) {
        // Add the record count to the total
        $totalRecords += $row['record_count'];
        
        // Display the division data
        echo "<tr>
                <td>" . htmlspecialchars($row['division']) . "</td>
                <td>" . $row['record_count'] . "</td>
              </tr>";
    }
    // Display the overall total at the end
    echo "<tr>
            <td><strong>Overall Total</strong></td>
            <td><strong>" . $totalRecords . "</strong></td>
          </tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
$query = "
    SELECT 
        l.division, 
        COUNT(am.id) AS record_count,
        l.division_id
    FROM starter_master am
    JOIN (SELECT DISTINCT division_id, division FROM location) l 
        ON am.division_id = l.division_id
    GROUP BY l.division, l.division_id
    ORDER BY l.division_id;
";

// Execute the query
$result = $db->query($query);

// Initialize a variable to store the overall total
$totalRecords = 0;

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Start the table to display the results
    echo "<h2> Starter Master</h2><table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Division Name</th>
                <th>Record Count</th>
            </tr>";
    
    // Loop through the result set and display division-wise counts
    while ($row = $result->fetch_assoc()) {
        // Add the record count to the total
        $totalRecords += $row['record_count'];
        
        // Display the division data
        echo "<tr>
                <td>" . htmlspecialchars($row['division']) . "</td>
                <td>" . $row['record_count'] . "</td>
              </tr>";
    }
    // Display the overall total at the end
    echo "<tr>
            <td><strong>Overall Total</strong></td>
            <td><strong>" . $totalRecords . "</strong></td>
          </tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
$query = "
    SELECT 
        l.division, 
        COUNT(am.id) AS record_count,
        l.division_id
    FROM rear_axle_master am
    JOIN (SELECT DISTINCT division_id, division FROM location) l 
        ON am.division_id = l.division_id
    GROUP BY l.division, l.division_id
    ORDER BY l.division_id;
";

// Execute the query
$result = $db->query($query);

// Initialize a variable to store the overall total
$totalRecords = 0;

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Start the table to display the results
    echo "<h2> Rear axle Master</h2><table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Division Name</th>
                <th>Record Count</th>
            </tr>";
    
    // Loop through the result set and display division-wise counts
    while ($row = $result->fetch_assoc()) {
        // Add the record count to the total
        $totalRecords += $row['record_count'];
        
        // Display the division data
        echo "<tr>
                <td>" . htmlspecialchars($row['division']) . "</td>
                <td>" . $row['record_count'] . "</td>
              </tr>";
    }
    // Display the overall total at the end
    echo "<tr>
            <td><strong>Overall Total</strong></td>
            <td><strong>" . $totalRecords . "</strong></td>
          </tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
$query = "
    SELECT 
        l.division, 
        COUNT(am.id) AS record_count,
        l.division_id
    FROM battery_master am
    JOIN (SELECT DISTINCT division_id, division FROM location) l 
        ON am.division_id = l.division_id
    GROUP BY l.division, l.division_id
    ORDER BY l.division_id;
";

// Execute the query
$result = $db->query($query);

// Initialize a variable to store the overall total
$totalRecords = 0;

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Start the table to display the results
    echo "<h2> Battery Master</h2><table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Division Name</th>
                <th>Record Count</th>
            </tr>";
    
    // Loop through the result set and display division-wise counts
    while ($row = $result->fetch_assoc()) {
        // Add the record count to the total
        $totalRecords += $row['record_count'];
        
        // Display the division data
        echo "<tr>
                <td>" . htmlspecialchars($row['division']) . "</td>
                <td>" . $row['record_count'] . "</td>
              </tr>";
    }
    // Display the overall total at the end
    echo "<tr>
            <td><strong>Overall Total</strong></td>
            <td><strong>" . $totalRecords . "</strong></td>
          </tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
$query = "
    SELECT 
        l.division, 
        COUNT(am.id) AS record_count,
        l.division_id
    FROM tyre_master am
    JOIN (SELECT DISTINCT division_id, division FROM location) l 
        ON am.division_id = l.division_id
    GROUP BY l.division, l.division_id
    ORDER BY l.division_id;
";

// Execute the query
$result = $db->query($query);

// Initialize a variable to store the overall total
$totalRecords = 0;

// Check if query executed successfully
if ($result->num_rows > 0) {
    // Start the table to display the results
    echo "<h2> Tyre Master</h2><table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Division Name</th>
                <th>Record Count</th>
            </tr>";
    
    // Loop through the result set and display division-wise counts
    while ($row = $result->fetch_assoc()) {
        // Add the record count to the total
        $totalRecords += $row['record_count'];
        
        // Display the division data
        echo "<tr>
                <td>" . htmlspecialchars($row['division']) . "</td>
                <td>" . $row['record_count'] . "</td>
              </tr>";
    }
    // Display the overall total at the end
    echo "<tr>
            <td><strong>Overall Total</strong></td>
            <td><strong>" . $totalRecords . "</strong></td>
          </tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
// Close the database dbection
$db->close();
?>