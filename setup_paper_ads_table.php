<?php
require_once 'config/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS paper_ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        width_cm DECIMAL(5, 2) NOT NULL,
        height_cm DECIMAL(5, 2) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        ad_content TEXT NOT NULL,
        image_path VARCHAR(255) DEFAULT NULL,
        contact_mobile VARCHAR(20) NOT NULL,
        contact_whatsapp VARCHAR(20) DEFAULT NULL,
        payment_slip_path VARCHAR(255) DEFAULT NULL,
        status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        closing_date DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table paper_ads created successfully.\n";

    // Add a price setting for paper ads if it doesn't exist
    $sqlPrice = "INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('paper_ad_rate_per_sq_cm', '50.00')";
    $pdo->exec($sqlPrice);
    echo "Default price rate setting added.\n";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>