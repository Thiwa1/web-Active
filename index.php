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
// SEO keywords for index page
$metaDesc = "Find your dream job or hire top talent on JobPortal. The leading job portal in Sri Lanka. Also offering Paper Advertising services for Sunday Lankadeepa and other classifieds.";

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
            <!-- UPDATED: Post CV now highlights Paper Ads indirectly via related category or just general prominence -->
            <div class="col-lg-2 col-md-4 col-6">
                <div class="p-3 border rounded-4 h-100 hover-shadow transition-all">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-newspaper fs-4"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Paper Ads</h6>
                    <p class="small text-muted mb-0">Sunday Lankadeepa & More</p>
                    <a href="paper_ads.php" class="stretched-link"></a>
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

<?php include 'layout/footer.php'; ?>
