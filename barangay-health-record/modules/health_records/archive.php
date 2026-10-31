<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();
verify_post();

$id = (int)post('id', '0');
$do = post('do');
if ($id && in_array($do, ['archive', 'restore'], true)) {
    $st = db()->prepare('UPDATE health_records SET is_archived = ? WHERE id = ?');
    $st->execute([$do === 'archive' ? 1 : 0, $id]);
    if ($st->rowCount() > 0) {
        log_activity($do, 'health_records', $id, 'Health record ' . $do . 'd');
        flash('success', 'Health record ' . $do . 'd.');
    }
}
redirect('modules/health_records/index.php' . ($do === 'restore' ? '?view=archived' : ''));
