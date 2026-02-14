<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

// Lazy Load Schema: Ensure is_paper_admin column exists
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM user_table LIKE 'is_paper_admin'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE user_table ADD COLUMN is_paper_admin TINYINT(1) DEFAULT 0");
    }
} catch (Exception $e) {
    // Ignore if error, might already exist or other DB issue
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Add New Paper Admin User
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $name = htmlspecialchars($_POST['full_name']);
        $email = htmlspecialchars($_POST['user_email']);
        $phone = htmlspecialchars($_POST['mobile_number']);
        $password = password_hash($_POST['user_password'], PASSWORD_BCRYPT);
        $role = 'PaperAdmin';
        $gender = 'Other';
        $dob = date('Y-m-d');

        try {
            // Ensure PaperAdmin role exists
            $pdo->exec("INSERT IGNORE INTO user_type_table (user_type_select, type_hide) VALUES ('PaperAdmin', 0)");

            $stmt = $pdo->prepare("INSERT INTO user_table (full_name, user_email, mobile_number, user_password, user_type, male_female, Birthday, WhatsApp_number, is_paper_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$name, $email, $phone, $password, $role, $gender, $dob, $phone]);
            $success = "New staff member created successfully.";
        } catch (PDOException $e) {
            // Fallback for schema variance
            if (strpos($e->getMessage(), "Unknown column 'type_hide'") !== false) {
                 try {
                    $pdo->exec("INSERT IGNORE INTO user_type_table (user_type_select) VALUES ('PaperAdmin')");
                    $stmt = $pdo->prepare("INSERT INTO user_table (full_name, user_email, mobile_number, user_password, user_type, male_female, Birthday, WhatsApp_number, is_paper_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$name, $email, $phone, $password, $role, $gender, $dob, $phone]);
                    $success = "New staff member created successfully.";
                 } catch (PDOException $ex) {
                    $error = "Error: " . $ex->getMessage();
                 }
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }

    // 2. Grant Admin Rights to Existing User
    if (isset($_POST['action']) && $_POST['action'] === 'grant_admin') {
        $target_email = $_POST['target_email'];
        $stmt = $pdo->prepare("UPDATE user_table SET is_paper_admin = 1 WHERE user_email = ?");
        $stmt->execute([$target_email]);
        if ($stmt->rowCount() > 0) {
            $success = "User '$target_email' has been granted Paper Admin privileges.";
        } else {
            $error = "User not found or already an admin.";
        }
    }

    // 3. Revoke Admin Rights
    if (isset($_POST['action']) && $_POST['action'] === 'revoke_admin') {
        $target_id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE user_table SET is_paper_admin = 0 WHERE id = ? AND user_type != 'PaperAdmin'");
        $stmt->execute([$target_id]);
        if ($stmt->rowCount() > 0) {
            $success = "Paper Admin privileges revoked.";
        } else {
            $error = "Cannot revoke privileges for dedicated Staff accounts (change their role instead) or user not found.";
        }
    }
}

// Fetch Staff (Dedicated PaperAdmin OR Promoted Users)
$staff = $pdo->query("SELECT id, full_name, user_email, mobile_number, user_type, is_paper_admin, created_at FROM user_table WHERE user_type = 'PaperAdmin' OR is_paper_admin = 1 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Login Logs (For anyone who is a paper admin)
$logs = $pdo->query("
    SELECT l.*, u.full_name, u.user_type
    FROM admin_login_logs l
    JOIN user_table u ON l.user_id = u.id
    WHERE u.user_type = 'PaperAdmin' OR u.is_paper_admin = 1
    ORDER BY l.login_time DESC LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

$totalStaff = count($staff);
$dedicatedStaff = 0;
foreach($staff as $s) { if($s['user_type'] === 'PaperAdmin') $dedicatedStaff++; }
$promotedStaff = $totalStaff - $dedicatedStaff;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management | Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --pro-blue: #4361ee; --pro-bg: #f8f9fc; }
        body { background-color: var(--pro-bg); font-family: 'Inter', sans-serif; color: #334155; }
        .summary-card { background: white; border-radius: 16px; padding: 1.25rem; border: 1px solid #e3e6f0; display: flex; align-items: center; }
        .summary-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 15px; }
        .job-table-card { background: white; border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; }
        .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
        .avatar-initial { width: 40px; height: 40px; border-radius: 10px; background: #e0e7ff; color: #4361ee; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
    </style>
</head>
<body>

<div class="container py-5">

    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Section -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="summary-card">
                <div class="summary-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-users-cog"></i></div>
                <div><div class="small text-muted fw-bold">TOTAL STAFF</div><div class="h4 fw-bold m-0"><?= $totalStaff ?></div></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card">
                <div class="summary-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-shield"></i></div>
                <div><div class="small text-muted fw-bold">DEDICATED</div><div class="h4 fw-bold m-0"><?= $dedicatedStaff ?></div></div>
            </div>
        </div>
        <div class="col-md-6 text-end d-flex align-items-center justify-content-end gap-2">
            <button class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus me-2"></i> New Staff</button>
            <a href="dashboard.php" class="btn btn-dark rounded-pill px-4"><i class="fas fa-arrow-left me-2"></i> Exit</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Staff List -->
        <div class="col-lg-8">
            <div class="job-table-card h-100">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Authorized Personnel</h5>
                    <button class="btn btn-sm btn-light text-primary fw-bold" data-bs-toggle="collapse" data-bs-target="#promoteBox"><i class="fas fa-level-up-alt me-2"></i>Promote User</button>
                </div>

                <div class="collapse p-3 bg-light border-bottom" id="promoteBox">
                    <form method="POST" class="row g-2 align-items-center">
                        <input type="hidden" name="action" value="grant_admin">
                        <div class="col-auto"><label class="fw-bold small">User Email:</label></div>
                        <div class="col"><input type="email" name="target_email" class="form-control form-control-sm" placeholder="e.g. employer@company.com" required></div>
                        <div class="col-auto"><button type="submit" class="btn btn-sm btn-success">Grant Access</button></div>
                    </form>
                    <small class="text-muted d-block mt-1 ms-1">Existing users will gain access to the Paper Admin dashboard.</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Staff Member</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Joined</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($staff as $u): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initial me-3"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($u['user_email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($u['user_type'] === 'PaperAdmin'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3">Dedicated</span>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3">Dual Role</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small"><i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($u['mobile_number']) ?></div>
                                    </td>
                                    <td><div class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></div></td>
                                    <td class="text-end pe-4">
                                        <?php if($u['user_type'] !== 'PaperAdmin'): ?>
                                            <form method="POST" onsubmit="return confirm('Revoke admin rights?');">
                                                <input type="hidden" name="action" value="revoke_admin">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger border-0"><i class="fas fa-user-times"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fas fa-lock"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($staff)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No staff accounts found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Access Logs -->
        <div class="col-lg-4">
            <div class="job-table-card h-100">
                <div class="p-3 border-bottom bg-light">
                    <h6 class="mb-0 fw-bold text-uppercase text-muted small">Recent Activity Log</h6>
                </div>
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover mb-0 small">
                        <tbody>
                            <?php foreach($logs as $log): ?>
                                <tr>
                                    <td class="ps-3 border-bottom-0">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($log['full_name']) ?></span>
                                            <span class="text-muted"><?= date('M d H:i', strtotime($log['login_time'])) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span class="text-muted">Login Success</span>
                                            <span class="font-monospace text-muted bg-light px-1 rounded"><?= $log['ip_address'] ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($logs)): ?>
                                <tr><td class="text-center py-3 text-muted">No logs available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>New Paper Admin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add_user">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase">Full Name</label>
                    <input type="text" name="full_name" class="form-control bg-light" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase">Email Address</label>
                    <input type="email" name="user_email" class="form-control bg-light" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase">Mobile Number</label>
                    <input type="text" name="mobile_number" class="form-control bg-light" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase">Password</label>
                    <input type="password" name="user_password" class="form-control bg-light" required>
                </div>
                <div class="alert alert-primary bg-opacity-10 border-0 small mb-0">
                    <i class="fas fa-info-circle me-1"></i> This creates a <strong>dedicated staff account</strong>. For existing users, use the "Promote User" tool.
                </div>
            </div>
            <div class="modal-footer p-3 bg-light border-0">
                <button type="button" class="btn btn-link text-muted text-decoration-none me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 rounded-pill">Create Account</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
