<?php
$host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'elitedb';

$conn = new mysqli($host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>