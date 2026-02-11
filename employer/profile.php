<?php
session_start();
require_once '../config/config.php';

// 1. Security Check
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        die("Session Expired");
    }
    header("Location: ../login.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Detect AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Direct Access Prevention: Redirect to Dashboard if not AJAX
if (!$isAjax) {
    $queryParams = $_GET;
    $queryString = http_build_query($queryParams);
    $redirectUrl = 'dashboard.php?page=profile_settings' . ($queryString ? '&' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit();
}

// 3. Fetch Profile Data
try {
    $stmt = $pdo->prepare("SELECT * FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// 4. Calculate Profile Strength
$total_fields = 8; 
$filled = 0;
if($profile) {
    if(!empty($profile['employer_name'])) $filled++;
    if(!empty($profile['employer_logo']) || !empty($profile['logo_path'])) $filled++;
    if(!empty($profile['employer_about_company'])) $filled++;
    if(!empty($profile['employer_mobile_no'])) $filled++;
    if(!empty($profile['employer_address_1'])) $filled++;
    if(!empty($profile['employer_BR']) || !empty($profile['br_path'])) $filled++;
    if(!empty($profile['employer_landline'])) $filled++;
    if(!empty($profile['employer_whatsapp_no'])) $filled++;
}
$strength = ($filled / $total_fields) * 100;
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        
        <div class="col-lg-8">
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-primary rounded-4 border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4">
                    <i class="fas fa-magic-wand-sparkles fs-4"></i>
                    <div>
                        <div class="fw-800">Complete your profile</div>
                        <div class="small opacity-75">Strong profiles get 60% more qualified applicants.</div>
                    </div>
                </div>
            <?php endif; ?>

            <form action="../actions/update_employer_profile.php" method="POST" enctype="multipart/form-data">
                <div class="glass-panel" style="background: white; border-radius: 24px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); overflow: hidden;">
                    <div class="section-header d-flex justify-content-between align-items-center" style="padding: 25px 35px; border-bottom: 1px solid #f1f5f9; background: #fff;">
                        <div>
                            <h4 class="fw-800 mb-1 text-dark">Company Brand Center</h4>
                            <p class="text-muted small mb-0">Control how your brand appears to potential talent.</p>
                        </div>
                        <div class="text-end" style="min-width: 150px;">
                            <div class="small fw-700 mb-1">Profile Strength: <?= round($strength) ?>%</div>
                            <div class="progress" style="height: 6px; border-radius: 10px; background: #e2e8f0;">
                                <div class="progress-bar bg-primary" style="width: <?= $strength ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 p-md-5">
                        <div class="row g-4 mb-4">
                            <div class="col-md-7">
                                <label class="form-label" style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Official Company Name</label>
                                <input type="text" name="employer_name" id="f_name" class="form-control form-control-lg fw-700 shadow-sm" value="<?= htmlspecialchars($profile['employer_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Landline Number</label>
                                <input type="text" name="employer_landline" class="form-control form-control-lg shadow-sm" value="<?= htmlspecialchars($profile['employer_landline'] ?? '') ?>" placeholder="Optional">
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-4 bg-light">
                                    <label class="form-label" style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;"><i class="fas fa-image me-1"></i> Brand Logo</label>
                                    <input type="file" name="employer_logo" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-4 bg-light">
                                    <label class="form-label" style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;"><i class="fas fa-file-pdf me-1"></i> Legal Document (BR)</label>
                                    <input type="file" name="employer_BR" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-12">
                                <label class="form-label" style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Headquarters Address</label>
                                <input type="text" name="employer_address_1" class="form-control mb-2 shadow-sm" placeholder="Street" value="<?= htmlspecialchars($profile['employer_address_1'] ?? '') ?>">
                                <div class="row g-2">
                                    <div class="col-md-6"><input type="text" id="f_loc" name="employer_address_2" class="form-control shadow-sm" placeholder="City" value="<?= htmlspecialchars($profile['employer_address_2'] ?? '') ?>"></div>
                                    <div class="col-md-6"><input type="text" name="employer_address_3" class="form-control shadow-sm" placeholder="Region" value="<?= htmlspecialchars($profile['employer_address_3'] ?? '') ?>"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Company Bio & Culture</label>
                            <div class="editor-container shadow-sm rounded-3 overflow-hidden">
                                <!-- Ensure raw HTML is output here for CKEditor to pick up -->
                                <?php
                                    $about = $profile['employer_about_company'] ?? '';
                                    // Robust decoding for potential multi-escaped legacy data
                                    for($i=0; $i<5; $i++) {
                                        $d = html_entity_decode($about, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                        if($d === $about) break;
                                        $about = $d;
                                    }
                                ?>
                                <textarea id="about_company_editor" name="employer_about_company"><?= htmlspecialchars($about) ?></textarea>
                                <div id="editor-error" class="text-danger small mt-2 d-none">
                                    <i class="fas fa-exclamation-triangle"></i> Editor resources could not be loaded. Please check your internet connection.
                                </div>
                            </div>
                        </div>

                        <script>
                        (function() {
                            function initProfileEditor() {
                                if (typeof tinymce === 'undefined') { setTimeout(initProfileEditor, 100); return; }

                                const el = document.querySelector('#about_company_editor');
                                if (!el || el.offsetParent === null) { setTimeout(initProfileEditor, 100); return; }

                                if(tinymce.get('about_company_editor')) tinymce.get('about_company_editor').remove();

                                tinymce.init({
                                    target: el,
                                    height: 300,
                                    menubar: false,
                                    branding: false,
                                    toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link removeformat',
                                    setup: function(editor) {
                                        editor.on('change keyup', function() {
                                            editor.save(); // Sync to textarea
                                        });
                                    }
                                });
                            }
                            initProfileEditor();
                        })();
                        </script>

                        <div class="d-flex justify-content-end pt-4 border-top">
                            <button type="submit" class="btn btn-primary px-5 fw-800 rounded-pill py-2 shadow">
                                <i class="fas fa-save me-2"></i> Update Brand Profile
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="preview-sticky" style="position: sticky; top: 20px;">
                <div class="identity-preview shadow-sm mb-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 20px; padding: 30px; color: white; text-align: center;">
                    <div class="preview-logo-circle" style="width: 100px; height: 100px; background: white; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid rgba(255,255,255,0.2);">
                        <?php if(!empty($profile['logo_path'])): ?>
                            <img src="../<?= $profile['logo_path'] ?>" class="w-100 h-100" style="object-fit: cover;">
                        <?php elseif(!empty($profile['employer_logo'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($profile['employer_logo']) ?>" class="w-100 h-100" style="object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-building fa-2x text-primary"></i>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-800 mb-1" id="p_name"><?= $profile['employer_name'] ?? 'Company Name' ?></h5>
                    <p class="small opacity-75 mb-3" id="p_loc"><?= $profile['employer_address_2'] ?? 'Location' ?></p>
                    <div class="badge bg-white text-primary rounded-pill px-3 py-2 fw-800">VERIFIED EMPLOYER</div>
                </div>
                
                <div class="p-4 bg-white rounded-4 border">
                    <h6 class="fw-800 mb-3 small text-uppercase"><i class="fas fa-lightbulb text-warning me-2"></i> Branding Tips</h6>
                    <ul class="list-unstyled small text-muted mb-0" style="line-height: 1.8;">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> High-res logo increases trust.</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Describe your culture (WFH, Benefits).</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> Verification takes 24-48 hours.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
