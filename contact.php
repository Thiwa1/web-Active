<?php
require_once 'config/config.php';
session_start();

// Prefill logic
$pre_name = ''; $pre_email = '';
if(isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT full_name, user_email FROM user_table WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if($user) { $pre_name = $user['full_name']; $pre_email = $user['user_email']; }
}

$pageTitle = "Contact Us";
$extraCss = '<style>
    .contact-card { border-radius: 24px; border: none; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1); background: #fff; overflow: hidden; }
    .contact-info-bg { background: linear-gradient(135deg, var(--primary) 0%, #4338ca 100%); color: white; padding: 40px; }
    .form-control, .form-select { border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0; }
    .btn-send { background: var(--dark); color: white; padding: 14px 28px; border-radius: 10px; font-weight: 600; width: 100%; border: none; transition: 0.3s; }
    .btn-send:hover { background: black; transform: translateY(-2px); }
</style>';

include 'layout/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card contact-card">
                <div class="row g-0">
                    <!-- Left Side: Info -->
                    <div class="col-md-5 contact-info-bg d-flex flex-column justify-content-between">
                        <div>
                            <h3 class="fw-bold mb-4">Get in touch</h3>
                            <p class="opacity-75 mb-4">Have questions about posting a job or finding talent? Our team is here to help.</p>

                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-white bg-opacity-10 p-2 rounded-circle me-3"><i class="fas fa-envelope"></i></div>
                                    <span>infor@tiptopvacancies.com</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-white bg-opacity-10 p-2 rounded-circle me-3"><i class="fas fa-phone"></i></div>
                                    <span>+94 71 353 4183</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="bg-white bg-opacity-10 p-2 rounded-circle me-3"><i class="fas fa-map-marker-alt"></i></div>
                                    <span>Homagama, Sri Lanka</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <a href="#" class="text-white"><i class="fab fa-facebook-f fa-lg"></i></a>
                            <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                            <a href="#" class="text-white"><i class="fab fa-linkedin-in fa-lg"></i></a>
                        </div>
                    </div>

                    <!-- Right Side: Form -->
                    <div class="col-md-7 p-4 p-md-5 bg-white">
                        <h4 class="fw-bold text-dark mb-4">Send us a message</h4>

                        <?php if(isset($_GET['error'])): ?>
                            <div class="alert alert-danger d-flex align-items-center border-0 small mb-4" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div><?= htmlspecialchars($_GET['error']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if(isset($_GET['msg'])): ?>
                            <div class="alert alert-success d-flex align-items-center border-0 small mb-4" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <div><?= htmlspecialchars($_GET['msg']); ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="actions/send_contact_message.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Your Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($pre_name) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Email Address</label>
                                    <input type="email" name="email" class="form-control" placeholder="john@example.com" value="<?= htmlspecialchars($pre_email) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Subject</label>
                                    <select name="subject" class="form-select" required>
                                        <option value="">Select a topic...</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                        <option value="Employer Support">Employer Support</option>
                                        <option value="Job Seeker Help">Job Seeker Help</option>
                                        <option value="Billing Issue">Billing Issue</option>
                                        <option value="Partnership">Partnership</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Message</label>
                                    <textarea name="message" class="form-control" rows="5" placeholder="How can we help you?" required></textarea>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-send">
                                        Send Message <i class="fas fa-paper-plane ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
