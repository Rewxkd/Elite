<?php
$host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'elitedb';

$conn = new mysqli($host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    die('Database connection failed.');
}

$conn->set_charset('utf8mb4');
?>
