<?php
require_once __DIR__ . '/config.php';

// Database credentials for XAMPP (MySQL)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sponsor_app');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

function get_db(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // 1. First try MySQL (XAMPP default)
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // MySQL wasn't reached, try SQLite fallback
    }

    // 2. Fallback to SQLite (ensures instant out-of-the-box operation in any environment)
    $sqliteFile = dirname(__DIR__) . '/database/sponsor_app.sqlite';
    if (!file_exists($sqliteFile)) {
        require_once dirname(__DIR__) . '/database/init_db.php';
    }

    try {
        $pdo = new PDO("sqlite:" . $sqliteFile, null, null, $options);
        // Enable foreign keys in SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failure: " . htmlspecialchars($e->getMessage()));
    }
}
