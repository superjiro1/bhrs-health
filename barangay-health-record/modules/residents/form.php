<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)get('id', '0');
$r = ['first_name' => '', 'middle_name' => '', 'last_name' => '', 'sex' => '', 'birthdate' => '', 'civil_status' => 'Single',
      'contact_no' => '', 'purok' => '', 'address' => '', 'blood_type' => 'Unknown', 'philhealth_no' => '', 'guardian_name' => ''];
if ($id) {
    $st = db()->prepare('SELECT * FROM residents WHERE id = ? AND is_archived = 0');
    $st->execute([$id]);
    $found = $st->fetch();
    if (!$found) { flash('danger', 'Resident not found or archived.'); redirect('modules/residents/index.php'); }
    $r = $found;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_post();
    foreach (array_keys($r) as $k) { $r[$k] = post($k, (string)($r[$k] ?? '')); }
    if ($r['first_name'] === '' || mb_strlen($r['first_name']) > 60) { $errors[] = 'First name is required (max 60 characters).'; }
    if ($r['last_name'] === ''  || mb_strlen($r['last_name']) > 60)  { $errors[] = 'Last name is required (max 60 characters).'; }
    if (mb_strlen($r['middle_name']) > 60) { $errors[] = 'Middle name is too long.'; }
    if (!in_array($r['sex'], ['Male', 'Female'], true)) { $errors[] = 'Please select sex.'; }
    if (!valid_date($r['birthdate']) || $r['birthdate'] > date('Y-m-d')) { $errors[] = 'Enter a valid birthdate that is not in the future.'; }
    if (!in_array($r['civil_status'], ['Single', 'Married', 'Widowed', 'Separated'], true)) { $errors[] = 'Invalid civil status.'; }
    if (!in_array($r['blood_type'], ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'], true)) { $errors[] = 'Invalid blood type.'; }
    if ($r['purok'] === '' || mb_strlen($r['purok']) > 60) { $errors[] = 'Purok is required.'; }
    if ($r['contact_no'] !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $r['contact_no'])) { $errors[] = 'Contact number format is invalid.'; }
    if (mb_strlen($r['address']) > 150 || mb_strlen($r['guardian_name']) > 120 || mb_strlen($r['philhealth_no']) > 30) { $errors[] = 'One of the text fields is too long.'; }

    if (!$errors) {
        // Prevent duplicate residents (same name + birthdate)
        $d = db()->prepare('SELECT id FROM residents WHERE first_name = ? AND last_name = ? AND birthdate = ? AND id <> ?');
        $d->execute([$r['first_name'], $r['last_name'], $r['birthdate'], $id]);
        if ($d->fetch()) { $errors[] = 'A resident with the same name and birthdate already exists.'; }
    }
    if (!$errors) {
        $vals = [$r['first_name'], null_if_empty($r['middle_name']), $r['last_name'], $r['sex'], $r['birthdate'], $r['civil_status'],
                 null_if_empty($r['contact_no']), $r['purok'], null_if_empty($r['address']), $r['blood_type'],
                 null_if_empty($r['philhealth_no']), null_if_empty($r['guardian_name'])];
        if ($id) {
            $vals[] = $id;
            db()->prepare('UPDATE residents SET first_name=?, middle_name=?, last_name=?, sex=?, birthdate=?, civil_status=?, contact_no=?, purok=?, address=?, blood_type=?, philhealth_no=?, guardian_name=? WHERE id=?')->execute($vals);
            log_activity('update', 'residents', $id, 'Resident profile updated');
            flash('success', 'Resident updated.');
        } else {
            $code = 'R-' . str_pad((string)((int)db()->query("SELECT COALESCE(MAX(CAST(SUBSTRING(resident_code, 3) AS UNSIGNED)), 0) FROM residents")->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
            $vals = array_merge([$code], $vals, [current_user()['id']]);
            db()->prepare('INSERT INTO residents (resident_code, first_name, middle_name, last_name, sex, birthdate, civil_status, contact_no, purok, address, blood_type, philhealth_no, guardian_name, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($vals);
            $id = (int)db()->lastInsertId();
            log_activity('create', 'residents', $id, 'Resident registered');
            flash('success', 'Resident registered.');
        }
        redirect('modules/residents/view.php?id=' . $id);
    }
}
$pageTitle = $id ? 'Edit Resident' : 'Register Resident';
$activeNav = 'residents';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" class="needs-validation row g-3" novalidate>
    <?= csrf_field() ?><?= form_id_field() ?><input type="hidden" name="_back" value="modules/residents/index.php">
    <div class="col-md-4"><label class="form-label required">First name</label><input class="form-control" name="first_name" value="<?= e($r['first_name']) ?>" required maxlength="60"></div>
    <div class="col-md-4"><label class="form-label">Middle name</label><input class="form-control" name="middle_name" value="<?= e($r['middle_name']) ?>" maxlength="60"></div>
    <div class="col-md-4"><label class="form-label required">Last name</label><input class="form-control" name="last_name" value="<?= e($r['last_name']) ?>" required maxlength="60"></div>
    <div class="col-md-3"><label class="form-label required">Sex</label><select class="form-select" name="sex" required><option value="">Select</option><?= options(['Male' => 'Male', 'Female' => 'Female'], $r['sex']) ?></select></div>
    <div class="col-md-3"><label class="form-label required">Birthdate</label><input type="date" class="form-control" name="birthdate" value="<?= e($r['birthdate']) ?>" max="<?= date('Y-m-d') ?>" required></div>
    <div class="col-md-3"><label class="form-label">Civil status</label><select class="form-select" name="civil_status"><?= options(['Single', 'Married', 'Widowed', 'Separated'], $r['civil_status']) ?></select></div>
    <div class="col-md-3"><label class="form-label">Blood type</label><select class="form-select" name="blood_type"><?= options(['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'], $r['blood_type']) ?></select></div>
    <div class="col-md-3"><label class="form-label required">Purok</label><input class="form-control" name="purok" value="<?= e($r['purok']) ?>" required maxlength="60" placeholder="e.g. Purok 1"></div>
    <div class="col-md-5"><label class="form-label">Address</label><input class="form-control" name="address" value="<?= e($r['address']) ?>" maxlength="150"></div>
    <div class="col-md-4"><label class="form-label">Contact number</label><input class="form-control" name="contact_no" value="<?= e($r['contact_no']) ?>" maxlength="20" pattern="[0-9+\-\s]{7,20}"></div>
    <div class="col-md-6"><label class="form-label">PhilHealth number</label><input class="form-control" name="philhealth_no" value="<?= e($r['philhealth_no']) ?>" maxlength="30"></div>
    <div class="col-md-6"><label class="form-label">Guardian / parent (for minors)</label><input class="form-control" name="guardian_name" value="<?= e($r['guardian_name']) ?>" maxlength="120"></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save resident</button>
      <a class="btn btn-light" href="<?= $id ? 'view.php?id=' . $id : 'index.php' ?>">Cancel</a></div>
  </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
