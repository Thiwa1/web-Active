<?php
session_start();
// Adjust path to your actual config
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Strict Security & Session Validation
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Candidate') {
        header("Location: ../login.php?msg=unauthorized");
        exit();
    }

    // Use filtering to sanitize inputs
    $job_ad_link = filter_var($_POST['job_ad_link'], FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id']; // This is the user_table ID
    $cover_letter = htmlspecialchars($_POST['cover_letter'] ?? '', ENT_QUOTES, 'UTF-8');
    $status = 'Pending';

    if (!$job_ad_link) {
        die("Invalid Job ID.");
    }

    try {
        $pdo->beginTransaction();

        // 2. Fetch the Seeker's Profile ID and the stored CV (BLOB)
        // We assume your profile table is 'employee_profile_seeker' linked by 'link_to_user'
        $stmtSeeker = $pdo->prepare("SELECT id, cv_blob FROM employee_profile_seeker WHERE link_to_user = ?");
        $stmtSeeker->execute([$user_id]);
        $seeker = $stmtSeeker->fetch(PDO::FETCH_ASSOC);

        if (!$seeker) {
            throw new Exception("Seeker profile not found. Please complete your profile.");
        }

        $seeker_link = $seeker['id']; // This is the ID for the applications table
        $binary_cv = $seeker['cv_blob']; // This is the file data

        // 3. Prevent Duplicate Applications
        $check = $pdo->prepare("SELECT id FROM job_applications WHERE job_ad_link = ? AND seeker_link = ?");
        $check->execute([$job_ad_link, $seeker_link]);
        
        if ($check->fetch()) {
            $pdo->rollBack();
            header("Location: ../index.php?msg=already_applied");
            exit();
        }

        // 4. Insert Application with the BLOB data
        // Columns: seeker_cv (binary), seeker_cover_letter (text)
        $sql = "INSERT INTO job_applications (
                    job_ad_link, 
                    seeker_link, 
                    seeker_cv, 
                    seeker_cover_letter, 
                    applied_date, 
                    application_status
                ) VALUES (?, ?, ?, ?, NOW(), ?)";

        $stmt = $pdo->prepare($sql);

        // 5. Pro Tip: Use bindParam with PDO::PARAM_LOB for binary data (resumes)
        $stmt->bindParam(1, $job_ad_link);
        $stmt->bindParam(2, $seeker_link);
        $stmt->bindParam(3, $binary_cv, PDO::PARAM_LOB); 
        $stmt->bindParam(4, $cover_letter);
        $stmt->bindParam(5, $status);

        $stmt->execute();
        
        $pdo->commit();

        // 6. Success Redirect
        header("Location: ../index.php?status=success&msg=" . urlencode("Application submitted successfully!"));
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // In production, log $e->getMessage() instead of showing it to user
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit();
}