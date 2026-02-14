<?php
session_start();
require_once 'config/config.php';

$pageTitle = "ඉරිදා ලංකාදීප | Paper Advertising";
include 'layout/header.php';

// Fetch Bank Details
$stmt = $pdo->query("SELECT * FROM system_bank_accounts LIMIT 1");
$bank = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Newspapers and Rates
$papers = $pdo->query("SELECT * FROM newspapers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$rates = [];
foreach ($papers as $p) {
    $r = $pdo->prepare("SELECT * FROM newspaper_rates WHERE newspaper_id = ?");
    $r->execute([$p['id']]);
    $rates[$p['id']] = $r->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch VAT setting
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'paper_ad_vat_percent'");
$stmt->execute();
$vatPercent = $stmt->fetchColumn() ?: 18; // Default 18%

// Calculate Next Closing Date
$nextFriday = date('Y-m-d', strtotime('next Friday'));

?>

<style>
    .lankadeepa-header {
        background: #8B0000;
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
    .text-lankadeepa { color: #8B0000; }
    .bg-lankadeepa-subtle { background-color: #f8d7da; color: #842029; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header lankadeepa-header p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1 fw-bold" style="font-family: 'Inter', sans-serif;">Newspaper Advertising</h4>
                            <p class="mb-0 small opacity-75 text-white">Publish your ad in top national newspapers</p>
                        </div>
                        <i class="fas fa-newspaper fa-2x opacity-50"></i>
                    </div>
                    <div class="mt-3 badge bg-white text-dark shadow-sm">
                        <i class="far fa-clock me-1"></i> Next Deadline: <?= date('M d, Y', strtotime($nextFriday)) ?>
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
                        <input type="hidden" id="vat_percent" value="<?= $vatPercent ?>">
                        <!-- JSON Data for JS -->
                        <script>
                            const rateData = <?= json_encode($rates) ?>;
                        </script>

                        <div class="section-block mb-5">
                            <h5 class="text-lankadeepa fw-bold mb-3 border-bottom pb-2">1. Select Publication & Rate</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Newspaper</label>
                                    <select name="newspaper_id" id="newspaper_id" class="form-select" required onchange="updateRates()">
                                        <option value="">Select Newspaper...</option>
                                        <?php foreach($papers as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Ad Type / Description</label>
                                    <select name="rate_id" id="rate_id" class="form-select" required onchange="calculatePrice()">
                                        <option value="">Select Newspaper First</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="section-block mb-5">
                            <h5 class="text-lankadeepa fw-bold mb-3 border-bottom pb-2">2. Dimensions</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Height (cm)</label>
                                    <input type="number" step="0.1" name="height_cm" id="height_cm" class="form-control" required min="1" value="5" oninput="calculatePrice()">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Columns</label>
                                    <input type="number" step="1" name="columns" id="columns" class="form-control" required min="1" value="1" oninput="calculatePrice()">
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="bg-light p-3 rounded-3 border-start border-4 border-lankadeepa">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted small">Base Amount:</span>
                                            <span class="fw-bold small">LKR <span id="base_price">0.00</span></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">VAT (<?= $vatPercent ?>%):</span>
                                            <span class="fw-bold small text-danger">+ LKR <span id="vat_amount">0.00</span></span>
                                        </div>
                                        <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                                            <span class="text-uppercase fw-bold text-dark">Total Payable</span>
                                            <h3 class="text-lankadeepa fw-bold mb-0">LKR <span id="total_price">0.00</span></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-block mb-5">
                            <h5 class="text-lankadeepa fw-bold mb-3 border-bottom pb-2">3. Content & Contact</h5>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">Ad Content</label>
                                <textarea name="ad_content" class="form-control" rows="4" placeholder="Type your advertisement text here..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">Upload Design (Optional)</label>
                                <input type="file" name="ad_image" class="form-control" accept="image/*,application/pdf">
                            </div>
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
                            <h5 class="text-lankadeepa fw-bold mb-3 border-bottom pb-2">4. Payment</h5>
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
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-lankadeepa btn-lg fw-bold shadow-sm">
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
    function updateRates() {
        const paperId = document.getElementById('newspaper_id').value;
        const rateSelect = document.getElementById('rate_id');
        rateSelect.innerHTML = '<option value="">Select Ad Type...</option>';

        if (paperId && rateData[paperId]) {
            rateData[paperId].forEach(rate => {
                const option = document.createElement('option');
                option.value = rate.id;
                option.dataset.rate = rate.rate;
                option.textContent = `${rate.description} - LKR ${rate.rate}`;
                rateSelect.appendChild(option);
            });
        }
        calculatePrice();
    }

    function calculatePrice() {
        const rateSelect = document.getElementById('rate_id');
        const selectedOption = rateSelect.options[rateSelect.selectedIndex];
        const rate = selectedOption ? parseFloat(selectedOption.dataset.rate) || 0 : 0;

        const h = parseFloat(document.getElementById('height_cm').value) || 0;
        const c = parseFloat(document.getElementById('columns').value) || 0;
        const vatPercent = parseFloat(document.getElementById('vat_percent').value) || 0;

        const base = rate * h * c;
        const vat = base * (vatPercent / 100);
        const total = base + vat;

        document.getElementById('base_price').textContent = base.toFixed(2);
        document.getElementById('vat_amount').textContent = vat.toFixed(2);
        document.getElementById('total_price').textContent = total.toFixed(2);
    }
</script>

<?php include 'layout/footer.php'; ?>
