<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forgot_password.php"); exit();
}

$mobile = trim($_POST['mobile_number']);

if (empty($mobile)) {
    header("Location: ../forgot_password.php?error=Enter mobile number"); exit();
}

try {
    // 1. Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM user_table WHERE mobile_number = ?");
    $stmt->execute([$mobile]);
    $user = $stmt->fetch();

    if (!$user) {
        // Security: Don't reveal if user doesn't exist, but for UX maybe say generic error or pretend sent.
        // For this project, I'll be specific.
        header("Location: ../forgot_password.php?error=Number not found"); exit();
    }

    // 2. Generate OTP (4 digits for simplicity)
    $otp = rand(1000, 9999);
    
    // 3. Update User Table with OTP and Send Time
    // Using `send_opt` and `send_time` from schema
    $sql = "UPDATE user_table SET send_opt = ?, send_time = NOW() WHERE id = ?";
    $pdo->prepare($sql)->execute([$otp, $user['id']]);

    // 4. Send SMS (Simulation & Logging)
    // In production: Call SMS Gateway API here
    
    // Attempt to log to sms_logs (Handling FK constraint on job_id)
    try {
        // Find a placeholder job_id since table requires it
        $jobStmt = $pdo->query("SELECT id FROM advertising_table LIMIT 1");
        $placeholderJobId = $jobStmt->fetchColumn();

        if ($placeholderJobId) {
            $logSql = "INSERT INTO sms_logs (job_id, user_id, phone_number, status, sent_at) VALUES (?, ?, ?, ?, NOW())";
            $pdo->prepare($logSql)->execute([$placeholderJobId, $user['id'], $mobile, "OTP: $otp"]);
        }
    } catch (Exception $logEx) {
        // Ignore logging errors to ensure flow continues
        error_log("OTP Logging Failed: " . $logEx->getMessage());
    }

    // Store mobile in session to verify later
    $_SESSION['reset_mobile'] = $mobile;

    header("Location: ../verify_otp.php?sent=1");

} catch (Exception $e) {
    header("Location: ../forgot_password.php?error=System Error");
}
