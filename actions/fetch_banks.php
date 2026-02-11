<?php
require_once '../config/config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT bank_name, account_number, branch_name, branch_code FROM system_bank_accounts ORDER BY id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
