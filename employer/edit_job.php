<?php
session_start();
require_once '../config/config.php';

// 1. Security & Identity
if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header("Location: ../login.php"); exit();
}

$job_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

// Direct Access Prevention: Redirect to Dashboard if not AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $queryParams = $_GET;
    $queryString = http_build_query($queryParams);
    $redirectUrl = 'dashboard.php?page=edit_job' . ($queryString ? '&' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit();
}

if (!$job_id) { header("Location: manage_jobs.php"); exit(); }

try {
    // Optimized: Fetch profile and job ownership in one go if possible, 
    // but keeping separate for clarity in this structure.
    $stmt = $pdo->prepare("SELECT id, employer_name, employer_logo FROM employer_profile WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM advertising_table WHERE id = ? AND link_to_employer_profile = ?");
    $stmt->execute([$job_id, $profile['id']]);
    $job = $stmt->fetch();

    if (!$job) { die("Access Denied."); }

    // Fetch Master Data
    $districts = $pdo->query("SELECT id, District_name FROM district_table")->fetchAll();
    $categories = $pdo->query("SELECT Description FROM job_category_table")->fetchAll();
    $industries = $pdo->query("SELECT Industry_name FROM Industry_Setting")->fetchAll();

    // Fetch cities for current district
    $stmt = $pdo->prepare("SELECT City FROM city_table c JOIN district_table d ON c.City_link = d.id WHERE d.District_name = ?");
    $stmt->execute([$job['District']]);
    $current_cities = $stmt->fetchAll();

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vacancy | <?= htmlspecialchars($profile['employer_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --brand-primary: #4f46e5;
            --bg-body: #f8fafc;
            --card-border: rgba(226, 232, 240, 0.8);
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            scroll-behavior: smooth;
        }

        .editor-header {
            background: white;
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.8);
        }

        .step-card {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--card-border);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        .section-nav {
            position: sticky;
            top: 100px;
        }

        .nav-indicator {
            padding: 10px 15px;
            border-radius: 10px;
            color: #64748b;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.2s;
            font-weight: 500;
        }

        .nav-indicator.active {
            background: var(--brand-primary);
            color: white;
        }

        .ck-editor__editable { min-height: 300px; border-radius: 0 0 12px 12px !important; }

        .btn-update {
            background: var(--brand-primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        .img-preview-container {
            width: 100%;
            height: 200px;
            border: 2px dashed #e2e8f0;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8fafc;
        }

        .img-preview-container img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>

<header class="editor-header">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <a href="manage_jobs.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h5 class="fw-800 mb-0">Editing Listing <span class="text-primary">#<?= $job['id'] ?></span></h5>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-light fw-600 rounded-pill" onclick="showPreview()">
                <i class="fas fa-eye me-2"></i> Preview
            </button>
            <button form="editJobForm" type="submit" class="btn btn-update">Update Live Ad</button>
        </div>
    </div>
</header>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-3 d-none d-lg-block">
            <div class="section-nav">
                <p class="small fw-bold text-muted mb-3 text-uppercase">Form Sections</p>
                <a href="#section-basic" class="nav-indicator active mb-2"><i class="fas fa-info-circle"></i> Basic Info</a>
                <a href="#section-location" class="nav-indicator mb-2"><i class="fas fa-map-pin"></i> Location</a>
                <a href="#section-content" class="nav-indicator mb-2"><i class="fas fa-pen-nib"></i> Ad Content</a>
                <a href="#section-media" class="nav-indicator mb-2"><i class="fas fa-photo-film"></i> Media & Settings</a>
            </div>
        </div>

        <div class="col-lg-9">
            <form id="editJobForm" action="../actions/update_vacancy.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">

                <div id="section-basic" class="step-card">
                    <h5 class="fw-800 mb-4">1. Basic Information</h5>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Job Role / Designation</label>
                            <input type="text" name="Job_role" id="job_role_input" class="form-control form-control-lg rounded-3" value="<?= htmlspecialchars($job['Job_role']) ?>" placeholder="e.g. Senior Software Engineer" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Job Type</label>
                            <select name="job_type" id="job_type_input" class="form-select rounded-3" required>
                                <?php
                                $types = ['Full Time', 'Part Time', 'Online', 'School Leaver'];
                                foreach ($types as $type) {
                                    $selected = ($job['job_type'] ?? 'Full Time') === $type ? 'selected' : '';
                                    echo "<option value=\"$type\" $selected>$type</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Job Category</label>
                            <select name="Job_category" id="job_cat_input" class="form-select rounded-3">
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['Description'] ?>" <?= $job['Job_category'] == $cat['Description'] ? 'selected' : '' ?>><?= $cat['Description'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Industry</label>
                            <select name="Industry" class="form-select rounded-3">
                                <?php foreach($industries as $ind): ?>
                                    <option value="<?= $ind['Industry_name'] ?>" <?= $job['Industry'] == $ind['Industry_name'] ? 'selected' : '' ?>><?= $ind['Industry_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="section-location" class="step-card">
                    <h5 class="fw-800 mb-4">2. Location Details</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">District</label>
                            <select name="District" id="district_select" class="form-select rounded-3" required>
                                <?php foreach($districts as $d): ?>
                                    <option value="<?= $d['District_name'] ?>" <?= $job['District'] == $d['District_name'] ? 'selected' : '' ?>><?= $d['District_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">City</label>
                            <select name="City" id="city_select" class="form-select rounded-3" required>
                                <?php foreach($current_cities as $c): ?>
                                    <option value="<?= $c['City'] ?>" <?= $job['City'] == $c['City'] ? 'selected' : '' ?>><?= $c['City'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="section-content" class="step-card">
                    <h5 class="fw-800 mb-4">3. Ad Content</h5>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Job Description & Requirements</label>
                        <textarea id="job_desc_editor" name="job_description"><?= $job['job_description'] ?></textarea>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Opening Date</label>
                            <input type="date" name="Opening_date" class="form-control" value="<?= $job['Opening_date'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Closing Date</label>
                            <input type="date" name="Closing_date" class="form-control border-primary" value="<?= $job['Closing_date'] ?>" required>
                        </div>
                    </div>
                </div>

                <div id="section-media" class="step-card">
                    <h5 class="fw-800 mb-4">4. Media & Application Channel</h5>
                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Update Ad Banner</label>
                            <input type="file" name="Img" class="form-control rounded-3" accept="image/*">
                        </div>
                        <div class="col-12 bg-light p-3 rounded-4">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="Apply_by_system" id="applySystem" value="1" <?= $job['Apply_by_system'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-700" for="applySystem">Receive applications through Dashboard</label>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="email" name="Apply_by_email_address" class="form-control" value="<?= htmlspecialchars($job['Apply_by_email_address']) ?>" placeholder="Contact Email">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="apply_WhatsApp_No" class="form-control" value="<?= htmlspecialchars($job['apply_WhatsApp_No']) ?>" placeholder="WhatsApp Number">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-body p-5">
                <div class="text-center mb-4">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($profile['employer_name']) ?>&background=4f46e5&color=fff" class="rounded-circle mb-3" width="70">
                    <h3 id="pre_role" class="fw-800 mb-1"></h3>
                    <p class="text-primary fw-600 mb-0"><?= htmlspecialchars($profile['employer_name']) ?></p>
                </div>
                
                <div class="d-flex justify-content-center gap-4 py-3 border-top border-bottom mb-4">
                    <span class="small text-muted"><i class="fas fa-location-dot me-2"></i><span id="pre_loc"></span></span>
                    <span class="small text-muted"><i class="fas fa-briefcase me-2"></i><span id="pre_cat"></span></span>
                </div>

                <div class="text-center mb-3">
                    <span class="badge bg-info text-white" id="pre_type"></span>
                </div>

                <div id="pre_desc" class="rich-text-content" style="max-height: 400px; overflow-y: auto;"></div>
                
                <div class="mt-5 text-center">
                    <button type="button" class="btn btn-secondary px-5 rounded-pill" data-bs-dismiss="modal">Back to Editor</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let jobEditor;
    ClassicEditor.create(document.querySelector('#job_desc_editor')).then(editor => { jobEditor = editor; });

    function showPreview() {
        $('#pre_role').text($('#job_role_input').val() || "Untitled Position");
        $('#pre_loc').text($('#city_select').val() + ", " + $('#district_select').val());
        $('#pre_cat').text($('#job_cat_input').val());
        $('#pre_type').text($('#job_type_input').val());
        $('#pre_desc').html(jobEditor.getData() || "No content provided.");
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    // Dynamic Location
    $('#district_select').on('change', function() {
        $.post('../actions/fetch_locations.php', {district_name: $(this).val()}, function(data) {
            let cities = JSON.parse(data);
            $('#city_select').empty();
            cities.forEach(c => $('#city_select').append(`<option value="${c.City}">${c.City}</option>`));
        });
    });

    // Sidebar active link on scroll
    $(window).scroll(function() {
        let scrollPos = $(document).scrollTop() + 150;
        $('.step-card').each(function() {
            let currLink = $(this);
            let id = currLink.attr('id');
            if (currLink.offset().top <= scrollPos && currLink.offset().top + currLink.height() > scrollPos) {
                $('.nav-indicator').removeClass("active");
                $(`.nav-indicator[href="#${id}"]`).addClass("active");
            }
        });
    });
</script>
</body>
</html>
