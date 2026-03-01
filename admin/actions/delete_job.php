<?php
session_start();
require_once '../../config/config.php';

// 1. Security Check: Only allow Admins to proceed
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
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

if (isset($_POST['id'])) {
    $job_id = $_POST['id'];
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
            // 3. Dynamic Column Discovery
            $sourceStmt = $pdo->query("SHOW COLUMNS FROM advertising_table");
            $sourceCols = $sourceStmt->fetchAll(PDO::FETCH_COLUMN);

            $targetStmt = $pdo->query("SHOW COLUMNS FROM advertising_table_deleted");
            $targetCols = $targetStmt->fetchAll(PDO::FETCH_COLUMN);

            // Find common columns, exclude 'id', 'deleted_by', 'deleted_date'
            $commonCols = array_intersect($sourceCols, $targetCols);
            $excludeCols = ['id', 'deleted_by', 'deleted_date'];
            $insertCols = array_diff($commonCols, $excludeCols);

            $colsStr = implode(", ", $insertCols);

            // Build the parameterized insert query
            $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));

            $insertSql = "INSERT INTO advertising_table_deleted (
                $colsStr, deleted_by, deleted_date
            ) VALUES ($placeholders, ?, NOW())";

            // Prepare parameters dynamically
            $params = [];
            foreach ($insertCols as $col) {
                $params[] = $jobData[$col] ?? null;
            }
            $params[] = $deleted_by;

            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute($params);

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