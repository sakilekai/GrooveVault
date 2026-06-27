<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_login();
$user = current_user();

// Require an active (non-expired) subscription; otherwise send to the plan picker.
require_active_plan();

// All of this user's channels, with live track counts.
$stmt = db()->prepare(
    'SELECT c.*, COUNT(t.id) AS track_count
     FROM channels c
     LEFT JOIN tracks t ON t.channel_id = c.id
     WHERE c.user_id = ?
     GROUP BY c.id
     ORDER BY c.created_at DESC'
);
$stmt->execute([$user['id']]);
$channels = $stmt->fetchAll();

$limit     = user_channel_limit($user);
$canCreate = count($channels) < $limit;

$pageTitle  = 'GrooveVault — My Channels';
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div class="container" style="padding-top:6rem;padding-bottom:2.5rem;">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-3">
    <div>
      <h2 class="section-title mb-0">MY <span class="text-blue">CHANNELS</span></h2>
      <p style="color:var(--text-muted);font-size:.88rem;margin:0;">Your music empire awaits.</p>
    </div>
    <?php if ($canCreate): ?>
      <a href="create_channel.php" class="btn btn-gv-primary"><i class="bi bi-plus-lg me-2"></i>New Channel</a>
    <?php endif; ?>
  </div>

  <?= flash_render() ?>

  <div class="gv-alert gv-alert-info mb-4">
    <i class="bi bi-lightning-charge-fill me-2"></i>
    <?= e($user['plan_name']) ?> ·
    <?= $limit === PHP_INT_MAX ? 'unlimited channels' : (count($channels) . ' / ' . $limit . ' channels used') ?>.
  </div>

  <div class="row g-4">
    <?php foreach ($channels as $c):
      $art = $c['emoji_icon'] ?: null;
      $bg  = $c['bg_color'] ?: 'linear-gradient(135deg,#7B2FF7,#00D4FF)';
    ?>
    <div class="col-md-4">
      <a href="channel_detail.php?id=<?= (int)$c['id'] ?>" class="text-decoration-none text-reset">
        <div class="channel-card">
          <div class="d-flex gap-3 align-items-center">
            <div class="channel-art" style="background:<?= e($bg) ?>;<?= $c['image_path'] ? 'background-image:url(' . e($c['image_path']) . ');background-size:cover;background-position:center;' : '' ?>">
              <?php if (!$c['image_path']): ?>
                <?= $art ? e($art) : '<i class="bi bi-music-note-beamed" style="color:#fff;"></i>' ?>
              <?php endif; ?>
            </div>
            <div class="min-w-0">
              <div style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:.5px;"><?= e($c['name']) ?></div>
              <div style="color:var(--text-muted);font-size:.82rem;"><?= (int)$c['track_count'] ?> track<?= $c['track_count'] == 1 ? '' : 's' ?></div>
            </div>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>

    <?php if ($canCreate): ?>
    <div class="col-md-4">
      <a href="create_channel.php" class="text-decoration-none text-reset">
        <div class="channel-card" style="border-style:dashed;display:flex;align-items:center;justify-content:center;min-height:112px;color:var(--text-muted);">
          <div class="text-center"><i class="bi bi-plus-circle" style="font-size:1.5rem;"></i><div style="font-size:.85rem;margin-top:.3rem;"><?= $channels ? 'Create a channel' : 'Create your first channel' ?></div></div>
        </div>
      </a>
    </div>
    <?php elseif (!$channels): ?>
      <div class="col-12"><p style="color:var(--text-muted);">You've reached your plan's channel limit.</p></div>
    <?php endif; ?>
  </div>
</div>

<?php
require_once('inc/footer.inc.php');
?>
