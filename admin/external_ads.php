<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

// Fetch Reference Data
$districts = $pdo->query("SELECT District_name FROM district_table ORDER BY District_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$categories = $pdo->query("SELECT Description FROM job_category_table ORDER BY Description ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch Existing Ads
$ads = $pdo->query("SELECT * FROM external_ads ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promo Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Inter', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h3><i class="fas fa-bullhorn text-primary me-2"></i>Third-Party Promotions</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- CREATE FORM -->
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="fw-bold mb-3">Add New Promo</h5>
                <form action="actions/save_external_ad.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Ad Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Accountant at XYZ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destination URL</label>
                        <input type="url" name="ad_url" class="form-control" placeholder="https://other-site.com/job/123" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Source Name</label>
                            <input type="text" name="media_name" class="form-control" placeholder="e.g. TopJobs">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Media Type</label>
                            <select name="media_type" class="form-select">
                                <option value="Job Site">Job Site</option>
                                <option value="Social Media">Social Media</option>
                                <option value="Newspaper">Newspaper</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-primary fw-bold">Targeting</label>
                        <select name="target_district" class="form-select mb-2">
                            <option value="">Any District</option>
                            <?php foreach($districts as $d): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="target_category" class="form-select">
                            <option value="">Any Category</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c ?>"><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short Blurb (Email Body)</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Promotion</button>
                </form>
            </div>
        </div>

        <!-- LIST -->
        <div class="col-lg-8">
            <div class="card p-4">
                <h5 class="fw-bold mb-3">Active Campaigns</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ad Details</th>
                                <th>Source</th>
                                <th>Targeting</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ads as $ad): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($ad['title']) ?></div>
                                    <a href="<?= htmlspecialchars($ad['ad_url']) ?>" target="_blank" class="small text-muted">View Link</a>
                                </td>
                                <td>
                                    <div class="badge bg-light text-dark border"><?= htmlspecialchars($ad['media_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($ad['media_type']) ?></div>
                                </td>
                                <td>
                                    <?php if($ad['target_district']): ?>
                                        <div class="badge bg-info bg-opacity-10 text-info mb-1"><i class="fas fa-map-marker-alt"></i> <?= $ad['target_district'] ?></div><br>
                                    <?php endif; ?>
                                    <?php if($ad['target_category']): ?>
                                        <div class="badge bg-warning bg-opacity-10 text-warning"><i class="fas fa-tag"></i> <?= $ad['target_category'] ?></div>
                                    <?php endif; ?>
                                    <?php if(!$ad['target_district'] && !$ad['target_category']) echo '<span class="text-muted small">Global</span>'; ?>
                                </td>
                                <td>
                                    <button onclick="blastAd(<?= $ad['id'] ?>)" class="btn btn-sm btn-dark w-100 mb-1">
                                        <i class="fas fa-paper-plane me-1"></i> Blast
                                    </button>
                                    <div class="small text-muted text-center" id="status-<?= $ad['id'] ?>"></div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function blastAd(id) {
    if(!confirm("Are you sure? This will send emails to ALL matching users who haven't received it yet.")) return;

    const btn = event.target;
    const status = document.getElementById('status-' + id);

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    fetch('actions/send_promo_blast.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Blast';
            if(data.success) {
                status.innerHTML = '<span class="text-success">Sent: ' + data.count + '</span>';
            } else {
                status.innerHTML = '<span class="text-danger">' + data.error + '</span>';
            }
        })
        .catch(e => {
            btn.disabled = false;
            status.innerHTML = 'Error';
        });
}
</script>
</body>
</html>
