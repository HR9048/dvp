<?php
/*$servername = "192.168.1.16"; 
$username = "dvp_data_user"; 
$password = "dvpdatabaseaccess"; 
$database = "kkrtcdvp_database"; 
$port = 33306; // MySQL custom port*/
$servername = "localhost"; 
$username = "root"; 
$password = "kkrtcsystem";
$port = 33306; // MySQL custom port
$database = "kkrtcdvp_data"; 
// Create connection using the custom MySQL port
$db = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
} 
?>
