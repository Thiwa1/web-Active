<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $url = trim($_POST['ad_url']);
    $media_name = trim($_POST['media_name']);
    $media_type = trim($_POST['media_type']);
    $target_district = trim($_POST['target_district']);
    $target_category = trim($_POST['target_category']);
    $desc = trim($_POST['description']);

    if(empty($title) || empty($url)) {
        header("Location: ../external_ads.php?error=Title and URL required");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO external_ads (title, ad_url, media_name, media_type, target_district, target_category, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $url, $media_name, $media_type, $target_district, $target_category, $desc]);
        header("Location: ../external_ads.php?msg=Promo Created");
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>