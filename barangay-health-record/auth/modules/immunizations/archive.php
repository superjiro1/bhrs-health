<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();
verify_post();

$id = (int)post('id', '0');
$do = post('do');
if ($id && in_array($do, ['archive', 'restore'], true)) {
    $st = db()->prepare('UPDATE immunizations SET is_archived = ? WHERE id = ?');
    $st->execute([$do === 'archive' ? 1 : 0, $id]);
    if ($st->rowCount() > 0) {
        log_activity($do, 'immunizations', $id, 'Immunization ' . $do . 'd');
        flash('success', 'Immunization ' . $do . 'd.');
    }
}
redirect('modules/immunizations/index.php' . ($do === 'restore' ? '?view=archived' : ''));
