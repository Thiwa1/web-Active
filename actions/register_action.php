<?php
/**
 * PRO PLUS REGISTRATION ENGINE - SCHEMA MATCHED VERSION
 */

require_once '../config/config.php';
require_once '../classes/ReCaptcha.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 0. reCAPTCHA Verification
    if (!isset($_POST['recaptcha_token']) || !ReCaptcha::verify($_POST['recaptcha_token'], 'register')) {
        header("Location: ../register.php?error=Security check failed. Please refresh and try again.");
        exit();
    }

    // 1. Data Collection & Sanitization
    $full_name       = htmlspecialchars(trim($_POST['full_name']), ENT_QUOTES, 'UTF-8');
    $user_email      = filter_var(trim($_POST['user_email']), FILTER_VALIDATE_EMAIL);
    $user_password   = $_POST['user_password'] ?? '';
    $country         = htmlspecialchars(trim($_POST['country'] ?? 'Sri Lanka'), ENT_QUOTES, 'UTF-8');
    $birthday        = $_POST['Birthday'] ?? null;
    $gender          = $_POST['male_female'] ?? 'Not Specified';
    $user_type       = trim($_POST['user_type']);
    $mobile          = preg_replace('/[^0-9+]/', '', $_POST['mobile_number']);
    $whatsapp        = preg_replace('/[^0-9+]/', '', $_POST['WhatsApp_number'] ?? $_POST['mobile_number']);
    $company_name    = htmlspecialchars(trim($_POST['company_name'] ?? ''), ENT_QUOTES, 'UTF-8');

    try {
        // 2. Initial Validation
        if (!$user_email) throw new Exception("A valid email address is required.");
        if (strlen($user_password) < 8) throw new Exception("Password must be at least 8 characters.");
        if (empty($birthday)) throw new Exception("Birthday is required by the system.");

        $pdo->beginTransaction();

        // 3. Check for existing email
        $checkStmt = $pdo->prepare("SELECT id FROM user_table WHERE user_email = ? LIMIT 1");
        $checkStmt->execute([$user_email]);
        if ($checkStmt->fetch()) throw new Exception("This email is already registered.");

        // 4. Secure Password Hashing
        $hashed_password = password_hash($user_password, PASSWORD_BCRYPT);

        // 5. Insert into user_table (Matches your User_Table schema)
        $sqlUser = "INSERT INTO user_table (
            user_email, user_password, full_name, Birthday, 
            male_female, user_type, mobile_number, WhatsApp_number, 
            country, user_active, user_block
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)";

        $stmtUser = $pdo->prepare($sqlUser);
        
        // Using bindValue to ensure compatibility with VARBINARY password field
        $stmtUser->bindValue(1, $user_email);
        $stmtUser->bindValue(2, $hashed_password); 
        $stmtUser->bindValue(3, $full_name);
        $stmtUser->bindValue(4, $birthday);
        $stmtUser->bindValue(5, $gender);
        $stmtUser->bindValue(6, $user_type);
        $stmtUser->bindValue(7, $mobile);
        $stmtUser->bindValue(8, $whatsapp);
        $stmtUser->bindValue(9, $country);
        
        $stmtUser->execute();
        $user_id = $pdo->lastInsertId();

        // 6. Role-Based Profile Initialization (FIXED COLUMN NAMES)
        if (strtolower($user_type) === 'employer') {
            // FIXED: Changed employer_mobile to employer_mobile_no to match your Workbench SQL
            $stmtProfile = $pdo->prepare("INSERT INTO employer_profile (link_to_user, employer_name, employer_mobile_no, employer_whatsapp_no) VALUES (?, ?, ?, ?)");
            $stmtProfile->execute([$user_id, $company_name ?: $full_name, $mobile, $whatsapp]);
        } else {
            // Inserts into employee_profile_seeker as per your schema
            // NOTE: Using employee_profile_seeker instead of candidate_profile to match dashboard logic
            $stmtProfile = $pdo->prepare("INSERT INTO employee_profile_seeker (link_to_user, employee_full_name) VALUES (?, ?)");
            $stmtProfile->execute([$user_id, $full_name]);
        }

        $pdo->commit();

        // 7. Send Welcome Email
        require_once '../config/mail_helper.php';
        $subject = "Welcome to JobPortal Pro!";
        $body = "
            <h2>Welcome, " . htmlspecialchars($full_name) . "!</h2>
            <p>Thank you for registering with us.</p>
            <p>Your account has been created successfully. You can now login and start using our services.</p>
            <p><a href='http://" . $_SERVER['HTTP_HOST'] . "/login.php'>Login Here</a></p>
        ";
        sendEmail($user_email, $subject, $body);

        // Success Redirection
        header("Location: ../login.php?registration=success");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Registration Error: " . $e->getMessage());
        header("Location: ../register.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}