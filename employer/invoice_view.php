<?php
session_start();
require_once '../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header("Location: ../login.php"); exit();
}

$payment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$payment_id) { die("Invalid Invoice ID"); }

try {
    // 2. Fetch Payment & Employer Details securely
    $sql = "SELECT p.*, e.employer_name, e.employer_address_1, e.employer_address_2, e.employer_mobile_no
            FROM payment_table p
            JOIN employer_profile e ON p.employer_link = e.id
            WHERE p.id = ? AND e.link_to_user = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$payment_id, $user_id]);
    $inv = $stmt->fetch();

    if (!$inv) { die("Invoice not found or access denied."); }

    // 3. Fetch System/Company Details
    $sys = $pdo->query("SELECT * FROM Compan_details LIMIT 1")->fetch();

} catch (PDOException $e) { die("System Error"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #INV-<?= str_pad($inv['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f3f4f6; padding: 40px 0; font-family: 'Inter', sans-serif; }
        .invoice-card { background: white; border-radius: 0; padding: 60px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 800px; margin: 0 auto; }
        .invoice-title { font-weight: 800; color: #1e293b; letter-spacing: -1px; }
        .meta-label { font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 5px; }
        .meta-val { font-weight: 600; color: #334155; }
        .table-invoice th { background: #f8fafc; text-transform: uppercase; font-size: 0.75rem; padding: 15px; border-bottom: 2px solid #e2e8f0; }
        .table-invoice td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .total-box { background: #f8fafc; padding: 20px; border-radius: 10px; }

        @media print {
            body { background: white; padding: 0; }
            .invoice-card { box-shadow: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="invoice-card">
        <div class="d-flex justify-content-between align-items-start mb-5">
            <div>
                <?php if(!empty($sys['logo_path'])): ?>
                    <img src="../<?= htmlspecialchars($sys['logo_path']) ?>" style="height: 60px; margin-bottom: 15px;">
                <?php else: ?>
                    <h2 class="invoice-title text-primary"><?= htmlspecialchars($sys['company_name'] ?? 'JobPortal') ?></h2>
                <?php endif; ?>
                <div class="small text-muted" style="line-height: 1.6;">
                    <?= htmlspecialchars($sys['addres1'] ?? '') ?><br>
                    <?= htmlspecialchars($sys['addres2'] ?? '') ?><br>
                    <?= htmlspecialchars($sys['TP_No'] ?? '') ?>
                </div>
            </div>
            <div class="text-end">
                <h1 class="invoice-title mb-1">INVOICE</h1>
                <div class="text-muted mb-4">#INV-<?= str_pad($inv['id'], 6, '0', STR_PAD_LEFT) ?></div>

                <?php if($inv['Approval'] == 1): ?>
                    <div class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill border border-success">
                        <i class="fas fa-check-circle me-1"></i> PAID
                    </div>
                <?php else: ?>
                     <div class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill border border-warning">
                        <i class="fas fa-clock me-1"></i> PENDING
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-6">
                <div class="meta-label">Bill To</div>
                <div class="meta-val fs-5 mb-1"><?= htmlspecialchars($inv['employer_name']) ?></div>
                <div class="text-muted small">
                    <?= htmlspecialchars($inv['employer_address_1'] ?? '') ?><br>
                    <?= htmlspecialchars($inv['employer_address_2'] ?? '') ?><br>
                    <?= htmlspecialchars($inv['employer_mobile_no'] ?? '') ?>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="meta-label">Invoice Date</div>
                        <div class="meta-val"><?= date('M d, Y', strtotime($inv['payment_date'])) ?></div>
                    </div>
                    <?php if($inv['Approval_date']): ?>
                    <div class="col-12">
                        <div class="meta-label">Approval Date</div>
                        <div class="meta-val"><?= date('M d, Y', strtotime($inv['Approval_date'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <table class="table table-invoice mb-4">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="text-center">Rate</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="fw-bold text-dark">Job Advertisement Package</div>
                        <div class="small text-muted">Standard vacancy listing credit</div>
                    </td>
                    <td class="text-center">See Plan</td>
                    <td class="text-center">1</td>
                    <td class="text-end fw-bold"><?= number_format($inv['Totaled_received'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="row justify-content-end">
            <div class="col-md-5">
                <div class="total-box">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span class="fw-bold"><?= number_format($inv['Totaled_received'], 2) ?></span>
                    </div>
                    <?php if($inv['VAT_enable']): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">VAT:</span>
                        <span class="fw-bold text-danger">+ 0.00</span>
                        <!-- VAT Logic specific to your system, assuming inclusive or 0 for now -->
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between border-top pt-3 mt-2">
                        <span class="fs-5 fw-bold text-dark">Total:</span>
                        <span class="fs-5 fw-bold text-primary">LKR <?= number_format($inv['Totaled_received'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 pt-4 border-top text-center text-muted small">
            <p class="mb-1">Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact <?= htmlspecialchars($sys['TP_No'] ?? 'Support') ?></p>
        </div>
    </div>

    <div class="text-center mt-4 no-print gap-2 d-flex justify-content-center">
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
            <i class="fas fa-print me-2"></i> Print / Save PDF
        </button>
        <button onclick="window.close()" class="btn btn-light rounded-pill px-4 fw-bold border">
            Close
        </button>
    </div>
</div>

</body>
</html>
