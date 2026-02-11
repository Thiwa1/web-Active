<?php
class GoogleAuth {
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct($pdo) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'google_%'");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $this->clientId = $settings['google_client_id'] ?? '';
            $this->clientSecret = $settings['google_client_secret'] ?? '';
            $this->redirectUri = $settings['google_redirect_uri'] ?? '';
        } catch (Exception $e) {
            // Settings table might not have keys yet
        }
    }

    public function isConfigured() {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->redirectUri);
    }

    public function getAuthUrl($state = '') {
        if (!$this->isConfigured()) return '#';
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'email profile openid',
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];
        if ($state) $params['state'] = $state;
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function getToken($code) {
        $url = 'https://oauth2.googleapis.com/token';
        $params = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];

        return $this->makeRequest($url, $params, true);
    }

    public function getUserInfo($accessToken) {
        $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        return $this->makeRequest($url, [], false, $accessToken);
    }

    private function makeRequest($url, $params = [], $post = false, $token = null) {
        // Try CURL first
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For compatibility

            if ($post) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            }

            if ($token) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
            }

            $response = curl_exec($ch);
            curl_close($ch);
            return json_decode($response, true);
        }

        // Fallback to file_get_contents
        $opts = ['http' => [
            'method' => $post ? 'POST' : 'GET',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'ignore_errors' => true
        ]];

        if ($post) {
            $opts['http']['content'] = http_build_query($params);
        }

        if ($token) {
            $opts['http']['header'] .= "\r\nAuthorization: Bearer " . $token;
        }

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }
}
?>