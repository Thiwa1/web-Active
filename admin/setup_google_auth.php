<?php
require_once '../config/config.php';
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

try {
    // 1. Add google_id to user_table
    try {
        $pdo->query("SELECT google_id FROM user_table LIMIT 1");
        echo "Column 'google_id' already exists.<br>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE user_table ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER user_email");
        echo "Added 'google_id' to user_table.<br>";
    }

    // 2. Add Settings Keys
    $keys = ['google_client_id', 'google_client_secret', 'google_redirect_uri'];
    foreach ($keys as $key) {
        $stmt = $pdo->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, '')")->execute([$key]);
            echo "Added setting '$key'.<br>";
        }
    }

    echo "<h3>Google Auth Setup Complete.</h3>";
    echo "<a href='settings.php#pane-sms'>Configure Keys</a>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>