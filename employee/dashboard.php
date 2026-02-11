<?php
session_start();
require_once '../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$site_name = "JobQuest Pro";

// INITIALIZE VARIABLES (For Default View)
$app_count = 0; 
$doc_count = 0;
$user_data = ['id' => null, 'employee_full_name' => 'User', 'employee_img' => null];
$matched_jobs = [];

try {
    // 2. Fetch Profile Info
    $stmt = $pdo->prepare("SELECT id, employee_full_name, employee_img FROM employee_profile_seeker WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $fetched_user = $stmt->fetch();
    
    if ($fetched_user) {
        $user_data = $fetched_user;
        $profile_id = $user_data['id'];

        // 3. Application Count
        $stmt_stats = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE seeker_link = ?");
        $stmt_stats->execute([$profile_id]);
        $app_count = $stmt_stats->fetchColumn();

        // 4. Document Count
        $stmt_docs = $pdo->prepare("SELECT COUNT(*) FROM employee_document WHERE link_to_employee_profile = ?");
        $stmt_docs->execute([$profile_id]);
        $doc_count = $stmt_docs->fetchColumn();

        // 5. Job Alerts / Matched Jobs (Multiple Criteria Support)
        $alert_stmt = $pdo->prepare("SELECT * FROM employee_alerted_setting WHERE link_to_employee_profile = ? AND active = 1");
        $alert_stmt->execute([$profile_id]);
        $all_prefs = $alert_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($all_prefs) {
            $conditions = [];
            $params = [];

            foreach($all_prefs as $p) {
                $row_conds = [];
                // If specific district is set, match it
                if(!empty($p['district'])) {
                    $row_conds[] = "District = ?";
                    $params[] = $p['district'];
                }
                // If specific city is set, match it
                if(!empty($p['city'])) {
                    $row_conds[] = "City = ?";
                    $params[] = $p['city'];
                }
                // If specific category is set, match it
                if(!empty($p['job_category'])) {
                    $row_conds[] = "Job_category = ?";
                    $params[] = $p['job_category'];
                }

                if(!empty($row_conds)) {
                    $conditions[] = "(" . implode(" AND ", $row_conds) . ")";
                }
            }

            if(!empty($conditions)) {
                $sql = "SELECT DISTINCT * FROM advertising_table WHERE Approved = 1 AND (" . implode(" OR ", $conditions) . ") ORDER BY Opening_date DESC LIMIT 6";
                $match_stmt = $pdo->prepare($sql);
                $match_stmt->execute($params);
                $matched_jobs = $match_stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= $site_name ?></title>

    <link rel="icon" href="../uploads/system/favicon.png" type="image/png">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

    <style>
        .tox-notifications-container { display: none !important; }
        :root {
            --sidebar-width: 260px;
            --primary: #2563eb;
            --surface: #ffffff;
            --body-bg: #f8fafc;
            --border-color: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
        }

        body {
            background: var(--body-bg);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            color: var(--text-primary);
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: #ffffff;
            border-right: 1px solid var(--border-color);
            padding: 24px;
            z-index: 1000;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .brand-logo {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 4px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .nav-link-custom:hover {
            background: #f1f5f9;
            color: var(--text-primary);
        }

        .nav-link-custom.active {
            background: #eff6ff;
            color: var(--primary);
            font-weight: 600;
        }

        .nav-link-custom i {
            width: 24px;
            margin-right: 10px;
            text-align: center;
        }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            min-height: 100vh;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* --- MOBILE TOGGLE --- */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 8px 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            color: var(--text-primary);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* --- DASHBOARD WIDGETS --- */
        .stat-card-pro {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
        }

        .stat-card-pro:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .icon-box {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        
        .bg-soft-blue { background: #eff6ff; color: #2563eb; }
        .bg-soft-purple { background: #faf5ff; color: #9333ea; }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, #4338ca 100%);
            border-radius: 16px;
            padding: 32px;
            color: white;
            margin-bottom: 32px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }

        .match-item {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: 0.2s;
        }
        .match-item:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        #content-loader {
            display: none;
            position: fixed;
            top: 0; left: var(--sidebar-width); right: 0;
            height: 3px;
            background: var(--primary);
            z-index: 2000;
        }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; padding-top: 80px; }
            .mobile-toggle { display: block; }
            #content-loader { left: 0; }
        }
    </style>
</head>
<body>

<div id="content-loader"></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Mobile Toggle Button -->
<button class="mobile-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars fa-lg"></i>
</button>

<aside class="sidebar shadow-sm" id="sidebar">
    <div class="brand-logo">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-rocket"></i>
            <span><?= $site_name ?></span>
        </div>
        <button class="btn btn-sm text-secondary d-lg-none" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
    </div>

    <nav>
        <div class="nav-link-custom active" onclick="loadContent('home'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-grid-2"></i> Overview
        </div>
        <div class="nav-link-custom" onclick="loadContent('browse_jobs'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-briefcase"></i> Browse Jobs
        </div>
        <div class="nav-link-custom" onclick="loadContent('my_applications'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-layer-group"></i> Applications
        </div>
        <div class="nav-link-custom" onclick="loadContent('manage_documents'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-file-shield"></i> Documents
        </div>
        <div class="nav-link-custom" onclick="loadContent('alert_settings'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-bolt"></i> Job Alerts
        </div>
        <div class="nav-link-custom" onclick="loadContent('promote_self'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-bullhorn"></i> Promote Me
        </div>
        <div class="nav-link-custom" onclick="loadContent('profile'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-circle-user"></i> My Profile
        </div>
    </nav>

    <div style="margin-top: auto; padding-top: 2rem; border-top: 1px solid var(--border-color);">
        <a href="../actions/logout.php" class="nav-link-custom text-danger">
            <i class="fas fa-power-off"></i> Sign Out
        </a>
    </div>
</aside>

<main class="main-content">
    <div id="dynamic-content-area">
        <!-- Default Content: Dashboard Home -->
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">Hello, <?= explode(' ', trim($user_data['employee_full_name']))[0] ?>! 👋</h2>
                    <p class="mb-4 opacity-75">Here is what's happening with your job search today.</p>
                    <button class="btn btn-light text-primary fw-bold px-4" onclick="loadContent('browse_jobs')">
                        <i class="fas fa-search me-2"></i> Find Jobs
                    </button>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="fas fa-rocket fa-4x opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card-pro">
                    <div class="icon-box bg-soft-blue me-3">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $app_count ?></h3>
                        <span class="text-muted small">Applications</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-pro">
                    <div class="icon-box bg-soft-purple me-3">
                        <i class="fas fa-file-lines"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $doc_count ?></h3>
                        <span class="text-muted small">Documents</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-pro" onclick="loadContent('alert_settings')" style="cursor: pointer;">
                    <div class="icon-box bg-primary text-white me-3">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Job Alerts</h6>
                        <span class="small opacity-75">View Settings</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 rounded-4" style="min-height: 400px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0"><i class="fas fa-wand-magic-sparkles text-primary me-2"></i>Smart Match Recommendations</h5>
                        <span class="badge bg-light text-dark fw-medium"><?= count($matched_jobs) ?> Matches</span>
                    </div>

                    <?php if (empty($matched_jobs)): ?>
                        <div class="text-center py-5">
                            <div class="display-1 text-muted opacity-25 mb-3"><i class="fas fa-search"></i></div>
                            <p class="text-muted">No specific matches yet. Try refining your <a href="#" onclick="loadContent('alert_settings'); return false;">preferences</a>.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($matched_jobs as $job): ?>
                                <div class="col-12 col-xl-6">
                                    <div class="match-item">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="badge bg-primary-subtle text-primary small"><?= htmlspecialchars($job['Job_category']) ?></span>
                                            <span class="text-muted small"><i class="far fa-clock me-1"></i>New</span>
                                        </div>
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($job['Job_role']) ?></h6>
                                        <p class="text-muted small mb-3"><i class="fas fa-location-dot me-1"></i> <?= htmlspecialchars($job['City']) ?></p>
                                        <a href="../job_view.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary w-100 rounded-pill">Apply Now</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                    <h6 class="fw-bold mb-3">Profile Strength</h6>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= ($doc_count > 0) ? '85' : '50' ?>%"></div>
                    </div>
                    <p class="text-muted small mb-4">Complete your documents to reach 100% visibility.</p>
                    <button onclick="loadContent('manage_documents')" class="btn btn-light btn-sm w-100 fw-bold border mb-2">Manage Documents</button>
                    <button onclick="loadContent('profile')" class="btn btn-light btn-sm w-100 fw-bold border">Edit My Info</button>
                </div>

                <div class="card border-0 shadow-sm p-4 rounded-4 bg-dark text-white">
                    <h6 class="fw-bold mb-2">Need Help?</h6>
                    <p class="small opacity-75">Our career support team is available 24/7 to assist with your applications.</p>
                    <a href="../contact.php" class="btn btn-primary btn-sm w-100 rounded-pill mt-2">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() {
        $('#sidebar').toggleClass('show');
        $('.sidebar-overlay').fadeToggle(200);
    }

    function loadContent(pageName) {
        $('#content-loader').show().css('width', '50%');

        // 1. Destroy existing editor (TinyMCE)
        if (typeof tinymce !== 'undefined') {
            tinymce.remove();
        }

        // Update Sidebar Active State
        if (window.event && window.event.currentTarget) {
            $('.nav-link-custom').removeClass('active');
            $(window.event.currentTarget).addClass('active');
        }

        const pageMap = {
            'home': 'RELOAD',
            'browse_jobs': 'browse_jobs.php',
            'my_applications': 'my_applications.php',
            'manage_documents': 'manage_documents.php',
            'alert_settings': 'alert_settings.php',
            'promote_self': 'promote_self.php',
            'profile': 'profile.php'
        };

        const targetFile = pageMap[pageName];
        if (!targetFile) return;

        if (targetFile === 'RELOAD') {
            window.location.href = 'dashboard.php';
            return;
        }

        // 2. Cache Buster
        const targetUrl = targetFile + '?_=' + new Date().getTime();

        $.ajax({
            url: targetUrl,
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function(response) {
                $('#content-loader').css('width', '100%').fadeOut(300);

                // Extract container content safely
                const htmlResponse = $.parseHTML(response, document, true);
                const newContent = $(htmlResponse).find('.container, .container-fluid').html() || response;

                $('#dynamic-content-area').fadeOut(150, function() {
                    $(this).html(newContent).fadeIn(200);
                    // 3. Delay init to ensure DOM is ready
                    setTimeout(initPlugins, 200);
                });
            },
            error: function() {
                $('#content-loader').hide();
                alert('Error loading content.');
            }
        });
    }

    function initPlugins() {
        // Init logic moved to individual fragments for better isolation
    }
</script>
<?php include '../layout/ui_helpers.php'; ?>
<?php include '../layout/chat_widget.php'; ?>
</body>
</html>
