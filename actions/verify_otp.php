<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../verify_otp.php"); exit();
}

$otp_input = trim($_POST['otp_code']);
$mobile = $_SESSION['reset_mobile'] ?? '';

if (empty($mobile) || empty($otp_input)) {
    header("Location: ../verify_otp.php?error=Invalid Request"); exit();
}

try {
    // 1. Verify OTP
    // Check if OTP matches AND was sent within last 15 minutes
    $stmt = $pdo->prepare("SELECT id FROM user_table WHERE mobile_number = ? AND send_opt = ? AND send_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute([$mobile, $otp_input]);
    $user = $stmt->fetch();

    if ($user) {
        // OTP Valid!
        $_SESSION['reset_user_id'] = $user['id']; // Allow password reset
        header("Location: ../reset_password.php");
    } else {
        header("Location: ../verify_otp.php?error=Invalid or Expired Code");
    }

} catch (Exception $e) {
    header("Location: ../verify_otp.php?error=System Error");
}
