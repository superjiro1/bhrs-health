<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$vaccineList = ['BCG', 'Hepatitis B', 'Pentavalent', 'Oral Polio (OPV)', 'Inactivated Polio (IPV)', 'Pneumococcal (PCV)', 'Rotavirus', 'MMR', 'Measles', 'Tetanus Toxoid', 'Influenza', 'COVID-19', 'HPV', 'Other'];
$id = (int)get('id', '0');
$m = ['resident_id' => (int)get('resident_id', '0'), 'vaccine_name' => '', 'dose_no' => '1', 'date_administered' => date('Y-m-d'), 'next_dose_date' => '', 'remarks' => ''];
if ($id) {
    $st = db()->prepare('SELECT * FROM immunizations WHERE id = ? AND is_archived = 0');
    $st->execute([$id]);
    $f = $st->fetch();
    if (!$f) { flash('danger', 'Immunization record not found.'); redirect('modules/immunizations/index.php'); }
    $m = array_merge($m, $f);
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_post();
    if (!$id) { $m['resident_id'] = (int)post('resident_id', '0'); }
    foreach (['vaccine_name', 'dose_no', 'date_administered', 'next_dose_date', 'remarks'] as $k) { $m[$k] = post($k); }
    $rs = db()->prepare('SELECT id FROM residents WHERE id = ? AND is_archived = 0');
    $rs->execute([$m['resident_id']]);
    if (!$rs->fetch()) { $errors[] = 'Please select a valid resident.'; }
    if ($m['vaccine_name'] === '' || mb_strlen($m['vaccine_name']) > 100) { $errors[] = 'Vaccine name is required (max 100 characters).'; }
    if (!ctype_digit($m['dose_no']) || (int)$m['dose_no'] < 1 || (int)$m['dose_no'] > 10) { $errors[] = 'Dose number must be between 1 and 10.'; }
    if (!valid_date($m['date_administered']) || $m['date_administered'] > date('Y-m-d')) { $errors[] = 'Date administered must be valid and not in the future.'; }
    if ($m['next_dose_date'] !== '' && (!valid_date($m['next_dose_date']) || $m['next_dose_date'] <= $m['date_administered'])) { $errors[] = 'Next dose date must be after the date administered.'; }
    if (mb_strlen($m['remarks']) > 255) { $errors[] = 'Remarks are too long (max 255).'; }
    if (!$errors) {
        $d = db()->prepare('SELECT id FROM immunizations WHERE resident_id = ? AND vaccine_name = ? AND dose_no = ? AND id <> ?');
        $d->execute([$m['resident_id'], $m['vaccine_name'], (int)$m['dose_no'], $id]);
        if ($d->fetch()) { $errors[] = 'This resident already has this dose of this vaccine recorded.'; }
    }
    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE immunizations SET vaccine_name=?, dose_no=?, date_administered=?, next_dose_date=?, remarks=? WHERE id=?')
                ->execute([$m['vaccine_name'], (int)$m['dose_no'], $m['date_administered'], null_if_empty($m['next_dose_date']), null_if_empty($m['remarks']), $id]);
            log_activity('update', 'immunizations', $id, 'Immunization updated');
        } else {
            db()->prepare('INSERT INTO immunizations (resident_id, vaccine_name, dose_no, date_administered, next_dose_date, remarks, administered_by) VALUES (?,?,?,?,?,?,?)')
                ->execute([$m['resident_id'], $m['vaccine_name'], (int)$m['dose_no'], $m['date_administered'], null_if_empty($m['next_dose_date']), null_if_empty($m['remarks']), current_user()['id']]);
            $id = (int)db()->lastInsertId();
            log_activity('create', 'immunizations', $id, 'Immunization recorded');
        }
        flash('success', 'Immunization saved.');
        redirect('modules/immunizations/index.php');
    }
}
$pageTitle = $id ? 'Edit Immunization' : 'Record Vaccination';
$activeNav = 'immun';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" class="needs-validation row g-3" novalidate>
    <?= csrf_field() ?><?= form_id_field() ?><input type="hidden" name="_back" value="modules/immunizations/index.php">
    <div class="col-md-6"><label class="form-label required">Resident</label>
      <?php if ($id): $rn = db()->prepare('SELECT CONCAT(last_name, ", ", first_name) FROM residents WHERE id = ?'); $rn->execute([$m['resident_id']]); ?>
        <input class="form-control" value="<?= e((string)$rn->fetchColumn()) ?>" disabled>
      <?php else: ?><select class="form-select" name="resident_id" required><?= resident_options((int)$m['resident_id']) ?></select><?php endif; ?></div>
    <div class="col-md-4"><label class="form-label required">Vaccine</label>
      <input class="form-control" name="vaccine_name" list="vaccines" value="<?= e($m['vaccine_name']) ?>" required maxlength="100">
      <datalist id="vaccines"><?php foreach ($vaccineList as $v): ?><option value="<?= e($v) ?>"><?php endforeach; ?></datalist></div>
    <div class="col-md-2"><label class="form-label required">Dose no.</label><input type="number" min="1" max="10" class="form-control" name="dose_no" value="<?= e((string)$m['dose_no']) ?>" required></div>
    <div class="col-md-4"><label class="form-label required">Date administered</label><input type="date" class="form-control" name="date_administered" value="<?= e($m['date_administered']) ?>" max="<?= date('Y-m-d') ?>" required></div>
    <div class="col-md-4"><label class="form-label">Next scheduled dose</label><input type="date" class="form-control" name="next_dose_date" value="<?= e((string)$m['next_dose_date']) ?>"></div>
    <div class="col-md-12"><label class="form-label">Remarks</label><input class="form-control" name="remarks" value="<?= e((string)$m['remarks']) ?>" maxlength="255"></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save</button><a class="btn btn-light" href="index.php">Cancel</a></div>
  </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
