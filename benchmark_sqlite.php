<?php
// Mocking PDO for SQLite to benchmark
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Create Newspapers Table
$pdo->exec("CREATE TABLE IF NOT EXISTS newspapers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE
);");

// 2. Create Newspaper Rates Table
$pdo->exec("CREATE TABLE IF NOT EXISTS newspaper_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    newspaper_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    rate DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (newspaper_id) REFERENCES newspapers(id) ON DELETE CASCADE
);");

// We need to use ON DUPLICATE KEY UPDATE? SQLite uses ON CONFLICT DO UPDATE.
// Wait, the prompt implies MySQL. I will use SQLite just to show the baseline of queries executed.
// Actually, let's track the number of queries!

class QueryCounterPDO extends PDO {
    public $queryCount = 0;
    public function prepare($query, $options = []) {
        $this->queryCount++;
        return parent::prepare($query, $options);
    }
    public function exec($statement) {
        $this->queryCount++;
        return parent::exec($statement);
    }
}

$pdo = new QueryCounterPDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Schema
$pdo->exec("CREATE TABLE IF NOT EXISTS newspapers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS newspaper_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    newspaper_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    rate DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (newspaper_id) REFERENCES newspapers(id) ON DELETE CASCADE
);");

// To support MySQL's ON DUPLICATE KEY UPDATE in SQLite, wait, we can't easily mock that syntax if we want the actual script to run.
// Let's just create a mock that counts execute() calls.
