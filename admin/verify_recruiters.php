<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

try {
    $sql = "SELECT ep.*, ut.user_email, ut.mobile_number, ut.user_active 
            FROM employer_profile ep 
            JOIN user_table ut ON ep.link_to_user = ut.id 
            WHERE ep.employer_Verified = 0 
            ORDER BY ep.id DESC";
    
    $pending_employers = $pdo->query($sql)->fetchAll();
    $pending_count = count($pending_employers);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trust Console | Recruiter Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --pro-success: #10b981; --pro-warning: #f59e0b; --pro-danger: #ef4444; --pro-slate: #0f172a; }
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Layout & Cards */
        .page-header { background: white; border-bottom: 1px solid #e2e8f0; padding: 2rem 0; margin-bottom: 2rem; }
        .glass-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
        
        /* Table Aesthetics */
        .table thead th { background: #f8fafc; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: #64748b; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        .table tbody td { padding: 1.25rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        /* Company Branding */
        .company-logo { width: 48px; height: 48px; background: #eff6ff; color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; border: 1px solid #dbeafe; }
        
        /* Action Buttons */
        .btn-approve { background: var(--pro-success); color: white; border: none; transition: 0.2s; }
        .btn-approve:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .btn-reject { color: #94a3b8; background: transparent; border: 1px solid #e2e8f0; }
        .btn-reject:hover { background: #fee2e2; color: var(--pro-danger); border-color: #fecaca; }

        /* Doc Viewer */
        .doc-link { cursor: pointer; color: #2563eb; font-weight: 600; text-decoration: none; padding: 6px 12px; background: #f0f7ff; border-radius: 8px; font-size: 0.85rem; }
        .doc-link:hover { background: #2563eb; color: white; }
    </style>
</head>
<body>

<header class="page-header">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item small"><a href="dashboard.php">Admin</a></li>
                    <li class="breadcrumb-item small active">Verification</li>
                </ol>
            </nav>
            <h3 class="fw-bold m-0 text-dark">Recruiter Pipeline</h3>
        </div>
        <div class="d-flex gap-3">
            <div class="text-end">
                <div class="small text-muted fw-bold">PENDING REVIEW</div>
                <div class="h4 m-0 fw-bold text-primary"><?= $pending_count ?> Companies</div>
            </div>
        </div>
    </div>
</header>

<div class="container">
    <div class="glass-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Entity Information</th>
                        <th>Security & Contact</th>
                        <th>Documents</th>
                        <th class="text-end pe-4">Compliance Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_count > 0): ?>
                        <?php foreach ($pending_employers as $emp): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="company-logo me-3">
                                        <?= strtoupper(substr($emp['employer_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($emp['employer_name']) ?></div>
                                        <div class="small text-muted"><i class="fas fa-location-dot me-1"></i> <?= htmlspecialchars($emp['employer_address_3']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="small fw-semibold text-dark mb-1"><i class="far fa-envelope me-2 text-muted"></i><?= htmlspecialchars($emp['user_email']) ?></span>
                                    <span class="small text-muted"><i class="fas fa-phone me-2"></i><?= htmlspecialchars($emp['employer_mobile_no']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if(!empty($emp['br_path'])): ?>
                                    <a href="../<?= htmlspecialchars($emp['br_path']) ?>" target="_blank" class="doc-link">
                                        <i class="fas fa-file-shield me-2"></i>View BR
                                    </a>
                                <?php elseif($emp['employer_BR']): ?>
                                    <a class="doc-link" onclick="viewBR('<?= base64_encode($emp['employer_BR']) ?>')">
                                        <i class="fas fa-file-shield me-2"></i>Verify BR (Legacy)
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border px-3">Missing BR</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <form action="actions/process_recruiter.php" method="POST" class="d-inline-flex gap-2">
                                    <input type="hidden" name="employer_id" value="<?= $emp['id'] ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-reject rounded-pill px-3" onclick="return confirm('Confirm Rejection?')">
                                        Decline
                                    </button>
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-approve rounded-pill px-4 fw-bold">
                                        Authorize
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="py-4">
                                    <i class="fas fa-check-circle fa-4x text-success opacity-25 mb-3"></i>
                                    <h5 class="fw-bold">Queue Empty</h5>
                                    <p class="text-muted small">All recruiters are currently verified and compliant.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="brModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.95); z-index:9999; backdrop-filter: blur(10px);">
    <div class="container h-100 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center py-4 text-white">
            <h5 class="m-0 fw-bold">Document Inspection</h5>
            <button class="btn btn-link text-white text-decoration-none fs-3 p-0" onclick="document.getElementById('brModal').style.display='none'">&times;</button>
        </div>
        <div class="flex-grow-1 d-flex justify-content-center align-items-center mb-4">
            <img id="fullBR" src="" style="max-width: 100%; max-height: 80vh; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
        </div>
        <div class="text-center pb-5">
            <button class="btn btn-light rounded-pill px-5" onclick="window.print()">Print Document</button>
        </div>
    </div>
</div>

<script>
function viewBR(base64) {
    document.getElementById('fullBR').src = "data:image/jpeg;base64," + base64;
    document.getElementById('brModal').style.display = 'block';
}
// Close on Escape key
document.addEventListener('keydown', (e) => { if(e.key === "Escape") document.getElementById('brModal').style.display='none'; });
</script>
</body>
</html>