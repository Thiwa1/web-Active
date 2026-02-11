<?php
session_start();
require_once '../config/config.php';

// 1. Security & Identity
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];

// Direct Access Prevention: Redirect to Dashboard if not AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $queryParams = $_GET;
    $queryString = http_build_query($queryParams);
    $redirectUrl = 'dashboard.php?page=post_job' . ($queryString ? '&' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit();
}

try {
    // Fetch Profile for the Sidebar/Header
    $stmt = $pdo->prepare("SELECT id, employer_name, employer_logo FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    if (!$profile) { header("Location: profile.php?msg=complete_profile"); exit(); }

    // Fetch Reference Data
    $districts = $pdo->query("SELECT District_name FROM district_table ORDER BY District_name ASC")->fetchAll();
    $categories = $pdo->query("SELECT Description FROM job_category_table ORDER BY Description ASC")->fetchAll();
    $industries = $pdo->query("SELECT Industry_name FROM Industry_Setting ORDER BY Industry_name ASC")->fetchAll();
    $pricingRules = $pdo->query("SELECT Unit_of_add, selling_price FROM Price_setting ORDER BY Unit_of_add ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Check Promotion Period
    $promoStmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'promotion_period' LIMIT 1");
    $isPromo = ($promoStmt->fetchColumn() == '1');

} catch (PDOException $e) { die("System Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Vacancy Pro | Enterprise Suite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>

<div class="container py-5">
    <style>
        .tox-notifications-container { display: none !important; }
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --brand-primary: #4f46e5;
            --brand-success: #10b981;
            --bg-body: #f1f5f9;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }

        /* Stepper UI */
        .step-indicator {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .step-dot {
            width: 35px; height: 35px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem;
            transition: 0.3s;
        }
        .step-item.active .step-dot { background: var(--brand-primary); color: white; transform: scale(1.1); }
        .step-item.active .step-label { color: var(--brand-primary); font-weight: 700; }

        /* Card Refinement */
        .glass-card {
            background: white;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.05);
            padding: 35px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04);
        }

        .form-label { font-weight: 700; font-size: 0.85rem; color: #475569; margin-bottom: 8px; }
        .form-control, .form-select {
            border-radius: 12px; border: 1px solid #e2e8f0; padding: 12px 16px; transition: 0.2s;
        }
        .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }

        /* Pricing Box */
        .pricing-summary {
            background: #f8fafc;
            border-radius: 20px;
            padding: 25px;
            border: 2px dashed #e2e8f0;
        }

        /* Float Preview */
        .preview-sticky { position: sticky; top: 20px; }
        
        #preview-frame {
            border: 1px solid #e2e8f0; border-radius: 20px;
            overflow: hidden; background: white;
        }

        /* Ensure Preview has basic styling for lists */
        #p_desc ul { list-style-type: disc; padding-left: 20px; }
        #p_desc ol { list-style-type: decimal; padding-left: 20px; }
    </style>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="fw-800 mb-0">Create Vacancy</h3>
                <a href="manage_jobs.php" class="btn btn-link text-muted text-decoration-none small fw-600">
                    <i class="fas fa-times me-1"></i> Exit Editor
                </a>
            </div>

            <div class="step-indicator d-flex justify-content-around">
                <div class="step-item active d-flex align-items-center gap-2">
                    <div class="step-dot">1</div>
                    <span class="step-label small">Details</span>
                </div>
                <div class="step-item d-flex align-items-center gap-2">
                    <div class="step-dot">2</div>
                    <span class="step-label small">Content</span>
                </div>
                <div class="step-item d-flex align-items-center gap-2">
                    <div class="step-dot">3</div>
                    <span class="step-label small">Checkout</span>
                </div>
            </div>

            <form action="../actions/save_vacancy.php" method="POST" enctype="multipart/form-data" id="jobForm">
                <div class="glass-card">
                    <div class="row g-4 mb-5">
                        <div class="col-12">
                            <label class="form-label">VACANCY TITLE</label>
                            <input type="text" name="Job_role" id="f_role" class="form-control form-control-lg fw-700" placeholder="e.g. Senior Software Engineer" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">JOB TYPE</label>
                            <select name="job_type" id="f_type" class="form-select" required>
                                <option value="Full Time">Full Time</option>
                                <option value="Part Time">Part Time</option>
                                <option value="Online">Online</option>
                                <option value="School Leaver">School Leaver</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CATEGORY</label>
                            <select name="Job_category" class="form-select" required>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['Description'] ?>"><?= $cat['Description'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">INDUSTRY</label>
                            <select name="Industry" class="form-select" required>
                                <?php foreach($industries as $ind): ?>
                                    <option value="<?= $ind['Industry_name'] ?>"><?= $ind['Industry_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label class="form-label">OPENING DATE</label>
                            <input type="date" name="Opening_date" id="f_opening" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CLOSING DATE</label>
                            <input type="date" name="Closing_date" id="f_closing" class="form-control" required>
                            <div class="invalid-feedback">Closing date must be after opening date.</div>
                        </div>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label class="form-label">TARGET DISTRICT</label>
                            <select name="District" id="f_district" class="form-select" required>
                                <option value="">Select District</option>
                                <?php foreach($districts as $d): ?>
                                    <option value="<?= $d['District_name'] ?>"><?= $d['District_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CITY</label>
                            <select name="City" id="f_city" class="form-select" disabled required>
                                <option value="">Pick District First</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">JOB DESCRIPTION</label>
                        <textarea id="f_editor" name="job_description"></textarea>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-primary">UPLOAD JOB FLYER / IMAGE</label>
                        <div class="border rounded-4 p-3 bg-light">
                            <input type="file" name="job_banner" id="f_img" class="form-control" accept="image/*">
                            <div class="form-text mt-2">Recommended: JPEG or PNG, max 2MB. This will be shown to candidates.</div>
                        </div>
                    </div>

                    <div class="row g-4 mb-5 p-4 rounded-4" style="background: #f8fafc;">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="f_portal" name="Apply_by_system" checked>
                                <label class="form-check-label fw-700" for="f_portal">Accept Portal Applications</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">HR EMAIL</label>
                            <input type="email" name="Apply_by_email_address" class="form-control" placeholder="careers@company.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WHATSAPP CHANNEL</label>
                            <input type="text" name="apply_WhatsApp_No" class="form-control" placeholder="07XXXXXXXX">
                        </div>
                    </div>

                    <div class="pricing-summary">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h6 class="fw-800 mb-3"><i class="fas fa-tags text-primary me-2"></i> Ad Inventory</h6>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <input type="number" name="unit_count" id="f_units" class="form-control w-25 fw-800" value="1" min="1">
                                    <span class="text-muted small fw-600">Ad Slots to purchase</span>
                                </div>
                                <div id="bulk_msg" class="badge bg-success-subtle text-success p-2 px-3 rounded-pill d-none">
                                    <i class="fas fa-sparkles me-1"></i> Bulk pricing applied!
                                </div>
                                
                                <div class="mt-4">
                                    <?php if ($isPromo): ?>
                                        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3">
                                            <i class="fas fa-gift fs-2"></i>
                                            <div>
                                                <h6 class="fw-bold mb-1">Promotional Period Active!</h6>
                                                <p class="small mb-0 opacity-75">You can post this vacancy for free. No payment slip required.</p>
                                            </div>
                                        </div>
                                        <input type="hidden" name="is_promo" value="1">
                                    <?php else: ?>
                                        <label class="form-label text-danger">UPLOAD TRANSFER SLIP</label>
                                        <input type="file" name="payment_slip" class="form-control border-danger" required>
                                        <div class="mt-3 p-3 bg-white rounded-3 border small">
                                            <div class="fw-bold text-dark mb-2">Transfer to:</div>
                                            <div id="bank_details_area">
                                                <div class="spinner-border spinner-border-sm text-muted"></div> Loading accounts...
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-5 text-end border-start">
                                <div class="text-muted small fw-600">Total Investment</div>
                                <div class="h2 fw-800 text-dark mb-0" id="f_total">0.00</div>
                                <div class="text-muted extra-small">Includes all applicable taxes</div>
                                <input type="hidden" name="calculated_total" id="f_hidden_total">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-800 py-3 mt-4 shadow-lg border-0">
                        <i class="fas fa-rocket me-2"></i> Publish Vacancy Now
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-4 d-none d-lg-block">
            <div class="preview-sticky">
                <div class="small fw-800 text-muted mb-3 uppercase letter-spacing-1">Live Ad Preview</div>
                <div id="preview-frame" class="shadow-sm">
                    <div class="p-3 bg-dark text-white d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary" style="width:10px; height:10px;"></div>
                        <span class="extra-small fw-700">MOBILE VIEW SIMULATION</span>
                    </div>
                    <div class="p-4" style="height: 600px; overflow-y: auto;">
                        <img id="p_img_preview" src="" class="img-fluid rounded-3 mb-3 d-none" alt="Job Flyer">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <?php if($profile['employer_logo']): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($profile['employer_logo']) ?>" width="50" class="rounded-3">
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-0 fw-800" id="p_role">Job Title</h6>
                                <div class="text-primary extra-small fw-700"><?= $profile['employer_name'] ?></div>
                            </div>
                        </div>
                        <div class="badge bg-light text-muted mb-3"><i class="fas fa-map-marker-alt me-1"></i> <span id="p_loc">Location</span></div>
                        <div class="badge bg-info text-white mb-3" id="p_type_badge">Full Time</div>
                        <hr>
                        <div id="p_desc" class="small text-muted" style="line-height: 1.6;">Your job description will appear here...</div>
                    </div>
                </div>
                <div class="mt-3 p-3 bg-white rounded-4 border small">
                    <i class="fas fa-lightbulb text-warning me-2"></i> <strong>Pro Tip:</strong> Use bullet points in your description to increase applicant readability by 40%.
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function() {
    // Sync Fields to Preview
    $('#f_role').on('input', function() { $('#p_role').text($(this).val() || 'Job Title'); });
    $('#f_district').on('change', function() { $('#p_loc').text($(this).val() || 'Location'); });
    $('#f_type').on('change', function() { $('#p_type_badge').text($(this).val()); });

    // Pricing Engine
    var pricing = <?= json_encode($pricingRules) ?>;
    var isPromo = <?= $isPromo ? 'true' : 'false' ?>;

    function runBilling() {
        var units = parseInt($('#f_units').val()) || 1;
        var rate = pricing.length > 0 ? pricing[0].selling_price : 0;
        
        pricing.forEach(rule => {
            if (units >= parseInt(rule.Unit_of_add)) rate = parseFloat(rule.selling_price);
        });

        var total = units * rate;

        if (isPromo) {
            total = 0;
            $('#f_total').text('0.00 LKR (Promo)');
            $('#f_total').addClass('text-success');
        } else {
            $('#f_total').text(total.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' LKR');
            $('#f_total').removeClass('text-success');
        }

        $('#f_hidden_total').val(total);
        units > 1 ? $('#bulk_msg').removeClass('d-none') : $('#bulk_msg').addClass('d-none');
    }

    $('#f_units').on('input change', runBilling);
    runBilling();

    // City Fetcher
    $('#f_district').on('change', function() {
        var d = $(this).val();
        if(!d) return;
        $.post('../actions/fetch_locations.php', {district_name: d}, function(res) {
            var cities = JSON.parse(res);
            $('#f_city').empty().prop('disabled', false);
            cities.forEach(c => $('#f_city').append(`<option value="${c.City}">${c.City}</option>`));
        });
    });

    // Load Bank Accounts
    $.get('../actions/fetch_banks.php', function(data) {
        var html = '';
        if(data.length === 0) {
            html = '<div class="text-danger">No bank accounts configured. Contact Admin.</div>';
        } else {
            data.forEach(acc => {
                html += `<div class="mb-2 pb-2 border-bottom last-no-border">
                    <div class="fw-bold text-primary">${acc.bank_name}</div>
                    <div class="font-monospace">${acc.account_number}</div>
                    <div class="text-muted" style="font-size:0.75rem">${acc.branch_name} (${acc.branch_code})</div>
                </div>`;
            });
        }
        $('#bank_details_area').html(html);
    }, 'json');

    // Date Verification Logic
    var today = new Date().toISOString().split('T')[0];
    var f_opening = document.getElementById('f_opening');
    var f_closing = document.getElementById('f_closing');

    f_opening.value = today;
    f_opening.min = today;
    
    var closeDate = new Date();
    closeDate.setDate(closeDate.getDate() + 30);
    f_closing.value = closeDate.toISOString().split('T')[0];

    function validateDates() {
        if(f_closing.value < f_opening.value) {
            f_closing.setCustomValidity("Closing date cannot be before opening date");
            f_closing.classList.add('is-invalid');
        } else {
            f_closing.setCustomValidity("");
            f_closing.classList.remove('is-invalid');
        }
    }

    f_opening.addEventListener('change', function() {
        f_closing.min = this.value;
        validateDates();
    });
    
    f_closing.addEventListener('change', validateDates);
    validateDates();
	
	// Image Preview Logic
    $('#f_img').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#p_img_preview').attr('src', e.target.result).removeClass('d-none');
            }
            reader.readAsDataURL(file);
        }
    });

    // --- CHANGED: TinyMCE Initialization (Word-like Editor) ---
    function initJobEditor() {
        if (typeof tinymce === 'undefined') {
            setTimeout(initJobEditor, 100);
            return;
        }

        const editorEl = document.querySelector('#f_editor');
        // Wait for visibility (dashboard fade-in)
        if (!editorEl || editorEl.offsetParent === null) {
            setTimeout(initJobEditor, 100);
            return;
        }

        // Remove existing instance if any (prevent double-init)
        if (tinymce.get('f_editor')) {
            tinymce.get('f_editor').remove();
        }

        tinymce.init({
                target: editorEl,
                height: 400,
                menubar: true,
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor backcolor | link image table | removeformat',
                content_style: 'body { font-family: "Plus Jakarta Sans", sans-serif; font-size: 14px; color: #333; }',
                branding: false,
                promotion: false,

                // Sync with Live Preview on keypress
                setup: function (editor) {
                    editor.on('keyup change', function () {
                        var content = editor.getContent();
                        $('#p_desc').html(content || 'Your job description will appear here...');
                        // Ensure textarea updates for form submission
                        editor.save();
                    });
                }
            });
    }
    initJobEditor();
})();
</script>
</div>
<?php include '../layout/ui_helpers.php'; ?>
</body>
</html>
