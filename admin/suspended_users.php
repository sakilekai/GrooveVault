<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'unsuspend') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $stmt = db()->prepare('SELECT display_name FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $name = $stmt->fetchColumn();
        if ($name !== false) {
            db()->prepare("UPDATE users SET status = 'active', suspended_reason = NULL, suspended_at = NULL WHERE id = ?")->execute([$uid]);
            db()->prepare("UPDATE subscriptions SET status = 'active' WHERE user_id = ? AND status = 'suspended'")->execute([$uid]);
            gv_log('unsuspend', 'Reactivated user ' . $name);
            flash_set('success', $name . ' has been reactivated.');
        }
    }
    redirect('suspended_users.php');
}

$rows = db()->query(
    "SELECT id, display_name, email, suspended_reason FROM users WHERE status = 'suspended' ORDER BY suspended_at DESC"
)->fetchAll();

$pageTitle   = 'GrooveVault — Suspended Users';
$adminActive = 'suspended';
$topbarTitle = 'Suspended Users';
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

    <div class="section-header"><div class="section-title">Suspended Users</div></div>
    <?php if (!$rows): ?>
      <div class="admin-card text-center" style="padding:2.5rem;">
        <div style="font-size:2.4rem;">✅</div>
        <p style="color:var(--text-muted);margin:.6rem 0 0;">No one is suspended. Everyone's grooving.</p>
      </div>
    <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>User</th><th>Email</th><th>Reason</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $u): ?>
          <tr>
            <td><div class="d-flex align-items-center gap-2"><div class="user-avatar-sm" style="background:linear-gradient(135deg,#F5456B,#FF6B00);"><?= e(initials($u['display_name'])) ?></div><?= e($u['display_name']) ?></div></td>
            <td style="color:var(--text-dim);"><?= e($u['email']) ?></td>
            <td style="color:var(--text-dim);"><?= e($u['suspended_reason'] ?: '—') ?></td>
            <td>
              <form method="post" action="suspended_users.php" style="margin:0;">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button name="action" value="unsuspend" class="btn-admin btn-admin-success">Unsuspend</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
