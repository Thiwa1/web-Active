<?php
require_once 'config/config.php';
session_start();

$districts = [];
$categories = [];

try {
    // Branding logic is now in header.php, but we need data for search filters
    $districts = $pdo->query("SELECT * FROM district_table ORDER BY District_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query("SELECT j.Description as Job_category, COUNT(a.id) as count FROM job_category_table j LEFT JOIN advertising_table a ON j.Description = a.Job_category AND a.Approved = 1 GROUP BY j.Description ORDER BY j.Description ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Stats
    // 1. Job Type Counts (Approved only)
    $statsQuery = "SELECT job_type, COUNT(*) as count FROM advertising_table WHERE Approved = 1 GROUP BY job_type";
    $typeStats = $pdo->query($statsQuery)->fetchAll(PDO::FETCH_KEY_PAIR);

    // Normalize keys in case of NULL or different casing
    $onlineCount = $typeStats['Online'] ?? 0;
    $partTimeCount = $typeStats['Part Time'] ?? 0;
    $fullTimeCount = $typeStats['Full Time'] ?? 0;
    $schoolLeaverCount = $typeStats['School Leaver'] ?? 0;

    // 2. Total Visitors (Approximation from job_views_log or page hits)
    // Using a simple count of job_views_log as a proxy for "Total Views"
    $viewsCount = $pdo->query("SELECT COUNT(*) FROM job_views_log")->fetchColumn();

    // 3. Total Seekers
    $seekersCount = $pdo->query("SELECT COUNT(*) FROM employee_profile_seeker")->fetchColumn();

    // 4. Total Employers
    $employersCount = $pdo->query("SELECT COUNT(*) FROM employer_profile")->fetchColumn();

} catch (Exception $e) { error_log($e->getMessage()); }

$pageTitle = "Home";
$extraCss = '<style>
    /* HERO SECTION */
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 80px 0 140px;
        position: relative;
        overflow: hidden;
    }
    .hero-section::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url(\'data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="none"/><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.05)"/></svg>\');
        opacity: 0.5;
    }
    .hero-content {
        position: relative;
        z-index: 1;
        color: white;
        text-align: center;
    }
    .hero-title {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 20px;
        text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        letter-spacing: -1px;
    }
    .hero-subtitle {
        font-size: 1.3rem;
        font-weight: 300;
        opacity: 0.95;
        margin-bottom: 0;
    }

    /* SEARCH CARD */
    .search-card {
        margin-top: 20px; /* Adjusted for banner */
        position: relative;
        z-index: 10;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        padding: 35px;
        border: 1px solid rgba(255,255,255,0.8);
    }

    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .search-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--secondary);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .form-control, .form-select {
        border: 2px solid var(--border);
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }
    .input-group-text {
        background: transparent;
        border: 2px solid var(--border);
        border-right: none;
        border-radius: 10px 0 0 10px;
    }
    .input-group .form-control {
        border-left: none;
        border-radius: 0 10px 10px 0;
    }
    .btn-search {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 12px 32px;
        font-weight: 700;
        letter-spacing: 0.5px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        transition: all 0.3s;
        color: white;
    }
    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }

    /* CITY DROPDOWN - FIXED Z-INDEX */
    .city-dropdown {
        position: relative;
        z-index: 9999;
    }
    .city-dropdown-btn {
        background: white;
        border: 2px solid var(--border);
        padding: 12px 16px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
        min-height: 48px;
        z-index: 9998;
    }
    .city-dropdown-btn:hover {
        border-color: var(--primary);
    }
    .city-dropdown-btn.active {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }
    .city-menu {
        position: absolute;
        z-index: 10000;
        background: white;
        border: 2px solid var(--border);
        width: 100%;
        max-height: 320px;
        overflow-y: auto;
        display: none;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        margin-top: 8px;
        top: 100%;
        left: 0;
    }
    .city-menu.show { display: block; }
    .city-item {
        padding: 8px 0;
        cursor: pointer;
        transition: all 0.2s;
        border-bottom: 1px solid var(--border);
    }
    .city-item:last-child {
        border-bottom: none;
    }
    .city-item:hover {
        padding-left: 5px;
        background-color: var(--excel-alt);
    }
    .city-item label {
        cursor: pointer;
        font-size: 0.9rem;
        margin-bottom: 0;
        display: flex;
        align-items: center;
        width: 100%;
    }

    /* MAIN CONTENT */
    .main-content {
        background: var(--light);
        min-height: 60vh;
        padding: 40px 0;
        position: relative;
        z-index: 1;
    }

    /* SIDEBAR - EXCEL STYLE */
    .sidebar-panel {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid var(--border);
    }
    .sidebar-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .category-list {
        padding: 10px 0;
        max-height: 400px;
        overflow-y: auto;
    }
    .cat-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 20px;
        text-decoration: none;
        color: var(--dark);
        border-left: 4px solid transparent;
        transition: all 0.2s;
        font-size: 0.9rem;
        cursor: pointer;
        font-weight: 500;
    }
    .cat-link:hover {
        background: var(--excel-alt);
        border-left-color: var(--primary);
        color: var(--primary);
        padding-left: 24px;
    }
    .cat-link.active {
        background: linear-gradient(to right, rgba(102, 126, 234, 0.1), transparent);
        border-left-color: var(--primary);
        color: var(--primary);
        font-weight: 700;
    }
    .cat-badge {
        background: var(--light);
        color: var(--secondary);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .cat-link.active .cat-badge {
        background: var(--primary);
        color: white;
    }

    /* EXCEL-STYLE TABLE (DESKTOP) */
    .excel-container {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid var(--border);
        position: relative;
        z-index: 1;
    }
    .excel-toolbar {
        background: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 2px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .excel-title {
        font-weight: 700;
        color: var(--dark);
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .excel-count {
        background: var(--primary);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .excel-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.9rem;
    }
    .excel-table thead th {
        background: var(--excel-header);
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 16px 12px;
        text-align: left;
        border-right: 1px solid rgba(255,255,255,0.1);
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .excel-table thead th:last-child {
        border-right: none;
    }
    .excel-table tbody tr {
        transition: all 0.2s;
        border-bottom: 1px solid var(--border);
    }
    .excel-table tbody tr:nth-child(even) {
        background: var(--excel-alt);
    }
    .excel-table tbody tr:hover {
        background: #fef3c7;
        transform: scale(1.01);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .excel-table td {
        padding: 16px 12px;
        vertical-align: middle;
        border-right: 1px solid var(--border);
    }
    .excel-table td:last-child {
        border-right: none;
    }
    .cell-number {
        font-weight: 700;
        color: var(--secondary);
        width: 60px;
        text-align: center;
    }
    .cell-position {
        font-weight: 600;
        color: var(--dark);
        min-width: 200px;
    }
    .cell-company {
        min-width: 180px;
    }
    .cell-category {
        min-width: 150px;
    }
    .cell-location {
        min-width: 160px;
    }
    .cell-date {
        min-width: 110px;
        text-align: center;
    }
    .company-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .company-icon {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .location-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .date-badge {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    .date-open {
        background: #d1fae5;
        color: #065f46;
    }
    .date-close {
        background: #fee2e2;
        color: #991b1b;
    }
    .btn-view-excel {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    .btn-view-excel:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }

    /* ENHANCED MOBILE CARD VIEW */
    #mobileResults {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        padding: 10px 0;
    }

    .mobile-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .mobile-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }

    .mobile-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border);
    }

    .mobile-job-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 40px;
        text-align: center;
    }

    .mobile-job-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 8px;
        line-height: 1.3;
        flex: 1;
        padding-right: 15px;
    }

    .mobile-company-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
        padding: 12px;
        background: var(--excel-alt);
        border-radius: 10px;
    }

    .mobile-company-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .mobile-company-details {
        flex: 1;
    }

    .mobile-company-name {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 3px;
        font-size: 0.95rem;
    }

    .mobile-job-category {
        font-size: 0.85rem;
        color: var(--primary);
        background: rgba(37, 99, 235, 0.1);
        padding: 3px 10px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 5px;
    }

    .mobile-job-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 20px;
    }

    .mobile-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: var(--secondary);
        padding: 10px;
        background: var(--light);
        border-radius: 10px;
    }

    .mobile-meta-item i {
        color: var(--primary);
        width: 20px;
        text-align: center;
    }

    .mobile-dates-container {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .mobile-date-card {
        flex: 1;
        padding: 12px;
        border-radius: 10px;
        text-align: center;
        font-size: 0.8rem;
    }

    .mobile-date-card.open {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .mobile-date-card.close {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .mobile-date-label {
        font-weight: 600;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
    }

    .mobile-date-value {
        font-weight: 700;
        font-size: 0.9rem;
    }

    .mobile-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid var(--border);
    }

    .mobile-location {
        font-size: 0.85rem;
        color: var(--secondary);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-view-mobile {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
        box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-view-mobile:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .mobile-no-jobs {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        margin: 20px 0;
    }

    .mobile-no-jobs i {
        font-size: 3rem;
        color: var(--border);
        margin-bottom: 20px;
        display: block;
    }

    .mobile-no-jobs h4 {
        color: var(--dark);
        font-weight: 700;
        margin-bottom: 10px;
    }

    .mobile-no-jobs p {
        color: var(--secondary);
        margin-bottom: 0;
    }

    /* STATS SECTION */
    .stats-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        border: 1px solid var(--border);
        transition: transform 0.3s ease;
        height: 100%;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 1.5rem;
    }
    .stats-number {
        font-size: 2rem;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 5px;
    }
    .stats-label {
        color: var(--secondary);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    .bg-soft-primary { background-color: rgba(79, 70, 229, 0.1); color: var(--primary); }
    .bg-soft-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .bg-soft-warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .bg-soft-danger { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .bg-soft-info { background-color: rgba(6, 182, 212, 0.1); color: #06b6d4; }
    .bg-soft-purple { background-color: rgba(168, 85, 247, 0.1); color: #a855f7; }

    /* RESPONSIVE */
    @media (max-width: 991px) {
        .desktop-only { display: none !important; }
        .mobile-only { display: block !important; }
        .hero-title { font-size: 2.5rem; }
        .search-card { padding: 25px; }
        .mobile-job-meta {
            grid-template-columns: 1fr;
        }
    }
    @media (min-width: 992px) {
        .mobile-only { display: none !important; }
    }
    @media (max-width: 576px) {
        .mobile-dates-container {
            flex-direction: column;
        }
        .mobile-card-footer {
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }
        .btn-view-mobile {
            width: 100%;
            justify-content: center;
        }
    }
</style>';

include 'layout/header.php';
?>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Find Your Dream Job</h1>
            <p class="hero-subtitle">Connecting Sri Lanka's finest talent with leading employers</p>
        </div>
    </div>
</section>

<!-- PROMOTION BANNER -->
<section class="py-5 d-none d-lg-block" style="background: white; margin-top: -60px; position: relative; z-index: 5; border-radius: 20px 20px 0 0;">
    <div class="container px-lg-5">
        <div class="row g-4 justify-content-center text-center">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="p-3 border rounded-4 h-100 hover-shadow transition-all">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-bullhorn fs-4"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Talent Promotion</h6>
                    <p class="small text-muted mb-0">Boost your profile visibility</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="p-3 border rounded-4 h-100 hover-shadow transition-all">
                    <div class="icon-box bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-sms fs-4"></i>
                    </div>
                    <h6 class="fw-bold mb-1">SMS Alerts</h6>
                    <p class="small text-muted mb-0">Instant job notifications</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="p-3 border rounded-4 h-100 hover-shadow transition-all">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-file-upload fs-4"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Post CV</h6>
                    <p class="small text-muted mb-0">Upload via Mobile App</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="p-3 border rounded-4 h-100 hover-shadow transition-all">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-user-clock fs-4"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Guest Apply</h6>
                    <p class="small text-muted mb-0">No registration needed</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="p-3 border rounded-4 h-100 hover-shadow transition-all">
                    <div class="icon-box bg-info bg-opacity-10 text-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-trophy fs-4"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Market Leader</h6>
                    <p class="small text-muted mb-0">Top recruitment platform</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SEARCH SECTION -->
<div class="container px-lg-5">
    <div class="search-card mt-0">
        <form id="searchForm" class="row g-4">
            <input type="hidden" name="category" id="hiddenCat" value="">
            
            <div class="col-lg-3 col-md-6">
                <label class="search-label">
                    <i class="fas fa-search me-1"></i> Keyword
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-briefcase text-muted"></i>
                    </span>
                    <input type="text" name="q" id="keyword" class="form-control" placeholder="Job title, skills...">
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <label class="search-label">
                    <i class="fas fa-map-marked-alt me-1"></i> District
                </label>
                <select name="district_id" id="districtSelect" class="form-select">
                    <option value="">All Districts</option>
                    <?php foreach($districts as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['District_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <label class="search-label">
                    <i class="fas fa-city me-1"></i> Cities
                </label>
                <div class="city-dropdown">
                    <div class="city-dropdown-btn" id="cityBtn">
                        <span id="cityLabel" class="text-muted">Select cities...</span>
                        <i class="fas fa-chevron-down text-muted"></i>
                    </div>
                    <div class="city-menu" id="cityMenu">
                        <input type="text" id="citySearch" class="form-control mb-3" placeholder="Search cities...">
                        <div id="cityContent" class="text-muted small">Select a district first</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-search w-100">
                    <i class="fas fa-search me-2"></i> SEARCH
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="container-fluid px-lg-5">
        <div class="row g-4">
            <!-- SIDEBAR (DESKTOP) -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="sidebar-panel">
                    <div class="sidebar-header">
                        <i class="fas fa-filter me-2"></i> Filter by Category
                    </div>
                    <div class="category-list" id="sidebarLinks">
                        <a class="cat-link active" data-val="">
                            <span><i class="fas fa-th-large me-2"></i> All Vacancies</span>
                            <span class="cat-badge">All</span>
                        </a>
                        <?php foreach($categories as $cat): ?>
                            <a class="cat-link" data-val="<?= htmlspecialchars($cat['Job_category']) ?>">
                                <span class="text-truncate"><?= htmlspecialchars($cat['Job_category']) ?></span>
                                <span class="cat-badge"><?= $cat['count'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT AREA -->
            <div class="col-lg-9">
                <!-- MOBILE FILTER TOGGLE -->
                <div class="d-lg-none mb-3">
                    <button class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                        <i class="fas fa-filter me-2"></i> Filter Jobs
                    </button>
                </div>

                <!-- DESKTOP EXCEL VIEW -->
                <div class="excel-container desktop-only">
                    <div class="excel-toolbar">
                        <div class="excel-title">
                            <i class="fas fa-table"></i>
                            Job Listings
                            <span class="excel-count" id="jobCount">0</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="excel-table">
                            <thead>
                                <tr>
                                    <th class="cell-number">#</th>
                                    <th class="cell-position">POSITION</th>
                                    <th class="cell-company">COMPANY</th>
                                    <th class="cell-category">CATEGORY</th>
                                    <th class="cell-location">LOCATION</th>
                                    <th class="cell-date">OPENING</th>
                                    <th class="cell-date">CLOSING</th>
                                    <th style="width: 120px; text-align: center;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="desktopResults">
                                <!-- Jobs will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MOBILE CARD VIEW -->
                <div id="mobileResults" class="mobile-only">
                    <!-- Mobile cards will be loaded here -->
                </div>

                <!-- LOADER -->
                <div id="loader" class="loader-container d-none">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-3 text-muted">Loading jobs...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STATS SECTION -->
<div class="container px-lg-5 py-5 mb-5">
    <div class="row g-4 justify-content-center">
        <!-- Job Type Stats -->
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stats-card">
                <div class="stats-icon bg-soft-primary">
                    <i class="fas fa-laptop-house"></i>
                </div>
                <div class="stats-number"><?= $onlineCount ?></div>
                <div class="stats-label">Online Jobs</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stats-card">
                <div class="stats-icon bg-soft-success">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-number"><?= $partTimeCount ?></div>
                <div class="stats-label">Part Time</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stats-card">
                <div class="stats-icon bg-soft-warning">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="stats-number"><?= $fullTimeCount ?></div>
                <div class="stats-label">Full Time</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stats-card">
                <div class="stats-icon bg-soft-danger">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stats-number"><?= $schoolLeaverCount ?></div>
                <div class="stats-label">School Leavers</div>
            </div>
        </div>

        <!-- Platform Stats -->
        <div class="col-lg-2 col-md-4 col-6">
             <div class="stats-card">
                <div class="stats-icon bg-soft-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number"><?= $seekersCount + $employersCount ?></div>
                <div class="stats-label">Total Users</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stats-card">
                <div class="stats-icon bg-soft-purple">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stats-number"><?= number_format($viewsCount) ?></div>
                <div class="stats-label">Total Views</div>
            </div>
        </div>
    </div>
</div>

<!-- MOBILE FILTER OFFCANVAS -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="filterOffcanvas" style="z-index: 10000;">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filters</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="category-list">
            <a class="cat-link active mobile-cat-link" data-val="">
                <span><i class="fas fa-th-large me-2"></i> All Vacancies</span>
                <span class="cat-badge">All</span>
            </a>
            <?php foreach($categories as $cat): ?>
                <a class="cat-link mobile-cat-link" data-val="<?= htmlspecialchars($cat['Job_category']) ?>">
                    <span class="text-truncate"><?= htmlspecialchars($cat['Job_category']) ?></span>
                    <span class="cat-badge"><?= $cat['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// City Dropdown Logic
const cityBtn = document.getElementById('cityBtn');
const cityMenu = document.getElementById('cityMenu');
const citySearch = document.getElementById('citySearch');

if (cityBtn) {
    cityBtn.onclick = (e) => {
        e.stopPropagation();
        cityMenu.classList.toggle('show');
        cityBtn.classList.toggle('active');
    };

    document.onclick = (e) => {
        if(!cityMenu.contains(e.target) && e.target !== cityBtn) {
            cityMenu.classList.remove('show');
            cityBtn.classList.remove('active');
        }
    };
}

if (citySearch) {
    citySearch.onkeyup = function() {
        let val = this.value.toLowerCase();
        document.querySelectorAll('.city-item').forEach(i => {
            i.style.display = i.innerText.toLowerCase().includes(val) ? "block" : "none";
        });
    };
}

// Load Cities based on District
if (document.getElementById('districtSelect')) {
    document.getElementById('districtSelect').onchange = async function() {
        const dId = this.value;
        const content = document.getElementById('cityContent');
        
        if(!dId) {
            content.innerHTML = '<span class="text-muted"><i class="fas fa-info-circle me-2"></i>Select a district first</span>';
            return;
        }
        
        content.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading cities...</span>';
        
        try {
            const res = await fetch(`get_cities.php?district_id=${dId}`);
            const cities = await res.json();

            if(cities.length === 0) {
                content.innerHTML = '<span class="text-muted"><i class="fas fa-exclamation-circle me-2"></i>No cities found</span>';
                return;
            }

            content.innerHTML = cities.map(c => `
                <div class="city-item">
                    <input type="checkbox" name="cities[]" value="${c.City}" class="city-check me-2" id="c_${c.id}">
                    <label for="c_${c.id}">${c.City}</label>
                </div>
            `).join('');

            // Add change handlers
            document.querySelectorAll('.city-check').forEach(box => {
                box.onchange = () => {
                    const count = document.querySelectorAll('.city-check:checked').length;
                    document.getElementById('cityLabel').innerText = count > 0 ? `${count} City Selected` : "Select cities...";
                    fetchJobs();
                };
            });
        } catch(error) {
            content.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading cities</span>';
            console.error('Error loading cities:', error);
        }

        fetchJobs();
    };
}

// AJAX Job Fetching
async function fetchJobs() {
    const loader = document.getElementById('loader');
    const desktopResults = document.getElementById('desktopResults');
    const mobileResults = document.getElementById('mobileResults');
    
    if (loader) loader.classList.remove('d-none');
    
    try {
        const searchForm = document.getElementById('searchForm');
        if (!searchForm) return;

        const formData = new FormData(searchForm);
        const params = new URLSearchParams(formData).toString();
        
        const res = await fetch('fetch_jobs.php?' + params);
        const rawText = await res.text();
        
        // Split the response using our custom marker
        const parts = rawText.split('###SPLIT###');
        
        if (parts.length === 2) {
            if (desktopResults) desktopResults.innerHTML = parts[0];
            if (mobileResults) mobileResults.innerHTML = parts[1];
            
            // Update the job count (count <tr> rows)
            const count = desktopResults ? desktopResults.querySelectorAll('tr').length : 0;
            const actualCount = desktopResults && desktopResults.innerText.includes("No Jobs Found") ? 0 : count;
            const countEl = document.getElementById('jobCount');
            if (countEl) countEl.textContent = actualCount;
        }
        
    } catch(error) {
        console.error('Fetch error:', error);
        if (desktopResults) desktopResults.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading data</td></tr>';
        if (mobileResults) mobileResults.innerHTML = '<div class="mobile-no-jobs"><i class="fas fa-exclamation-circle"></i><h4>Error Loading Jobs</h4><p>Please try again later</p></div>';
    } finally {
        if (loader) loader.classList.add('d-none');
    }
}

// Category Filter
function setupCategoryLinks() {
    const links = document.querySelectorAll('.cat-link');
    links.forEach(link => {
        link.onclick = function(e) {
            e.preventDefault();
            
            // Update active state
            const val = this.dataset.val;
            links.forEach(a => {
                if(a.dataset.val === val) a.classList.add('active');
                else a.classList.remove('active');
            });

            const hiddenCat = document.getElementById('hiddenCat');
            if (hiddenCat) hiddenCat.value = val;

            fetchJobs();

            // Close offcanvas if open (mobile)
            const offcanvasEl = document.getElementById('filterOffcanvas');
            if (offcanvasEl) {
                // Assuming bootstrap is loaded globally
                const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                if (bsOffcanvas) bsOffcanvas.hide();
            }
        };
    });
}
setupCategoryLinks();

// Search Form Submit
if (document.getElementById('searchForm')) {
    document.getElementById('searchForm').onsubmit = function(e) {
        e.preventDefault();
        fetchJobs();
    };
}

// Load jobs on page load
window.onload = fetchJobs;

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.view-job');
    if (btn) {
        e.preventDefault();
        const jobId = btn.getAttribute('data-id');
        if (jobId) {
            window.location.href = 'job_details.php?id=' + jobId;
        }
    }
});
</script>

<?php include 'layout/footer.php'; ?>
