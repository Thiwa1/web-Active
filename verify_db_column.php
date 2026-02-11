<?php
require_once 'config/config.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM advertising_table LIKE 'job_type'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($col) {
        echo "VERIFICATION SUCCESS: Column 'job_type' exists. Type: " . $col['Type'];
    } else {
        echo "VERIFICATION FAILED: Column 'job_type' not found.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
