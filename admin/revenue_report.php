<?php
session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // 1. Core Metrics
    $totalRev = $pdo->query("SELECT SUM(Totaled_received) FROM payment_table WHERE Approval = 1")->fetchColumn() ?: 0;
    
    $currentMonthRev = $pdo->query("SELECT SUM(Totaled_received) FROM payment_table 
                                    WHERE Approval = 1 
                                    AND MONTH(payment_date) = MONTH(CURRENT_DATE()) 
                                    AND YEAR(payment_date) = YEAR(CURRENT_DATE())")->fetchColumn() ?: 0;

    $pendingCount = $pdo->query("SELECT COUNT(*) FROM payment_table WHERE Approval = 0")->fetchColumn() ?: 0;

    // 2. Chart Data (Last 6 Months)
    $monthlyQuery = "SELECT 
                        DATE_FORMAT(payment_date, '%b') as m_label, 
                        SUM(Totaled_received) as total 
                     FROM payment_table 
                     WHERE Approval = 1 AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY YEAR(payment_date), MONTH(payment_date) 
                     ORDER BY payment_date ASC";
    $chartData = $pdo->query($monthlyQuery)->fetchAll();
    
    $labels = json_encode(array_column($chartData, 'm_label'));
    $values = json_encode(array_column($chartData, 'total'));

    // 3. Top Spending Employers
    $topEmployers = $pdo->query("SELECT e.employer_name, SUM(p.Totaled_received) as total_spent, COUNT(p.id) as payment_count
                                 FROM payment_table p
                                 JOIN employer_profile e ON p.employer_link = e.id
                                 WHERE p.Approval = 1
                                 GROUP BY e.id ORDER BY total_spent DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    die("Data Integrity Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Command | TopVacancy Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --pro-blue: #4361ee; --pro-success: #2ec4b6; --pro-dark: #1e293b; }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Dashboard Cards */
        .stat-card { 
            background: white; border: none; border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.04); transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        
        .icon-box {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }

        /* Gradient Hero */
        .hero-gradient {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 24px; padding: 40px; color: white; margin-bottom: 30px;
        }

        .table-pro thead th {
            background: transparent; text-transform: uppercase; font-size: 0.7rem;
            font-weight: 700; color: #94a3b8; border: none;
        }
        
        .employer-rank {
            width: 24px; height: 24px; background: #f1f5f9; border-radius: 6px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: bold; margin-right: 10px;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0 text-dark">Financial Intelligence</h2>
            <p class="text-muted small">Real-time revenue tracking and employer performance analytics.</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-white border shadow-sm rounded-pill px-4 dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-download me-2"></i>Export Report
            </button>
            <ul class="dropdown-menu border-0 shadow">
                <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2 text-danger"></i>Export PDF</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-file-csv me-2 text-success"></i>Export CSV</a></li>
            </ul>
        </div>
    </div>

    <div class="hero-gradient shadow-lg">
        <div class="row align-items-center">
            <div class="col-md-6 border-end border-secondary">
                <span class="badge bg-primary mb-3 px-3 py-2 rounded-pill">Total Net Revenue</span>
                <h1 class="display-4 fw-bold mb-0">LKR <?= number_format($totalRev, 2) ?></h1>
                <p class="text-light opacity-50 small mt-2"><i class="fas fa-shield-check me-1"></i> Audited and Verified Funds</p>
            </div>
            <div class="col-md-6 ps-md-5">
                <div class="row">
                    <div class="col-6">
                        <small class="text-uppercase opacity-50 fw-bold small">This Month</small>
                        <h3 class="fw-bold">Rs. <?= number_format($currentMonthRev, 2) ?></h3>
                    </div>
                    <div class="col-6">
                        <small class="text-uppercase opacity-50 fw-bold small">Pending Review</small>
                        <h3 class="fw-bold text-warning"><?= $pendingCount ?> Slips</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="stat-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0">Revenue Velocity</h5>
                    <span class="text-success small fw-bold"><i class="fas fa-arrow-up"></i> Last 6 Months</span>
                </div>
                <canvas id="revenueChart" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="stat-card p-4 h-100">
                <h5 class="fw-bold mb-4">Top Recruiters</h5>
                <?php $rank = 1; foreach ($topEmployers as $te): ?>
                <div class="d-flex align-items-center mb-3 p-2 rounded-3 hover-bg-light">
                    <div class="employer-rank"><?= $rank++ ?></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold small"><?= htmlspecialchars($te['employer_name']) ?></div>
                        <div class="text-muted" style="font-size: 0.7rem;"><?= $te['payment_count'] ?> Total Transactions</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary small">Rs. <?= number_format($te['total_spent'], 0) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <a href="manage_employers.php" class="btn btn-light w-100 mt-3 btn-sm fw-bold">View All Partners</a>
            </div>
        </div>
    </div>
</div>



<script>
// Chart.js Professional Configuration
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= $labels ?>,
        datasets: [{
            label: 'Revenue (LKR)',
            data: <?= $values ?>,
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67, 97, 238, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#4361ee'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { display: false } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>