<?php
session_start();
require_once '../../config/config.php';

// Security Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $district_id = filter_input(INPUT_POST, 'district_id', FILTER_VALIDATE_INT);
    $raw_cities = $_POST['city_list'] ?? '';

    // Handle File Upload
    if (isset($_FILES['city_file']) && $_FILES['city_file']['error'] === UPLOAD_ERR_OK) {
        $fileContent = file_get_contents($_FILES['city_file']['tmp_name']);
        if ($fileContent) {
            $raw_cities .= "\n" . $fileContent;
        }
    }

    if (!$district_id || empty(trim($raw_cities))) {
        header("Location: ../settings.php?status=error&msg=Please provide a list or upload a file#pane-geo");
        exit();
    }

    // Parse Input: Split by newline, comma, or pipe
    $cities = preg_split('/[\r\n,]+/', $raw_cities);
    $added_count = 0;
    $skipped_count = 0;

    foreach ($cities as $city) {
        $city = trim($city);
        // Remove extra spaces inside name? Maybe not.
        if (empty($city)) continue;

        try {
            // Check existence first to avoid auto-increment gaps on failure (optional but cleaner)
            // Or just try insert. Using Try/Catch for duplicate key violation.
            $stmt = $pdo->prepare("INSERT INTO city_table (City, City_link) VALUES (?, ?)");
            $stmt->execute([$city, $district_id]);
            $added_count++;
        } catch (PDOException $e) {
            // Error 23000 is Integrity Constraint Violation (Duplicate Entry)
            if ($e->getCode() == '23000') {
                $skipped_count++;
            } else {
                // Log real errors?
                error_log("Bulk City Add Error: " . $e->getMessage());
            }
        }
    }

    $msg = "Added $added_count cities.";
    if ($skipped_count > 0) {
        $msg .= " ($skipped_count skipped as duplicates)";
    }

    header("Location: ../settings.php?status=success&msg=" . urlencode($msg) . "#pane-geo");
    exit();
}
?>