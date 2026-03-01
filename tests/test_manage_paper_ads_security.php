<?php
// Static analysis test for admin/manage_paper_ads.php security validation

$file_path = __DIR__ . '/../admin/manage_paper_ads.php';
$content = file_get_contents($file_path);

$tests = [
    'Contains strictly typed allowlist check' => 'in_array($statusFilter, $allowedStatuses, true)',
    'Contains string validation' => 'is_string($statusFilter)',
    'Contains parameterised query for status' => 'WHERE p.status = ?',
    'Binds statusFilter to parameters' => '$params[] = $statusFilter',
];

$all_passed = true;

echo "Running Security Tests for manage_paper_ads.php...\n\n";

foreach ($tests as $description => $expected_code) {
    if (strpos($content, $expected_code) !== false) {
        echo "✅ PASS: $description\n";
    } else {
        echo "❌ FAIL: $description (Expected code snippet missing)\n";
        $all_passed = false;
    }
}

if (!$all_passed) {
    exit(1);
}

echo "\nAll security validation tests passed successfully.\n";
