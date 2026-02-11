<?php
session_start();
require_once '../config/config.php';

// 1. Security & Identity
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get employer identity
    $stmt = $pdo->prepare("SELECT id, employer_name FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch();
    $emp_id = $emp['id'];

    // 2. Fetch Payment History with subqueries for ad counts
$sql = "SELECT p.id, p.Totaled_received, p.Payment_slip, p.Approval, 
               p.payment_date, p.Reject_comment,
        (SELECT COUNT(*) FROM paid_advertising WHERE slip_link = p.id) as total_ads
        FROM payment_table p 
        WHERE p.employer_link = ? 
        ORDER BY p.payment_date DESC";
$stmtPay = $pdo->prepare($sql);
$stmtPay->execute([$emp_id]);
$payments = $stmtPay->fetchAll();
    // 3. Stats Calculation for the Dashboard
    $total_invested = 0;
    $pending_verification = 0;
    foreach($payments as $pay) {
        if($pay['Approval'] == 1) $total_invested += $pay['Totaled_received'];
        if($pay['Approval'] == 0) $pending_verification++;
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Invoices | Pro Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --brand-primary: #4f46e5;
            --brand-success: #10b981;
            --brand-warning: #f59e0b;
            --brand-danger: #ef4444;
            --bg-body: #f8fafc;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }

        /* Stats Cards */
        .bento-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            height: 100%;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        /* Table Styling */
        .billing-container {
            background: white;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04);
        }

        .table thead th {
            background: #f8fafc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 700;
            padding: 20px 25px;
            color: #64748b;
            border: none;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-verified { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }

        .transaction-id {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .btn-view-slip {
            background: #f1f5f9;
            color: #1e293b;
            border: none;
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .btn-view-slip:hover { background: var(--brand-primary); color: white; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="manage_jobs.php" class="text-decoration-none text-muted small">Dashboard</a></li>
                    <li class="breadcrumb-item active small" aria-current="page">Billing History</li>
                </ol>
            </nav>
            <h2 class="fw-800 mb-0">Billing & Invoices</h2>
            <p class="text-muted mb-0">Track your advertising spend and verification status.</p>
        </div>
        <button class="btn btn-dark rounded-pill px-4 fw-600" onclick="window.print()">
            <i class="fas fa-download me-2"></i> Export Report
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="bento-card">
                <div class="icon-box bg-primary-subtle text-primary"><i class="fas fa-wallet fa-lg"></i></div>
                <div class="text-muted small fw-600">Total Ad Spend</div>
                <div class="h3 fw-800 mb-0"><?= number_format($total_invested, 2) ?> <span class="fs-6">LKR</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bento-card">
                <div class="icon-box bg-warning-subtle text-warning"><i class="fas fa-hourglass-half fa-lg"></i></div>
                <div class="text-muted small fw-600">Pending Verification</div>
                <div class="h3 fw-800 mb-0"><?= $pending_verification ?> <span class="fs-6">Payments</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bento-card">
                <div class="icon-box bg-success-subtle text-success"><i class="fas fa-ad fa-lg"></i></div>
                <div class="text-muted small fw-600">Lifetime Ads Published</div>
                <div class="h3 fw-800 mb-0"><?= array_sum(array_column($payments, 'total_ads')) ?> <span class="fs-6">Units</span></div>
            </div>
        </div>
    </div>

    <div class="billing-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Date & ID</th>
                        <th>Amount</th>
                        <th>Allocated Ads</th>
                        <th>Status</th>
                        <th>Remark</th>
                        <th class="text-end pe-4">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="ps-4 py-4">
                                    <div class="fw-700 text-dark"><?= date('d M, Y', strtotime($p['payment_date'])) ?></div>
                                    <div class="transaction-id mt-1 small">PAY-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                </td>
                                <td>
                                    <div class="fw-800 text-dark"><?= number_format($p['Totaled_received'], 2) ?></div>
                                    <div class="text-muted extra-small">LKR (Bank Transfer)</div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-dark fw-700 border px-3"><?= $p['total_ads'] ?> Positions</span>
                                </td>
                                <td>
                                    <?php if ($p['Approval'] == 1): ?>
                                        <span class="status-badge badge-verified"><i class="fas fa-check-circle"></i> Verified</span>
                                    <?php elseif ($p['Approval'] == 2): ?>
                                        <span class="status-badge badge-rejected"><i class="fas fa-times-circle"></i> Rejected</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-pending"><i class="fas fa-clock"></i> In Review</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 200px;">
    <?php if ($p['Approval'] == 2): ?>
        <div class="text-danger small fw-700">
            <i class="fas fa-info-circle me-1"></i> 
            <?= htmlspecialchars($p['Reject_comment'] ?? 'No reason provided') ?>
        </div>
    <?php elseif ($p['Approval'] == 1): ?>
        <div class="text-muted small">Processed on <?= date('d M', strtotime($p['payment_date'])) ?></div>
    <?php else: ?>
        <div class="text-muted small italic">Bank clearance in progress</div>
    <?php endif; ?>
</td>
<td class="text-end pe-4">
    <button class="btn btn-view-slip" onclick="viewSlip('<?= base64_encode($p['Payment_slip']) ?>')">
        <i class="fas fa-eye me-1"></i> View Slip
    </button>
</td>
                                
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 fw-600 text-muted">No transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="slipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-800 mb-0">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="slipImg" src="" class="img-fluid rounded-3 shadow-sm border" alt="Payment Slip">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function viewSlip(base64Data) {
        if(!base64Data) return alert('No slip image found.');
        document.getElementById('slipImg').src = 'data:image/jpeg;base64,' + base64Data;
        new bootstrap.Modal(document.getElementById('slipModal')).show();
    }
</script>
</body>
</html>