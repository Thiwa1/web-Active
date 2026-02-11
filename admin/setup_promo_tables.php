<?php
require_once '../config/config.php';
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

try {
    // 1. External Ads Table
    $sql1 = "CREATE TABLE IF NOT EXISTS `external_ads` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `ad_url` VARCHAR(500) NOT NULL,
        `media_name` VARCHAR(100) NULL,
        `media_type` VARCHAR(50) NULL,
        `target_district` VARCHAR(50) NULL,
        `target_category` VARCHAR(50) NULL,
        `description` TEXT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql1);
    echo "Table 'external_ads' created or exists.<br>";

    // 2. Logs Table (Prevent duplicates)
    $sql2 = "CREATE TABLE IF NOT EXISTS `external_ad_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `ad_id` INT(11) NOT NULL,
        `employee_id` INT(11) NOT NULL,
        `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_ad_emp` (`ad_id`, `employee_id`),
        CONSTRAINT `fk_log_ad` FOREIGN KEY (`ad_id`) REFERENCES `external_ads` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql2);
    echo "Table 'external_ad_logs' created or exists.<br>";

    echo "<h3>Setup Complete. You can now use the Promo Tools.</h3>";
    echo "<a href='external_ads.php'>Go to Promo Manager</a>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>