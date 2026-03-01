<?php
// Script to test CSRF validation in admin/actions/process_payment.php
session_start();

$_SESSION['user_type'] = 'Admin';
$valid_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $valid_token;

echo "Testing Process Payment CSRF Validation\n";
echo "========================================\n\n";

$target_dir = realpath(__DIR__ . '/../admin/actions/');

// Helper function to simulate a request
function simulate_request($post_data) {
    global $target_dir;

    // Create a temporary db config to prevent PDO fail
    $mock_config = '<?php
    // Mock config to skip real DB connection
    $pdo = new class {
        public function prepare() { return new class { public function execute() { return true; } public function fetchAll() { return []; } }; }
        public function beginTransaction() {}
        public function commit() {}
        public function rollBack() {}
        public function inTransaction() { return false; }
    };
    ?>';
    $mock_config_path = sys_get_temp_dir() . '/mock_config.php';
    file_put_contents($mock_config_path, $mock_config);

    // Create a dummy NotifySMS class file to prevent missing class errors
    $mock_sms = '<?php
    class NotifySMS {
        public function __construct($pdo) {}
        public function sendJobAlert() { return true; }
    }
    ?>';
    $mock_sms_path = sys_get_temp_dir() . '/NotifySMS.php';
    file_put_contents($mock_sms_path, $mock_sms);

    $code = '<?php
        session_start();
        $_SESSION["user_type"] = "Admin";
        $_SESSION["csrf_token"] = "' . $_SESSION['csrf_token'] . '";
        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST = ' . var_export($post_data, true) . ';

        // intercept config requires by prepending an autoloader or similar...
        // Actually, we can just read the target script and replace requires
        $target_content = file_get_contents("' . $target_dir . '/process_payment.php");

        // Remove requires
        $target_content = preg_replace("/require_once.*?;/s", "", $target_content);

        // Remove session_start since we already did it
        $target_content = str_replace("session_start();", "", $target_content);

        require_once "' . $mock_config_path . '";
        require_once "' . $mock_sms_path . '";

        // Evaluate the modified target content
        eval("?>" . $target_content);
    ?>';

    $temp_file = tempnam(sys_get_temp_dir(), 'test_csrf_');
    file_put_contents($temp_file, $code);

    $output = shell_exec('php ' . escapeshellarg($temp_file) . ' 2>&1');

    unlink($temp_file);
    unlink($mock_config_path);
    unlink($mock_sms_path);
    return $output;
}

// Test 1: No CSRF token
echo "Test 1: Missing CSRF token... ";
$out1 = simulate_request([
    'payment_id' => 1,
    'action' => 'approve'
]);

if (strpos($out1, "CSRF Token Validation Failed") !== false) {
    echo "PASSED (Validation failed as expected)\n";
} else {
    echo "FAILED\n  Output was: " . trim($out1) . "\n";
}

// Test 2: Invalid CSRF token
echo "Test 2: Invalid CSRF token... ";
$out2 = simulate_request([
    'payment_id' => 1,
    'action' => 'approve',
    'csrf_token' => 'invalid_token_123'
]);

if (strpos($out2, "CSRF Token Validation Failed") !== false) {
    echo "PASSED (Validation failed as expected)\n";
} else {
    echo "FAILED\n  Output was: " . trim($out2) . "\n";
}

// Test 3: Valid CSRF token
echo "Test 3: Valid CSRF token... ";
$out3 = simulate_request([
    'payment_id' => 1,
    'action' => 'approve',
    'csrf_token' => $valid_token
]);

if (strpos($out3, "CSRF Token Validation Failed") === false) {
    echo "PASSED (CSRF check passed)\n";
} else {
    echo "FAILED (Unexpectedly failed CSRF check)\n  Output was: " . trim($out3) . "\n";
}

echo "\nTests completed.\n";
