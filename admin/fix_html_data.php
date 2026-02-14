<?php
session_start();
require_once '../config/config.php';

// Security: Admin Only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    die("Unauthorized Access");
}

echo "<html><body style='font-family: sans-serif; padding: 20px;'>";
echo "<h2>Database HTML Cleaner</h2>";
echo "<p>Removing Google Search artifacts from job descriptions...</p>";

try {
    // 1. Fetch all jobs with content
    $stmt = $pdo->query("SELECT id, job_description FROM advertising_table WHERE job_description LIKE '%class=\"otQkpb\"%' OR job_description LIKE '%data-processed=\"true\"%'");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($jobs)) {
        echo "<div style='color: green;'>No corrupted records found. Database is clean.</div>";
    } else {
        echo "<p>Found " . count($jobs) . " records to clean.</p>";

        $count = 0;
        foreach ($jobs as $job) {
            $original = $job['job_description'];
            $clean = $original;

            // REMOVE GARBAGE PATTERNS

            // 1. Remove Button Tags completely (View related links)
            $clean = preg_replace('/<button\b[^>]*>(.*?)<\/button>/is', "", $clean);

            // 2. Remove specific wrapper divs if they are just containers for headers (optional, or just clean attributes)
            // Strategy: Clean attributes from ALL tags

            // Remove 'class="..."'
            $clean = preg_replace('/ class="[^"]*"/', '', $clean);

            // Remove 'data-...' attributes
            $clean = preg_replace('/ data-[a-zA-Z0-9-]+="[^"]*"/', '', $clean);

            // Remove 'role="..."'
            $clean = preg_replace('/ role="[^"]*"/', '', $clean);

            // Remove 'aria-...'
            $clean = preg_replace('/ aria-[a-zA-Z0-9-]+="[^"]*"/', '', $clean);

            // Remove 'tabindex="..."'
            $clean = preg_replace('/ tabindex="[^"]*"/', '', $clean);

            // Remove empty spans that might be left over
            $clean = preg_replace('/<span>\s*<\/span>/', '', $clean);

            // 3. Optional: Convert specific div headers to standard h4/h5 if needed, but stripping attributes usually leaves <div>Header</div> which is fine.

            // 4. Decode entities just in case
            // $clean = html_entity_decode($clean); // Careful, might break HTML structure if stored encoded

            if ($original !== $clean) {
                $update = $pdo->prepare("UPDATE advertising_table SET job_description = ? WHERE id = ?");
                $update->execute([$clean, $job['id']]);
                $count++;
                echo "<div>Fixed Job ID: {$job['id']}</div>";
            }
        }
        echo "<hr><h3 style='color: green;'>Successfully cleaned $count records.</h3>";
    }

} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}

echo "<a href='dashboard.php'>Back to Dashboard</a>";
echo "</body></html>";
