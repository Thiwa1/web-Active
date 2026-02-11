<?php
require_once 'config/config.php';
require_once 'classes/GoogleAuth.php';
session_start();

$google = new GoogleAuth($pdo);
$authUrl = $google->getAuthUrl();

// Generate CSRF Token for Security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = "Secure Login";
$extraCss = '<style>
    .login-card { border-radius: 20px; border: none; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); background: #ffffff; overflow: hidden; }
    .card-header-accent { background: var(--primary); height: 5px; width: 100%; }
    
    .input-group-text { background: transparent; border-right: none; color: #94a3b8; }
    .form-control { border-left: none; padding: 12px; }
    .form-control:focus { box-shadow: none; border-color: #dee2e6; }
    .input-group:focus-within { outline: 2px solid #c7d2fe; border-radius: 0.375rem; }

    .btn-login { background: var(--primary); border: none; padding: 14px; font-weight: 700; transition: all 0.3s ease; border-radius: 12px; }
    .btn-login:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }

    .password-toggle { cursor: pointer; transition: color 0.2s; }
    .password-toggle:hover { color: var(--primary); }
    .grecaptcha-badge { visibility: hidden; }
</style>';
?>
<script src="https://www.google.com/recaptcha/api.js?render=6Le5oFQsAAAAAHU-Fy3CB9jGJqJq6j51omSnCh0_"></script>
<?php include 'layout/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="login-card card">
                <div class="card-header-accent"></div>
                <div class="card-body p-4 p-sm-5">
                    
                    <div class="text-center mb-5">
                        <img src="<?= $logoSrc; ?>" alt="Logo" style="max-height: 120px; width: auto;" class="mb-3">
                        <h3 class="fw-bold" style="color: var(--primary);">Welcome Back</h3>
                        <p class="text-muted small">Please enter your details to sign in</p>
                    </div>

                    <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-danger d-flex align-items-center border-0 small" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div><?= htmlspecialchars($_GET['error']); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="actions/login_action.php" method="POST" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token">

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                <input type="email" name="user_email" class="form-control" required 
                                       placeholder="name@company.com" autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between">
                                <label class="form-label fw-bold small">Password</label>
                                <a href="forgot_password.php" class="text-decoration-none small fw-bold">Forgot?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="user_password" id="passwordInput" class="form-control" 
                                       required placeholder="••••••••">
                                <span class="input-group-text password-toggle" onclick="togglePassword()">
                                    <i class="far fa-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-login w-100 mb-4" id="submitBtn">
                            <span id="btnText">SIGN IN</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>

                        <div class="text-center">
                            <span class="text-muted small">New to our platform?</span> 
                            <a href="register.php" class="text-decoration-none small fw-bold ms-1">Create an account</a>
                        </div>

                        <?php if($authUrl && $authUrl != '#'): ?>
                        <div class="my-3 d-flex align-items-center">
                            <div class="flex-grow-1 border-bottom"></div>
                            <span class="px-2 text-muted small">OR</span>
                            <div class="flex-grow-1 border-bottom"></div>
                        </div>
                        <a href="<?= htmlspecialchars($authUrl) ?>" class="btn btn-light w-100 border shadow-sm d-flex align-items-center justify-content-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48" class="me-2"><path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/><path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/><path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/><path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/></svg>
                            Sign in with Google
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-4 text-muted small">
                &copy; <?= date('Y'); ?> <?= htmlspecialchars($siteName); ?>. All rights reserved.
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('passwordInput');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Inline login handler preserved for reCAPTCHA flow
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // UI Helper handles the visual loader via global event listener
        // But we need to handle the reCAPTCHA token injection manually here
        
        grecaptcha.ready(function() {
            grecaptcha.execute('6Le5oFQsAAAAAHU-Fy3CB9jGJqJq6j51omSnCh0_', {action: 'login'}).then(function(token) {
                document.getElementById('recaptcha_token').value = token;
                // Native submit to bypass this listener again
                HTMLFormElement.prototype.submit.call(document.getElementById('loginForm'));
            });
        });
    });
</script>

<?php include 'layout/footer.php'; ?>
