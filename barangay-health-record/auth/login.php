<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

// If there are no users yet, send the person to the first-administrator setup.
$userCount = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount === 0) {
    redirect('auth/setup.php');
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        $error = 'Your session expired. Please try again.';
    } elseif (($_SESSION['lock_until'] ?? 0) > time()) {
        $error = 'Too many failed attempts. Please wait ' . ($_SESSION['lock_until'] - time()) . ' seconds.';
    } else {
        $username = post('username');
        $password = $_POST['password'] ?? '';
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        // Always run a hash check so response time does not reveal valid usernames.
        $hash = $u['password_hash'] ?? '$2y$10$usesomesillystringforsaltuQ0mKQe4v9v5Q8Zr0yW3u7kqYvC2e';
        $ok = is_string($password) && password_verify($password, $hash) && $u && $u['status'] === 'active';
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$u['id'], 'username' => $u['username'], 'full_name' => $u['full_name'], 'role' => $u['role']];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['attempts'], $_SESSION['lock_until']);
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
            }
            db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);
            log_activity('login', 'auth', (int)$u['id'], 'User logged in');
            redirect('index.php');
        }
        $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
        if ($_SESSION['attempts'] >= 5) {
            $_SESSION['lock_until'] = time() + 60;
            $_SESSION['attempts'] = 0;
        }
        $error = 'Invalid username or password.';
    }
}
$fl = flashes();
$cssBootstrap = vendor('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
$cssIcons     = vendor('bootstrap-icons/bootstrap-icons.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · <?= e(APP_SHORT) ?></title>
  <link rel="stylesheet" href="<?= e($cssBootstrap) ?>">
  <link rel="stylesheet" href="<?= e($cssIcons) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="login-wrap">
  <section class="login-art">
    <div class="brand-mark mb-4" style="width:54px;height:54px;background:rgba(255,255,255,.18)"><i class="bi bi-heart-pulse"></i></div>
    <h2>Barangay Health<br>Record System</h2>
    <p class="mb-4 opacity-75">Secure, organized health records for every resident of the barangay.</p>
    <ul class="list-unstyled">
      <li><i class="bi bi-check2-circle me-2"></i>Resident and consultation records</li>
      <li><i class="bi bi-check2-circle me-2"></i>Immunization and maternal &amp; child care</li>
      <li><i class="bi bi-check2-circle me-2"></i>Printable reports and CSV exports</li>
    </ul>
  </section>
  <section class="login-form">
    <div class="box">
      <h4 class="mb-1">Welcome back</h4>
      <p class="text-muted mb-4">Sign in to continue</p>
      <?php foreach ($fl as $f): ?><div class="alert alert-<?= e($f['type']) ?> py-2"><?= e($f['msg']) ?></div><?php endforeach; ?>
      <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
      <form method="post" class="needs-validation" novalidate autocomplete="off">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="username">Username</label>
          <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
            <input class="form-control" id="username" name="username" value="<?= e($username) ?>" required maxlength="50" autofocus></div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" required></div>
        </div>
        <button class="btn btn-primary w-100" type="submit">Sign in</button>
      </form>
      <p class="text-center text-muted small mt-4 mb-0">Authorized barangay health personnel only.</p>
    </div>
  </section>
</div>
<script src="<?= e(vendor('bootstrap/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
