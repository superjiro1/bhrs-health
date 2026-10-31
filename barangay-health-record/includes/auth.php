<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
    // Inactivity timeout
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        fresh_session();
        flash('warning', 'You were logged out because of inactivity.');
        redirect('auth/login.php');
    }
    $_SESSION['last_activity'] = time();
    // Make sure the account still exists and is active
    $st = db()->prepare('SELECT id, role, status FROM users WHERE id = ?');
    $st->execute([$_SESSION['user']['id']]);
    $u = $st->fetch();
    if (!$u || $u['status'] !== 'active') {
        fresh_session();
        flash('danger', 'Your account is not active.');
        redirect('auth/login.php');
    }
    $_SESSION['user']['role'] = $u['role'];
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        require ROOT_PATH . '/includes/header.php';
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        require ROOT_PATH . '/includes/footer.php';
        exit;
    }
}
