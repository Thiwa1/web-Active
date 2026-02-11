<?php
session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    echo json_encode(['error' => 'Access Denied']); exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get Employer Profile ID
    $stmt = $pdo->prepare("SELECT id FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $emp_id = $stmt->fetchColumn();

    if (!$emp_id) { echo json_encode(['error' => 'Profile not found']); exit(); }

    // Fetch Last 30 Days Data
    $dates = [];
    for ($i = 29; $i >= 0; $i--) {
        $dates[] = date('Y-m-d', strtotime("-$i days"));
    }

    // 1. Views Over Time
    $viewSql = "
        SELECT v.viewed_at, COUNT(*) as count 
        FROM job_views_log v
        JOIN advertising_table a ON v.job_id = a.id
        WHERE a.link_to_employer_profile = ? 
        AND v.viewed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY v.viewed_at
    ";
    $stmt = $pdo->prepare($viewSql);
    $stmt->execute([$emp_id]);
    $viewsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [date => count]

    // 2. Applications Over Time
    $appSql = "
        SELECT DATE(ja.applied_date) as app_date, COUNT(*) as count 
        FROM job_applications ja
        JOIN advertising_table a ON ja.job_ad_link = a.id
        WHERE a.link_to_employer_profile = ?
        AND ja.applied_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(ja.applied_date)
    ";
    $stmt = $pdo->prepare($appSql);
    $stmt->execute([$emp_id]);
    $appsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Guest Apps
    $guestSql = "
        SELECT DATE(ga.applied_date) as app_date, COUNT(*) as count 
        FROM guest_job_applications ga
        JOIN advertising_table a ON ga.job_ad_link = a.id
        WHERE a.link_to_employer_profile = ?
        AND ga.applied_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(ga.applied_date)
    ";
    $stmt = $pdo->prepare($guestSql);
    $stmt->execute([$emp_id]);
    $guestRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Merge Data
    $viewData = [];
    $appData = [];

    foreach ($dates as $d) {
        $viewData[] = isset($viewsRaw[$d]) ? (int)$viewsRaw[$d] : 0;
        $totalApps = (isset($appsRaw[$d]) ? (int)$appsRaw[$d] : 0) + (isset($guestRaw[$d]) ? (int)$guestRaw[$d] : 0);
        $appData[] = $totalApps;
    }

    echo json_encode([
        'labels' => $dates,
        'views' => $viewData,
        'applications' => $appData
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
