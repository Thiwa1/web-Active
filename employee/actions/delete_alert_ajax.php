<?php
session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $profile_id = $stmt->fetchColumn();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $del_id = intval($_POST['id']);
        $del = $pdo->prepare("DELETE FROM employee_alerted_setting WHERE id = ? AND link_to_employee_profile = ?");
        $del->execute([$del_id, $profile_id]);

        echo json_encode(['status' => 'success', 'message' => 'Alert removed']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>