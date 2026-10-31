<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)get('id', '0');
$st = db()->prepare('SELECT * FROM residents WHERE id = ?');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) { flash('danger', 'Resident not found.'); redirect('modules/residents/index.php'); }

$hr = db()->prepare('SELECT * FROM health_records WHERE resident_id = ? AND is_archived = 0');
$hr->execute([$id]);
$health = $hr->fetch();

$cons = db()->prepare('SELECT id, consult_date, diagnosis, status FROM consultations WHERE resident_id = ? AND is_archived = 0 ORDER BY consult_date DESC LIMIT 10');
$cons->execute([$id]); $cons = $cons->fetchAll();
$imm = db()->prepare('SELECT * FROM immunizations WHERE resident_id = ? AND is_archived = 0 ORDER BY date_administered DESC');
$imm->execute([$id]); $imm = $imm->fetchAll();
$mc = db()->prepare('SELECT id, record_type, visit_date, next_visit_date FROM maternal_child_records WHERE resident_id = ? AND is_archived = 0 ORDER BY visit_date DESC LIMIT 10');
$mc->execute([$id]); $mc = $mc->fetchAll();

log_activity('view', 'residents', $id, 'Resident profile viewed');
$pageTitle = 'Resident Profile';
$activeNav = 'residents';
require __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <div><h5 class="mb-0"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></h5><small class="text-muted"><?= e($r['resident_code']) ?></small></div>
        <?php if ($r['is_archived']): ?><span class="badge text-bg-secondary">Archived</span><?php endif; ?>
      </div>
      <hr>
      <dl class="detail-grid mb-0">
        <dt>Sex / Age</dt><dd><?= e($r['sex']) ?>, <?= e(age_from($r['birthdate'])) ?></dd>
        <dt>Birthdate</dt><dd><?= e(fmt_date($r['birthdate'])) ?></dd>
        <dt>Civil status</dt><dd><?= e($r['civil_status']) ?></dd>
        <dt>Blood type</dt><dd><?= e($r['blood_type']) ?></dd>
        <dt>Address</dt><dd><?= e($r['purok']) ?><?= $r['address'] ? ', ' . e($r['address']) : '' ?></dd>
        <dt>Contact</dt><dd><?= e($r['contact_no'] ?: '—') ?></dd>
        <dt>PhilHealth</dt><dd><?= e($r['philhealth_no'] ?: '—') ?></dd>
        <dt>Guardian</dt><dd><?= e($r['guardian_name'] ?: '—') ?></dd>
      </dl>
      <?php if (!$r['is_archived']): ?><a class="btn btn-sm btn-outline-primary mt-2" href="form.php?id=<?= $id ?>"><i class="bi bi-pencil"></i> Edit profile</a><?php endif; ?>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-header d-flex justify-content-between">Health record
      <?php if (!$r['is_archived']): ?><a class="small fw-normal" href="<?= e(url('modules/health_records/form.php?resident_id=' . $id)) ?>"><?= $health ? 'Update' : 'Create' ?></a><?php endif; ?></div>
      <div class="card-body">
      <?php if ($health): ?>
        <dl class="detail-grid row mb-0">
          <div class="col-md-6"><dt>Allergies</dt><dd><?= e($health['allergies'] ?: '—') ?></dd></div>
          <div class="col-md-6"><dt>Chronic conditions</dt><dd><?= e($health['chronic_conditions'] ?: '—') ?></dd></div>
          <div class="col-md-6"><dt>Current medications</dt><dd><?= e($health['current_medications'] ?: '—') ?></dd></div>
          <div class="col-md-6"><dt>Height / Weight</dt><dd><?= e(($health['height_cm'] ?? '—') . ' cm / ' . ($health['weight_kg'] ?? '—') . ' kg') ?></dd></div>
        </dl>
      <?php else: ?><div class="empty py-3"><i class="bi bi-clipboard2-pulse"></i>No health record yet.</div><?php endif; ?>
    </div></div>

    <div class="card mb-3"><div class="card-header d-flex justify-content-between">Consultations
      <?php if (!$r['is_archived']): ?><a class="small fw-normal" href="<?= e(url('modules/consultations/form.php?resident_id=' . $id)) ?>">Add</a><?php endif; ?></div>
      <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
      <?php foreach ($cons as $c): ?><tr><td><?= e(fmt_date($c['consult_date'])) ?></td><td><?= e($c['diagnosis'] ?: '—') ?></td><td><span class="badge badge-soft"><?= e($c['status']) ?></span></td>
        <td class="text-end"><a href="<?= e(url('modules/consultations/view.php?id=' . (int)$c['id'])) ?>">View</a></td></tr><?php endforeach; ?>
      <?php if (!$cons): ?><tr><td class="empty">No consultations.</td></tr><?php endif; ?></tbody></table></div></div>

    <div class="row g-3">
      <div class="col-md-6"><div class="card h-100"><div class="card-header d-flex justify-content-between">Immunizations
        <?php if (!$r['is_archived']): ?><a class="small fw-normal" href="<?= e(url('modules/immunizations/form.php?resident_id=' . $id)) ?>">Add</a><?php endif; ?></div>
        <ul class="list-group list-group-flush">
        <?php foreach ($imm as $i): ?><li class="list-group-item small"><strong><?= e($i['vaccine_name']) ?></strong> (dose <?= (int)$i['dose_no'] ?>)<br><span class="text-muted"><?= e(fmt_date($i['date_administered'])) ?><?= $i['next_dose_date'] ? ' · next ' . e(fmt_date($i['next_dose_date'])) : '' ?></span></li><?php endforeach; ?>
        <?php if (!$imm): ?><li class="list-group-item empty">None recorded.</li><?php endif; ?></ul></div></div>
      <div class="col-md-6"><div class="card h-100"><div class="card-header d-flex justify-content-between">Maternal &amp; child
        <?php if (!$r['is_archived']): ?><a class="small fw-normal" href="<?= e(url('modules/maternal_child/form.php?resident_id=' . $id)) ?>">Add</a><?php endif; ?></div>
        <ul class="list-group list-group-flush">
        <?php foreach ($mc as $m): ?><li class="list-group-item small d-flex justify-content-between"><span><strong><?= e($m['record_type']) ?></strong><br><span class="text-muted"><?= e(fmt_date($m['visit_date'])) ?></span></span><a href="<?= e(url('modules/maternal_child/view.php?id=' . (int)$m['id'])) ?>">View</a></li><?php endforeach; ?>
        <?php if (!$mc): ?><li class="list-group-item empty">None recorded.</li><?php endif; ?></ul></div></div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
