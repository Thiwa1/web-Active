<?php
session_start();
require_once '../../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE advertising_table SET Approved = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // --- ALERT LOGIC ---
        require_once '../../config/mail_helper.php';
        
        // 1. Get Job Details
        $jobStmt = $pdo->prepare("SELECT Job_category, City, District, Job_role FROM advertising_table WHERE id = ?");
        $jobStmt->execute([$id]);
        $job = $jobStmt->fetch();

        if ($job) {
             // 2. Find Seekers (Distinct to avoid spam)
             $sql = "SELECT DISTINCT u.user_email, u.full_name
                     FROM employee_alerted_setting s
                     JOIN employee_profile_seeker p ON s.link_to_employee_profile = p.id
                     JOIN user_table u ON p.link_to_user = u.id
                     WHERE s.active = 1
                     AND (s.job_category = ? OR s.city = ? OR s.district = ?)";

             $seekers = $pdo->prepare($sql);
             $seekers->execute([$job['Job_category'], $job['City'], $job['District']]);

             // Base URL Construction
             $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
             $host = $_SERVER['HTTP_HOST'];
             // Assumes script is in /admin/actions/ so root is ../../
             // But we need web path.
             // Simplest is to assume root is /
             // If local dev is /topvacancy/, this might be /job_view.php (root of server).
             // I'll try to detect path.
             $path = dirname($_SERVER['PHP_SELF']); // /admin/actions
             $root = dirname(dirname($path)); // /
             // Normalize
             $root = ($root == '/' || $root == '\\') ? '' : $root;

             $link = "$protocol://$host$root/job_view.php?id=$id";

             $sentCount = 0;
             while ($row = $seekers->fetch()) {
                 $subject = "New Job Alert: " . $job['Job_role'];
                 $body = "
                 <p>Hi " . htmlspecialchars($row['full_name']) . ",</p>
                 <p>A new job matching your preferences has been posted:</p>
                 <p><strong>" . htmlspecialchars($job['Job_role']) . "</strong> in " . htmlspecialchars($job['City']) . "</p>
                 <p><a href='$link' style='background:#2563eb;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>View Job</a></p>
                 ";

                 if(sendEmail($row['user_email'], $subject, $body)) {
                     $sentCount++;
                 }
             }
        }

        header("Location: ../manage_jobs.php?msg=Job Approved. Alerts sent to $sentCount candidates.");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../manage_jobs.php?error=Invalid ID");
}
