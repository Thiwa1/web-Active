<?php
class NotifySMS {
    private $db;
    private $userId;
    private $apiKey;
    private $senderId;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        $this->loadSettings();
    }

    private function loadSettings() {
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('sms_user_id', 'sms_api_key', 'sms_sender_id')");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $this->userId = $settings['sms_user_id'] ?? '';
            $this->apiKey = $settings['sms_api_key'] ?? '';
            $this->senderId = $settings['sms_sender_id'] ?? 'NotifyDEMO';
        } catch (PDOException $e) {
            error_log("Settings Load Error: " . $e->getMessage());
        }
    }

    private function formatNumber($phone) {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '+'], '', $phone);
        if (substr($phone, 0, 1) == '0') { return '94' . substr($phone, 1); }
        if (substr($phone, 0, 2) != '94') { return '94' . $phone; }
        return $phone;
    }

    public function send($to, $message) {
        if (!$this->isConfigured()) return false;
        $to = $this->formatNumber($to);
        return $this->executeCurl($to, $message);
    }

    public function sendOTP($phone, $otpCode) {
        $message = "Your verification code is: $otpCode. Do not share this with anyone.";
        return $this->send($phone, $message);
    }

    public function sendJobAlert($phone, $jobRole, $city) {
        $message = "New Job: {$jobRole} in {$city}. Apply now on TipTop Vacancies!";
        return $this->send($phone, $message);
    }

    private function executeCurl($to, $message) {
        $url = "https://app.notify.lk/api/v1/send";
        $data = [
            "user_id" => $this->userId,
            "api_key" => $this->apiKey,
            "sender_id" => $this->senderId,
            "to" => $to,
            "message" => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log("NotifySMS Curl Error: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        
        // Notify.lk returns simple "Sent" or JSON {"status":"success",...} depending on version?
        // User doc says JSON: { "status": "success", "data": "Sent" }
        $result = json_decode($response, true);

        if (isset($result['status']) && $result['status'] == 'success') {
            return true;
        }

        error_log("NotifySMS API Error: " . $response);
        return false;
    }

    public function isConfigured() {
        return !empty($this->apiKey) && !empty($this->userId);
    }
}
