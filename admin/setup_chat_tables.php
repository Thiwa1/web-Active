<?php
require_once '../config/config.php';
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    echo "<div style='color:red; padding:20px; border:1px solid red; font-family:sans-serif;'><strong>Error:</strong> " . htmlspecialchars("Access Denied") . " <a href='../login.php'>Login here</a></div>"; exit();
}

try {
    // 1. Chats Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_chats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(50) NOT NULL,
        status ENUM('open', 'closed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id, user_type),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table 'support_chats' checked.<br>";

    // 2. Messages Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_id INT NOT NULL,
        sender_type ENUM('user', 'admin') NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chat (chat_id),
        FOREIGN KEY (chat_id) REFERENCES support_chats(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table 'support_messages' checked.<br>";

    // 3. Enable Setting
    $stmt = $pdo->prepare("SELECT id FROM site_settings WHERE setting_key = 'chat_enabled'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('chat_enabled', '1')")->execute();
        echo "Added 'chat_enabled' setting.<br>";
    }

    echo "<h3>Chat System Ready.</h3>";
    echo "<a href='chat_manager.php'>Go to Chat Manager</a>";

} catch (PDOException $e) {
    echo "<div style='color:red; padding:20px; border:1px solid red; font-family:sans-serif;'><strong>System Error:</strong> " . htmlspecialchars("Error: ") . " A database error occurred. <a href='dashboard.php'>Back to Dashboard</a></div>"; exit();
}
?>