<?php
/**
 * Database Configuration
 * Setting up a secure PDO connection for the Vacancies Portal
 */

// 1. Database Credentials
$host    = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'tiptromr_vacancies';
$db_user = getenv('DB_USER') ?: 'root';      // Default to root for local dev
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''; // Support empty password
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

// SMTP Configuration
if (!defined('SMTP_HOST')) define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
if (!defined('SMTP_PORT')) define('SMTP_PORT', getenv('SMTP_PORT') ?: 465);
if (!defined('SMTP_USER')) define('SMTP_USER', getenv('SMTP_USER') ?: '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', getenv('SMTP_PASS') ?: '');

// 2. Data Source Name
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// 3. PDO Options for Security and Error Handling
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throws errors if a query fails
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Returns data as an associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
];

// Development Mode: Allow logging emails to file if SMTP fails
define('ALLOW_LOCAL_MAIL_LOG', true);

try {
    // 4. Initialize Connection
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Optional: echo "Connected successfully"; // Use only for testing
} catch (PDOException $e) {
    // 5. Handle Connection Errors professionally
    // In production, log the error to a file instead of echoing it
    die("Database connection failed: " . $e->getMessage());
}
?>