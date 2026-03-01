<?php

require_once __DIR__ . '/../classes/ReCaptcha.php';

/**
 * Mock class to simulate the ReCaptcha API without making actual HTTP requests.
 */
class MockReCaptcha extends ReCaptcha {
    public static $mockResponse = '';
    public static $mockError = null;
    public static $lastPostData = null;

    protected static function sendRequest($postData, &$error) {
        self::$lastPostData = $postData;
        $error = self::$mockError;
        return self::$mockResponse;
    }
}

class ReCaptchaTest {
    private $passed = 0;
    private $failed = 0;

    public function run() {
        echo "Running ReCaptcha Tests...\n\n";

        $this->testEmptyToken();
        $this->testCurlError();
        $this->testInvalidJson();
        $this->testSuccessScoreHighActionMatch();
        $this->testSuccessScoreHighActionMismatch();
        $this->testSuccessScoreLow();
        $this->testFailureFromApi();
        $this->testGetSiteKey();

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

    private function testEmptyToken() {
        $result = MockReCaptcha::verify('');
        $this->assertEqual(false, $result, "verify - Empty Token");
    }

    private function testCurlError() {
        MockReCaptcha::$mockError = "Connection timeout";
        $result = MockReCaptcha::verify('test_token');
        $this->assertEqual(false, $result, "verify - Curl Error");
    }

    private function testInvalidJson() {
        MockReCaptcha::$mockError = null;
        MockReCaptcha::$mockResponse = "invalid json";
        $result = MockReCaptcha::verify('test_token');
        $this->assertEqual(false, $result, "verify - Invalid JSON response");
    }

    private function testSuccessScoreHighActionMatch() {
        MockReCaptcha::$mockError = null;
        MockReCaptcha::$mockResponse = json_encode([
            'success' => true,
            'score' => 0.9,
            'action' => 'login'
        ]);
        $result = MockReCaptcha::verify('test_token', 'login', 0.5);
        $this->assertEqual(true, $result, "verify - Success (High score, Action match)");
    }

    private function testSuccessScoreHighActionMismatch() {
        MockReCaptcha::$mockError = null;
        MockReCaptcha::$mockResponse = json_encode([
            'success' => true,
            'score' => 0.9,
            'action' => 'register'
        ]);
        $result = MockReCaptcha::verify('test_token', 'login', 0.5);
        $this->assertEqual(false, $result, "verify - Failure (Action mismatch)");
    }

    private function testSuccessScoreLow() {
        MockReCaptcha::$mockError = null;
        MockReCaptcha::$mockResponse = json_encode([
            'success' => true,
            'score' => 0.3,
            'action' => 'login'
        ]);
        $result = MockReCaptcha::verify('test_token', 'login', 0.5);
        $this->assertEqual(false, $result, "verify - Failure (Low score)");
    }

    private function testFailureFromApi() {
        MockReCaptcha::$mockError = null;
        MockReCaptcha::$mockResponse = json_encode([
            'success' => false,
            'error-codes' => ['invalid-input-response']
        ]);
        $result = MockReCaptcha::verify('test_token');
        $this->assertEqual(false, $result, "verify - Failure from API");
    }

    private function testGetSiteKey() {
        $this->assertEqual('6Le5oFQsAAAAAHU-Fy3CB9jGJqJq6j51omSnCh0_', MockReCaptcha::getSiteKey(), "getSiteKey - Returns correct key");
    }
}

$test = new ReCaptchaTest();
$test->run();
