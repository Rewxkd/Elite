<?php
$host = 'localhost';
$db_user = 'root';
$db_password = '';
<<<<<<< HEAD
$db_name = 'eliteDB';
=======
$db_name = 'elitedb';
>>>>>>> 76dd0d18bd76d8a820dce3247eee8bbbe1355fa2

$conn = new mysqli($host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>