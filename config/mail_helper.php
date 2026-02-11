<?php
/**
 * Email Helper
 * Handles sending emails using SMTP with SSL/TLS.
 *
 * NOTE: This implementation uses raw socket (fsockopen) instead of PHPMailer
 * because Composer is not available in this environment to install dependencies.
 * If PHPMailer becomes available, it is recommended to switch for better stability.
 */

function sendEmail($to, $subject, $message, $replyTo = null, &$errorDebug = null) {
    // Retrieve configuration or use defaults (for testing/fallback)
    $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'server386.web-hosting.com';
    $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 465;
    $smtpUser = defined('SMTP_USER') ? SMTP_USER : 'infor@tiptopvacancies.com';
    $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : 'Aa@@!21219125';
    $replyTo  = $replyTo ? $replyTo : $smtpUser;

    // --- PHPMailer Strategy (Preferred) ---
    $phpMailerBase = __DIR__ . '/../PHPMailer/src/';
    if (file_exists($phpMailerBase . 'PHPMailer.php')) {
        try {
            require_once $phpMailerBase . 'Exception.php';
            require_once $phpMailerBase . 'PHPMailer.php';
            require_once $phpMailerBase . 'SMTP.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Server settings
            // $mail->SMTPDebug = 2; // Enable verbose debug output
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $smtpPort;

            // Recipients
            $mail->setFrom($smtpUser, 'Tip Top Vacancies');
            $mail->addAddress($to);
            $mail->addReplyTo($replyTo);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
            return true;

        } catch (Exception $e) {
            // PHPMailer Exception
            if ($errorDebug !== null) {
                // $mail is defined inside try, might need check if exception happened before instantiation?
                // Catch generic \Exception or PHPMailer Exception
                // But wait, Exception class name collision if I use `use`.
                // I am using fully qualified names for PHPMailer, so Exception is global Exception (or PHPMailer's if namespaced).
                // PHPMailer throws \PHPMailer\PHPMailer\Exception
                $errMsg = method_exists($e, 'errorMessage') ? $e->errorMessage() : $e->getMessage();
                $errorDebug = "PHPMailer Error: " . $errMsg;
            }
            error_log("PHPMailer Error: " . $e->getMessage());

            // Fallback to Log if allowed
            if (defined('ALLOW_LOCAL_MAIL_LOG') && ALLOW_LOCAL_MAIL_LOG) {
                $logFile = __DIR__ . '/../uploads/email_debug.log';
                $logEntry = "[" . date('Y-m-d H:i:s') . "] (PHPMailer Fail) TO: $to | SUBJ: $subject | ERROR: " . $e->getMessage() . "\n";
                file_put_contents($logFile, $logEntry, FILE_APPEND);
                return true;
            }
            return false;
        }
    }

    // --- Legacy fsockopen Strategy (Fallback) ---
    $crlf = "\r\n";

    try {
        // Connect to SMTP server
        $socket = @fsockopen("ssl://" . $smtpHost, $smtpPort, $errno, $errstr, 5);
        if (!$socket) {
            throw new Exception("SMTP Connect Failed: $errstr ($errno)");
        }

        // Read server greeting
        $response = fgets($socket, 515);

        // HELO/EHLO
        fwrite($socket, 'EHLO ' . $smtpHost . $crlf);
        fgets($socket, 515);

        // AUTH LOGIN
        fwrite($socket, 'AUTH LOGIN' . $crlf);
        fgets($socket, 515);

        fwrite($socket, base64_encode($smtpUser) . $crlf);
        fgets($socket, 515);

        fwrite($socket, base64_encode($smtpPass) . $crlf);
        $authRes = fgets($socket, 515);

        if (strpos($authRes, '235') === false) {
            error_log("SMTP Auth Failed: $authRes");
            if ($errorDebug !== null) $errorDebug = "SMTP Auth Failed: " . trim($authRes);
            fclose($socket);
            return false;
        }

        // MAIL FROM
        fwrite($socket, "MAIL FROM: <$smtpUser>$crlf");
        fgets($socket, 515);

        // RCPT TO
        fwrite($socket, "RCPT TO: <$to>$crlf");
        fgets($socket, 515);

        // DATA
        fwrite($socket, "DATA$crlf");
        fgets($socket, 515);

        // Message Headers & Body
        // Note: replyTo logic was moved up for PHPMailer, reused here

        $headers  = "MIME-Version: 1.0" . $crlf;
        $headers .= "Content-type: text/html; charset=UTF-8" . $crlf;
        $headers .= "From: TipTop Vacancies <$smtpUser>" . $crlf;
        $headers .= "Reply-To: $replyTo" . $crlf;
        $headers .= "To: $to" . $crlf;
        $headers .= "Subject: $subject" . $crlf;

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Inter', sans-serif; background-color: #f8fafc; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
                .header { text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 20px; }
                .footer { text-align: center; color: #94a3b8; font-size: 12px; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
                .btn { display: inline-block; padding: 12px 24px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='color: #0f172a; margin:0;'>TipTop Vacancies</h2>
                </div>
                <div style='color: #334155; line-height: 1.6;'>
                    $message
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " TipTop Vacancies. All rights reserved.<br>
                    Homagama, Sri Lanka
                </div>
            </div>
        </body>
        </html>";

        fwrite($socket, $headers . $crlf . $body . $crlf . "." . $crlf);
        fgets($socket, 515);

        // QUIT
        fwrite($socket, "QUIT$crlf");
        fclose($socket);

        return true;

    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        if ($errorDebug !== null) {
            $errorDebug = $e->getMessage();
        }

        // Fallback for Localhost/Dev (Log to file)
        if (defined('ALLOW_LOCAL_MAIL_LOG') && ALLOW_LOCAL_MAIL_LOG) {
            $logFile = __DIR__ . '/../uploads/email_debug.log';
            $logEntry = "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJ: $subject | MSG: " . strip_tags(substr($message, 0, 100)) . "...\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            return true; // Simulate success
        }

        return false;
    }
}
?>