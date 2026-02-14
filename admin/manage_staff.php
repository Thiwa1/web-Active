<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

$pageTitle = "Staff Management";

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['user_email']);
    $phone = htmlspecialchars($_POST['mobile_number']);
    $password = password_hash($_POST['user_password'], PASSWORD_BCRYPT);
    $role = 'PaperAdmin';
    $gender = 'Other'; // Default
    $dob = date('Y-m-d'); // Default

    try {
        $stmt = $pdo->prepare("INSERT INTO user_table (full_name, user_email, mobile_number, user_password, user_type, male_female, Birthday, WhatsApp_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $password, $role, $gender, $dob, $phone]);
        $success = "New staff member added.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Staff
$staff = $pdo->query("SELECT id, full_name, user_email, mobile_number, created_at FROM user_table WHERE user_type = 'PaperAdmin' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Login Logs for Staff
$logs = $pdo->query("SELECT l.*, u.full_name FROM admin_login_logs l JOIN user_table u ON l.user_id = u.id WHERE u.user_type = 'PaperAdmin' ORDER BY l.login_time DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

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
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus me-2"></i> Add Paper Admin</button>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Staff List -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">Paper Ad Administrators</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($staff as $u): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
                                        <td><?= htmlspecialchars($u['user_email']) ?></td>
                                        <td><?= htmlspecialchars($u['mobile_number']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($staff)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">No staff accounts found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Login Logs -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">Access Logs</h5>
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
                                        <td class="ps-3"><?= htmlspecialchars($log['full_name']) ?></td>
                                        <td><?= $log['login_time'] ?></td>
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
                    This user will have restricted access to the <strong>Paper Ads</strong> module only.
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
