<?php
session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // Fetch Payments with Employer Details and Ad Count
    $sql = "SELECT p.*, e.employer_name, e.employer_logo,
            (SELECT COUNT(*) FROM paid_advertising WHERE slip_link = p.id) as ad_count
            FROM payment_table p
            JOIN employer_profile e ON p.employer_link = e.id
            ORDER BY p.Approval ASC, p.id DESC";
    $payments = $pdo->query($sql)->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Control | TopVacancy Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --pro-blue: #4361ee; --pro-bg: #f8f9fc; }
        body { background-color: var(--pro-bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Modern Table Card */
        .glass-card { 
            background: white; 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        /* Status Styling */
        .status-pill {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .status-approved { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .status-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }

        /* Slip Thumbnail */
        .slip-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .slip-thumb:hover { transform: scale(1.1); }

        .employer-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #64748b;
        }

        /* Full-screen Lightbox */
        #slipLightbox {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.9);
            z-index: 9999;
            backdrop-filter: blur(8px);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Financials</li>
                </ol>
            </nav>
            <h2 class="fw-bold m-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Payment Verification</h2>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-dark border p-2 px-3 rounded-pill shadow-sm">
                <i class="fas fa-sync fa-spin text-primary me-2"></i>Live Billing Data
            </span>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center mb-4">
            <i class="fas fa-check-circle fs-4 me-3"></i>
            <div><?= htmlspecialchars($_GET['msg']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="glass-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom">
                    <tr>
                        <th class="ps-4 py-3 text-uppercase small fw-bold text-muted">Transaction</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted">Employer</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted">Amount</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted text-center">Reference</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted text-center">Status</th>
                        <th class="pe-4 py-3 text-uppercase small fw-bold text-muted text-end">Management</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-dark">#TXN-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            <div class="small text-muted"><?= date('M d, Y • h:i A', strtotime($p['payment_date'])) ?></div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="employer-avatar me-2">
                                    <?= strtoupper(substr($p['employer_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($p['employer_name']) ?></div>
                                    <div class="text-primary x-small fw-bold" style="font-size: 0.7rem;"><?= $p['ad_count'] ?> Active Slots</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">LKR <?= number_format($p['Totaled_received'], 2) ?></div>
                            <span class="x-small text-muted" style="font-size: 0.7rem;">Bank Transfer</span>
                        </td>
                        <td class="text-center">
                            <?php if($p['Payment_slip']): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($p['Payment_slip']) ?>" 
                                     class="slip-thumb" 
                                     onclick="openLightbox('<?= base64_encode($p['Payment_slip']) ?>')">
                            <?php else: ?>
                                <span class="badge bg-light text-muted fw-normal">No Slip</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($p['Approval'] == 1): ?>
                                <span class="status-pill status-approved">Verified</span>
                            <?php elseif($p['Approval'] == 2): ?>
                                <span class="status-pill status-rejected" title="<?= $p['rejection_reason'] ?>">Declined</span>
                            <?php else: ?>
                                <span class="status-pill status-pending">Action Required</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <?php if($p['Approval'] == 0): ?>
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                    <button class="btn btn-primary btn-sm px-3" onclick="processAction(<?= $p['id'] ?>, 'approve')">
                                        <i class="fas fa-check me-1"></i> Approve
                                    </button>
                                    <button class="btn btn-dark btn-sm px-3" onclick="processAction(<?= $p['id'] ?>, 'reject')">
                                        <i class="fas fa-times me-1"></i> Reject
                                    </button>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-light btn-sm rounded-pill disabled px-3">
                                    <i class="fas fa-lock me-1"></i> Finalized
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="actions/process_payment.php" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4">
                <h5 class="modal-title fw-bold">Decline Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <input type="hidden" name="payment_id" id="modal_payment_id">
                <input type="hidden" name="action" value="reject">
                
                <p class="text-muted small">Please specify why this payment is being rejected. This will be visible to the employer.</p>
                <div class="form-floating mb-3">
                    <textarea name="reason" class="form-control" placeholder="Reason" id="reasonArea" style="height: 120px" required></textarea>
                    <label for="reasonArea">Rejection Details</label>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<div id="slipLightbox">
    <div class="position-absolute top-0 end-0 p-4">
        <button class="btn btn-white btn-lg rounded-circle shadow" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
    </div>
    <div class="d-flex flex-column justify-content-center align-items-center h-100">
        <img id="fullSlipImg" src="" style="max-width: 85%; max-height: 85vh; border: 5px solid white; border-radius: 12px;">
        <p class="text-white mt-3 fw-bold"><i class="fas fa-search-plus me-2"></i>Receipt Inspection Mode</p>
    </div>
</div>

<form id="approveForm" action="actions/process_payment.php" method="POST" style="display:none;">
    <input type="hidden" name="payment_id" id="approve_id">
    <input type="hidden" name="action" value="approve">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function processAction(id, type) {
    if(type === 'approve') {
        if(confirm('PROCEED WITH APPROVAL?\nThis will activate all advertisements linked to this slip.')) {
            document.getElementById('approve_id').value = id;
            document.getElementById('approveForm').submit();
        }
    } else {
        document.getElementById('modal_payment_id').value = id;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }
}

function openLightbox(base64) {
    document.getElementById('fullSlipImg').src = "data:image/jpeg;base64," + base64;
    document.getElementById('slipLightbox').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Stop scrolling
}

function closeLightbox() {
    document.getElementById('slipLightbox').style.display = 'none';
    document.body.style.overflow = 'auto';
}
</script>
</body>
</html>