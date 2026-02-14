<?php
require_once 'config/config.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM user_table LIKE 'is_paper_admin'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $sql = "ALTER TABLE user_table ADD COLUMN is_paper_admin TINYINT(1) DEFAULT 0";
        $pdo->exec($sql);
        echo "Added 'is_paper_admin' column to user_table.\n";
    } else {
        echo "'is_paper_admin' column already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>