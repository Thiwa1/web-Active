<?php
require_once 'classes/ReCaptcha.php';

// Set mock environment variables
putenv("RECAPTCHA_SITE_KEY=mock_site_key_123");
putenv("RECAPTCHA_SECRET_KEY=mock_secret_key_456");

$siteKey = ReCaptcha::getSiteKey();
echo "Site Key: " . $siteKey . "\n";
if ($siteKey === "mock_site_key_123") {
    echo "Site Key Test: PASS\n";
} else {
    echo "Site Key Test: FAIL\n";
}

// Use reflection to test private getSecretKey
$reflection = new ReflectionClass('ReCaptcha');
$method = $reflection->getMethod('getSecretKey');
$method->setAccessible(true);
$secretKey = $method->invoke(null);

echo "Secret Key: " . $secretKey . "\n";
if ($secretKey === "mock_secret_key_456") {
    echo "Secret Key Test: PASS\n";
} else {
    echo "Secret Key Test: FAIL\n";
}

?>