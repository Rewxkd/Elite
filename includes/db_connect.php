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

$conn->query("
    CREATE TABLE IF NOT EXISTS vip_claims (
        claim_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        claim_type VARCHAR(32) NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vip_claims_user_type_created (user_id, claim_type, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$conn->query("
    CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(120) NOT NULL,
        message VARCHAR(255) NOT NULL,
        action_key VARCHAR(50) DEFAULT NULL,
        amount DECIMAL(10,2) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user_read_created (user_id, is_read, created_at),
        INDEX idx_notifications_user_type_read (user_id, type, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

if (!function_exists('elite_db_column_exists')) {
    function elite_db_column_exists($conn, $table, $column) {
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $safeColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('elite_db_index_exists')) {
    function elite_db_index_exists($conn, $table, $index) {
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $safeIndex = $conn->real_escape_string($index);
        $result = $conn->query("SHOW INDEX FROM `$safeTable` WHERE Key_name = '$safeIndex'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('elite_db_try_migration')) {
    function elite_db_try_migration($conn, $sql) {
        try {
            $conn->query($sql);
        } catch (Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false) {
                throw $e;
            }
        }
    }
}

if (!elite_db_column_exists($conn, 'notifications', 'type')) {
    elite_db_try_migration($conn, "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'general' AFTER user_id");
}

if (!elite_db_column_exists($conn, 'notifications', 'action_key')) {
    elite_db_try_migration($conn, "ALTER TABLE notifications ADD COLUMN action_key VARCHAR(50) DEFAULT NULL AFTER message");
}

if (!elite_db_column_exists($conn, 'notifications', 'amount')) {
    elite_db_try_migration($conn, "ALTER TABLE notifications ADD COLUMN amount DECIMAL(10,2) DEFAULT NULL AFTER action_key");
}

if (!elite_db_index_exists($conn, 'notifications', 'idx_notifications_user_read_created')) {
    elite_db_try_migration($conn, "ALTER TABLE notifications ADD INDEX idx_notifications_user_read_created (user_id, is_read, created_at)");
}

if (!elite_db_index_exists($conn, 'notifications', 'idx_notifications_user_type_read')) {
    elite_db_try_migration($conn, "ALTER TABLE notifications ADD INDEX idx_notifications_user_type_read (user_id, type, is_read)");
}
?>
