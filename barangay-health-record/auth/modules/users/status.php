<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();
verify_post();

$id = (int)post('id', '0');
if ($id === (int)current_user()['id']) {
    flash('danger', 'You cannot deactivate your own account.');
    redirect('modules/users/index.php');
}
$st = db()->prepare('SELECT id, role, status FROM users WHERE id = ?');
$st->execute([$id]);
$u = $st->fetch();
if ($u) {
    $new = $u['status'] === 'active' ? 'inactive' : 'active';
    if ($new === 'inactive' && $u['role'] === 'admin') {
        $n = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
        if ($n <= 1) { flash('danger', 'At least one active administrator is required.'); redirect('modules/users/index.php'); }
    }
    db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$new, $id]);
    log_activity('update', 'users', $id, 'Account ' . ($new === 'active' ? 'activated' : 'deactivated'));
    flash('success', 'Account ' . ($new === 'active' ? 'activated' : 'deactivated') . '.');
}
redirect('modules/users/index.php');
