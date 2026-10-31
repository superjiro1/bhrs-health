<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$q = get('q'); $status = get('status'); $from = get('from'); $to = get('to');
$view = get('view', 'active') === 'archived' ? 1 : 0;
$where = ['c.is_archived = ?']; $args = [$view];
if ($q !== '') {
    $where[] = '(r.first_name LIKE ? OR r.last_name LIKE ? OR r.resident_code LIKE ? OR c.diagnosis LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($args, $like, $like, $like, $like);
}
if (in_array($status, ['Open', 'Follow-up', 'Closed'], true)) { $where[] = 'c.status = ?'; $args[] = $status; }
if (valid_date($from)) { $where[] = 'c.consult_date >= ?'; $args[] = $from; }
if (valid_date($to))   { $where[] = 'c.consult_date <= ?'; $args[] = $to; }
$w = implode(' AND ', $where);
$cnt = db()->prepare("SELECT COUNT(*) FROM consultations c JOIN residents r ON r.id = c.resident_id WHERE $w");
$cnt->execute($args);
$p = paginate((int)$cnt->fetchColumn(), (int)get('page', '1'));
$st = db()->prepare("SELECT c.*, r.first_name, r.last_name, r.resident_code FROM consultations c JOIN residents r ON r.id = c.resident_id WHERE $w ORDER BY c.consult_date DESC, c.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}");
$st->execute($args);
$rows = $st->fetchAll();

$pageTitle = 'Consultation Management';
$activeNav = 'consult';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
    <form class="row g-2 flex-grow-1" method="get"><input type="hidden" name="view" value="<?= $view ? 'archived' : 'active' ?>">
      <div class="col-12 col-md-3"><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Search resident or diagnosis"></div>
      <div class="col-6 col-md-2"><select class="form-select form-select-sm" name="status"><option value="">Any status</option><?= options(['Open', 'Follow-up', 'Closed'], $status) ?></select></div>
      <div class="col-6 col-md-2"><input type="date" class="form-control form-control-sm" name="from" value="<?= e($from) ?>" title="From"></div>
      <div class="col-6 col-md-2"><input type="date" class="form-control form-control-sm" name="to" value="<?= e($to) ?>" title="To"></div>
      <div class="col-6 col-md-auto"><button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button> <a class="btn btn-sm btn-light" href="?view=<?= $view ? 'archived' : 'active' ?>">Reset</a></div>
    </form>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="?view=<?= $view ? 'active' : 'archived' ?>"><i class="bi bi-archive"></i> <?= $view ? 'Show active' : 'Show archived' ?></a>
      <a class="btn btn-sm btn-primary" href="form.php"><i class="bi bi-plus-lg"></i> New consultation</a>
    </div>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Date</th><th>Resident</th><th>Diagnosis</th><th>Follow-up</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $c): ?><tr>
      <td><?= e(fmt_date($c['consult_date'])) ?></td>
      <td><a class="fw-semibold text-decoration-none" href="view.php?id=<?= (int)$c['id'] ?>"><?= e($c['last_name'] . ', ' . $c['first_name']) ?></a><br><small class="text-muted"><?= e($c['resident_code']) ?></small></td>
      <td><?= e($c['diagnosis'] ?: '—') ?></td><td><?= e(fmt_date($c['follow_up_date'])) ?></td>
      <td><span class="badge badge-soft"><?= e($c['status']) ?></span></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-light" href="view.php?id=<?= (int)$c['id'] ?>"><i class="bi bi-eye"></i></a>
        <?php if (!$view): ?><a class="btn btn-sm btn-light" href="form.php?id=<?= (int)$c['id'] ?>"><i class="bi bi-pencil"></i></a><?php endif; ?>
        <form method="post" action="archive.php" class="d-inline" data-confirm-title="<?= $view ? 'Restore consultation?' : 'Archive consultation?' ?>" data-confirm="<?= $view ? 'It will return to the active list.' : 'It will be hidden from active lists but not deleted.' ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="do" value="<?= $view ? 'restore' : 'archive' ?>">
          <button class="btn btn-sm btn-light"><i class="bi <?= $view ? 'bi-arrow-counterclockwise' : 'bi-archive' ?>"></i></button></form>
      </td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-stethoscope"></i>No consultations found.</div></td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3"><small class="text-muted"><?= (int)$p['total'] ?> consultation(s)</small><?= pager($p) ?></div>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
