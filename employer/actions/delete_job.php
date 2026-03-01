<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    die("Access Denied");
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($job_id > 0) {
    try {
        // Verify Ownership
        $stmt = $pdo->prepare("
            SELECT a.id, a.link_to_employer_profile 
            FROM advertising_table a 
            JOIN employer_profile e ON a.link_to_employer_profile = e.id 
            WHERE a.id = ? AND e.link_to_user = ?
        ");
        $stmt->execute([$job_id, $user_id]);
        $job = $stmt->fetch();

        if ($job) {
            // Move to Archive (Soft Delete)
            $pdo->beginTransaction();
            
            // 1. Copy to Deleted Table
            $sqlCopy = "INSERT INTO advertising_table_deleted SELECT * FROM advertising_table WHERE id = ?";
            // Note: Schema might mismatch if deleted table has extra cols (deleted_by, deleted_date).
            // Better to select specific columns or rely on exact schema match.
            // Assuming schema provided earlier, let's try explicit insert or just INSERT SELECT * if identical.
            // Based on user schema: advertising_table_deleted has `deleted_by`, `deleted_date`.
            // So SELECT * will fail column count match.
            
            // Explicit Insert
            $cols = "link_to_employer_profile, Opening_date, Closing_date, Industry, Job_category, Job_role, Img, City, job_description, District, Apply_by_email, Apply_by_system, apply_WhatsApp, Apply_by_email_address, apply_WhatsApp_No, Approved, Rejection_comment, rejection_reason";
            
            $sqlArchive = "INSERT INTO advertising_table_deleted ($cols, deleted_by, deleted_date) 
                           SELECT $cols, ?, NOW() 
                           FROM advertising_table WHERE id = ?";
                           
            $stmtArchive = $pdo->prepare($sqlArchive);
            $stmtArchive->execute([$user_id, $job_id]);
            
            // 2. Delete from Active Table
            $stmtDel = $pdo->prepare("DELETE FROM advertising_table WHERE id = ?");
            $stmtDel->execute([$job_id]);
            
            $pdo->commit();
            header("Location: ../dashboard.php?page=manage_jobs&msg=Job Archived Successfully");
        } else {
            die("Job not found or access denied.");
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
} else {
    die("Invalid ID");
}
