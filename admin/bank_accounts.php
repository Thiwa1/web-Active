<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

$msg = "";
$status = "";

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $pdo->prepare("DELETE FROM system_bank_accounts WHERE id = ?")->execute([$_GET['delete']]);
        $msg = "Account deleted.";
        $status = "success";
    } catch (Exception $e) {
        $msg = "Error deleting account.";
        $status = "danger";
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bank = trim($_POST['bank_name']);
    $acc = trim($_POST['account_number']);
    $branch = trim($_POST['branch_name']);
    $code = trim($_POST['branch_code']);
    
    try {
        if (!empty($_POST['id'])) {
            $sql = "UPDATE system_bank_accounts SET bank_name=?, account_number=?, branch_name=?, branch_code=? WHERE id=?";
            $pdo->prepare($sql)->execute([$bank, $acc, $branch, $code, $_POST['id']]);
            $msg = "Account updated successfully.";
        } else {
            $sql = "INSERT INTO system_bank_accounts (bank_name, account_number, branch_name, branch_code) VALUES (?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$bank, $acc, $branch, $code]);
            $msg = "New account added.";
        }
        $status = "success";
    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
        $status = "danger";
    }
}

// Fetch Accounts
$accounts = $pdo->query("SELECT * FROM system_bank_accounts ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Accounts | ProAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .glass-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .acc-card { border-left: 4px solid #4f46e5; background: #fff; border-radius: 12px; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .acc-card:hover { transform: translateX(5px); box-shadow: 0 10px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">Payment Receiving Accounts</h2>
            <p class="text-muted">Manage bank details displayed to employers for payments.</p>
        </div>
        <a href="dashboard.php" class="btn btn-light rounded-pill px-4 fw-bold">Dashboard</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $status ?> rounded-4 mb-4 shadow-sm"><i class="fas fa-info-circle me-2"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="row g-5">
        <div class="col-lg-4">
            <div class="glass-card sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-4">Add Account</h5>
                <form method="POST">
                    <input type="hidden" name="id" id="f_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Bank Name</label>
                        <input type="text" name="bank_name" id="f_bank" class="form-control" required placeholder="e.g. Commercial Bank">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Account Number</label>
                        <input type="text" name="account_number" id="f_acc" class="form-control" required placeholder="XXXXXXXXXX">
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Branch</label>
                            <input type="text" name="branch_name" id="f_br" class="form-control" placeholder="City">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Branch Code</label>
                            <input type="text" name="branch_code" id="f_code" class="form-control" placeholder="000">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Save Details</button>
                    <button type="button" class="btn btn-light w-100 rounded-pill mt-2 d-none" id="btn_cancel" onclick="resetForm()">Cancel Edit</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <h5 class="fw-bold mb-4">Active Accounts</h5>
            <?php if(empty($accounts)): ?>
                <div class="text-center py-5 text-muted">No accounts configured. Employers won't know where to pay.</div>
            <?php else: ?>
                <?php foreach($accounts as $a): ?>
                    <div class="acc-card p-4 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($a['bank_name']) ?></h5>
                            <div class="font-monospace fs-5 text-primary mb-1"><?= htmlspecialchars($a['account_number']) ?></div>
                            <div class="text-muted small">
                                <i class="fas fa-code-branch me-1"></i> <?= htmlspecialchars($a['branch_name']) ?> 
                                (<?= htmlspecialchars($a['branch_code']) ?>)
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-light border" onclick="edit(<?= htmlspecialchars(json_encode($a)) ?>)">
                                <i class="fas fa-pen"></i>
                            </button>
                            <a href="?delete=<?= $a['id'] ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Delete?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function edit(data) {
    document.getElementById('f_id').value = data.id;
    document.getElementById('f_bank').value = data.bank_name;
    document.getElementById('f_acc').value = data.account_number;
    document.getElementById('f_br').value = data.branch_name;
    document.getElementById('f_code').value = data.branch_code;
    document.getElementById('btn_cancel').classList.remove('d-none');
}
function resetForm() {
    document.getElementById('f_id').value = '';
    document.getElementById('f_bank').value = '';
    document.getElementById('f_acc').value = '';
    document.getElementById('f_br').value = '';
    document.getElementById('f_code').value = '';
    document.getElementById('btn_cancel').classList.add('d-none');
}
</script>
</body>
</html>
