<?php
// ================================================================
// EduStar — Database Configuration
// Copy this file and update values for your environment.
// ================================================================

define('DB_HOST',   getenv('DB_HOST')   ?: 'localhost');
define('DB_PORT',   getenv('DB_PORT')   ?: '3306');
define('DB_NAME',   getenv('DB_NAME')   ?: 'edustar');
define('DB_USER',   getenv('DB_USER')   ?: 'root');
define('DB_PASS',   getenv('DB_PASS')   ?: '');
define('DB_CHARSET','utf8mb4');

// JWT-style session secret — change this to a long random string
define('APP_SECRET', getenv('APP_SECRET') ?: 'CHANGE_THIS_SECRET_IN_PRODUCTION_abc123xyz');

// File upload settings
define('UPLOAD_DIR',      __DIR__ . '/../uploads/books/');
define('UPLOAD_URL_BASE', '/uploads/books/');
define('MAX_FILE_BYTES',  50 * 1024 * 1024);   // 50 MB
define('ALLOWED_TYPES',   ['application/pdf', 'image/jpeg', 'image/png', 'image/gif']);

// CORS — set to your frontend domain in production
define('CORS_ORIGIN', getenv('CORS_ORIGIN') ?: '*');

/**
 * Return a connected PDO instance (singleton).
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
