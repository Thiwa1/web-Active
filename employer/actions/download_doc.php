<?php
session_start();
require_once '../../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    die("Access Denied. User type: " . ($_SESSION['user_type'] ?? 'None'));
}

$employer_user_id = $_SESSION['user_id'];
$doc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($doc_id <= 0) {
    die("Invalid Document ID.");
}

try {
    // 2. Resolve Employer Profile ID
    $stmt = $pdo->prepare("SELECT id FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$employer_user_id]);
    $emp = $stmt->fetch();
    if (!$emp) die("Employer profile error.");
    $emp_id = $emp['id'];

    // 3. Authorization Check
    // Ensure the document belongs to a seeker who has applied to THIS employer's job
    // This prevents employers from downloading random documents by guessing IDs
    $authSql = "
        SELECT ed.document, ed.document_type, ed.doc_path
        FROM employee_document ed
        JOIN job_applications ja ON ed.link_to_employee_profile = ja.seeker_link
        JOIN advertising_table at ON ja.job_ad_link = at.id
        WHERE ed.id = ? AND at.link_to_employer_profile = ?
        LIMIT 1";

    $stmtAuth = $pdo->prepare($authSql);
    $stmtAuth->execute([$doc_id, $emp_id]);
    $doc = $stmtAuth->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        die("You do not have permission to view this document.");
    }

    if (ob_get_level()) ob_end_clean();

    // 4. Serve File - Check Path First
    $clean_path = ltrim($doc['doc_path'], '/');
    if (!empty($clean_path) && file_exists('../../' . $clean_path)) {
        $filePath = '../../' . $clean_path;
        $filename = basename($filePath);
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $contentType = 'application/octet-stream';
        if ($ext == 'pdf') $contentType = 'application/pdf';
        if ($ext == 'jpg' || $ext == 'jpeg') $contentType = 'image/jpeg';
        if ($ext == 'png') $contentType = 'image/png';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }

    // 5. Serve File - Fallback to BLOB
    if (empty($doc['document'])) {
        die("File content empty.");
    }

    // Determine extension/mime based on type or generic
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $doc['document_type']) . "_" . $doc_id . ".pdf"; // Defaulting to PDF
    
    // Basic signature check for images
    $header = substr($doc['document'], 0, 4);
    $hex = bin2hex($header);
    $contentType = 'application/pdf'; // Default
    
    if (strpos($hex, 'ffd8ff') === 0) {
        $contentType = 'image/jpeg';
        $filename = str_replace('.pdf', '.jpg', $filename);
    } elseif (strpos($hex, '89504e47') === 0) {
        $contentType = 'image/png';
        $filename = str_replace('.pdf', '.png', $filename);
    }
    
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($doc['document']));

    echo $doc['document'];
    exit;

} catch (Exception $e) {
    die("System Error.");
}
