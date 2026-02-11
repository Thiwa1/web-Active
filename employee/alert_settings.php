<?php
session_start();
require_once '../config/config.php';

$site_name = "JobQuest Pro"; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$status_type = ""; $msg = "";

try {
    // 2. Get Profile Info
    $stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    if (!$profile) { die("Please create your profile before setting alerts."); }
    $profile_id = $profile['id'];

    // 3. Fetch All Settings
    $stmt = $pdo->prepare("SELECT * FROM employee_alerted_setting WHERE link_to_employee_profile = ? ORDER BY id DESC");
    $stmt->execute([$profile_id]);
    $my_alerts = $stmt->fetchAll();

    // 4. Fetch Lookups
    $districts = $pdo->query("SELECT District_name FROM district_table ORDER BY District_name ASC")->fetchAll();
    $categories = $pdo->query("SELECT Description FROM job_category_table ORDER BY Description ASC")->fetchAll();

} catch (PDOException $e) {
    $msg = "Error: " . $e->getMessage(); $status_type = "danger";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alert Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .glass-card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .alert-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .form-control, .form-select { border-radius: 10px; padding: 12px; }
        .btn-add { background: #2563eb; color: white; border-radius: 10px; padding: 12px; font-weight: 600; border: none; width: 100%; }
        .btn-add:hover { background: #1d4ed8; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="mb-4">
        <a href="dashboard.php" class="text-decoration-none text-muted small"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <div class="row g-4">
        <!-- ADD NEW FORM -->
        <div class="col-lg-4">
            <div class="glass-card">
                <h5 class="fw-bold mb-4"><i class="fas fa-plus-circle text-primary me-2"></i>New Alert</h5>

                <div id="alertMsg"></div>

                <form id="alertForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">District</label>
                        <select name="district" id="districtSelect" class="form-select">
                            <option value="">Any District</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= htmlspecialchars($d['District_name']) ?>"><?= htmlspecialchars($d['District_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Specific City</label>
                        <select name="city" id="citySelect" class="form-select" disabled>
                             <option value="">Any City</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category</label>
                        <select name="job_category" class="form-select" required>
                            <option value="" disabled selected>Select Industry</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c['Description']) ?>"><?= htmlspecialchars($c['Description']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-add" id="btnAdd">Add Criteria</button>
                </form>
            </div>
        </div>

        <!-- LIST ALERTS -->
        <div class="col-lg-8">
            <div class="glass-card">
                <h5 class="fw-bold mb-4">Active Alerts</h5>
                <?php if(count($my_alerts) > 0): ?>
                    <?php foreach($my_alerts as $alert): ?>
                        <div class="alert-item">
                            <div>
                                <div class="fw-bold text-dark">
                                    <?= htmlspecialchars($alert['job_category'] ?: 'All Industries') ?>
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($alert['district'] ?: 'All Districts') ?>
                                    <?= $alert['city'] ? ' &bull; ' . htmlspecialchars($alert['city']) : '' ?>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-delete" data-id="<?= $alert['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-bell-slash fa-3x mb-3 opacity-25"></i>
                        <p>No alerts set. Add one to start matching.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Fetch Cities
        $('#districtSelect').on('change', function() {
            var dist = $(this).val();
            var citySelect = $('#citySelect');

            if(dist) {
                $.post('../actions/fetch_locations.php', {district_name: dist}, function(res) {
                    var data = JSON.parse(res);
                    citySelect.empty().append('<option value="">Any City</option>');
                    data.forEach(function(c) {
                        citySelect.append('<option value="'+c.City+'">'+c.City+'</option>');
                    });
                    citySelect.prop('disabled', false);
                });
            } else {
                citySelect.empty().append('<option value="">Any City</option>').prop('disabled', true);
            }
        });

        // AJAX Save
        $('#alertForm').on('submit', function(e) {
            e.preventDefault();
            var btn = $('#btnAdd');
            var originalText = btn.text();
            btn.prop('disabled', true).text('Saving...');

            $.post('actions/save_alert_ajax.php', $(this).serialize(), function(res) {
                if(res.status === 'success') {
                    // Reload Content using the dashboard function
                    if(typeof loadContent === 'function') {
                        loadContent('alert_settings');
                    } else {
                        location.reload();
                    }
                } else {
                    $('#alertMsg').html('<div class="alert alert-danger small">'+res.message+'</div>');
                    btn.prop('disabled', false).text(originalText);
                }
            }, 'json').fail(function() {
                alert('Server Error');
                btn.prop('disabled', false).text(originalText);
            });
        });

        // AJAX Delete
        $('.btn-delete').on('click', function() {
            if(!confirm('Remove this alert?')) return;

            var id = $(this).data('id');
            $.post('actions/delete_alert_ajax.php', {id: id}, function(res) {
                if(res.status === 'success') {
                    if(typeof loadContent === 'function') {
                        loadContent('alert_settings');
                    } else {
                        location.reload();
                    }
                } else {
                    alert(res.message);
                }
            }, 'json');
        });
    });
</script>

</body>
</html>
