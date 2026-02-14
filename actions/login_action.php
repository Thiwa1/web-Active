<?php
/**
 * PRO PLUS AUTHENTICATION ENGINE
 * Features: CSRF Protection, Session Fixation Defense, LOB Handling, and Audit Logging
 */

// 1. Strict Error Handling & Environment Setup
ini_set('display_errors', 0); // Disable in production
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Load Configuration
$configPath = dirname(__DIR__) . '/config/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
    // Load ReCaptcha
    require_once dirname(__DIR__) . '/classes/ReCaptcha.php';
} else {
    error_log("Critical: Config missing at $configPath");
    die("Internal System Error.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    try {
        // 3. CSRF Protection Check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            throw new Exception("Security token mismatch. Please try again.");
        }

        // 3.5. reCAPTCHA Verification
        if (!isset($_POST['recaptcha_token']) || !ReCaptcha::verify($_POST['recaptcha_token'], 'login')) {
            throw new Exception("Security check failed (reCAPTCHA). Please try again.");
        }

        // 4. Input Sanitization & Rate Limiting (Sanitization only here)
        $email = filter_var(trim($_POST['user_email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['user_password'] ?? '';

        if (empty($email) || empty($password)) {
            throw new Exception("Please fill in all fields.");
        }

        // 5. Secure Database Query
        $stmt = $pdo->prepare("SELECT * FROM user_table WHERE user_email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // 6. Account Status Verification
            if ((int)$user['user_block'] === 1) {
                throw new Exception("This account has been suspended. Contact support.");
            }

            // 7. Robust VARBINARY/LOB Password Extraction
            $db_password_data = $user['user_password'];
            if (is_resource($db_password_data)) {
                $db_hash = stream_get_contents($db_password_data);
            } else {
                $db_hash = (string)$db_password_data;
            }
            $db_hash = trim($db_hash);

            // 8. Cryptographic Verification
            if (password_verify($password, $db_hash)) {
                
                /**
                 * PRO PLUS SECURITY: Session Regeneration
                 * Prevents Session Fixation attacks by changing the ID on login.
                 */
                session_regenerate_id(true);

                // 9. Establish Session Context
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_type']  = trim($user['user_type']);
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['last_login'] = time();

                // Log Admin Logins (PaperAdmin & Admin)
                $role = strtolower(trim($user['user_type']));
                if ($role === 'paperadmin' || $role === 'admin') {
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $pdo->prepare("INSERT INTO admin_login_logs (user_id, ip_address) VALUES (?, ?)")->execute([$user['id'], $ip]);
                }

                // 10. Intelligent Redirection Logic
                $redirectMap = [
                    'employer'   => '../employer/dashboard.php',
                    'admin'      => '../admin/dashboard.php',
                    'employee'   => '../employee/dashboard.php',
                    'candidate'  => '../employee/dashboard.php',
                    'seeker'     => '../employee/dashboard.php',
                    'paperadmin' => '../admin/manage_paper_ads.php'
                ];

                $location = $redirectMap[$role] ?? '../index.php';
                
                header("Location: " . $location);
                exit();

            } else {
                // Generic error to prevent account enumeration
                throw new Exception("Invalid email or password.");
            }
        } else {
            throw new Exception("Invalid email or password.");
        }

    } catch (Exception $e) {
        // Log the real error for the admin, but show the user the Exception message
        error_log("Login attempt failed for $email: " . $e->getMessage());
        header("Location: ../login.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}