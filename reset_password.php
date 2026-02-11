<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: login.php"); exit();
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | JobQuest</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-hex: #4f46e5; --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-gradient); min-height: 100vh; display: flex; align-items: center; }
        
        .login-card { border-radius: 20px; border: none; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); background: #ffffff; overflow: hidden; max-width: 450px; width: 100%; margin: auto; }
        .card-header-accent { background: var(--primary-hex); height: 5px; width: 100%; }
        
        .input-group-text { background: transparent; border-right: none; color: #94a3b8; }
        .form-control { border-left: none; padding: 12px; }
        .form-control:focus { box-shadow: none; border-color: #dee2e6; }
        .input-group:focus-within { outline: 2px solid #c7d2fe; border-radius: 0.375rem; }

        .btn-login { background: var(--primary-hex); border: none; padding: 14px; font-weight: 700; transition: all 0.3s ease; border-radius: 12px; }
        .btn-login:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        
        .icon-box { width: 60px; height: 60px; background: #eff6ff; color: var(--primary-hex); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="login-card card">
                <div class="card-header-accent"></div>
                <div class="card-body p-4 p-sm-5">
                    
                    <div class="text-center mb-4">
                        <div class="icon-box"><i class="fas fa-lock-open"></i></div>
                        <h3 class="fw-bold text-dark">Reset Access</h3>
                        <p class="text-muted small">Create a strong password to secure your account.</p>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger d-flex align-items-center border-0 small rounded-3 mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="actions/update_password.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-600 small">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="6">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-600 small">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-login w-100">Reset Password</button>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-4 text-muted small">
                &copy; <?= date('Y'); ?> JobQuest. All rights reserved.
            </div>
        </div>
    </div>
</div>

</body>
</html>
