<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_login();
$user = current_user();
require_active_plan();

$channelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$channel   = owned_channel($channelId, (int)$user['id']);
if (!$channel) { flash_set('error', 'Channel not found.'); redirect('dashboard.php'); }

$stmt = db()->prepare('SELECT * FROM tracks WHERE channel_id = ? ORDER BY position, id');
$stmt->execute([$channel['id']]);
$tracks = $stmt->fetchAll();
if (!$tracks) { flash_set('info', 'Add a track before playing.'); redirect('channel_detail.php?id=' . $channel['id']); }

$shuffle = !empty($_GET['shuffle']);
$bg = $channel['bg_color'] ?: 'linear-gradient(135deg,#7B2FF7,#00D4FF)';

// Optional ?track=<id> — start the player on a specific track (and autoplay it).
$startIndex = 0;
if (isset($_GET['track'])) {
    foreach ($tracks as $i => $t) {
        if ((int)$t['id'] === (int)$_GET['track']) { $startIndex = $i; break; }
    }
}
$autoplay = isset($_GET['track']) || $shuffle;

// Build the playlist for the front-end player. Tracks an admin has removed are
// kept in the list (shown as "Removed by admin") but carry no playable source.
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

$pageTitle  = 'GrooveVault — Player';
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div style="background:var(--deep-purple);min-height:100vh;padding-top:5rem;">
  <div class="container" style="padding:2rem 1rem;">
    <a href="channel_detail.php?id=<?= (int)$channel['id'] ?>" class="btn btn-sm btn-gv-outline mb-3"><i class="bi bi-arrow-left me-1"></i>Back to channel</a>
    <div class="gv-alert gv-alert-success mb-4" style="display:inline-flex;align-items:center;gap:.6rem;"><i class="bi bi-broadcast"></i> Plays every track in order, non-stop, until the last file — like a radio station.</div>

    <div class="row g-4">
      <div class="col-lg-4 text-center">
        <div id="artStage" style="position:relative;width:100%;max-width:320px;aspect-ratio:1/1;border-radius:18px;margin:0 auto 1rem;overflow:hidden;background:<?= e($bg) ?>;">
          <div id="artFallback" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:4rem;<?= $channel['image_path'] ? 'background-image:url(' . e($channel['image_path']) . ');background-size:cover;background-position:center;' : '' ?>">
            <?php if (!$channel['image_path']): ?><?= $channel['emoji_icon'] ? e($channel['emoji_icon']) : '<i class="bi bi-music-note-beamed" style="color:#fff;"></i>' ?><?php endif; ?>
          </div>
          <video id="audio" playsinline style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;background:#000;display:none;"></video>
          <div id="embedHost" style="position:absolute;inset:0;display:none;background:#000;"></div>
        </div>
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;letter-spacing:1px;"><?= e($channel['name']) ?></h3>
        <p style="color:var(--text-muted);font-size:.85rem;"><?= count($tracks) ?> tracks</p>
      </div>
      <div class="col-lg-8">
        <div id="trackList"></div>
      </div>
    </div>
  </div>

  <div class="player-bar" style="position:fixed;bottom:0;left:0;right:0;">
    <div class="container">
      <div class="d-flex align-items-center gap-3">
        <div style="width:44px;height:44px;border-radius:8px;flex-shrink:0;background:<?= e($bg) ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><?= $channel['emoji_icon'] ? e($channel['emoji_icon']) : '<i class="bi bi-music-note" style="color:#fff;"></i>' ?></div>
        <div style="min-width:0;flex:1;"><div class="player-title" id="npTitle">—</div><div class="player-channel"><?= e($channel['name']) ?></div></div>
        <div class="d-flex align-items-center gap-2">
          <button class="player-btn" id="btnShuffle" title="Shuffle"><i class="bi bi-shuffle"></i></button>
          <button class="player-btn" id="btnPrev" title="Previous"><i class="bi bi-skip-backward-fill"></i></button>
          <button class="player-btn" id="btnPlay" style="font-size:1.6rem;"><i class="bi bi-play-circle-fill" style="color:var(--neon-green);"></i></button>
          <button class="player-btn" id="btnNext" title="Skip to next"><i class="bi bi-skip-forward-fill"></i></button>
          <button class="player-btn" id="btnRepeat" title="Repeat"><i class="bi bi-repeat"></i></button>
        </div>
        <div class="d-flex align-items-center gap-2 ms-2 player-vol"><i class="bi bi-volume-up" style="color:var(--text-muted);"></i><input type="range" class="vol-slider" id="vol" min="0" max="100" value="80"></div>
      </div>
      <div class="player-progress" id="progress" style="margin-top:.6rem;"><div class="player-progress-fill" id="progressFill" style="width:0%"></div></div>
      <div class="d-flex justify-content-between" style="font-family:'Space Mono',monospace;font-size:.72rem;color:var(--text-muted);"><span id="curTime">0:00</span><span id="durTime">0:00</span></div>
    </div>
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
    shuffle:    <?= $shuffle ? 'true' : 'false' ?>,
    startIndex: <?= (int)$startIndex ?>,
    autoplay:   <?= $autoplay ? 'true' : 'false' ?>,
    volume:     0.8,
    onState: function (st) {
      var pi = document.querySelector('#btnPlay i');
      if (pi) { pi.className = st.playing ? 'bi bi-pause-circle-fill' : 'bi bi-play-circle-fill'; pi.style.color = 'var(--neon-green)'; }
      document.getElementById('btnShuffle').classList.toggle('active', st.shuffle);
      document.getElementById('btnRepeat').classList.toggle('active', st.repeat);
    }
  });
  document.getElementById('btnPlay').addEventListener('click', function () { player.toggle(); });
  document.getElementById('btnNext').addEventListener('click', function () { player.next(); });
  document.getElementById('btnPrev').addEventListener('click', function () { player.prev(); });
  document.getElementById('btnShuffle').addEventListener('click', function () { player.toggleShuffle(); });
  document.getElementById('btnRepeat').addEventListener('click', function () { player.toggleRepeat(); });
  document.getElementById('vol').addEventListener('input', function () { player.setVolume(this.value / 100); });
  player.start();
</script>

<?php
require_once('inc/footer.inc.php');
?>
