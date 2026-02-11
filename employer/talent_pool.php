<?php
session_start();
require_once '../config/config.php';
require_once '../config/security.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header("Location: ../login.php"); exit();
}

// Direct Access Prevention: Redirect to Dashboard if not AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $queryParams = $_GET;
    $queryString = http_build_query($queryParams);
    $redirectUrl = 'dashboard.php?page=talent_pool' . ($queryString ? '&' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit();
}

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    // UPDATED QUERY: Fetch img_path and cv_path as well
    $sql = "
        SELECT t.*, p.employee_full_name, p.employee_img, p.img_path, p.cv_path,
               u.mobile_number, u.user_email, u.WhatsApp_number, u.country, u.male_female
        FROM talent_offers t
        JOIN employee_profile_seeker p ON t.seeker_link = p.id
        JOIN user_table u ON p.link_to_user = u.id
        WHERE t.is_active = 1 AND t.expiry_date >= CURDATE()
    ";

    $params = [];
    if (!empty($keyword)) {
        $sql .= " AND (t.headline LIKE ? OR t.skills_tags LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }

    $sql .= " ORDER BY t.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $talents = $stmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">Talent Marketplace</h2>
            <p class="text-muted">Directly connect with candidates open to opportunities.</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-6 mx-auto">
            <form class="input-group shadow-sm" onsubmit="event.preventDefault(); loadContent('talent_pool?q='+this.q.value)">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0 py-3" placeholder="Search by skill (e.g. Java) or title..." value="<?= htmlspecialchars($keyword) ?>">
                <button class="btn btn-primary px-4 fw-bold">Find Talent</button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if(empty($talents)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No active talent offers found matching your criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach($talents as $t): 
                // Robust Image Resolution
                $img_url = '';
                // Check physical file existence relative to this script
                if (!empty($t['img_path']) && file_exists('../' . $t['img_path'])) {
                    $img_url = '../' . $t['img_path'];
                } elseif (!empty($t['employee_img'])) {
                    $img_url = 'actions/view_image.php?id='.$t['seeker_link'];
                } else {
                    $img_url = ''; // Will trigger fallback icon
                }

                // Create lightweight object for JS
                $t_json = $t;
                $t_json['has_img'] = !empty($img_url);
                $t_json['img_url'] = $img_url;

                $t_json['description'] = clean_html($t['description']);
                unset($t_json['employee_img']); // Remove blob from JSON
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="talent-card bg-white rounded-4 border p-4 h-100 shadow-sm d-flex flex-column transition-hover" style="transition:0.2s;">
                        <div class="d-flex align-items-center mb-3">
                            <?php if($img_url): ?>
                                <img src="<?= htmlspecialchars($img_url) ?>" class="avatar me-3 rounded-circle" style="width:60px;height:60px;object-fit:cover;">
                            <?php else: ?>
                                <div class="avatar me-3 bg-light d-flex align-items-center justify-content-center text-muted rounded-circle" style="width:60px;height:60px;"><i class="fas fa-user fa-lg"></i></div>
                            <?php endif; ?>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($t['employee_full_name']) ?></h6>
                                <span class="small text-muted"><?= $t['experience_years'] ?> Years Experience</span>
                            </div>
                        </div>

                        <h5 class="fw-bold text-primary mb-3"><?= htmlspecialchars($t['headline']) ?></h5>
                        
                        <div class="mb-3">
                            <?php 
                                $skills = explode(',', $t['skills_tags']);
                                foreach(array_slice($skills, 0, 5) as $s) echo '<span class="badge bg-light text-dark border me-1">'.trim(htmlspecialchars($s)).'</span>';
                            ?>
                        </div>

                        <div class="text-muted small flex-grow-1 mb-3" style="line-height: 1.6;">
                            <?php 
                                $clean_desc = strip_tags($t['description']); 
                                echo htmlspecialchars(substr($clean_desc, 0, 150)) . '...';
                            ?>
                        </div>

                        <div class="d-grid mt-3">
                            <button class="btn btn-outline-primary rounded-pill fw-bold"
                                onclick="viewProfile(<?= htmlspecialchars(json_encode($t_json)) ?>)">
                                View Profile
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" style="z-index: 2000;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <div class="d-flex align-items-center mb-4">
                    <img id="m_img" src="" class="avatar me-3 rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                    <div>
                        <h4 class="fw-bold mb-1" id="m_name">Name</h4>
                        <div class="text-muted small" id="m_headline">Headline</div>
                        <div class="small mt-1 text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i> <span id="m_country">Country</span> &bull; 
                            <span id="m_gender">Gender</span>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted fw-bold">EXPERIENCE</small>
                            <div class="fw-bold text-dark" id="m_exp">0 Years</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted fw-bold">EXPECTED SALARY</small>
                            <div class="fw-bold text-dark" id="m_salary">0 LKR</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="p-3 border rounded-3 bg-white">
                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-address-book me-2"></i>Contact Details</h6>
                            <div class="row g-3">
                                <div class="col-sm-4">
                                    <small class="text-muted d-block">Mobile</small>
                                    <span class="fw-semibold text-dark" id="m_mobile"></span>
                                </div>
                                <div class="col-sm-4">
                                    <small class="text-muted d-block">WhatsApp</small>
                                    <span class="fw-semibold text-dark" id="m_whatsapp_num"></span>
                                </div>
                                <div class="col-sm-4">
                                    <small class="text-muted d-block">Email</small>
                                    <span class="fw-semibold text-dark" id="m_email_txt"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-2">About Candidate</h6>
                <div id="m_desc" class="text-muted small mb-4" style="line-height: 1.7;"></div>

                <h6 class="fw-bold text-primary mb-2">Skills</h6>
                <div id="m_skills"></div>

                <div class="mt-4 border-top pt-3 text-center">
                    <a id="m_cv_link" href="#" target="_blank" class="btn btn-dark rounded-pill px-4 fw-bold">
                        <i class="fas fa-file-download me-2"></i> Download CV
                    </a>
                </div>

                <div id="m_docs_area" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script>
    function viewProfile(data) {
    document.getElementById('m_name').textContent = data.employee_full_name;
    document.getElementById('m_headline').textContent = data.headline;
    document.getElementById('m_exp').textContent = data.experience_years + ' Years';
    document.getElementById('m_salary').textContent = 'Rs. ' + parseInt(data.expected_salary).toLocaleString();
    
    document.getElementById('m_country').textContent = data.country || 'N/A';
    document.getElementById('m_gender').textContent = data.male_female || 'N/A';

    document.getElementById('m_mobile').textContent = data.mobile_number || 'N/A';
    document.getElementById('m_whatsapp_num').textContent = data.WhatsApp_number || 'N/A';
    document.getElementById('m_email_txt').textContent = data.user_email || 'N/A';

    document.getElementById('m_desc').innerHTML = data.description; 

    // Handle Image from pre-calculated URL
    let imgSrc = data.has_img ? data.img_url : 'https://via.placeholder.com/150';
    document.getElementById('m_img').src = imgSrc;
    
    // Skills
    const skills = data.skills_tags ? data.skills_tags.split(',') : [];
    let skillHtml = '';
    skills.forEach(s => {
        skillHtml += `<span class="badge bg-light text-dark border me-1 mb-2">${s.trim()}</span>`;
    });
    document.getElementById('m_skills').innerHTML = skillHtml;

    // Handle CV Link
    const cvBtn = document.getElementById('m_cv_link');
    if (data.cv_path || data.employee_cv) {
        cvBtn.href = 'actions/download_talent_doc.php?type=cv&id=' + data.seeker_link;
        cvBtn.style.display = 'inline-block';
    } else {
        cvBtn.style.display = 'none';
    }

    // Fetch and Render Other Documents
    const docsContainer = document.getElementById('m_docs_area');
    if(docsContainer) {
        docsContainer.innerHTML = '<div class="text-center py-2"><span class="spinner-border spinner-border-sm text-muted"></span></div>';

        fetch('actions/fetch_talent_docs.php?seeker_id=' + data.seeker_link)
            .then(res => res.json())
            .then(res => {
                if(res.success && res.docs.length > 0) {
                    let html = '<h6 class="fw-bold text-primary mb-2 mt-4">Other Documents</h6><div class="d-flex flex-wrap gap-2">';
                    res.docs.forEach(doc => {
                        html += `<a href="actions/download_talent_doc.php?type=doc&doc_id=${doc.id}&seeker_id=${data.seeker_link}" target="_blank" class="btn btn-sm btn-light border">
                                    <i class="fas fa-file me-1"></i> ${doc.document_type}
                                 </a>`;
                    });
                    html += '</div>';
                    docsContainer.innerHTML = html;
                } else {
                    docsContainer.innerHTML = '';
                }
            })
            .catch(err => {
                console.error(err);
                docsContainer.innerHTML = '';
            });
    }

        new bootstrap.Modal(document.getElementById('profileModal')).show();
    }
    </script>
</div>
