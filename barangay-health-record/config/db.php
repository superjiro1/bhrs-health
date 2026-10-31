<?php
/**
 * Database credentials - EDIT THIS FILE for the environment where the app runs.
 *
 * For Laragon, the default MySQL user is "root". Whether it has a password depends on your
 * installation, so check yours: Laragon > Menu > MySQL > "Show/Change root password"
 * or open Database tab > Password. If root has no password, leave DB_PASS empty.
 * For InfinityFree or other shared hosting, use the database host, name, username,
 * and password shown in the hosting control panel; do not use localhost/root defaults.
 */
return [
    'host'    => '127.0.0.1',
    'port'    => 3306,
    'name'    => 'barangay_health_db',
    'user'    => 'root',
    'pass'    => '',
    'charset' => 'utf8mb4',
];
