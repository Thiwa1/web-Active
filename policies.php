<?php
require_once 'config/config.php';
session_start();

$pageTitle = "Terms and Policies";
$extraCss = '<style>
    .policy-container { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    .policy-nav .nav-link { color: #64748b; font-weight: 600; padding: 10px 20px; border-radius: 8px; margin-bottom: 5px; }
    .policy-nav .nav-link.active { background: var(--primary); color: white; }
    h2 { font-weight: 800; color: var(--dark); margin-bottom: 20px; }
    h4 { font-weight: 700; color: #334155; margin-top: 30px; margin-bottom: 15px; }
    p, li { color: #475569; line-height: 1.7; }
</style>';

include 'layout/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 mb-4">
            <div class="sticky-top" style="top: 100px;">
                <h5 class="fw-bold mb-3 ps-3">Company Policies</h5>
                <div class="nav flex-column nav-pills policy-nav" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <a class="nav-link active" id="v-pills-terms-tab" data-bs-toggle="pill" href="#v-pills-terms" role="tab">Terms of Service</a>
                    <a class="nav-link" id="v-pills-privacy-tab" data-bs-toggle="pill" href="#v-pills-privacy" role="tab">Privacy Policy</a>
                    <a class="nav-link" id="v-pills-employer-tab" data-bs-toggle="pill" href="#v-pills-employer" role="tab">Employer Guidelines</a>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="col-md-9">
            <div class="tab-content" id="v-pills-tabContent">

                <!-- Terms of Service -->
                <div class="tab-pane fade show active policy-container" id="v-pills-terms" role="tabpanel">
                    <h2>Terms of Service</h2>
                    <p class="small text-muted mb-4">Last Updated: <?= date('F d, Y') ?></p>

                    <h4>1. Introduction</h4>
                    <p>Welcome to JobPortal. By accessing our website, you agree to be bound by these Terms of Service, all applicable laws, and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.</p>

                    <h4>2. Use License</h4>
                    <p>Permission is granted to temporarily download one copy of the materials (information or software) on JobPortal's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title.</p>

                    <h4>3. User Obligations</h4>
                    <ul>
                        <li>You agree to provide accurate and complete information during registration.</li>
                        <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
                        <li>You must not use the service for any illegal or unauthorized purpose.</li>
                    </ul>

                    <h4>4. Disclaimer</h4>
                    <p>The materials on JobPortal's website are provided on an 'as is' basis. JobPortal makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>
                </div>

                <!-- Privacy Policy -->
                <div class="tab-pane fade policy-container" id="v-pills-privacy" role="tabpanel">
                    <h2>Privacy Policy</h2>
                    <p class="small text-muted mb-4">Last Updated: <?= date('F d, Y') ?></p>

                    <h4>1. Information Collection</h4>
                    <p>We collect information you provide directly to us, such as when you create an account, update your profile, post a job, or communicate with us. This may include your name, email address, phone number, employment history, and other relevant details.</p>

                    <h4>2. Use of Information</h4>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Provide, maintain, and improve our services.</li>
                        <li>Match job seekers with potential employers.</li>
                        <li>Send you technical notices, updates, security alerts, and support messages.</li>
                    </ul>

                    <h4>3. Data Security</h4>
                    <p>We implement appropriate technical and organizational measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction.</p>
                </div>

                <!-- Employer Guidelines -->
                <div class="tab-pane fade policy-container" id="v-pills-employer" role="tabpanel">
                    <h2>Employer Guidelines</h2>
                    <p class="small text-muted mb-4">Last Updated: <?= date('F d, Y') ?></p>

                    <h4>1. Job Postings</h4>
                    <p>All job postings must represent real, current vacancies. We do not allow postings for multi-level marketing (MLM) schemes, "get rich quick" opportunities, or any illegal activities.</p>

                    <h4>2. Candidate Communication</h4>
                    <p>Employers must treat all candidates with respect and professionalism. Using candidate data for any purpose other than recruitment is strictly prohibited.</p>

                    <h4>3. Account Suspension</h4>
                    <p>We reserve the right to suspend or terminate any employer account that violates these guidelines or receives multiple complaints from job seekers.</p>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'layout/footer.php'; ?>
