<?php
session_start();
require_once '../../config/config.php';

// 1. Enhanced Security Check
if (!isset($_SESSION['user_id']) || !isset($_GET['id']) || !isset($_GET['source'])) {
    header("Location: ../dashboard.php?page=view_applications&error=missing_params");
    exit();
}

$employer_user_id = $_SESSION['user_id'];
$source = $_GET['source']; 
$app_id = (int)$_GET['id'];
$new_status = $_GET['status'];

// Validate allowed statuses to prevent SQL injection or data corruption
$allowed_statuses = ['Pending', 'Shortlisted', 'Rejected'];
if (!in_array($new_status, $allowed_statuses)) {
    die("Invalid status type.");
}

try {
    /* IMPORTANT: We join with advertising_table and employer_profile 
       to ensure this application actually belongs to the logged-in employer.
    */
    
    if ($source === 'reg') {
        $query = "UPDATE job_applications a
                  JOIN advertising_table j ON a.job_ad_link = j.id
                  JOIN employer_profile e ON j.link_to_employer_profile = e.id
                  SET a.application_status = :status
                  WHERE a.id = :app_id AND e.link_to_user = :user_id";
    } else {
        $query = "UPDATE guest_job_applications g
                  JOIN advertising_table j ON g.job_ad_link = j.id
                  JOIN employer_profile e ON j.link_to_employer_profile = e.id
                  SET g.application_status = :status
                  WHERE g.id = :app_id AND e.link_to_user = :user_id";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'status'  => $new_status,
        'app_id'  => $app_id,
        'user_id' => $employer_user_id
    ]);

    // Check if any row was actually changed
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($stmt->rowCount() > 0) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'new_status' => $new_status]);
        } else {
            header("Location: ../dashboard.php?page=view_applications&updated=1&status=" . urlencode($new_status));
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            // Check if status is already set to the requested value (rowCount is 0 but no error)
            // But for simplicity, we treat it as success or warning
             echo json_encode(['success' => false, 'error' => 'No changes made or unauthorized']);
        } else {
            header("Location: ../dashboard.php?page=view_applications&error=not_authorized");
        }
    }
    exit();

} catch (PDOException $e) {
    // In production, log this error instead of die()
    error_log("Update Failed: " . $e->getMessage());

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'db_failure']);
    } else {
        header("Location: ../dashboard.php?page=view_applications&error=db_failure");
    }
    exit();
}