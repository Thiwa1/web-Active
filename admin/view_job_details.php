<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

$job_id = $_GET['id'] ?? null;
if (!$job_id) { header("Location: manage_jobs.php"); exit(); }

try {
    $sql = "SELECT a.*, e.employer_name, e.employer_mobile_no, e.employer_logo, e.logo_path
            FROM advertising_table a 
            JOIN employer_profile e ON a.link_to_employer_profile = e.id 
            WHERE a.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    if (!$job) { die("Record Expired or Not Found."); }

    // Logic: Calculate Days Remaining
    $closing_date = new DateTime($job['Closing_date']);
    $today = new DateTime();
    $days_left = $today->diff($closing_date)->format("%r%a");

} catch (PDOException $e) {
    die("Engine Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inspector | <?= htmlspecialchars($job['Job_role']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --pro-accent: #6366f1; --pro-bg: #f8fafc; }
        body { background-color: var(--pro-bg); font-family: 'Inter', system-ui, sans-serif; color: #1e293b; }
        
        /* Layout Components */
        .glass-card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .sidebar-sticky { position: sticky; top: 2rem; }
        
        /* Visual Elements */
        .ad-preview-container { background: #f1f5f9; border-radius: 16px; padding: 20px; text-align: center; border: 2px dashed #cbd5e1; }
        .ad-preview-img { max-width: 100%; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        
        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .status-live { background: #10b981; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
        
        .meta-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .rich-content { line-height: 1.8; font-size: 1rem; color: #334155; }
        .rich-content ul { list-style-type: none; padding-left: 0; }
        .rich-content ul li::before { content: "→"; color: var(--pro-accent); font-weight: bold; margin-right: 10px; }
        
        .btn-action { border-radius: 12px; padding: 12px; font-weight: 600; transition: 0.3s; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="manage_jobs.php" class="text-decoration-none text-muted">Management</a></li>
                <li class="breadcrumb-item active fw-semibold">Detail Audit</li>
            </ol>
        </nav>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="copyJobLink(<?= $job['id'] ?>)">
                <i class="fab fa-facebook me-2"></i> Copy Link
            </button>
            <button class="btn btn-white border shadow-sm rounded-pill px-4" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Print Report
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card glass-card p-4 p-md-5 mb-4">
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <?php if($job['Approved'] == 1): ?>
                            <span class="badge bg-success-subtle text-success px-3 rounded-pill">
                                <span class="status-dot status-live"></span>Active Production
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning px-3 rounded-pill">Draft / Pending</span>
                        <?php endif; ?>
                        <span class="text-muted small">Job ID: #<?= $job['id'] ?></span>
                    </div>
                    <h1 class="fw-bold tracking-tight text-dark"><?= htmlspecialchars($job['Job_role']) ?></h1>
                    <p class="text-muted lead"><?= htmlspecialchars($job['Job_category']) ?> &bull; <?= htmlspecialchars($job['City']) ?></p>
                </div>

                <div class="ad-preview-container mb-5">
                    <div class="meta-label mb-3">Advertisement Creative</div>
                    <?php if(!empty($job['img_path'])): ?>
                        <img src="../<?= htmlspecialchars($job['img_path']) ?>" class="ad-preview-img">
                    <?php elseif($job['Img']): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($job['Img']) ?>" class="ad-preview-img">
                    <?php else: ?>
                        <div class="py-5 text-muted">
                            <i class="fas fa-file-image fa-3x opacity-20"></i>
                            <p class="mt-2">No visual asset provided</p>
                        </div>
                    <?php endif; ?>
                </div>

                <h5 class="fw-bold mb-3 d-flex align-items-center">
                    <i class="fas fa-align-left text-primary me-2"></i> Role Description
                </h5>
                <div class="rich-content bg-light bg-opacity-50 p-4 rounded-4">
                    <?= $job['job_description'] ?: '<em class="text-muted">No content provided.</em>' ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sidebar-sticky">
                <div class="card glass-card p-4 mb-4 text-center">
                    <div class="meta-label mb-3">Listing Owner</div>
                    <?php if(!empty($job['logo_path'])): ?>
                        <img src="../<?= htmlspecialchars($job['logo_path']) ?>" class="rounded-4 shadow-sm mb-3 mx-auto" style="width: 80px; height: 80px; object-fit: cover;">
                    <?php elseif($job['employer_logo']): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($job['employer_logo']) ?>" class="rounded-4 shadow-sm mb-3 mx-auto" style="width: 80px; height: 80px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-4 shadow-sm mb-3 mx-auto d-flex align-items-center justify-content-center bg-light text-primary fw-bold" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?= strtoupper(substr($job['employer_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($job['employer_name']) ?></h5>
                    <p class="small text-muted mb-0">Member Since: <?= date('Y') ?></p>
                </div>

                <div class="card glass-card p-4 mb-4">
                    <h6 class="fw-bold mb-4">Engagement Intelligence</h6>
                    
                    <div class="mb-3">
                        <div class="meta-label">Expirations</div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="fw-bold"><?= date('M d, Y', strtotime($job['Closing_date'])) ?></span>
                            <?php if($days_left > 0): ?>
                                <span class="badge bg-primary rounded-pill"><?= $days_left ?> Days left</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill">Expired</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="meta-label">Application Channels</div>
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <?php if($job['Apply_by_system']): ?>
                                <span class="badge border text-dark fw-normal"><i class="fas fa-globe me-1"></i> Portal</span>
                            <?php endif; ?>
                            <?php if($job['Apply_by_email']): ?>
                                <span class="badge border text-dark fw-normal"><i class="fas fa-at me-1"></i> Email</span>
                            <?php endif; ?>
                            <?php if($job['apply_WhatsApp']): ?>
                                <span class="badge border text-dark fw-normal"><i class="fab fa-whatsapp me-1"></i> WhatsApp</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card glass-card p-3 border-danger border-opacity-10 bg-danger bg-opacity-10">
                    <button onclick="confirmDelete(<?= $job['id'] ?>)" class="btn btn-danger btn-action w-100 shadow-sm">
                        <i class="fas fa-trash-alt me-2"></i> Remove Ad Permanently
                    </button>
                    <p class="text-center small text-danger mt-2 mb-0 opacity-75">This will notify the employer.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if(confirm('CRITICAL: Permanent deletion will remove all applicant data linked to this job. Continue?')) {
        window.location.href = 'actions/delete_job.php?id=' + id;
    }
}

function copyJobLink(id) {
    const url = window.location.origin + window.location.pathname.replace('/admin/view_job_details.php', '') + '/job_details.php?id=' + id;
    navigator.clipboard.writeText(url).then(() => {
        alert('Link Copied! You can now paste this on Facebook.');
    });
}
</script>
</body>
</html>