<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    die("Access Denied");
}

$seeker_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT employee_img FROM employee_profile_seeker WHERE id = ?");
    $stmt->execute([$seeker_id]);
    $img_data = $stmt->fetchColumn();

    if ($img_data) {
        // Clean buffer
        if (ob_get_length()) ob_clean();

        // Detect MIME type
        $mime = 'application/octet-stream';
        
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($img_data);
        } else {
            // Manual Fallback
            $header = substr($img_data, 0, 4);
            $hex = bin2hex($header);
            if (strpos($hex, 'ffd8ff') === 0) $mime = 'image/jpeg';
            elseif (strpos($hex, '89504e47') === 0) $mime = 'image/png';
            elseif (strpos($hex, '47494638') === 0) $mime = 'image/gif';
        }
        
        header("Content-Type: " . $mime);
        header("Content-Length: " . strlen($img_data));
        echo $img_data;
        exit;
    } else {
        // Redirect to default or show placeholder logic (handled by frontend usually)
        http_response_code(404);
    }

} catch (Exception $e) {
    die("Error");
}
