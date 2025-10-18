<?php
// Reusable PDO database connection

require_once __DIR__ . '/../config.php';

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
    // Fallback environment variables (optional)
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'research_papers_db');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: ''));
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '', $options);
    } catch (PDOException $e) {
        error_log('[DB] Connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Database connection error.');
    }
    return $pdo;
}

?>
