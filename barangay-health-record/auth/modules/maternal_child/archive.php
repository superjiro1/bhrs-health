<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();
verify_post();

$id = (int)post('id', '0');
$do = post('do');
if ($id && in_array($do, ['archive', 'restore'], true)) {
    $st = db()->prepare('UPDATE maternal_child_records SET is_archived = ? WHERE id = ?');
    $st->execute([$do === 'archive' ? 1 : 0, $id]);
    if ($st->rowCount() > 0) {
        log_activity($do, 'maternal_child', $id, 'Maternal/child record ' . $do . 'd');
        flash('success', 'Record ' . $do . 'd.');
    }
}
redirect('modules/maternal_child/index.php' . ($do === 'restore' ? '?view=archived' : ''));
