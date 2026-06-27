<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $cid = (int)($_POST['channel_id'] ?? 0);
        $stmt = db()->prepare('SELECT name FROM channels WHERE id = ?');
        $stmt->execute([$cid]);
        $name = $stmt->fetchColumn();
        if ($name !== false) {
            db()->prepare('DELETE FROM channels WHERE id = ?')->execute([$cid]);
            gv_log('delete', 'Deleted channel ' . $name);
            flash_set('success', 'Channel “' . $name . '” deleted.');
        }
    }
    redirect('channels.php');
}

$q = trim($_GET['q'] ?? '');
$where = $q !== '' ? 'WHERE c.name LIKE ? OR u.display_name LIKE ?' : '';
$params = $q !== '' ? ["%$q%", "%$q%"] : [];

$maxTracks = (int)setting('max_tracks_per_channel', 12);
$stmt = db()->prepare(
    "SELECT c.*, u.display_name, COUNT(t.id) AS track_count
     FROM channels c
     JOIN users u ON u.id = c.user_id
     LEFT JOIN tracks t ON t.channel_id = c.id
     $where
     GROUP BY c.id ORDER BY c.created_at DESC"
);
$stmt->execute($params);
$channels = $stmt->fetchAll();

$pageTitle   = 'GrooveVault — All Channels';
$adminActive = 'channels';
$topbarTitle = 'Channels';
$topbarRight = '<form method="get" action="channels.php" class="search-wrap"><i class="bi bi-search"></i><input name="q" class="admin-search" placeholder="Search channels" value="' . e($q) . '"></form>';
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Channel</th><th>Owner</th><th>Tracks</th><th>Created</th><th></th></tr></thead>
        <tbody>
          <?php if (!$channels): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No channels found.</td></tr>
          <?php endif; ?>
          <?php foreach ($channels as $c):
            $bg = $c['bg_color'] ?: 'linear-gradient(135deg,#7B2FF7,#00D4FF)';
          ?>
          <tr>
            <td><div class="d-flex align-items-center gap-2"><div class="user-avatar-sm" style="border-radius:8px;background:<?= e($bg) ?>;"><?= $c['emoji_icon'] ? e($c['emoji_icon']) : '<i class="bi bi-music-note" style="color:#fff;"></i>' ?></div><span><?= e($c['name']) ?></span></div></td>
            <td style="color:var(--text-dim);"><?= e($c['display_name']) ?></td>
            <td style="font-family:'Space Mono',monospace;"><?= (int)$c['track_count'] ?> / <?= $maxTracks ?></td>
            <td style="color:var(--text-dim);"><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
            <td>
              <div class="d-flex gap-2 justify-content-end">
                <a href="channel_tracks.php?id=<?= (int)$c['id'] ?>" class="btn-admin btn-admin-ghost text-decoration-none" title="View &amp; moderate tracks"><i class="bi bi-list-ul"></i> Tracks</a>
                <form method="post" action="channels.php" style="margin:0;" onsubmit="return confirm('Delete this channel and all its tracks?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="channel_id" value="<?= (int)$c['id'] ?>">
                  <button name="action" value="delete" class="btn-admin btn-admin-danger"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
