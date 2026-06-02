<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'admin') {
    header("Location: ../../login.php?error=" . urlencode("Access Denied")); exit();
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

    // Clean and filter items
    $cleaned_items = [];
    foreach ($items as $item) {
        $item = trim($item);
        if ($item !== '') {
            $cleaned_items[] = $item;
        }
    }

    $total_cleaned = count($cleaned_items);
    $unique_items = array_unique($cleaned_items);
    $skipped += ($total_cleaned - count($unique_items)); // Skip duplicates in input

    if (!empty($unique_items)) {
        // Chunk items to avoid exceeding MySQL placeholder limits
        $chunks = array_chunk($unique_items, 500);

        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '(?)'));
            $sql = "INSERT IGNORE INTO $table ($col) VALUES $placeholders";

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($chunk);
                $inserted = $stmt->rowCount();
                $added += $inserted;
                $skipped += (count($chunk) - $inserted);
            } catch (PDOException $e) {
                // If the entire chunk fails for some other reason, mark them all as skipped
                // to maintain the original contract of not crashing on inserts.
                $skipped += count($chunk);
            }
        }
    }

    $msg = "Added $added items.";
    if ($skipped > 0) $msg .= " ($skipped duplicates skipped)";

    header("Location: ../settings.php?status=success&msg=" . urlencode($msg) . "#pane-taxonomy");
    exit();
}
?>