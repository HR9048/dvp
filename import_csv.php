<?php
include 'includes/connection.php';

if (isset($_POST['import'])) {
    $fileName = $_FILES['csv_file']['tmp_name'];

    echo "<h3>Debug Info:</h3>";

    if (!is_uploaded_file($fileName)) {
        echo "<p style='color:red;'>Error: File not uploaded.</p>";
    } elseif (filesize($fileName) <= 0) {
        echo "<p style='color:red;'>Error: Uploaded file is empty.</p>";
    } else {
        echo "<p style='color:green;'>File uploaded successfully. Size: " . filesize($fileName) . " bytes</p>";

        $file = fopen($fileName, 'r');

        if (!$file) {
            echo "<p style='color:red;'>Error: Unable to open file.</p>";
        } else {
            echo "<p style='color:green;'>File opened successfully.</p>";

            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Line No</th><th>Cause ID</th><th>Cause</th><th>Reason</th><th>SQL Query</th><th>Status</th></tr>";

            $lineNumber = 0;
            while (($line = fgets($file)) !== false) {
                $lineNumber++;
                $line = trim($line);

                if (empty($line)) {
                    echo "<tr><td>{$lineNumber}</td><td colspan='5' style='color:orange;'>Empty line skipped.</td></tr>";
                    continue;
                }

                // Parse CSV line properly (comma-separated)
                $column = str_getcsv($line);

                echo "<tr><td>{$lineNumber}</td><td colspan='5'><pre>" . htmlspecialchars(print_r($column, true)) . "</pre></td></tr>";

                if (count($column) < 2) {
                    echo "<tr><td>{$lineNumber}</td><td colspan='5' style='color:red;'>Error: Not enough columns in line.</td></tr>";
                    continue;
                }

                $cause_id = $db->real_escape_string($column[0]);
                $cause    = $db->real_escape_string($column[1]);
                $reason   = isset($column[2]) ? $db->real_escape_string($column[2]) : NULL;

                $sql = "INSERT INTO bd_causess (cause_id, cause, reason) VALUES ('$cause_id', '$cause', '$reason')";
                $result = $db->query($sql);

                $status = $result ? "<span style='color:green;'>Success</span>" : "<span style='color:red;'>Failed: " . $db->error . "</span>";

                echo "<tr>
                        <td>{$lineNumber}</td>
                        <td>{$cause_id}</td>
                        <td>{$cause}</td>
                        <td>{$reason}</td>
                        <td><pre>" . htmlspecialchars($sql) . "</pre></td>
                        <td>{$status}</td>
                      </tr>";
            }

            fclose($file);
            echo "</table>";
        }
    }
}

$db->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>CSV Import Debug</title>
</head>
<body>
    <h2>Import CSV File (Debug Mode)</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv, .txt" required>
        <br><br>
        <button type="submit" name="import">Import</button>
    </form>
</body>
</html>
