<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$id = (int)get('id', '0');
$u = ['username' => '', 'full_name' => '', 'email' => '', 'role' => 'health_worker', 'status' => 'active'];
if ($id) {
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $f = $st->fetch();
    if (!$f) { flash('danger', 'User not found.'); redirect('modules/users/index.php'); }
    $u = $f;
}
$isSelf = $id === (int)current_user()['id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_post();
    foreach (['username', 'full_name', 'email', 'role'] as $k) { $u[$k] = post($k); }
    $pw = $_POST['password'] ?? '';
    if ($isSelf) { $u['role'] = 'admin'; }   // administrators cannot demote themselves
    if (!$id && !preg_match('/^[A-Za-z0-9_.]{4,50}$/', $u['username'])) { $errors[] = 'Username must be 4-50 characters (letters, numbers, underscore, dot).'; }
    if ($u['full_name'] === '' || mb_strlen($u['full_name']) > 100) { $errors[] = 'Full name is required (max 100 characters).'; }
    if ($u['email'] !== '' && !filter_var($u['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email address is not valid.'; }
    if (!in_array($u['role'], ['admin', 'health_worker'], true)) { $errors[] = 'Invalid role.'; }
    if (!$id || $pw !== '') {
        if (!is_string($pw) || strlen($pw) < 10 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) { $errors[] = 'Password must be at least 10 characters and contain letters and numbers.'; }
    }
    if (!$errors && !$id) {
        $d = db()->prepare('SELECT id FROM users WHERE username = ?');
        $d->execute([$u['username']]);
        if ($d->fetch()) { $errors[] = 'That username is already taken.'; }
    }
    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE users SET full_name=?, email=?, role=? WHERE id=?')->execute([$u['full_name'], null_if_empty($u['email']), $u['role'], $id]);
            if ($pw !== '') { db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]); }
            log_activity('update', 'users', $id, 'User account updated' . ($pw !== '' ? ' (password reset)' : ''));
        } else {
            db()->prepare('INSERT INTO users (username, full_name, email, password_hash, role) VALUES (?,?,?,?,?)')
                ->execute([$u['username'], $u['full_name'], null_if_empty($u['email']), password_hash($pw, PASSWORD_DEFAULT), $u['role']]);
            $id = (int)db()->lastInsertId();
            log_activity('create', 'users', $id, 'User account created');
        }
        flash('success', 'User saved.');
        redirect('modules/users/index.php');
    }
}
$pageTitle = $id ? 'Edit User' : 'Add User';
$activeNav = 'users';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card" style="max-width:640px"><div class="card-body">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" class="needs-validation row g-3" novalidate autocomplete="off">
    <?= csrf_field() ?><?= form_id_field() ?><input type="hidden" name="_back" value="modules/users/index.php">
    <div class="col-md-6"><label class="form-label required">Full name</label><input class="form-control" name="full_name" value="<?= e($u['full_name']) ?>" required maxlength="100"></div>
    <div class="col-md-6"><label class="form-label required">Username</label><input class="form-control" name="username" value="<?= e($u['username']) ?>" <?= $id ? 'disabled' : 'required pattern="[A-Za-z0-9_.]{4,50}"' ?> maxlength="50"></div>
    <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e((string)$u['email']) ?>" maxlength="120"></div>
    <div class="col-md-6"><label class="form-label required">Role</label>
      <select class="form-select" name="role" <?= $isSelf ? 'disabled' : '' ?>><?= options(['health_worker' => 'Health Worker', 'admin' => 'Administrator'], $u['role']) ?></select>
      <?php if ($isSelf): ?><div class="form-text">You cannot change your own role.</div><?php endif; ?></div>
    <div class="col-12"><label class="form-label <?= $id ? '' : 'required' ?>"><?= $id ? 'New password (leave blank to keep current)' : 'Password' ?></label>
      <input type="password" class="form-control" name="password" minlength="10" <?= $id ? '' : 'required' ?> autocomplete="new-password">
      <div class="form-text">At least 10 characters, with letters and numbers.</div></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save user</button><a class="btn btn-light" href="index.php">Cancel</a></div>
  </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
