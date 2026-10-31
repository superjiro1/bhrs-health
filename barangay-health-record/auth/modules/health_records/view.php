<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)get('id', '0');
$st = db()->prepare('SELECT h.*, r.first_name, r.last_name, r.resident_code, r.birthdate, r.sex, r.blood_type FROM health_records h JOIN residents r ON r.id = h.resident_id WHERE h.id = ?');
$st->execute([$id]);
$h = $st->fetch();
if (!$h) { flash('danger', 'Health record not found.'); redirect('modules/health_records/index.php'); }
log_activity('view', 'health_records', $id, 'Health record viewed');
$pageTitle = 'Health Record';
$activeNav = 'health';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
    <div><h5 class="mb-0"><?= e($h['first_name'] . ' ' . $h['last_name']) ?></h5>
      <small class="text-muted"><?= e($h['resident_code']) ?> · <?= e($h['sex']) ?>, <?= e(age_from($h['birthdate'])) ?> · Blood type <?= e($h['blood_type']) ?></small></div>
    <div class="d-flex gap-2 no-print">
      <a class="btn btn-sm btn-light" href="<?= e(url('modules/residents/view.php?id=' . (int)$h['resident_id'])) ?>">Resident profile</a>
      <?php if (!$h['is_archived']): ?><a class="btn btn-sm btn-primary" href="form.php?id=<?= $id ?>"><i class="bi bi-pencil"></i> Edit</a><?php endif; ?>
      <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
  </div><hr>
  <dl class="detail-grid row">
    <div class="col-md-4"><dt>Height</dt><dd><?= e($h['height_cm'] !== null ? $h['height_cm'] . ' cm' : '—') ?></dd></div>
    <div class="col-md-4"><dt>Weight</dt><dd><?= e($h['weight_kg'] !== null ? $h['weight_kg'] . ' kg' : '—') ?></dd></div>
    <div class="col-md-4"><dt>Last updated</dt><dd><?= e(fmt_date($h['updated_at'])) ?></dd></div>
    <div class="col-md-6"><dt>Allergies</dt><dd><?= nl2br(e($h['allergies'] ?: '—')) ?></dd></div>
    <div class="col-md-6"><dt>Chronic conditions</dt><dd><?= nl2br(e($h['chronic_conditions'] ?: '—')) ?></dd></div>
    <div class="col-md-6"><dt>Current medications</dt><dd><?= nl2br(e($h['current_medications'] ?: '—')) ?></dd></div>
    <div class="col-md-6"><dt>Medical history</dt><dd><?= nl2br(e($h['medical_history'] ?: '—')) ?></dd></div>
    <div class="col-12"><dt>Health notes</dt><dd><?= nl2br(e($h['health_notes'] ?: '—')) ?></dd></div>
  </dl>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
