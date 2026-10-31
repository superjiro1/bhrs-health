<?php
/**
 * First-administrator setup. Works ONLY while the users table is empty, so no default
 * account or password ever exists. After the first admin is created this page redirects away.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if ((int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    flash('info', 'Setup is already complete. Please sign in.');
    redirect('auth/login.php');
}

$errors = [];
$v = ['username' => '', 'full_name' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_post();
    $v['username']  = post('username');
    $v['full_name'] = post('full_name');
    $v['email']     = post('email');
    $pw  = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.]{4,50}$/', $v['username'])) { $errors[] = 'Username must be 4-50 characters (letters, numbers, underscore, dot).'; }
    if ($v['full_name'] === '' || mb_strlen($v['full_name']) > 100) { $errors[] = 'Full name is required (max 100 characters).'; }
    if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email address is not valid.'; }
    if (!is_string($pw) || strlen($pw) < 10 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) { $errors[] = 'Password must be at least 10 characters and contain letters and numbers.'; }
    if ($pw !== $pw2) { $errors[] = 'Passwords do not match.'; }

    if (!$errors) {
        $st = db()->prepare('INSERT INTO users (username, full_name, email, password_hash, role, status) VALUES (?,?,?,?, "admin", "active")');
        $st->execute([$v['username'], $v['full_name'], null_if_empty($v['email']), password_hash($pw, PASSWORD_DEFAULT)]);
        $id = (int)db()->lastInsertId();
        $_SESSION['user'] = null;
        unset($_SESSION['user']);
        log_activity('create', 'users', $id, 'First administrator account created');
        flash('success', 'Administrator account created. Please sign in.');
        redirect('auth/login.php');
    }
}
$cssBootstrap = vendor('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
$cssIcons     = vendor('bootstrap-icons/bootstrap-icons.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>First-time setup · <?= e(APP_SHORT) ?></title>
  <link rel="stylesheet" href="<?= e($cssBootstrap) ?>">
  <link rel="stylesheet" href="<?= e($cssIcons) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card p-4" style="width:100%;max-width:440px">
  <h5 class="mb-1">Create the first administrator</h5>
  <p class="text-muted small">This page is available only once, before any account exists.</p>
  <?php if ($errors): ?><div class="alert alert-danger py-2"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" class="needs-validation" novalidate autocomplete="off">
    <?= csrf_field() ?><?= form_id_field() ?><input type="hidden" name="_back" value="auth/setup.php">
    <div class="mb-3"><label class="form-label required" for="full_name">Full name</label>
      <input class="form-control" id="full_name" name="full_name" value="<?= e($v['full_name']) ?>" required maxlength="100"></div>
    <div class="mb-3"><label class="form-label required" for="username">Username</label>
      <input class="form-control" id="username" name="username" value="<?= e($v['username']) ?>" required pattern="[A-Za-z0-9_.]{4,50}" maxlength="50"></div>
    <div class="mb-3"><label class="form-label" for="email">Email (optional)</label>
      <input type="email" class="form-control" id="email" name="email" value="<?= e($v['email']) ?>" maxlength="120"></div>
    <div class="mb-3"><label class="form-label required" for="password">Password</label>
      <input type="password" class="form-control" id="password" name="password" required minlength="10">
      <div class="form-text">At least 10 characters, with letters and numbers.</div></div>
    <div class="mb-4"><label class="form-label required" for="password2">Confirm password</label>
      <input type="password" class="form-control" id="password2" name="password2" required minlength="10"></div>
    <button class="btn btn-primary w-100" type="submit">Create administrator</button>
  </form>
</div>
<script src="<?= e(vendor('bootstrap/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
