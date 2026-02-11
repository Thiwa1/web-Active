<?php
session_start();
// Fix: Use correct relative path to config
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) die("Denied");

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc_type = isset($_GET['doc_type']) ? $_GET['doc_type'] : 'cv'; // Default to CV, support 'cl'

try {
    $file_path = null;
    $file_blob = null;

    if ($type == 'reg') {
        // Fetch CV/CL for Registered User
        if ($doc_type == 'cl') {
            $sql = "SELECT p.employee_cover_letter AS cv, p.cl_path AS cv_path FROM job_applications a
                    JOIN employee_profile_seeker p ON a.seeker_link = p.id WHERE a.id = ?";
        } else {
            $sql = "SELECT p.employee_cv AS cv, p.cv_path FROM job_applications a
                    JOIN employee_profile_seeker p ON a.seeker_link = p.id WHERE a.id = ?";
        }
    } elseif ($type == 'guest') {
        // Fetch CV/CL for Guest User
        if ($doc_type == 'cl') {
            $sql = "SELECT guest_cover_letter AS cv, cl_path AS cv_path FROM guest_job_applications WHERE id = ?";
        } else {
            $sql = "SELECT guest_cv AS cv, cv_path FROM guest_job_applications WHERE id = ?";
        }
    } else {
        die("Invalid Type");
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $file = $stmt->fetch();

    if ($file) {
        $path = '';
        if (!empty($file['cv_path'])) {
             $clean_path = ltrim($file['cv_path'], '/');
             $candidates = [
                 __DIR__ . '/../' . $clean_path,
                 $clean_path
             ];

             foreach($candidates as $c) {
                 if(file_exists($c)) {
                     $path = $c;
                     break;
                 }
             }
        }

        if (!empty($path)) {
            // Serve from file
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename=CV_Applicant_".$id.".pdf");
            header("Content-Length: " . filesize($path));
            readfile($path);
        } elseif (!empty($file['cv'])) {
            // Fallback to Blob
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename=CV_Applicant_".$id.".pdf");
            echo $file['cv'];
        } else {
            echo "CV file not found.";
        }
    } else {
        echo "Applicant record not found.";
    }
} catch (Exception $e) { echo "Error."; }