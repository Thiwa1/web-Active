<?php
session_start();
require_once '../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    // Handle AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        die("Session Expired");
    }
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Direct Access Prevention: Redirect to Dashboard if not AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $queryParams = $_GET;
    $queryString = http_build_query($queryParams);
    $redirectUrl = 'dashboard.php?page=manage_jobs' . ($queryString ? '&' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit();
}

try {
    // 2. Get employer profile
    $stmt = $pdo->prepare("SELECT id, employer_name FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch();

    if (!$emp) {
        header("Location: profile.php?msg=complete_profile");
        exit();
    }

    $emp_id = $emp['id'];

    // 3. Fetch Rejected Payments (Action Alerts)
    $stmtReject = $pdo->prepare("SELECT id, Reject_comment, Totaled_received FROM payment_table WHERE employer_link = ? AND Approval = 2");
    $stmtReject->execute([$emp_id]);
    $rejectedPayments = $stmtReject->fetchAll();

    // 4. Fetch Vacancies with sophisticated applicant sub-queries
    $sql = "SELECT j.*, 
            (SELECT COUNT(*) FROM job_applications WHERE job_ad_link = j.id) as reg_applicants,
            (SELECT COUNT(*) FROM guest_job_applications WHERE job_ad_link = j.id) as guest_applicants,
            DATEDIFF(j.Closing_date, CURDATE()) as days_left,
            0 as is_deleted
            FROM advertising_table j 
            WHERE j.link_to_employer_profile = ? 
            ORDER BY j.id DESC";
            
    $stmtJobs = $pdo->prepare($sql);
    $stmtJobs->execute([$emp_id]);
    $activeJobs = $stmtJobs->fetchAll(PDO::FETCH_ASSOC);

    // 4.1 Fetch Deleted Vacancies
    $sqlDel = "SELECT d.*, 
            0 as reg_applicants,
            0 as guest_applicants,
            DATEDIFF(d.Closing_date, CURDATE()) as days_left,
            1 as is_deleted
            FROM advertising_table_deleted d
            WHERE d.link_to_employer_profile = ? 
            ORDER BY d.id DESC";
            
    $stmtDel = $pdo->prepare($sqlDel);
    $stmtDel->execute([$emp_id]);
    $deletedJobs = $stmtDel->fetchAll(PDO::FETCH_ASSOC);

    // Merge & Sort
    $jobs = array_merge($activeJobs, $deletedJobs);
    usort($jobs, function($a, $b) {
        return strtotime($b['Opening_date']) - strtotime($a['Opening_date']);
    });

    // 5. Stat Calculations
    $active_ads = 0;
    $total_candidates = 0;
    foreach($jobs as $job) {
        if($job['Approved'] == 1) $active_ads++;
        $total_candidates += ($job['reg_applicants'] + $job['guest_applicants']);
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    
    <div class="manage-header" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius: 24px; padding: 35px; color: white; margin-bottom: 30px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="fw-800 mb-1">Vacancy Command Center</h2>
                <p class="text-white-50 mb-4">Manage your listings, track applicants, and optimize your hiring flow.</p>
                <a href="#" onclick="loadContent('post_job'); return false;" class="btn btn-primary btn-lg rounded-pill px-4 fw-700 border-0 shadow" style="background: var(--primary);">
                    <i class="fas fa-plus-circle me-2"></i> Post New Vacancy
                </a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-card-mini" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 15px 20px;">
                            <div class="small text-white-50">Active Ads</div>
                            <div class="h3 fw-800 mb-0"><?= $active_ads ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card-mini" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 15px 20px;">
                            <div class="small text-white-50">Total Candidates</div>
                            <div class="h3 fw-800 mb-0"><?= $total_candidates ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($rejectedPayments as $rp): ?>
        <div class="alert-rejection-pro d-flex justify-content-between align-items-center mb-4" style="background: #fff; border-left: 5px solid #ef4444; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-danger">Payment Issue</span>
                    <h6 class="mb-0 fw-700 text-danger">Action Required: Ref #PAY-<?= $rp['id'] ?></h6>
                </div>
                <p class="small text-muted mb-0">Reason: <?= htmlspecialchars($rp['Reject_comment']) ?></p>
            </div>
            <a href="#" onclick="loadContent('reupload_payment?id=<?= $rp['id'] ?>'); return false;" class="btn btn-dark btn-sm rounded-pill px-4 fw-600">Fix Payment</a>
        </div>
    <?php endforeach; ?>

    <div class="job-card-table bg-white rounded-4 shadow-sm border overflow-hidden">
        <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
            <h5 class="fw-800 mb-0">My Listings</h5>
            <div class="small text-muted">Showing <?= count($jobs) ?> records</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Job Designation</th>
                        <th>Engagement</th>
                        <th>Timeline</th>
                        <th>Ad Status</th>
                        <th class="text-end pe-4">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($jobs) > 0): ?>
                        <?php foreach ($jobs as $job): 
                            $total_apps = $job['reg_applicants'] + $job['guest_applicants'];
                            $days = $job['days_left'];
                            $isLive = ($job['Approved'] == 1);
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="job-title-cell fw-bold text-dark"><?= htmlspecialchars($job['Job_role']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($job['Job_category']) ?></div>
                                </td>
                                <td>
                                    <a href="#" onclick="loadContent('view_applications?job_id=<?= $job['id'] ?>'); return false;" class="text-decoration-none">
                                        <div class="counter-box bg-light rounded px-2 py-1 d-inline-block">
                                            <span class="fw-800 text-primary fs-6"><?= $total_apps ?></span>
                                            <span class="text-muted small ms-1">Applicants</span>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <?php if($job['is_deleted']): ?>
                                        <span class="text-muted small">Archived</span>
                                    <?php elseif($days < 0): ?>
                                        <span class="text-danger small fw-700">Expired</span>
                                    <?php else: ?>
                                        <div class="fw-600 mb-0 small"><?= date('d M, Y', strtotime($job['Closing_date'])) ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;"><?= $days ?> days remaining</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($job['is_deleted']): ?>
                                        <span class="badge bg-light text-muted border"><i class="fas fa-trash fa-xs"></i> Deleted</span>
                                    <?php elseif ($job['Approved'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success"><i class="fas fa-circle fa-xs"></i> Live</span>
                                    <?php elseif ($job['Approved'] == 2): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger" title="<?= htmlspecialchars($job['Rejection_comment']) ?>"><i class="fas fa-circle-xmark fa-xs"></i> Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock fa-xs"></i> Under Review</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php if(!$job['is_deleted']): ?>
                                            <?php if($isLive): ?>
                                                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="Cannot edit live ads">
                                                    <button class="btn btn-sm btn-outline-secondary rounded-3" style="width:38px;height:38px;" disabled>
                                                        <i class="fas fa-pen-to-square"></i>
                                                    </button>
                                                </span>
                                            <?php else: ?>
                                                <a href="#" onclick="loadContent('edit_job?id=<?= $job['id'] ?>'); return false;" class="btn btn-sm btn-outline-secondary rounded-3 d-flex align-items-center justify-content-center" style="width:38px;height:38px;" title="Edit Listing">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="#" onclick="loadContent('view_applications?job_id=<?= $job['id'] ?>'); return false;" class="btn btn-sm btn-outline-primary rounded-3 d-flex align-items-center justify-content-center" style="width:38px;height:38px;" title="View Applicants">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="actions/delete_job.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-danger rounded-3 d-flex align-items-center justify-content-center" style="width:38px;height:38px;" onclick="return confirm('Archive this vacancy?');" title="Delete">
                                                <i class="fas fa-trash-can"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">Archived</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="opacity-25 mb-3"><i class="fas fa-folder-open fa-3x"></i></div>
                                <p class="text-muted fw-500">You haven't posted any jobs yet.</p>
                                <button onclick="loadContent('post_job');" class="btn btn-outline-primary btn-sm rounded-pill">Post your first job</button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Initialize Tooltips -->
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
