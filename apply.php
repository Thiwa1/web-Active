<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Candidate') {
    header("Location: login.php?msg=login_required"); exit();
}

$job_id = $_GET['job_id'] ?? null;
$user_id = $_SESSION['user_id'];

try {
    // 1. Fetch Job & Employer Info
    $stmt = $pdo->prepare("SELECT a.Job_role, e.employer_name, e.employer_logo 
                           FROM advertising_table a 
                           JOIN employer_profile e ON a.link_to_employer_profile = e.id 
                           WHERE a.id = ? AND a.Approved = 1");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    if (!$job) { die("Vacancy no longer active."); }

    // 2. Fetch Candidate Profile Snapshot (To show what they are submitting)
    $stmt = $pdo->prepare("SELECT p.employee_full_name, p.cv_blob, p.id as seeker_id 
                           FROM employee_profile_seeker p 
                           WHERE p.link_to_user = ?");
    $stmt->execute([$user_id]);
    $seeker = $stmt->fetch();

    if (!$seeker || empty($seeker['cv_blob'])) {
        header("Location: profile_setup.php?error=no_cv"); exit();
    }

    // 3. Check for existing application
    $check = $pdo->prepare("SELECT id FROM job_applications WHERE job_ad_link = ? AND seeker_link = ?");
    $check->execute([$job_id, $seeker['seeker_id']]);
    $already_applied = $check->fetch();

} catch (PDOException $e) { die("System Error"); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Application | Pro Plus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        .apply-card { border-radius: 20px; border: none; overflow: hidden; }
        .profile-preview { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; }
        .btn-confirm { background: #4f46e5; border: none; padding: 12px; border-radius: 10px; font-weight: 700; transition: 0.3s; }
        .btn-confirm:hover { background: #4338ca; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <div class="card apply-card shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="badge bg-primary-subtle text-primary mb-2 px-3 py-2 rounded-pill">Job Application</div>
                        <h2 class="fw-bold mb-1"><?= htmlspecialchars($job['Job_role']) ?></h2>
                        <p class="text-muted"><?= htmlspecialchars($job['employer_name']) ?></p>
                    </div>

                    <?php if ($already_applied): ?>
                        <div class="text-center py-4">
                            <div class="mb-3 text-success"><i class="fas fa-check-circle fa-4x"></i></div>
                            <h4>Already Applied!</h4>
                            <p class="text-muted">Your application is currently being reviewed.</p>
                            <a href="my_applications.php" class="btn btn-outline-primary w-100 rounded-3">Track Status</a>
                        </div>
                    <?php else: ?>
                        <div class="profile-preview p-3 mb-4">
                            <h6 class="small fw-800 text-uppercase text-muted mb-3">Your Submission Snapshot</h6>
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-white p-2 rounded-3 border"><i class="fas fa-file-pdf fa-2x text-danger"></i></div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($seeker['employee_full_name']) ?></div>
                                    <div class="small text-muted">Stored CV: <strong>Verified PDF</strong></div>
                                </div>
                            </div>
                        </div>

                        <form action="actions/process_application.php" method="POST">
                            <input type="hidden" name="job_id" value="<?= $job_id ?>">
                            <input type="hidden" name="seeker_id" value="<?= $seeker['seeker_id'] ?>">

                            <div class="mb-4">
                                <label class="form-label fw-bold small">Cover Note</label>
                                <textarea name="cover_letter" class="form-control border-0 bg-light" rows="4" placeholder="Mention why you are a good fit..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-confirm w-100 shadow">
                                Submit Application <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                            <a href="job_details.php?id=<?= $job_id ?>" class="btn btn-link w-100 text-muted mt-2">Back to Search</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>