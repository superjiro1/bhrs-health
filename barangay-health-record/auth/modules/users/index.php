<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$rows = db()->query('SELECT * FROM users ORDER BY role, full_name')->fetchAll();
$pageTitle = 'User Management';
$activeNav = 'users';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between mb-3"><small class="text-muted"><?= count($rows) ?> account(s). Accounts are deactivated, never deleted, so activity history stays intact.</small>
    <a class="btn btn-sm btn-primary" href="form.php"><i class="bi bi-plus-lg"></i> Add user</a></div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Last login</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $u): $self = (int)$u['id'] === (int)current_user()['id']; ?><tr>
      <td class="fw-semibold"><?= e($u['full_name']) ?><?= $self ? ' <span class="badge text-bg-light">You</span>' : '' ?></td>
      <td><?= e($u['username']) ?></td>
      <td><span class="badge badge-soft"><?= $u['role'] === 'admin' ? 'Administrator' : 'Health Worker' ?></span></td>
      <td><span class="badge <?= $u['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
      <td><?= $u['last_login'] ? e(date('M d, Y h:i A', strtotime($u['last_login']))) : '—' ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-light" href="form.php?id=<?= (int)$u['id'] ?>"><i class="bi bi-pencil"></i></a>
        <?php if (!$self): ?>
        <form method="post" action="status.php" class="d-inline" data-confirm-title="<?= $u['status'] === 'active' ? 'Deactivate account?' : 'Activate account?' ?>" data-confirm="<?= $u['status'] === 'active' ? 'This user will no longer be able to sign in.' : 'This user will be able to sign in again.' ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button class="btn btn-sm btn-light" title="Toggle status"><i class="bi <?= $u['status'] === 'active' ? 'bi-person-dash' : 'bi-person-check' ?>"></i></button></form>
        <?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
