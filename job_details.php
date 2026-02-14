<?php
require_once 'config/config.php';
session_start();

$job_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$job_id) { header("Location: index.php"); exit(); }

try {
    // Select correct date columns from advertising_table
    $stmt = $pdo->prepare("
        SELECT a.*, e.employer_name, e.employer_logo, e.logo_path, e.employer_about_company, e.employer_address_1
        FROM advertising_table a
        JOIN employer_profile e ON a.link_to_employer_profile = e.id
        WHERE a.id = ? AND a.Approved = 1
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    if (!$job) { die("Vacancy not found or pending approval."); }

    // Fetch Global Settings for Feature Toggles
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('enable_direct_apply', 'enable_whatsapp_apply')");
    $globalSettings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

    // Determine Feature Availability (Default enabled if setting missing, or respect '0'/'1')
    $canDirectApply = !isset($globalSettings['enable_direct_apply']) || $globalSettings['enable_direct_apply'] == '1';
    $canWhatsAppApply = !isset($globalSettings['enable_whatsapp_apply']) || $globalSettings['enable_whatsapp_apply'] == '1';

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

function render_html($data) {
    if (empty($data)) return '';
    return html_entity_decode(html_entity_decode($data, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['Job_role']) ?> | Pro Plus Career</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-pro: #4f46e5; --secondary-pro: #64748b; }
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
        
        /* Premium Header */
        .job-hero { background: white; border-bottom: 1px solid #e2e8f0; padding: 60px 0 40px; }
        .company-badge { width: 100px; height: 100px; object-fit: contain; border-radius: 16px; padding: 10px; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        /* Layout */
        .main-content { margin-top: -30px; }
        .glass-card { background: white; border-radius: 24px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 40px; }
        
        /* Typography */
        .description-area { font-size: 1.05rem; line-height: 1.8; color: #334155; }
        .meta-box { background: #f1f5f9; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; }
        .icon-circle { width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-pro); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

        .btn-apply-now { background: var(--primary-pro); color: white; border: none; transition: all 0.3s; }
        .btn-apply-now:hover { background: #4338ca; transform: translateY(-2px); color: white; }

        @media (max-width: 768px) {
            .fixed-action-bar { position: fixed; bottom: 0; left: 0; right: 0; background: white; padding: 15px; border-top: 1px solid #e2e8f0; z-index: 1000; box-shadow: 0 -5px 20px rgba(0,0,0,0.1); }
        }
    </style>
</head>
<body>

<?php if (isset($_GET['applied']) && $_GET['applied'] == 'success'): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> <strong>Application Sent!</strong> Your profile has been submitted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<header class="job-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-auto text-center text-md-start mb-4 mb-md-0">
                <?php if (!empty($job['logo_path'])): ?>
                    <img src="<?= htmlspecialchars($job['logo_path']) ?>" class="company-badge">
                <?php elseif ($job['employer_logo']): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($job['employer_logo']) ?>" class="company-badge">
                <?php endif; ?>
            </div>
            <div class="col-md text-center text-md-start">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2"><?= htmlspecialchars($job['Job_category']) ?></span>
                <h1 class="display-6 fw-bold mb-1"><?= htmlspecialchars($job['Job_role']) ?></h1>
                <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3 mt-2 text-muted fw-semibold">
                    <span><i class="fas fa-building me-1"></i> <?= htmlspecialchars($job['employer_name']) ?></span>
                    <span><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['City']) ?></span>
                    <span><i class="far fa-calendar-alt me-1"></i> Posted <?= date('M d', strtotime($job['Opening_date'])) ?></span>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container main-content pb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="glass-card mb-4">
                <?php if (!empty($job['img_path'])): ?>
                    <div class="rounded-4 overflow-hidden mb-5 border">
                        <img src="<?= htmlspecialchars($job['img_path']) ?>" class="w-100 h-auto" alt="Job Visual">
                    </div>
                <?php elseif (!empty($job['Img'])): ?>
                    <div class="rounded-4 overflow-hidden mb-5 border">
                        <img src="data:image/jpeg;base64,<?= base64_encode($job['Img']) ?>" class="w-100 h-auto" alt="Job Visual">
                    </div>
                <?php endif; ?>

                <h4 class="fw-bold mb-4 d-flex align-items-center">
                    <span class="icon-circle me-3"><i class="fas fa-file-alt"></i></span> 
                    Opportunity Details
                </h4>
                <div class="description-area">
                    <?= render_html($job['job_description']) ?>
                </div>

                <hr class="my-5 opacity-50">

                <h4 class="fw-bold mb-4 d-flex align-items-center">
                    <span class="icon-circle me-3"><i class="fas fa-info-circle"></i></span> 
                    About Company
                </h4>
                <div class="description-area small opacity-75">
                    <?= !empty($job['employer_about_company']) ? render_html($job['employer_about_company']) : 'Professional employer listing.' ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sticky-top" style="top: 20px;">
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-4">Application Center</h5>
                    
                    <?php if ($job['Apply_by_system'] && $canDirectApply): ?>
                        <button type="button" class="btn btn-apply-now btn-lg w-100 rounded-4 fw-bold mb-3 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#applyModal">
                            Apply for Job <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    <?php endif; ?>

                    <?php if ($job['apply_WhatsApp'] && $canWhatsAppApply): ?>
                        <a href="https://wa.me/<?= str_replace([' ', '+', '-'], '', $job['apply_WhatsApp_No']) ?>" target="_blank" class="btn btn-outline-success btn-lg w-100 rounded-4 fw-bold mb-3 py-3 border-2">
                            <i class="fab fa-whatsapp me-2"></i> WhatsApp
                        </a>
                    <?php endif; ?>

                    <div class="mt-4">
                        <div class="meta-box mb-3 d-flex align-items-center gap-3">
                            <div class="icon-circle"><i class="fas fa-briefcase"></i></div>
                            <div><small class="text-muted d-block">Industry</small><strong><?= htmlspecialchars($job['Industry']) ?></strong></div>
                        </div>
                        <div class="meta-box mb-3 d-flex align-items-center gap-3">
                            <div class="icon-circle"><i class="fas fa-hourglass-end text-danger"></i></div>
                            <div><small class="text-muted d-block">Deadline</small><strong class="text-danger"><?= ($job['Closing_date'] && $job['Closing_date'] != '0000-00-00') ? date('M d, Y', strtotime($job['Closing_date'])) : 'Until Filled' ?></strong></div>
                        </div>
                    </div>

                    <?php if ($job['Apply_by_email']): ?>
                        <div class="p-3 bg-light rounded-4 text-center border">
                            <small class="text-muted">Email your CV to:</small>
                            <a href="mailto:<?= htmlspecialchars($job['Apply_by_email_address']) ?>" class="d-block fw-bold text-primary text-decoration-none"><?= htmlspecialchars($job['Apply_by_email_address']) ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php if ($job['Apply_by_system'] && $canDirectApply): ?>
<div class="fixed-action-bar d-md-none">
    <button class="btn btn-primary w-100 rounded-pill fw-bold py-2" data-bs-toggle="modal" data-bs-target="#applyModal">Apply Now</button>
</div>

<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold px-3 pt-3">Quick Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/process_guest_apply.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="job_ad_link" value="<?= $job['id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="guest_full_name" class="form-control rounded-3" placeholder="Enter your name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contact Number</label>
                        <input type="text" name="guest_contact_no" class="form-control rounded-3" placeholder="Phone/Mobile" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Gender</label>
                        <select name="guest_gender" class="form-select rounded-3" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Upload CV (PDF/Word)</label>
                        <input type="file" name="guest_cv" class="form-control rounded-3" accept=".pdf,.doc,.docx" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Cover Letter / Message</label>
                        <textarea name="guest_cover_letter" class="form-control rounded-3" rows="3" placeholder="Optional brief introduction..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-apply-now w-100 rounded-3 py-2 fw-bold">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>