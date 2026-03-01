<?php
session_start();
require_once '../../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    die("CSRF Token Validation Failed");
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE advertising_table SET Approved = 0 WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: ../manage_jobs.php?msg=Job taken offline successfully.");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../manage_jobs.php?error=Invalid ID");
}
