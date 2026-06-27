<?php
/* Shared USER-side header.
   Set these BEFORE including this file (all optional):
     $pageTitle  – browser tab title
     $navVariant – 'guest' (default) | 'user' | 'public'
     $navUser    – display name shown in the logged-in nav            */
require_once __DIR__ . '/functions.inc.php';
$pageTitle  = $pageTitle  ?? 'GrooveVault';
$navVariant = $navVariant ?? 'guest';
$gvUser     = current_user();
$navUser    = $navUser ?? ($gvUser['display_name'] ?? 'Guest');

// Maintenance mode: when on, the whole user app is down. Admins are exempt so
// they can still reach the panel and toggle it back off.
if (setting('maintenance_mode', '0') === '1' && !current_admin()) {
    http_response_code(503);
    ?>
    <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrooveVault — Maintenance</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link href="gv-user.css" rel="stylesheet">
    <style>body{background:#0D0618;color:#F0E6FF;font-family:'DM Sans',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;padding:2rem;}h1{font-size:1.6rem;}p{color:#8A7AAA;}</style>
    </head><body><div><div style="font-size:3rem;">🛠️</div><h1>We'll be right back</h1>
    <p>GrooveVault is down for scheduled maintenance.<br>Please check back soon.</p></div></body></html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="icon" type="image/png" href="assets/favicon.png">
<link rel="apple-touch-icon" href="assets/favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="gv-user.css" rel="stylesheet">
<style>
:root {
  --neon-green: #39FF14;
  --hot-pink: #FF2D78;
  --electric-blue: #00D4FF;
  --sunset-orange: #FF6B00;
  --acid-yellow: #FFE600;
  --deep-purple: #1A0A2E;
  --dark-bg: #0D0618;
  --card-bg: #160D28;
  --card-border: #2D1854;
  --text-main: #F0E6FF;
  --text-muted: #8A7AAA;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--dark-bg);color:var(--text-main);font-family:'DM Sans',sans-serif;min-height:100vh;overflow-x:hidden;}
/* kill horizontal overflow on mobile (html, not just body, must clip) */
html{overflow-x:hidden;}
body{max-width:100%;}
img,svg,video,canvas,iframe{max-width:100%;}
.min-w-0{min-width:0;}            /* used in markup but not a real Bootstrap class */
/* long emails / channel names in flex rows must wrap, not widen the page */
.channel-card,.gv-modal-card,.gv-alert{overflow-wrap:anywhere;}
/* comfortable side gaps so content never hugs the screen edges */
.container{padding-left:1.25rem;padding-right:1.25rem;}
@media(max-width:768px){.container{padding-left:15px!important;padding-right:15px!important;}}
@media(min-width:992px){.container{padding-left:2rem;padding-right:2rem;}}
body::before{content:'';position:fixed;top:0;left:0;width:100%;height:100%;background:radial-gradient(ellipse at 20% 20%,rgba(57,255,20,0.04) 0%,transparent 50%),radial-gradient(ellipse at 80% 80%,rgba(255,45,120,0.05) 0%,transparent 50%),radial-gradient(ellipse at 50% 50%,rgba(0,212,255,0.03) 0%,transparent 70%);pointer-events:none;z-index:0;}

/* NAV */
.gv-nav{background:rgba(13,6,24,0.92);backdrop-filter:blur(20px);border-bottom:1px solid var(--card-border);position:fixed;top:0;width:100%;z-index:1000;}
.gv-logo{font-family:'Bebas Neue',sans-serif;font-size:1.9rem;letter-spacing:2px;background:linear-gradient(135deg,var(--neon-green),var(--electric-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.gv-logo span{color:var(--hot-pink);-webkit-text-fill-color:var(--hot-pink);}
.gv-menu-item{display:block;padding:.6rem 1rem;font-size:.85rem;color:var(--text-main);text-decoration:none;transition:background .15s;}
.gv-menu-item:hover{background:rgba(255,255,255,0.06);color:var(--text-main);}

/* keep modal cards clear of the fixed nav (and let tall ones scroll, not clip) */
.view{overflow:visible;}
.modal-stage{min-height:calc(100vh - 64px);margin-top:64px;}

/* BUTTONS */
.btn-gv-primary{background:linear-gradient(135deg,var(--hot-pink),var(--sunset-orange));border:none;color:#fff;font-weight:600;border-radius:8px;padding:.55rem 1.4rem;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-gv-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(255,45,120,0.4);color:#fff;}
.btn-gv-outline{background:transparent;border:1.5px solid var(--neon-green);color:var(--neon-green);font-weight:600;border-radius:8px;padding:.5rem 1.3rem;transition:all .2s;}
.btn-gv-outline:hover{background:var(--neon-green);color:#000;box-shadow:0 0 20px rgba(57,255,20,0.35);}
.btn-gv-blue{background:linear-gradient(135deg,var(--electric-blue),#0077FF);border:none;color:#fff;font-weight:600;border-radius:8px;padding:.55rem 1.4rem;transition:all .2s;}
.btn-gv-blue:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,212,255,0.4);color:#fff;}

/* SECTIONS */
.section{position:relative;z-index:1;padding:5rem 0;}

/* HERO */
.hero-section{padding:9rem 0 5rem;text-align:center;position:relative;z-index:1;}
.hero-badge{display:inline-block;background:rgba(57,255,20,0.1);border:1px solid rgba(57,255,20,0.3);color:var(--neon-green);font-family:'Space Mono',monospace;font-size:.72rem;padding:.35rem 1rem;border-radius:50px;margin-bottom:1.5rem;letter-spacing:1px;}
.hero-title{font-family:'Bebas Neue',sans-serif;font-size:clamp(3rem,9vw,7rem);line-height:1;letter-spacing:2px;margin-bottom:1.2rem;}
.hero-title .accent-pink{color:var(--hot-pink);}
.hero-title .accent-blue{color:var(--electric-blue);}
.hero-sub{font-size:1.15rem;color:var(--text-muted);max-width:560px;margin:0 auto 2.5rem;line-height:1.7;}

/* PRICING */
.pricing-card{background:var(--card-bg);border:1.5px solid var(--card-border);border-radius:18px;padding:2.2rem;transition:all .3s;position:relative;overflow:hidden;}
.pricing-card::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(57,255,20,0.04) 0%,transparent 60%);pointer-events:none;}
.pricing-card.featured{border-color:var(--hot-pink);box-shadow:0 0 40px rgba(255,45,120,0.15);}
.pricing-card .price{font-family:'Bebas Neue',sans-serif;font-size:3.5rem;color:var(--neon-green);}
.pricing-card .price span{font-size:1.2rem;color:var(--text-muted);}

/* MISC */
.badge-gv{background:rgba(57,255,20,0.12);color:var(--neon-green);border:1px solid rgba(57,255,20,0.25);padding:.25rem .65rem;border-radius:50px;font-size:.75rem;font-family:'Space Mono',monospace;}
.badge-gv-pink{background:rgba(255,45,120,0.12);color:var(--hot-pink);border:1px solid rgba(255,45,120,0.25);}
.section-title{font-family:'Bebas Neue',sans-serif;font-size:2.5rem;letter-spacing:1px;margin-bottom:.3rem;}
.gv-divider{height:1px;background:linear-gradient(90deg,transparent,var(--card-border),transparent);margin:2rem 0;}

/* COLOR ACCENTS */
.text-neon{color:var(--neon-green);}
.text-pink{color:var(--hot-pink);}
.text-blue{color:var(--electric-blue);}
.text-orange{color:var(--sunset-orange);}
.text-yellow{color:var(--acid-yellow);}

/* PLAYER PROGRESS (demo preview) */
.player-progress{width:100%;height:4px;background:rgba(255,255,255,0.1);border-radius:2px;cursor:pointer;position:relative;margin:.4rem 0;}
.player-progress-fill{height:100%;background:linear-gradient(90deg,var(--neon-green),var(--electric-blue));border-radius:2px;width:0%;transition:width .5s linear;}

/* WAVEFORM ANIMATION */
.waveform{display:flex;align-items:center;gap:2px;height:20px;}
.waveform span{display:block;width:3px;background:var(--neon-green);border-radius:2px;animation:wave 1s infinite ease-in-out;}
.waveform span:nth-child(1){animation-delay:0s;height:8px;}
.waveform span:nth-child(2){animation-delay:.1s;height:14px;}
.waveform span:nth-child(3){animation-delay:.2s;height:10px;}
.waveform span:nth-child(4){animation-delay:.3s;height:18px;}
.waveform span:nth-child(5){animation-delay:.4s;height:12px;}
@keyframes wave{0%,100%{transform:scaleY(1);}50%{transform:scaleY(.4);}}

/* FOOTER */
.gv-footer{background:var(--card-bg);border-top:1px solid var(--card-border);padding:2.5rem 0;margin-top:4rem;position:relative;z-index:1;}

/* SCROLLBAR */
::-webkit-scrollbar{width:6px;}
::-webkit-scrollbar-track{background:var(--dark-bg);}
::-webkit-scrollbar-thumb{background:var(--card-border);border-radius:3px;}

/* RESPONSIVE */
.gv-nav-actions{display:flex;align-items:center;}
@media(max-width:768px){
  .gv-nav .container{padding-top:.45rem;padding-bottom:.45rem;}
  .gv-logo{font-size:1.7rem;letter-spacing:1px;}
  .gv-nav .btn-sm{padding:.46rem .95rem!important;font-size:.87rem;line-height:1.2;}
  .nav-label{display:none;}   /* "Sign Up Free" -> "Sign Up" on tablet & phones */
  .gv-nav-actions{gap:.5rem!important;}
  .hero-section{padding:7rem 0 3rem;}
  .section{padding:3rem 0;}
  .hero-sub{font-size:1.02rem;}
  .section-title{font-size:2rem;}
  .pricing-card{padding:1.6rem;}
  .player-bar{padding:.7rem .85rem;}
  .player-btn{font-size:1.15rem;padding:.15rem .32rem;}
  .vol-slider{width:60px;}
  .track-item{gap:.6rem;padding:.7rem .8rem;}
}
@media(max-width:480px){
  .gv-logo{font-size:1.5rem;}
  .gv-nav .btn-sm{padding:.42rem .82rem!important;font-size:.82rem;}
  .gv-nav-actions{gap:.4rem!important;}
  #gvUserToggle{max-width:140px;}
  #gvUserToggle .gv-uname{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  /* free up room for transport controls on tiny screens */
  .player-vol{display:none!important;}
  .gv-hide-sm{display:none!important;}
  .player-btn{font-size:1.05rem;padding:.1rem .28rem;}
  .hero-title{font-size:2.6rem;}
}
@media(max-width:360px){
  .gv-logo{font-size:1.34rem;}
  .gv-nav .btn-sm{padding:.37rem .66rem!important;font-size:.77rem;}
  .gv-nav-actions{gap:.3rem!important;}
}
/* homepage demo player preview — keep it from crowding on small screens */
@media(max-width:576px){
  .demo-card{padding:1.2rem!important;}
  .demo-card .waveform{display:none;}
  .demo-controls{gap:.65rem!important;}
  .demo-controls i{font-size:1rem!important;}
  .demo-controls .demo-play{width:34px!important;height:34px!important;}
}
/* track lists (channel detail / player / public) — stop rows colliding on phones */
@media(max-width:576px){
  .track-item{gap:.5rem;padding:.6rem .65rem;}
  .track-item .gap-3{gap:.5rem!important;}                     /* inner play-link spacing */
  .track-title{font-size:.85rem;}
  .track-info > div:nth-child(2){font-size:.66rem!important;}  /* source-type subtitle */
  .track-item a .bi-play-fill{display:none!important;}         /* drop the redundant green arrow */
  .track-item form{gap:.12rem!important;margin-left:.25rem!important;}
  .track-item form .btn{padding:.15rem .3rem!important;}
  .track-duration{font-size:.72rem;}
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="gv-nav">
  <div class="container d-flex align-items-center justify-content-between py-2">
    <a href="index.php" class="gv-logo text-decoration-none d-inline-flex align-items-center gap-2" style="cursor:pointer"><img src="assets/favicon.png" alt="" style="height:0.8em;width:auto;">Groove<span>Vault</span></a>
<?php if ($navVariant === 'user'): ?>
    <div class="d-flex align-items-center gap-3 gv-nav-actions">
      <a href="dashboard.php" class="btn btn-gv-outline btn-sm"><i class="bi bi-grid me-1"></i><span class="nav-label">Dashboard</span></a>
      <div class="gv-usermenu" style="position:relative;">
        <button type="button" id="gvUserToggle" class="badge-gv" style="border:none;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;">
          <i class="bi bi-person-circle"></i><span class="gv-uname"><?= htmlspecialchars($navUser) ?></span><i class="bi bi-chevron-down" style="font-size:.65rem;"></i>
        </button>
        <div id="gvUserMenu" style="display:none;position:absolute;right:0;top:calc(100% + 10px);min-width:220px;background:var(--card-bg);border:1px solid var(--card-border);border-radius:12px;box-shadow:0 10px 34px rgba(0,0,0,.45);overflow:hidden;z-index:1100;">
          <div style="padding:.8rem 1rem;border-bottom:1px solid var(--card-border);">
            <div style="font-weight:600;font-size:.85rem;color:var(--text-main);"><?= htmlspecialchars($gvUser['display_name'] ?? 'Account') ?></div>
            <div style="font-size:.74rem;color:var(--text-muted);word-break:break-all;"><?= htmlspecialchars($gvUser['email'] ?? '') ?></div>
            <?php if (!empty($gvUser['plan_name'])): ?><span class="badge-gv mt-2 d-inline-block" style="font-size:.62rem;"><?= htmlspecialchars($gvUser['plan_name']) ?></span><?php endif; ?>
          </div>
          <a href="account.php" class="gv-menu-item"><i class="bi bi-gear me-2"></i>Settings</a>
          <a href="dashboard.php" class="gv-menu-item"><i class="bi bi-grid me-2"></i>My Channels</a>
          <a href="logout.php" class="gv-menu-item" style="color:var(--hot-pink);"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a>
        </div>
      </div>
    </div>
    <script>
      (function () {
        var t = document.getElementById('gvUserToggle'), m = document.getElementById('gvUserMenu');
        if (!t || !m) return;
        t.addEventListener('click', function (e) { e.stopPropagation(); m.style.display = m.style.display === 'block' ? 'none' : 'block'; });
        document.addEventListener('click', function (e) { if (!m.contains(e.target) && e.target !== t) m.style.display = 'none'; });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') m.style.display = 'none'; });
      })();
    </script>
<?php elseif ($navVariant === 'public'): ?>
    <div class="d-flex gap-2 align-items-center gv-nav-actions">
      <span class="badge-gv gv-hide-sm">SHARED CHANNEL</span>
      <a href="login.php" class="btn btn-gv-outline btn-sm">Log In</a>
      <a href="register.php" class="btn btn-gv-primary btn-sm">Sign Up<span class="nav-label"> Free</span></a>
    </div>
<?php else: ?>
    <div class="d-flex gap-2 gv-nav-actions">
      <a href="login.php" class="btn btn-gv-outline btn-sm">Log In</a>
      <a href="register.php" class="btn btn-gv-primary btn-sm">Sign Up<span class="nav-label"> Free</span></a>
    </div>
<?php endif; ?>
  </div>
</nav>
