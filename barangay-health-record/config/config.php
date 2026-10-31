<?php
/**
 * Application configuration.
 * Database credentials live in config/db.php (edit that file for your Laragon setup).
 */
declare(strict_types=1);

define('APP_NAME', 'Barangay Health Record System');
define('APP_SHORT', 'BHRS');
define('ROOT_PATH', dirname(__DIR__));
define('SESSION_TIMEOUT', 1800); // seconds of inactivity before automatic logout (30 min)
define('PER_PAGE', 10);

// Web path of the project, detected automatically (e.g. "/barangay-health-record").
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$base = '';
if ($docRoot !== false) {
    $base = str_replace('\\', '/', substr(ROOT_PATH, strlen($docRoot)));
    $base = '/' . trim($base, '/');
    if ($base === '/') {
        $base = '';
    }
}
define('BASE_URL', $base);

date_default_timezone_set('Asia/Manila');
