<?php
session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // Audit Metrics
    $pendingTotal = $pdo->query("SELECT SUM(Totaled_received) FROM payment_table WHERE Approval = 0")->fetchColumn() ?: 0;
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM payment_table WHERE Approval = 0")->fetchColumn() ?: 0;

    // Fetch Payments with Ad Detail Strings (MySQL GROUP_CONCAT for Pro performance)
$sql = "SELECT p.*, e.employer_name, u.user_email as emp_email,
        GROUP_CONCAT(a.Job_role SEPARATOR '||') as ad_titles,
        COUNT(pa.id) as ad_count
        FROM payment_table p
        JOIN employer_profile e ON p.employer_link = e.id
        JOIN user_table u ON e.link_to_user = u.id  -- Added this JOIN to get email
        LEFT JOIN paid_advertising pa ON pa.slip_link = p.id
        LEFT JOIN advertising_table a ON pa.add_link = a.id
        GROUP BY p.id
        ORDER BY p.Approval ASC, p.id DESC";
    $payments = $pdo->query($sql)->fetchAll();

} catch (PDOException $e) {
    die("Data Integrity Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Audit | Pro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --pro-success: #10b981; --pro-warning: #f59e0b; --pro-danger: #ef4444; }
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #1e293b; }
        
        /* Glassmorphism Header */
        .audit-summary { background: #1e293b; color: white; border-radius: 20px; padding: 30px; margin-bottom: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        /* Pro Table */
        .card-table { background: white; border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; }
        .table thead th { background: #f1f5f9; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; padding: 15px 20px; border: none; }
        
        /* Slip Interaction */
        .slip-container { position: relative; width: 60px; height: 60px; cursor: pointer; }
        .slip-thumb { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; border: 2px solid #e2e8f0; transition: 0.3s; }
        .slip-container:hover .slip-thumb { border-color: #4361ee; transform: scale(1.05); }
        .zoom-overlay { position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background: rgba(0,0,0,0.3); color:white; border-radius:12px; opacity:0; transition:0.3s; }
        .slip-container:hover .zoom-overlay { opacity: 1; }

        /* Badge Logic */
        .badge-pro { padding: 6px 12px; border-radius: 8px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; }
        .bg-pending { background: #fef3c7; color: #92400e; }
        .bg-approved { background: #dcfce7; color: #166534; }
        
        /* UI Components */
        .search-bar { border-radius: 50px; padding: 12px 25px; border: 1px solid #e2e8f0; background: white; margin-bottom: 25px; width: 100%; max-width: 400px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="audit-summary d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Payment Queue</h2>
            <p class="text-white text-opacity-50 m-0 small">Verification required for active service delivery.</p>
        </div>
        <div class="d-flex gap-4 text-end">
            <div>
                <div class="text-white text-opacity-50 small text-uppercase fw-bold">Pending Amount</div>
                <h3 class="fw-bold m-0 text-warning">LKR <?= number_format($pendingTotal, 2) ?></h3>
            </div>
            <div class="border-start border-secondary ps-4">
                <div class="text-white text-opacity-50 small text-uppercase fw-bold">Queue Size</div>
                <h3 class="fw-bold m-0"><?= $pendingCount ?> Slips</h3>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <input type="text" class="search-bar shadow-sm" id="proSearch" placeholder="Filter by Employer or Amount...">
        <div class="btn-group">
            <a href="dashboard.php" class="btn btn-white border px-4 rounded-pill fw-bold small shadow-sm">Dashboard</a>
            <button onclick="location.reload()" class="btn btn-white border px-3 rounded-pill ms-2 shadow-sm"><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>

    <div class="card card-table">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="paymentTable">
                <thead>
                    <tr>
                        <th class="ps-4">Transaction Source</th>
                        <th>Settlement</th>
                        <th>Coverage</th>
                        <th>Evidence</th>
                        <th>Audit Status</th>
                        <th class="text-end pe-4">Manual Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): 
                        $ads = explode('||', $p['ad_titles']);
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($p['employer_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($p['emp_email']) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">LKR <?= number_format($p['Totaled_received'], 2) ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;"><?= date('M d, Y • h:i A', strtotime($p['payment_date'])) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.7rem; cursor:help" title="<?= implode(', ', $ads) ?>">
                                <i class="fas fa-layer-group me-1"></i> <?= $p['ad_count'] ?> Ads Covered
                            </span>
                        </td>
                        <td>
                            <?php if($p['Payment_slip']): ?>
                                <div class="slip-container" onclick="showSlip('<?= base64_encode($p['Payment_slip']) ?>')">
                                    <img src="data:image/jpeg;base64,<?= base64_encode($p['Payment_slip']) ?>" class="slip-thumb">
                                    <div class="zoom-overlay"><i class="fas fa-search-plus"></i></div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted italic small">No Slip</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($p['Approval'] == 1): ?>
                                <div class="badge-pro bg-approved"><i class="fas fa-check-circle me-2"></i> Verified</div>
                            <?php elseif($p['Approval'] == 2): ?>
                                <div class="badge-pro bg-danger text-white"><i class="fas fa-times-circle me-2"></i> Rejected</div>
                            <?php else: ?>
                                <div class="badge-pro bg-pending"><i class="fas fa-hourglass-half me-2"></i> Pending Review</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if($p['Approval'] == 0): ?>
                                <button class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm fw-bold" onclick="processPayment(<?= $p['id'] ?>, 'approve')">Authorize</button>
                                <button class="btn btn-outline-danger btn-sm rounded-pill px-3 ms-1" onclick="processPayment(<?= $p['id'] ?>, 'reject')">Decline</button>
                            <?php else: ?>
                                <span class="text-muted small fw-bold">Closed <i class="fas fa-lock ms-1"></i></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="proSlipViewer" class="modal-backdrop d-none" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.95); z-index:9999; display:flex; flex-direction:column; align-items:center; justify-content:center;">
    <div class="position-absolute top-0 end-0 p-4">
        <button class="btn btn-link text-white text-decoration-none fs-2" onclick="closeSlip()">&times;</button>
    </div>
    <div class="text-center mb-3 text-white opacity-75 small">Scroll to zoom • Click and drag to pan</div>
    <img id="fullResSlip" src="" style="max-width: 90%; max-height: 85vh; border: 10px solid #1e293b; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
</div>

<script>
// Search Filter
document.getElementById('proSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#paymentTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});

function showSlip(base64) {
    document.getElementById('fullResSlip').src = "data:image/jpeg;base64," + base64;
    document.getElementById('proSlipViewer').classList.remove('d-none');
    document.body.style.overflow = 'hidden';
}

function closeSlip() {
    document.getElementById('proSlipViewer').classList.add('d-none');
    document.body.style.overflow = 'auto';
}

function processPayment(id, action) {
    if(action === 'approve') {
        if(confirm("AUTHORIZATION REQUIRED: Verify funds and trigger SMS system?")) {
            submitAction(id, 'approve');
        }
    } else {
        const reason = prompt("Enter REJECTION REASON (will be sent to Employer):");
        if(reason) {
            submitAction(id, 'reject', reason);
        }
    }
}

function submitAction(id, action, reason = '') {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'actions/process_payment.php';

    const fields = { payment_id: id, action: action, reason: reason };
    for (const key in fields) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>