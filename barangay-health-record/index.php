<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();
$one = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();

$stats = [
    ['bi-people',         $one('SELECT COUNT(*) FROM residents WHERE is_archived = 0'), 'Registered residents'],
    ['bi-stethoscope',    $one('SELECT COUNT(*) FROM consultations WHERE is_archived = 0 AND consult_date = CURDATE()'), 'Consultations today'],
    ['bi-calendar-check', $one("SELECT COUNT(*) FROM consultations WHERE is_archived = 0 AND follow_up_date >= CURDATE() AND status <> 'Closed'"), 'Upcoming follow-ups'],
    ['bi-shield-plus',    $one('SELECT COUNT(*) FROM immunizations WHERE is_archived = 0 AND next_dose_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)'), 'Vaccine doses due (14 days)'],
    ['bi-heart-pulse',    $one("SELECT COUNT(DISTINCT resident_id) FROM maternal_child_records WHERE is_archived = 0 AND record_type = 'Prenatal' AND expected_delivery >= CURDATE()"), 'Active prenatal cases'],
    ['bi-clipboard2-pulse', $one('SELECT COUNT(*) FROM consultations WHERE is_archived = 0 AND YEAR(consult_date) = YEAR(CURDATE()) AND MONTH(consult_date) = MONTH(CURDATE())'), 'Consultations this month'],
];

// Consultations per month (last 6 months)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[date('Y-m', strtotime("first day of -$i month"))] = 0;
}
$st = $pdo->prepare("SELECT DATE_FORMAT(consult_date, '%Y-%m') ym, COUNT(*) c FROM consultations WHERE is_archived = 0 AND consult_date >= ? GROUP BY ym");
$st->execute([date('Y-m-01', strtotime('first day of -5 month'))]);
foreach ($st->fetchAll() as $r) {
    if (isset($months[$r['ym']])) { $months[$r['ym']] = (int)$r['c']; }
}
$monthLabels = array_map(fn($m) => date('M Y', strtotime($m . '-01')), array_keys($months));

// Residents by age group
$ageRows = $pdo->query("SELECT CASE
    WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 5 THEN '0-4'
    WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 18 THEN '5-17'
    WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 60 THEN '18-59'
    ELSE '60+' END AS grp, COUNT(*) c FROM residents WHERE is_archived = 0 GROUP BY grp")->fetchAll(PDO::FETCH_KEY_PAIR);
$ageLabels = ['0-4', '5-17', '18-59', '60+'];
$ageData = array_map(fn($g) => (int)($ageRows[$g] ?? 0), $ageLabels);

// Top diagnoses
$diag = $pdo->query("SELECT diagnosis, COUNT(*) c FROM consultations WHERE is_archived = 0 AND diagnosis IS NOT NULL AND diagnosis <> '' GROUP BY diagnosis ORDER BY c DESC LIMIT 5")->fetchAll();

// Recent consultations
$recent = $pdo->query("SELECT c.id, c.consult_date, c.status, r.first_name, r.last_name, r.id AS rid FROM consultations c JOIN residents r ON r.id = c.resident_id WHERE c.is_archived = 0 ORDER BY c.consult_date DESC, c.id DESC LIMIT 6")->fetchAll();

// Recent activity: administrators see everyone, health workers see only their own actions
if (is_admin()) {
    $act = $pdo->query('SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 6')->fetchAll();
} else {
    $a = $pdo->prepare('SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id WHERE l.user_id = ? ORDER BY l.id DESC LIMIT 6');
    $a->execute([current_user()['id']]);
    $act = $a->fetchAll();
}

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
$useCharts = true;
$chartData = json_encode([
    'months' => ['labels' => $monthLabels, 'data' => array_values($months)],
    'age'    => ['labels' => $ageLabels, 'data' => $ageData],
], JSON_HEX_TAG | JSON_HEX_AMP);
$extraScript = '<script>window.BHRS_CHARTS = ' . $chartData . ';</script><script src="' . e(url('assets/js/dashboard.js')) . '"></script>';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-3">
  <?php foreach ($stats as [$icon, $val, $label]): ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card stat-card h-100">
      <div class="stat-icon"><i class="bi <?= e($icon) ?>"></i></div>
      <div><div class="stat-value"><?= number_format($val) ?></div><div class="stat-label"><?= e($label) ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card h-100"><div class="card-header">Consultations — last 6 months</div>
      <div class="card-body"><div class="chart-box"><canvas id="chartMonths"></canvas></div></div></div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-header">Residents by age group</div>
      <div class="card-body"><div class="chart-box"><canvas id="chartAge"></canvas></div></div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-header">Top diagnoses</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($diag as $d): ?>
          <li class="list-group-item d-flex justify-content-between"><span><?= e($d['diagnosis']) ?></span><span class="badge badge-soft"><?= (int)$d['c'] ?></span></li>
        <?php endforeach; ?>
        <?php if (!$diag): ?><li class="list-group-item empty">No diagnoses recorded yet.</li><?php endif; ?>
      </ul></div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-header d-flex justify-content-between">Recent consultations <a class="small fw-normal" href="<?= e(url('modules/consultations/index.php')) ?>">View all</a></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($recent as $r): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><a href="<?= e(url('modules/consultations/view.php?id=' . (int)$r['id'])) ?>"><?= e($r['last_name'] . ', ' . $r['first_name']) ?></a><br><small class="text-muted"><?= e(fmt_date($r['consult_date'])) ?></small></span>
            <span class="badge badge-soft"><?= e($r['status']) ?></span>
          </li>
        <?php endforeach; ?>
        <?php if (!$recent): ?><li class="list-group-item empty">No consultations yet.</li><?php endif; ?>
      </ul></div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-header">Recent activity</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($act as $a): ?>
          <li class="list-group-item small"><strong><?= e($a['full_name'] ?? 'System') ?></strong> — <?= e($a['action']) ?> <?= e($a['module']) ?><br>
            <span class="text-muted"><?= e(date('M d, h:i A', strtotime($a['created_at']))) ?></span></li>
        <?php endforeach; ?>
        <?php if (!$act): ?><li class="list-group-item empty">No activity recorded yet.</li><?php endif; ?>
      </ul></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
