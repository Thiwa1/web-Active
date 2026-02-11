<?php
/**
 * Database Schema Updater
 * Run this script to add missing columns to the database.
 */

session_start();
require_once '../config/config.php';

// Security Check: Ensure only Admin can run this
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied. Please log in as Admin.");
}

echo "<h2>Database Schema Update Tool</h2>";

try {
    // 1. Check for 'job_type' column in 'advertising_table'
    $stmt = $pdo->query("SHOW COLUMNS FROM advertising_table LIKE 'job_type'");
    if (!$stmt->fetch()) {
        echo "<p>Adding 'job_type' column to 'advertising_table'...</p>";
        $pdo->exec("ALTER TABLE advertising_table ADD COLUMN job_type VARCHAR(50) DEFAULT 'Full Time' AFTER Job_role");
        $pdo->exec("ALTER TABLE advertising_table ADD INDEX (job_type)");
        echo "<p style='color:green'>Success: Column 'job_type' added.</p>";
    } else {
        echo "<p style='color:blue'>Info: Column 'job_type' already exists in 'advertising_table'.</p>";
    }

    // 2. Check for 'job_type' column in 'advertising_table_deleted' (Archive table)
    $stmt = $pdo->query("SHOW COLUMNS FROM advertising_table_deleted LIKE 'job_type'");
    if (!$stmt->fetch()) {
        echo "<p>Adding 'job_type' column to 'advertising_table_deleted'...</p>";
        $pdo->exec("ALTER TABLE advertising_table_deleted ADD COLUMN job_type VARCHAR(50) DEFAULT 'Full Time' AFTER Job_role");
        echo "<p style='color:green'>Success: Column 'job_type' added to archive table.</p>";
    } else {
        echo "<p style='color:blue'>Info: Column 'job_type' already exists in 'advertising_table_deleted'.</p>";
    }

    echo "<h3>Database Update Complete. You can now use the new features.</h3>";
    echo "<a href='../dashboard.php'>Return to Dashboard</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error updating database: " . $e->getMessage() . "</h3>";
}
?>
