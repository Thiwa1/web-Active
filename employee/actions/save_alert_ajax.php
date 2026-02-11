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

    if (!$profile_id) {
        echo json_encode(['status' => 'error', 'message' => 'Profile not found']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $district = $_POST['district'] ?? '';
        $city = trim($_POST['city'] ?? '');
        $category = $_POST['job_category'] ?? '';
        $active = 1;

        if ($district || $category) {
            $sql = "INSERT INTO employee_alerted_setting (district, city, job_category, link_to_employee_profile, active) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$district, $city, $category, $profile_id, $active]);
            echo json_encode(['status' => 'success', 'message' => 'Alert created successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Please select at least a District or Category']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>