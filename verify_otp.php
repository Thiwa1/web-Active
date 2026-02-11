<?php
session_start();
require_once 'config/config.php';

// Check for any valid session identifier
if (!isset($_SESSION['reset_mobile']) && !isset($_SESSION['reset_email']) && !isset($_SESSION['reset_user_id'])) {
    header("Location: forgot_password.php"); exit();
}

$identifier = $_SESSION['reset_email'] ?? $_SESSION['reset_mobile'] ?? 'your account';
$error = $_GET['error'] ?? '';
$sent = $_GET['sent'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | JobQuest</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-hex: #4f46e5; --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-gradient); min-height: 100vh; display: flex; align-items: center; }
        
        .login-card { border-radius: 20px; border: none; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); background: #ffffff; overflow: hidden; max-width: 450px; width: 100%; margin: auto; }
        .card-header-accent { background: var(--primary-hex); height: 5px; width: 100%; }
        
        .form-control { border-left: none; padding: 12px; }
        .form-control:focus { box-shadow: none; border-color: #dee2e6; }
        
        .btn-login { background: var(--primary-hex); border: none; padding: 14px; font-weight: 700; transition: all 0.3s ease; border-radius: 12px; }
        .btn-login:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        
        .icon-box { width: 60px; height: 60px; background: #ecfdf5; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem; }
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
                        <div class="icon-box"><i class="fas fa-shield-alt"></i></div>
                        <h3 class="fw-bold text-dark">Verify Code</h3>
                        <p class="text-muted small">We sent a 4-digit code to <strong><?= htmlspecialchars($identifier) ?></strong></p>
                        <?php if($sent): ?>
                            <div class="alert alert-success border-0 rounded-3 small p-2 mb-3">
                                <i class="fas fa-check-circle me-1"></i> Code Sent! Check your SMS or Email.
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger d-flex align-items-center border-0 small rounded-3 mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="actions/verify_otp.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-600 small">Enter OTP</label>
                            <input type="text" name="otp_code" class="form-control text-center fw-bold fs-4 border" style="letter-spacing: 5px; border-left: 1px solid #dee2e6;" maxlength="4" placeholder="----" required autofocus>
                        </div>

                        <button type="submit" class="btn btn-primary btn-login w-100 mb-3">Verify & Proceed</button>
                        <a href="forgot_password.php" class="btn btn-link text-decoration-none w-100 small text-muted">Resend Code</a>
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
