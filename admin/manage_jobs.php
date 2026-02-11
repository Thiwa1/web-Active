<?php
session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // 1. Analytics Fetching
    $totalJobs = $pdo->query("SELECT COUNT(*) FROM advertising_table")->fetchColumn();
    $liveJobs = $pdo->query("SELECT COUNT(*) FROM advertising_table WHERE Approved = 1")->fetchColumn();
    $pendingJobs = $pdo->query("SELECT COUNT(*) FROM advertising_table WHERE Approved = 0")->fetchColumn();

    // 2. Main Query: Join employer and check if job exists in paid_advertising table
    $sql = "SELECT a.*, e.employer_name, e.employer_logo, 
            pa.paid as is_paid, 0 as is_deleted
            FROM advertising_table a 
            JOIN employer_profile e ON a.link_to_employer_profile = e.id 
            LEFT JOIN paid_advertising pa ON a.id = pa.add_link AND pa.paid = 1
            ORDER BY a.Opening_date DESC";
    
    $activeJobs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Deleted Jobs
    $deletedSql = "SELECT d.*, e.employer_name, e.employer_logo, 
                   0 as is_paid, 1 as is_deleted
                   FROM advertising_table_deleted d
                   JOIN employer_profile e ON d.link_to_employer_profile = e.id
                   ORDER BY d.Opening_date DESC";
    
    $deletedJobs = $pdo->query($deletedSql)->fetchAll(PDO::FETCH_ASSOC);

    // Merge and Sort
    $jobs = array_merge($activeJobs, $deletedJobs);
    
    // Sort by Opening_date DESC
    usort($jobs, function($a, $b) {
        return strtotime($b['Opening_date']) - strtotime($a['Opening_date']);
    });

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Inventory Management | Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --pro-blue: #4361ee; --pro-bg: #f8f9fc; }
        body { background-color: var(--pro-bg); font-family: 'Inter', sans-serif; color: #334155; }
        .summary-card { background: white; border-radius: 16px; padding: 1.25rem; border: 1px solid #e3e6f0; display: flex; align-items: center; }
        .summary-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 15px; }
        .search-container { background: white; border-radius: 12px; padding: 10px 20px; border: 1px solid #e3e6f0; }
        .search-input { border: none; outline: none; width: 100%; padding: 5px; }
        .job-table-card { background: white; border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; }
        .job-img { width: 42px; height: 42px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; }
        .badge-live { background: #dcfce7; color: #15803d; }
        .badge-pending { background: #fef9c3; color: #a16207; }
        .badge-expired { background: #fee2e2; color: #b91c1c; }
        .badge-deleted { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .badge-paid { background: #e0e7ff; color: #4361ee; font-size: 0.7rem; border: 1px solid #c7d2fe; }
        .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
    </style>
</head>
<body>

<div class="container py-5">
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="summary-card">
                <div class="summary-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-briefcase"></i></div>
                <div><div class="small text-muted fw-bold">TOTAL</div><div class="h4 fw-bold m-0"><?= $totalJobs ?></div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card">
                <div class="summary-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                <div><div class="small text-muted fw-bold">LIVE</div><div class="h4 fw-bold m-0"><?= $liveJobs ?></div></div>
            </div>
        </div>
        <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
            <a href="dashboard.php" class="btn btn-dark rounded-pill px-4"><i class="fas fa-arrow-left me-2"></i> Exit</a>
        </div>
    </div>

    <div class="search-container d-flex align-items-center mb-4">
        <i class="fas fa-search text-muted me-3"></i>
        <input type="text" id="jobSearch" class="search-input" placeholder="Search Role or Employer...">
    </div>

    <div class="job-table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="inventoryTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Job / Type</th>
                        <th>Recruiter</th>
                        <th>Location</th>
                        <th class="text-center">Status</th>
                        <th class="pe-4 text-end">Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $j): 
                        $isExpired = strtotime($j['Closing_date']) < time();
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <?php if(!empty($j['img_path'])): ?>
                                    <img src="../<?= htmlspecialchars($j['img_path']) ?>" class="job-img me-3">
                                <?php elseif($j['Img']): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($j['Img']) ?>" class="job-img me-3">
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($j['Job_role']) ?></div>
                                    <?php if($j['is_paid']): ?>
                                        <span class="badge badge-paid"><i class="fas fa-star me-1"></i>PAID AD</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><div class="small fw-bold"><?= htmlspecialchars($j['employer_name']) ?></div></td>
                        <td><div class="small"><?= htmlspecialchars($j['City']) ?></div></td>
                        <td class="text-center">
                            <?php if($j['is_deleted']): ?>
                                <span class="badge badge-deleted rounded-pill px-3">Deleted</span>
                            <?php elseif($isExpired): ?>
                                <span class="badge badge-expired rounded-pill px-3">Expired</span>
                            <?php elseif($j['Approved'] == 1): ?>
                                <span class="badge badge-live rounded-pill px-3">Live</span>
                            <?php else: ?>
                                <span class="badge badge-pending rounded-pill px-3">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <?php if(!$j['is_deleted']): ?>
                                    <?php if($j['Approved'] == 0): ?>
                                        <a href="actions/approve_job.php?id=<?= $j['id'] ?>" class="btn btn-sm btn-success fw-bold px-3 py-1 d-flex align-items-center"><i class="fas fa-check me-2"></i> Approve</a>
                                    <?php endif; ?>
                                    <button onclick="copyJobLink(<?= $j['id'] ?>)" class="btn-icon btn btn-light text-info border" title="Copy Public Link"><i class="fas fa-link"></i></button>
                                    <a href="view_job_details.php?id=<?= $j['id'] ?>" class="btn-icon btn btn-light text-primary border"><i class="fas fa-edit"></i></a>
                                    <button onclick="confirmArchive(<?= $j['id'] ?>, <?= $j['is_paid'] ? 1 : 0 ?>)" class="btn-icon btn btn-light text-danger border"><i class="fas fa-trash-can"></i></button>
                                <?php else: ?>
                                    <span class="text-muted small">Archived</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('jobSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#inventoryTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});

function confirmArchive(id, isPaid) {
    let msg = isPaid ? "CRITICAL: This is a PAID job. Are you sure you want to archive it?" : "Archive this job posting?";
    if(confirm(msg)) {
        window.location.href = 'actions/delete_job.php?id=' + id;
    }
}

function copyJobLink(id) {
    // Construct absolute URL assuming /admin/ is current dir
    const url = window.location.origin + window.location.pathname.replace('/admin/manage_jobs.php', '') + '/job_details.php?id=' + id;
    navigator.clipboard.writeText(url).then(() => {
        alert('Job Link Copied to Clipboard!\nPaste it on Facebook.');
    });
}
</script>
<?php include '../layout/ui_helpers.php'; ?>
</body>
</html>