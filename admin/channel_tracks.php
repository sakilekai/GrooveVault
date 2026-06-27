<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

$channelId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['channel_id'] ?? 0);

/* Load the channel + its owner. */
$stmt = db()->prepare(
    'SELECT c.*, u.display_name
     FROM channels c JOIN users u ON u.id = c.user_id
     WHERE c.id = ?'
);
$stmt->execute([$channelId]);
$channel = $stmt->fetch();
if (!$channel) { flash_set('error', 'Channel not found.'); redirect('channels.php'); }

/* Moderation actions: remove / restore a single track. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action  = $_POST['action'] ?? '';
    $trackId = (int)($_POST['track_id'] ?? 0);

    // Confirm the track belongs to this channel.
    $tStmt = db()->prepare('SELECT * FROM tracks WHERE id = ? AND channel_id = ?');
    $tStmt->execute([$trackId, $channel['id']]);
    $track = $tStmt->fetch();

    if ($track) {
        $admin   = current_admin();
        $adminId = $admin ? (int)$admin['id'] : null;
        if ($action === 'remove') {
            db()->prepare('UPDATE tracks SET removed_by_admin = 1, removed_at = NOW(), removed_by = ? WHERE id = ?')
                ->execute([$adminId, $track['id']]);
            gv_log('moderate', 'Removed track “' . $track['title'] . '” from channel ' . $channel['name']);
            flash_set('success', 'Track “' . $track['title'] . '” removed. Listeners can no longer play it.');
        } elseif ($action === 'restore') {
            db()->prepare('UPDATE tracks SET removed_by_admin = 0, removed_at = NULL, removed_by = NULL WHERE id = ?')
                ->execute([$track['id']]);
            gv_log('moderate', 'Restored track “' . $track['title'] . '” in channel ' . $channel['name']);
            flash_set('success', 'Track “' . $track['title'] . '” restored.');
        }
    }
    redirect('channel_tracks.php?id=' . $channel['id']);
}

/* Tracks in play order. */
$tStmt = db()->prepare('SELECT * FROM tracks WHERE channel_id = ? ORDER BY position, id');
$tStmt->execute([$channel['id']]);
$tracks = $tStmt->fetchAll();

$removedCount = 0;
foreach ($tracks as $t) { if ((int)($t['removed_by_admin'] ?? 0) === 1) $removedCount++; }

$bg = $channel['bg_color'] ?: 'linear-gradient(135deg,#7B2FF7,#00D4FF)';

$pageTitle   = 'GrooveVault — Channel Tracks';
$adminActive = 'channels';
$topbarTitle = 'Channel Tracks';
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-3">
        <div class="user-avatar-sm" style="width:44px;height:44px;border-radius:10px;background:<?= e($bg) ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
          <?= $channel['emoji_icon'] ? e($channel['emoji_icon']) : '<i class="bi bi-music-note" style="color:#fff;"></i>' ?>
        </div>
        <div>
          <div style="font-weight:600;font-size:1.05rem;"><?= e($channel['name']) ?></div>
          <div style="color:var(--text-muted);font-size:.82rem;">by <?= e($channel['display_name']) ?> · <?= count($tracks) ?> track<?= count($tracks) === 1 ? '' : 's' ?><?= $removedCount ? ' · ' . $removedCount . ' removed' : '' ?></div>
        </div>
      </div>
      <a href="channels.php" class="btn-admin btn-admin-ghost text-decoration-none"><i class="bi bi-arrow-left"></i> Back to channels</a>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Title</th><th>Source</th><th>Duration</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$tracks): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">This channel has no tracks.</td></tr>
          <?php endif; ?>
          <?php foreach ($tracks as $i => $t):
            $removed = (int)($t['removed_by_admin'] ?? 0) === 1;
            $src     = $t['source_type'] === 'upload' ? $t['file_path'] : $t['source_url'];
          ?>
          <tr<?= $removed ? ' style="opacity:.6;"' : '' ?>>
            <td style="font-family:'Space Mono',monospace;color:var(--text-muted);"><?= sprintf('%02d', $i + 1) ?></td>
            <td>
              <div style="<?= $removed ? 'text-decoration:line-through;' : '' ?>"><?= e($t['title']) ?></div>
              <?php if ($src): ?>
                <div style="font-size:.7rem;color:var(--text-muted);max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($src) ?></div>
              <?php endif; ?>
            </td>
            <td style="color:var(--text-dim);"><?= $t['source_type'] === 'upload' ? 'Uploaded file' : 'MP4 link' ?></td>
            <td style="font-family:'Space Mono',monospace;color:var(--text-dim);"><?= e(fmt_duration((int)$t['duration_seconds'])) ?></td>
            <td>
              <?php if ($removed): ?>
                <span style="font-size:.78rem;"><span class="status-dot suspended"></span>Removed by admin</span>
                <?php if ($t['removed_at']): ?><div style="font-size:.68rem;color:var(--text-muted);"><?= e(time_ago($t['removed_at'])) ?></div><?php endif; ?>
              <?php else: ?>
                <span style="font-size:.78rem;"><span class="status-dot active"></span>Live</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" action="channel_tracks.php" style="margin:0;text-align:right;"
                    onsubmit="return confirm('<?= $removed ? 'Restore this track so listeners can play it again?' : 'Remove this track? It will show as “Removed by admin” and can no longer be played.' ?>');">
                <?= csrf_field() ?>
                <input type="hidden" name="channel_id" value="<?= (int)$channel['id'] ?>">
                <input type="hidden" name="track_id" value="<?= (int)$t['id'] ?>">
                <?php if ($removed): ?>
                  <button name="action" value="restore" class="btn-admin btn-admin-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                <?php else: ?>
                  <button name="action" value="remove" class="btn-admin btn-admin-danger"><i class="bi bi-slash-circle"></i> Remove</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
