<?php
require_once 'config/config.php';
require_once 'classes/GoogleAuth.php';
session_start();

$google = new GoogleAuth($pdo);
$authUrl = $google->getAuthUrl('Employee');

// Fetch User Types
$userTypes = [];
try {
    $userTypes = $pdo->query("SELECT user_type_select FROM user_type_table WHERE type_hide = 0 AND user_type_select != 'Admin'")->fetchAll();
} catch (PDOException $e) { }

$pageTitle = "Create Account";
$extraCss = '<style>
    .registration-card { border-radius: 24px; border: none; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); background: #fff; }
    .form-control, .form-select { border-radius: 12px; padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 0.95rem; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
    .step-indicator { display: flex; align-items: center; justify-content: center; margin-bottom: 2rem; }
    .step-dot { width: 10px; height: 10px; background: #e2e8f0; border-radius: 50%; margin: 0 5px; transition: 0.3s; }
    .step-dot.active { width: 30px; background: var(--primary); border-radius: 10px; }
    .btn-register { background: var(--primary); color: white; border: none; padding: 16px; font-weight: 700; border-radius: 14px; transition: 0.3s; width: 100%; }
    .btn-register:hover { background: var(--primary-dark); }
    #recruiter_fields { display: none; }
    .grecaptcha-badge { visibility: hidden; } /* Optional: hide floating badge if you include branding elsewhere */
</style>';
?>
<script src="https://www.google.com/recaptcha/api.js?render=6Le5oFQsAAAAAHU-Fy3CB9jGJqJq6j51omSnCh0_"></script>
<?php include 'layout/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            
            <div class="text-center mb-4">
                <img src="<?= $logoSrc; ?>" alt="Logo" class="mb-3" style="max-height: 120px;">
                <h2 class="fw-bold" style="color: var(--primary);">Join <?= htmlspecialchars($siteName); ?></h2>

                <?php if($authUrl && $authUrl != '#'): ?>
                    <a href="<?= htmlspecialchars($authUrl) ?>" class="btn btn-white shadow-sm rounded-pill mt-3 px-4 border fw-bold text-dark text-decoration-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48" class="me-2"><path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/><path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/><path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/><path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/></svg>
                        Sign up with Google
                    </a>
                <?php endif; ?>
            </div>

            <div class="card registration-card p-4 p-md-5">
                <div class="step-indicator">
                    <div id="dot1" class="step-dot active"></div>
                    <div id="dot2" class="step-dot"></div>
                </div>

                <form action="actions/register_action.php" method="POST" id="regForm">
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                    <div class="row g-4">
                        <div class="col-12"><h5 class="fw-bold border-start border-4 border-primary ps-3">Account Identity</h5></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">I am joining as</label>
                            <select name="user_type" id="user_type" class="form-select" onchange="toggleEmployerFields()" required>
                                <option value="">Select Account Type</option>
                                <?php foreach($userTypes as $type): ?>
                                    <option value="<?= $type['user_type_select']; ?>"><?= $type['user_type_select']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="recruiter_fields">
                            <label class="form-label small fw-bold text-primary">Company Name</label>
                            <input type="text" name="company_name" class="form-control border-primary" placeholder="Legal Company Name">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="full_name" class="form-control" placeholder="John Doe" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Birthday</label>
                            <input type="date" name="Birthday" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="male_female" class="form-select" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Country</label>
                            <input type="text" name="country" class="form-control" value="Sri Lanka" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="user_email" class="form-control" required oninput="document.getElementById('dot2').classList.add('active')">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mobile Number</label>
                            <input type="text" name="mobile_number" class="form-control" placeholder="07XXXXXXXX" required>
                        </div>
                        
                        <input type="hidden" name="WhatsApp_number" id="whatsapp_sync">

                        <div class="col-12 mt-4"><h5 class="fw-bold border-start border-4 border-primary ps-3">Security</h5></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="user_password" id="user_password" class="form-control" required onkeyup="evaluatePassword()">
                            <div class="strength-meter mt-2"><div id="strength-fill" style="height:4px; width:0; transition:0.3s; border-radius:2px;"></div></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Confirm Password</label>
                            <input type="password" id="confirm_password" class="form-control" required onkeyup="checkMatch()">
                            <div id="match-feedback" class="small mt-1"></div>
                        </div>

                        <div class="col-12 mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="termsCheck" required>
                                <label class="form-check-label small" for="termsCheck">
                                    I agree to the <a href="policies.php" target="_blank" class="text-primary text-decoration-none">Terms and Conditions</a> and <a href="policies.php#v-pills-privacy" target="_blank" class="text-primary text-decoration-none">Privacy Policy</a>.
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-register mt-4">COMPLETE REGISTRATION</button>
                </form>
                <div class="text-center mt-3 text-muted small">
                    This site is protected by reCAPTCHA and the Google
                    <a href="https://policies.google.com/privacy">Privacy Policy</a> and
                    <a href="https://policies.google.com/terms">Terms of Service</a> apply.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// reCAPTCHA Integration
document.getElementById('regForm').addEventListener('submit', function(e) {
    e.preventDefault();
    grecaptcha.ready(function() {
        grecaptcha.execute('6Le5oFQsAAAAAHU-Fy3CB9jGJqJq6j51omSnCh0_', {action: 'register'}).then(function(token) {
            document.getElementById('recaptcha_token').value = token;
            document.getElementById('regForm').submit();
        });
    });
});

function toggleEmployerFields() {
    const type = document.getElementById('user_type').value;
    document.getElementById('recruiter_fields').style.display = (type === 'Employer') ? 'block' : 'none';
}

// Ensure Mobile and WhatsApp are the same for initial registration
document.querySelector('input[name="mobile_number"]').addEventListener('input', function() {
    document.getElementById('whatsapp_sync').value = this.value;
});

function checkMatch() {
    const p1 = document.getElementById('user_password').value;
    const p2 = document.getElementById('confirm_password').value;
    document.getElementById('match-feedback').innerHTML = (p1 === p2) ? '<span class="text-success">Match!</span>' : '<span class="text-danger">No match</span>';
}

function evaluatePassword() {
    const val = document.getElementById('user_password').value;
    const fill = document.getElementById('strength-fill');
    let s = 0;
    if(val.length > 7) s++;
    if(/[A-Z]/.test(val)) s++;
    if(/[0-9]/.test(val)) s++;
    const colors = ['#e2e8f0', '#ef4444', '#f59e0b', '#10b981'];
    fill.style.width = (s * 33) + '%';
    fill.style.backgroundColor = colors[s];
}
</script>

<?php include 'layout/footer.php'; ?>
