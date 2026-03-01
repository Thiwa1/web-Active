<?php

require_once __DIR__ . '/../classes/NotifySMS.php';

class MockPDOStmtForSMS {
    private $data;
    public function __construct($data) {
        $this->data = $data;
    }
    public function execute() {
        return true;
    }
    public function fetchAll($mode) {
        return $this->data;
    }
}

class MockPDOForSMS {
    private $data;
    public $exception = false;
    public function __construct($data = []) {
        $this->data = $data;
    }
    public function prepare($sql) {
        if ($this->exception) {
            throw new PDOException("Mock DB Error");
        }
        return new MockPDOStmtForSMS($this->data);
    }
}

class NotifySMSTest {
    private $passed = 0;
    private $failed = 0;

    public function run() {
        echo "Running NotifySMS Tests...\n\n";

        $this->testSettingsLoadedCorrectly();
        $this->testSettingsLoadErrorPdoException();
        $this->testFormatNumber();

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

    private function testSettingsLoadedCorrectly() {
        $pdo = new MockPDOForSMS([
            'sms_user_id' => 'user123',
            'sms_api_key' => 'key123',
            'sms_sender_id' => 'MySender'
        ]);

        $notifySms = new NotifySMS($pdo);
        $this->assertEqual(true, $notifySms->isConfigured(), "SettingsLoadedCorrectly - isConfigured");

        $reflector = new ReflectionClass(NotifySMS::class);
        $userIdProp = $reflector->getProperty('userId');
        $userIdProp->setAccessible(true);
        $this->assertEqual('user123', $userIdProp->getValue($notifySms), "SettingsLoadedCorrectly - userId is correct");

        $apiKeyProp = $reflector->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $this->assertEqual('key123', $apiKeyProp->getValue($notifySms), "SettingsLoadedCorrectly - apiKey is correct");

        $senderIdProp = $reflector->getProperty('senderId');
        $senderIdProp->setAccessible(true);
        $this->assertEqual('MySender', $senderIdProp->getValue($notifySms), "SettingsLoadedCorrectly - senderId is correct");
    }

    private function testSettingsLoadErrorPdoException() {
        $pdo = new MockPDOForSMS();
        $pdo->exception = true;

        // Output buffering to hide expected error_log "Settings Load Error: Mock DB Error"
        ob_start();
        $notifySms = new NotifySMS($pdo);
        // Ensure error_log doesn't mess up output
        $errorLogOutput = ob_get_clean();

        $this->assertEqual(false, $notifySms->isConfigured(), "SettingsLoadErrorPdoException - isConfigured handles Exception without crashing");

        // Verify internal state uses defaults or is null when an exception occurs
        $reflector = new ReflectionClass(NotifySMS::class);
        $userIdProp = $reflector->getProperty('userId');
        $userIdProp->setAccessible(true);
        $this->assertEqual(null, $userIdProp->getValue($notifySms), "SettingsLoadErrorPdoException - userId is null");

        $apiKeyProp = $reflector->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $this->assertEqual(null, $apiKeyProp->getValue($notifySms), "SettingsLoadErrorPdoException - apiKey is null");

        $senderIdProp = $reflector->getProperty('senderId');
        $senderIdProp->setAccessible(true);
        $this->assertEqual(null, $senderIdProp->getValue($notifySms), "SettingsLoadErrorPdoException - senderId is null");
    }

    private function testFormatNumber() {
        $pdo = new MockPDOForSMS([
            'sms_user_id' => 'user123',
            'sms_api_key' => 'key123',
            'sms_sender_id' => 'MySender'
        ]);
        $notifySms = new NotifySMS($pdo);

        // Since formatNumber is private, we can use reflection to test it.
        $reflector = new ReflectionClass(NotifySMS::class);
        $method = $reflector->getMethod('formatNumber');
        $method->setAccessible(true);

        $this->assertEqual('94771234567', $method->invoke($notifySms, '0771234567'), "FormatNumber - Starts with 0");
        $this->assertEqual('94771234567', $method->invoke($notifySms, '94771234567'), "FormatNumber - Starts with 94");
        $this->assertEqual('94771234567', $method->invoke($notifySms, '+94 77-123 4567'), "FormatNumber - With symbols");
        $this->assertEqual('94771234567', $method->invoke($notifySms, '771234567'), "FormatNumber - Starts with neither");
    }
}

$test = new NotifySMSTest();
$test->run();
