<?php
session_start();
require_once '../config/config.php';

// 1. Configuration & Security
$site_name = "JobQuest Pro"; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = false;

// 2. Fetch Current Profile Data
try {
    $stmt = $pdo->prepare("SELECT * FROM employee_profile_seeker WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    if (!$profile) {
        $pdo->prepare("INSERT INTO employee_profile_seeker (link_to_user) VALUES (?)")->execute([$user_id]);
        header("Refresh:0");
        exit();
    }
} catch (PDOException $e) {
    die("Database Connection Error");
}

// 3. Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['employee_full_name'];
    $initial_name = $_POST['employee_name_with_initial'];

    try {
        // Update basic text info
        $sql = "UPDATE employee_profile_seeker SET 
                employee_full_name = ?, 
                employee_name_with_initial = ? 
                WHERE link_to_user = ?";
        $pdo->prepare($sql)->execute([$full_name, $initial_name, $user_id]);

        // Handle BLOB Uploads (Images and Documents)
        $file_map = [
            'employee_cv' => 'cv_file',
            'employee_cover_letter' => 'cl_file',
            'employee_img' => 'img_file'
        ];

        foreach ($file_map as $column => $input_name) {
            if (!empty($_FILES[$input_name]['tmp_name'])) {
                $content = file_get_contents($_FILES[$input_name]['tmp_name']);
                $stmt = $pdo->prepare("UPDATE employee_profile_seeker SET $column = ? WHERE link_to_user = ?");
                $stmt->bindParam(1, $content, PDO::PARAM_LOB);
                $stmt->bindParam(2, $user_id);
                $stmt->execute();
            }
        }
        
        header("Location: profile.php?success=1");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Profile | <?= $site_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --accent-color: #2563eb; --body-bg: #f8fafc; }
        body { background-color: var(--body-bg); font-family: 'Inter', sans-serif; }
        
        .glass-card { background: white; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        
        .profile-pic-wrapper { position: relative; width: 150px; height: 150px; margin: 0 auto; }
        .profile-pic { width: 100%; height: 100%; object-fit: cover; border-radius: 40px; border: 5px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .edit-badge { position: absolute; bottom: -5px; right: -5px; background: var(--accent-color); color: white; padding: 10px; border-radius: 50%; cursor: pointer; border: 3px solid #fff; transition: 0.3s; }
        .edit-badge:hover { transform: scale(1.1); }

        .form-label { font-weight: 600; font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { padding: 12px 15px; border-radius: 12px; border: 1px solid #e2e8f0; background: #fdfdfd; }
        .form-control:focus { border-color: var(--accent-color); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }

        .doc-upload-zone { border: 2px dashed #cbd5e1; border-radius: 15px; padding: 20px; text-align: center; transition: 0.3s; background: #f8fafc; cursor: pointer; }
        .doc-upload-zone:hover { border-color: var(--accent-color); background: #eff6ff; }
        
        .sticky-sidebar { position: sticky; top: 2rem; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold mb-0">Edit Professional Profile</h3>
            <p class="text-muted small">Update your identity and career documents</p>
        </div>
        <a href="dashboard.php" class="btn btn-light border rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i> Dashboard
        </a>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="fas fa-check-circle me-2"></i> Profile updated successfully! Your matches are being recalculated.
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card glass-card p-4 text-center sticky-sidebar">
                    <div class="profile-pic-wrapper mb-4">
                        <?php if ($profile['employee_img']): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($profile['employee_img']) ?>" class="profile-pic" id="img-preview">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($profile['employee_full_name']) ?>&background=random&size=200" class="profile-pic" id="img-preview">
                        <?php endif; ?>
                        
                        <label for="img_file" class="edit-badge">
                            <i class="fas fa-camera"></i>
                            <input type="file" name="img_file" id="img_file" hidden accept="image/*" onchange="previewImage(event)">
                        </label>
                    </div>
                    
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($profile['employee_name_with_initial'] ?: 'Anonymous User') ?></h5>
                    <p class="text-muted small mb-4">Job Seeker ID: #SEEK-<?= str_pad($profile['id'], 4, '0', STR_PAD_LEFT) ?></p>
                    
                    <div class="d-flex justify-content-around bg-light rounded-4 p-3">
                        <div>
                            <span class="d-block fw-bold small"><?= $profile['employee_cv'] ? 'Yes' : 'No' ?></span>
                            <span class="text-muted x-small" style="font-size:0.7rem;">CV Attached</span>
                        </div>
                        <div class="border-start"></div>
                        <div>
                            <span class="d-block fw-bold small"><?= $profile['employee_cover_letter'] ? 'Yes' : 'No' ?></span>
                            <span class="text-muted x-small" style="font-size:0.7rem;">Cover Letter</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card glass-card p-4 p-md-5">
                    <h6 class="fw-bold mb-4 text-primary">Identity Details</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="employee_full_name" class="form-control" value="<?= htmlspecialchars($profile['employee_full_name']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Professional Name (with Initials)</label>
                            <input type="text" name="employee_name_with_initial" class="form-control" value="<?= htmlspecialchars($profile['employee_name_with_initial']) ?>" placeholder="E.g. A.B.C. Perera">
                        </div>
                    </div>

                    <h6 class="fw-bold mt-5 mb-4 text-primary">Core Application Documents</h6>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Curriculum Vitae (CV)</label>
                            <div class="doc-upload-zone" onclick="document.getElementById('cv_file').click()">
                                <i class="fas fa-file-pdf fa-2x mb-2 text-danger"></i>
                                <p class="small text-muted mb-0">Click to upload PDF</p>
                                <input type="file" name="cv_file" id="cv_file" hidden>
                                <?php if ($profile['employee_cv']): ?>
                                    <div class="badge bg-success-subtle text-success mt-2">Document Stored</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cover Letter</label>
                            <div class="doc-upload-zone" onclick="document.getElementById('cl_file').click()">
                                <i class="fas fa-file-word fa-2x mb-2 text-primary"></i>
                                <p class="small text-muted mb-0">Click to upload Document</p>
                                <input type="file" name="cl_file" id="cl_file" hidden>
                                <?php if ($profile['employee_cover_letter']): ?>
                                    <div class="badge bg-success-subtle text-success mt-2">Document Stored</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 border-top pt-4">
                        <button type="submit" class="btn btn-primary px-5 py-3 fw-bold rounded-pill shadow-sm">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Live Image Preview Function
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('img-preview');
            output.src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>