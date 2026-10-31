<?php
$nav = [
    ['dashboard',   'index.php',                          'bi-speedometer2',  'Dashboard', false],
    ['residents',   'modules/residents/index.php',        'bi-people',        'Residents', false],
    ['health',      'modules/health_records/index.php',   'bi-clipboard2-pulse','Health Records', false],
    ['consult',     'modules/consultations/index.php',    'bi-stethoscope',   'Consultations', false],
    ['immun',       'modules/immunizations/index.php',    'bi-shield-plus',   'Immunizations', false],
    ['maternal',    'modules/maternal_child/index.php',   'bi-heart-pulse',   'Maternal & Child', false],
    ['reports',     'reports/index.php',                  'bi-file-earmark-bar-graph', 'Reports', false],
    ['users',       'modules/users/index.php',            'bi-person-gear',   'User Management', true],
    ['logs',        'modules/logs/index.php',             'bi-journal-text',  'Activity Logs', true],
];
?>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <span class="brand-mark"><i class="bi bi-plus-lg"></i></span>
    <span><strong><?= e(APP_SHORT) ?></strong><small>Barangay Health</small></span>
  </a>
  <nav class="nav flex-column">
    <?php foreach ($nav as [$key, $href, $icon, $label, $adminOnly]): ?>
      <?php if ($adminOnly && !is_admin()) { continue; } ?>
      <a class="nav-link<?= $activeNav === $key ? ' active' : '' ?>" href="<?= e(url($href)) ?>">
        <i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
