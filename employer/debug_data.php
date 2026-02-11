<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'employer') {
    die("Access Denied");
}

echo "<h2>Debug Data Inspector</h2>";

// 1. Check a sample Job Application
echo "<h3>1. Sample Job Application (ID 1)</h3>";
try {
    $stmt = $pdo->query("SELECT a.id, p.employee_full_name, p.cv_path, p.cl_path, p.employee_cv IS NOT NULL as has_blob_cv FROM job_applications a JOIN employee_profile_seeker p ON a.seeker_link = p.id LIMIT 1");
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($app, true) . "</pre>";

    if ($app) {
        $path = '../' . $app['cv_path'];
        echo "CV Path Check: " . (file_exists($path) ? "Exists" : "Not Found ($path)") . "<br>";
    }
} catch (Exception $e) { echo $e->getMessage(); }

// 2. Check Employee Documents
echo "<h3>2. Employee Documents</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM employee_document LIMIT 5");
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($docs, true) . "</pre>";
} catch (Exception $e) { echo $e->getMessage(); }

// 3. Check Talent Pool
echo "<h3>3. Talent Offers</h3>";
try {
    $stmt = $pdo->query("SELECT t.id, p.employee_full_name, p.cv_path FROM talent_offers t JOIN employee_profile_seeker p ON t.seeker_link = p.id LIMIT 5");
    $talents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($talents, true) . "</pre>";
} catch (Exception $e) { echo $e->getMessage(); }
