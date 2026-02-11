<?php
session_start();
require_once '../config/config.php';

// Only Admin should run this
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Unauthorized. Admin access required.");
}

echo "<h2>Starting BLOB Cleanup (Freeing DB Space)...</h2>";
echo "<p><strong>Warning:</strong> This will delete binary image data from the database where a file path exists. Ensure you have run 'migrate_blobs.php' first!</p>";

try {
    $pdo->beginTransaction();

    // 1. Employer Logos
    $stmt = $pdo->exec("UPDATE employer_profile SET employer_logo = NULL WHERE logo_path IS NOT NULL AND logo_path != ''");
    echo "Cleared Employer Logos: $stmt rows.<br>";

    // 2. Employer BR
    $stmt = $pdo->exec("UPDATE employer_profile SET employer_BR = NULL WHERE br_path IS NOT NULL AND br_path != ''");
    echo "Cleared Employer BRs: $stmt rows.<br>";

    // 3. Job Banners
    $stmt = $pdo->exec("UPDATE advertising_table SET Img = NULL WHERE img_path IS NOT NULL AND img_path != ''");
    echo "Cleared Job Banners: $stmt rows.<br>";

    // 4. Seeker Images
    $stmt = $pdo->exec("UPDATE employee_profile_seeker SET employee_img = NULL WHERE img_path IS NOT NULL AND img_path != ''");
    echo "Cleared Seeker Images: $stmt rows.<br>";

    // 5. Seeker CVs
    $stmt = $pdo->exec("UPDATE employee_profile_seeker SET employee_cv = NULL WHERE cv_path IS NOT NULL AND cv_path != ''");
    echo "Cleared Seeker CVs: $stmt rows.<br>";

    // 6. Admin/System Logo
    $stmt = $pdo->exec("UPDATE Compan_details SET logo = NULL WHERE logo_path IS NOT NULL AND logo_path != ''");
    echo "Cleared System Logos: $stmt rows.<br>";

    // 7. Payment Slips
    $stmt = $pdo->exec("UPDATE payment_table SET Payment_slip = NULL WHERE slip_path IS NOT NULL AND slip_path != ''");
    echo "Cleared Payment Slips: $stmt rows.<br>";

    // 8. Guest Documents
    $stmt = $pdo->exec("UPDATE guest_job_applications SET guest_cv = '' WHERE cv_path IS NOT NULL AND cv_path != ''");
    echo "Cleared Guest CVs: $stmt rows.<br>";

    $stmt = $pdo->exec("UPDATE guest_job_applications SET guest_cover_letter = NULL WHERE cl_path IS NOT NULL AND cl_path != ''");
    echo "Cleared Guest Cover Letters: $stmt rows.<br>";

    $pdo->commit();
    echo "<h3>Cleanup Complete. Database size should be significantly reduced.</h3>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
