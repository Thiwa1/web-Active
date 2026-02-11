<?php
session_start();
require_once '../config/config.php';

// 1. Security & Configuration
$site_name = "JobQuest Pro"; 

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_msg = "";

try {
    // 2. Data Retrieval with Optimization
    $stmt = $pdo->prepare("SELECT * FROM employee_profile_seeker WHERE link_to_user = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    if (!$profile) {
        $pdo->prepare("INSERT INTO employee_profile_seeker (link_to_user) VALUES (?)")->execute([$user_id]);
        header("Location: profile.php?initial=1");
        exit();
    }
} catch (PDOException $e) {
    error_log("Profile Fetch Error: " . $e->getMessage());
    die("A secure connection error occurred. Please try again later.");
}

// 3. Pro Update Engine (Transactional)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = filter_input(INPUT_POST, 'employee_full_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $initial_name = filter_input(INPUT_POST, 'employee_name_with_initial', FILTER_SANITIZE_SPECIAL_CHARS);

    try {
        $pdo->beginTransaction();

        // Update Textual Data
        $sql = "UPDATE employee_profile_seeker SET employee_full_name = ?, employee_name_with_initial = ? WHERE link_to_user = ?";
        $pdo->prepare($sql)->execute([$full_name, $initial_name, $user_id]);

        // Smart File Handling (BLOB)
        $file_map = [
            'img_file' => 'employee_img',
            'cv_file' => 'employee_cv',
            'cl_file' => 'employee_cover_letter'
        ];

        foreach ($file_map as $input => $column) {
            if (!empty($_FILES[$input]['tmp_name']) && $_FILES[$input]['error'] == 0) {
                // Validation: Max 5MB for images/docs
                if ($_FILES[$input]['size'] <= 5242880) { 
                    $data = file_get_contents($_FILES[$input]['tmp_name']);
                    $stmt = $pdo->prepare("UPDATE employee_profile_seeker SET $column = ? WHERE link_to_user = ?");
                    $stmt->bindParam(1, $data, PDO::PARAM_LOB);
                    $stmt->bindParam(2, $user_id);
                    $stmt->execute();
                }
            }
        }

        $pdo->commit();
        header("Location: profile.php?success=1");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $status_msg = "Critical failure: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Identity | <?= $site_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --p-blue: #0ea5e9; --p-dark: #0f172a; --p-slate: #f1f5f9; }
        body { background-color: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: var(--p-dark); }
        
        /* Modern Pro Card Layout */
        .glass-card { background: white; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .sticky-summary { position: sticky; top: 2rem; }
        
        /* Interactive Profile Image */
        .avatar-uploader { position: relative; width: 140px; height: 140px; margin: 0 auto; }
        .avatar-preview { width: 140px; height: 140px; border-radius: 40px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .avatar-edit-btn { position: absolute; bottom: -5px; right: -5px; background: var(--p-blue); color: white; width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 3px solid #fff; cursor: pointer; transition: 0.3s; }
        .avatar-edit-btn:hover { transform: scale(1.1); background: #0284c7; }

        .form-label { font-weight: 600; font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .form-control { border-radius: 12px; padding: 12px 16px; border: 1px solid #e2e8f0; background: #fdfdfd; transition: 0.2s; }
        .form-control:focus { border-color: var(--p-blue); box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); }

        .doc-zone { border: 2px dashed #e2e8f0; border-radius: 16px; padding: 20px; transition: 0.3s; background: #fcfcfc; }
        .doc-zone:hover { border-color: var(--p-blue); background: #f0f9ff; }
        .status-pill { font-size: 0.75rem; padding: 4px 12px; border-radius: 100px; font-weight: 600; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row mb-5 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold m-0 text-gradient">Personal Identity</h2>
            <p class="text-muted m-0">Define how employers see your professional brand.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-4 border-2 fw-bold small">
                <i class="fas fa-house me-2"></i>Exit to Portal
            </a>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 py-3">
            <i class="fas fa-check-circle me-2"></i> <strong>Success!</strong> Your professional profile has been synchronized.
        </div>
    <?php endif; ?>

    <form action="profile.php" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="glass-card p-4 text-center sticky-summary">
                    <div class="avatar-uploader mb-4">
                        <?php if (!empty($profile['employee_img'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($profile['employee_img']) ?>" class="avatar-preview" id="preview-img">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($profile['employee_full_name'] ?? 'U') ?>&background=0ea5e9&color=fff&size=200" class="avatar-preview" id="preview-img">
                        <?php endif; ?>
                        
                        <label for="img_file" class="avatar-edit-btn">
                            <i class="fas fa-pen-nib"></i>
                            <input type="file" id="img_file" name="img_file" hidden accept="image/*" onchange="loadFile(event)">
                        </label>
                    </div>

                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($profile['employee_name_with_initial'] ?: 'Candidate Name') ?></h5>
                    <p class="text-muted small mb-4"><?= htmlspecialchars($profile['employee_full_name'] ?: 'Not configured') ?></p>

                    <div class="bg-light rounded-4 p-3 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold">Profile Strength</span>
                        <span class="status-pill bg-primary text-white">85% Complete</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="glass-card p-4 p-md-5 mb-4">
                    <h6 class="fw-bold mb-4 d-flex align-items-center">
                        <span class="bg-primary text-white rounded-circle me-2 d-inline-flex align-items-center justify-content-center" style="width:24px; height:24px; font-size:10px;">1</span>
                        Identity Configuration
                    </h6>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">Legal Full Name</label>
                            <input type="text" name="employee_full_name" class="form-control" value="<?= htmlspecialchars($profile['employee_full_name'] ?? '') ?>" placeholder="e.g. John Alexander Smith" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Professional Alias (Name with Initials)</label>
                            <input type="text" name="employee_name_with_initial" class="form-control" value="<?= htmlspecialchars($profile['employee_name_with_initial'] ?? '') ?>" placeholder="e.g. J. A. Smith" required>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-4 p-md-5">
                    <h6 class="fw-bold mb-4 d-flex align-items-center">
                        <span class="bg-primary text-white rounded-circle me-2 d-inline-flex align-items-center justify-content-center" style="width:24px; height:24px; font-size:10px;">2</span>
                        Asset Management
                    </h6>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Curriculum Vitae (PDF/Doc)</label>
                            <div class="doc-zone">
                                <input type="file" name="cv_file" class="form-control form-control-sm mb-3">
                                <?php if(!empty($profile['employee_cv'])): ?>
                                    <span class="status-pill bg-success-subtle text-success border border-success-subtle">
                                        <i class="fas fa-check-double me-1"></i> CV Stored
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill bg-secondary-subtle text-secondary">No File Attached</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Cover Letter</label>
                            <div class="doc-zone">
                                <input type="file" name="cl_file" class="form-control form-control-sm mb-3">
                                <?php if(!empty($profile['employee_cover_letter'])): ?>
                                    <span class="status-pill bg-success-subtle text-success border border-success-subtle">
                                        <i class="fas fa-check-double me-1"></i> Letter Stored
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill bg-secondary-subtle text-secondary">No File Attached</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 text-end">
                        <button type="submit" class="btn btn-primary px-5 py-3 rounded-pill fw-bold shadow-lg transition">
                            Save My Identity <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Live Image Preview Engine
    var loadFile = function(event) {
        var output = document.getElementById('preview-img');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function() {
            URL.revokeObjectURL(output.src) // free memory
        }
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>