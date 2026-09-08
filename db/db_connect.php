<?php
/**
 * Database connection (PDO)
 * IT Complaints Support System
 *
 * Include this file wherever you need database access:
 *   require_once __DIR__ . '/../db/db_connect.php';
 * Then use the $pdo variable.
 */

// ------------------------------------------------------------
// Base URL — IMPORTANT: change this to match your project's
// folder name inside htdocs.
//
// Example: if you browse to the login page at
//   http://localhost/IT_COMPLAINTS_SUPPORT_SYSTEM/login.php
// then set:
//   define('BASE_URL', '/IT_COMPLAINTS_SUPPORT_SYSTEM');
// ------------------------------------------------------------
define('BASE_URL', '/IT_COMPLAINTS_SUPPORT_SYSTEM');

// ------------------------------------------------------------
// Connection settings — update these to match your environment
// ------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'it_complaints_system');
define('DB_USER', 'root');       // change for production
define('DB_PASS', '');           // change for production
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}