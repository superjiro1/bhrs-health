<?php
declare(strict_types=1);

/**
 * Returns a shared PDO connection. On failure shows a friendly page that never reveals
 * credentials or driver messages.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = require __DIR__ . '/db.php';
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('BHRS DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        echo '<!doctype html><meta charset="utf-8"><title>Database unavailable</title>'
           . '<body style="font-family:sans-serif;max-width:560px;margin:80px auto;color:#333">'
           . '<h2>Database connection failed</h2>'
           . '<p>The system could not connect to the database. Please make sure MySQL is running in Laragon, '
           . 'that <code>database/database.sql</code> has been imported, and that <code>config/db.php</code> '
           . 'has the correct settings.</p></body>';
        exit;
    }
    return $pdo;
}
