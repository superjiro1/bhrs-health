<?php
/** Expects $pageTitle (string) and optionally $activeNav (string). Requires bootstrap.php loaded. */
$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
$cssBootstrap = vendor('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
$cssIcons     = vendor('bootstrap-icons/bootstrap-icons.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · <?= e(APP_SHORT) ?></title>
  <link rel="stylesheet" href="<?= e($cssBootstrap) ?>">
  <link rel="stylesheet" href="<?= e($cssIcons) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app-shell">
  <?php require __DIR__ . '/sidebar.php'; ?>
  <div class="app-main">
    <header class="topbar">
      <button class="btn btn-light btn-sm d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
      <h1 class="page-title"><?= e($pageTitle) ?></h1>
      <div class="ms-auto d-flex align-items-center gap-2">
        <div class="text-end d-none d-sm-block lh-sm">
          <div class="fw-semibold small"><?= e(current_user()['full_name'] ?? '') ?></div>
          <div class="text-muted small"><?= is_admin() ? 'Administrator' : 'Health Worker' ?></div>
        </div>
        <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="m-0">
          <?= csrf_field() ?>
          <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Logout</span></button>
        </form>
      </div>
    </header>
    <main class="content">
      <?php foreach (flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
          <?= e($f['msg']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endforeach; ?>
