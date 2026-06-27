<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'suspend') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $stmt = db()->prepare('SELECT display_name FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $name = $stmt->fetchColumn();
        if ($name !== false) {
            db()->prepare("UPDATE users SET status = 'suspended', suspended_reason = 'Admin action', suspended_at = NOW() WHERE id = ?")->execute([$uid]);
            db()->prepare("UPDATE subscriptions SET status = 'suspended' WHERE user_id = ? AND status = 'active'")->execute([$uid]);
            gv_log('suspend', 'Suspended subscription for ' . $name);
            flash_set('success', $name . ' has been suspended.');
        }
    }

    if ($action === 'delete') {
        $subId = (int)($_POST['subscription_id'] ?? 0);
        $stmt = db()->prepare(
            'SELECT s.*, u.display_name FROM subscriptions s
             JOIN users u ON u.id = s.user_id WHERE s.id = ?'
        );
        $stmt->execute([$subId]);
        $sub = $stmt->fetch();

        if ($sub) {
            $userId = (int)$sub['user_id'];
            db()->prepare('DELETE FROM subscriptions WHERE id = ?')->execute([$subId]);

            // Keep the user's plan in sync with their remaining active subscription(s).
            $stmt = db()->prepare(
                'SELECT plan_id FROM subscriptions WHERE user_id = ? AND status = "active"
                 ORDER BY started_at DESC LIMIT 1'
            );
            $stmt->execute([$userId]);
            $planId = $stmt->fetchColumn();
            db()->prepare('UPDATE users SET plan_id = ? WHERE id = ?')
                ->execute([$planId ?: null, $userId]);

            gv_log('delete', 'Deleted subscription #' . $subId . ' for ' . $sub['display_name']);
            flash_set('success', 'Subscription record for ' . $sub['display_name'] . ' has been deleted.');
        } else {
            flash_set('error', 'Subscription record not found.');
        }
    }

    redirect('subscriptions.php');
}

// Per-plan stat cards.
$planStats = db()->query(
    "SELECT p.code, p.name, p.price, p.billing_period,
            COUNT(s.id) AS subs, COALESCE(SUM(s.mrr),0) AS mrr
     FROM plans p
     LEFT JOIN subscriptions s ON s.plan_id = p.id AND s.status = 'active'
     GROUP BY p.id ORDER BY p.sort_order"
)->fetchAll();

$rows = db()->query(
    "SELECT s.*, u.display_name, u.id AS uid, p.code AS plan_code, p.name AS plan_name
     FROM subscriptions s
     JOIN users u ON u.id = s.user_id
     JOIN plans p ON p.id = s.plan_id
     ORDER BY s.started_at DESC"
)->fetchAll();

$cardColor = ['starter' => 'blue', 'pro' => 'purple', 'annual' => 'green'];

$pageTitle   = 'GrooveVault — Subscriptions';
$adminActive = 'subscriptions';
$topbarTitle = 'Subscriptions';
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

    <?= gv_admin_flash() ?>

    <div class="row g-3 mb-3">
      <?php foreach ($planStats as $p):
        $per = $p['billing_period'] === 'annual' ? '/yr' : '/mo';
      ?>
      <div class="col-md-4"><div class="stat-card <?= $cardColor[$p['code']] ?? 'blue' ?>"><div class="stat-label"><?= e($p['name']) ?> · $<?= number_format((float)$p['price'], 2) ?><?= $per ?></div><div class="stat-value"><?= (int)$p['subs'] ?></div><div class="stat-delta up">$<?= number_format((float)$p['mrr'], 2) ?> / mo</div></div></div>
      <?php endforeach; ?>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>User</th><th>Plan</th><th>MRR</th><th>Since</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No subscriptions yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><div class="d-flex align-items-center gap-2"><div class="user-avatar-sm" style="background:linear-gradient(135deg,#5B6EF5,#8B5CF6);"><?= e(initials($r['display_name'])) ?></div><?= e($r['display_name']) ?></div></td>
            <td><span class="plan-badge <?= e($r['plan_code']) ?>"><?= e($r['plan_name']) ?></span></td>
            <td style="font-family:'Space Mono',monospace;color:var(--accent-green);">$<?= number_format((float)$r['mrr'], 2) ?></td>
            <td style="color:var(--text-dim);"><?= e(date('M j, Y', strtotime($r['started_at']))) ?></td>
            <td><span class="status-dot <?= $r['status'] === 'active' ? 'active' : 'suspended' ?>"></span><?= ucfirst($r['status']) ?></td>
            <td>
              <div class="d-flex gap-1 justify-content-end">
                <?php if ($r['status'] === 'active'): ?>
                <form method="post" action="subscriptions.php" style="margin:0;" onsubmit="return confirm('Suspend <?= e($r['display_name']) ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int)$r['uid'] ?>">
                  <button name="action" value="suspend" class="btn-admin btn-admin-warning">Suspend</button>
                </form>
                <?php endif; ?>
                <form method="post" action="subscriptions.php" style="margin:0;" onsubmit="return confirm('Delete this subscription record for <?= e($r['display_name']) ?>? This cannot be undone.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="subscription_id" value="<?= (int)$r['id'] ?>">
                  <button name="action" value="delete" class="btn-admin btn-admin-danger" title="Delete record"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
