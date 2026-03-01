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

    $valid_cities = [];
    foreach ($cities as $city) {
        $city = trim($city);
        if (!empty($city)) {
            $valid_cities[] = $city;
        }
    }

    // Deduplicate array internally to avoid redundant inserts in same batch
    $valid_cities = array_unique($valid_cities);

    $added_count = 0;
    $skipped_count = 0;
    $total_attempted = count($valid_cities);

    if ($total_attempted > 0) {
        $chunks = array_chunk($valid_cities, 500);
        foreach ($chunks as $chunk) {
            $placeholders = [];
            $params = [];
            foreach ($chunk as $city) {
                $placeholders[] = '(?, ?)';
                $params[] = $city;
                $params[] = $district_id;
            }

            $sql = "INSERT IGNORE INTO city_table (City, City_link) VALUES " . implode(', ', $placeholders);
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                // rowCount() returns rows inserted. INSERT IGNORE returns 0 for duplicates.
                $added_count += $stmt->rowCount();
            } catch (PDOException $e) {
                error_log("Bulk City Add Error: " . $e->getMessage());
            }
        }
        $skipped_count = $total_attempted - $added_count;
    }

    $msg = "Added $added_count cities.";
    if ($skipped_count > 0) {
        $msg .= " ($skipped_count skipped as duplicates)";
    }

    header("Location: ../settings.php?status=success&msg=" . urlencode($msg) . "#pane-geo");
    exit();
}
?>