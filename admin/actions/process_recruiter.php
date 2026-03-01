<?php
session_start();
require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_type'] === 'Admin') {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $employer_id = $_POST['employer_id'];
    $action = $_POST['action'];
    $admin_name = $_SESSION['full_name'] ?? 'System Admin';

    try {
        if ($action === 'approve') {
            // Updated column names: employer_Verified, employer_Verified_by
            $stmt = $pdo->prepare("UPDATE employer_profile SET employer_Verified = 1, employer_Verified_by = ? WHERE id = ?");
            $stmt->execute([$admin_name, $employer_id]);
            $msg = "Recruiter has been approved and can now post jobs.";
        } else {
            $stmt = $pdo->prepare("UPDATE employer_profile SET employer_Verified = 2 WHERE id = ?");
            $stmt->execute([$employer_id]);
            $msg = "Recruiter was rejected.";
        }

        header("Location: ../verify_recruiters.php?msg=" . urlencode($msg));
        exit();
    } catch (PDOException $e) {
        header("Location: ../dashboard.php?error=" . urlencode("Database Error:  A database error occurred.")); exit();
    }
}