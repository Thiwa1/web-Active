<?php
session_start();
require_once '../../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    die("Access Denied.");
}

// Allow 'id' parameter to map to 'seeker_id' for consistency with other scripts
$seeker_id = isset($_GET['seeker_id']) ? (int)$_GET['seeker_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$type = isset($_GET['type']) ? $_GET['type'] : ''; // 'cv', 'cl', 'doc'
$doc_id = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0;

if ($seeker_id <= 0) {
    die("Invalid Request ID.");
}

try {
    // 2. Authorization Check
    // We must ensure the seeker actually has an ACTIVE talent offer.
    // If they are in the talent pool (active=1, expiry >= today), they are public to employers.
    $authSql = "
        SELECT count(*) 
        FROM talent_offers t
        WHERE t.seeker_link = ? AND t.is_active = 1 AND t.expiry_date >= CURDATE()
    ";
    $stmtAuth = $pdo->prepare($authSql);
    $stmtAuth->execute([$seeker_id]);
    
    if ($stmtAuth->fetchColumn() == 0) {
        die("This profile is not currently active in the talent pool.");
    }

    // 3. Fetch Document
    $blobData = null;
    $filePath = null;
    $filename = "document.pdf";
    $mimeType = "application/pdf";

    if ($type === 'cv') {
        $stmt = $pdo->prepare("SELECT employee_cv, cv_path FROM employee_profile_seeker WHERE id = ?");
        $stmt->execute([$seeker_id]);
        $row = $stmt->fetch();
        $blobData = $row['employee_cv'] ?? null;
        $filePath = $row['cv_path'] ?? null;
        $filename = "CV_Candidate_" . $seeker_id . ".pdf";
    } 
    elseif ($type === 'cl') {
        $stmt = $pdo->prepare("SELECT employee_cover_letter, cl_path FROM employee_profile_seeker WHERE id = ?");
        $stmt->execute([$seeker_id]);
        $row = $stmt->fetch();
        $blobData = $row['employee_cover_letter'] ?? null;
        $filePath = $row['cl_path'] ?? null;
        $filename = "CoverLetter_Candidate_" . $seeker_id . ".pdf";
    }
    elseif ($type === 'doc' && $doc_id > 0) {
        $stmt = $pdo->prepare("SELECT document, doc_path, document_type FROM employee_document WHERE id = ? AND link_to_employee_profile = ?");
        $stmt->execute([$doc_id, $seeker_id]);
        $row = $stmt->fetch();
        if ($row) {
            $blobData = $row['document'];
            $filePath = $row['doc_path'];
            // Sanitize filename from document_type
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['document_type']);
            $filename = $safeName . "_" . $doc_id . ".pdf"; // Default pdf
        }
    }

    // 4. Serve File (Path preferred over Blob)
    if (!empty($filePath)) {
        // Clean path (remove leading slash)
        $cleanPath = ltrim($filePath, '/');
        
        // Try multiple standard locations
        $candidates = [
            '../../' . $cleanPath,       // Relative to employer/actions/
            '../' . $cleanPath,          // If path includes 'employer'
            $cleanPath                   // If path is absolute
        ];
        
        $fullPath = null;
        foreach($candidates as $c) {
            if (file_exists($c)) {
                $fullPath = $c;
                break;
            }
        }

        if ($fullPath) {
            // Determine MIME based on extension
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
            
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"'); // Use safe filename
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit;
        }
    }

    // Fallback to BLOB
    if (!empty($blobData)) {
        // Basic signature check
        $header = substr($blobData, 0, 4);
        $hex = bin2hex($header);
        
        if (strpos($hex, 'ffd8ff') === 0) {
            $mimeType = 'image/jpeg';
            $filename = str_replace('.pdf', '.jpg', $filename);
        } elseif (strpos($hex, '89504e47') === 0) {
            $mimeType = 'image/png';
            $filename = str_replace('.pdf', '.png', $filename);
        } elseif (strpos($hex, '25504446') === 0) {
            $mimeType = 'application/pdf';
        }

        if (ob_get_level()) ob_end_clean();
        
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($blobData));

        echo $blobData;
        exit;
    }

    die("File content empty or not found.");

} catch (Exception $e) {
    die("System Error: " . $e->getMessage());
}
