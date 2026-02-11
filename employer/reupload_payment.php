<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header("Location: ../login.php"); exit();
}

$payment_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$payment_id) { header("Location: manage_jobs.php"); exit(); }

try {
    // Identity-Safe Fetch
    $stmt = $pdo->prepare("SELECT p.*, e.employer_name 
                           FROM payment_table p 
                           JOIN employer_profile e ON p.employer_link = e.id 
                           WHERE p.id = ? AND e.link_to_user = ?");
    $stmt->execute([$payment_id, $user_id]);
    $payment = $stmt->fetch();

    if (!$payment) { die("Record access denied."); }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_FILES['new_slip']['tmp_name'])) {
            $slip_data = file_get_contents($_FILES['new_slip']['tmp_name']);
            
            // Re-submit logic: Reset status to Pending (0) and clear previous rejection
            $update = $pdo->prepare("UPDATE payment_table SET 
                                     Payment_slip = ?, 
                                     Approval = 0, 
                                     Reject_comment = NULL,
                                     payment_date = NOW() 
                                     WHERE id = ?");
            
            $update->bindParam(1, $slip_data, PDO::PARAM_LOB);
            $update->bindParam(2, $payment_id);
            
            if ($update->execute()) {
                header("Location: payment_history.php?msg=resubmitted");
                exit();
            }
        } else {
            $error = "A valid image file is required to proceed.";
        }
    }
} catch (PDOException $e) { die("System Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resubmit Payment | Enterprise Suite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            background-color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .reupload-container { max-width: 900px; margin: 60px auto; }

        .glass-card {
            background: white;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .rejection-banner {
            background: #fff1f2;
            border-bottom: 2px solid #fecdd3;
            padding: 25px;
        }

        /* Drop Zone UI */
        .drop-zone {
            border: 2px dashed #e2e8f0;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            transition: 0.3s;
            background: #f8fafc;
            cursor: pointer;
        }
        .drop-zone:hover { border-color: #4f46e5; background: #f5f3ff; }
        
        .old-slip-preview {
            width: 100%; height: 150px;
            object-fit: cover;
            border-radius: 12px;
            filter: grayscale(1);
            opacity: 0.5;
        }

        .step-pill {
            width: 28px; height: 28px;
            background: #4f46e5;
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 800;
            margin-right: 10px;
        }
    </style>
</head>
<body>

<div class="container reupload-container">
    <div class="glass-card">
        <div class="rejection-banner">
            <div class="d-flex gap-3">
                <div class="text-danger"><i class="fas fa-exclamation-circle fa-2x"></i></div>
                <div>
                    <h5 class="fw-800 text-danger mb-1">Action Required: Payment Verification Failed</h5>
                    <p class="text-muted small mb-0">Your previous submission was declined for the following reason:</p>
                    <div class="mt-2 p-2 px-3 bg-white rounded-3 border border-danger-subtle fw-700 text-dark small">
                        "<?= htmlspecialchars($payment['Reject_comment'] ?? 'Information on slip is illegible or incorrect.') ?>"
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 p-md-5">
            <div class="row g-5">
                <div class="col-md-5">
                    <h6 class="fw-800 mb-4 uppercase small letter-spacing-1">How to fix this:</h6>
                    
                    <div class="mb-4 d-flex align-items-start">
                        <div class="step-pill">1</div>
                        <div class="small fw-600 text-muted">Ensure the amount is exactly <span class="text-dark fw-800"><?= number_format($payment['Totaled_received'], 2) ?> LKR</span>.</div>
                    </div>
                    <div class="mb-4 d-flex align-items-start">
                        <div class="step-pill">2</div>
                        <div class="small fw-600 text-muted">Capture the full receipt including the Transaction Reference Number.</div>
                    </div>
                    <div class="mb-4 d-flex align-items-start">
                        <div class="step-pill">3</div>
                        <div class="small fw-600 text-muted">Ensure the image is clear and text is readable (No blurs).</div>
                    </div>

                    <div class="mt-5">
                        <label class="small fw-800 text-muted mb-2 uppercase">Previous Submission</label>
                        <img src="data:image/jpeg;base64,<?= base64_encode($payment['Payment_slip']) ?>" class="old-slip-preview border">
                        <div class="extra-small text-muted mt-2">This image was marked as invalid.</div>
                    </div>
                </div>

                <div class="col-md-7 border-start ps-md-5">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <label class="form-label fw-800">Upload New Proof of Payment</label>
                        
                        <div class="drop-zone mb-4" onclick="document.getElementById('new_slip_input').click()">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h6 class="fw-700 mb-1">Click to browse or Drag & Drop</h6>
                            <p class="text-muted extra-small">JPG, PNG or PDF (Max 2MB)</p>
                            <input type="file" name="new_slip" id="new_slip_input" class="d-none" accept="image/*" required onchange="previewFile()">
                        </div>

                        <div id="file-preview-zone" class="d-none mb-4">
                            <div class="p-2 border rounded-4 d-flex align-items-center gap-3">
                                <i class="fas fa-file-image fa-2x text-primary"></i>
                                <div class="flex-grow-1">
                                    <div id="file-name" class="small fw-800">filename.jpg</div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light rounded-circle" onclick="resetInput()"><i class="fas fa-times"></i></button>
                            </div>
                        </div>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger p-3 small border-0 rounded-4"><?= $error ?></div>
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-800 py-3 shadow-lg border-0">
                                Resubmit for Approval
                            </button>
                            <a href="manage_jobs.php" class="btn btn-link mt-2 text-muted text-decoration-none fw-600 small">Return to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function previewFile() {
        const file = document.getElementById('new_slip_input').files[0];
        if (file) {
            document.getElementById('file-preview-zone').classList.remove('d-none');
            document.getElementById('file-name').textContent = file.name;
        }
    }

    function resetInput() {
        document.getElementById('new_slip_input').value = "";
        document.getElementById('file-preview-zone').classList.add('d-none');
    }
</script>

</body>
</html>