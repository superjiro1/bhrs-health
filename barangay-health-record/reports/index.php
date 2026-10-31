<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

require __DIR__ . '/period.php';   // provides $period, $date, $from, $to, $label

$pdo = db();
$run = function (string $sql, array $a) use ($pdo): array { $s = $pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); };
$one = fn(string $sql, array $a) => (int)$run($sql, $a)[0]['n'];

$totals = [
    'New residents registered' => $one('SELECT COUNT(*) n FROM residents WHERE DATE(created_at) BETWEEN ? AND ?', [$from, $to]),
    'Consultations' => $one('SELECT COUNT(*) n FROM consultations WHERE is_archived = 0 AND consult_date BETWEEN ? AND ?', [$from, $to]),
    'Vaccinations administered' => $one('SELECT COUNT(*) n FROM immunizations WHERE is_archived = 0 AND date_administered BETWEEN ? AND ?', [$from, $to]),
    'Maternal & child visits' => $one('SELECT COUNT(*) n FROM maternal_child_records WHERE is_archived = 0 AND visit_date BETWEEN ? AND ?', [$from, $to]),
];
$diag = $run("SELECT diagnosis, COUNT(*) n FROM consultations WHERE is_archived = 0 AND consult_date BETWEEN ? AND ? AND diagnosis IS NOT NULL AND diagnosis <> '' GROUP BY diagnosis ORDER BY n DESC LIMIT 10", [$from, $to]);
$vax  = $run('SELECT vaccine_name, COUNT(*) n FROM immunizations WHERE is_archived = 0 AND date_administered BETWEEN ? AND ? GROUP BY vaccine_name ORDER BY n DESC', [$from, $to]);
$mc   = $run('SELECT record_type, COUNT(*) n FROM maternal_child_records WHERE is_archived = 0 AND visit_date BETWEEN ? AND ? GROUP BY record_type', [$from, $to]);
$byPurok = $run('SELECT r.purok, COUNT(*) n FROM consultations c JOIN residents r ON r.id = c.resident_id WHERE c.is_archived = 0 AND c.consult_date BETWEEN ? AND ? GROUP BY r.purok ORDER BY r.purok', [$from, $to]);

$pageTitle = 'Reports';
$activeNav = 'reports';
require __DIR__ . '/../includes/header.php';
$qs = http_build_query(['period' => $period, 'date' => $date]);
?>
<div class="card mb-3 no-print"><div class="card-body">
  <form class="row g-2 align-items-end" method="get">
    <div class="col-6 col-md-2"><label class="form-label">Report type</label><select class="form-select form-select-sm" name="period" id="period"><?= options(['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly'], $period) ?></select></div>
    <div class="col-6 col-md-3"><label class="form-label">Date</label><input type="date" class="form-control form-control-sm" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>"></div>
    <div class="col-12 col-md-auto"><button class="btn btn-sm btn-primary"><i class="bi bi-bar-chart"></i> Generate</button></div>
    <div class="col-12 col-md ms-md-auto text-md-end">
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      <div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-filetype-csv"></i> Export CSV</button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php foreach (['consultations' => 'Consultations', 'immunizations' => 'Immunizations', 'residents' => 'New residents', 'maternal_child' => 'Maternal & child'] as $k => $lbl): ?>
            <li><a class="dropdown-item" href="export.php?<?= e($qs) ?>&dataset=<?= e($k) ?>"><?= e($lbl) ?></a></li><?php endforeach; ?>
        </ul></div>
    </div>
  </form>
</div></div>

<div class="card"><div class="card-body">
  <div class="text-center mb-4"><h5 class="mb-0"><?= e(APP_NAME) ?></h5><div class="text-muted"><?= e(ucfirst($period)) ?> Health Report — <?= e($periodLabel) ?></div></div>
  <div class="row g-3 mb-4">
    <?php foreach ($totals as $lbl => $n): ?><div class="col-6 col-md-3"><div class="border rounded p-3 text-center"><div class="stat-value"><?= number_format($n) ?></div><div class="stat-label"><?= e($lbl) ?></div></div></div><?php endforeach; ?>
  </div>
  <div class="row g-4">
    <div class="col-md-6"><h6>Top diagnoses</h6><table class="table table-sm"><tbody>
      <?php foreach ($diag as $d): ?><tr><td><?= e($d['diagnosis']) ?></td><td class="text-end"><?= (int)$d['n'] ?></td></tr><?php endforeach; ?>
      <?php if (!$diag): ?><tr><td class="text-muted">No data for this period.</td></tr><?php endif; ?></tbody></table></div>
    <div class="col-md-6"><h6>Vaccinations by type</h6><table class="table table-sm"><tbody>
      <?php foreach ($vax as $d): ?><tr><td><?= e($d['vaccine_name']) ?></td><td class="text-end"><?= (int)$d['n'] ?></td></tr><?php endforeach; ?>
      <?php if (!$vax): ?><tr><td class="text-muted">No data for this period.</td></tr><?php endif; ?></tbody></table></div>
    <div class="col-md-6"><h6>Consultations by purok</h6><table class="table table-sm"><tbody>
      <?php foreach ($byPurok as $d): ?><tr><td><?= e($d['purok']) ?></td><td class="text-end"><?= (int)$d['n'] ?></td></tr><?php endforeach; ?>
      <?php if (!$byPurok): ?><tr><td class="text-muted">No data for this period.</td></tr><?php endif; ?></tbody></table></div>
    <div class="col-md-6"><h6>Maternal &amp; child visits</h6><table class="table table-sm"><tbody>
      <?php foreach ($mc as $d): ?><tr><td><?= e($d['record_type']) ?></td><td class="text-end"><?= (int)$d['n'] ?></td></tr><?php endforeach; ?>
      <?php if (!$mc): ?><tr><td class="text-muted">No data for this period.</td></tr><?php endif; ?></tbody></table></div>
  </div>
  <div class="text-muted small mt-3">Generated <?= e(date('M d, Y h:i A')) ?> by <?= e(current_user()['full_name']) ?></div>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
