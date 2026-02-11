<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

try {
    // 1. Total Reach Metrics
    $total_subscribers = $pdo->query("SELECT COUNT(DISTINCT link_to_employee_profile) FROM employee_alerted_setting WHERE active = 1")->fetchColumn() ?: 0;
    
    // 2. High-Performance Stats Gathering
    $category_stats = $pdo->query("SELECT job_category, COUNT(*) as count 
                                   FROM employee_alerted_setting WHERE active = 1 
                                   GROUP BY job_category ORDER BY count DESC")->fetchAll();

    $district_stats = $pdo->query("SELECT district, COUNT(*) as count 
                                   FROM employee_alerted_setting WHERE active = 1 AND district != '' 
                                   GROUP BY district ORDER BY count DESC LIMIT 8")->fetchAll();

    // Prepare Data for Chart.js
    $chartLabels = [];
    $chartData = [];
    foreach(array_slice($category_stats, 0, 5) as $row) {
        $chartLabels[] = $row['job_category'] ?: 'Other';
        $chartData[] = $row['count'];
    }

} catch (PDOException $e) {
    die("Intelligence Engine Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reach Analytics | Pro Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --pro-blue: #0ea5e9; --pro-indigo: #6366f1; --pro-slate: #0f172a; }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: #1e293b; }
        
        /* Stats Dashboard Styling */
        .glass-card { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .hero-stat { background: linear-gradient(135deg, var(--pro-slate), #1e293b); color: white; border-radius: 24px; position: relative; overflow: hidden; }
        .hero-stat::after { content: ''; position: absolute; top: -20%; right: -10%; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%; }
        
        /* Reach Table */
        .district-row { transition: 0.2s; border-radius: 12px; margin-bottom: 8px; }
        .district-row:hover { background: #f8fafc; transform: scale(1.01); }
        
        .progress-slim { height: 6px; background: #f1f5f9; border-radius: 10px; }
        .indicator { width: 12px; height: 12px; border-radius: 3px; display: inline-block; margin-right: 8px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-7">
            <h1 class="fw-bold tracking-tight mb-1">Market Reach <span class="text-primary text-opacity-50">Insights</span></h1>
            <p class="text-muted lead">Real-time candidate distribution and job-alert density across the network.</p>
        </div>
        <div class="col-lg-5">
            <div class="hero-stat p-4 shadow-lg d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-white text-opacity-50 small text-uppercase fw-bold mb-1">Network Capacity</div>
                    <h2 class="display-5 fw-bold mb-0"><?= number_format($total_subscribers) ?></h2>
                    <span class="badge bg-success mt-2"><i class="fas fa-arrow-up me-1"></i> Active Candidates</span>
                </div>
                <i class="fas fa-users-rays fa-4x text-white opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card glass-card h-100 p-4">
                <h5 class="fw-bold mb-4">Top Growth Sectors</h5>
                <canvas id="categoryChart" class="mb-4"></canvas>
                <div class="mt-2">
                    <?php foreach (array_slice($category_stats, 0, 4) as $index => $stat): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="small fw-semibold">
                                <span class="indicator" style="background: <?= ['#6366f1', '#0ea5e9', '#f43f5e', '#10b981'][$index] ?>"></span>
                                <?= htmlspecialchars($stat['job_category']) ?>
                            </div>
                            <div class="small text-muted fw-bold"><?= round(($stat['count']/$total_subscribers)*100, 1) ?>%</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0">Sector Density</h5>
                    <button class="btn btn-sm btn-light rounded-pill px-3">View All</button>
                </div>
                <div class="pe-2" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($category_stats as $stat): 
                        $pct = ($total_subscribers > 0) ? ($stat['count'] / $total_subscribers) * 100 : 0;
                    ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small fw-bold text-dark"><?= htmlspecialchars($stat['job_category'] ?: 'Other') ?></span>
                            <span class="small text-muted"><?= number_format($stat['count']) ?></span>
                        </div>
                        <div class="progress progress-slim">
                            <div class="progress-bar bg-indigo" style="width: <?= $pct ?>%; background-color: var(--pro-indigo)"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-card h-100 p-4">
                <h5 class="fw-bold mb-4">Geographic Hotspots</h5>
                
                <div class="list-group list-group-flush mt-3">
                    <?php foreach ($district_stats as $stat): ?>
                    <div class="list-group-item bg-transparent border-0 px-0 district-row">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold small"><?= htmlspecialchars($stat['district']) ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;">Market Share: <?= round(($stat['count']/$total_subscribers)*100, 1) ?>%</div>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3"><?= $stat['count'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Sector Chart
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                data: <?= json_encode($chartData) ?>,
                backgroundColor: ['#6366f1', '#0ea5e9', '#f43f5e', '#10b981', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            cutout: '75%',
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>
</body>
</html>