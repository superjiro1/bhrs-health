<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$types = ['Prenatal', 'Postnatal', 'Child Growth'];
$type = get('type');
$q = get('q');
$view = get('view', 'active') === 'archived' ? 1 : 0;
$where = ['m.is_archived = ?']; $args = [$view];
if (in_array($type, $types, true)) { $where[] = 'm.record_type = ?'; $args[] = $type; }
if ($q !== '') {
    $where[] = '(r.first_name LIKE ? OR r.last_name LIKE ? OR r.resident_code LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($args, $like, $like, $like);
}
$w = implode(' AND ', $where);
$cnt = db()->prepare("SELECT COUNT(*) FROM maternal_child_records m JOIN residents r ON r.id = m.resident_id WHERE $w");
$cnt->execute($args);
$p = paginate((int)$cnt->fetchColumn(), (int)get('page', '1'));
$st = db()->prepare("SELECT m.*, r.first_name, r.last_name, r.resident_code FROM maternal_child_records m JOIN residents r ON r.id = m.resident_id WHERE $w ORDER BY m.visit_date DESC, m.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}");
$st->execute($args);
$rows = $st->fetchAll();

$pageTitle = 'Maternal and Child Health';
$activeNav = 'maternal';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <ul class="nav nav-pills mb-3">
    <?php foreach (array_merge(['' => 'All'], array_combine($types, $types)) as $k => $label): ?>
      <li class="nav-item"><a class="nav-link py-1 <?= $type === (string)$k ? 'active' : '' ?>" style="<?= $type === (string)$k ? 'background:var(--sage)' : 'color:var(--sage-dark)' ?>" href="?type=<?= e(urlencode((string)$k)) ?>&view=<?= $view ? 'archived' : 'active' ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
    <form class="d-flex gap-2" method="get"><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="view" value="<?= $view ? 'archived' : 'active' ?>">
      <input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Search resident"><button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button></form>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="?type=<?= e($type) ?>&view=<?= $view ? 'active' : 'archived' ?>"><i class="bi bi-archive"></i> <?= $view ? 'Show active' : 'Show archived' ?></a>
      <a class="btn btn-sm btn-primary" href="form.php"><i class="bi bi-plus-lg"></i> New record</a>
    </div>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Resident</th><th>Type</th><th>Visit date</th><th>Key details</th><th>Next visit</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $m): ?><tr>
      <td><a class="fw-semibold text-decoration-none" href="view.php?id=<?= (int)$m['id'] ?>"><?= e($m['last_name'] . ', ' . $m['first_name']) ?></a><br><small class="text-muted"><?= e($m['resident_code']) ?></small></td>
      <td><span class="badge badge-soft"><?= e($m['record_type']) ?></span></td>
      <td><?= e(fmt_date($m['visit_date'])) ?></td>
      <td class="small"><?php
        if ($m['record_type'] === 'Child Growth') { echo e(($m['weight_kg'] ?? '—') . ' kg · ' . ($m['height_cm'] ?? '—') . ' cm · ' . ($m['nutrition_status'] ?? '—')); }
        elseif ($m['record_type'] === 'Prenatal') { echo e(($m['gestational_weeks'] ?? '—') . ' weeks · BP ' . ($m['blood_pressure'] ?? '—') . ' · EDD ' . fmt_date($m['expected_delivery'])); }
        else { echo e('BP ' . ($m['blood_pressure'] ?? '—') . ' · ' . ($m['weight_kg'] ?? '—') . ' kg'); } ?></td>
      <td><?= e(fmt_date($m['next_visit_date'])) ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-light" href="view.php?id=<?= (int)$m['id'] ?>"><i class="bi bi-eye"></i></a>
        <?php if (!$view): ?><a class="btn btn-sm btn-light" href="form.php?id=<?= (int)$m['id'] ?>"><i class="bi bi-pencil"></i></a><?php endif; ?>
        <form method="post" action="archive.php" class="d-inline" data-confirm-title="<?= $view ? 'Restore record?' : 'Archive record?' ?>" data-confirm="<?= $view ? 'It will return to the active list.' : 'It will be hidden from active lists but not deleted.' ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="do" value="<?= $view ? 'restore' : 'archive' ?>">
          <button class="btn btn-sm btn-light"><i class="bi <?= $view ? 'bi-arrow-counterclockwise' : 'bi-archive' ?>"></i></button></form>
      </td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-heart-pulse"></i>No records found.</div></td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3"><small class="text-muted"><?= (int)$p['total'] ?> record(s)</small><?= pager($p) ?></div>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
