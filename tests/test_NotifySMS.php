<?php

require_once __DIR__ . '/../classes/NotifySMS.php';

class MockPDOStmt {
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

class MockPDO {
    private $data;
    public $exception = false;
    public function __construct($data = []) {
        $this->data = $data;
    }
    public function prepare($sql) {
        if ($this->exception) {
            throw new PDOException("DB Error");
        }
        return new MockPDOStmt($this->data);
    }
}

class TestableNotifySMS extends NotifySMS {
    public $lastTo = null;
    public $lastMessage = null;
    public $curlResult = true;

    protected function executeCurl($to, $message) {
        $this->lastTo = $to;
        $this->lastMessage = $message;
        return $this->curlResult;
    }
}

class NotifySMSTest {
    private $passed = 0;
    private $failed = 0;

    public function run() {
        echo "Running NotifySMS Tests...\n\n";

        $this->testIsConfiguredTrue();
        $this->testIsConfiguredFalse();
        $this->testDbException();
        $this->testFormatNumber();
        $this->testSendSuccess();
        $this->testSendNotConfigured();
        $this->testSendOTP();
        $this->testSendJobAlert();

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
            'sms_user_id' => 'user_123',
            'sms_api_key' => 'key_123',
            'sms_sender_id' => 'Sender'
        ]);
        $sms = new TestableNotifySMS($pdo);
        $this->assertEqual(true, $sms->isConfigured(), "isConfigured - Fully Configured");
    }

    private function testIsConfiguredFalse() {
        $pdo = new MockPDO([
            'sms_user_id' => '',
            'sms_api_key' => 'key_123'
        ]);
        $sms = new TestableNotifySMS($pdo);
        $this->assertEqual(false, $sms->isConfigured(), "isConfigured - Missing User ID");

        $pdo2 = new MockPDO([
            'sms_user_id' => 'user_123',
            'sms_api_key' => ''
        ]);
        $sms2 = new TestableNotifySMS($pdo2);
        $this->assertEqual(false, $sms2->isConfigured(), "isConfigured - Missing API Key");

        $pdo3 = new MockPDO([]);
        $sms3 = new TestableNotifySMS($pdo3);
        $this->assertEqual(false, $sms3->isConfigured(), "isConfigured - Empty Settings");
    }

    private function testDbException() {
        $pdo = new MockPDO();
        $pdo->exception = true;
        $sms = new TestableNotifySMS($pdo);
        $this->assertEqual(false, $sms->isConfigured(), "isConfigured - DB Exception handles gracefully");
    }

    private function testFormatNumber() {
        $pdo = new MockPDO([]);
        $sms = new TestableNotifySMS($pdo);

        $reflector = new ReflectionClass(TestableNotifySMS::class);
        $method = $reflector->getMethod('formatNumber');
        $method->setAccessible(true);

        $this->assertEqual('94771234567', $method->invoke($sms, '0771234567'), "formatNumber - Leading 0");
        $this->assertEqual('94771234567', $method->invoke($sms, '94771234567'), "formatNumber - Already 94");
        $this->assertEqual('94771234567', $method->invoke($sms, '+94771234567'), "formatNumber - With +94");
        $this->assertEqual('94771234567', $method->invoke($sms, '771234567'), "formatNumber - Without 0 or 94");
        $this->assertEqual('94771234567', $method->invoke($sms, ' 077 123 4567 '), "formatNumber - With spaces");
        $this->assertEqual('94771234567', $method->invoke($sms, '077-123-4567'), "formatNumber - With dashes");
    }

    private function testSendSuccess() {
        $pdo = new MockPDO([
            'sms_user_id' => 'user_123',
            'sms_api_key' => 'key_123'
        ]);
        $sms = new TestableNotifySMS($pdo);

        $result = $sms->send('0771234567', 'Test Message');

        $this->assertEqual(true, $result, "send - Returns true on success");
        $this->assertEqual('94771234567', $sms->lastTo, "send - Formats number correctly");
        $this->assertEqual('Test Message', $sms->lastMessage, "send - Passes message correctly");
    }

    private function testSendNotConfigured() {
        $pdo = new MockPDO([]);
        $sms = new TestableNotifySMS($pdo);

        $result = $sms->send('0771234567', 'Test Message');

        $this->assertEqual(false, $result, "send - Returns false when not configured");
        $this->assertEqual(null, $sms->lastTo, "send - executeCurl not called");
    }

    private function testSendOTP() {
        $pdo = new MockPDO([
            'sms_user_id' => 'user_123',
            'sms_api_key' => 'key_123'
        ]);
        $sms = new TestableNotifySMS($pdo);

        $result = $sms->sendOTP('0771234567', '9999');

        $this->assertEqual(true, $result, "sendOTP - Returns true on success");
        $this->assertEqual('94771234567', $sms->lastTo, "sendOTP - Formats number correctly");
        $this->assertEqual('Your verification code is: 9999. Do not share this with anyone.', $sms->lastMessage, "sendOTP - Correct message format");
    }

    private function testSendJobAlert() {
        $pdo = new MockPDO([
            'sms_user_id' => 'user_123',
            'sms_api_key' => 'key_123'
        ]);
        $sms = new TestableNotifySMS($pdo);

        $result = $sms->sendJobAlert('0771234567', 'Software Engineer', 'Colombo');

        $this->assertEqual(true, $result, "sendJobAlert - Returns true on success");
        $this->assertEqual('94771234567', $sms->lastTo, "sendJobAlert - Formats number correctly");
        $this->assertEqual('New Job: Software Engineer in Colombo. Apply now on TipTop Vacancies!', $sms->lastMessage, "sendJobAlert - Correct message format");
    }
}

$test = new NotifySMSTest();
$test->run();
