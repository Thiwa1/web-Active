<?php
// Standalone Mail Tester
require_once '../config/config.php';
require_once '../config/mail_helper.php';

$message = "";
$status = "";
$debugInfo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['email'];
    $subject = "Test Email from Tip Top Vacancies";
    $body = "<p>Hello,</p><p>This is a test email to verify the SMTP configuration.</p><p>Time: " . date('Y-m-d H:i:s') . "</p>";

    // Pass debug variable by reference
    $smtpError = "";
    if (sendEmail($to, $subject, $body, null, $smtpError)) {
        $status = "success";
        $message = "Email sent successfully to $to!";
    } else {
        $status = "danger";
        $message = "Failed to send email.";
        $debugInfo = $smtpError;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mail Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="card p-4 mx-auto" style="max-width: 600px;">
        <h3>SMTP Mail Tester</h3>

        <?php if($message): ?>
            <div class="alert alert-<?= $status ?>">
                <strong><?= $message ?></strong>
                <?php if($debugInfo): ?>
                    <hr>
                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.85rem;"><?= htmlspecialchars($debugInfo) ?></pre>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Recipient Email</label>
                <input type="email" name="email" class="form-control" required placeholder="you@example.com">
            </div>
            <button type="submit" class="btn btn-primary">Send Test Email</button>
        </form>
    </div>
</body>
</html>
