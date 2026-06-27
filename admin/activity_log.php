<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'clear') {
        db()->exec('DELETE FROM activity_log');
        gv_log('purge', 'Cleared activity log');   // logged after clearing, so this entry remains
        flash_set('success', 'Activity log cleared.');
    }
    redirect('activity_log.php');
}

$rows = db()->query('SELECT * FROM activity_log ORDER BY created_at DESC')->fetchAll();

$icon = [
    'login'     => ['bi-box-arrow-in-right', 'var(--accent)',       'rgba(91,110,245,0.12)'],
    'logout'    => ['bi-box-arrow-right',    'var(--text-muted)',   'rgba(90,96,128,0.12)'],
    'suspend'   => ['bi-slash-circle',       'var(--accent-pink)',  'rgba(245,69,107,0.12)'],
    'unsuspend' => ['bi-check-circle',        'var(--accent-green)', 'rgba(34,215,138,0.12)'],
    'delete'    => ['bi-trash',              'var(--accent-pink)',  'rgba(245,69,107,0.12)'],
    'export'    => ['bi-download',           'var(--accent-amber)', 'rgba(245,166,35,0.12)'],
    'purge'     => ['bi-exclamation-octagon','var(--accent-pink)',  'rgba(245,69,107,0.12)'],
];

$pageTitle   = 'GrooveVault — Activity Log';
$adminActive = 'activity';
$topbarTitle = 'Activity Log';
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

    <div class="section-header">
      <div class="section-title">Activity Log</div>
      <?php if ($rows): ?>
      <form method="post" action="activity_log.php" style="margin:0;" onsubmit="return confirm('Clear the entire activity log?');">
        <?= csrf_field() ?>
        <button name="action" value="clear" class="btn-admin btn-admin-ghost"><i class="bi bi-trash me-1"></i>Clear</button>
      </form>
      <?php endif; ?>
    </div>
    <div class="admin-card">
      <?php if (!$rows): ?>
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;">No activity recorded yet.</p>
      <?php endif; ?>
      <?php foreach ($rows as $i => $l):
        $ic = $icon[$l['action']] ?? ['bi-activity', 'var(--accent)', 'rgba(91,110,245,0.12)'];
      ?>
        <div class="activity-item"<?= $i === 0 ? ' style="padding-top:0;"' : '' ?>>
          <div class="activity-dot" style="background:<?= $ic[2] ?>;color:<?= $ic[1] ?>;"><i class="bi <?= $ic[0] ?>"></i></div>
          <div style="flex:1;"><div style="font-size:.85rem;"><?= e($l['detail'] ?: ucfirst($l['action'])) ?></div><div class="activity-time"><?= e(time_ago($l['created_at'])) ?></div></div>
        </div>
      <?php endforeach; ?>
    </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
