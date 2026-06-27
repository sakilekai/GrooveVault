<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

$userId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $target = $stmt->fetch();

    if ($target) {
        if ($action === 'suspend') {
            $reason = trim($_POST['reason'] ?? '') ?: 'Admin action';
            db()->prepare("UPDATE users SET status = 'suspended', suspended_reason = ?, suspended_at = NOW() WHERE id = ?")
                ->execute([$reason, $target['id']]);
            db()->prepare("UPDATE subscriptions SET status = 'suspended' WHERE user_id = ? AND status = 'active'")
                ->execute([$target['id']]);
            gv_log('suspend', 'Suspended user ' . $target['display_name']);
            flash_set('success', $target['display_name'] . ' has been suspended.');
            redirect('user_detail.php?id=' . $target['id']);
        } elseif ($action === 'unsuspend') {
            db()->prepare("UPDATE users SET status = 'active', suspended_reason = NULL, suspended_at = NULL WHERE id = ?")
                ->execute([$target['id']]);
            gv_log('unsuspend', 'Reactivated user ' . $target['display_name']);
            flash_set('success', $target['display_name'] . ' has been reactivated.');
            redirect('user_detail.php?id=' . $target['id']);
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$target['id']]);
            gv_log('delete', 'Deleted user ' . $target['display_name']);
            flash_set('success', $target['display_name'] . ' and all their data were deleted.');
            redirect('users.php');
        }
    }
    redirect('users.php');
}

$stmt = db()->prepare(
    'SELECT u.*, p.name AS plan_name, p.price AS plan_price, p.billing_period AS plan_billing
     FROM users u LEFT JOIN plans p ON p.id = u.plan_id WHERE u.id = ?'
);
$stmt->execute([$userId]);
$u = $stmt->fetch();
if (!$u) { flash_set('error', 'User not found.'); redirect('users.php'); }

$chStmt = db()->prepare(
    'SELECT c.*, COUNT(t.id) AS track_count
     FROM channels c LEFT JOIN tracks t ON t.channel_id = c.id
     WHERE c.user_id = ? GROUP BY c.id ORDER BY c.created_at DESC'
);
$chStmt->execute([$u['id']]);
$channels = $chStmt->fetchAll();
$trackTotal = array_sum(array_map(fn($c) => (int)$c['track_count'], $channels));

$priceLabel = $u['plan_price'] !== null
    ? $u['plan_name'] . ' · $' . number_format((float)$u['plan_price'], 2) . ($u['plan_billing'] === 'annual' ? '/yr' : '/mo')
    : 'No plan';

$pageTitle = 'GrooveVault — User Detail';
$adminBare = true;
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

  <div class="modal-stage" style="align-items:flex-start;">
    <div class="detail-panel" style="max-width:760px;width:100%;">
      <?= gv_admin_flash() ?>
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="d-flex align-items-center gap-3">
          <div class="user-avatar-sm" style="width:48px;height:48px;font-size:1rem;background:linear-gradient(135deg,#5B6EF5,#8B5CF6);"><?= e(initials($u['display_name'])) ?></div>
          <div><div style="font-weight:600;font-size:1.05rem;"><?= e($u['display_name']) ?></div><div style="color:var(--text-muted);font-size:.82rem;"><?= e($u['email']) ?></div></div>
        </div>
        <div class="d-flex gap-2">
          <?php if ($u['status'] === 'active'): ?>
            <button type="button" class="btn-admin btn-admin-warning" onclick="document.getElementById('suspendForm').style.display='block';">Suspend</button>
          <?php else: ?>
            <form method="post" action="user_detail.php" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button name="action" value="unsuspend" class="btn-admin btn-admin-success">Unsuspend</button></form>
          <?php endif; ?>
          <form method="post" action="user_detail.php" style="margin:0;" onsubmit="return confirm('Delete this user and ALL their channels/tracks? This cannot be undone.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button name="action" value="delete" class="btn-admin btn-admin-danger">Delete</button></form>
          <a href="users.php" class="btn-admin btn-admin-ghost text-decoration-none">✕ Close</a>
        </div>
      </div>

      <div id="suspendForm" style="display:none;margin-bottom:1rem;">
        <form method="post" action="user_detail.php" class="admin-card" style="padding:1rem;">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <label class="form-label">Suspension reason</label>
          <input name="reason" class="form-control mb-2" placeholder="e.g. Terms violation" value="Admin action">
          <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn-admin btn-admin-ghost" onclick="document.getElementById('suspendForm').style.display='none';">Cancel</button>
            <button name="action" value="suspend" class="btn-admin btn-admin-warning">Confirm Suspend</button>
          </div>
        </form>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="admin-card" style="padding:.9rem;"><div class="stat-label">Plan</div><span class="plan-badge <?= e($u['plan_name'] ? strtolower(explode(' ', $u['plan_name'])[1] ?? 'none') : 'none') ?> mt-1 d-inline-block"><?= e($priceLabel) ?></span></div></div>
        <div class="col-6 col-md-3"><div class="admin-card" style="padding:.9rem;"><div class="stat-label">Status</div><div style="margin-top:.3rem;font-size:.85rem;"><span class="status-dot <?= $u['status'] === 'active' ? 'active' : 'suspended' ?>"></span><?= ucfirst($u['status']) ?></div><?php if ($u['status'] === 'suspended' && $u['suspended_reason']): ?><div style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem;"><?= e($u['suspended_reason']) ?></div><?php endif; ?></div></div>
        <div class="col-6 col-md-3"><div class="admin-card" style="padding:.9rem;"><div class="stat-label">Channels</div><div class="stat-value" style="font-size:1.6rem;"><?= count($channels) ?></div></div></div>
        <div class="col-6 col-md-3"><div class="admin-card" style="padding:.9rem;"><div class="stat-label">Tracks</div><div class="stat-value" style="font-size:1.6rem;"><?= $trackTotal ?></div></div></div>
      </div>

      <div class="stat-label mb-2">Channels</div>
      <?php if (!$channels): ?>
        <p style="color:var(--text-muted);font-size:.82rem;">This user has no channels.</p>
      <?php else: ?>
      <div class="d-flex gap-2 flex-wrap">
        <?php foreach ($channels as $c): ?>
          <span class="channel-chip"><?= $c['emoji_icon'] ? '<span style="font-size:1rem;">' . e($c['emoji_icon']) . '</span>' : '<i class="bi bi-music-note" style="color:var(--accent);"></i>' ?> <?= e($c['name']) ?> · <?= (int)$c['track_count'] ?> tracks</span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
