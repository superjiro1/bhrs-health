<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$q = get('q');
$view = get('view', 'active') === 'archived' ? 1 : 0;
$where = ['h.is_archived = ?'];
$args = [$view];
if ($q !== '') {
    $where[] = '(r.first_name LIKE ? OR r.last_name LIKE ? OR r.resident_code LIKE ? OR h.allergies LIKE ? OR h.chronic_conditions LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($args, $like, $like, $like, $like, $like);
}
$w = implode(' AND ', $where);
$cnt = db()->prepare("SELECT COUNT(*) FROM health_records h JOIN residents r ON r.id = h.resident_id WHERE $w");
$cnt->execute($args);
$p = paginate((int)$cnt->fetchColumn(), (int)get('page', '1'));
$st = db()->prepare("SELECT h.*, r.first_name, r.last_name, r.resident_code FROM health_records h JOIN residents r ON r.id = h.resident_id WHERE $w ORDER BY r.last_name, r.first_name LIMIT {$p['per']} OFFSET {$p['offset']}");
$st->execute($args);
$rows = $st->fetchAll();

$pageTitle = 'Health Records';
$activeNav = 'health';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
    <form class="d-flex gap-2" method="get"><input type="hidden" name="view" value="<?= $view ? 'archived' : 'active' ?>">
      <input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Search resident, allergy, condition">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button></form>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="?view=<?= $view ? 'active' : 'archived' ?>"><i class="bi bi-archive"></i> <?= $view ? 'Show active' : 'Show archived' ?></a>
      <a class="btn btn-sm btn-primary" href="form.php"><i class="bi bi-plus-lg"></i> New health record</a>
    </div>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Resident</th><th>Allergies</th><th>Conditions</th><th>Medications</th><th>Updated</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $h): ?><tr>
      <td><a class="fw-semibold text-decoration-none" href="view.php?id=<?= (int)$h['id'] ?>"><?= e($h['last_name'] . ', ' . $h['first_name']) ?></a><br><small class="text-muted"><?= e($h['resident_code']) ?></small></td>
      <td><?= e($h['allergies'] ?: '—') ?></td><td><?= e($h['chronic_conditions'] ?: '—') ?></td><td><?= e($h['current_medications'] ?: '—') ?></td>
      <td><?= e(fmt_date($h['updated_at'])) ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-light" href="view.php?id=<?= (int)$h['id'] ?>"><i class="bi bi-eye"></i></a>
        <?php if (!$view): ?><a class="btn btn-sm btn-light" href="form.php?id=<?= (int)$h['id'] ?>"><i class="bi bi-pencil"></i></a><?php endif; ?>
        <form method="post" action="archive.php" class="d-inline" data-confirm-title="<?= $view ? 'Restore record?' : 'Archive record?' ?>" data-confirm="<?= $view ? 'The record will return to the active list.' : 'The record will be hidden from active lists but is not deleted.' ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><input type="hidden" name="do" value="<?= $view ? 'restore' : 'archive' ?>">
          <button class="btn btn-sm btn-light"><i class="bi <?= $view ? 'bi-arrow-counterclockwise' : 'bi-archive' ?>"></i></button></form>
      </td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-clipboard2-pulse"></i>No health records found.</div></td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3"><small class="text-muted"><?= (int)$p['total'] ?> record(s)</small><?= pager($p) ?></div>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
