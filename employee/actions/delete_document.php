<?php
session_start();
require_once '../../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    if (isset($_GET['id'])) {
        // Allow access if it's a delete request but redirect after? No, delete is dangerous.
        // Just redirect.
        header("Location: ../../login.php");
        exit();
    }
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $doc_id = (int)$_GET['id'];

    try {
        // Resolve Profile
        $stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();

        if ($profile) {
            $profile_id = $profile['id'];

            // Delete Logic
            // First fetch to delete file
            $stmtFetch = $pdo->prepare("SELECT doc_path FROM employee_document WHERE id = ? AND link_to_employee_profile = ?");
            $stmtFetch->execute([$doc_id, $profile_id]);
            $doc = $stmtFetch->fetch();

            if ($doc) {
                // Delete file if exists
                if (!empty($doc['doc_path'])) {
                    $clean_path = ltrim($doc['doc_path'], '/');
                    if (file_exists('../../' . $clean_path)) {
                        unlink('../../' . $clean_path);
                    }
                }

                // Delete DB Record
                $pdo->prepare("DELETE FROM employee_document WHERE id = ? AND link_to_employee_profile = ?")
                    ->execute([$doc_id, $profile_id]);

                header("Location: ../dashboard.php?page=manage_documents&delete_success=1");
                exit();
            }
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

header("Location: ../dashboard.php?page=manage_documents&error=delete_failed");
exit();
