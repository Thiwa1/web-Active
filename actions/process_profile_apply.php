<?php
session_start();
require_once '../config/config.php';

// 1. Check if the user is a logged-in Candidate
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Candidate') {
    header("Location: ../login.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = filter_var($_POST['job_id'], FILTER_VALIDATE_INT);

if (!$job_id) {
    die("Invalid Job ID");
}

try {
    // 2. Fetch the candidate's existing Profile ID and CV Blob
    $stmt = $pdo->prepare("SELECT id, full_name, cv_blob FROM candidate_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $candidate = $stmt->fetch();

    if (!$candidate) {
        die("Profile not found. Please register as a Candidate first.");
    }

    // 3. Check if the candidate actually has a CV uploaded in their profile
    if (empty($candidate['cv_blob'])) {
        // If no CV exists in profile, redirect to profile page to upload one
        header("Location: ../profile.php?error=no_cv_on_profile");
        exit();
    }

    // 4. Insert into job_applications using the blob from the profile
    $sql = "INSERT INTO job_applications (job_link, candidate_link, apply_date, cv_snapshot, candidate_name) 
            VALUES (?, ?, CURDATE(), ?, ?)";
    $apply = $pdo->prepare($sql);
    
    // We are "Piking" it (fetching from DB) and "Sending" it (inserting into applications table)
    $apply->execute([
        $job_id, 
        $candidate['id'], 
        $candidate['cv_blob'], 
        $candidate['full_name']
    ]);

    // 5. Success redirect
    header("Location: ../job_view.php?id=$job_id&applied=success");
    exit();

} catch (PDOException $e) {
    error_log("Profile Apply Error: " . $e->getMessage());
    die("A system error occurred. Please try again later.");
}