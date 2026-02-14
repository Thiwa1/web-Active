<?php
session_start();
require_once '../config/config.php';

// Auth Check - Allow both Admin and PaperAdmin
if (!isset($_SESSION['user_type']) || (!in_array($_SESSION['user_type'], ['Admin', 'PaperAdmin']))) {
    header("Location: ../login.php"); exit();
}

$isPaperAdmin = ($_SESSION['user_type'] === 'PaperAdmin');
$pageTitle = "Paper Ad Requests";
$activeTab = 'paper_ads';

// Handle Status Updates
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['ad_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE paper_ads SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: manage_paper_ads.php?msg=Status Updated");
    exit();
}

// Fetch Ads
$statusFilter = $_GET['status'] ?? 'All';
$sql = "SELECT p.*, u.full_name, u.user_email
        FROM paper_ads p
        LEFT JOIN user_table u ON p.user_id = u.id";

if ($statusFilter !== 'All') {
    $sql .= " WHERE p.status = '$statusFilter'";
}
$sql .= " ORDER BY p.created_at DESC";

$ads = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Paper Ads | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .nav-link { color: rgba(255,255,255,0.8); }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
        .status-badge { font-size: 0.8rem; padding: 5px 10px; border-radius: 20px; }
        .img-thumb { width: 50px; height: 50px; object-fit: cover; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-3 d-none d-md-block" style="width: 250px;">
        <div class="mb-4 text-center">
            <h4 class="mb-0"><?= $isPaperAdmin ? 'Paper Desk' : 'Admin Panel' ?></h4>
            <?php if($isPaperAdmin): ?>
                <small class="text-muted">Restricted Access</small>
            <?php endif; ?>
        </div>

        <ul class="nav flex-column gap-2">
            <?php if(!$isPaperAdmin): ?>
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="manage_jobs.php" class="nav-link"><i class="fas fa-briefcase me-2"></i> Jobs</a></li>
            <?php endif; ?>

            <li class="nav-item"><a href="manage_paper_ads.php" class="nav-link active"><i class="fas fa-newspaper me-2"></i> Paper Ads</a></li>
            <li class="nav-item"><a href="manage_rates.php" class="nav-link"><i class="fas fa-tags me-2"></i> Ad Rates</a></li>
            <li class="nav-item"><a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Paper Advertisement Requests</h2>
            <div class="btn-group">
                <a href="?status=All" class="btn btn-outline-secondary <?= $statusFilter == 'All' ? 'active' : '' ?>">All</a>
                <a href="?status=Pending" class="btn btn-outline-warning <?= $statusFilter == 'Pending' ? 'active' : '' ?>">Pending</a>
                <a href="?status=Approved" class="btn btn-outline-success <?= $statusFilter == 'Approved' ? 'active' : '' ?>">Approved</a>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>User</th>
                                <th>Ad Details</th>
                                <th>Price</th>
                                <th>Contact</th>
                                <th>Slip</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ads as $ad): ?>
                                <tr>
                                    <td class="ps-4">#<?= $ad['id'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($ad['full_name'] ?? 'Guest') ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($ad['user_email'] ?? '-') ?></div>
                                    </td>
                                    <td style="max-width: 300px;">
                                        <div class="small fw-bold mb-1">Dim: <?= $ad['width_cm'] ?> x <?= $ad['height_cm'] ?> cm</div>
                                        <div class="text-truncate text-muted" title="<?= htmlspecialchars($ad['ad_content']) ?>">
                                            <?= htmlspecialchars(substr($ad['ad_content'], 0, 50)) ?>...
                                        </div>
                                        <?php if($ad['image_path']): ?>
                                            <a href="../<?= htmlspecialchars($ad['image_path']) ?>" target="_blank" class="badge bg-info text-decoration-none mt-1">
                                                <i class="fas fa-image me-1"></i> View Layout
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success">LKR <?= number_format($ad['price'], 2) ?></div>
                                        <small class="text-muted">Deadline: <?= $ad['closing_date'] ?></small>
                                    </td>
                                    <td>
                                        <div><i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($ad['contact_mobile']) ?></div>
                                        <?php if($ad['contact_whatsapp']): ?>
                                            <div><i class="fab fa-whatsapp me-1 text-success"></i> <?= htmlspecialchars($ad['contact_whatsapp']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($ad['payment_slip_path']): ?>
                                            <a href="../<?= htmlspecialchars($ad['payment_slip_path']) ?>" target="_blank">
                                                <img src="../<?= htmlspecialchars($ad['payment_slip_path']) ?>" class="img-thumb border" alt="Slip">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-danger small">Missing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $badgeClass = 'bg-secondary';
                                            if ($ad['status'] == 'Approved') $badgeClass = 'bg-success';
                                            if ($ad['status'] == 'Rejected') $badgeClass = 'bg-danger';
                                            if ($ad['status'] == 'Pending') $badgeClass = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= $ad['status'] ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $ad['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- View/Edit Modal -->
                                <div class="modal fade" id="viewModal<?= $ad['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Ad Request #<?= $ad['id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-7 border-end">
                                                        <h6>Ad Content</h6>
                                                        <div class="p-3 bg-light rounded border mb-3">
                                                            <?= nl2br(htmlspecialchars($ad['ad_content'])) ?>
                                                        </div>
                                                        <?php if($ad['image_path']): ?>
                                                            <h6>Provided Layout/Image</h6>
                                                            <img src="../<?= htmlspecialchars($ad['image_path']) ?>" class="img-fluid rounded border mb-3">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <h6 class="fw-bold">Transaction Details</h6>
                                                        <p class="mb-1"><strong>Price:</strong> LKR <?= number_format($ad['price'], 2) ?></p>
                                                        <p class="mb-3"><strong>Submitted:</strong> <?= $ad['created_at'] ?></p>

                                                        <h6>Payment Proof</h6>
                                                        <?php if($ad['payment_slip_path']): ?>
                                                            <a href="../<?= htmlspecialchars($ad['payment_slip_path']) ?>" target="_blank">
                                                                <img src="../<?= htmlspecialchars($ad['payment_slip_path']) ?>" class="img-fluid rounded border mb-3">
                                                            </a>
                                                        <?php else: ?>
                                                            <p class="text-danger">No slip uploaded.</p>
                                                        <?php endif; ?>

                                                        <hr>
                                                        <form method="POST">
                                                            <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                                            <label class="form-label fw-bold">Update Status</label>
                                                            <select name="status" class="form-select mb-3">
                                                                <option value="Pending" <?= $ad['status'] == 'Pending' ? 'selected' : '' ?>>Pending Review</option>
                                                                <option value="Approved" <?= $ad['status'] == 'Approved' ? 'selected' : '' ?>>Approve & Publish</option>
                                                                <option value="Rejected" <?= $ad['status'] == 'Rejected' ? 'selected' : '' ?>>Reject Request</option>
                                                            </select>
                                                            <button type="submit" name="update_status" class="btn btn-primary w-100">Save Changes</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if(empty($ads)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">No paper ad requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
