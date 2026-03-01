<?php

class MockPDOStatement {
    public $query;
    public function __construct($query) {
        $this->query = $query;
    }
    public function execute($params = null) {
        global $queryCount;
        $queryCount++;
        return true;
    }
    public function fetchColumn() {
        return false;
    }
    public function fetch() {
        return false;
    }
}

class MockPDO {
    public function prepare($query) {
        global $queryCount;
        $queryCount++;
        return new MockPDOStatement($query);
    }
    public function exec($query) {
        global $queryCount;
        $queryCount++;
        return true;
    }
    public function lastInsertId() {
        return 1;
    }
    public function query($query) {
        global $queryCount;
        $queryCount++;
        return new MockPDOStatement($query);
    }
}

$queryCount = 0;
$pdo = new MockPDO();

// Include the script content partially to count queries
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

    // Insert all newspapers using INSERT IGNORE
    $papers = array_keys($data);
    $placeholders = implode(',', array_fill(0, count($papers), '(?)'));
    $pdo->prepare("INSERT IGNORE INTO newspapers (name) VALUES $placeholders")->execute($papers);

    // Fetch their IDs
    $stmt = $pdo->query("SELECT id, name FROM newspapers");
    $paperIds = [];
    while ($row = $stmt->fetch()) {
        $paperIds[$row['name']] = $row['id'];
    }

    // Fetch existing rates to avoid inserting duplicates
    $existingRates = [];
    $stmt = $pdo->query("SELECT newspaper_id, description FROM newspaper_rates");
    while ($row = $stmt->fetch()) {
        $existingRates[$row['newspaper_id'] . '-' . $row['description']] = true;
    }

    // Build batch insert for rates
    $rateValues = [];
    $rateParams = [];
    foreach ($data as $paper => $rates) {
        if (!isset($paperIds[$paper])) continue;
        $id = $paperIds[$paper];
        foreach ($rates as $r) {
            if (!isset($existingRates[$id . '-' . $r[0]])) {
                $rateValues[] = '(?, ?, ?)';
                array_push($rateParams, $id, $r[0], $r[1]);
            }
        }
    }

    // Insert rates in batch
    if (!empty($rateValues)) {
        $ratePlaceholders = implode(', ', $rateValues);
        $pdo->prepare("INSERT INTO newspaper_rates (newspaper_id, description, rate) VALUES $ratePlaceholders")->execute($rateParams);
    }

echo "Total queries to setup tables + data: $queryCount\n";
