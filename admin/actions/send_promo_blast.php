<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/mail_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Auth Failed']);
    exit();
}

$ad_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$ad_id) { echo json_encode(['success' => false, 'error' => 'No ID']); exit(); }

try {
    // 1. Fetch Ad
    $stmt = $pdo->prepare("SELECT * FROM external_ads WHERE id = ?");
    $stmt->execute([$ad_id]);
    $ad = $stmt->fetch();
    if (!$ad) { echo json_encode(['success' => false, 'error' => 'Ad Not Found']); exit(); }

    // 2. Build Query for Targets
    // We want users who match criteria AND have NOT received this ad
    $sql = "SELECT DISTINCT u.id as user_id, u.user_email, u.full_name
            FROM user_table u
            JOIN employee_profile_seeker e ON u.id = e.link_to_user
            LEFT JOIN employee_alerted_setting s ON e.id = s.link_to_employee_profile
            WHERE u.user_type = 'Employee' AND u.user_active = 1
            AND u.id NOT IN (SELECT employee_id FROM external_ad_logs WHERE ad_id = ?)";

    $params = [$ad_id];

    // Apply Filters (OR logic usually better for broad reach, but specific matching requested)
    $conditions = [];
    if (!empty($ad['target_district'])) {
        $conditions[] = "s.district = ?";
        $params[] = $ad['target_district'];
    }
    if (!empty($ad['target_category'])) {
        $conditions[] = "s.job_category = ?";
        $params[] = $ad['target_category'];
    }

    if (!empty($conditions)) {
        $sql .= " AND (" . implode(" OR ", $conditions) . ")";
    }

    // Limit to 20 to prevent timeouts
    $sql .= " LIMIT 20";

    $targets = $pdo->prepare($sql);
    $targets->execute($params);
    $users = $targets->fetchAll();

    $count = 0;
    foreach ($users as $user) {
        $subject = "Opportunity Alert: " . $ad['title'];
        $body = "
        <h3>New Opportunity via " . htmlspecialchars($ad['media_name']) . "</h3>
        <p>Hi " . htmlspecialchars($user['full_name']) . ",</p>
        <p>We found a job listing that matches your interests:</p>
        <p><strong>" . htmlspecialchars($ad['title']) . "</strong></p>
        <p>" . nl2br(htmlspecialchars($ad['description'])) . "</p>
        <br>
        <a href='" . htmlspecialchars($ad['ad_url']) . "' style='background:#4f46e5;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>View Job</a>
        <br><br>
        <small>Source: " . htmlspecialchars($ad['media_type']) . " - " . htmlspecialchars($ad['media_name']) . "</small>
        ";

        if (sendEmail($user['user_email'], $subject, $body)) {
            // Log It
            $log = $pdo->prepare("INSERT INTO external_ad_logs (ad_id, employee_id) VALUES (?, ?)");
            $log->execute([$ad_id, $user['user_id']]);
            $count++;
        }
    }

    echo json_encode(['success' => true, 'count' => $count]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>