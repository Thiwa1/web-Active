<?php
session_start();
// Adjusted path to match your folder structure (actions/process_guest_apply.php)
require_once '../config/config.php';
require_once '../config/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Sanitize and Match POST keys from the Modal
    // Note: We use the names from the Modal in job_details.php
    $job_id  = filter_var($_POST['job_ad_link'] ?? null, FILTER_VALIDATE_INT);
    $name    = htmlspecialchars(trim($_POST['guest_full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $contact = htmlspecialchars(trim($_POST['guest_contact_no'] ?? ''), ENT_QUOTES, 'UTF-8');
    $gender  = htmlspecialchars(trim($_POST['guest_gender'] ?? ''), ENT_QUOTES, 'UTF-8');
    $cover_letter_text = htmlspecialchars(trim($_POST['guest_cover_letter'] ?? ''), ENT_QUOTES, 'UTF-8');

    if (!$job_id || empty($name) || empty($contact)) {
        die("Invalid form submission. Please fill in all required fields.");
    }

    try {
        // 2. File Upload (New Logic)
        $cv_path = null;
        if (!empty($_FILES['guest_cv']['tmp_name'])) {
            try {
                $raw_cv = uploadImage($_FILES['guest_cv'], '../uploads/guests/', ['pdf','doc','docx']);
                $cv_path = str_replace(['../', '../../'], '', $raw_cv);
            } catch (Exception $e) { die("CV Error: " . $e->getMessage()); }
        } else {
            die("Error: CV is required.");
        }

        $cl_path = null;
        if (!empty($_FILES['guest_cover_letter']['tmp_name'])) {
            try {
                $raw_cl = uploadImage($_FILES['guest_cover_letter'], '../uploads/guests/', ['pdf','doc','docx']);
                $cl_path = str_replace(['../', '../../'], '', $raw_cl);
            } catch (Exception $e) { /* Optional */ }
        }

        // 4. Database Transaction
        $pdo->beginTransaction();

        // Matching your schema columns exactly (updated for file paths): 
        // Added 'guest_cv' with empty string to satisfy NOT NULL constraint on BLOB column
        $sql = "INSERT INTO guest_job_applications 
                (job_ad_link, guest_full_name, guest_contact_no, guest_gender, cv_path, cl_path, guest_cover_letter, guest_cv, applied_date, application_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, '', NOW(), 'Pending')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$job_id, $name, $contact, $gender, $cv_path, $cl_path, $cover_letter_text]);
        $pdo->commit();

        // 5. Success Redirect
        header("Location: ../job_details.php?id=$job_id&applied=success");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Guest Application Error: " . $e->getMessage());
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit();
}