<?php
// Determine base path for links
if (!isset($basePath)) {
    $basePath = './';
    $scriptDir = dirname($_SERVER['PHP_SELF']);
    $dirName = basename($scriptDir);
    if ($dirName == 'admin' || $dirName == 'employer' || $dirName == 'employee' || $dirName == 'layout') {
        $basePath = '../';
    }
}
?>
<!-- FOOTER - Minimal Version -->
<footer class="main-footer">
    <div class="container px-lg-5 d-flex flex-column flex-md-row justify-content-between align-items-center">
        <div class="mb-3 mb-md-0">
            <span class="fw-bold text-dark"><?= htmlspecialchars($siteName ?? 'JobPortal') ?></span>
            <span class="text-muted ms-2">&copy; <?= date('Y') ?></span>
        </div>

        <div class="footer-links d-flex">
            <a href="<?= $basePath ?>index.php">Browse Jobs</a>
            <a href="<?= $basePath ?>register.php">Post a Job</a>
            <a href="<?= $basePath ?>policies.php#v-pills-privacy">Privacy</a>
            <a href="<?= $basePath ?>contact.php">Contact</a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
if (file_exists(__DIR__ . '/ui_helpers.php')) {
    include_once __DIR__ . '/ui_helpers.php';
}
?>
</body>
</html>
