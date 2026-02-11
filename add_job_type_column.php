<?php
require_once 'config/config.php';

try {
    $pdo->exec("ALTER TABLE advertising_table ADD COLUMN job_type VARCHAR(50) DEFAULT 'Full Time'");
    $pdo->exec("ALTER TABLE advertising_table ADD INDEX (job_type)");
    echo "Column 'job_type' added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'job_type' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
