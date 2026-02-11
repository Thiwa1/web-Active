<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../reset_password.php"); exit();
}

$pass = $_POST['password'];
$confirm = $_POST['confirm_password'];
$user_id = $_SESSION['reset_user_id'] ?? 0;

if (!$user_id) {
    header("Location: ../login.php"); exit();
}

if (strlen($pass) < 6) {
    header("Location: ../reset_password.php?error=Password must be at least 6 characters"); exit();
}

if ($pass !== $confirm) {
    header("Location: ../reset_password.php?error=Passwords do not match"); exit();
}

try {
    // Hash password
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    // Update DB
    // Clear send_opt to prevent reuse
    $sql = "UPDATE user_table SET user_password = ?, send_opt = NULL WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hash, $user_id]);

    // Clear session
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_mobile']);

    header("Location: ../login.php?success=Password updated successfully");

} catch (Exception $e) {
    header("Location: ../reset_password.php?error=System Error");
}
