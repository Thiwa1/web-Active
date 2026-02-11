<?php
session_start();
require_once '../config/config.php';

// Security: Only Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$job_id = $_GET['id'] ?? null;

if (!$job_id) {
    die("Invalid Job ID.");
}

try {
    $stmt = $pdo->prepare("SELECT j.*, e.employer_name, e.employer_logo, e.email as emp_email 
                            FROM advertising_table j 
                            JOIN employer_profile e ON j.link_to_employer_profile = e.id 
                            WHERE j.id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    if (!$job) { die("Job not found."); }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Audit | #<?= $job_id ?> - <?= htmlspecialchars($job['Job_role']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --pro-bg: #f8f9fc; --sidebar-bg: #ffffff; }
        body { background-color: var(--pro-bg); font-family: 'Inter', sans-serif; }
        
        /* Layout Elements */
        .audit-header { background: white; border-bottom: 1px solid #e3e6f0; padding: 1.5rem 0; }
        .glass-card { background: white; border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        /* Typography */
        .section-title { font-size: 0.85rem; font-weight: 800; color: #4e73df; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1.5rem; }
        .data-label { font-size: 0.75rem; color: #858796; font-weight: 600; margin-bottom: 2px; }
        .data-value { font-size: 0.95rem; color: #334155; font-weight: 500; margin-bottom: 15px; }

        /* Status & Badges */
        .audit-badge { padding: 6px 16px; border-radius: 50px; font-weight: 700; font-size: 0.75rem; }
        
        /* Preview Image */
        .ad-preview-container { border: 1px solid #e3e6f0; border-radius: 12px; overflow: hidden; background: #f1f5f9; position: relative; }
        .ad-preview-img { width: 100%; height: auto; display: block; cursor: zoom-in; }
        
        .action-bar { position: sticky; bottom: 0; background: white; border-top: 1px solid #e3e6f0; padding: 1rem 0; box-shadow: 0 -5px 15px rgba(0,0,0,0.05); z-index: 100; }
    </style>
</head>
<body>

<div class="audit-header mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="manage_jobs.php" class="btn btn-sm btn-light mb-2 text-muted px-3"><i class="fas fa-arrow-left me-2"></i>Inventory</a>
                <h2 class="fw-bold m-0">Audit: <?= htmlspecialchars($job['Job_role']) ?></h2>
            </div>
            <div class="text-end">
                <span class="audit-badge <?= $job['Approved'] == 1 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> border">
                    <i class="fas fa-circle me-1 small"></i> <?= $job['Approved'] == 1 ? 'LIVE CONTENT' : 'PENDING REVIEW' ?>
                </span>
                <p class="text-muted small mt-2 m-0">System Reference: <strong>#JOB-<?= str_pad($job_id, 6, '0', STR_PAD_LEFT) ?></strong></p>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="glass-card p-4 mb-4">
                <h6 class="section-title"><i class="fas fa-align-left me-2"></i>Job Specification</h6>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="data-label">Official Job Role</div>
                        <div class="data-value"><?= htmlspecialchars($job['Job_role']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="data-label">Category</div>
                        <div class="data-value"><span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($job['Job_category']) ?></span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="data-label">Employment Type</div>
                        <div class="data-value"><?= htmlspecialchars($job['Job_type'] ?? 'Not Specified') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="data-label">Assigned Location</div>
                        <div class="data-value"><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= htmlspecialchars($job['City']) ?>, <?= htmlspecialchars($job['District']) ?></div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="data-label">Job Description / Requirements</div>
                    <div class="p-3 bg-light rounded-3" style="font-size: 0.95rem; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($job['job_description'])) ?>
                    </div>
                </div>
            </div>

            <?php if($job['Img']): ?>
            <div class="glass-card p-4">
                <h6 class="section-title"><i class="fas fa-image me-2"></i>Marketing Asset (Ad Image)</h6>
                <div class="ad-preview-container">
                    <img src="data:image/jpeg;base64,<?= base64_encode($job['Img']) ?>" class="ad-preview-img" id="mainAdImg">
                    <div class="p-3 bg-white border-top">
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> This image is displayed on the primary job feed.</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <div class="glass-card p-4 mb-4">
                <h6 class="section-title"><i class="fas fa-building me-2"></i>Recruiter Information</h6>
                <div class="d-flex align-items-center mb-4 p-3 border rounded-3 bg-light bg-opacity-50">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-weight: bold;">
                        <?= strtoupper(substr($job['employer_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($job['employer_name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($job['emp_email']) ?></div>
                    </div>
                </div>
                
                <div class="data-label">How to Apply Link</div>
                <div class="data-value">
                    <a href="<?= htmlspecialchars($job['Link']) ?>" target="_blank" class="text-primary text-decoration-none">
                        <i class="fas fa-external-link-alt me-1"></i> Open External Link
                    </a>
                </div>
            </div>

            <div class="glass-card p-4">
                <h6 class="section-title"><i class="fas fa-clock me-2"></i>Lifecycle & Timeline</h6>
                <div class="row text-center mb-4">
                    <div class="col-6 border-end">
                        <div class="data-label">OPENING DATE</div>
                        <div class="fw-bold"><?= date('M d, Y', strtotime($job['Opening_date'])) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="data-label">CLOSING DATE</div>
                        <div class="fw-bold text-danger"><?= date('M d, Y', strtotime($job['Closing_date'])) ?></div>
                    </div>
                </div>
                
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                        <span class="text-muted">Approval Status:</span>
                        <span class="fw-bold"><?= $job['Approved'] == 1 ? 'Approved' : 'Pending' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                        <span class="text-muted">Visibility:</span>
                        <span class="fw-bold text-success">Visible to Public</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="action-bar">
    <div class="container text-end">
        <button class="btn btn-outline-danger px-4 rounded-pill me-2" onclick="confirmDelete(<?= $job_id ?>)">
            <i class="fas fa-trash me-2"></i>Remove Ad
        </button>
        <?php if($job['Approved'] == 0): ?>
            <button class="btn btn-primary px-5 rounded-pill shadow" onclick="window.location.href='actions/approve_job.php?id=<?= $job_id ?>'">
                <i class="fas fa-check-circle me-2"></i>Authorize & Publish
            </button>
        <?php else: ?>
             <button class="btn btn-secondary px-5 rounded-pill shadow" onclick="window.location.href='actions/unapprove_job.php?id=<?= $job_id ?>'">
                <i class="fas fa-pause-circle me-2"></i>Take Offline
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if(confirm('SECURITY WARNING: Are you sure you want to permanently delete this job post? This action is recorded in the admin log.')) {
        window.location.href = 'actions/delete_job.php?id=' + id;
    }
}
</script>
</body>
</html>