<?php
session_start();
require_once '../config/config.php';

// 1. Security & Profile Verification
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header("Location: ../login.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Optimized Data Fetching
    $stmt = $pdo->prepare("
        SELECT ep.id, ep.employer_name,
        (SELECT COUNT(*) FROM advertising_table WHERE link_to_employer_profile = ep.id) as total_ads,
        (SELECT COUNT(*) FROM job_applications ja JOIN advertising_table ad ON ja.job_ad_link = ad.id WHERE ad.link_to_employer_profile = ep.id) as reg_apps,
        (SELECT COUNT(*) FROM guest_job_applications ga JOIN advertising_table ad ON ga.job_ad_link = ad.id WHERE ad.link_to_employer_profile = ep.id) as guest_apps
        FROM employer_profile ep WHERE ep.link_to_user = ?
    ");
    $stmt->execute([$user_id]);
    $data = $stmt->fetch();

    if (!$data) { 
        header("Location: profile.php?msg=complete_profile"); 
        exit(); 
    }

} catch (Exception $e) { 
    die("System Error: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= htmlspecialchars($data['employer_name']) ?></title>
    
    <link rel="icon" href="../uploads/system/favicon.png" type="image/png">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .tox-notifications-container { display: none !important; }
        :root {
            --sidebar-width: 260px;
            --primary: #4f46e5;
            --surface: #ffffff;
            --body-bg: #ffffff;
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
            background: #eef2ff;
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
            display: none; /* Hidden on desktop */
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
        .stat-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, #4338ca 100%);
            border-radius: 16px;
            padding: 32px;
            color: white;
            margin-bottom: 32px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }

        .btn-primary-custom {
            background: white;
            color: var(--primary);
            border: none;
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
        }

        .btn-primary-custom:hover {
            background: #f8fafc;
            color: var(--primary);
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
            <span>JobPortal Pro</span>
        </div>
        <button class="btn btn-sm text-secondary d-lg-none" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
    </div>

    <nav>
        <div class="nav-link-custom active" onclick="loadContent('home'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-home"></i> Overview
        </div>
        <div class="nav-link-custom" onclick="loadContent('view_applications'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-users"></i> Applicants
        </div>
        <div class="nav-link-custom" onclick="loadContent('talent_pool'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-search"></i> Talent Search
        </div>
        <div class="nav-link-custom" onclick="loadContent('manage_jobs'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-briefcase"></i> My Listings
        </div>
        <div class="nav-link-custom" onclick="loadContent('billing'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-credit-card"></i> Billing
        </div>
        <div class="nav-link-custom" onclick="loadContent('bank_details'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-university"></i> Bank Info
        </div>
        <div class="nav-link-custom" onclick="loadContent('profile_settings'); if(window.innerWidth < 992) toggleSidebar();">
            <i class="fas fa-cog"></i> Settings
        </div>
    </nav>

    <div style="margin-top: auto; padding-top: 2rem; border-top: 1px solid var(--border-color);">
        <a href="../actions/logout.php" class="nav-link-custom text-danger">
            <i class="fas fa-sign-out-alt"></i> Sign Out
        </a>
    </div>
</aside>

<main class="main-content">
    <div id="dynamic-content-area">
        <!-- Default Content: Dashboard Home -->
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">Hello, <?= explode(' ', $data['employer_name'])[0] ?>!</h2>
                    <p class="mb-4 opacity-75">You have <?= $data['reg_apps'] + $data['guest_apps'] ?> new candidates to review today.</p>
                    <button class="btn btn-primary-custom" onclick="loadContent('post_job')">
                        <i class="fas fa-plus me-2"></i> Post New Job
                    </button>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="fas fa-chart-line fa-4x opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Active Jobs</div>
                    <div class="stat-value"><?= $data['total_ads'] ?></div>
                    <div class="text-success small mt-2 fw-medium"><i class="fas fa-check-circle me-1"></i> Currently Live</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Total Applicants</div>
                    <div class="stat-value"><?= $data['reg_apps'] + $data['guest_apps'] ?></div>
                    <div class="text-primary small mt-2 fw-medium"><i class="fas fa-user-plus me-1"></i> All Time</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Profile Views</div>
                    <div class="stat-value">1.2k</div> <!-- Mock Data for visual -->
                    <div class="text-muted small mt-2 fw-medium">Last 30 Days</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Recruitment Analytics</h5>
                <canvas id="analyticsChart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
    </div>
</main>

<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /**
     * Sidebar Toggle Logic
     */
    function toggleSidebar() {
        $('#sidebar').toggleClass('show');
        $('.sidebar-overlay').fadeToggle(200);
    }

    /**
     * SPA Content Loader & Sidebar Logic
     */
    function loadContent(pageName) {
        $('#content-loader').show().css('width', '50%');
        
        // 1. Destroy old Editor instances (TinyMCE)
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
            'view_applications': 'view_applications.php',
            'talent_pool': 'talent_pool.php',
            'manage_jobs': 'manage_jobs.php',
            'billing': 'billing.php',
            'bank_details': 'bank_details.php',
            'post_job': 'post_job.php',
            'edit_job': 'edit_job.php',
            'reupload_payment': 'reupload_payment.php',
            'profile_settings': 'profile.php'
        };

        let baseName = pageName;
        let params = '';
        if (pageName.includes('?')) {
            const parts = pageName.split('?');
            baseName = parts[0];
            params = '?' + parts[1];
        }

        const targetFile = pageMap[baseName];
        if (!targetFile) {
            console.error('Page not found:', baseName);
            $('#content-loader').hide();
            return;
        }

        if (targetFile === 'RELOAD') {
            window.location.href = 'dashboard.php';
            return;
        }

        // 2. CACHE BUSTER: Add a random timestamp to the URL
        const cacheBuster = (params ? '&' : '?') + '_=' + new Date().getTime();
        const targetUrl = targetFile + params + cacheBuster;

        $.ajax({
            url: targetUrl,
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function(response) {
                $('#content-loader').css('width', '100%').fadeOut(300);
                
                const htmlResponse = $.parseHTML(response, document, true);
                const newContent = $(htmlResponse).find('.container, .container-fluid').html() || response;
                
                $('#dynamic-content-area').fadeOut(150, function() {
                    $(this).html(newContent).fadeIn(200);
                    // Initialize plugins ONLY after HTML is injected
                    setTimeout(initPlugins, 200);
                });
            },
            error: function() {
                $('#content-loader').hide();
                alert('Error loading content. Please check your connection.');
            }
        });
    }

    // Initialize CKEditor safely
    let editorInstance = null;

    function initPlugins() {
        initAnalytics();

        // Re-init Bootstrap Tooltips & Dropdowns
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl)
        });

        // Live Preview Logic
        const fName = document.getElementById('f_name');
        const pName = document.getElementById('p_name');
        if(fName && pName) {
            fName.addEventListener('input', () => pName.textContent = fName.value || 'Company Name');
        }
    }

    // Slip Viewer Logic
    function viewSlip(url) {
        if(!url) return;
        $('#slipImage').attr('src', url);
        new bootstrap.Modal(document.getElementById('slipModal')).show();
    }

    $(document).ready(function() {
        initAnalytics();

        // Initial Page Load from URL Params
        const urlParams = new URLSearchParams(window.location.search);
        let page = urlParams.get('page');

        if (page) {
            // Remove the 'active' class from Overview
            $('.nav-link-custom').removeClass('active');

            // Try to find the nav link matching this page and make it active
            $('.nav-link-custom').each(function() {
                if ($(this).attr('onclick') && $(this).attr('onclick').includes("'" + page.split('?')[0] + "'")) {
                    $(this).addClass('active');
                }
            });

            // Re-append other query parameters (e.g. updated=1, status=Rejected)
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.delete('page'); // Remove 'page' so we don't duplicate it
            const paramString = currentParams.toString();

            if (paramString) {
                // If the page string doesn't already have params, add ?
                // If it does, add & (though usually 'page' here is just the name)
                page += (page.includes('?') ? '&' : '?') + paramString;
            }

            loadContent(page);
        }
    });
</script>

<!-- Payment Slip Modal -->
<div class="modal fade" id="slipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg bg-transparent">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                <img id="slipImage" src="" class="img-fluid rounded-4 shadow-lg" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<?php include '../layout/ui_helpers.php'; ?>
<?php include '../layout/chat_widget.php'; ?>
</body>
</html>
