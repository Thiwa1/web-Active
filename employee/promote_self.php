<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];

// Check for existing offer
$stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
$stmt->execute([$user_id]);
$profile_id = $stmt->fetchColumn();

if (!$profile_id) { header("Location: profile.php?msg=complete_first"); exit(); }

$stmtOffer = $pdo->prepare("SELECT * FROM talent_offers WHERE seeker_link = ? LIMIT 1");
$stmtOffer->execute([$profile_id]);
$offer = $stmtOffer->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promote Myself | JobQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ck-editor__editable { min-height: 200px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h4 class="fw-bold mb-4">Promote Your Profile</h4>
                
                <form action="actions/save_promotion.php" method="POST">
                    <input type="hidden" name="offer_id" value="<?= $offer['id'] ?? '' ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Professional Headline</label>
                        <input type="text" name="headline" class="form-control" value="<?= htmlspecialchars($offer['headline'] ?? '') ?>" placeholder="e.g. Senior Java Developer | 5 Years Exp" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Experience (Years)</label>
                            <input type="number" name="experience_years" class="form-control" value="<?= $offer['experience_years'] ?? 0 ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expected Salary (LKR)</label>
                            <input type="number" name="expected_salary" class="form-control" value="<?= $offer['expected_salary'] ?? 0 ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Skills (Comma separated)</label>
                        <input type="text" name="skills_tags" class="form-control" value="<?= htmlspecialchars($offer['skills_tags'] ?? '') ?>" placeholder="PHP, MySQL, React..." required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">About Me / Pitch</label>
                        <?php
                            $desc = $offer['description'] ?? '';
                            // Robust decoding
                            for($i=0; $i<5; $i++) {
                                $d = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                if($d === $desc) break;
                                $desc = $d;
                            }
                        ?>
                        <textarea name="description" id="editor"><?= $desc ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Duration</label>
                        <select name="duration" class="form-select">
                            <option value="30">30 Days</option>
                            <option value="60">60 Days</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill">
                        <?= $offer ? 'Update Promotion' : 'Publish to Talent Pool' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function initPromoteEditor() {
        if (typeof tinymce === 'undefined') { setTimeout(initPromoteEditor, 100); return; }

        const el = document.querySelector('#editor');
        if (!el || el.offsetParent === null) { setTimeout(initPromoteEditor, 100); return; }

        if (tinymce.get('editor')) tinymce.get('editor').remove();

        tinymce.init({
            target: el,
            height: 300,
            menubar: false,
            branding: false,
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link removeformat',
            setup: function(editor) {
                editor.on('change keyup', function() {
                    editor.save();
                });
            }
        });
    }
    initPromoteEditor();
})();
</script>
</body>
</html>
