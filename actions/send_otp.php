<?php
session_start();
require_once '../config/config.php';
require_once '../config/mail_helper.php';
require_once '../classes/NotifySMS.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forgot_password.php"); exit();
}

$channel = $_POST['channel'] ?? 'email';
$emailInput = trim($_POST['email'] ?? '');
$mobileInput = trim($_POST['mobile_number'] ?? '');

$user = null;

try {
    // 1. Resolve User based on Input
    if ($channel === 'email' && !empty($emailInput)) {
        $stmt = $pdo->prepare("SELECT id, user_email, mobile_number FROM user_table WHERE user_email = ?");
        $stmt->execute([$emailInput]);
        $user = $stmt->fetch();
        if (!$user) {
            header("Location: ../forgot_password.php?error=Email not found"); exit();
        }
    } elseif ($channel === 'sms' && !empty($mobileInput)) {
        $stmt = $pdo->prepare("SELECT id, user_email, mobile_number FROM user_table WHERE mobile_number = ?");
        $stmt->execute([$mobileInput]);
        $user = $stmt->fetch();
        if (!$user) {
            header("Location: ../forgot_password.php?error=Mobile number not found"); exit();
        }
    } else {
        header("Location: ../forgot_password.php?error=Please provide valid contact details"); exit();
    }

    // 2. Generate OTP (4 digits for simplicity)
    $otp = rand(1000, 9999);
    
    // 3. Update User Table with OTP and Send Time
    $sql = "UPDATE user_table SET send_opt = ?, send_time = NOW() WHERE id = ?";
    $pdo->prepare($sql)->execute([$otp, $user['id']]);

    // 4. Send OTP via Email or SMS
    $sent = false;
    $errorMsg = '';

    if ($channel === 'sms') {
        $sms = new NotifySMS($pdo);
        if ($sms->sendOTP($user['mobile_number'], $otp)) {
            $sent = true;
        } else {
            $errorMsg = "Failed to send SMS. Check system logs.";
        }
    } else {
        // Email Fallback
        if (!empty($user['user_email'])) {
            $subject = "Your Password Reset Code";
            $msg = "<p>Your One-Time Password (OTP) for password recovery is:</p>
                    <h1 style='letter-spacing: 5px; color: #333;'>$otp</h1>
                    <p>This code expires in 10 minutes.</p>";

            $sent = sendEmail($user['user_email'], $subject, $msg, null, $emailError);
            if(!$sent) $errorMsg = $emailError;
        }
    }

    // 5. Store Identifier in Session for Verification Step
    $_SESSION['reset_user_id'] = $user['id'];
    if(!empty($user['mobile_number'])) $_SESSION['reset_mobile'] = $user['mobile_number'];
    $_SESSION['reset_email'] = $user['user_email'];

    if ($sent) {
        header("Location: ../verify_otp.php?sent=1");
    } else {
        $debugMsg = $errorMsg ? " ($errorMsg)" : "";
        header("Location: ../forgot_password.php?error=Failed to send OTP.$debugMsg");
    }

} catch (Exception $e) {
    header("Location: ../forgot_password.php?error=System Error");
}
