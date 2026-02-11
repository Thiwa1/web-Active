<?php
// This is a fragment, so we don't need <html> or <head> tags.
// Just the inner content.
session_start();
require_once '../config/config.php';

$user_id = $_SESSION['user_id'];

// Fetch the same stats used in the main dashboard
$stmt = $pdo->prepare("
    SELECT ep.id, ep.employer_name,
    (SELECT COUNT(*) FROM advertising_table WHERE link_to_employer_profile = ep.id) as total_ads,
    (SELECT COUNT(*) FROM job_applications ja JOIN advertising_table ad ON ja.job_ad_link = ad.id WHERE ad.link_to_employer_profile = ep.id) as reg_apps,
    (SELECT COUNT(*) FROM guest_job_applications ga JOIN advertising_table ad ON ga.job_ad_link = ad.id WHERE ad.link_to_employer_profile = ep.id) as guest_apps
    FROM employer_profile ep WHERE ep.link_to_user = ?
");
$stmt->execute([$user_id]);
$data = $stmt->fetch();
?>

<div class="welcome-card shadow-lg">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="fw-800">Welcome back, <?= explode(' ', $data['employer_name'])[0] ?>!</h1>
            <p class="text-white-50 fs-5">You have <?= $data['reg_apps'] + $data['guest_apps'] ?> candidates waiting for review.</p>
            <button class="btn btn-primary rounded-pill px-4 py-2 mt-3 fw-700" onclick="loadContent('post_job')">
                + Post a New Vacancy
            </button>
        </div>
    </div>
    <i class="fas fa-briefcase fa-10x"></i>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="bento-stat">
            <div class="text-muted small fw-800 uppercase tracking-wider mb-2">Active Roles</div>
            <h2 class="fw-800"><?= $data['total_ads'] ?></h2>
            <div class="text-success small fw-700 mt-2"><i class="fas fa-arrow-up me-1"></i> Live on Site</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bento-stat">
            <div class="text-muted small fw-800 uppercase tracking-wider mb-2">Total Outreach</div>
            <h2 class="fw-800"><?= $data['reg_apps'] + $data['guest_apps'] ?></h2>
            <div class="text-primary small fw-700 mt-2"><i class="fas fa-users me-1"></i> Total Candidates</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bento-stat">
            <div class="text-muted small fw-800 uppercase tracking-wider mb-2">Avg. Conversion</div>
            <h2 class="fw-800">18.4%</h2>
            <div class="text-muted small fw-700 mt-2">Industry Standard: 12%</div>
        </div>
    </div>
</div>