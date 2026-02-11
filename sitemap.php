<?php
require_once 'config/config.php';

header("Content-Type: application/xml; charset=utf-8");

$baseUrl = "https://tiptopvacancies.com";

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Static Pages
$pages = [
    'index.php' => 'daily',
    'login.php' => 'monthly',
    'register.php' => 'monthly',
    'contact.php' => 'yearly',
    'about.php' => 'yearly', // Assuming exists or will exist
    'policies.php' => 'yearly'
];

foreach ($pages as $page => $freq) {
    echo '<url>';
    echo '<loc>' . $baseUrl . '/' . $page . '</loc>';
    echo '<changefreq>' . $freq . '</changefreq>';
    echo '<priority>0.8</priority>';
    echo '</url>';
}

// Dynamic Job Pages
try {
    $stmt = $pdo->query("SELECT id, Opening_date FROM advertising_table WHERE Approved = 1 AND Closing_date >= CURDATE() ORDER BY id DESC");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($jobs as $job) {
        echo '<url>';
        echo '<loc>' . $baseUrl . '/job_details.php?id=' . $job['id'] . '</loc>';
        echo '<lastmod>' . $job['Opening_date'] . '</lastmod>';
        echo '<changefreq>weekly</changefreq>';
        echo '<priority>1.0</priority>';
        echo '</url>';
    }
} catch (Exception $e) {
    // Fail silently for sitemap
}

echo '</urlset>';
?>