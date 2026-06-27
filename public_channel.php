<?php
require_once __DIR__ . '/inc/functions.inc.php';

$token = $_GET['c'] ?? '';
$stmt = db()->prepare(
    'SELECT c.*, u.display_name, u.status AS owner_status
     FROM channels c JOIN users u ON u.id = c.user_id
     WHERE c.public_token = ?'
);
$stmt->execute([$token]);
$channel = $stmt->fetch();

$notFound = !$channel || $channel['owner_status'] === 'suspended';

// The track owner is the channel owner (tracks are added by the channel's user).
// Admin-removed tracks stay visible (as "Removed by admin") only to that owner;
// everyone else doesn't see them at all.
$me      = current_user();
$isOwner = !$notFound && $me && (int)$me['id'] === (int)$channel['user_id'];

$tracks = [];
if (!$notFound) {
    $t = db()->prepare('SELECT * FROM tracks WHERE channel_id = ? ORDER BY position, id');
    $t->execute([$channel['id']]);
    $tracks = $t->fetchAll();
    if (!$isOwner) {
        // Hide admin-removed tracks from non-owners entirely.
        $tracks = array_values(array_filter($tracks, fn($t) => (int)($t['removed_by_admin'] ?? 0) !== 1));
    }
}

$pageTitle  = $notFound ? 'GrooveVault — Channel Not Found' : 'GrooveVault — ' . $channel['name'];
$navVariant = 'public';
require_once('inc/header.inc.php');
?>

<?php if ($notFound): ?>
  <div class="container" style="max-width:520px;padding-top:7rem;text-align:center;">
    <div style="font-size:3rem;">🔌</div>
    <h3 style="font-family:'Bebas Neue',sans-serif;letter-spacing:1px;margin-top:.5rem;">CHANNEL NOT FOUND</h3>
    <p style="color:var(--text-muted);">This shared link is invalid or the channel is no longer available.</p>
    <a href="index.php" class="btn btn-gv-primary mt-2">Go to GrooveVault</a>
  </div>
<?php else:
  $bg  = $channel['bg_color'] ?: 'linear-gradient(135deg,#FF2D78,#FF6B00)';
  $secs = array_sum(array_map(fn($t) => (int)$t['duration_seconds'], $tracks));
  // Admin-removed tracks stay listed (as "Removed by admin") but carry no src.
  $playlist = array_map(function ($t) {
      $m = track_media($t);
      return [
          'title'    => $t['title'],
          'kind'     => $m['kind'],
          'ref'      => $m['ref'],
          'src'      => $m['src'],
          'duration' => (int)$t['duration_seconds'],
          'removed'  => (int)($t['removed_by_admin'] ?? 0) === 1,
      ];
  }, $tracks);
?>
<div class="container" style="max-width:720px;padding-top:6rem;padding-bottom:8rem;">
  <div class="gv-alert gv-alert-info mb-4"><i class="bi bi-broadcast me-2"></i>You're listening to a shared GrooveVault channel!</div>
  <div style="background:var(--card-bg);border:1.5px solid var(--card-border);border-radius:20px;padding:2rem;text-align:center;margin-bottom:1.6rem;">
    <div id="artStage" style="position:relative;width:100%;max-width:260px;aspect-ratio:1/1;border-radius:16px;margin:0 auto 1rem;overflow:hidden;background:<?= e($bg) ?>;">
      <div id="artFallback" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:3rem;<?= $channel['image_path'] ? 'background-image:url(' . e($channel['image_path']) . ');background-size:cover;background-position:center;' : '' ?>">
        <?php if (!$channel['image_path']): ?><?= $channel['emoji_icon'] ? e($channel['emoji_icon']) : '🎵' ?><?php endif; ?>
      </div>
      <?php if ($tracks): ?><video id="audio" playsinline style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;background:#000;display:none;"></video><div id="embedHost" style="position:absolute;inset:0;display:none;background:#000;"></div><?php endif; ?>
    </div>
    <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;letter-spacing:1px;"><?= e($channel['name']) ?></h3>
    <p style="color:var(--text-muted);font-size:.85rem;" class="mb-3">by <?= e($channel['display_name']) ?> · <?= count($tracks) ?> tracks · <?= round($secs / 60) ?> min</p>
    <?php if ($tracks): ?>
    <div class="d-flex gap-2 justify-content-center">
      <button class="btn btn-gv-primary" id="btnPlay"><i class="bi bi-play-fill me-1"></i>Play Channel</button>
      <button class="btn btn-gv-outline" id="btnShuffle"><i class="bi bi-shuffle me-1"></i>Shuffle</button>
      <button class="btn btn-gv-outline" id="btnRepeat"><i class="bi bi-repeat me-1"></i>Repeat</button>
    </div>
    <?php endif; ?>
    <p style="color:var(--text-muted);font-size:.76rem;margin:.9rem 0 0;"><i class="bi bi-lock me-1"></i>Listen only — no downloads</p>
  </div>

  <?php if (!$tracks): ?>
    <p style="color:var(--text-muted);text-align:center;">This channel has no tracks yet.</p>
  <?php else: ?>
    <div id="trackList"></div>
  <?php endif; ?>

  <div class="text-center mt-4">
    <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.6rem;">Want your own channel?</p>
    <a href="register.php" class="btn btn-gv-primary">Create a Free Account</a>
  </div>
</div>

<?php if ($tracks): ?>
<div class="player-bar" style="position:fixed;bottom:0;left:0;right:0;">
  <div class="container">
    <div class="d-flex align-items-center gap-3">
      <div style="width:40px;height:40px;border-radius:8px;flex-shrink:0;background:<?= e($bg) ?>;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><?= $channel['emoji_icon'] ? e($channel['emoji_icon']) : '🎵' ?></div>
      <div style="min-width:0;flex:1;"><div class="player-title" id="npTitle">—</div><div class="player-channel"><?= e($channel['name']) ?></div></div>
      <div class="d-flex align-items-center gap-2">
        <button class="player-btn" id="btnPrev" title="Previous"><i class="bi bi-skip-backward-fill"></i></button>
        <button class="player-btn" id="btnBarPlay" style="font-size:1.5rem;"><i class="bi bi-play-circle-fill" style="color:var(--neon-green);"></i></button>
        <button class="player-btn" id="btnNext" title="Next"><i class="bi bi-skip-forward-fill"></i></button>
      </div>
    </div>
    <div class="player-progress" id="progress" style="margin-top:.5rem;"><div class="player-progress-fill" id="progressFill" style="width:0%"></div></div>
    <div class="d-flex justify-content-between" style="font-family:'Space Mono',monospace;font-size:.72rem;color:var(--text-muted);"><span id="curTime">0:00</span><span id="durTime">0:00</span></div>
  </div>
</div>
<script src="gv-player.js"></script>
<script>
  var player = GVPlayer({
    playlist: <?= json_encode($playlist, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    els: {
      audio:        document.getElementById('audio'),
      artFallback:  document.getElementById('artFallback'),
      embedHost:    document.getElementById('embedHost'),
      trackList:    document.getElementById('trackList'),
      npTitle:      document.getElementById('npTitle'),
      curTime:      document.getElementById('curTime'),
      durTime:      document.getElementById('durTime'),
      progress:     document.getElementById('progress'),
      progressFill: document.getElementById('progressFill')
    },
    volume: 0.9,
    onState: function (st) {
      var bi = document.querySelector('#btnBarPlay i');
      if (bi) { bi.className = st.playing ? 'bi bi-pause-circle-fill' : 'bi bi-play-circle-fill'; bi.style.color = 'var(--neon-green)'; }
      var bp = document.querySelector('#btnPlay i');
      if (bp) bp.className = (st.playing ? 'bi bi-pause-fill' : 'bi bi-play-fill') + ' me-1';
      var sh = document.getElementById('btnShuffle'); sh.classList.toggle('btn-gv-primary', st.shuffle); sh.classList.toggle('btn-gv-outline', !st.shuffle);
      var rp = document.getElementById('btnRepeat'); rp.classList.toggle('btn-gv-primary', st.repeat); rp.classList.toggle('btn-gv-outline', !st.repeat);
    }
  });
  document.getElementById('btnPlay').addEventListener('click', function () { player.toggle(); });
  document.getElementById('btnBarPlay').addEventListener('click', function () { player.toggle(); });
  document.getElementById('btnNext').addEventListener('click', function () { player.next(); });
  document.getElementById('btnPrev').addEventListener('click', function () { player.prev(); });
  document.getElementById('btnShuffle').addEventListener('click', function () { player.toggleShuffle(); });
  document.getElementById('btnRepeat').addEventListener('click', function () { player.toggleRepeat(); });
  player.start();
</script>
<?php endif; ?>

<?php endif; /* notFound */ ?>

<?php
require_once('inc/footer.inc.php');
?>
