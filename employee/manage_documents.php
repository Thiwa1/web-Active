<?php
session_start();
require_once '../config/config.php';

// 1. Configuration & Security
$site_name = "JobQuest Pro"; 

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_msg = "";
$status_type = "";

try {
    // 2. Resolve Profile Identity
    $stmt = $pdo->prepare("SELECT id FROM employee_profile_seeker WHERE link_to_user = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        die("Critical Error: Profile not found. Please contact support.");
    }
    $profile_id = $profile['id'];

    // Logic handled by actions/upload_document.php and actions/delete_document.php
    // to support SPA architecture.

    // 5. Fetch Documents
    $stmt = $pdo->prepare("SELECT id, document_type FROM employee_document WHERE link_to_employee_profile = ? ORDER BY id DESC");
    $stmt->execute([$profile_id]);
    $documents = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $status_msg = "A database error occurred.";
    $status_type = "danger";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Vault | <?= $site_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --vault-blue: #2563eb; --vault-bg: #f1f5f9; }
        body { background-color: var(--vault-bg); font-family: 'Inter', sans-serif; }
        
        .vault-card { border: none; border-radius: 20px; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        
        /* Modern File Row */
        .file-item { 
            background: white; 
            border-radius: 16px; 
            padding: 1rem; 
            transition: all 0.2s ease; 
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .file-item:hover { border-color: var(--vault-blue); transform: translateX(5px); }
        
        .file-icon { 
            width: 48px; height: 48px; 
            background: #eff6ff; 
            color: var(--vault-blue); 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.2rem;
        }

        .btn-upload { background: var(--vault-blue); border: none; border-radius: 12px; font-weight: 600; padding: 12px; }
        .btn-action { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; }
        
        .upload-sidebar { position: sticky; top: 2rem; }
        
        .empty-state { padding: 4rem 2rem; text-align: center; color: #94a3b8; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row mb-5 align-items-center">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Document Vault</li>
                </ol>
            </nav>
            <h2 class="fw-bold m-0"><i class="fas fa-shield-halved text-primary me-2"></i>Document Vault</h2>
            <p class="text-muted m-0">Securely manage your credentials for job applications.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="badge bg-white text-dark border p-2 px-3 rounded-pill shadow-sm">
                <i class="fas fa-circle text-success me-1 small"></i> Verification Status: Level 1
            </div>
        </div>
    </div>

    <?php if(isset($_GET['upload_success'])): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Document encrypted and stored successfully.
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['delete_success'])): ?>
        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 fade show" role="alert">
            <i class="fas fa-trash-can me-2"></i> Document deleted securely.
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> Action failed. Please try again.
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card vault-card p-4 upload-sidebar">
                <h5 class="fw-bold mb-4">Secure Upload</h5>
                <form action="actions/upload_document.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Category</label>
                        <select name="document_type" class="form-select border-0 bg-light rounded-3 py-2" required>
                            <option value="" selected disabled>Choose document...</option>
                            <option value="NIC / Passport">Identity (NIC/Passport)</option>
                            <option value="Degree Certificate">Academic Degree</option>
                            <option value="Service Letter">Work Experience Letter</option>
                            <option value="Professional License">Professional License</option>
                            <option value="Other">Other Certificate</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Attach File</label>
                        <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                            <input type="file" name="doc_file" class="form-control form-control-sm border-0 bg-transparent" required>
                            <div class="form-text x-small mt-2" style="font-size: 0.7rem;">
                                Supported: PDF, JPG, PNG <br>Max Size: 10MB
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-upload w-100 shadow-sm">
                        <i class="fas fa-cloud-arrow-up me-2"></i>Upload to Vault
                    </button>
                </form>
                
                <div class="mt-4 p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                    <p class="small text-primary m-0">
                        <i class="fas fa-info-circle me-1"></i> 
                        These documents are only visible to employers when you explicitly apply for a job.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card vault-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0">Stored Assets</h5>
                    <span class="text-muted small"><?= count($documents) ?> Total Items</span>
                </div>

                <?php if (empty($documents)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-shield fa-4x opacity-25 mb-3"></i>
                        <h5>No documents yet</h5>
                        <p class="small">Upload your NIC or Degree certificates to increase your profile trust score.</p>
                    </div>
                <?php else: ?>
                    <div class="file-list">
                        <?php foreach ($documents as $doc): ?>
                            <div class="file-item">
                                <div class="d-flex align-items-center">
                                    <div class="file-icon me-3">
                                        <?php 
                                            // Dynamic Icon based on type
                                            $icon = "fa-file-lines";
                                            if(strpos($doc['document_type'], 'NIC') !== false) $icon = "fa-id-card";
                                            if(strpos($doc['document_type'], 'Degree') !== false) $icon = "fa-graduation-cap";
                                            echo '<i class="fas '.$icon.'"></i>';
                                        ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($doc['document_type']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Verified Storage Slot #<?= $doc['id'] ?></div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="actions/download_doc.php?id=<?= $doc['id'] ?>" target="_blank" class="btn-action bg-light text-primary" title="View/Download">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="actions/delete_document.php?id=<?= $doc['id'] ?>" class="btn-action bg-light text-danger"
                                       onclick="return confirm('Delete this document permanently?')" title="Delete">
                                        <i class="fas fa-trash-can"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-auto pt-4 text-center">
                    <p class="text-muted x-small mb-0"><i class="fas fa-lock me-1"></i> AES-256 Bit Encryption Active for All Assets</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>