<?php
require_once 'config/config.php';

// First, drop the tables to start fresh
$pdo->exec("DROP TABLE IF EXISTS newspaper_rates;");
$pdo->exec("DROP TABLE IF EXISTS newspapers;");
$pdo->exec("DROP TABLE IF EXISTS paper_ads;");

$start = microtime(true);
require 'setup_newspaper_tables.php';
$end = microtime(true);

echo "First run (inserting): " . ($end - $start) * 1000 . " ms\n";

$start2 = microtime(true);
require 'setup_newspaper_tables.php';
$end2 = microtime(true);

echo "Second run (checking duplicates): " . ($end2 - $start2) * 1000 . " ms\n";
