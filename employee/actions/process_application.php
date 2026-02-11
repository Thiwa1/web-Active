<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = (int)$_POST['job_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Get Seeker Profile ID
        $stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
        $stmt->execute([$user_id]);
        $seeker = $stmt->fetch();

        if (!$seeker) die("Profile not found. Please complete your profile.");

        // Check if already applied
        $check = $pdo->prepare("SELECT id FROM job_applications WHERE job_ad_link = ? AND seeker_link = ?");
        $check->execute([$job_id, $seeker['id']]);
        
        if ($check->fetch()) {
            die("You have already applied for this job.");
        }

        // Apply
        $sql = "INSERT INTO job_applications (job_ad_link, seeker_link, applied_date, application_status) VALUES (?, ?, NOW(), 'Pending')";
        $pdo->prepare($sql)->execute([$job_id, $seeker['id']]);

        header("Location: ../browse_jobs.php?msg=Application Submitted");

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
