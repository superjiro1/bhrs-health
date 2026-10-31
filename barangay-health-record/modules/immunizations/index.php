<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$q = get('q'); $vaccine = get('vaccine'); $due = get('due');
$view = get('view', 'active') === 'archived' ? 1 : 0;
$where = ['i.is_archived = ?']; $args = [$view];
if ($q !== '') {
    $where[] = '(r.first_name LIKE ? OR r.last_name LIKE ? OR r.resident_code LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($args, $like, $like, $like);
}
if ($vaccine !== '') { $where[] = 'i.vaccine_name = ?'; $args[] = $vaccine; }
if ($due === 'upcoming') { $where[] = 'i.next_dose_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'; }
if ($due === 'overdue')  { $where[] = 'i.next_dose_date < CURDATE()'; }
$w = implode(' AND ', $where);
$cnt = db()->prepare("SELECT COUNT(*) FROM immunizations i JOIN residents r ON r.id = i.resident_id WHERE $w");
$cnt->execute($args);
$p = paginate((int)$cnt->fetchColumn(), (int)get('page', '1'));
$st = db()->prepare("SELECT i.*, r.first_name, r.last_name, r.resident_code FROM immunizations i JOIN residents r ON r.id = i.resident_id WHERE $w ORDER BY i.date_administered DESC, i.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}");
$st->execute($args);
$rows = $st->fetchAll();
$vaccines = db()->query('SELECT DISTINCT vaccine_name FROM immunizations ORDER BY vaccine_name')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Immunization Management';
$activeNav = 'immun';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
    <form class="row g-2 flex-grow-1" method="get"><input type="hidden" name="view" value="<?= $view ? 'archived' : 'active' ?>">
      <div class="col-12 col-md-3"><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Search resident"></div>
      <div class="col-6 col-md-3"><select class="form-select form-select-sm" name="vaccine"><option value="">All vaccines</option><?= options(array_combine($vaccines, $vaccines), $vaccine) ?></select></div>
      <div class="col-6 col-md-3"><select class="form-select form-select-sm" name="due"><option value="">Any schedule</option><?= options(['upcoming' => 'Next dose within 30 days', 'overdue' => 'Next dose overdue'], $due) ?></select></div>
      <div class="col-12 col-md-auto"><button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button> <a class="btn btn-sm btn-light" href="?view=<?= $view ? 'archived' : 'active' ?>">Reset</a></div>
    </form>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="?view=<?= $view ? 'active' : 'archived' ?>"><i class="bi bi-archive"></i> <?= $view ? 'Show active' : 'Show archived' ?></a>
      <a class="btn btn-sm btn-primary" href="form.php"><i class="bi bi-plus-lg"></i> Record vaccination</a>
    </div>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Resident</th><th>Vaccine</th><th>Dose</th><th>Administered</th><th>Next dose</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $i): $overdue = $i['next_dose_date'] && $i['next_dose_date'] < date('Y-m-d'); ?><tr>
      <td><a class="fw-semibold text-decoration-none" href="<?= e(url('modules/residents/view.php?id=' . (int)$i['resident_id'])) ?>"><?= e($i['last_name'] . ', ' . $i['first_name']) ?></a><br><small class="text-muted"><?= e($i['resident_code']) ?></small></td>
      <td><?= e($i['vaccine_name']) ?></td><td><?= (int)$i['dose_no'] ?></td><td><?= e(fmt_date($i['date_administered'])) ?></td>
      <td><?= e(fmt_date($i['next_dose_date'])) ?><?= $overdue ? ' <span class="badge text-bg-warning">Overdue</span>' : '' ?></td>
      <td class="text-end text-nowrap">
        <?php if (!$view): ?><a class="btn btn-sm btn-light" href="form.php?id=<?= (int)$i['id'] ?>"><i class="bi bi-pencil"></i></a><?php endif; ?>
        <form method="post" action="archive.php" class="d-inline" data-confirm-title="<?= $view ? 'Restore entry?' : 'Archive entry?' ?>" data-confirm="<?= $view ? 'It will return to the active list.' : 'It will be hidden from active lists but not deleted.' ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$i['id'] ?>"><input type="hidden" name="do" value="<?= $view ? 'restore' : 'archive' ?>">
          <button class="btn btn-sm btn-light"><i class="bi <?= $view ? 'bi-arrow-counterclockwise' : 'bi-archive' ?>"></i></button></form>
      </td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-shield-plus"></i>No immunization records found.</div></td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3"><small class="text-muted"><?= (int)$p['total'] ?> record(s)</small><?= pager($p) ?></div>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
