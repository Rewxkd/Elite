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

$conn->query("
    CREATE TABLE IF NOT EXISTS latest_bets (
        bet_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        game_type VARCHAR(50) NOT NULL,
        game_name VARCHAR(100) NOT NULL,
        wager_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payout_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        net_result DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_latest_bets_created_at (created_at),
        INDEX idx_latest_bets_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
?>
