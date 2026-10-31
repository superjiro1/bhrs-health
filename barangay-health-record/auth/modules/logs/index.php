<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$uid = get('user'); $mod = get('module'); $from = get('from'); $to = get('to');
$where = ['1=1']; $args = [];
if (ctype_digit($uid)) { $where[] = 'l.user_id = ?'; $args[] = (int)$uid; }
if ($mod !== '') { $where[] = 'l.module = ?'; $args[] = $mod; }
if (valid_date($from)) { $where[] = 'l.created_at >= ?'; $args[] = $from . ' 00:00:00'; }
if (valid_date($to))   { $where[] = 'l.created_at <= ?'; $args[] = $to . ' 23:59:59'; }
$w = implode(' AND ', $where);
$cnt = db()->prepare("SELECT COUNT(*) FROM activity_logs l WHERE $w");
$cnt->execute($args);
$p = paginate((int)$cnt->fetchColumn(), (int)get('page', '1'), 20);
$st = db()->prepare("SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id WHERE $w ORDER BY l.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}");
$st->execute($args);
$rows = $st->fetchAll();
$users = db()->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll(PDO::FETCH_KEY_PAIR);
$mods = db()->query('SELECT DISTINCT module FROM activity_logs ORDER BY module')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Activity Logs';
$activeNav = 'logs';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="get">
    <div class="col-6 col-md-3"><select class="form-select form-select-sm" name="user"><option value="">All users</option><?= options($users, $uid) ?></select></div>
    <div class="col-6 col-md-2"><select class="form-select form-select-sm" name="module"><option value="">All modules</option><?= options(array_combine($mods, $mods), $mod) ?></select></div>
    <div class="col-6 col-md-2"><input type="date" class="form-control form-control-sm" name="from" value="<?= e($from) ?>"></div>
    <div class="col-6 col-md-2"><input type="date" class="form-control form-control-sm" name="to" value="<?= e($to) ?>"></div>
    <div class="col-12 col-md-auto"><button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button> <a class="btn btn-sm btn-light" href="index.php">Reset</a></div>
  </form>
  <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Module</th><th>Ref</th><th>Description</th><th>IP</th></tr></thead><tbody>
    <?php foreach ($rows as $l): ?><tr>
      <td class="text-nowrap"><?= e(date('M d, Y h:i A', strtotime($l['created_at']))) ?></td><td><?= e($l['full_name'] ?? 'System') ?></td>
      <td><span class="badge badge-soft"><?= e($l['action']) ?></span></td><td><?= e($l['module']) ?></td><td><?= $l['target_id'] ? '#' . (int)$l['target_id'] : '—' ?></td>
      <td><?= e($l['description']) ?></td><td class="text-muted small"><?= e($l['ip_address']) ?></td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7"><div class="empty"><i class="bi bi-journal-text"></i>No activity found.</div></td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3"><small class="text-muted"><?= (int)$p['total'] ?> entr<?= $p['total'] === 1 ? 'y' : 'ies' ?></small><?= pager($p) ?></div>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
