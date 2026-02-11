<?php
session_start();
require_once '../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    // Check for AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        die("Session Expired");
    }
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Direct Access Prevention: Redirect to Dashboard if not AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $queryParams = $_GET;
    $queryString = http_build_query($queryParams);
    $redirectUrl = 'dashboard.php?page=billing' . ($queryString ? '&' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit();
}

try {
    // 2. Fetch Branding & Employer Profile
    $stmtSite = $pdo->query("SELECT company_name FROM Compan_details LIMIT 1");
    $site_name = $stmtSite->fetchColumn() ?? "JobQuest Pro";

    $stmtEmp = $pdo->prepare("SELECT id, employer_name FROM employer_profile WHERE link_to_user = ?");
    $stmtEmp->execute([$user_id]);
    $emp = $stmtEmp->fetch();

    if (!$emp) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             die('<div class="alert alert-warning">Please complete your profile to view this section.</div>');
        }
        header("Location: profile.php?msg=complete_profile");
        exit();
    }

    $emp_id = $emp['id'];

    // 3. Financial Summary Logic
    $stmtSum = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN Approval = 1 THEN Totaled_received ELSE 0 END) as total_spent,
            COUNT(CASE WHEN Approval = 0 THEN 1 END) as pending_count
        FROM payment_table WHERE employer_link = ?
    ");
    $stmtSum->execute([$emp_id]);
    $summary = $stmtSum->fetch();

    // 4. Fetch Detailed Payment History
    $stmtPayments = $pdo->prepare("SELECT * FROM payment_table WHERE employer_link = ? ORDER BY id DESC");
    $stmtPayments->execute([$emp_id]);
    $payments = $stmtPayments->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("System Error: Unable to retrieve financial records.");
}
?>

<div class="container py-5">
    <div class="billing-header" style="background: linear-gradient(135deg, #1e293b, #334155); border-radius: 24px; padding: 40px; color: white; margin-bottom: 30px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <a href="#" onclick="loadContent('home'); return false;" class="back-link small mb-2 d-inline-block text-white-50 text-decoration-none">
                    <i class="fas fa-chevron-left me-1"></i> Dashboard
                </a>
                <h2 class="fw-800 mb-1">Billing & Invoices</h2>
                <p class="text-white-50 mb-0">Manage your subscription and track advertising spend.</p>
            </div>
            <i class="fas fa-wallet fa-3x opacity-25"></i>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="bento-mini" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 20px;">
                    <div class="small text-white-50 mb-1">Total Investment</div>
                    <div class="h3 fw-bold mb-0">LKR <?= number_format($summary['total_spent'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bento-mini" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 20px;">
                    <div class="small text-white-50 mb-1">Pending Verifications</div>
                    <div class="h3 fw-bold mb-0"><?= $summary['pending_count'] ?> <span class="fs-6 fw-normal text-white-50">Slips</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-card bg-white rounded-4 border shadow-sm overflow-hidden">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white">
            <h5 class="fw-bold mb-0">Transaction History</h5>
            <button class="btn btn-light btn-sm rounded-3 border">
                <i class="fas fa-download me-2"></i> Export CSV
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3 text-uppercase small fw-bold">Transaction ID</th>
                        <th class="py-3 text-uppercase small fw-bold">Billing Date</th>
                        <th class="py-3 text-uppercase small fw-bold">Amount</th>
                        <th class="py-3 text-uppercase small fw-bold">Slip Status</th>
                        <th class="py-3 text-uppercase small fw-bold">System Status</th>
                        <th class="py-3 text-uppercase small fw-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-700">#PAY-<?= $p['id'] ?></div>
                                    <div class="text-muted extra-small" style="font-size: 0.7rem;">REF: <?= md5($p['id']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-600 text-dark"><?= $p['payment_date'] ? date('d M, Y', strtotime($p['payment_date'])) : '---' ?></div>
                                    <div class="text-muted small">Electronic Transfer</div>
                                </td>
                                <td>
                                    <div class="fw-800 text-dark">LKR <?= number_format($p['Totaled_received'], 2) ?></div>
                                    <div class="extra-small text-muted" style="font-size: 0.7rem;">Incl. Taxes</div>
                                </td>
                                <td>
                                    <?php if(!empty($p['Payment_slip']) || !empty($p['slip_path'])): ?>
                                        <div class="text-success small fw-600"><i class="fas fa-check-double me-1"></i> Attached</div>
                                    <?php else: ?>
                                        <div class="text-danger small fw-600"><i class="fas fa-circle-exclamation me-1"></i> Missing</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['Approval'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2"><i class="fas fa-circle fa-xs me-1"></i> Approved</span>
                                    <?php elseif ($p['Approval'] == 2): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2"><i class="fas fa-circle fa-xs me-1"></i> Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2"><i class="fas fa-circle fa-xs me-1"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <?php if($p['Approval'] == 2): ?>
                                            <a href="reupload_payment.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-flex align-items-center justify-content-center" style="width:32px;height:32px;" title="Fix Issue">
                                                <i class="fas fa-sync-alt"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php
                                            $slipUrl = '';
                                            if (!empty($p['slip_path'])) {
                                                $slipUrl = '../' . htmlspecialchars($p['slip_path']);
                                            } elseif (!empty($p['Payment_slip'])) {
                                                $slipUrl = 'data:image/jpeg;base64,' . base64_encode($p['Payment_slip']);
                                            }
                                        ?>
                                        <?php if($slipUrl): ?>
                                            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center" style="width:32px;height:32px;" title="View Slip" onclick="viewSlip('<?= $slipUrl ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-light text-secondary btn-sm rounded-3 d-flex align-items-center justify-content-center" style="width:32px;height:32px;" disabled>
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if($p['Approval'] == 1): ?>
                                            <a href="invoice_view.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-3 d-flex align-items-center justify-content-center" style="width:32px;height:32px;" title="Download Invoice">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-file-invoice-dollar fa-3x text-muted opacity-25 mb-3"></i>
                                <p class="text-muted fw-500">No payment history available yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Removed redundant HTML wrapper and script includes for AJAX load compatibility -->
