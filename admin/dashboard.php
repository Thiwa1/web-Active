<?php
session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // Analytics Queries
    $pendingRecruiters = $pdo->query("SELECT COUNT(*) FROM employer_profile WHERE employer_Verified = 0")->fetchColumn();
    $pendingPayments = $pdo->query("SELECT COUNT(*) FROM payment_table WHERE Approval = 0")->fetchColumn();
    $activeJobs = $pdo->query("SELECT COUNT(*) FROM advertising_table WHERE Approved = 1")->fetchColumn();
    
    // Revenue logic (Current Month vs Last Month for growth indicator)
    $revenueStmt = $pdo->query("SELECT SUM(Totaled_received) FROM payment_table WHERE Approval = 1 AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
    $monthlyRevenue = $revenueStmt->fetchColumn() ?: 0;

    $currentPrice = $pdo->query("SELECT * FROM Price_setting LIMIT 1")->fetch();

    // Check Migration Status for Promo System
    $migrationNeeded = false;
    try {
        $pdo->query("SELECT 1 FROM external_ads LIMIT 1");
    } catch (Exception $e) {
        $migrationNeeded = true;
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    $pendingRecruiters = $pendingPayments = $activeJobs = $monthlyRevenue = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProAdmin | Analytics Dashboard</title>
    <link rel="icon" href="../uploads/system/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --sidebar-width: 260px;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --primary-accent: #4361ee;
            --bg-color: #f8f9fc;
        }

        body { background-color: var(--bg-color); font-family: 'Inter', system-ui, -apple-system, sans-serif; overflow-x: hidden; }

        /* Professional Sidebar */
        .sidebar { 
            width: var(--sidebar-width); 
            height: 100vh; 
            background: #ffffff; 
            position: fixed; 
            border-right: 1px solid #e3e6f0;
            padding: 1.5rem 1rem;
            transition: all 0.3s;
        }

        .sidebar .nav-link {
            color: #6e707e;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            margin-bottom: 0.3rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link i { font-size: 1.1rem; width: 25px; margin-right: 10px; }
        .sidebar .nav-link:hover { background: #f1f4ff; color: var(--primary-accent); }
        .sidebar .nav-link.active { background: var(--primary-accent); color: white; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3); }

        /* Main Content Area */
        .main-wrapper { margin-left: var(--sidebar-width); padding: 2rem; transition: all 0.3s; }

        /* Pro Card Styling */
        .card-pro {
            background: white;
            border: none;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            position: relative;
            overflow: hidden;
        }

        .card-pro::after {
            content: '';
            position: absolute;
            top: 0; right: 0; width: 4px; height: 100%;
        }
        .border-left-primary::after { background: var(--primary-accent); }
        .border-left-success::after { background: #1cc88a; }
        .border-left-warning::after { background: #f6c23e; }

        .stat-value { font-size: 1.8rem; font-weight: 700; color: #4e73df; }
        .stat-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05rem; color: #858796; margin-bottom: 0.5rem; }

        /* Quick Action Buttons */
        .btn-action {
            background: white;
            border: 1px solid #e3e6f0;
            padding: 1rem;
            border-radius: 12px;
            text-align: left;
            transition: all 0.2s;
            width: 100%;
        }
        .btn-action:hover { border-color: var(--primary-accent); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="d-flex align-items-center mb-4 px-3">
        <div class="bg-primary text-white p-2 rounded-3 me-2">
            <i class="fas fa-rocket"></i>
        </div>
        <h5 class="m-0 fw-bold">TopVacancy</h5>
    </div>
    
    <nav class="nav flex-column mt-4">
        <a class="nav-link active" href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a class="nav-link" href="approve_payments.php"><i class="fas fa-credit-card"></i> Payments 
            <?php if($pendingPayments > 0): ?><span class="badge bg-warning text-dark ms-auto"><?= $pendingPayments ?></span><?php endif; ?>
        </a>
        <a class="nav-link" href="bank_accounts.php"><i class="fas fa-university"></i> Bank Accounts</a>
        <a class="nav-link" href="verify_recruiters.php"><i class="fas fa-user-check"></i> Verification 
             <?php if($pendingRecruiters > 0): ?><span class="badge bg-danger ms-auto"><?= $pendingRecruiters ?></span><?php endif; ?>
        </a>
        <a class="nav-link" href="manage_jobs.php"><i class="fas fa-list-ul"></i> Manage Jobs</a>
        <a class="nav-link" href="manage_paper_ads.php"><i class="fas fa-newspaper"></i> Paper Ads</a>
        <!-- NEW: Manage Staff -->
        <a class="nav-link" href="manage_staff.php"><i class="fas fa-users-cog"></i> Staff</a>

        <a class="nav-link" href="external_ads.php"><i class="fas fa-bullhorn"></i> Promo Manager</a>
        <a class="nav-link" href="sms_panel.php"><i class="fas fa-comment-sms"></i> SMS Manager</a>
        <div class="my-4 border-top"></div>
        <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> Site Settings</a>
        <a class="nav-link text-danger mt-5" href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<div class="main-wrapper">
    <?php if($migrationNeeded): ?>
    <div class="alert alert-danger mb-4 shadow-sm border-0 rounded-4">
        <div class="d-flex align-items-center">
            <div class="bg-danger text-white rounded-circle p-3 me-3"><i class="fas fa-database fa-lg"></i></div>
            <div>
                <h5 class="alert-heading fw-bold mb-1">Database Update Required</h5>
                <p class="mb-0">The Promo System requires new tables to function. <a href="setup_promo_tables.php" class="btn btn-sm btn-light fw-bold ms-2 text-danger">Run Setup Script</a></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">System Insights</h3>
            <p class="text-muted">Real-time platform performance overview.</p>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <span class="text-muted small"><i class="fas fa-circle text-success me-1"></i> DB Online</span>
            <img src="https://ui-avatars.com/api/?name=Admin&background=4361ee&color=fff" class="rounded-circle" width="45">
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card-pro h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="m-0 fw-bold text-dark">Revenue Forecast</h6>
                    <button class="btn btn-sm btn-light border">Download PDF</button>
                </div>
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-pro h-100 bg-primary text-white border-0">
                <h6 class="stat-label text-white opacity-75">Pricing Model</h6>
                <div class="mt-4">
                    <h1 class="fw-bold">Rs. <?= number_format($currentPrice['selling_price'], 0); ?></h1>
                    <p class="opacity-75">Base Rate per <?= $currentPrice['Unit_of_add']; ?> Job Postings</p>
                </div>
                <div class="mt-auto pt-5">
                    <button class="btn btn-white w-100 fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#priceModal">Change Pricing</button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card-pro border-left-primary h-100">
                <div class="stat-label">Total Active Vacancies</div>
                <div class="d-flex align-items-center">
                    <div class="stat-value"><?= $activeJobs ?></div>
                    <div class="ms-auto text-primary bg-primary bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-briefcase fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pro border-left-success h-100">
                <div class="stat-label">Monthly Revenue (LKR)</div>
                <div class="d-flex align-items-center">
                    <div class="stat-value">Rs. <?= number_format($monthlyRevenue, 0) ?></div>
                    <div class="ms-auto text-success bg-success bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-coins fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pro border-left-warning h-100">
                <div class="stat-label">Action Required</div>
                <div class="d-flex align-items-center">
                    <div class="stat-value"><?= $pendingPayments + $pendingRecruiters ?></div>
                    <div class="ms-auto text-warning bg-warning bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-bell fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-3 mt-5">Quick Operations</h5>
    <div class="row g-3">
        <div class="col-md-3">
            <a href="manage_jobs.php" class="btn-action text-decoration-none d-block">
                <i class="fas fa-search-plus text-primary mb-2 d-block fa-lg"></i>
                <span class="fw-bold text-dark d-block">Audit Jobs</span>
                <span class="text-muted small">Review active content</span>
            </a>
        </div>
        <div class="col-md-3">
            <a href="verify_recruiters.php" class="btn-action text-decoration-none d-block">
                <i class="fas fa-id-card text-danger mb-2 d-block fa-lg"></i>
                <span class="fw-bold text-dark d-block">Verify KYC</span>
                <span class="text-muted small"><?= $pendingRecruiters ?> pending docs</span>
            </a>
        </div>
        <div class="col-md-3">
            <a href="approve_payments.php" class="btn-action text-decoration-none d-block">
                <i class="fas fa-file-invoice-dollar text-success mb-2 d-block fa-lg"></i>
                <span class="fw-bold text-dark d-block">Billing Review</span>
                <span class="text-muted small"><?= $pendingPayments ?> new receipts</span>
            </a>
        </div>
        <div class="col-md-3">
            <a href="sms_panel.php" class="btn-action text-decoration-none d-block">
                <i class="fas fa-paper-plane text-warning mb-2 d-block fa-lg"></i>
                <span class="fw-bold text-dark d-block">SMS Campaign</span>
                <span class="text-muted small">Notify Employees</span>
            </a>
        </div>
    </div>
</div>

<div class="modal fade" id="priceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="../actions/update_settings.php" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-adjust me-2 text-warning"></i>Adjust Pricing Model</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action_type" value="update_price">
                <input type="hidden" name="price_id" value="<?= $currentPrice['id']; ?>">
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase">Selling Price (LKR)</label>
                    <input type="number" step="0.01" name="selling_price" class="form-control form-control-lg bg-light" value="<?= $currentPrice['selling_price']; ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold text-uppercase">Ad Slot Quantity</label>
                    <input type="number" name="Unit_of_add" class="form-control form-control-lg bg-light" value="<?= $currentPrice['Unit_of_add']; ?>" required>
                </div>
            </div>
            <div class="modal-footer p-4 border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5">Deploy Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Revenue Chart Logic
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Monthly Growth',
                data: [12, 19, 15, <?= $monthlyRevenue / 1000 ?>], // Example data
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { display: false }, x: { grid: { display: false } } }
        }
    });
</script>
<?php include '../layout/ui_helpers.php'; ?>
</body>
</html>