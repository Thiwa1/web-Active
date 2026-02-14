<?php
require_once 'config/config.php';

try {
    // 1. Create Newspapers Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS newspapers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Table 'newspapers' created.\n";

    // 2. Create Newspaper Rates Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS newspaper_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        newspaper_id INT NOT NULL,
        description VARCHAR(255) NOT NULL,
        rate DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (newspaper_id) REFERENCES newspapers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Table 'newspaper_rates' created.\n";

    // 3. Update paper_ads table to store specific details
    // We add columns if they don't exist. Using simplified approach since 'ADD COLUMN IF NOT EXISTS' isn't supported in all MySQL versions directly in one go efficiently without procedure,
    // but try/catch block for each column or checking information_schema is safer.
    // For simplicity in this script, we'll try adding and suppress errors or check first.

    $cols = [
        "ADD COLUMN newspaper_rate_id INT DEFAULT NULL",
        "ADD COLUMN columns INT DEFAULT 1"
    ];

    foreach ($cols as $col) {
        try {
            $pdo->exec("ALTER TABLE paper_ads $col");
            echo "Updated paper_ads with column: $col\n";
        } catch (PDOException $e) {
            // Ignore duplicate column error
        }
    }

    // 4. Seed Data
    $data = [
        "Sunday Lankadeepa" => [
            ["Black & white US", 1120],
            ["Black & white EO", 1080],
            ["Black & One colour", 1180],
            ["Full colour", 1310]
        ],
        "Silumina" => [
            ["Black & white", 910],
            ["Black & one colour", 1065],
            ["Black & two colour", 1120],
            ["Full colour", 1195]
        ],
        "Sunday Observer" => [
            ["Black & white", 550],
            ["Black & one colour", 650],
            ["Black & two colour", 680],
            ["Full colour", 730]
        ],
        "The Sunday Times" => [
            ["Black & white EO", 640],
            ["Black & white US", 675],
            ["Black & One colour", 735],
            ["Full colour", 750]
        ],
        "Daily Lankadeepa" => [
            ["Black & white", 620],
            ["Black & one colour", 660],
            ["Black & two colour", 695],
            ["Full colour", 360]
        ],
        "Daily News" => [
            ["Black & white", 400],
            ["Black & one colour", 480],
            ["Black & two colour", 535],
            ["Full colour", 560]
        ],
        "D/Virakesari" => [
            ["Black & white", 400],
            ["Black & one colour", 530],
            ["Black & two colour", 0], // Assuming 0 implies N/A or free? Or user data entry. Keeping 0.
            ["Full colour", 600]
        ],
        "S/Virakesari" => [
            ["Black & white", 600],
            ["Black & one colour", 700],
            ["Black & two colour", 750],
            ["Full colour", 900]
        ],
        "D Mirror" => [
            ["Black & white", 465],
            ["Black & one colour", 490],
            ["Black & two colour", 525],
            ["Full colour", 550]
        ],
        "Dinamina" => [
            ["Black & white", 400],
            ["Black & W - Produ/Ed", 360],
            ["Black & one colour", 530],
            ["Full colour", 575]
        ],
        "Hit" => [
            ["Box amount", 13585]
        ]
    ];

    foreach ($data as $paper => $rates) {
        // Insert Newspaper
        $stmt = $pdo->prepare("SELECT id FROM newspapers WHERE name = ?");
        $stmt->execute([$paper]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            $pdo->prepare("INSERT INTO newspapers (name) VALUES (?)")->execute([$paper]);
            $id = $pdo->lastInsertId();
        }

        // Insert Rates
        $stmtRate = $pdo->prepare("INSERT INTO newspaper_rates (newspaper_id, description, rate) VALUES (?, ?, ?)");
        foreach ($rates as $r) {
            // Avoid duplicates
            $check = $pdo->prepare("SELECT id FROM newspaper_rates WHERE newspaper_id = ? AND description = ?");
            $check->execute([$id, $r[0]]);
            if (!$check->fetch()) {
                $stmtRate->execute([$id, $r[0], $r[1]]);
            }
        }
    }
    echo "Seed data inserted.\n";

    // 5. Settings
    $pdo->exec("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('paper_admin_edit_rights', '0')");
    // Ensure Paper VAT is set (if not already handled by logic, but good to have setting)
    $pdo->exec("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('paper_ad_vat_percent', '18')");
    echo "Settings updated.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>