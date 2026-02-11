<?php
session_start();
require_once 'config/config.php';

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery | JobQuest</title>
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
                        <div class="icon-box"><i class="fas fa-lock"></i></div>
                        <h3 class="fw-bold" style="color: var(--primary-hex);">Forgot Password?</h3>
                        <p class="text-muted small">Enter your email address to receive a recovery code.</p>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger d-flex align-items-center border-0 small rounded-3 mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if($success): ?>
                        <div class="alert alert-success d-flex align-items-center border-0 small rounded-3 mb-4">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?= htmlspecialchars($success) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="actions/send_otp.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-600 small">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="name@company.com" required autofocus>
                            </div>
                        </div>

                        <!-- Hidden field to default to Email, user can toggle SMS -->
                        <input type="hidden" name="channel" id="channel" value="email">

                        <button type="submit" class="btn btn-primary btn-login w-100 mb-3">Send Recovery Code</button>

                        <div class="text-center mb-3">
                            <a href="#" onclick="toggleChannel()" class="small text-decoration-none" id="toggleLink">Didn't receive email? Send via SMS</a>
                        </div>

                        <a href="login.php" class="btn btn-light w-100 fw-bold py-2 text-muted" style="border-radius: 12px;">Back to Login</a>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-4 text-muted small">
                &copy; <?= date('Y'); ?> JobQuest. All rights reserved.
            </div>
        </div>
    </div>
</div>

<script>
function toggleChannel() {
    const channelInput = document.getElementById('channel');
    const toggleLink = document.getElementById('toggleLink');
    const label = document.querySelector('label');
    const input = document.querySelector('input[name="email"]');
    const icon = document.querySelector('.input-group-text i');

    if (channelInput.value === 'email') {
        // Switch to SMS Mode
        channelInput.value = 'sms';
        toggleLink.innerText = "Prefer Email? Send via Email";
        label.innerText = "Mobile Number";
        input.type = "text";
        input.name = "mobile_number";
        input.placeholder = "07XXXXXXXX";
        icon.className = "fas fa-phone";
    } else {
        // Switch to Email Mode
        channelInput.value = 'email';
        toggleLink.innerText = "Didn't receive email? Send via SMS";
        label.innerText = "Email Address";
        input.type = "email";
        input.name = "email";
        input.placeholder = "name@company.com";
        icon.className = "fas fa-envelope";
    }
}
</script>

</body>
</html>
