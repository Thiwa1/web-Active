<?php
session_start();
require_once 'config/config.php';

$pageTitle = "ඉරිදා ලංකාදීප | Paper Advertising";
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

<style>
    /* Custom Styling for Lankadeepa Branding */
    .lankadeepa-header {
        background: #8B0000; /* Dark Red */
        color: white;
        border-radius: 20px 20px 0 0;
        background-image: linear-gradient(135deg, #8B0000 0%, #A52A2A 100%);
    }
    .btn-lankadeepa {
        background-color: #8B0000;
        border-color: #8B0000;
        color: white;
    }
    .btn-lankadeepa:hover {
        background-color: #660000;
        border-color: #660000;
        color: white;
    }
    .text-lankadeepa {
        color: #8B0000;
    }
    .border-lankadeepa {
        border-color: #8B0000 !important;
    }
    .bg-lankadeepa-subtle {
        background-color: #f8d7da; /* Light Red */
        color: #842029;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header lankadeepa-header p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1 fw-bold" style="font-family: 'Inter', sans-serif;">ඉරිදා ලංකාදීප</h4>
                            <p class="mb-0 small opacity-75 text-white">Sunday Lankadeepa Paper Advertisement</p>
                        </div>
                        <i class="fas fa-newspaper fa-2x opacity-50"></i>
                    </div>
                    <div class="mt-3 badge bg-white text-dark shadow-sm">
                        <i class="far fa-clock me-1"></i> Next Closing: <?= date('M d, Y', strtotime($nextFriday)) ?>
                    </div>
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

                        <div class="section-block mb-5">
                            <h5 class="text-lankadeepa fw-bold mb-3 border-bottom pb-2">1. Ad Dimensions</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Width (cm)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="width_cm" id="width_cm" class="form-control" required min="1" value="5">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Height (cm)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" name="height_cm" id="height_cm" class="form-control" required min="1" value="5">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="bg-light p-3 rounded-3 border-start border-4 border-lankadeepa text-center">
                                        <small class="text-muted d-block text-uppercase fw-bold">Total Cost</small>
                                        <h3 class="text-lankadeepa fw-bold mb-0">LKR <span id="total_price">0.00</span></h3>
                                        <small class="text-muted">(Rate: LKR <?= number_format($rate, 2) ?> per cm²)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-block mb-5">
                            <h5 class="text-lankadeepa fw-bold mb-3 border-bottom pb-2">2. Advertisement Content</h5>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">Ad Text / Description</label>
                                <textarea name="ad_content" class="form-control" rows="5" placeholder="Type your advertisement text here..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">Upload Artwork (Optional)</label>
                                <input type="file" name="ad_image" class="form-control" accept="image/*,application/pdf">
                                <div class="form-text text-muted">If you have a pre-designed image, upload it here.</div>
                            </div>
                        </div>

                        <div class="section-block mb-5">
                            <h5 class="text-lankadeepa fw-bold mb-3 border-bottom pb-2">3. Your Contact Details</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Mobile Number</label>
                                    <input type="text" name="contact_mobile" class="form-control" required placeholder="07xxxxxxxx">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">WhatsApp Number</label>
                                    <input type="text" name="contact_whatsapp" class="form-control" placeholder="07xxxxxxxx">
                                </div>
                            </div>
                        </div>

                        <div class="section-block mb-4">
                            <h5 class="text-lankadeepa fw-bold mb-3 border-bottom pb-2">4. Payment Verification</h5>
                            <div class="mb-4">
                                <div class="alert bg-lankadeepa-subtle border-0">
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
                                <div class="form-text">Please upload a clear photo/scan of your bank transfer slip.</div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-lankadeepa btn-lg fw-bold shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i> Submit to Lankadeepa
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
