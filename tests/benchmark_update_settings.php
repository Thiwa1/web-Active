<?php
// Use SQLite for benchmarking since we don't have MySQL
$pdo = new PDO('sqlite:test.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure we have some site_settings
$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT
)");

// Insert dummy settings
$pdo->exec("DELETE FROM site_settings");
$stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
for ($i = 1; $i <= 100; $i++) {
    $stmt->execute(["key_$i", "value_$i"]);
}

// Fetch the IDs to update
$stmt = $pdo->query("SELECT id FROM site_settings");
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Prepare the POST array
$_POST['settings'] = [];
foreach ($ids as $id) {
    $_POST['settings'][$id] = 'new_value_' . $id . '_' . rand(1, 1000);
}

// Benchmark the old way
$start_old = microtime(true);
if (isset($_POST['settings']) && is_array($_POST['settings'])) {
    $sql = "UPDATE site_settings SET setting_value = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    foreach ($_POST['settings'] as $id => $val) {
        $stmt->execute([$val, $id]);
    }
}
$end_old = microtime(true);
$time_old = ($end_old - $start_old) * 1000;

echo "Baseline (N+1 Update) Time: " . round($time_old, 2) . " ms\n";

// Fetch the IDs to update again and prep new random values
$stmt = $pdo->query("SELECT id FROM site_settings");
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($ids as $id) {
    $_POST['settings'][$id] = 'newer_value_' . $id . '_' . rand(1, 1000);
}

// Benchmark the new way
$start_new = microtime(true);
if (isset($_POST['settings']) && is_array($_POST['settings']) && count($_POST['settings']) > 0) {
    $ids_array = array_keys($_POST['settings']);
    $placeholders = implode(',', array_fill(0, count($ids_array), '?'));

    $sql = "UPDATE site_settings SET setting_value = CASE id ";
    $params = [];

    foreach ($_POST['settings'] as $id => $val) {
        $sql .= "WHEN ? THEN ? ";
        $params[] = $id;
        $params[] = $val;
    }

    $sql .= "END WHERE id IN ($placeholders)";

    foreach ($ids_array as $id) {
        $params[] = $id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}
$end_new = microtime(true);
$time_new = ($end_new - $start_new) * 1000;

echo "Optimized (CASE Update) Time: " . round($time_new, 2) . " ms\n";

if ($time_old > 0) {
    echo "Improvement: " . round((($time_old - $time_new) / $time_old) * 100, 2) . "%\n";
}
