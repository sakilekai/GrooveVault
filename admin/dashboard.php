<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

$pdo = db();
$totalUsers   = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$activeSubs   = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
$totalChans   = (int)$pdo->query('SELECT COUNT(*) FROM channels')->fetchColumn();
$totalTracks  = (int)$pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();

$newUsersWeek = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY')->fetchColumn();
$newSubsWeek  = (int)$pdo->query('SELECT COUNT(*) FROM subscriptions WHERE created_at >= NOW() - INTERVAL 7 DAY')->fetchColumn();
$newChansWeek = (int)$pdo->query('SELECT COUNT(*) FROM channels WHERE created_at >= NOW() - INTERVAL 7 DAY')->fetchColumn();

// Plan breakdown (count of users on each plan).
$planRows = $pdo->query(
    "SELECT p.code, p.name, COUNT(u.id) AS cnt
     FROM plans p LEFT JOIN users u ON u.plan_id = p.id
     GROUP BY p.id ORDER BY p.sort_order"
)->fetchAll();
$planMax = max(1, ...array_map(fn($r) => (int)$r['cnt'], $planRows ?: [['cnt' => 0]]));

$mrr = (float)$pdo->query("SELECT COALESCE(SUM(mrr),0) FROM subscriptions WHERE status = 'active'")->fetchColumn();

$recentUsers = $pdo->query(
    'SELECT u.display_name, p.code AS plan_code, p.name AS plan_name
     FROM users u LEFT JOIN plans p ON p.id = u.plan_id
     ORDER BY u.created_at DESC LIMIT 4'
)->fetchAll();

$recentLog = $pdo->query('SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 4')->fetchAll();

$planColors = ['starter' => 'var(--accent-blue)', 'pro' => 'var(--accent)', 'annual' => 'var(--accent-green)'];

$pageTitle   = 'GrooveVault — Admin Dashboard';
$adminActive = 'dashboard';
$topbarTitle = 'Dashboard';
$topbarRight = '<a href="dashboard.php" class="btn-admin btn-admin-ghost text-decoration-none"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</a><div class="admin-avatar">A</div>';
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="stat-card purple"><div class="stat-icon purple"><i class="bi bi-people"></i></div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Total users</div><div class="stat-delta up">▲ <?= $newUsersWeek ?> this week</div></div></div>
      <div class="col-md-3"><div class="stat-card green"><div class="stat-icon green"><i class="bi bi-credit-card"></i></div><div class="stat-value"><?= $activeSubs ?></div><div class="stat-label">Active subscriptions</div><div class="stat-delta up">▲ <?= $newSubsWeek ?> this week</div></div></div>
      <div class="col-md-3"><div class="stat-card blue"><div class="stat-icon blue"><i class="bi bi-collection-play"></i></div><div class="stat-value"><?= $totalChans ?></div><div class="stat-label">Channels</div><div class="stat-delta up">▲ <?= $newChansWeek ?> this week</div></div></div>
      <div class="col-md-3"><div class="stat-card amber"><div class="stat-icon amber"><i class="bi bi-music-note-beamed"></i></div><div class="stat-value"><?= $totalTracks ?></div><div class="stat-label">Tracks</div><div class="stat-delta up">live count</div></div></div>
    </div>
    <div class="row g-3">
      <div class="col-md-5"><div class="admin-card">
        <div class="admin-card-title">Plan Breakdown</div>
        <?php foreach ($planRows as $r):
          $pct = round((int)$r['cnt'] / $planMax * 100);
          $col = $planColors[$r['code']] ?? 'var(--accent)';
        ?>
          <div class="d-flex justify-content-between mb-1" style="font-size:.8rem;"><span style="color:var(--text-dim);"><?= e($r['name']) ?></span><span style="color:var(--text-muted);"><?= (int)$r['cnt'] ?></span></div>
          <div class="prog-bar-wrap mb-3"><div class="prog-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div></div>
        <?php endforeach; ?>
        <div class="divider"></div>
        <div class="d-flex justify-content-between align-items-end"><span style="color:var(--text-muted);font-size:.8rem;">Estimated MRR</span><span class="stat-value" style="font-size:1.8rem;color:var(--accent-green);">$<?= number_format($mrr, 2) ?></span></div>
      </div></div>
      <div class="col-md-4"><div class="admin-card">
        <div class="admin-card-title">Recent Signups</div>
        <?php if (!$recentUsers): ?>
          <p style="color:var(--text-muted);font-size:.82rem;">No users yet.</p>
        <?php endif; ?>
        <?php foreach ($recentUsers as $u):
          $badge = $u['plan_code'] ?: 'none';
          $label = $u['plan_name'] ?: 'None';
        ?>
          <div class="d-flex align-items-center gap-2 mb-3"><div class="user-avatar-sm" style="background:linear-gradient(135deg,#5B6EF5,#8B5CF6);"><?= e(initials($u['display_name'])) ?></div><div style="flex:1;font-size:.82rem;"><?= e($u['display_name']) ?></div><span class="plan-badge <?= e($badge) ?>"><?= e($label) ?></span></div>
        <?php endforeach; ?>
      </div></div>
      <div class="col-md-3"><div class="admin-card">
        <div class="admin-card-title">Activity</div>
        <?php if (!$recentLog): ?>
          <p style="color:var(--text-muted);font-size:.82rem;">No activity logged.</p>
        <?php endif; ?>
        <?php foreach ($recentLog as $i => $l): ?>
          <div class="activity-item"<?= $i === 0 ? ' style="padding-top:0;"' : '' ?>><div class="activity-dot" style="background:rgba(91,110,245,0.12);color:var(--accent);"><i class="bi bi-activity"></i></div><div><div style="font-size:.8rem;"><?= e($l['action']) ?><?= $l['detail'] ? ' — ' . e($l['detail']) : '' ?></div><div class="activity-time"><?= e(time_ago($l['created_at'])) ?></div></div></div>
        <?php endforeach; ?>
      </div></div>
    </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
