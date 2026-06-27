<?php
require_once __DIR__ . '/inc/functions.inc.php';
$pageTitle  = 'GrooveVault — Home';
// Show the logged-in nav (Dashboard + name + logout) when a user is signed in.
$navVariant = current_user() ? 'user' : 'guest';
$pricingPlans = db()->query('SELECT * FROM plans ORDER BY sort_order, id')->fetchAll();
$maxTracksPerChannel = (int)setting('max_tracks_per_channel', 12);
require_once('inc/header.inc.php');
?>

<!-- ============ HOME PAGE ============ -->
<div id="page-home">
  <div class="hero-section container">
    <div class="mb-3"><img src="assets/favicon.png" alt="GrooveVault" style="height:90px;width:auto;"></div>
    <div class="hero-badge"><i class="bi bi-music-note-beamed me-1"></i> YOUR MUSIC. YOUR CHANNEL.</div>
    <h1 class="hero-title">DROP THE<br><span class="accent-pink">BEAT</span> BUILD THE <span class="accent-blue">VIBE</span></h1>
    <p class="hero-sub">Create unlimited music channels, curate up to 12 tracks, shuffle your playlist, and share your channel with the world — all from GrooveVault.</p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="register.php" class="btn btn-gv-primary btn-lg"><i class="bi bi-rocket-takeoff me-2"></i>Start Your Channel</a>
      <a href="#pricing" class="btn btn-gv-outline btn-lg"><i class="bi bi-tag me-2"></i>See Pricing</a>
    </div>
    <!-- DEMO PLAYER PREVIEW -->
    <div class="row justify-content-center mt-5">
      <div class="col-lg-8">
        <div class="demo-card" style="background:var(--card-bg);border:1.5px solid var(--card-border);border-radius:20px;padding:2rem;position:relative;overflow:hidden;">
          <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:radial-gradient(circle,rgba(255,45,120,0.15),transparent);border-radius:50%;"></div>
          <div style="position:absolute;bottom:-40px;left:-40px;width:150px;height:150px;background:radial-gradient(circle,rgba(0,212,255,0.12),transparent);border-radius:50%;"></div>
          <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width:60px;height:60px;background:linear-gradient(135deg,#FF2D78,#FF6B00);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">🔥</div>
            <div class="text-start">
              <div style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:1px;">Summer Bangers Vol. 1</div>
              <div style="font-size:.8rem;color:var(--text-muted);">8 tracks · Public Channel</div>
            </div>
            <div class="ms-auto waveform"><span></span><span></span><span></span><span></span><span></span></div>
          </div>
          <div class="player-progress"><div class="player-progress-fill" style="width:42%"></div></div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <div style="font-family:'Space Mono',monospace;font-size:.78rem;color:var(--text-muted);">1:47</div>
            <div class="d-flex gap-3 align-items-center demo-controls">
              <i class="bi bi-shuffle" style="color:var(--neon-green);font-size:1.1rem;"></i>
              <i class="bi bi-skip-backward-fill" style="font-size:1.2rem;"></i>
              <div class="demo-play" style="width:38px;height:38px;background:linear-gradient(135deg,var(--hot-pink),var(--sunset-orange));border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;"><i class="bi bi-pause-fill" style="font-size:1.1rem;"></i></div>
              <i class="bi bi-skip-forward-fill" style="font-size:1.2rem;"></i>
              <i class="bi bi-repeat" style="color:var(--text-muted);font-size:1.1rem;"></i>
            </div>
            <div style="font-family:'Space Mono',monospace;font-size:.78rem;color:var(--text-muted);">4:12</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FEATURES -->
  <div class="section container">
    <div class="row g-4">
      <div class="col-md-4">
        <div style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;padding:1.8rem;height:100%;">
          <div style="font-size:2.2rem;margin-bottom:1rem;">🎚️</div>
          <h5 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1px;color:var(--electric-blue);">Curate Your Channels</h5>
          <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">Upload MP4 audio tracks or paste links. Organize up to 12 songs per channel. Drag to reorder, shuffle on the fly.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;padding:1.8rem;height:100%;">
          <div style="font-size:2.2rem;margin-bottom:1rem;">🔗</div>
          <h5 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1px;color:var(--hot-pink);">Share Instantly</h5>
          <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">Every channel gets a unique shareable link. Send it to anyone — they can listen without an account.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;padding:1.8rem;height:100%;">
          <div style="font-size:2.2rem;margin-bottom:1rem;">⚡</div>
          <h5 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1px;color:var(--neon-green);">Multi-Channel Power</h5>
          <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">Create as many channels as you want. Workout, chill, party — a channel for every mood, every moment.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- PRICING -->
  <div id="pricing" class="section container">
    <div class="text-center mb-5">
      <div class="hero-badge mb-2">PLANS</div>
      <h2 class="section-title">PICK YOUR <span class="text-pink">VIBE</span></h2>
      <p style="color:var(--text-muted);">All plans include multi-channel creation, shuffle, and sharing.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <?php
      $planColors = ['var(--electric-blue)', 'var(--hot-pink)', 'var(--acid-yellow)', 'var(--neon-green)', 'var(--electric-blue)'];
      foreach ($pricingPlans as $i => $plan):
        $priceParts = explode('.', number_format((float)$plan['price'], 2, '.', ''));
        $per = $plan['billing_period'] === 'annual' ? '/yr' : '/mo';
        $color = $planColors[$i % count($planColors)];
        $channelText = $plan['channel_limit'] === null
            ? 'Unlimited channels'
            : 'Up to ' . (int)$plan['channel_limit'] . ' channels';
      ?>
      <div class="col-md-4">
        <div class="pricing-card<?= $plan['is_popular'] ? ' featured' : '' ?>">
          <?php if ($plan['is_popular']): ?><div class="badge-gv-pink badge-gv mb-2">MOST POPULAR</div><?php endif; ?>
          <h5 style="font-family:'Bebas Neue',sans-serif;letter-spacing:1px;font-size:1.4rem;color:<?= $color ?>;"><?= e(strtoupper($plan['name'])) ?></h5>
          <div class="price">$<?= e($priceParts[0]) ?><span>.<?= e($priceParts[1] ?? '00') . $per ?></span></div>
          <hr style="border-color:var(--card-border);">
          <ul class="list-unstyled" style="font-size:.9rem;color:var(--text-muted);line-height:2;">
            <li><i class="bi bi-check2 text-neon me-2"></i><?= e($channelText) ?></li>
            <li><i class="bi bi-check2 text-neon me-2"></i><?= (int)$maxTracksPerChannel ?> songs per channel</li>
            <li><i class="bi bi-check2 text-neon me-2"></i>Shareable links</li>
            <li><i class="bi bi-check2 text-neon me-2"></i>Shuffle mode</li>
            <?php if ($plan['billing_period'] === 'annual'): ?>
            <li><i class="bi bi-check2 text-neon me-2"></i>Best yearly value</li>
            <?php elseif ($plan['is_popular']): ?>
            <li><i class="bi bi-check2 text-neon me-2"></i>Priority support</li>
            <?php endif; ?>
          </ul>
          <a href="register.php" class="btn <?= $plan['is_popular'] ? 'btn-gv-primary' : ($plan['billing_period'] === 'annual' ? 'btn-gv-blue' : 'btn-gv-outline') ?> w-100 mt-3">
            <?= $plan['is_popular'] ? 'Go Pro' : ($plan['billing_period'] === 'annual' ? 'Best Value' : 'Get Started') ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div><!-- /#page-home -->
<?php
require_once('inc/footer.inc.php');
?>