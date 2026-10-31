<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)get('id', '0');
$c = ['resident_id' => (int)get('resident_id', '0'), 'consult_date' => date('Y-m-d'), 'symptoms' => '', 'blood_pressure' => '', 'temperature_c' => '', 'weight_kg' => '',
      'assessment' => '', 'diagnosis' => '', 'treatment' => '', 'follow_up_date' => '', 'status' => 'Open'];
if ($id) {
    $st = db()->prepare('SELECT * FROM consultations WHERE id = ? AND is_archived = 0');
    $st->execute([$id]);
    $f = $st->fetch();
    if (!$f) { flash('danger', 'Consultation not found.'); redirect('modules/consultations/index.php'); }
    $c = array_merge($c, $f);
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_post();
    if (!$id) { $c['resident_id'] = (int)post('resident_id', '0'); }
    foreach (['consult_date', 'symptoms', 'blood_pressure', 'temperature_c', 'weight_kg', 'assessment', 'diagnosis', 'treatment', 'follow_up_date', 'status'] as $k) { $c[$k] = post($k); }

    $rs = db()->prepare('SELECT id FROM residents WHERE id = ? AND is_archived = 0');
    $rs->execute([$c['resident_id']]);
    if (!$rs->fetch()) { $errors[] = 'Please select a valid resident.'; }
    if (!valid_date($c['consult_date']) || $c['consult_date'] > date('Y-m-d')) { $errors[] = 'Consultation date must be valid and not in the future.'; }
    if ($c['symptoms'] === '') { $errors[] = 'Symptoms are required.'; }
    if ($c['blood_pressure'] !== '' && !preg_match('/^\d{2,3}\/\d{2,3}$/', $c['blood_pressure'])) { $errors[] = 'Blood pressure must look like 120/80.'; }
    if ($c['temperature_c'] !== '' && (!is_numeric($c['temperature_c']) || $c['temperature_c'] < 30 || $c['temperature_c'] > 45)) { $errors[] = 'Temperature must be between 30 and 45 °C.'; }
    if ($c['weight_kg'] !== '' && (!is_numeric($c['weight_kg']) || $c['weight_kg'] < 1 || $c['weight_kg'] > 400)) { $errors[] = 'Weight must be between 1 and 400 kg.'; }
    if ($c['follow_up_date'] !== '' && (!valid_date($c['follow_up_date']) || $c['follow_up_date'] < $c['consult_date'])) { $errors[] = 'Follow-up date must be valid and on/after the consultation date.'; }
    if (!in_array($c['status'], ['Open', 'Follow-up', 'Closed'], true)) { $errors[] = 'Invalid status.'; }
    if (mb_strlen($c['diagnosis']) > 255) { $errors[] = 'Diagnosis is too long (max 255).'; }
    foreach (['symptoms', 'assessment', 'treatment'] as $k) { if (mb_strlen($c[$k]) > 3000) { $errors[] = 'Text fields are limited to 3000 characters.'; break; } }

    if (!$errors) {
        $vals = [$c['consult_date'], $c['symptoms'], null_if_empty($c['blood_pressure']), null_if_empty((string)$c['temperature_c']), null_if_empty((string)$c['weight_kg']),
                 null_if_empty($c['assessment']), null_if_empty($c['diagnosis']), null_if_empty($c['treatment']), null_if_empty($c['follow_up_date']), $c['status']];
        if ($id) {
            $vals[] = $id;
            db()->prepare('UPDATE consultations SET consult_date=?, symptoms=?, blood_pressure=?, temperature_c=?, weight_kg=?, assessment=?, diagnosis=?, treatment=?, follow_up_date=?, status=? WHERE id=?')->execute($vals);
            log_activity('update', 'consultations', $id, 'Consultation updated');
        } else {
            array_unshift($vals, $c['resident_id']);
            $vals[] = current_user()['id'];
            db()->prepare('INSERT INTO consultations (resident_id, consult_date, symptoms, blood_pressure, temperature_c, weight_kg, assessment, diagnosis, treatment, follow_up_date, status, attended_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute($vals);
            $id = (int)db()->lastInsertId();
            log_activity('create', 'consultations', $id, 'Consultation recorded');
        }
        flash('success', 'Consultation saved.');
        redirect('modules/consultations/view.php?id=' . $id);
    }
}
$pageTitle = $id ? 'Edit Consultation' : 'New Consultation';
$activeNav = 'consult';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" class="needs-validation row g-3" novalidate>
    <?= csrf_field() ?><?= form_id_field() ?><input type="hidden" name="_back" value="modules/consultations/index.php">
    <div class="col-md-6"><label class="form-label required">Resident</label>
      <?php if ($id): $rn = db()->prepare('SELECT CONCAT(last_name, ", ", first_name) FROM residents WHERE id = ?'); $rn->execute([$c['resident_id']]); ?>
        <input class="form-control" value="<?= e((string)$rn->fetchColumn()) ?>" disabled>
      <?php else: ?><select class="form-select" name="resident_id" required><?= resident_options((int)$c['resident_id']) ?></select><?php endif; ?></div>
    <div class="col-md-3"><label class="form-label required">Consultation date</label><input type="date" class="form-control" name="consult_date" value="<?= e($c['consult_date']) ?>" max="<?= date('Y-m-d') ?>" required></div>
    <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><?= options(['Open', 'Follow-up', 'Closed'], $c['status']) ?></select></div>
    <div class="col-md-4"><label class="form-label">Blood pressure</label><input class="form-control" name="blood_pressure" value="<?= e((string)$c['blood_pressure']) ?>" placeholder="120/80" pattern="\d{2,3}/\d{2,3}" maxlength="7"></div>
    <div class="col-md-4"><label class="form-label">Temperature (°C)</label><input type="number" step="0.1" min="30" max="45" class="form-control" name="temperature_c" value="<?= e((string)$c['temperature_c']) ?>"></div>
    <div class="col-md-4"><label class="form-label">Weight (kg)</label><input type="number" step="0.1" min="1" max="400" class="form-control" name="weight_kg" value="<?= e((string)$c['weight_kg']) ?>"></div>
    <div class="col-12"><label class="form-label required">Symptoms</label><textarea class="form-control" rows="2" name="symptoms" required maxlength="3000"><?= e($c['symptoms']) ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Assessment</label><textarea class="form-control" rows="2" name="assessment" maxlength="3000"><?= e((string)$c['assessment']) ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Diagnosis</label><input class="form-control" name="diagnosis" value="<?= e((string)$c['diagnosis']) ?>" maxlength="255"></div>
    <div class="col-md-8"><label class="form-label">Treatment</label><textarea class="form-control" rows="2" name="treatment" maxlength="3000"><?= e((string)$c['treatment']) ?></textarea></div>
    <div class="col-md-4"><label class="form-label">Follow-up date</label><input type="date" class="form-control" name="follow_up_date" value="<?= e((string)$c['follow_up_date']) ?>"></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save consultation</button><a class="btn btn-light" href="index.php">Cancel</a></div>
  </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
