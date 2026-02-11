<?php
session_start();
require_once '../../config/config.php';

// Security: Check if logged in
if (!isset($_SESSION['user_id'])) {
    exit("Access Denied");
}

if (isset($_GET['id'])) {
    $doc_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Fetch document and verify ownership via a JOIN with the profile table
        $sql = "SELECT d.document, d.document_type, d.doc_path
                FROM employee_document d
                JOIN employee_profile_seeker p ON d.link_to_employee_profile = p.id
                WHERE d.id = ? AND p.link_to_user = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$doc_id, $user_id]);
        $file = $stmt->fetch();

        if ($file) {
            // Check for File Path first (New Method)
            $clean_path = ltrim($file['doc_path'] ?? '', '/');
            if (!empty($clean_path) && file_exists('../../' . $clean_path)) {
                $filePath = '../../' . $clean_path;
                $filename = basename($filePath);
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                // Set Mime Type
                $contentType = 'application/octet-stream';
                if ($ext == 'pdf') $contentType = 'application/pdf';
                if ($ext == 'jpg' || $ext == 'jpeg') $contentType = 'image/jpeg';
                if ($ext == 'png') $contentType = 'image/png';

                header("Content-Type: $contentType");
                header("Content-Disposition: attachment; filename=\"$filename\"");
                header("Content-Length: " . filesize($filePath));

                readfile($filePath);
                exit();
            }

            // Fallback to BLOB (Old Method)
            if (!empty($file['document'])) {
                $filename = str_replace(' ', '_', $file['document_type']) . ".pdf";

                header("Content-Type: application/octet-stream");
                header("Content-Disposition: attachment; filename=\"$filename\"");
                echo $file['document'];
                exit();
            }
            
            echo "Document file missing.";
        } else {
            echo "Document not found or access denied.";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}