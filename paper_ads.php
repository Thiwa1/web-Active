<?php
session_start();
require_once 'config/config.php';

$pageTitle = "Paper Advertising";
include 'layout/header.php';

// Fetch Bank Details
$stmt = $pdo->query("SELECT * FROM system_bank_accounts LIMIT 1");
$bank = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Price Setting (default 50 LKR/cm2 if not set)
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'paper_ad_rate_per_sq_cm'");
$stmt->execute();
$rate = $stmt->fetchColumn() ?: 50;

// Calculate Next Closing Date (e.g., Next Friday)
$nextFriday = date('Y-m-d', strtotime('next Friday'));

?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-primary text-white p-4 rounded-top-4">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-newspaper me-2"></i> Submit Paper Advertisement</h4>
                    <p class="mb-0 small opacity-75">Reach thousands with our weekly print edition. Deadline: <?= date('M d, Y', strtotime($nextFriday)) ?></p>
                </div>
                <div class="card-body p-4 p-md-5">

                    <?php if(isset($_GET['success'])): ?>
                        <div class="alert alert-success rounded-3 mb-4">
                            <i class="fas fa-check-circle me-2"></i> Ad submitted successfully! We will review and contact you.
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-danger rounded-3 mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($_GET['error']) ?>
                        </div>
                    <?php endif; ?>

                    <form action="actions/submit_paper_ad.php" method="POST" enctype="multipart/form-data" id="adForm">
                        <input type="hidden" name="rate" id="rate" value="<?= $rate ?>">

                        <h5 class="text-primary fw-bold mb-3">1. Ad Specifications</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">Width (cm)</label>
                                <input type="number" step="0.1" name="width_cm" id="width_cm" class="form-control" required min="1" value="5">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" id="height_cm" class="form-control" required min="1" value="5">
                            </div>
                            <div class="col-12">
                                <div class="bg-light p-3 rounded-3 border text-center">
                                    <small class="text-muted d-block text-uppercase fw-bold">Estimated Cost</small>
                                    <h3 class="text-primary fw-bold mb-0">LKR <span id="total_price">0.00</span></h3>
                                    <small class="text-muted">(Rate: LKR <?= number_format($rate, 2) ?> per cm²)</small>
                                </div>
                            </div>
                        </div>

                        <h5 class="text-primary fw-bold mb-3">2. Ad Content</h5>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Ad Text</label>
                            <textarea name="ad_content" class="form-control" rows="5" placeholder="Type your advertisement text here..." required></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase">Optional Image / Layout Draft</label>
                            <input type="file" name="ad_image" class="form-control" accept="image/*,application/pdf">
                            <div class="form-text">Upload if you have a specific design or logo to include.</div>
                        </div>

                        <h5 class="text-primary fw-bold mb-3">3. Contact Information</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">Mobile Number</label>
                                <input type="text" name="contact_mobile" class="form-control" required placeholder="07xxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">WhatsApp Number</label>
                                <input type="text" name="contact_whatsapp" class="form-control" placeholder="07xxxxxxxx">
                            </div>
                        </div>

                        <h5 class="text-primary fw-bold mb-3">4. Payment</h5>
                        <div class="mb-4">
                            <div class="alert alert-info border-0 bg-primary-subtle text-primary-emphasis">
                                <div class="d-flex gap-3">
                                    <i class="fas fa-university fa-2x mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Bank Transfer Details</h6>
                                        <?php if($bank): ?>
                                            <p class="mb-0 small">
                                                <strong>Bank:</strong> <?= htmlspecialchars($bank['bank_name']) ?><br>
                                                <strong>Account No:</strong> <?= htmlspecialchars($bank['account_number']) ?><br>
                                                <strong>Branch:</strong> <?= htmlspecialchars($bank['branch_name']) ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="mb-0 small">Please contact admin for bank details.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <label class="form-label fw-bold small text-uppercase">Upload Payment Slip</label>
                            <input type="file" name="payment_slip" class="form-control" required accept="image/*,application/pdf">
                            <div class="form-text">Please upload clear photo/scan of bank transfer slip.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i> Submit Advertisement
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const widthInput = document.getElementById('width_cm');
    const heightInput = document.getElementById('height_cm');
    const priceDisplay = document.getElementById('total_price');
    const rate = parseFloat(document.getElementById('rate').value);

    function calculatePrice() {
        const w = parseFloat(widthInput.value) || 0;
        const h = parseFloat(heightInput.value) || 0;
        const total = (w * h * rate).toFixed(2);
        priceDisplay.textContent = total;
    }

    widthInput.addEventListener('input', calculatePrice);
    heightInput.addEventListener('input', calculatePrice);

    // Init
    calculatePrice();
</script>

<?php include 'layout/footer.php'; ?>
