<?php
// ================================================================
// EduStar — Database Configuration (InfinityFree)
// ================================================================

define('DB_HOST',    'sql303.infinityfree.com');
define('DB_PORT',    '3306');
define('DB_NAME',    'if0_41350612_edustar');
define('DB_USER',    'if0_41350612');
define('DB_PASS',    '0EPfOPRSTK');
define('DB_CHARSET', 'utf8mb4');

define('APP_SECRET', 'EduStar_ShonZ_2026_xK9mP3qR7vL2nW8jT5yB4cD6fH1sA0');

define('UPLOAD_DIR',      __DIR__ . '/../uploads/books/');
define('UPLOAD_URL_BASE', '/uploads/books/');
define('MAX_FILE_BYTES',  50 * 1024 * 1024);
define('ALLOWED_TYPES',   ['application/pdf', 'image/jpeg', 'image/png', 'image/gif']);

define('CORS_ORIGIN', 'https://shonz.great-site.net');

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
