<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    header("Location: ../login.php"); exit();
}

// Direct Access Prevention: Redirect to Dashboard if not AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $queryParams = $_GET;
    $queryString = http_build_query($queryParams);
    $redirectUrl = 'dashboard.php?page=bank_details' . ($queryString ? '&' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Details | JobQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .glass-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .bank-card { 
            border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; transition: 0.3s; background: white; 
            border-left: 5px solid #4f46e5;
        }
        .bank-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">Payment Information</h2>
            <p class="text-muted">Use these details to transfer payments for your job postings.</p>
        </div>
    </div>

    <div class="glass-card">
        <h5 class="fw-bold mb-4">Active Bank Accounts</h5>
        <div id="bank-list-container" class="row g-4">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="text-muted mt-2">Loading bank details...</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Fetch and Render Bank Accounts
    $.get('../actions/fetch_banks.php', function(data) {
        let html = '';
        if(data.length === 0) {
            html = '<div class="col-12 text-center text-muted">No bank accounts configured. Please contact support.</div>';
        } else {
            data.forEach(acc => {
                html += `
                <div class="col-md-6">
                    <div class="bank-card h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold text-dark m-0">${acc.bank_name}</h5>
                            <i class="fas fa-university text-primary fa-2x opacity-25"></i>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-bold">Account Number</small>
                            <div class="fs-4 font-monospace fw-bold text-primary">${acc.account_number}</div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted text-uppercase fw-bold">Branch</small>
                                <div class="fw-bold">${acc.branch_name}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted text-uppercase fw-bold">Branch Code</small>
                                <div class="fw-bold">${acc.branch_code}</div>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
        }
        $('#bank-list-container').html(html);
    }, 'json');
</script>

</body>
</html>
