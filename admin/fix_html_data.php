<?php
session_start();
require_once '../config/config.php';

// Security: Admin only (or temporary override if needed)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied. Please login as Admin.");
}

echo "Starting HTML Data Cleanup...<br>";

try {
    $stmt = $pdo->query("SELECT id, employer_about_company FROM employer_profile WHERE employer_about_company IS NOT NULL AND employer_about_company != ''");
    $count = 0;

    while ($row = $stmt->fetch()) {
        $original = $row['employer_about_company'];
        $clean = $original;

        // Decode repeatedly until stable (handles double/triple escaping)
        for ($i = 0; $i < 5; $i++) {
            $decoded = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $clean) break;
            $clean = $decoded;
        }

        // Update if changed
        if ($clean !== $original) {
            $upd = $pdo->prepare("UPDATE employer_profile SET employer_about_company = ? WHERE id = ?");
            $upd->execute([$clean, $row['id']]);
            $count++;
            echo "Fixed Record ID: " . $row['id'] . "<br>";
        }
    }

    echo "Cleanup Complete. Updated $count records.";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
