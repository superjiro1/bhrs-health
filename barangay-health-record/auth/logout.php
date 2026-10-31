<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $t = $_POST['csrf'] ?? '';
    if (is_string($t) && hash_equals(csrf_token(), $t)) {
        log_activity('logout', 'auth', (int)$_SESSION['user']['id'], 'User logged out');
        fresh_session();
        flash('success', 'You have been signed out.');
    }
}
redirect('auth/login.php');
