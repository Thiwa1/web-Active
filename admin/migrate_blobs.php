<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Access Denied");
}

require_once __DIR__ . '/../config/config.php';

/**
 * Migration Script: BLOB to File
 *
 * Iterates through key tables, takes BLOB data, saves it to disk,
 * updates the `_path` column, and NULLs the BLOB to save space.
 */

// Function to process a table
function migrateTable($pdo, $table, $idCol, $blobCol, $pathCol, $subFolder) {
    echo "Processing $table...\n";

    // Select records that have a BLOB but no Path (or path is empty)
    // Use unbuffered query or just standard query with fetch loop to save memory
    $stmt = $pdo->query("SELECT $idCol, $blobCol FROM $table WHERE $blobCol IS NOT NULL AND $blobCol != '' AND ($pathCol IS NULL OR $pathCol = '')");

    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $blobData = $row[$blobCol];
        $id = $row[$idCol];

        if (empty($blobData)) continue;

        // Determine extension (magic bytes or default)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($blobData);
        $ext = 'jpg'; // default
        if ($mime == 'image/png') $ext = 'png';
        if ($mime == 'image/jpeg') $ext = 'jpg';
        if ($mime == 'application/pdf') $ext = 'pdf';

        // Generate Filename and Paths
        $fileNameOnly = $subFolder . '_' . $id . '_' . time() . '.' . $ext;

        // Relative path to store in DB (e.g., uploads/docs/file.pdf)
        $dbPath = 'uploads/' . $subFolder . '/' . $fileNameOnly;

        // Absolute path to write the file
        $targetDir = __DIR__ . '/../uploads/' . $subFolder . '/';
        $fullSystemPath = $targetDir . $fileNameOnly;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Write file
        if (file_put_contents($fullSystemPath, $blobData)) {
            // Update DB with the RELATIVE PATH including 'uploads/...'
            $update = $pdo->prepare("UPDATE $table SET $pathCol = :path, $blobCol = NULL WHERE $idCol = :id");
            $update->execute([':path' => $dbPath, ':id' => $id]);
            $count++;
        }

        // Free memory for this iteration
        unset($blobData);
    }
    echo "Migrated $count records in $table.\n<br>";
}

// 1. Employer Profile (Logos)
migrateTable($pdo, 'employer_profile', 'id', 'employer_logo', 'logo_path', 'employers');

// 2. Employer Profile (BR)
migrateTable($pdo, 'employer_profile', 'id', 'employer_BR', 'br_path', 'docs');

// 3. Employee/Seeker (Profile Img)
migrateTable($pdo, 'employee_profile_seeker', 'id', 'employee_img', 'img_path', 'seekers');

// 4. Employee/Seeker (CV)
migrateTable($pdo, 'employee_profile_seeker', 'id', 'employee_cv', 'cv_path', 'docs');

// 5. Employee/Seeker (Cover Letter)
migrateTable($pdo, 'employee_profile_seeker', 'id', 'employee_cover_letter', 'cl_path', 'docs');

// 6. Job Ads (Images)
migrateTable($pdo, 'advertising_table', 'id', 'Img', 'img_path', 'jobs');

// 7. System/Company Details (Logo)
migrateTable($pdo, 'Compan_details', 'id', 'logo', 'logo_path', 'system');

// 8. Guest Applications (CV)
migrateTable($pdo, 'guest_job_applications', 'id', 'guest_cv', 'cv_path', 'guests');

echo "Migration Complete.";
?>