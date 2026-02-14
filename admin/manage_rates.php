<?php
session_start();
require_once '../config/config.php';

// Auth
$isPaperAdminRole = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'PaperAdmin');
$isMainAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin');
$isPromotedAdmin = (isset($_SESSION['is_paper_admin']) && $_SESSION['is_paper_admin'] === 1);

if (!$isMainAdmin && !$isPaperAdminRole && !$isPromotedAdmin) {
    header("Location: ../login.php"); exit();
}

$isPaperAdmin = ($isPaperAdminRole || $isPromotedAdmin);

// Check Rights for PaperAdmin
$canEdit = $isMainAdmin;
if ($isPaperAdmin && !$isMainAdmin) {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'paper_admin_edit_rights'");
    $stmt->execute();
    $rights = $stmt->fetchColumn();
    if ($rights == '1') $canEdit = true;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (isset($_POST['action']) && $_POST['action'] === 'update_rate') {
        $id = (int)$_POST['id'];
        $rate = (float)$_POST['rate'];
        $pdo->prepare("UPDATE newspaper_rates SET rate = ? WHERE id = ?")->execute([$rate, $id]);
        header("Location: manage_rates.php?success=Rate Updated");
        exit();
    }

    // Toggle Rights (Main Admin Only)
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_rights' && $isMainAdmin) {
        $val = (int)$_POST['allow_edit'];
        $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'paper_admin_edit_rights'")->execute([$val]);
        header("Location: manage_rates.php?success=Permissions Updated");
        exit();
    }
}

// Fetch Data
$papers = $pdo->query("SELECT * FROM newspapers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$rates = [];
foreach ($papers as $p) {
    $r = $pdo->prepare("SELECT * FROM newspaper_rates WHERE newspaper_id = ?");
    $r->execute([$p['id']]);
    $rates[$p['id']] = $r->fetchAll(PDO::FETCH_ASSOC);
}

// Get Permission Status
$rightsStatus = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'paper_admin_edit_rights'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Newspaper Rates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .nav-link { color: rgba(255,255,255,0.8); }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar (Simplified duplication for context) -->
    <div class="sidebar p-3 d-none d-md-block" style="width: 250px;">
        <h4 class="mb-4 text-center">Panel</h4>
        <ul class="nav flex-column gap-2">
            <?php if($isMainAdmin): ?>
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a></li>
            <?php endif; ?>
            <li class="nav-item"><a href="manage_paper_ads.php" class="nav-link"><i class="fas fa-newspaper me-2"></i> Paper Ads</a></li>
            <li class="nav-item"><a href="manage_rates.php" class="nav-link active"><i class="fas fa-tags me-2"></i> Ad Rates</a></li>
            <li class="nav-item"><a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>

    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Newspaper Rate Card</h2>
            <?php if($isMainAdmin): ?>
                <form method="POST" class="d-flex align-items-center bg-white p-2 rounded shadow-sm">
                    <input type="hidden" name="action" value="toggle_rights">
                    <div class="form-check form-switch mb-0 me-2">
                        <input class="form-check-input" type="checkbox" name="allow_edit" value="1" id="permSwitch" <?= $rightsStatus == '1' ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label small fw-bold" for="permSwitch">Allow Agency Editing</label>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <?php if(!$canEdit): ?>
            <div class="alert alert-warning"><i class="fas fa-lock me-2"></i> You are in Read-Only mode. Contact Main Admin to request editing rights.</div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach($papers as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-primary text-white fw-bold">
                            <?= htmlspecialchars($p['name']) ?>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php foreach($rates[$p['id']] as $r): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="small"><?= htmlspecialchars($r['description']) ?></span>

                                    <?php if($canEdit): ?>
                                        <form method="POST" class="d-flex align-items-center" style="max-width: 120px;">
                                            <input type="hidden" name="action" value="update_rate">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" name="rate" class="form-control" value="<?= $r['rate'] ?>">
                                                <button class="btn btn-outline-success" type="submit"><i class="fas fa-check"></i></button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">LKR <?= number_format($r['rate'], 2) ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>