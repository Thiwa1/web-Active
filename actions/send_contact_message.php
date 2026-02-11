<?php
session_start();
require_once '../config/config.php';
require_once '../config/mail_helper.php';

// Check Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../contact.php");
    exit();
}

// Sanitize Inputs
$name = htmlspecialchars(trim($_POST['name'] ?? ''));
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$subject = htmlspecialchars(trim($_POST['subject'] ?? 'General Inquiry'));
$message = htmlspecialchars(trim($_POST['message'] ?? ''));

// Validate
if (!$name || !$email || !$message) {
    header("Location: ../contact.php?error=All fields are required");
    exit();
}

// 1. Send Verification to User
$userSubject = "Message Received: $subject";
$userBody = "
<p>Hello $name,</p>
<p>Thank you for contacting us. We have successfully received your message regarding '<strong>$subject</strong>'.</p>
<p>Our support team will review your inquiry and respond shortly.</p>
<br>
<p>Best Regards,<br>JobPortal Support Team</p>
";
sendEmail($email, $userSubject, $userBody);

// 2. Forward to Admin
$to = "srithiwankara@gmail.com";
$emailSubject = "New Contact Msg from $name: " . $subject;
$emailBody = "
<h3>New Contact Message</h3>
<p><strong>Name:</strong> $name</p>
<p><strong>Email:</strong> $email</p>
<p><strong>Subject:</strong> $subject</p>
<hr>
<p><strong>Message:</strong></p>
<p>" . nl2br($message) . "</p>
";

// Pass user email as reply-to for admin message
$errorDebug = '';
if (sendEmail($to, $emailSubject, $emailBody, $email, $errorDebug)) {
    header("Location: ../contact.php?msg=Message sent successfully! Please check your email.");
} else {
    // Include debug info in the error message for troubleshooting
    $msg = "Failed to send message. Please try again later.";
    if (!empty($errorDebug)) {
        $msg .= " (Debug: " . $errorDebug . ")";
    }
    header("Location: ../contact.php?error=" . urlencode($msg));
}
exit();
?>