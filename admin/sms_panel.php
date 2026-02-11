<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

try {
    // 1. Stats
    $total_active = $pdo->query("SELECT COUNT(*) FROM employee_alerted_setting WHERE active = 1")->fetchColumn();
    
    // Check if column exists logic would be good, but assuming DB update applied
    // We can try-catch the query
    try {
        $pending_alert = $pdo->query("SELECT COUNT(*) FROM employee_alerted_setting WHERE active = 1 AND (last_alert_sent IS NULL OR last_alert_sent < DATE_SUB(NOW(), INTERVAL 24 HOUR))")->fetchColumn();
    } catch (Exception $e) {
        $pending_alert = "N/A (DB Update Required)";
    }

    $todays_sent = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE DATE(sent_at) = CURDATE()")->fetchColumn();

    // 2. Recent Logs
    $logs = $pdo->query("
        SELECT l.*, u.full_name, a.Job_role 
        FROM sms_logs l 
        LEFT JOIN user_table u ON l.user_id = u.id 
        LEFT JOIN advertising_table a ON l.job_id = a.id 
        ORDER BY l.id DESC LIMIT 10
    ")->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SMS Command Center | Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .stat-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; }
        .control-panel { background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">SMS Campaign Manager</h2>
            <p class="text-muted">Target and notify job seekers based on their preferences.</p>
        </div>
        <a href="dashboard.php" class="btn btn-light rounded-pill px-4 fw-bold">Dashboard</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success rounded-4 mb-4 shadow-sm">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="text-muted small fw-bold text-uppercase">Active Subscribers</h6>
                <h3 class="fw-bold m-0 text-primary"><?= $total_active ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="text-muted small fw-bold text-uppercase">Pending (24h)</h6>
                <h3 class="fw-bold m-0 text-warning"><?= $pending_alert ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="text-muted small fw-bold text-uppercase">Sent Today</h6>
                <h3 class="fw-bold m-0 text-success"><?= $todays_sent ?></h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="control-panel h-100">
                <h5 class="fw-bold mb-4"><i class="fas fa-paper-plane me-2 text-primary"></i>Launch Campaign</h5>
                <form action="actions/send_alert_sms.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Sending Strategy</label>
                        <select name="strategy" class="form-select rounded-3 p-3 bg-light border-0">
                            <option value="smart">Smart Match (Based on Alert Settings)</option>
                            <!-- Future: <option value="all">Broadcast All (Caution)</option> -->
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Batch Limit</label>
                        <div class="range-wrap">
                            <input type="range" class="form-range" name="limit" min="10" max="500" step="10" value="50" oninput="this.nextElementSibling.value = this.value">
                            <output class="fw-bold text-primary">50</output> Employees
                        </div>
                        <div class="form-text small">Use smaller batches to prevent timeout.</div>
                    </div>

                    <div class="alert alert-info border-0 bg-info bg-opacity-10 small rounded-3">
                        <i class="fas fa-info-circle me-1"></i> System will select employees who haven't received alerts in the last 24 hours.
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow">
                        Start Sending Process
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white p-3 border-bottom">
                    <h6 class="fw-bold m-0">Transmission Logs</h6>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 small align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Recipient</th>
                                <th>Matched Job</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $l): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= htmlspecialchars($l['full_name']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['Job_role']) ?></span></td>
                                <td>
                                    <?php if($l['status'] == 'Sent'): ?>
                                        <span class="text-success"><i class="fas fa-check-circle me-1"></i>Sent</span>
                                    <?php else: ?>
                                        <span class="text-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end text-muted"><?= date('H:i', strtotime($l['sent_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
