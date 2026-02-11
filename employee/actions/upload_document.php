<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/upload_helper.php'; // Reuse helper if available, or write inline

// 1. Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['doc_file'])) {
    $doc_type = $_POST['document_type'];
    $file = $_FILES['doc_file'];

    // 2. Resolve Profile Identity
    try {
        $stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();

        if (!$profile) {
            die("Profile not found.");
        }
        $profile_id = $profile['id'];

        // 3. File Upload Logic
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            header("Location: ../dashboard.php?page=manage_documents&error=invalid_type");
            exit();
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            header("Location: ../dashboard.php?page=manage_documents&error=file_too_large");
            exit();
        }

        // Generate Unique Name
        $new_filename = 'doc_' . $profile_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        // Ensure directory exists
        $target_dir = '../../uploads/docs/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $target_path = $target_dir . $new_filename;
        // DB Path (relative to root or relative to script? Storing relative to root usually best)
        // System convention seems to be 'uploads/docs/...' based on migrate script
        $db_path = 'uploads/docs/' . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // 4. Insert into DB (Path based)
            $sql = "INSERT INTO employee_document (link_to_employee_profile, document_type, doc_path, document) VALUES (?, ?, ?, NULL)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$profile_id, $doc_type, $db_path]);

            header("Location: ../dashboard.php?page=manage_documents&upload_success=1");
            exit();
        } else {
            header("Location: ../dashboard.php?page=manage_documents&error=upload_failed");
            exit();
        }

    } catch (PDOException $e) {
        error_log($e->getMessage());
        header("Location: ../dashboard.php?page=manage_documents&error=db_error");
        exit();
    }
} else {
    header("Location: ../dashboard.php?page=manage_documents");
    exit();
}
