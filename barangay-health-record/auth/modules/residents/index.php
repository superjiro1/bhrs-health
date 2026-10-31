<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$q      = get('q');
$purok  = get('purok');
$sex    = get('sex');
$view   = get('view', 'active') === 'archived' ? 1 : 0;

$where = ['is_archived = ?'];
$args  = [$view];
if ($q !== '') {
    $where[] = '(first_name LIKE ? OR last_name LIKE ? OR resident_code LIKE ? OR contact_no LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($args, $like, $like, $like, $like);
}
if ($purok !== '') { $where[] = 'purok = ?'; $args[] = $purok; }
if (in_array($sex, ['Male', 'Female'], true)) { $where[] = 'sex = ?'; $args[] = $sex; }
$w = implode(' AND ', $where);

$cnt = db()->prepare("SELECT COUNT(*) FROM residents WHERE $w");
$cnt->execute($args);
$p = paginate((int)$cnt->fetchColumn(), (int)get('page', '1'));

$st = db()->prepare("SELECT * FROM residents WHERE $w ORDER BY last_name, first_name LIMIT {$p['per']} OFFSET {$p['offset']}");
$st->execute($args);
$rows = $st->fetchAll();
$puroks = db()->query('SELECT DISTINCT purok FROM residents ORDER BY purok')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Resident Management';
$activeNav = 'residents';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
      <form class="row g-2 flex-grow-1" method="get">
        <input type="hidden" name="view" value="<?= $view ? 'archived' : 'active' ?>">
        <div class="col-12 col-md-4"><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Search name, ID or contact"></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" name="purok"><option value="">All puroks</option><?= options(array_combine($puroks, $puroks), $purok) ?></select></div>
        <div class="col-6 col-md-2"><select class="form-select form-select-sm" name="sex"><option value="">Any sex</option><?= options(['Male' => 'Male', 'Female' => 'Female'], $sex) ?></select></div>
        <div class="col-12 col-md-auto"><button class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Search</button>
          <a class="btn btn-sm btn-light" href="?view=<?= $view ? 'archived' : 'active' ?>">Reset</a></div>
      </form>
      <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="?view=<?= $view ? 'active' : 'archived' ?>"><i class="bi bi-archive"></i> <?= $view ? 'Show active' : 'Show archived' ?></a>
        <a class="btn btn-sm btn-primary" href="form.php"><i class="bi bi-plus-lg"></i> Register resident</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>ID</th><th>Name</th><th>Sex</th><th>Age</th><th>Purok</th><th>Contact</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['resident_code']) ?></td>
            <td><a href="view.php?id=<?= (int)$r['id'] ?>" class="fw-semibold text-decoration-none"><?= e($r['last_name'] . ', ' . $r['first_name'] . ' ' . ($r['middle_name'] ? mb_substr($r['middle_name'], 0, 1) . '.' : '')) ?></a></td>
            <td><?= e($r['sex']) ?></td>
            <td><?= e(age_from($r['birthdate'])) ?></td>
            <td><?= e($r['purok']) ?></td>
            <td><?= e($r['contact_no'] ?: '—') ?></td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-light" href="view.php?id=<?= (int)$r['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
              <?php if (!$view): ?><a class="btn btn-sm btn-light" href="form.php?id=<?= (int)$r['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a><?php endif; ?>
              <?php if (is_admin()): ?>
              <form method="post" action="archive.php" class="d-inline" data-confirm-title="<?= $view ? 'Restore resident?' : 'Archive resident?' ?>"
                    data-confirm="<?= $view ? 'This resident will be moved back to the active list.' : 'The resident and their records will be hidden from active lists. Nothing is deleted and it can be restored.' ?>">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="<?= $view ? 'restore' : 'archive' ?>">
                <button class="btn btn-sm btn-light" title="<?= $view ? 'Restore' : 'Archive' ?>"><i class="bi <?= $view ? 'bi-arrow-counterclockwise' : 'bi-archive' ?>"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7"><div class="empty"><i class="bi bi-people"></i>No residents found.</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
      <small class="text-muted"><?= (int)$p['total'] ?> resident(s)</small><?= pager($p) ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
