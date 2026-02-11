<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    echo json_encode(['success' => false, 'message' => 'Please login as an employee.']);
    exit();
}

$user_id = $_SESSION['user_id'];

$job_id = 0;
if (isset($_POST['job_id'])) {
    $job_id = (int)$_POST['job_id'];
} else {
    $data = json_decode(file_get_contents('php://input'), true);
    $job_id = isset($data['job_id']) ? (int)$data['job_id'] : 0;
}

if ($job_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Job ID.']);
    exit();
}

try {
    // 1. Get Seeker ID
    $stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $seeker = $stmt->fetch();

    if (!$seeker) {
        echo json_encode(['success' => false, 'message' => 'Profile incomplete.']);
        exit();
    }

    // 2. Check Duplicate
    $check = $pdo->prepare("SELECT id FROM job_applications WHERE job_ad_link = ? AND seeker_link = ?");
    $check->execute([$job_id, $seeker['id']]);
    
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already applied.']);
        exit();
    }

    // 3. Insert Application
    $sql = "INSERT INTO job_applications (job_ad_link, seeker_link, applied_date, application_status) VALUES (?, ?, NOW(), 'Pending')";
    $pdo->prepare($sql)->execute([$job_id, $seeker['id']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System Error.']);
}
