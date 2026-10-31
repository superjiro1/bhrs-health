<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)get('id', '0');
$h = ['resident_id' => (int)get('resident_id', '0'), 'medical_history' => '', 'allergies' => '', 'chronic_conditions' => '', 'current_medications' => '', 'height_cm' => '', 'weight_kg' => '', 'health_notes' => ''];
if ($id) {
    $st = db()->prepare('SELECT * FROM health_records WHERE id = ? AND is_archived = 0');
    $st->execute([$id]);
    $f = $st->fetch();
    if (!$f) { flash('danger', 'Health record not found.'); redirect('modules/health_records/index.php'); }
    $h = array_merge($h, $f);
} elseif ($h['resident_id']) {
    // Only one health record per resident: jump to the existing one for editing.
    $st = db()->prepare('SELECT id, is_archived FROM health_records WHERE resident_id = ?');
    $st->execute([$h['resident_id']]);
    if ($ex = $st->fetch()) {
        if ($ex['is_archived']) { flash('warning', 'This resident has an archived health record. Restore it instead of creating a new one.'); redirect('modules/health_records/index.php?view=archived'); }
        redirect('modules/health_records/form.php?id=' . (int)$ex['id']);
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_post();
    $h['resident_id'] = $id ? (int)$h['resident_id'] : (int)post('resident_id', '0');
    foreach (['medical_history', 'allergies', 'chronic_conditions', 'current_medications', 'height_cm', 'weight_kg', 'health_notes'] as $k) { $h[$k] = post($k); }

    $rs = db()->prepare('SELECT id FROM residents WHERE id = ? AND is_archived = 0');
    $rs->execute([$h['resident_id']]);
    if (!$rs->fetch()) { $errors[] = 'Please select a valid resident.'; }
    if ($h['height_cm'] !== '' && (!is_numeric($h['height_cm']) || $h['height_cm'] < 30 || $h['height_cm'] > 250)) { $errors[] = 'Height must be between 30 and 250 cm.'; }
    if ($h['weight_kg'] !== '' && (!is_numeric($h['weight_kg']) || $h['weight_kg'] < 1 || $h['weight_kg'] > 400)) { $errors[] = 'Weight must be between 1 and 400 kg.'; }
    foreach (['medical_history', 'allergies', 'chronic_conditions', 'current_medications', 'health_notes'] as $k) {
        if (mb_strlen($h[$k]) > 3000) { $errors[] = 'Text fields are limited to 3000 characters.'; break; }
    }
    if (!$id && !$errors) {
        $d = db()->prepare('SELECT id FROM health_records WHERE resident_id = ?');
        $d->execute([$h['resident_id']]);
        if ($d->fetch()) { $errors[] = 'This resident already has a health record.'; }
    }
    if (!$errors) {
        $vals = [null_if_empty($h['medical_history']), null_if_empty($h['allergies']), null_if_empty($h['chronic_conditions']), null_if_empty($h['current_medications']),
                 null_if_empty((string)$h['height_cm']), null_if_empty((string)$h['weight_kg']), null_if_empty($h['health_notes'])];
        if ($id) {
            $vals[] = $id;
            db()->prepare('UPDATE health_records SET medical_history=?, allergies=?, chronic_conditions=?, current_medications=?, height_cm=?, weight_kg=?, health_notes=? WHERE id=?')->execute($vals);
            log_activity('update', 'health_records', $id, 'Health record updated');
        } else {
            array_unshift($vals, $h['resident_id']);
            $vals[] = current_user()['id'];
            db()->prepare('INSERT INTO health_records (resident_id, medical_history, allergies, chronic_conditions, current_medications, height_cm, weight_kg, health_notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)')->execute($vals);
            $id = (int)db()->lastInsertId();
            log_activity('create', 'health_records', $id, 'Health record created');
        }
        flash('success', 'Health record saved.');
        redirect('modules/health_records/view.php?id=' . $id);
    }
}
$pageTitle = $id ? 'Edit Health Record' : 'New Health Record';
$activeNav = 'health';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" class="needs-validation row g-3" novalidate>
    <?= csrf_field() ?><?= form_id_field() ?><input type="hidden" name="_back" value="modules/health_records/index.php">
    <div class="col-md-6"><label class="form-label required">Resident</label>
      <?php if ($id): ?>
        <?php $rn = db()->prepare('SELECT CONCAT(last_name, ", ", first_name) FROM residents WHERE id = ?'); $rn->execute([$h['resident_id']]); ?>
        <input class="form-control" value="<?= e((string)$rn->fetchColumn()) ?>" disabled>
      <?php else: ?><select class="form-select" name="resident_id" required><?= resident_options((int)$h['resident_id']) ?></select><?php endif; ?></div>
    <div class="col-md-3"><label class="form-label">Height (cm)</label><input type="number" step="0.1" min="30" max="250" class="form-control" name="height_cm" value="<?= e((string)$h['height_cm']) ?>"></div>
    <div class="col-md-3"><label class="form-label">Weight (kg)</label><input type="number" step="0.1" min="1" max="400" class="form-control" name="weight_kg" value="<?= e((string)$h['weight_kg']) ?>"></div>
    <div class="col-md-6"><label class="form-label">Allergies</label><textarea class="form-control" rows="2" name="allergies" maxlength="3000"><?= e($h['allergies']) ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Chronic conditions</label><textarea class="form-control" rows="2" name="chronic_conditions" maxlength="3000"><?= e($h['chronic_conditions']) ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Current medications</label><textarea class="form-control" rows="2" name="current_medications" maxlength="3000"><?= e($h['current_medications']) ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Medical history</label><textarea class="form-control" rows="2" name="medical_history" maxlength="3000"><?= e($h['medical_history']) ?></textarea></div>
    <div class="col-12"><label class="form-label">Health notes</label><textarea class="form-control" rows="3" name="health_notes" maxlength="3000"><?= e($h['health_notes']) ?></textarea></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save record</button><a class="btn btn-light" href="index.php">Cancel</a></div>
  </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
