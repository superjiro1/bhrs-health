<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)get('id', '0');
$keys = ['record_type', 'visit_date', 'lmp_date', 'expected_delivery', 'gestational_weeks', 'blood_pressure', 'weight_kg', 'height_cm', 'head_circ_cm', 'nutrition_status', 'risk_notes', 'notes', 'next_visit_date'];
$m = ['resident_id' => (int)get('resident_id', '0'), 'record_type' => get('type', 'Prenatal'), 'visit_date' => date('Y-m-d'), 'lmp_date' => '', 'expected_delivery' => '', 'gestational_weeks' => '',
      'blood_pressure' => '', 'weight_kg' => '', 'height_cm' => '', 'head_circ_cm' => '', 'nutrition_status' => '', 'risk_notes' => '', 'notes' => '', 'next_visit_date' => ''];
if ($id) {
    $st = db()->prepare('SELECT * FROM maternal_child_records WHERE id = ? AND is_archived = 0');
    $st->execute([$id]);
    $f = $st->fetch();
    if (!$f) { flash('danger', 'Record not found.'); redirect('modules/maternal_child/index.php'); }
    $m = array_merge($m, $f);
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_post();
    if (!$id) { $m['resident_id'] = (int)post('resident_id', '0'); }
    foreach ($keys as $k) { $m[$k] = post($k); }
    $type = $m['record_type'];

    $rs = db()->prepare('SELECT sex, birthdate FROM residents WHERE id = ? AND is_archived = 0');
    $rs->execute([$m['resident_id']]);
    $res = $rs->fetch();
    if (!in_array($type, ['Prenatal', 'Postnatal', 'Child Growth'], true)) { $errors[] = 'Please choose a record type.'; }
    if (!$res) { $errors[] = 'Please select a valid resident.'; }
    elseif (in_array($type, ['Prenatal', 'Postnatal'], true) && $res['sex'] !== 'Female') { $errors[] = 'Prenatal and postnatal records can only be created for female residents.'; }
    elseif ($type === 'Child Growth' && (new DateTime($res['birthdate']))->diff(new DateTime('today'))->y >= 6) { $errors[] = 'Child growth monitoring is for children under 6 years old.'; }
    if (!valid_date($m['visit_date']) || $m['visit_date'] > date('Y-m-d')) { $errors[] = 'Visit date must be valid and not in the future.'; }
    if ($m['next_visit_date'] !== '' && (!valid_date($m['next_visit_date']) || $m['next_visit_date'] <= $m['visit_date'])) { $errors[] = 'Next visit must be after the visit date.'; }
    if ($m['blood_pressure'] !== '' && !preg_match('/^\d{2,3}\/\d{2,3}$/', $m['blood_pressure'])) { $errors[] = 'Blood pressure must look like 110/70.'; }
    foreach (['weight_kg' => [1, 400, 'Weight'], 'height_cm' => [20, 250, 'Height'], 'head_circ_cm' => [20, 70, 'Head circumference']] as $k => [$lo, $hi, $lbl]) {
        if ($m[$k] !== '' && (!is_numeric($m[$k]) || $m[$k] < $lo || $m[$k] > $hi)) { $errors[] = "$lbl must be between $lo and $hi."; }
    }
    if ($type === 'Prenatal') {
        if ($m['lmp_date'] !== '' && (!valid_date($m['lmp_date']) || $m['lmp_date'] > $m['visit_date'])) { $errors[] = 'LMP date must be valid and on/before the visit date.'; }
        if ($m['expected_delivery'] !== '' && !valid_date($m['expected_delivery'])) { $errors[] = 'Expected delivery date is not valid.'; }
        if ($m['gestational_weeks'] !== '' && (!ctype_digit($m['gestational_weeks']) || (int)$m['gestational_weeks'] > 45)) { $errors[] = 'Gestational age must be 0-45 weeks.'; }
    }
    if ($type === 'Child Growth') {
        if ($m['weight_kg'] === '' || $m['height_cm'] === '') { $errors[] = 'Weight and height are required for child growth monitoring.'; }
        if ($m['nutrition_status'] !== '' && !in_array($m['nutrition_status'], ['Normal', 'Underweight', 'Severely Underweight', 'Overweight', 'Stunted'], true)) { $errors[] = 'Invalid nutrition status.'; }
    }
    if (mb_strlen($m['risk_notes']) > 3000 || mb_strlen($m['notes']) > 3000) { $errors[] = 'Notes are limited to 3000 characters.'; }

    if (!$errors) {
        $isPre = $type === 'Prenatal'; $isChild = $type === 'Child Growth';
        $vals = [$type, $m['visit_date'],
            $isPre ? null_if_empty($m['lmp_date']) : null, $isPre ? null_if_empty($m['expected_delivery']) : null, $isPre ? null_if_empty($m['gestational_weeks']) : null,
            $isChild ? null : null_if_empty($m['blood_pressure']), null_if_empty((string)$m['weight_kg']), $isChild ? null_if_empty((string)$m['height_cm']) : null, $isChild ? null_if_empty((string)$m['head_circ_cm']) : null,
            $isChild ? null_if_empty($m['nutrition_status']) : null, $isPre ? null_if_empty($m['risk_notes']) : null, null_if_empty($m['notes']), null_if_empty($m['next_visit_date'])];
        if ($id) {
            $vals[] = $id;
            db()->prepare('UPDATE maternal_child_records SET record_type=?, visit_date=?, lmp_date=?, expected_delivery=?, gestational_weeks=?, blood_pressure=?, weight_kg=?, height_cm=?, head_circ_cm=?, nutrition_status=?, risk_notes=?, notes=?, next_visit_date=? WHERE id=?')->execute($vals);
            log_activity('update', 'maternal_child', $id, $type . ' record updated');
        } else {
            array_unshift($vals, $m['resident_id']);
            $vals[] = current_user()['id'];
            db()->prepare('INSERT INTO maternal_child_records (resident_id, record_type, visit_date, lmp_date, expected_delivery, gestational_weeks, blood_pressure, weight_kg, height_cm, head_circ_cm, nutrition_status, risk_notes, notes, next_visit_date, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($vals);
            $id = (int)db()->lastInsertId();
            log_activity('create', 'maternal_child', $id, $type . ' record created');
        }
        flash('success', 'Record saved.');
        redirect('modules/maternal_child/view.php?id=' . $id);
    }
}
$pageTitle = $id ? 'Edit Maternal / Child Record' : 'New Maternal / Child Record';
$activeNav = 'maternal';
$extraScript = '<script src="' . e(url('assets/js/maternal.js')) . '"></script>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" class="needs-validation row g-3" novalidate>
    <?= csrf_field() ?><?= form_id_field() ?><input type="hidden" name="_back" value="modules/maternal_child/index.php">
    <div class="col-md-5"><label class="form-label required">Resident</label>
      <?php if ($id): $rn = db()->prepare('SELECT CONCAT(last_name, ", ", first_name) FROM residents WHERE id = ?'); $rn->execute([$m['resident_id']]); ?>
        <input class="form-control" value="<?= e((string)$rn->fetchColumn()) ?>" disabled>
      <?php else: ?><select class="form-select" name="resident_id" required><?= resident_options((int)$m['resident_id']) ?></select><?php endif; ?></div>
    <div class="col-md-4"><label class="form-label required">Record type</label>
      <select class="form-select" name="record_type" id="record_type" required><?= options(['Prenatal', 'Postnatal', 'Child Growth'], $m['record_type']) ?></select></div>
    <div class="col-md-3"><label class="form-label required">Visit date</label><input type="date" class="form-control" name="visit_date" value="<?= e($m['visit_date']) ?>" max="<?= date('Y-m-d') ?>" required></div>

    <div class="col-md-4 grp-pre"><label class="form-label">Last menstrual period</label><input type="date" class="form-control" name="lmp_date" value="<?= e((string)$m['lmp_date']) ?>"></div>
    <div class="col-md-4 grp-pre"><label class="form-label">Expected delivery</label><input type="date" class="form-control" name="expected_delivery" value="<?= e((string)$m['expected_delivery']) ?>"></div>
    <div class="col-md-4 grp-pre"><label class="form-label">Gestational age (weeks)</label><input type="number" min="0" max="45" class="form-control" name="gestational_weeks" value="<?= e((string)$m['gestational_weeks']) ?>"></div>

    <div class="col-md-3 grp-mom"><label class="form-label">Blood pressure</label><input class="form-control" name="blood_pressure" value="<?= e((string)$m['blood_pressure']) ?>" placeholder="110/70" pattern="\d{2,3}/\d{2,3}" maxlength="7"></div>
    <div class="col-md-3"><label class="form-label">Weight (kg)</label><input type="number" step="0.01" class="form-control" name="weight_kg" value="<?= e((string)$m['weight_kg']) ?>"></div>
    <div class="col-md-3 grp-child"><label class="form-label">Height / length (cm)</label><input type="number" step="0.1" class="form-control" name="height_cm" value="<?= e((string)$m['height_cm']) ?>"></div>
    <div class="col-md-3 grp-child"><label class="form-label">Head circumference (cm)</label><input type="number" step="0.1" class="form-control" name="head_circ_cm" value="<?= e((string)$m['head_circ_cm']) ?>"></div>
    <div class="col-md-4 grp-child"><label class="form-label">Nutrition status</label><select class="form-select" name="nutrition_status"><option value="">—</option><?= options(['Normal', 'Underweight', 'Severely Underweight', 'Overweight', 'Stunted'], (string)$m['nutrition_status']) ?></select></div>

    <div class="col-12 grp-pre"><label class="form-label">Risk notes</label><textarea class="form-control" rows="2" name="risk_notes" maxlength="3000"><?= e((string)$m['risk_notes']) ?></textarea></div>
    <div class="col-md-8"><label class="form-label">Notes</label><textarea class="form-control" rows="2" name="notes" maxlength="3000"><?= e((string)$m['notes']) ?></textarea></div>
    <div class="col-md-4"><label class="form-label">Next visit</label><input type="date" class="form-control" name="next_visit_date" value="<?= e((string)$m['next_visit_date']) ?>"></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save record</button><a class="btn btn-light" href="index.php">Cancel</a></div>
  </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
