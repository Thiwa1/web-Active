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
    $newspaper_id = filter_var($_POST['newspaper_id'], FILTER_VALIDATE_INT);
    $rate_id = filter_var($_POST['rate_id'], FILTER_VALIDATE_INT);
    $height_cm = (float)$_POST['height_cm'];
    $columns = (int)$_POST['columns'];

    $ad_content = htmlspecialchars($_POST['ad_content'] ?? '');
    $contact_mobile = htmlspecialchars($_POST['contact_mobile'] ?? '');
    $contact_whatsapp = htmlspecialchars($_POST['contact_whatsapp'] ?? '');

    if (!$newspaper_id || !$rate_id || $height_cm <= 0 || $columns <= 0 || empty($ad_content) || empty($contact_mobile)) {
        header("Location: ../paper_ads.php?error=Invalid inputs. Please check all fields.");
        exit();
    }

    // 3. Calculate Price Server-Side
    try {
        // Fetch Rate
        $stmt = $pdo->prepare("SELECT rate FROM newspaper_rates WHERE id = ? AND newspaper_id = ?");
        $stmt->execute([$rate_id, $newspaper_id]);
        $rateVal = $stmt->fetchColumn();

        if (!$rateVal) {
            throw new Exception("Invalid rate selected.");
        }

        // Fetch VAT
        $stmtVat = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'paper_ad_vat_percent'");
        $stmtVat->execute();
        $vatPercent = (float)($stmtVat->fetchColumn() ?: 18);

        // Calculation: (Rate * Height * Columns) * (1 + VAT%)
        $basePrice = $rateVal * $height_cm * $columns;
        $vatAmount = $basePrice * ($vatPercent / 100);
        $finalPrice = $basePrice + $vatAmount;

        // 4. Handle File Uploads
        $ad_image_path = null;
        $payment_slip_path = null;

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
        // Note: Using 'width_cm' column to store 'columns' count if we don't want to alter table again,
        // OR better: use the new 'columns' column I added in setup script.
        // I added 'columns' and 'newspaper_rate_id'.
        // I will map 'width_cm' to 0 or null, or just store for legacy/compatibility if needed.
        // Actually, let's just use the correct columns.

        $sql = "INSERT INTO paper_ads (
                    user_id, newspaper_rate_id, height_cm, columns, price, ad_content, image_path,
                    contact_mobile, contact_whatsapp, payment_slip_path, status, closing_date, width_cm
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?)";

        $stmt = $pdo->prepare($sql);
        // width_cm is technically not used now, passing 0
        $stmt->execute([
            $user_id, $rate_id, $height_cm, $columns, $finalPrice, $ad_content, $ad_image_path,
            $contact_mobile, $contact_whatsapp, $payment_slip_path, $target_date, 0
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
