<?php
require_once 'config/config.php';

try {
    // 1. Add 'PaperAdmin' to user_type_table
    $sql = "INSERT IGNORE INTO user_type_table (user_type_select, type_hide) VALUES ('PaperAdmin', 0)";
    $pdo->exec($sql);
    echo "Added 'PaperAdmin' role.\n";

    // 2. Create Login Logs Table
    $sqlLogs = "CREATE TABLE IF NOT EXISTS admin_login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45) DEFAULT NULL,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sqlLogs);
    echo "Created 'admin_login_logs' table.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>