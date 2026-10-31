<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();
verify_post();

$id = (int)post('id', '0');
$do = post('do');
if ($id && in_array($do, ['archive', 'restore'], true)) {
    $flag = $do === 'archive' ? 1 : 0;
    $st = db()->prepare('UPDATE residents SET is_archived = ? WHERE id = ?');
    $st->execute([$flag, $id]);
    if ($st->rowCount() > 0) {
        log_activity($do, 'residents', $id, 'Resident ' . $do . 'd');
        flash('success', 'Resident ' . $do . 'd.');
    }
}
redirect('modules/residents/index.php' . ($do === 'restore' ? '?view=archived' : ''));
