<?php
// 1. Start session
session_start();

// 2. Clear output buffer to prevent corruption
if (ob_get_level()) ob_end_clean();

require_once '../../config/config.php';

// 3. Security Check
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Access Denied.");
}

$source = $_GET['type'] ?? ''; 
$app_id = (int)($_GET['id'] ?? 0);
$file_request = $_GET['file'] ?? ''; 

try {
    // Select the binary data column
    if ($source === 'reg') {
        $stmt = $pdo->prepare("SELECT seeker_cv, seeker_cover_letter FROM job_applications WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT guest_cv, guest_cover_letter FROM guest_job_applications WHERE id = ?");
    }
    
    $stmt->execute([$app_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        die("Record not found.");
    }

    // 4. Identify which column holds the file data
    $fileData = ($file_request === 'cv') 
        ? ($source === 'reg' ? $row['seeker_cv'] : $row['guest_cv']) 
        : ($source === 'reg' ? $row['seeker_cover_letter'] : $row['guest_cover_letter']);

    if (empty($fileData)) {
        echo "<script>alert('No file data found in database.'); window.history.back();</script>";
        exit;
    }

    // 5. Clean buffer again and set headers for download
    if (ob_get_level()) ob_end_clean();

    // Create a generic filename for the download
    $downloadName = ($file_request === 'cv') ? "Candidate_CV.pdf" : "Cover_Letter.pdf";

    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf'); // Most resumes are PDF
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($fileData));

    // 6. Output the binary data
    echo $fileData;
    exit;

} catch (PDOException $e) {
    die("System Error: " . $e->getMessage());
}