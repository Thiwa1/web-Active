<?php
session_start();
require_once '../../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access Denied']);
    exit();
}

$seeker_id = isset($_GET['seeker_id']) ? (int)$_GET['seeker_id'] : 0;

if ($seeker_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

try {
    // 2. Authorization (Must be in active talent pool)
    $authSql = "
        SELECT count(*)
        FROM talent_offers t
        WHERE t.seeker_link = ? AND t.is_active = 1 AND t.expiry_date >= CURDATE()
    ";
    $stmtAuth = $pdo->prepare($authSql);
    $stmtAuth->execute([$seeker_id]);

    if ($stmtAuth->fetchColumn() == 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Profile inactive']);
        exit();
    }

    // 3. Fetch Documents
    $stmt = $pdo->prepare("SELECT id, document_type FROM employee_document WHERE link_to_employee_profile = ?");
    $stmt->execute([$seeker_id]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'docs' => $docs]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'System Error']);
}
