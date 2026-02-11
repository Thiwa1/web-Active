<?php
session_start();
require_once '../config/config.php';
require_once '../classes/AiRecruiter.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        die("Session Expired");
    }
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];

// Direct Access Prevention: Redirect to Dashboard if not AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    // Reconstruct query parameters
    $queryParams = $_GET;
    $queryString = http_build_query($queryParams);
    $redirectUrl = 'dashboard.php?page=view_applications' . ($queryString ? '&' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit();
}

$filter_job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$ai = new AiRecruiter();

try {
    // 1. Fetch Job Title for Header if specific job selected
    $currentJobTitle = "All Vacancies";
    if ($filter_job_id > 0) {
        $stmt = $pdo->prepare("SELECT Job_role FROM advertising_table WHERE id = ?");
        $stmt->execute([$filter_job_id]);
        $row = $stmt->fetch();
        if ($row) $currentJobTitle = $row['Job_role'];
    }

    // 2. Fetch Applicants (Registered) - Join AD table to get context for AI
    // Note: GROUP BY logic is handled below to support dynamic filtering

    $params = [$user_id];
    // Base WHERE clause is already in $sqlReg from above, but we need to append
    // conditionally BEFORE GROUP BY.
    // To handle this cleanly without rebuilding the whole string repeatedly:
    // 1. Remove the pre-appended "GROUP BY a.id" from the initial definition above.
    // 2. Append extra WHERE conditions.
    // 3. Append GROUP BY at the end.

    // Redefine base query without GROUP BY for clarity and safety
    // Filter for the latest application per seeker per job to avoid duplicates
    $sqlRegBase = "SELECT a.id as app_id, a.applied_date, a.application_status,
                      p.employee_full_name, p.employee_img, p.img_path, u.mobile_number, u.user_email, 'reg' as source,
                      p.id as profile_id,
                      p.cl_path, p.employee_cover_letter,
                      MAX(t.skills_tags) as skills_tags,
                      MAX(t.experience_years) as experience_years,
                      MAX(t.description) as profile_desc,
                      ad.job_description, ad.Job_role, ad.Job_category
               FROM job_applications a
               JOIN employee_profile_seeker p ON a.seeker_link = p.id
               JOIN user_table u ON p.link_to_user = u.id
               JOIN advertising_table ad ON a.job_ad_link = ad.id
               LEFT JOIN talent_offers t ON p.id = t.seeker_link AND t.is_active = 1
               WHERE ad.link_to_employer_profile = (SELECT id FROM employer_profile WHERE link_to_user = ?)
               AND a.id IN (
                   SELECT MAX(ja.id)
                   FROM job_applications ja
                   GROUP BY ja.job_ad_link, ja.seeker_link
               )";

    if ($filter_job_id > 0) {
        $sqlRegBase .= " AND a.job_ad_link = ?";
        $params[] = $filter_job_id;
    }
    
    // Group by Application ID to aggregate talent offers if multiple active offers exist (rare but possible)
    $sqlReg = $sqlRegBase . " GROUP BY a.id";

    // Fetch Applicants (Guest)
    $sqlGuest = "SELECT g.id as app_id, g.applied_date, g.application_status,
                        g.guest_full_name as employee_full_name, NULL as employee_img, NULL as img_path,
                        g.guest_contact_no as mobile_number, NULL as user_email, 'guest' as source,
                        0 as profile_id,
                        g.cl_path, g.guest_cover_letter as employee_cover_letter,
                        NULL as skills_tags, 0 as experience_years, NULL as profile_desc,
                        ad.job_description, ad.Job_role, ad.Job_category
                 FROM guest_job_applications g
                 JOIN advertising_table ad ON g.job_ad_link = ad.id
                 WHERE ad.link_to_employer_profile = (SELECT id FROM employer_profile WHERE link_to_user = ?)";
                 
    $paramsGuest = [$user_id];
    if ($filter_job_id > 0) {
        $sqlGuest .= " AND g.job_ad_link = ?";
        $paramsGuest[] = $filter_job_id;
    }

    $appsReg = $pdo->prepare($sqlReg);
    $appsReg->execute($params);
    $regResults = $appsReg->fetchAll(PDO::FETCH_ASSOC);

    $appsGuest = $pdo->prepare($sqlGuest);
    $appsGuest->execute($paramsGuest);
    $guestResults = $appsGuest->fetchAll(PDO::FETCH_ASSOC);

    $applicants = array_merge($regResults, $guestResults);

    // Process Applicants & AI Scoring
    foreach ($applicants as &$app) {
        $candidateText = $app['employee_full_name'] . ' ' . ($app['skills_tags'] ?? '') . ' ' . ($app['profile_desc'] ?? '');
        $exp = $app['experience_years'] ?? 0;

        // Use specific job context for this application
        $jobContext = ($app['job_description'] ?? '') . ' ' . ($app['Job_role'] ?? '');
        $jobCat = $app['Job_category'] ?? '';

        $aiResult = $ai->scoreCV($candidateText, $jobContext, $exp, 0); // Required exp unknown, passing 0
        $app['ai_score'] = $aiResult['score'];
        $app['ai_rec'] = $aiResult['recommendation'];
        $app['ai_salary'] = $ai->estimateSalary($candidateText, $exp, $jobCat);

        // Fetch docs if registered
        $app['docs'] = [];
        if ($app['source'] == 'reg' && !empty($app['profile_id'])) {
            $stmtDocs = $pdo->prepare("SELECT id, document_type FROM employee_document WHERE link_to_employee_profile = ?");
            $stmtDocs->execute([$app['profile_id']]);
            $app['docs'] = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Sort by AI Score Desc
    usort($applicants, function($a, $b) {
        return $b['ai_score'] <=> $a['ai_score'];
    });

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Applicants</h3>
            <p class="text-muted">For: <?= htmlspecialchars($currentJobTitle) ?></p>
        </div>
    </div>

    <?php if(empty($applicants)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
            <h5>No applicants found yet</h5>
            <p>Share your job posting to attract more candidates.</p>
        </div>
    <?php else: ?>
        <div class="alert alert-info border-0 bg-soft-primary d-flex align-items-center mb-4 rounded-4 shadow-sm">
            <div class="icon-box bg-white text-primary rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <strong>AI Analysis Active:</strong> Candidates are ranked by matching their profile skills & description against your job requirements.
            </div>
        </div>

        <div class="table-responsive-xl" style="min-height: 400px;">
            <table class="table table-hover align-middle bg-white rounded-4 shadow-sm" style="border-collapse: separate; border-spacing: 0;">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-uppercase text-secondary small fw-bold">Candidate</th>
                        <th class="py-3 text-uppercase text-secondary small fw-bold">Match Score</th>
                        <th class="py-3 text-uppercase text-secondary small fw-bold">Est. Salary</th>
                        <th class="py-3 text-uppercase text-secondary small fw-bold">Applied Date</th>
                        <th class="py-3 text-uppercase text-secondary small fw-bold">Documents</th>
                        <th class="py-3 text-uppercase text-secondary small fw-bold">Status</th>
                        <th class="pe-4 py-3 text-end text-uppercase text-secondary small fw-bold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($applicants as $app): ?>
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center">
                                <?php
                                    $img_src = '';
                                    $clean_path = ltrim($app['img_path'] ?? '', '/');
                                    if (!empty($clean_path) && file_exists('../'.$clean_path)) {
                                        $img_src = '../'.$clean_path;
                                    } elseif (!empty($app['employee_img'])) {
                                        $img_src = "actions/view_image.php?id=" . $app['profile_id'];
                                    }
                                ?>
                                <?php if($img_src): ?>
                                    <img src="<?= htmlspecialchars($img_src) ?>" class="avatar me-3 rounded-circle border" style="width:48px;height:48px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="avatar me-3 bg-light d-flex align-items-center justify-content-center text-muted rounded-circle border" style="width:48px;height:48px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($app['employee_full_name']) ?></div>
                                    <div class="small text-muted">
                                        <i class="fas fa-briefcase me-1"></i>For: <?= htmlspecialchars($app['Job_role']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                                $scoreClass = 'bg-secondary';
                                if($app['ai_score'] >= 80) $scoreClass = 'bg-success';
                                elseif($app['ai_score'] >= 50) $scoreClass = 'bg-primary';
                                elseif($app['ai_score'] >= 30) $scoreClass = 'bg-warning text-dark';
                            ?>
                            <div class="d-flex flex-column">
                                <span class="badge <?= $scoreClass ?> rounded-pill mb-1" style="width: fit-content;">
                                    <i class="fas fa-bolt me-1"></i><?= $app['ai_score'] ?>%
                                </span>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= $app['ai_rec'] ?></small>
                            </div>
                        </td>
                        <td>
                             <div class="fw-bold text-dark">LKR <?= number_format($app['ai_salary']) ?></div>
                             <small class="text-muted">Month</small>
                        </td>
                        <td class="text-muted small">
                            <?= date('M d, Y', strtotime($app['applied_date'])) ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="../actions/download_resume.php?type=<?= $app['source'] ?>&id=<?= $app['app_id'] ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-3" title="Download CV">
                                    <i class="fas fa-file-pdf text-danger me-1"></i> CV
                                </a>
                                <?php if(!empty($app['cl_path']) || !empty($app['employee_cover_letter'])): ?>
                                    <a href="../actions/download_resume.php?type=<?= $app['source'] ?>&id=<?= $app['app_id'] ?>&doc_type=cl" target="_blank" class="btn btn-sm btn-light border rounded-pill px-3" title="Cover Letter">
                                        <i class="fas fa-file-lines text-primary me-1"></i> CL
                                    </a>
                                <?php endif; ?>
                                <?php if(!empty($app['docs'])): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Docs
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php foreach($app['docs'] as $d): ?>
                                                <li><a class="dropdown-item" href="actions/download_doc.php?id=<?= $d['id'] ?>"><?= htmlspecialchars($d['document_type']) ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php
                                $statusClass = 'bg-secondary';
                                if($app['application_status'] == 'Shortlisted') $statusClass = 'bg-success';
                                if($app['application_status'] == 'Rejected') $statusClass = 'bg-danger';
                            ?>
                            <span id="status-badge-<?= $app['source'] ?>-<?= $app['app_id'] ?>" class="badge <?= $statusClass ?> rounded-pill px-3"><?= htmlspecialchars($app['application_status']) ?></span>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="dropdown">
                                <button id="btn-<?= $app['source'] ?>-<?= $app['app_id'] ?>" class="btn btn-light btn-sm rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport">
                                    <i class="fas fa-ellipsis-v text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3" style="z-index: 1050;">
                                    <li><h6 class="dropdown-header text-uppercase small fw-bold">Update Status</h6></li>
                                    <li><a class="dropdown-item text-success fw-medium" href="#" onclick="updateStatus(event, <?= $app['app_id'] ?>, '<?= $app['source'] ?>', 'Shortlisted')"><i class="fas fa-check me-2"></i>Shortlist</a></li>
                                    <li><a class="dropdown-item text-danger fw-medium" href="#" onclick="updateStatus(event, <?= $app['app_id'] ?>, '<?= $app['source'] ?>', 'Rejected')"><i class="fas fa-times me-2"></i>Reject</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="mailto:<?= $app['user_email'] ?>"><i class="fas fa-envelope me-2"></i>Email Candidate</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <script>
        // Re-init dropdowns for SPA
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
          return new bootstrap.Dropdown(dropdownToggleEl);
        });

        // AJAX Status Update
        function updateStatus(event, id, source, newStatus) {
            event.preventDefault();

            const btn = document.getElementById('btn-' + source + '-' + id);
            const badge = document.getElementById('status-badge-' + source + '-' + id);

            // Show Loading State
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            btn.disabled = true;

            fetch(`actions/update_status.php?id=${id}&source=${source}&status=${newStatus}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update Badge
                    badge.textContent = newStatus;
                    badge.className = 'badge rounded-pill px-3';
                    if(newStatus === 'Shortlisted') badge.classList.add('bg-success');
                    else if(newStatus === 'Rejected') badge.classList.add('bg-danger');
                    else badge.classList.add('bg-secondary');

                    // Show Toast (if available via UI helpers) or Alert
                    if(typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated',
                            text: 'Candidate marked as ' + newStatus,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                } else {
                    alert('Error updating status: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred.');
            })
            .finally(() => {
                // Restore Button
                btn.innerHTML = originalIcon;
                btn.disabled = false;
            });
        }
    </script>
</div>
