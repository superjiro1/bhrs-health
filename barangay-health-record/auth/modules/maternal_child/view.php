<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)get('id', '0');
$st = db()->prepare('SELECT m.*, r.first_name, r.last_name, r.resident_code, r.birthdate, r.sex FROM maternal_child_records m JOIN residents r ON r.id = m.resident_id WHERE m.id = ?');
$st->execute([$id]);
$m = $st->fetch();
if (!$m) { flash('danger', 'Record not found.'); redirect('modules/maternal_child/index.php'); }

$hist = db()->prepare('SELECT id, visit_date, weight_kg, height_cm, nutrition_status, blood_pressure, gestational_weeks FROM maternal_child_records WHERE resident_id = ? AND record_type = ? AND is_archived = 0 ORDER BY visit_date DESC');
$hist->execute([$m['resident_id'], $m['record_type']]);
$hist = $hist->fetchAll();
$imm = [];
if ($m['record_type'] === 'Child Growth') {
    $i = db()->prepare('SELECT vaccine_name, dose_no, date_administered, next_dose_date FROM immunizations WHERE resident_id = ? AND is_archived = 0 ORDER BY date_administered');
    $i->execute([$m['resident_id']]);
    $imm = $i->fetchAll();
}
log_activity('view', 'maternal_child', $id, 'Maternal/child record viewed');
$pageTitle = 'Maternal / Child Record';
$activeNav = 'maternal';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card mb-3"><div class="card-body">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
    <div><h5 class="mb-0"><?= e($m['first_name'] . ' ' . $m['last_name']) ?> <span class="badge badge-soft"><?= e($m['record_type']) ?></span></h5>
      <small class="text-muted"><?= e($m['resident_code']) ?> · <?= e($m['sex']) ?>, <?= e(age_from($m['birthdate'])) ?></small></div>
    <div class="d-flex gap-2 no-print">
      <a class="btn btn-sm btn-light" href="<?= e(url('modules/residents/view.php?id=' . (int)$m['resident_id'])) ?>">Resident profile</a>
      <?php if (!$m['is_archived']): ?><a class="btn btn-sm btn-primary" href="form.php?id=<?= $id ?>"><i class="bi bi-pencil"></i> Edit</a><?php endif; ?>
      <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
  </div><hr>
  <dl class="detail-grid row mb-0">
    <div class="col-md-3"><dt>Visit date</dt><dd><?= e(fmt_date($m['visit_date'])) ?></dd></div>
    <div class="col-md-3"><dt>Next visit</dt><dd><?= e(fmt_date($m['next_visit_date'])) ?></dd></div>
    <?php if ($m['record_type'] === 'Prenatal'): ?>
      <div class="col-md-3"><dt>Gestational age</dt><dd><?= e($m['gestational_weeks'] !== null ? $m['gestational_weeks'] . ' weeks' : '—') ?></dd></div>
      <div class="col-md-3"><dt>Expected delivery</dt><dd><?= e(fmt_date($m['expected_delivery'])) ?></dd></div>
      <div class="col-md-3"><dt>LMP</dt><dd><?= e(fmt_date($m['lmp_date'])) ?></dd></div>
    <?php endif; ?>
    <?php if ($m['record_type'] !== 'Child Growth'): ?><div class="col-md-3"><dt>Blood pressure</dt><dd><?= e($m['blood_pressure'] ?: '—') ?></dd></div><?php endif; ?>
    <div class="col-md-3"><dt>Weight</dt><dd><?= e($m['weight_kg'] !== null ? $m['weight_kg'] . ' kg' : '—') ?></dd></div>
    <?php if ($m['record_type'] === 'Child Growth'): ?>
      <div class="col-md-3"><dt>Height / length</dt><dd><?= e($m['height_cm'] !== null ? $m['height_cm'] . ' cm' : '—') ?></dd></div>
      <div class="col-md-3"><dt>Head circumference</dt><dd><?= e($m['head_circ_cm'] !== null ? $m['head_circ_cm'] . ' cm' : '—') ?></dd></div>
      <div class="col-md-3"><dt>Nutrition status</dt><dd><?= e($m['nutrition_status'] ?: '—') ?></dd></div>
    <?php endif; ?>
    <?php if ($m['record_type'] === 'Prenatal'): ?><div class="col-12"><dt>Risk notes</dt><dd><?= nl2br(e($m['risk_notes'] ?: '—')) ?></dd></div><?php endif; ?>
    <div class="col-12"><dt>Notes</dt><dd><?= nl2br(e($m['notes'] ?: '—')) ?></dd></div>
  </dl>
</div></div>

<div class="row g-3">
  <div class="col-lg-<?= $imm ? '7' : '12' ?>"><div class="card"><div class="card-header"><?= $m['record_type'] === 'Child Growth' ? 'Growth monitoring history' : 'Visit history' ?></div>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Date</th>
      <?php if ($m['record_type'] === 'Child Growth'): ?><th>Weight</th><th>Height</th><th>Nutrition</th><?php else: ?><th>BP</th><?php if ($m['record_type'] === 'Prenatal'): ?><th>Weeks</th><?php endif; ?><th>Weight</th><?php endif; ?></tr></thead><tbody>
      <?php foreach ($hist as $h): ?><tr><td><?= e(fmt_date($h['visit_date'])) ?></td>
        <?php if ($m['record_type'] === 'Child Growth'): ?><td><?= e($h['weight_kg'] ?? '—') ?> kg</td><td><?= e($h['height_cm'] ?? '—') ?> cm</td><td><?= e($h['nutrition_status'] ?? '—') ?></td>
        <?php else: ?><td><?= e($h['blood_pressure'] ?? '—') ?></td><?php if ($m['record_type'] === 'Prenatal'): ?><td><?= e($h['gestational_weeks'] ?? '—') ?></td><?php endif; ?><td><?= e($h['weight_kg'] ?? '—') ?> kg</td><?php endif; ?></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
  <?php if ($imm): ?>
  <div class="col-lg-5"><div class="card"><div class="card-header">Immunization history</div>
    <ul class="list-group list-group-flush"><?php foreach ($imm as $i): ?>
      <li class="list-group-item small"><strong><?= e($i['vaccine_name']) ?></strong> (dose <?= (int)$i['dose_no'] ?>) · <?= e(fmt_date($i['date_administered'])) ?><?= $i['next_dose_date'] ? '<br><span class="text-muted">Next: ' . e(fmt_date($i['next_dose_date'])) . '</span>' : '' ?></li>
    <?php endforeach; ?></ul></div></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
