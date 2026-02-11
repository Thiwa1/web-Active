<?php
require_once 'config/config.php';
session_start();

$job_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$job_id) { header("Location: index.php"); exit(); }

try {
    // 1. Fetch Job Data AND Company Profile Image (employer_logo)
    $stmt = $pdo->prepare("
        SELECT a.*, e.employer_name, e.employer_logo, e.logo_path, e.employer_about_company, e.employer_address_1
        FROM advertising_table a
        JOIN employer_profile e ON a.link_to_employer_profile = e.id
        WHERE a.id = ? AND a.Approved = 1
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) { die("Vacancy not found or pending approval."); }

    // 2. Track Views (Unique per session)
    if (!isset($_SESSION['viewed_job_' . $job_id])) {
        // Increment Total
        $pdo->prepare("UPDATE advertising_table SET views = views + 1 WHERE id = ?")->execute([$job_id]);
        
        // Log Time Series (Graceful fallback if table missing)
        try {
            $pdo->prepare("INSERT INTO job_views_log (job_id, viewed_at) VALUES (?, CURDATE())")->execute([$job_id]);
        } catch (Exception $e) { /* Table might not exist yet */ }

        $_SESSION['viewed_job_' . $job_id] = true;
    }

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

function deep_decode($text) {
    $decoded = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    while ($decoded !== html_entity_decode($decoded, ENT_QUOTES, 'UTF-8')) {
        $decoded = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');
    }
    return $decoded;
}

// 2. Logic to detect logged-in Seeker (Employee)
$isLoggedInSeeker = (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Employee');
$hasApplied = false;

if ($isLoggedInSeeker) {
    // Check if already applied
    $chkSeeker = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
    $chkSeeker->execute([$_SESSION['user_id']]);
    $sData = $chkSeeker->fetch();

    if ($sData) {
        $chkApp = $pdo->prepare("SELECT id FROM job_applications WHERE job_ad_link = ? AND seeker_link = ?");
        $chkApp->execute([$job['id'], $sData['id']]);
        if ($chkApp->fetch()) {
            $hasApplied = true;
        }
    }
}

// Check Expiry
$isExpired = (!empty($job['Closing_date']) && $job['Closing_date'] != '0000-00-00' && strtotime($job['Closing_date']) < time());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['Job_role']) ?> | Pro++</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root { 
            --brand: #0061ff; 
            --bg: #f8fafc;
            --dark: #1e293b;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--dark); }
        
        /* Company Profile Image Styling */
        .company-badge-lg { 
            width: 90px; 
            height: 90px; 
            object-fit: contain; 
            border-radius: 18px; 
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 8px;
        }

        /* JOB FLYER IMAGE STYLING (FIXED) */
        .job-flyer-container {
            width: 100%;
            overflow: hidden;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.08);
            margin-bottom: 30px;
            background-color: #f1f5f9;
            text-align: center; /* Centers image if it's smaller */
        }
        .job-flyer-img {
            max-width: 100%;       /* Ensure it fits container width */
            height: auto;          /* Maintain aspect ratio */
            max-height: 800px;     /* Prevent it from being too tall on huge screens */
            display: block;
            margin: 0 auto;        /* Center align */
        }

        .glass-card { 
            background: white; 
            border-radius: 24px; 
            border: 1px solid rgba(0,0,0,0.04); 
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.06); 
            padding: 35px; 
        }

        .btn-pro { 
            background: var(--brand); 
            color: #fff; 
            border-radius: 12px; 
            padding: 16px; 
            font-weight: 700; 
            border: none; 
            width: 100%; 
            transition: 0.3s; 
        }
        .btn-pro:hover { background: #0046b8; transform: translateY(-2px); color: white; }
        .btn-pro:disabled { background: #94a3b8; transform: none; }

        .tag { background: #eff6ff; color: var(--brand); padding: 5px 12px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="bg-white border-bottom py-5 animate__animated animate__fadeInDown">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-auto text-center text-md-start mb-3 mb-md-0">
                <?php if (!empty($job['logo_path'])): ?>
                    <img src="<?= htmlspecialchars($job['logo_path']) ?>" class="company-badge-lg" alt="Company Logo">
                <?php elseif (!empty($job['employer_logo'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($job['employer_logo']) ?>" class="company-badge-lg" alt="Company Logo">
                <?php else: ?>
                    <div class="company-badge-lg d-flex align-items-center justify-content-center bg-light text-muted">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md">
                <div class="d-flex gap-2 mb-2 justify-content-center justify-content-md-start">
                    <span class="tag"><i class="fas fa-layer-group me-1"></i> <?= htmlspecialchars($job['Job_category']) ?></span>
                    <span class="tag"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['City']) ?></span>
                </div>
                <h1 class="fw-800 display-6 mb-1 text-center text-md-start"><?= htmlspecialchars($job['Job_role']) ?></h1>
                <p class="text-muted mb-0 fw-600 text-center text-md-start"><?= htmlspecialchars($job['employer_name']) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <div class="row g-4">
        <div class="col-lg-8 animate__animated animate__fadeInLeft">
            <div class="glass-card mb-4">
                
                <?php if (!empty($job['img_path'])): ?>
                    <div class="job-flyer-container">
                        <img src="<?= htmlspecialchars($job['img_path']) ?>" class="job-flyer-img" alt="Job Advertisement">
                    </div>
                <?php elseif (!empty($job['Img'])): ?>
                    <div class="job-flyer-container">
                        <img src="data:image/jpeg;base64,<?= base64_encode($job['Img']) ?>" class="job-flyer-img" alt="Job Advertisement">
                    </div>
                <?php endif; ?>

                <h4 class="fw-800 mb-4">Description</h4>
                <div class="description-area text-secondary" style="line-height: 1.8;">
                    <?= deep_decode($job['job_description']) ?>
                </div>

                <hr class="my-5">

                <h5 class="fw-800 mb-3">About the Company</h5>
                <p class="text-muted"><?= deep_decode($job['employer_about_company']) ?></p>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sticky-top" style="top: 20px;">
                <div class="glass-card animate__animated animate__fadeInRight">
                    <h5 class="fw-800 mb-4">Application Hub</h5>

                    <div id="apply-ui">
                        <?php if ($job['Apply_by_system']): ?>
                            <?php if ($isLoggedInSeeker): ?>
                                <?php if ($hasApplied): ?>
                                    <button class="btn btn-secondary w-100 py-3 rounded-4 fw-bold shadow-sm" disabled>
                                        <i class="fas fa-check-circle me-2"></i> Already Applied
                                    </button>
                                <?php elseif ($isExpired): ?>
                                    <button class="btn btn-secondary w-100 py-3 rounded-4 fw-bold shadow-sm" disabled>
                                        <i class="fas fa-hourglass-end me-2"></i> Job Expired
                                    </button>
                                <?php else: ?>
                                    <button id="directApplyBtn" class="btn btn-pro shadow-sm">
                                        <i class="fas fa-bolt me-2"></i> Apply with Profile
                                    </button>
                                <?php endif; ?>

                                <div class="text-center mt-3">
                                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                        <i class="fas fa-user-check me-1"></i> Logged in as Seeker
                                    </span>
                                </div>
                            <?php else: ?>
                                <?php if ($isExpired): ?>
                                    <button class="btn btn-secondary w-100 py-3 rounded-4 fw-bold shadow-sm" disabled>
                                        <i class="fas fa-hourglass-end me-2"></i> Job Expired
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-pro shadow-sm" data-bs-toggle="modal" data-bs-target="#guestModal">
                                        Quick Apply (Guest)
                                    </button>
                                <?php endif; ?>
                                <p class="text-center small text-muted mt-2">Upload CV required</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning border-0 small">
                                <i class="fas fa-info-circle me-1"></i> Online applications disabled for this job.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="success-msg" class="text-center d-none animate__animated animate__zoomIn">
                        <div class="alert alert-success border-0 rounded-4 py-4">
                            <div class="mb-2"><i class="fas fa-check-circle fa-3x text-success"></i></div>
                            <h5 class="fw-800 mb-1">Applied!</h5>
                            <p class="small mb-0 text-muted">Your profile was sent successfully.</p>
                        </div>
                    </div>

                    <?php if ($job['apply_WhatsApp']): ?>
                        <a href="https://wa.me/<?= str_replace([' ', '+', '-'], '', $job['apply_WhatsApp_No']) ?>" class="btn btn-outline-success w-100 py-3 rounded-4 fw-bold border-2 mt-3">
                            <i class="fab fa-whatsapp me-2"></i> Chat via WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="guestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5 pt-0">
                <div class="text-center mb-4 mt-2">
                    <h3 class="fw-800">Submit Application</h3>
                    <p class="text-muted small">Apply as a guest instantly</p>
                </div>
                <form action="actions/process_guest_apply.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                    <div class="mb-3">
                        <label class="fw-bold small text-uppercase text-muted">Full Name</label>
                        <input type="text" name="full_name" class="form-control rounded-3 p-3 bg-light border-0" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="mb-4">
                        <label class="fw-bold small text-uppercase text-muted">Upload CV (PDF)</label>
                        <input type="file" name="cv_file" class="form-control rounded-3 p-3 bg-light border-0" accept=".pdf,.doc,.docx" required>
                    </div>
                    <button type="submit" class="btn btn-pro shadow-sm">Send Application</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // AJAX Logic for Logged-In Seekers
    $('#directApplyBtn').click(function() {
        const btn = $(this);
        
        // 1. Loading State
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Sending...');

        // 2. AJAX Request
        $.ajax({
            url: 'actions/process_profile_apply_ajax.php',
            type: 'POST',
            data: { job_id: <?= $job['id'] ?> },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // 3. Success State
                    $('#apply-ui').addClass('d-none');
                    $('#success-msg').removeClass('d-none');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Your application has been sent.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    // Error Handling
                    btn.prop('disabled', false).html('<i class="fas fa-bolt me-2"></i> Apply with Profile');
                    Swal.fire({
                        icon: 'error',
                        title: 'Application Failed',
                        text: response.message
                    });
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-bolt me-2"></i> Apply with Profile');
                Swal.fire('Server Error', 'Could not connect to the server.', 'error');
            }
        });
    });
});
</script>
</body>
</html>