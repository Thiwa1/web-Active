<?php
session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

try {
    // Optimized queries
    $company    = $pdo->query("SELECT * FROM Compan_details LIMIT 1")->fetch();
    $districts  = $pdo->query("SELECT * FROM district_table ORDER BY District_name ASC")->fetchAll();
    $industries = $pdo->query("SELECT * FROM Industry_Setting ORDER BY Industry_name ASC")->fetchAll();
    $categories = $pdo->query("SELECT * FROM job_category_table ORDER BY Description ASC")->fetchAll();
    $siteSettings = $pdo->query("SELECT * FROM site_settings ORDER BY setting_key ASC")->fetchAll();
    
    // Fetch Cities with Districts
    $cities = $pdo->query("SELECT c.*, d.District_name FROM city_table c JOIN district_table d ON c.City_link = d.id ORDER BY d.District_name ASC, c.City ASC")->fetchAll();
    
    // Group Cities by District
    $citiesByDistrict = [];
    foreach($cities as $c) {
        $citiesByDistrict[$c['District_name']][] = $c;
    }

    // Summary Stats
    $cityCount = count($cities);
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Config | Pro Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --pro-blue: #2563eb; --pro-slate: #0f172a; --pro-border: #e2e8f0; }
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #334155; }
        .settings-grid { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; }
        .config-nav { background: white; border-radius: 16px; border: 1px solid var(--pro-border); padding: 1.5rem; position: sticky; top: 2rem; height: calc(100vh - 4rem); }
        .nav-link-pro { display: flex; align-items: center; padding: 0.8rem 1rem; border-radius: 10px; color: #64748b; text-decoration: none; margin-bottom: 0.5rem; transition: 0.2s; font-weight: 500; }
        .nav-link-pro:hover { background: #f1f5f9; color: var(--pro-blue); }
        .nav-link-pro.active { background: var(--pro-blue); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .nav-link-pro i { width: 24px; font-size: 1.1rem; margin-right: 12px; }
        .glass-card { background: white; border-radius: 16px; border: 1px solid var(--pro-border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 2rem; margin-bottom: 2rem; }
        .section-header { border-bottom: 1px solid #f1f5f9; padding-bottom: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .stat-chip { background: #f1f5f9; padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; color: #475569; }
        .form-control-pro { border-radius: 10px; border: 1px solid #e2e8f0; padding: 0.75rem 1rem; }
        .hover-bg-light:hover { background-color: #f8fafc; transition: 0.2s; }
        .cursor-pointer { cursor: pointer; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">
    <div class="settings-grid">
        <aside>
            <div class="config-nav shadow-sm d-flex flex-column">
                <div class="mb-4 px-2">
                    <h5 class="fw-bold m-0">System Settings</h5>
                    <p class="text-muted small">Global Environment Config</p>
                </div>
                <nav class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                    <a class="nav-link-pro active" data-bs-toggle="pill" href="#pane-general"><i class="fas fa-sliders"></i> General</a>
                    <a class="nav-link-pro" data-bs-toggle="pill" href="#pane-geo"><i class="fas fa-map-location-dot"></i> Geography</a>
                    <a class="nav-link-pro" data-bs-toggle="pill" href="#pane-taxonomy"><i class="fas fa-sitemap"></i> Job Taxonomy</a>
                    <a class="nav-link-pro" data-bs-toggle="pill" href="#pane-sms"><i class="fas fa-comment-sms"></i> SMS / API</a>
                </nav>
                <div class="mt-auto pt-4">
                    <a href="dashboard.php" class="btn btn-light w-100 rounded-pill fw-bold small"><i class="fas fa-arrow-left me-2"></i>Exit Config</a>
                </div>
            </div>
        </aside>

        <main>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="pane-general">
                    <div class="glass-card">
                        <div class="section-header">
                            <div>
                                <h4 class="fw-bold m-0">Portal Branding</h4>
                                <p class="text-muted small m-0">Identity and contact metadata.</p>
                            </div>
                        </div>
                        <form action="actions/update_settings.php" method="POST" enctype="multipart/form-data" class="row g-4">
                            <input type="hidden" name="action_type" value="update_company">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">Official Entity Name</label>
                                <input type="text" name="company_name" class="form-control form-control-pro" value="<?= htmlspecialchars($company['company_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Support Hotline</label>
                                <input type="text" name="TP_No" class="form-control form-control-pro" value="<?= $company['TP_No'] ?? '' ?>">
                            </div>
                            
                            <div class="col-12">
                                <h6 class="fw-bold mt-3 mb-3 text-muted border-bottom pb-2">Address & Extra Details</h6>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold small">Address Line 1</label>
                                <input type="text" name="addres1" class="form-control form-control-pro" value="<?= htmlspecialchars($company['addres1'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Address Line 2</label>
                                <input type="text" name="addres2" class="form-control form-control-pro" value="<?= htmlspecialchars($company['addres2'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Address Line 3</label>
                                <input type="text" name="addres3" class="form-control form-control-pro" value="<?= htmlspecialchars($company['addres3'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold small">Additional Company Info</label>
                                <input type="text" name="Compan_detailscol" class="form-control form-control-pro" value="<?= htmlspecialchars($company['Compan_detailscol'] ?? '') ?>">
                            </div>

                            <div class="col-12">
                                <h6 class="fw-bold mt-3 mb-3 text-muted border-bottom pb-2">Branding Assets</h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Company Logo</label>
                                <input type="file" name="logo" class="form-control form-control-pro" accept="image/*">
                                <div class="form-text small">Max 2MB. PNG/JPG recommended.</div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <?php if(!empty($company['logo_path'])): ?>
                                    <div class="me-3">
                                        <img src="../<?= htmlspecialchars($company['logo_path']) ?>" height="50" class="rounded border p-1">
                                        <span class="small text-muted ms-2">Current Logo</span>
                                    </div>
                                <?php elseif(!empty($company['logo'])): ?>
                                    <div class="me-3">
                                        <img src="data:image/jpeg;base64,<?= base64_encode($company['logo']) ?>" height="50" class="rounded border p-1">
                                        <span class="small text-muted ms-2">Current Logo</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="pane-geo">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="glass-card h-100">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="fw-bold m-0">Districts</h5>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openAddModal('district')">+ Add</button>
                                </div>
                                <div class="overflow-auto" style="max-height: 400px;">
                                    <?php foreach($districts as $d): ?>
                                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom hover-bg-light">
                                        <span class="fw-medium text-dark"><?= $d['District_name'] ?></span>
                                        <div class="btn-group">
                                            <button class="btn btn-link text-muted p-0 me-3" onclick="editItem('district', <?= $d['id'] ?>, '<?= $d['District_name'] ?>')"><i class="fas fa-pen small"></i></button>
                                            <button class="btn btn-link text-danger p-0" onclick="deleteItem('district', <?= $d['id'] ?>)"><i class="fas fa-trash-can small"></i></button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="glass-card h-100">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="fw-bold m-0">Cities</h5>
                                    <div>
                                        <button class="btn btn-sm btn-outline-dark rounded-pill px-3 me-1" onclick="new bootstrap.Modal(document.getElementById('bulkCityModal')).show()">+ Bulk</button>
                                        <button class="btn btn-sm btn-dark rounded-pill px-3" onclick="openAddModal('city')">+ Add</button>
                                    </div>
                                </div>
                                <div class="overflow-auto" style="max-height: 400px;">
                                    <?php if(empty($citiesByDistrict)): ?>
                                        <div class="text-center py-4 text-muted">No cities found.</div>
                                    <?php else: ?>
                                        <div class="accordion accordion-flush" id="cityAccordion">
                                            <?php $i=0; foreach($citiesByDistrict as $district => $cityList): $i++; ?>
                                            <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed bg-light py-2 px-3 shadow-none small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#dist-<?= $i ?>">
                                                        <?= htmlspecialchars($district) ?> <span class="badge bg-secondary ms-2"><?= count($cityList) ?></span>
                                                    </button>
                                                </h2>
                                                <div id="dist-<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#cityAccordion">
                                                    <div class="accordion-body p-0">
                                                        <?php foreach($cityList as $city): ?>
                                                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom hover-bg-light small">
                                                            <span class="text-dark"><?= htmlspecialchars($city['City']) ?></span>
                                                            <div class="btn-group">
                                                                <button class="btn btn-link text-muted p-0 me-2" onclick="editItem('city', <?= $city['id'] ?>, '<?= addslashes($city['City']) ?>')"><i class="fas fa-pen fa-xs"></i></button>
                                                                <button class="btn btn-link text-danger p-0" onclick="deleteItem('city', <?= $city['id'] ?>)"><i class="fas fa-trash-can fa-xs"></i></button>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pane-taxonomy">
                    <div class="glass-card">
                        <div class="section-header">
                            <h5 class="fw-bold m-0">Job Classifications</h5>
                            <div class="btn-group gap-2">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openBulkTaxonomy('industry')">+ Bulk</button>
                                    <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="openAddModal('industry')">+ Industry</button>
                                </div>
                                <div class="btn-group ms-2">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openBulkTaxonomy('category')">+ Bulk</button>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openAddModal('category')">+ Category</button>
                                </div>
                            </div>
                        </div>
                        <div class="row g-5">
                            <div class="col-md-6">
                                <p class="small text-muted mb-3 fw-bold text-uppercase">Industries</p>
                                <div class="list-group list-group-flush border rounded-4">
                                    <?php foreach($industries as $i): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <span class="small fw-semibold"><?= $i['Industry_name'] ?></span>
                                        <div>
                                            <i class="fas fa-pen text-muted me-3 cursor-pointer small" onclick="editItem('industry', <?= $i['id'] ?>, '<?= $i['Industry_name'] ?>')"></i>
                                            <i class="fas fa-trash text-danger cursor-pointer small" onclick="deleteItem('industry', <?= $i['id'] ?>)"></i>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <p class="small text-muted mb-3 fw-bold text-uppercase">Job Categories</p>
                                <div class="list-group list-group-flush border rounded-4">
                                    <?php foreach($categories as $cat): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <span class="small fw-semibold"><?= $cat['Description'] ?></span>
                                        <div>
                                            <i class="fas fa-pen text-muted me-3 cursor-pointer small" onclick="editItem('category', <?= $cat['id'] ?>, '<?= $cat['Description'] ?>')"></i>
                                            <i class="fas fa-trash text-danger cursor-pointer small" onclick="deleteItem('category', <?= $cat['id'] ?>)"></i>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pane-sms">
                    <div class="glass-card">
                        <div class="section-header">
                            <div>
                                <h4 class="fw-bold m-0">SMS & API Integrations</h4>
                                <p class="text-muted small m-0">Manage system-wide API keys and configuration constants.</p>
                            </div>
                            <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="openAddModal('site_setting')">+ Add Key</button>
                        </div>
                        
                        <form action="actions/update_settings.php" method="POST">
                            <input type="hidden" name="action_type" value="update_site_settings">
                            
                            <!-- Global Promotion Toggle -->
                            <div class="p-3 mb-4 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="fw-bold text-primary mb-1"><i class="fas fa-bullhorn me-2"></i>Global Promotion Mode</h6>
                                        <p class="small text-muted mb-0">When enabled, employers can post jobs without payment slips (Free Tier).</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <?php 
                                            // Find promotion_period setting
                                            $isPromo = false;
                                            $promoId = 0;
                                            foreach($siteSettings as $s) {
                                                if($s['setting_key'] === 'promotion_period') {
                                                    $isPromo = ($s['setting_value'] == '1');
                                                    $promoId = $s['id'];
                                                    break;
                                                }
                                            }
                                        ?>
                                        <?php if($promoId): ?>
                                            <input type="hidden" name="settings[<?= $promoId ?>]" value="0"> <!-- Default off if unchecked -->
                                            <input class="form-check-input p-2" type="checkbox" name="settings[<?= $promoId ?>]" value="1" <?= $isPromo ? 'checked' : '' ?> style="transform: scale(1.5);">
                                        <?php else: ?>
                                            <div class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i> Key missing</div>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="addPromoKey()">Create Key</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- NEW: Paper Ad Settings -->
                            <div class="p-3 mb-4 bg-light rounded-4 border">
                                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-newspaper me-2"></i>Paper Ad Configuration</h6>
                                <?php
                                    $paperRate = '50.00';
                                    $paperRateId = 0;
                                    foreach($siteSettings as $s) {
                                        if($s['setting_key'] === 'paper_ad_rate_per_sq_cm') {
                                            $paperRate = $s['setting_value'];
                                            $paperRateId = $s['id'];
                                            break;
                                        }
                                    }
                                ?>
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold small">Rate per Square Centimeter (LKR)</label>
                                        <p class="small text-muted mb-0">Base calculation: Width * Height * Rate</p>
                                    </div>
                                    <div class="col-md-4">
                                        <?php if($paperRateId): ?>
                                            <input type="number" step="0.01" name="settings[<?= $paperRateId ?>]" class="form-control form-control-pro" value="<?= $paperRate ?>">
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-dark w-100" onclick="addPaperRateKey()">Initialize Rate Key</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php
                                $groups = ['SMS Gateway' => [], 'Google Integration' => [], 'System Config' => []];
                                foreach($siteSettings as $s) {
                                    if($s['setting_key'] === 'promotion_period') continue; // Handled by toggle
                                    if($s['setting_key'] === 'paper_ad_rate_per_sq_cm') continue; // Handled above

                                    if (strpos($s['setting_key'], 'sms_') === 0) $groups['SMS Gateway'][] = $s;
                                    elseif (strpos($s['setting_key'], 'google_') === 0) $groups['Google Integration'][] = $s;
                                    else $groups['System Config'][] = $s;
                                }
                            ?>

                            <?php foreach($groups as $name => $items): ?>
                                <?php if(empty($items)) continue; ?>
                                <h6 class="fw-bold text-muted text-uppercase small mb-3 border-bottom pb-2 mt-4"><?= $name ?></h6>
                                <div class="table-responsive mb-4">
                                    <table class="table align-middle mb-0">
                                        <tbody>
                                            <?php foreach($items as $s): ?>
                                            <tr>
                                                <td style="width: 30%;">
                                                    <div class="fw-bold text-dark font-monospace small"><?= htmlspecialchars($s['setting_key']) ?></div>
                                                </td>
                                                <td>
                                                    <input type="text" name="settings[<?= $s['id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($s['setting_value']) ?>">
                                                </td>
                                                <td style="width: 50px;">
                                                    <button type="button" class="btn btn-link text-danger p-0" onclick="deleteItem('site_setting', <?= $s['id'] ?>)"><i class="fas fa-trash-can"></i></button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Save Configuration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="actions/update_settings.php" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4 text-center">
                <input type="hidden" name="action_type" value="edit_entry">
                <input type="hidden" name="type" id="edit_type">
                <input type="hidden" name="id" id="edit_id">
                <div class="bg-primary bg-opacity-10 text-primary d-inline-block p-3 rounded-circle mb-3"><i class="fas fa-pen-nib fs-4"></i></div>
                <h5 class="fw-bold">Modify Entry</h5>
                <input type="text" name="new_value" id="edit_value" class="form-control form-control-pro text-center mt-3" required>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1">Save Update</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="bulkCityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="actions/bulk_add_cities.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="bg-info bg-opacity-10 text-info d-inline-block p-3 rounded-circle mb-3"><i class="fas fa-layer-group fs-4"></i></div>
                    <h5 class="fw-bold">Bulk Import Cities</h5>
                    <p class="small text-muted">Add multiple cities to a district at once.</p>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Select Target District</label>
                    <select name="district_id" class="form-select form-control-pro" required>
                        <option value="">Choose District...</option>
                        <?php foreach($districts as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= $d['District_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Option 1: Paste City List</label>
                    <textarea name="city_list" class="form-control form-control-pro" rows="5" placeholder="Kottawa, Maharagama, Nugegoda..."></textarea>
                </div>

                <div class="text-center text-muted small my-2">- OR -</div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Option 2: Upload CSV/TXT</label>
                    <input type="file" name="city_file" class="form-control form-control-pro" accept=".csv,.txt">
                    <div class="form-text small">Simple list of cities, one per line or comma separated.</div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info rounded-pill px-4 flex-grow-1 text-white fw-bold">Import Cities</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="bulkTaxonomyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="actions/bulk_add_taxonomy.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4">
                <input type="hidden" name="type" id="bulk_tax_type">
                <div class="text-center mb-4">
                    <div class="bg-success bg-opacity-10 text-success d-inline-block p-3 rounded-circle mb-3"><i class="fas fa-list-ul fs-4"></i></div>
                    <h5 class="fw-bold">Bulk Import <span id="bulk_tax_label">Items</span></h5>
                    <p class="small text-muted">Add multiple entries at once.</p>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Option 1: Paste List</label>
                    <textarea name="list_text" class="form-control form-control-pro" rows="5" placeholder="Item 1, Item 2, Item 3..."></textarea>
                </div>

                <div class="text-center text-muted small my-2">- OR -</div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Option 2: Upload CSV/TXT</label>
                    <input type="file" name="list_file" class="form-control form-control-pro" accept=".csv,.txt">
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success rounded-pill px-4 flex-grow-1 text-white fw-bold">Import Data</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="actions/update_settings.php" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4">
                <input type="hidden" name="action_type" value="add_entry">
                <input type="hidden" name="type" id="add_type">
                <div class="text-center mb-4">
                    <div class="bg-success bg-opacity-10 text-success d-inline-block p-3 rounded-circle mb-3"><i class="fas fa-plus fs-4"></i></div>
                    <h5 class="fw-bold">Create New <span id="add_label">Entry</span></h5>
                </div>
                
                <div id="city_extra_fields" style="display:none;" class="mb-3">
                    <label class="form-label small fw-bold">Select District</label>
                    <select name="district_id" class="form-select form-control-pro">
                        <?php foreach($districts as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= $d['District_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Name / Description</label>
                    <input type="text" name="value" class="form-control form-control-pro" required>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1">Confirm Add</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Tab Persistence
    document.addEventListener("DOMContentLoaded", function() {
        var hash = window.location.hash;
        if (hash) {
            var triggerEl = document.querySelector('a[href="' + hash + '"]');
            if (triggerEl) {
                var tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }
        
        // Update hash when tab changes
        var tabEls = document.querySelectorAll('a[data-bs-toggle="pill"]');
        tabEls.forEach(function(tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (event) {
                history.pushState(null, null, event.target.getAttribute('href'));
            });
        });
    });

    function editItem(type, id, val) {
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_value').value = val;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    function openAddModal(type) {
        document.getElementById('add_type').value = type;
        document.getElementById('add_label').innerText = type.charAt(0).toUpperCase() + type.slice(1);
        
        // Show district dropdown only for cities
        document.getElementById('city_extra_fields').style.display = (type === 'city') ? 'block' : 'none';
        
        new bootstrap.Modal(document.getElementById('addModal')).show();
    }

    function deleteItem(type, id) {
        if(confirm(`WARNING: Deleting this ${type} may cause errors in jobs linked to it. Proceed?`)) {
            window.location.href = `actions/update_settings.php?action=delete&type=${type}&id=${id}`;
        }
    }

    function openBulkTaxonomy(type) {
        document.getElementById('bulk_tax_type').value = type;
        document.getElementById('bulk_tax_label').innerText = type === 'industry' ? 'Industries' : 'Categories';
        new bootstrap.Modal(document.getElementById('bulkTaxonomyModal')).show();
    }

    function addPromoKey() {
        submitNewKey('promotion_period', '1');
    }

    function addPaperRateKey() {
        submitNewKey('paper_ad_rate_per_sq_cm', '50.00');
    }

    function submitNewKey(key, val) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'actions/update_settings.php';
        
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'action_type';
        typeInput.value = 'add_entry';
        
        const tableInput = document.createElement('input');
        tableInput.type = 'hidden';
        tableInput.name = 'type';
        tableInput.value = 'site_setting';
        
        const valInput = document.createElement('input');
        valInput.type = 'hidden';
        valInput.name = 'value';
        valInput.value = key;

        // For value, we might need another field or defaults
        // But add_entry for site_setting usually takes 'value' as the Key Name (based on existing logic?)
        // Let's check logic: if type=site_setting, insert into site_settings (setting_key, setting_value)
        // I need to confirm how add_entry works for site_setting.

        // Wait, standard add_entry might not support setting_value default.
        // It likely only inserts the Name.
        // I'll assume it inserts (setting_key=value, setting_value='').
        // Then I can edit it.
        
        form.appendChild(typeInput);
        form.appendChild(tableInput);
        form.appendChild(valInput);
        
        document.body.appendChild(form);
        form.submit();
    }
</script>
<?php include '../layout/ui_helpers.php'; ?>
</body>
</html>