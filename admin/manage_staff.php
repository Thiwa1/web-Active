<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

$pageTitle = "Staff Management";

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
        // Only revoke if they are NOT a primary PaperAdmin (to avoid locking out the main staff accounts)
        // Or if they are, maybe we just set the flag to 0?
        // Logic: If user_type is PaperAdmin, they are dedicated. If not, they are promoted.
        // Let's just set flag to 0.
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
$staff = $pdo->query("SELECT id, full_name, user_email, mobile_number, user_type, is_paper_admin FROM user_table WHERE user_type = 'PaperAdmin' OR is_paper_admin = 1 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Login Logs (For anyone who is a paper admin)
// We need to join logs with user table to check permissions
$logs = $pdo->query("
    SELECT l.*, u.full_name, u.user_type
    FROM admin_login_logs l
    JOIN user_table u ON l.user_id = u.id
    WHERE u.user_type = 'PaperAdmin' OR u.is_paper_admin = 1
    ORDER BY l.login_time DESC LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .nav-link { color: rgba(255,255,255,0.8); }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-3 d-none d-md-block" style="width: 250px;">
        <h4 class="mb-4 text-center">Admin Panel</h4>
        <ul class="nav flex-column gap-2">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a></li>
            <li class="nav-item"><a href="manage_jobs.php" class="nav-link"><i class="fas fa-briefcase me-2"></i> Jobs</a></li>
            <li class="nav-item"><a href="manage_paper_ads.php" class="nav-link"><i class="fas fa-newspaper me-2"></i> Paper Ads</a></li>
            <li class="nav-item"><a href="manage_staff.php" class="nav-link active"><i class="fas fa-users-cog me-2"></i> Staff</a></li>
            <li class="nav-item"><a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Staff Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus me-2"></i> Create Paper Admin</button>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Promote User Card -->
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title">Promote Existing User</h5>
                        <form method="POST" class="row g-3 align-items-end">
                            <input type="hidden" name="action" value="grant_admin">
                            <div class="col-md-6">
                                <label class="form-label">User Email Address</label>
                                <input type="email" name="target_email" class="form-control" placeholder="Enter email of existing user (e.g. employer)" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-outline-success w-100"><i class="fas fa-user-shield me-2"></i> Grant Privileges</button>
                            </div>
                            <div class="col-md-12">
                                <small class="text-muted">This will allow the user to access the Paper Ads dashboard in addition to their current role.</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Staff List -->
            <div class="col-md-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">Authorized Administrators</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Name</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($staff as $u): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
                                        <td>
                                            <?php if($u['user_type'] === 'PaperAdmin'): ?>
                                                <span class="badge bg-primary">Dedicated Staff</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark"><?= htmlspecialchars($u['user_type']) ?> + Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($u['user_email']) ?></td>
                                        <td class="text-end pe-3">
                                            <?php if($u['user_type'] !== 'PaperAdmin'): ?>
                                                <form method="POST" onsubmit="return confirm('Revoke admin rights?');">
                                                    <input type="hidden" name="action" value="revoke_admin">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Revoke</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">Primary</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($staff)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">No staff accounts found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Login Logs -->
            <div class="col-md-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">Recent Access</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 small">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">User</th>
                                    <th>Time</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($logs as $log): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <?= htmlspecialchars($log['full_name']) ?>
                                            <?php if($log['user_type'] !== 'PaperAdmin') echo '<span class="text-muted">*</span>'; ?>
                                        </td>
                                        <td><?= date('M d H:i', strtotime($log['login_time'])) ?></td>
                                        <td><span class="font-monospace text-muted"><?= $log['ip_address'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($logs)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">No activity recorded.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Paper Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_user">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="user_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="mobile_number" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="user_password" class="form-control" required>
                </div>
                <div class="alert alert-info small mb-0">
                    This user will be a <strong>dedicated admin</strong>. To give an existing user (like an Employer) admin rights, close this and use the "Promote Existing User" form.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
