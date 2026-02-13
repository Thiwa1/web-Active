<?php
session_start();
require_once '../config/config.php';
require_once '../config/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Validate Login
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php?msg=login_required");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // 2. Validate Inputs
    $width_cm = (float)$_POST['width_cm'];
    $height_cm = (float)$_POST['height_cm'];
    $ad_content = htmlspecialchars($_POST['ad_content'] ?? '');
    $contact_mobile = htmlspecialchars($_POST['contact_mobile'] ?? '');
    $contact_whatsapp = htmlspecialchars($_POST['contact_whatsapp'] ?? '');

    if ($width_cm <= 0 || $height_cm <= 0 || empty($ad_content) || empty($contact_mobile)) {
        header("Location: ../paper_ads.php?error=Invalid inputs");
        exit();
    }

    // 3. Calculate Price
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'paper_ad_rate_per_sq_cm'");
    $stmt->execute();
    $rate = (float)($stmt->fetchColumn() ?: 50);
    $price = $width_cm * $height_cm * $rate;

    // 4. Handle File Uploads
    $ad_image_path = null;
    $payment_slip_path = null;

    try {
        if (!empty($_FILES['ad_image']['tmp_name'])) {
            $raw_path = uploadImage($_FILES['ad_image'], '../uploads/paper_ads/', ['jpg','jpeg','png','pdf']);
            $ad_image_path = str_replace(['../', './'], '', $raw_path);
        }

        if (!empty($_FILES['payment_slip']['tmp_name'])) {
            $raw_path = uploadImage($_FILES['payment_slip'], '../uploads/docs/', ['jpg','jpeg','png','pdf']);
            $payment_slip_path = str_replace(['../', './'], '', $raw_path);
        } else {
            throw new Exception("Payment slip is required.");
        }

        // 5. Calculate Target Date (Next Friday)
        $target_date = date('Y-m-d', strtotime('next Friday'));

        // 6. Insert Record
        $sql = "INSERT INTO paper_ads (
                    user_id, width_cm, height_cm, price, ad_content, image_path,
                    contact_mobile, contact_whatsapp, payment_slip_path, status, closing_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id, $width_cm, $height_cm, $price, $ad_content, $ad_image_path,
            $contact_mobile, $contact_whatsapp, $payment_slip_path, $target_date
        ]);

        header("Location: ../paper_ads.php?success=1");
        exit();

    } catch (Exception $e) {
        header("Location: ../paper_ads.php?error=" . urlencode($e->getMessage()));
        exit();
    }

} else {
    header("Location: ../index.php");
    exit();
}
