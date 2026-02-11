<?php
require_once '../config/config.php'; 
session_start();

// 1. Configuration & Security
$site_name = "JobQuest Pro"; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    /** * THE FIX: 
     * We join advertising_table (a) with employer_profile (ep) 
     * to fetch the actual 'employer_name' field.
     */
    $sql = "SELECT 
                ja.applied_date, 
                ja.application_status, 
                a.Job_role, 
                a.City, 
                ep.employer_name,
                a.id as job_link_id
            FROM job_applications ja
            JOIN advertising_table a ON ja.job_ad_link = a.id
            JOIN employer_profile ep ON a.link_to_employer_profile = ep.id
            JOIN employee_profile_seeker eps ON ja.seeker_link = eps.id
            WHERE eps.link_to_user = ?
            ORDER BY ja.applied_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// 2. Helper function for Status Styling
function getStatusBadge($status) {
    $status = strtolower($status ?? 'pending');
    return match($status) {
        'pending'   => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">Pending</span>',
        'reviewed'  => '<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">Reviewed</span>',
        'interview' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Interviewing</span>',
        'rejected'  => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Rejected</span>',
        default     => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3">'.ucfirst($status).'</span>',
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications | <?= $site_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --app-bg: #f8fafc; --accent: #2563eb; }
        body { background-color: var(--app-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-stack { border: none; border-radius: 20px; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden; }
        .app-item { padding: 20px; border-bottom: 1px solid #f1f5f9; transition: 0.2s; display: flex; align-items: center; text-decoration: none; color: inherit; }
        .app-item:hover { background-color: #fbfcfe; }
        .app-item:last-child { border-bottom: none; }
        .company-logo { width: 55px; height: 55px; background: #eef2ff; color: var(--accent); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; text-transform: uppercase; }
        .date-badge { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
        .job-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .city-text { font-size: 0.85rem; color: #64748b; }
        .empty-state { padding: 80px 20px; text-align: center; }
        .empty-icon { font-size: 4rem; color: #e2e8f0; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row mb-5 align-items-center">
        <div class="col-md-7">
            <h3 class="fw-bold text-dark mb-1">My Applications</h3>
            <p class="text-muted mb-0">Track the status of your sent job requests.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <a href="dashboard.php" class="btn btn-white bg-white border rounded-pill px-4 shadow-sm me-2">Dashboard</a>
            <a href="../index.php" class="btn btn-primary rounded-pill px-4 shadow-sm">Find Jobs</a>
        </div>
    </div>

    <div class="card card-stack">
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                <h5 class="fw-bold">No applications found</h5>
                <p class="text-muted">You haven't applied for any positions yet.</p>
                <a href="../index.php" class="btn btn-outline-primary rounded-pill px-4 mt-2">Start Browsing</a>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($applications as $app): 
                    $employer = !empty($app['employer_name']) ? $app['employer_name'] : 'Employer';
                ?>
                    <div class="app-item">
                        <div class="company-logo me-3 shadow-sm">
                            <?= substr($employer, 0, 1) ?>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-md-flex justify-content-between align-items-start">
                                <div class="mb-2 mb-md-0">
                                    <h6 class="job-title mb-0"><?= htmlspecialchars($app['Job_role']) ?></h6>
                                    <div class="city-text">
                                        <span class="text-dark fw-medium"><?= htmlspecialchars($employer) ?></span> 
                                        <span class="mx-2 text-silver">•</span> 
                                        <i class="fas fa-location-dot me-1 opacity-50"></i> <?= htmlspecialchars($app['City']) ?>
                                    </div>
                                </div>
                                <div class="text-md-end">
                                    <div class="mb-2"><?= getStatusBadge($app['application_status']) ?></div>
                                    <div class="date-badge">Applied: <?= date('d M Y', strtotime($app['applied_date'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="ms-4 d-none d-md-block">
                            <a href="../job_view.php?id=<?= $app['job_link_id'] ?>" class="btn btn-light btn-sm rounded-circle shadow-sm" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-arrow-right text-muted small"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>