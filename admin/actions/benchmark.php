<?php
require_once __DIR__ . '/../../config/config.php';

// Prepare data
$items = [];
for ($i = 0; $i < 1000; $i++) {
    $items[] = "Category " . $i;
}

// Create a temp table to test on
$pdo->exec("CREATE TEMPORARY TABLE temp_job_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Description VARCHAR(255) UNIQUE
)");

echo "--- OLD METHOD ---\n";
$start = microtime(true);
$added = 0;
$skipped = 0;
foreach ($items as $item) {
    try {
        $stmt = $pdo->prepare("INSERT INTO temp_job_category (Description) VALUES (?)");
        $stmt->execute([$item]);
        $added++;
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $skipped++;
        }
    }
}
$end = microtime(true);
$oldTime = $end - $start;
echo "Added $added items, skipped $skipped.\n";
echo "Time: " . round($oldTime, 4) . " seconds.\n\n";

// Clear temp table
$pdo->exec("TRUNCATE TABLE temp_job_category");

echo "--- NEW METHOD (Transactions) ---\n";
$start = microtime(true);
$added = 0;
$skipped = 0;

$pdo->beginTransaction();
$stmt = $pdo->prepare("INSERT INTO temp_job_category (Description) VALUES (?)");
foreach ($items as $item) {
    try {
        $stmt->execute([$item]);
        $added++;
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $skipped++;
        }
    }
}
$pdo->commit();

$end = microtime(true);
$transactionTime = $end - $start;
echo "Added $added items, skipped $skipped.\n";
echo "Time: " . round($transactionTime, 4) . " seconds.\n\n";

// Clear temp table
$pdo->exec("TRUNCATE TABLE temp_job_category");

echo "--- NEW METHOD (Bulk INSERT IGNORE) ---\n";
$start = microtime(true);
$added = 0;
$skipped = 0;

// Deduplicate in PHP
$unique_items = array_unique($items);

$chunks = array_chunk($unique_items, 100);
foreach ($chunks as $chunk) {
    $placeholders = implode(',', array_fill(0, count($chunk), '(?)'));
    $stmt = $pdo->prepare("INSERT IGNORE INTO temp_job_category (Description) VALUES $placeholders");
    $stmt->execute($chunk);
    $added += $stmt->rowCount();
}
$skipped = count($items) - $added;

$end = microtime(true);
$bulkTime = $end - $start;
echo "Added $added items, skipped $skipped.\n";
echo "Time: " . round($bulkTime, 4) . " seconds.\n\n";

echo "--- IMPROVEMENT ---\n";
echo "Transaction Improvement: " . round((($oldTime - $transactionTime) / $oldTime) * 100, 2) . "%\n";
echo "Bulk Improvement: " . round((($oldTime - $bulkTime) / $oldTime) * 100, 2) . "%\n";

$pdo->exec("DROP TEMPORARY TABLE temp_job_category");
