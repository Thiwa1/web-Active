<?php
session_start();

// Security Check
if (!isset($_SESSION['user_type']) || !isset($_SESSION['is_paper_admin']) || $_SESSION['is_paper_admin'] !== 1) {
    header("Location: login.php");
    exit();
}

$role = strtolower(trim($_SESSION['user_type']));
$name = $_SESSION['full_name'];

// Determine default dashboard path
$dashboardMap = [
    'employer'   => 'employer/dashboard.php',
    'employee'   => 'employee/dashboard.php',
    'candidate'  => 'employee/dashboard.php',
    'seeker'     => 'employee/dashboard.php',
    'admin'      => 'admin/dashboard.php'
];

$defaultDashboard = $dashboardMap[$role] ?? 'index.php';
$paperDashboard = 'admin/manage_paper_ads.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Dashboard | Pro Plus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card { transition: transform 0.2s; cursor: pointer; border: none; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .icon-box { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center mb-5">
            <h2 class="fw-bold text-dark">Welcome back, <?= htmlspecialchars($name) ?></h2>
            <p class="text-muted">You have access to multiple areas. Please select where you would like to go.</p>
        </div>
    </div>

    <div class="row justify-content-center g-4">
        <!-- Default Role Dashboard -->
        <div class="col-md-4">
            <a href="<?= $defaultDashboard ?>" class="text-decoration-none">
                <div class="card shadow-sm h-100 p-4 text-center">
                    <div class="card-body">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h4 class="card-title text-dark mb-3"><?= ucfirst($role) ?> Dashboard</h4>
                        <p class="text-muted small">Access your personal profile, job applications, or employer tools.</p>
                        <span class="btn btn-outline-primary w-100 rounded-pill">Enter Portal</span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Paper Admin Dashboard -->
        <div class="col-md-4">
            <a href="<?= $paperDashboard ?>" class="text-decoration-none">
                <div class="card shadow-sm h-100 p-4 text-center">
                    <div class="card-body">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <h4 class="card-title text-dark mb-3">Paper Admin</h4>
                        <p class="text-muted small">Manage newspaper advertisements, rates, and configurations.</p>
                        <span class="btn btn-outline-success w-100 rounded-pill">Manage Ads</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row justify-content-center mt-5">
        <div class="col-auto">
            <a href="logout.php" class="text-muted text-decoration-none"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>
</div>

</body>
</html>
