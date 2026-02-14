<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

$pageTitle = "Seeker Intelligence Report";

// Filters
$f_district = $_GET['district'] ?? '';
$f_category = $_GET['category'] ?? '';
$f_start = $_GET['start_date'] ?? '';
$f_end = $_GET['end_date'] ?? '';

// Build Query
// We join user_table (for date/contact), seeker profile (for name), and alert settings (for preferences)
$sql = "SELECT u.id, u.full_name, u.mobile_number, u.created_at,
               s.employee_name_with_initial,
               eas.district, eas.city, eas.job_category
        FROM user_table u
        JOIN employee_profile_seeker s ON u.id = s.link_to_user
        LEFT JOIN employee_alerted_setting eas ON s.id = eas.link_to_employee_profile
        WHERE u.user_type = 'Employee'";

$params = [];

if ($f_district) {
    $sql .= " AND eas.district = ?";
    $params[] = $f_district;
}
if ($f_category) {
    $sql .= " AND eas.job_category = ?";
    $params[] = $f_category;
}
if ($f_start) {
    $sql .= " AND DATE(u.created_at) >= ?";
    $params[] = $f_start;
}
if ($f_end) {
    $sql .= " AND DATE(u.created_at) <= ?";
    $params[] = $f_end;
}

$sql .= " ORDER BY u.created_at DESC LIMIT 100";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $seekers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Dropdown Data
    $districts = $pdo->query("SELECT District_name FROM district_table ORDER BY District_name")->fetchAll(PDO::FETCH_COLUMN);
    $categories = $pdo->query("SELECT Description FROM job_category_table ORDER BY Description")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error = "DB Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seeker Report | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background: #f8f9fa; }</style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold"><i class="fas fa-users-viewfinder me-2 text-primary"></i>Seeker Intelligence</h3>
            <p class="text-muted small">Analyze candidate preferences and distribution.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase">Target District</label>
                    <select name="district" class="form-select">
                        <option value="">All Districts</option>
                        <?php foreach($districts as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $f_district == $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase">Job Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $f_category == $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase">From Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $f_start ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase">To Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $f_end ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fas fa-filter me-2"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Candidate</th>
                            <th>Contact</th>
                            <th>Preferred Location</th>
                            <th>Target Job Type</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($seekers)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No records found matching criteria.</td></tr>
                        <?php else: ?>
                            <?php foreach($seekers as $s): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= htmlspecialchars($s['employee_name_with_initial'] ?: $s['full_name']) ?></td>
                                    <td>
                                        <i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($s['mobile_number']) ?>
                                    </td>
                                    <td>
                                        <?php if($s['district']): ?>
                                            <span class="badge bg-info text-dark"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($s['district']) ?></span>
                                            <?php if($s['city']): ?>
                                                <small class="text-muted d-block mt-1"><?= htmlspecialchars($s['city']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($s['job_category']): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($s['job_category']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Any</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?= date('Y-m-d', strtotime($s['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>