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

class MockGoogleAuth extends GoogleAuth {
    public $lastRequestUrl = null;
    public $lastRequestParams = null;
    public $lastRequestPost = null;
    public $lastRequestToken = null;
    public $mockResponse = null;

    protected function makeRequest($url, $params = [], $post = false, $token = null) {
        $this->lastRequestUrl = $url;
        $this->lastRequestParams = $params;
        $this->lastRequestPost = $post;
        $this->lastRequestToken = $token;
        return $this->mockResponse;
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
        $this->testIsConfiguredViaReflection();

        $this->testGetAuthUrlConfiguredWithState();
        $this->testGetAuthUrlConfiguredWithoutState();
        $this->testGetAuthUrlNotConfigured();

        $this->testGetTokenSuccess();
        $this->testGetTokenFailure();

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

    private function testIsConfiguredViaReflection() {
        $pdo = new MockPDO([]);
        $auth = new GoogleAuth($pdo);

        // Initial state should be unconfigured
        $this->assertEqual(false, $auth->isConfigured(), "isConfigured - Initial state unconfigured");

        // Set properties via reflection to test state directly
        $reflector = new ReflectionClass(GoogleAuth::class);

        $clientIdProp = $reflector->getProperty('clientId');
        $clientIdProp->setAccessible(true);
        $clientIdProp->setValue($auth, 'ref_client_id');

        $clientSecretProp = $reflector->getProperty('clientSecret');
        $clientSecretProp->setAccessible(true);
        $clientSecretProp->setValue($auth, 'ref_client_secret');

        $redirectUriProp = $reflector->getProperty('redirectUri');
        $redirectUriProp->setAccessible(true);
        $redirectUriProp->setValue($auth, 'http://localhost/ref');

        $this->assertEqual(true, $auth->isConfigured(), "isConfigured - Configured via Reflection");
    }

    private function testGetAuthUrlConfiguredWithState() {
        $pdo = new MockPDO([
            'google_client_id' => 'id_123',
            'google_client_secret' => 'secret_123',
            'google_redirect_uri' => 'http://localhost/callback'
        ]);
        $auth = new GoogleAuth($pdo);
        $url = $auth->getAuthUrl('state_456');

        $parsedUrl = parse_url($url);
        $this->assertEqual('https', $parsedUrl['scheme'] ?? '', "getAuthUrlWithState - Correct scheme");
        $this->assertEqual('accounts.google.com', $parsedUrl['host'] ?? '', "getAuthUrlWithState - Correct host");
        $this->assertEqual('/o/oauth2/v2/auth', $parsedUrl['path'] ?? '', "getAuthUrlWithState - Correct path");

        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $this->assertEqual('code', $queryParams['response_type'] ?? '', "getAuthUrlWithState - Correct response_type");
        $this->assertEqual('id_123', $queryParams['client_id'] ?? '', "getAuthUrlWithState - Correct client_id");
        $this->assertEqual('http://localhost/callback', $queryParams['redirect_uri'] ?? '', "getAuthUrlWithState - Correct redirect_uri");
        $this->assertEqual('email profile openid', $queryParams['scope'] ?? '', "getAuthUrlWithState - Correct scope");
        $this->assertEqual('online', $queryParams['access_type'] ?? '', "getAuthUrlWithState - Correct access_type");
        $this->assertEqual('select_account', $queryParams['prompt'] ?? '', "getAuthUrlWithState - Correct prompt");
        $this->assertEqual('state_456', $queryParams['state'] ?? '', "getAuthUrlWithState - Correct state");
    }

    private function testGetAuthUrlConfiguredWithoutState() {
        $pdo = new MockPDO([
            'google_client_id' => 'id_123',
            'google_client_secret' => 'secret_123',
            'google_redirect_uri' => 'http://localhost/callback'
        ]);
        $auth = new GoogleAuth($pdo);
        $url = $auth->getAuthUrl();

        $parsedUrl = parse_url($url);
        $this->assertEqual('https', $parsedUrl['scheme'] ?? '', "getAuthUrlWithoutState - Correct scheme");
        $this->assertEqual('accounts.google.com', $parsedUrl['host'] ?? '', "getAuthUrlWithoutState - Correct host");
        $this->assertEqual('/o/oauth2/v2/auth', $parsedUrl['path'] ?? '', "getAuthUrlWithoutState - Correct path");

        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $this->assertEqual('code', $queryParams['response_type'] ?? '', "getAuthUrlWithoutState - Correct response_type");
        $this->assertEqual('id_123', $queryParams['client_id'] ?? '', "getAuthUrlWithoutState - Correct client_id");
        $this->assertEqual('http://localhost/callback', $queryParams['redirect_uri'] ?? '', "getAuthUrlWithoutState - Correct redirect_uri");
        $this->assertEqual('email profile openid', $queryParams['scope'] ?? '', "getAuthUrlWithoutState - Correct scope");
        $this->assertEqual('online', $queryParams['access_type'] ?? '', "getAuthUrlWithoutState - Correct access_type");
        $this->assertEqual('select_account', $queryParams['prompt'] ?? '', "getAuthUrlWithoutState - Correct prompt");
        $this->assertEqual(false, isset($queryParams['state']), "getAuthUrlWithoutState - State should not be set");
    }

    private function testGetAuthUrlNotConfigured() {
        $pdo = new MockPDO([]);
        $auth = new GoogleAuth($pdo);
        $this->assertEqual('#', $auth->getAuthUrl(), "getAuthUrl - Returns # when not configured");
    }

    private function testGetTokenSuccess() {
        $pdo = new MockPDO([
            'google_client_id' => 'id_123',
            'google_client_secret' => 'secret_123',
            'google_redirect_uri' => 'http://localhost/callback'
        ]);

        $auth = new MockGoogleAuth($pdo);
        $auth->mockResponse = [
            'access_token' => 'mock_access_token',
            'expires_in' => 3599,
            'scope' => 'email profile openid',
            'token_type' => 'Bearer',
            'id_token' => 'mock_id_token'
        ];

        $code = 'mock_auth_code';
        $result = $auth->getToken($code);

        // Verify return value
        $this->assertEqual($auth->mockResponse, $result, "getToken - Returns expected response");

        // Verify makeRequest arguments
        $this->assertEqual('https://oauth2.googleapis.com/token', $auth->lastRequestUrl, "getToken - Requests correct URL");
        $this->assertEqual(true, $auth->lastRequestPost, "getToken - Uses POST request");
        $this->assertEqual(null, $auth->lastRequestToken, "getToken - Does not send Bearer token");

        $expectedParams = [
            'code' => 'mock_auth_code',
            'client_id' => 'id_123',
            'client_secret' => 'secret_123',
            'redirect_uri' => 'http://localhost/callback',
            'grant_type' => 'authorization_code'
        ];
        $this->assertEqual($expectedParams, $auth->lastRequestParams, "getToken - Sends correct parameters");
    }

    private function testGetTokenFailure() {
        $pdo = new MockPDO([
            'google_client_id' => 'id_123',
            'google_client_secret' => 'secret_123',
            'google_redirect_uri' => 'http://localhost/callback'
        ]);

        $auth = new MockGoogleAuth($pdo);
        $auth->mockResponse = [
            'error' => 'invalid_grant',
            'error_description' => 'Bad Request'
        ];

        $code = 'invalid_code';
        $result = $auth->getToken($code);

        // Verify return value
        $this->assertEqual($auth->mockResponse, $result, "getToken - Handles error response");

        $expectedParams = [
            'code' => 'invalid_code',
            'client_id' => 'id_123',
            'client_secret' => 'secret_123',
            'redirect_uri' => 'http://localhost/callback',
            'grant_type' => 'authorization_code'
        ];
        $this->assertEqual($expectedParams, $auth->lastRequestParams, "getToken - Sends correct parameters on failure");
    }
}

$test = new GoogleAuthTest();
$test->run();
