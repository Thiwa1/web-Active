<?php
session_start();
require_once '../../config/config.php';

// 1. Security Check: Only allow Admins to proceed
if (isset($_GET['id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin') {
    $job_id = $_GET['id'];
    // Capture the ID of the admin performing the deletion for the audit log
    $deleted_by = $_SESSION['user_id'] ?? 0; 

    try {
        // Start transaction to ensure data integrity
        $pdo->beginTransaction();

        // 2. FETCH the existing data from the main table
        $fetchStmt = $pdo->prepare("SELECT * FROM advertising_table WHERE id = ?");
        $fetchStmt->execute([$job_id]);
        $jobData = $fetchStmt->fetch(PDO::FETCH_ASSOC);

        if ($jobData) {
            // 3. INSERT the data into the 'advertising_table_deleted' table
            // Based on your schema: we map columns and add 'deleted_by'
            $insertSql = "INSERT INTO advertising_table_deleted (
                link_to_employer_profile, Opening_date, Closing_date, Industry, 
                Job_category, Job_role, Img, City, job_description, District, 
                Apply_by_email, Apply_by_system, apply_WhatsApp, Apply_by_email_address, 
                apply_WhatsApp_No, Approved, Rejection_comment, rejection_reason, deleted_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                $jobData['link_to_employer_profile'],
                $jobData['Opening_date'],
                $jobData['Closing_date'],
                $jobData['Industry'],
                $jobData['Job_category'],
                $jobData['Job_role'],
                $jobData['Img'],
                $jobData['City'],
                $jobData['job_description'],
                $jobData['District'],
                $jobData['Apply_by_email'],
                $jobData['Apply_by_system'],
                $jobData['apply_WhatsApp'],
                $jobData['Apply_by_email_address'],
                $jobData['apply_WhatsApp_No'],
                $jobData['Approved'],
                $jobData['Rejection_comment'],
                $jobData['rejection_reason'],
                $deleted_by
            ]);

            // 4. DELETE from the active table after successful copy
            $deleteStmt = $pdo->prepare("DELETE FROM advertising_table WHERE id = ?");
            $deleteStmt->execute([$job_id]);

            // Commit the transaction
            $pdo->commit();

            header("Location: ../manage_jobs.php?msg=Job archived and deleted successfully.");
            exit();
        } else {
            // Job ID not found in database
            $pdo->rollBack();
            header("Location: ../manage_jobs.php?msg=Job not found.");
            exit();
        }

    } catch (PDOException $e) {
        // If anything goes wrong, cancel all changes
        $pdo->rollBack();
        die("Error archiving/deleting job: " . $e->getMessage());
    }
} else {
    // Unauthorized access or missing ID
    header("Location: ../manage_jobs.php");
    exit();
}