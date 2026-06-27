<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_login();
$user = current_user();
require_active_plan();

$channelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$channel   = owned_channel($channelId, (int)$user['id']);
if (!$channel) { flash_set('error', 'Channel not found.'); redirect('dashboard.php'); }

/* Track actions: delete + reorder (up/down). */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action  = $_POST['action'] ?? '';
    $trackId = (int)($_POST['track_id'] ?? 0);

    // Confirm the track belongs to this channel.
    $stmt = db()->prepare('SELECT * FROM tracks WHERE id = ? AND channel_id = ?');
    $stmt->execute([$trackId, $channel['id']]);
    $track = $stmt->fetch();

    if ($track) {
        if ($action === 'delete') {
            // Remove the uploaded file if it was a local upload.
            if ($track['source_type'] === 'upload' && $track['file_path']) {
                $abs = __DIR__ . '/' . $track['file_path'];
                if (is_file($abs)) @unlink($abs);
            }
            db()->prepare('DELETE FROM tracks WHERE id = ?')->execute([$track['id']]);
            flash_set('success', 'Track removed.');
        } elseif ($action === 'up' || $action === 'down') {
            $op  = $action === 'up' ? '<' : '>';
            $dir = $action === 'up' ? 'DESC' : 'ASC';
            $stmt = db()->prepare(
                "SELECT * FROM tracks WHERE channel_id = ? AND position $op ?
                 ORDER BY position $dir LIMIT 1"
            );
            $stmt->execute([$channel['id'], $track['position']]);
            $swap = $stmt->fetch();
            if ($swap) {
                $u = db()->prepare('UPDATE tracks SET position = ? WHERE id = ?');
                $u->execute([$swap['position'], $track['id']]);
                $u->execute([$track['position'], $swap['id']]);
            }
        }
    }
    redirect('channel_detail.php?id=' . $channel['id']);
}

// Tracks in play order.
$stmt = db()->prepare('SELECT * FROM tracks WHERE channel_id = ? ORDER BY position, id');
$stmt->execute([$channel['id']]);
$tracks = $stmt->fetchAll();

$maxTracks = (int)setting('max_tracks_per_channel', 12);
$totalSecs = array_sum(array_map(fn($t) => (int)$t['duration_seconds'], $tracks));
$totalMin  = round($totalSecs / 60);

$art = $channel['emoji_icon'] ?: null;
$bg  = $channel['bg_color'] ?: 'linear-gradient(135deg,#7B2FF7,#00D4FF)';

$pageTitle  = 'GrooveVault — ' . $channel['name'];
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div class="container" style="padding-top:6rem;padding-bottom:2.5rem;">
  <a href="dashboard.php" class="btn btn-sm btn-gv-outline mb-4"><i class="bi bi-arrow-left me-1"></i>Back</a>
  <?= flash_render() ?>
  <div class="row g-4">
    <div class="col-lg-4">
      <div style="background:var(--card-bg);border:1.5px solid var(--card-border);border-radius:20px;padding:1.8rem;text-align:center;">
        <div style="width:130px;height:130px;border-radius:18px;margin:0 auto 1.2rem;background:<?= e($bg) ?>;<?= $channel['image_path'] ? 'background-image:url(' . e($channel['image_path']) . ');background-size:cover;background-position:center;' : '' ?>display:flex;align-items:center;justify-content:center;font-size:2.6rem;">
          <?php if (!$channel['image_path']): ?>
            <?= $art ? e($art) : '<i class="bi bi-music-note-beamed" style="color:#fff;"></i>' ?>
          <?php endif; ?>
        </div>
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;letter-spacing:1px;"><?= e($channel['name']) ?></h3>
        <p style="color:var(--text-muted);font-size:.85rem;" class="mb-3"><?= count($tracks) ?> track<?= count($tracks) === 1 ? '' : 's' ?> · <?= $totalMin ?> min</p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
          <?php if ($tracks): ?>
          <a href="player.php?id=<?= (int)$channel['id'] ?>" class="btn btn-gv-primary btn-sm"><i class="bi bi-play-fill me-1"></i>Play</a>
          <a href="player.php?id=<?= (int)$channel['id'] ?>&shuffle=1" class="btn btn-gv-outline btn-sm"><i class="bi bi-shuffle me-1"></i>Shuffle</a>
          <?php endif; ?>
          <a href="share_channel.php?id=<?= (int)$channel['id'] ?>" class="btn btn-gv-blue btn-sm"><i class="bi bi-share me-1"></i>Share</a>
        </div>
        <div class="gv-divider"></div>
        <div class="d-flex gap-3 justify-content-center">
          <a href="create_channel.php?id=<?= (int)$channel['id'] ?>" class="text-decoration-none" style="color:var(--text-muted);font-size:.85rem;"><i class="bi bi-pencil me-1"></i>Edit</a>
          <a href="delete_channel.php?id=<?= (int)$channel['id'] ?>" class="text-decoration-none" style="color:var(--hot-pink);font-size:.85rem;"><i class="bi bi-trash me-1"></i>Delete</a>
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 style="font-weight:600;margin:0;">Track List</h5>
        <?php if (count($tracks) < $maxTracks): ?>
          <a href="add_track.php?channel=<?= (int)$channel['id'] ?>" class="btn btn-gv-primary btn-sm"><i class="bi bi-plus me-1"></i>Add Track</a>
        <?php else: ?>
          <span class="badge-gv">Channel full (<?= $maxTracks ?>)</span>
        <?php endif; ?>
      </div>

      <?php if (!$tracks): ?>
        <div class="gv-modal-card p-4 text-center" style="max-width:none;">
          <div style="font-size:2.2rem;">🎶</div>
          <p style="color:var(--text-muted);margin:.6rem 0 1rem;">No tracks yet. Add your first one to start the vibe.</p>
          <a href="add_track.php?channel=<?= (int)$channel['id'] ?>" class="btn btn-gv-primary btn-sm"><i class="bi bi-plus me-1"></i>Add Track</a>
        </div>
      <?php else: ?>
        <?php foreach ($tracks as $i => $t): $removed = (int)($t['removed_by_admin'] ?? 0) === 1; ?>
          <div class="track-item"<?= $removed ? ' style="opacity:.6;"' : '' ?>>
            <?php if ($removed): ?>
            <div class="d-flex align-items-center gap-3" style="flex:1;min-width:0;" title="Removed by admin — cannot be played">
              <span class="track-num"><?= sprintf('%02d', $i + 1) ?></span>
              <div class="track-info">
                <div class="track-title" style="text-decoration:line-through;"><?= e($t['title']) ?></div>
                <div style="font-size:.7rem;color:var(--hot-pink);"><i class="bi bi-slash-circle me-1"></i>Removed by admin</div>
              </div>
              <i class="bi bi-lock-fill" style="color:var(--text-muted);"></i>
              <span class="track-duration"><?= fmt_duration((int)$t['duration_seconds']) ?></span>
            </div>
            <?php else: ?>
            <a href="player.php?id=<?= (int)$channel['id'] ?>&track=<?= (int)$t['id'] ?>" class="d-flex align-items-center gap-3 text-decoration-none text-reset" style="flex:1;min-width:0;" title="Play this track">
              <span class="track-num"><?= sprintf('%02d', $i + 1) ?></span>
              <div class="track-info">
                <div class="track-title"><?= e($t['title']) ?></div>
                <div style="font-size:.7rem;color:var(--text-muted);"><?= $t['source_type'] === 'upload' ? 'Uploaded file' : 'MP4 link' ?></div>
              </div>
              <i class="bi bi-play-fill" style="color:var(--neon-green);"></i>
              <span class="track-duration"><?= fmt_duration((int)$t['duration_seconds']) ?></span>
            </a>
            <?php endif; ?>
            <form method="post" class="d-flex gap-1 ms-2" style="margin:0;">
              <?= csrf_field() ?>
              <input type="hidden" name="track_id" value="<?= (int)$t['id'] ?>">
              <button name="action" value="up"     class="btn btn-sm" style="color:var(--text-muted);padding:.1rem .3rem;"<?= $i === 0 ? ' disabled' : '' ?> title="Move up"><i class="bi bi-chevron-up"></i></button>
              <button name="action" value="down"   class="btn btn-sm" style="color:var(--text-muted);padding:.1rem .3rem;"<?= $i === count($tracks) - 1 ? ' disabled' : '' ?> title="Move down"><i class="bi bi-chevron-down"></i></button>
              <a href="add_track.php?edit=<?= (int)$t['id'] ?>" class="btn btn-sm" style="color:var(--electric-blue);padding:.1rem .3rem;" title="Edit track"><i class="bi bi-pencil"></i></a>
              <button name="action" value="delete" class="btn btn-sm" style="color:var(--hot-pink);padding:.1rem .3rem;" title="Remove" onclick="return confirm('Remove this track?');"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        <?php endforeach; ?>
        <p style="color:var(--text-muted);font-size:.8rem;margin-top:.6rem;">Click a track to play it · use the arrows to reorder · up to <?= $maxTracks ?> tracks, <?= (int)setting('max_track_duration_min', 10) ?> min max each.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
require_once('inc/footer.inc.php');
?>
