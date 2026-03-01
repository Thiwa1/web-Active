<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    die("CSRF Token Validation Failed");
}

$job_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
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
            
            // 1. Dynamic Column Discovery
            $sourceStmt = $pdo->query("SHOW COLUMNS FROM advertising_table");
            $sourceCols = $sourceStmt->fetchAll(PDO::FETCH_COLUMN);

            $targetStmt = $pdo->query("SHOW COLUMNS FROM advertising_table_deleted");
            $targetCols = $targetStmt->fetchAll(PDO::FETCH_COLUMN);

            // Find common columns, exclude 'id', 'deleted_by', 'deleted_date'
            $commonCols = array_intersect($sourceCols, $targetCols);
            $excludeCols = ['id', 'deleted_by', 'deleted_date'];
            $insertCols = array_diff($commonCols, $excludeCols);

            $colsStr = implode(", ", $insertCols);

            // Generate explicit insert query based on common columns
            $sqlArchive = "INSERT INTO advertising_table_deleted ($colsStr, deleted_by, deleted_date)
                           SELECT $colsStr, ?, NOW()
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
