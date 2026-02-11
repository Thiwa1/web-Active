<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$job_id = $_GET['id'];

try {
    // 1. Get employer profile ID
    $stmt = $pdo->prepare("SELECT id FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch();

    if ($emp) {
        // 2. Delete the job ONLY if it belongs to this employer
        $stmt = $pdo->prepare("DELETE FROM advertising_table WHERE id = ? AND link_to_employer_profile = ?");
        $stmt->execute([$job_id, $emp['id']]);
        
        header("Location: ../employer/manage_jobs.php?msg=Vacancy deleted successfully");
    }
} catch (PDOException $e) {
    header("Location: ../employer/manage_jobs.php?error=" . urlencode($e->getMessage()));
}