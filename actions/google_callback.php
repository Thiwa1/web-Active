<?php
session_start();
require_once '../config/config.php';
require_once '../classes/GoogleAuth.php';

$google = new GoogleAuth($pdo);

if (isset($_GET['code'])) {
    $tokenData = $google->getToken($_GET['code']);

    if (isset($tokenData['access_token'])) {
        $userInfo = $google->getUserInfo($tokenData['access_token']);

        if (isset($userInfo['sub'])) { // 'sub' is Google ID
            $gid = $userInfo['sub'];
            $email = $userInfo['email'];
            $name = $userInfo['name'];
            $picture = $userInfo['picture'] ?? '';

            // 1. Check by Google ID
            $stmt = $pdo->prepare("SELECT * FROM user_table WHERE google_id = ?");
            $stmt->execute([$gid]);
            $user = $stmt->fetch();

            if (!$user) {
                // 2. Check by Email
                $stmt = $pdo->prepare("SELECT * FROM user_table WHERE user_email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Link Account
                    $pdo->prepare("UPDATE user_table SET google_id = ? WHERE id = ?")->execute([$gid, $user['id']]);
                } else {
                    // 3. Register New User
                    // Use State param or default to Employee
                    $type = isset($_GET['state']) && in_array($_GET['state'], ['Employer', 'Employee']) ? $_GET['state'] : 'Employee';

                    // Generate random password
                    $pass = bin2hex(random_bytes(8));

                    $sql = "INSERT INTO user_table (user_email, user_password, full_name, user_type, user_active, google_id, mobile_number, WhatsApp_number, male_female, Birthday)
                            VALUES (?, ?, ?, ?, 1, ?, '', '', 'Other', '2000-01-01')";
                    $pdo->prepare($sql)->execute([$email, $pass, $name, $type, $gid]);

                    $uid = $pdo->lastInsertId();

                    // Create Profile Stub
                    if ($type == 'Employee') {
                        $pdo->prepare("INSERT INTO employee_profile_seeker (link_to_user, employee_full_name) VALUES (?, ?)")->execute([$uid, $name]);
                    } elseif ($type == 'Employer') {
                        $pdo->prepare("INSERT INTO employer_profile (link_to_user, employer_name) VALUES (?, ?)")->execute([$uid, $name]);
                    }

                    // Fetch newly created user
                    $stmt = $pdo->prepare("SELECT * FROM user_table WHERE id = ?");
                    $stmt->execute([$uid]);
                    $user = $stmt->fetch();
                }
            }

            // 4. Login Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_name'] = $user['full_name'];

            // Redirect based on type
            if ($user['user_type'] == 'Admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['user_type'] == 'Employer') {
                header("Location: ../employer/dashboard.php");
            } else {
                header("Location: ../employee/dashboard.php");
            }
            exit();
        }
    }
}

// Error or Cancel
header("Location: ../login.php?error=Google Authentication Failed");
exit();
?>