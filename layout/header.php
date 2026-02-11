<?php
// Ensure config is loaded
require_once __DIR__ . '/../config/config.php';

// Branding Logic
$siteName = "JobPortal";
$logoSrc = 'https://via.placeholder.com/150';

// Dynamic Base Path Logic
$basePath = './';
$scriptDir = dirname($_SERVER['PHP_SELF']);
$dirName = basename($scriptDir);

if ($dirName == 'admin' || $dirName == 'employer' || $dirName == 'employee' || $dirName == 'layout') {
    $basePath = '../';
} elseif ($dirName == 'actions') {
    $basePath = '../';
}

try {
    // Only fetch if not already set (in case the parent script set it)
    if (!isset($brand) || !is_array($brand)) {
        $brand = $pdo->query("SELECT * FROM Compan_details LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    if ($brand) {
        if (!empty($brand['company_name'])) $siteName = $brand['company_name'];
        if (!empty($brand['logo_path'])) {
            $logoSrc = $basePath . 'uploads/system/' . basename($brand['logo_path']);
        } elseif (!empty($brand['logo'])) {
            // Fallback for migration
            $logoSrc = 'data:image/png;base64,' . base64_encode($brand['logo']);
        }
    }
} catch (Exception $e) { error_log($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . " | " : "" ?><?= htmlspecialchars($siteName) ?> | Professional Job Portal</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= isset($metaDesc) ? htmlspecialchars($metaDesc) : 'Find your dream job or hire top talent on ' . htmlspecialchars($siteName) . '. The leading job portal in Sri Lanka connecting employers and job seekers.' ?>">
    <meta name="keywords" content="jobs, vacancies, sri lanka, recruitment, hiring, employment, careers, <?= htmlspecialchars($siteName) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://tiptopvacancies.com<?= $_SERVER['REQUEST_URI'] ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://tiptopvacancies.com<?= $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= isset($pageTitle) ? htmlspecialchars($pageTitle) . " | " : "" ?><?= htmlspecialchars($siteName) ?>">
    <meta property="og:description" content="<?= isset($metaDesc) ? htmlspecialchars($metaDesc) : 'Find your dream job or hire top talent on ' . htmlspecialchars($siteName) . '.' ?>">
    <meta property="og:image" content="<?= str_replace('./', 'https://tiptopvacancies.com/', $logoSrc) ?>">

    <!-- Favicon -->
    <link rel="icon" href="<?= $basePath ?>uploads/system/favicon.png" type="image/png">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= $basePath ?>layout/custom.css" rel="stylesheet">

    <!-- Page Specific CSS -->
    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand d-flex align-items-center" href="<?= $basePath ?>index.php">
            <img src="<?= $logoSrc ?>" alt="Logo">
            <span class="fs-4 ms-2"><?= htmlspecialchars($siteName) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="ms-auto d-flex align-items-center gap-2">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark fw-bold" data-bs-toggle="dropdown">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:35px; height:35px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span>My Account</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                             <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employer'): ?>
                                <li><a class="dropdown-item" href="<?= $basePath ?>employer/dashboard.php"><i class="fas fa-columns me-2 text-primary"></i> Dashboard</a></li>
                            <?php elseif(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employee'): ?>
                                <li><a class="dropdown-item" href="<?= $basePath ?>employee/dashboard.php"><i class="fas fa-columns me-2 text-primary"></i> Dashboard</a></li>
                            <?php elseif(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Admin'): ?>
                                <li><a class="dropdown-item" href="<?= $basePath ?>admin/dashboard.php"><i class="fas fa-columns me-2 text-primary"></i> Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $basePath ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= $basePath ?>login.php" class="nav-login">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                    <a href="<?= $basePath ?>register.php" class="btn btn-post-job">
                        <i class="fas fa-plus-circle me-1"></i> Post a Job
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
