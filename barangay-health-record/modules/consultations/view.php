<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)get('id', '0');
$st = db()->prepare('SELECT c.*, r.first_name, r.last_name, r.resident_code, r.birthdate, r.sex, u.full_name AS attended FROM consultations c JOIN residents r ON r.id = c.resident_id LEFT JOIN users u ON u.id = c.attended_by WHERE c.id = ?');
$st->execute([$id]);
$c = $st->fetch();
if (!$c) { flash('danger', 'Consultation not found.'); redirect('modules/consultations/index.php'); }
log_activity('view', 'consultations', $id, 'Consultation viewed');
$pageTitle = 'Consultation Details';
$activeNav = 'consult';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
    <div><h5 class="mb-0"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></h5>
      <small class="text-muted"><?= e($c['resident_code']) ?> · <?= e($c['sex']) ?>, <?= e(age_from($c['birthdate'])) ?></small></div>
    <div class="d-flex gap-2 no-print">
      <a class="btn btn-sm btn-light" href="<?= e(url('modules/residents/view.php?id=' . (int)$c['resident_id'])) ?>">Resident profile</a>
      <?php if (!$c['is_archived']): ?><a class="btn btn-sm btn-primary" href="form.php?id=<?= $id ?>"><i class="bi bi-pencil"></i> Edit</a><?php endif; ?>
      <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
  </div><hr>
  <dl class="detail-grid row">
    <div class="col-md-3"><dt>Date</dt><dd><?= e(fmt_date($c['consult_date'])) ?></dd></div>
    <div class="col-md-3"><dt>Status</dt><dd><span class="badge badge-soft"><?= e($c['status']) ?></span></dd></div>
    <div class="col-md-3"><dt>Attended by</dt><dd><?= e($c['attended'] ?: '—') ?></dd></div>
    <div class="col-md-3"><dt>Follow-up</dt><dd><?= e(fmt_date($c['follow_up_date'])) ?></dd></div>
    <div class="col-md-4"><dt>Blood pressure</dt><dd><?= e($c['blood_pressure'] ?: '—') ?></dd></div>
    <div class="col-md-4"><dt>Temperature</dt><dd><?= e($c['temperature_c'] !== null ? $c['temperature_c'] . ' °C' : '—') ?></dd></div>
    <div class="col-md-4"><dt>Weight</dt><dd><?= e($c['weight_kg'] !== null ? $c['weight_kg'] . ' kg' : '—') ?></dd></div>
    <div class="col-12"><dt>Symptoms</dt><dd><?= nl2br(e($c['symptoms'])) ?></dd></div>
    <div class="col-md-6"><dt>Assessment</dt><dd><?= nl2br(e($c['assessment'] ?: '—')) ?></dd></div>
    <div class="col-md-6"><dt>Diagnosis</dt><dd><?= e($c['diagnosis'] ?: '—') ?></dd></div>
    <div class="col-12"><dt>Treatment</dt><dd><?= nl2br(e($c['treatment'] ?: '—')) ?></dd></div>
  </dl>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
