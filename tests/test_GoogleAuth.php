<?php

require_once __DIR__ . '/../classes/GoogleAuth.php';

class MockPDOStmt {
    private $data;
    public function __construct($data) {
        $this->data = $data;
    }
    public function fetchAll($mode) {
        return $this->data;
    }
}

class MockPDO {
    private $data;
    public $exception = false;
    public function __construct($data = []) {
        $this->data = $data;
    }
    public function query($sql) {
        if ($this->exception) {
            throw new Exception("DB Error");
        }
        return new MockPDOStmt($this->data);
    }
}

class GoogleAuthTest {
    private $passed = 0;
    private $failed = 0;

    public function run() {
        echo "Running GoogleAuth Tests...\n\n";

        $this->testIsConfiguredTrue();
        $this->testIsConfiguredMissingClientId();
        $this->testIsConfiguredMissingClientSecret();
        $this->testIsConfiguredMissingRedirectUri();
        $this->testIsConfiguredEmptySettings();
        $this->testIsConfiguredDbException();

        $this->testGetAuthUrlConfigured();
        $this->testGetAuthUrlNotConfigured();

        echo "\nTest Summary:\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        if ($this->failed > 0) {
            echo "\nFAILURE: Some tests failed.\n";
            exit(1);
        } else {
            echo "\nSUCCESS: All tests passed.\n";
            exit(0);
        }
    }

    private function assertEqual($expected, $actual, $testName) {
        if ($expected === $actual) {
            echo "✅ PASS: $testName\n";
            $this->passed++;
        } else {
            echo "❌ FAIL: $testName\n";
            echo "   Expected: " . var_export($expected, true) . "\n";
            echo "   Actual:   " . var_export($actual, true) . "\n";
            $this->failed++;
        }
    }

    private function testIsConfiguredTrue() {
        $pdo = new MockPDO([
            'google_client_id' => 'id_123',
            'google_client_secret' => 'secret_123',
            'google_redirect_uri' => 'http://localhost/callback'
        ]);
        $auth = new GoogleAuth($pdo);
        $this->assertEqual(true, $auth->isConfigured(), "isConfigured - Fully Configured");
    }

    private function testIsConfiguredMissingClientId() {
        $pdo = new MockPDO([
            'google_client_id' => '',
            'google_client_secret' => 'secret_123',
            'google_redirect_uri' => 'http://localhost/callback'
        ]);
        $auth = new GoogleAuth($pdo);
        $this->assertEqual(false, $auth->isConfigured(), "isConfigured - Missing Client ID");
    }

    private function testIsConfiguredMissingClientSecret() {
        $pdo = new MockPDO([
            'google_client_id' => 'id_123',
            'google_client_secret' => '',
            'google_redirect_uri' => 'http://localhost/callback'
        ]);
        $auth = new GoogleAuth($pdo);
        $this->assertEqual(false, $auth->isConfigured(), "isConfigured - Missing Client Secret");
    }

    private function testIsConfiguredMissingRedirectUri() {
        $pdo = new MockPDO([
            'google_client_id' => 'id_123',
            'google_client_secret' => 'secret_123',
            'google_redirect_uri' => ''
        ]);
        $auth = new GoogleAuth($pdo);
        $this->assertEqual(false, $auth->isConfigured(), "isConfigured - Missing Redirect URI");
    }

    private function testIsConfiguredEmptySettings() {
        $pdo = new MockPDO([]);
        $auth = new GoogleAuth($pdo);
        $this->assertEqual(false, $auth->isConfigured(), "isConfigured - Empty Settings");
    }

    private function testIsConfiguredDbException() {
        $pdo = new MockPDO();
        $pdo->exception = true;
        $auth = new GoogleAuth($pdo);
        $this->assertEqual(false, $auth->isConfigured(), "isConfigured - DB Exception");
    }

    private function testGetAuthUrlConfigured() {
        $pdo = new MockPDO([
            'google_client_id' => 'id_123',
            'google_client_secret' => 'secret_123',
            'google_redirect_uri' => 'http://localhost/callback'
        ]);
        $auth = new GoogleAuth($pdo);
        $url = $auth->getAuthUrl('state_456');

        $this->assertEqual(true, strpos($url, 'https://accounts.google.com/o/oauth2/v2/auth?') === 0, "getAuthUrl - Base URL correct");
        $this->assertEqual(true, strpos($url, 'client_id=id_123') !== false, "getAuthUrl - Contains client_id");
        $this->assertEqual(true, strpos($url, 'redirect_uri=http%3A%2F%2Flocalhost%2Fcallback') !== false, "getAuthUrl - Contains redirect_uri");
        $this->assertEqual(true, strpos($url, 'state=state_456') !== false, "getAuthUrl - Contains state");
    }

    private function testGetAuthUrlNotConfigured() {
        $pdo = new MockPDO([]);
        $auth = new GoogleAuth($pdo);
        $this->assertEqual('#', $auth->getAuthUrl(), "getAuthUrl - Returns # when not configured");
    }
}

$test = new GoogleAuthTest();
$test->run();
