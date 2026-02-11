<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $raw_list = $_POST['list_text'] ?? '';

    // Handle File
    if (isset($_FILES['list_file']) && $_FILES['list_file']['error'] === UPLOAD_ERR_OK) {
        $fileContent = file_get_contents($_FILES['list_file']['tmp_name']);
        if ($fileContent) {
            $raw_list .= "\n" . $fileContent;
        }
    }

    if (empty(trim($raw_list)) || !in_array($type, ['industry', 'category'])) {
        header("Location: ../settings.php?status=error&msg=Invalid Input#pane-taxonomy");
        exit();
    }

    // Determine Table
    $table = ($type === 'industry') ? 'Industry_Setting' : 'job_category_table';
    $col = ($type === 'industry') ? 'Industry_name' : 'Description';

    // Parse
    $items = preg_split('/[\r\n,]+/', $raw_list);
    $added = 0;
    $skipped = 0;

    foreach ($items as $item) {
        $item = trim($item);
        if (empty($item)) continue;

        try {
            $stmt = $pdo->prepare("INSERT INTO $table ($col) VALUES (?)");
            $stmt->execute([$item]);
            $added++;
        } catch (PDOException $e) {
            // Check for duplicate entry (23000)
            if ($e->getCode() == '23000') {
                $skipped++;
            }
        }
    }

    $msg = "Added $added items.";
    if ($skipped > 0) $msg .= " ($skipped duplicates skipped)";

    header("Location: ../settings.php?status=success&msg=" . urlencode($msg) . "#pane-taxonomy");
    exit();
}
?>