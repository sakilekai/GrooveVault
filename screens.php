<?php /* Screens index — quick navigation to every page (user app + admin panel). */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GrooveVault — Screens Index</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{--hot-pink:#FF2D78;--electric-blue:#00D4FF;--neon-green:#39FF14;--dark-bg:#0D0618;--card-bg:#160D28;--card-border:#2D1854;--text-main:#F0E6FF;--text-muted:#8A7AAA;--accent:#5B6EF5;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--dark-bg);color:var(--text-main);font-family:'DM Sans',sans-serif;-webkit-font-smoothing:antialiased;padding:3rem 1.6rem 5rem;}
.wrap{max-width:1080px;margin:0 auto;}
.logo{font-family:'Bebas Neue',sans-serif;font-size:2.4rem;letter-spacing:2px;background:linear-gradient(135deg,var(--neon-green),var(--electric-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.logo span{color:var(--hot-pink);-webkit-text-fill-color:var(--hot-pink);}
.sub{color:var(--text-muted);font-size:.95rem;margin-top:.4rem;max-width:60ch;line-height:1.6;}
.sec{display:flex;align-items:baseline;gap:1rem;margin:3rem 0 1.2rem;border-bottom:1px solid var(--card-border);padding-bottom:.6rem;}
.sec .n{font-family:'Space Mono',monospace;color:var(--neon-green);font-size:.8rem;}
.sec.admin .n{color:var(--accent);}
.sec h2{font-family:'Bebas Neue',sans-serif;font-size:1.7rem;letter-spacing:1.5px;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.9rem;}
.pcard{display:flex;align-items:center;gap:.8rem;text-decoration:none;color:var(--text-main);background:var(--card-bg);border:1.5px solid var(--card-border);border-radius:12px;padding:.85rem 1rem;transition:.18s;}
.pcard:hover{border-color:var(--electric-blue);box-shadow:0 0 24px rgba(0,212,255,.1);transform:translateY(-2px);}
.pid{font-family:'Space Mono',monospace;font-size:.72rem;font-weight:700;color:var(--neon-green);border:1px solid var(--card-border);border-radius:6px;padding:.2rem .5rem;flex-shrink:0;}
.ptitle{font-size:.9rem;line-height:1.25;}
.admin-grid .pid{color:var(--accent);}
.admin-grid .pcard:hover{border-color:var(--accent);box-shadow:0 0 24px rgba(91,110,245,.12);}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">Groove<span>Vault</span></div>
  <p class="sub">All screens as live PHP pages. User-app pages share <code>inc/header.inc.php</code> + <code>inc/footer.inc.php</code>; admin pages live in <code>/admin/</code> and share <code>inc/admin-header.inc.php</code> + <code>inc/admin-footer.inc.php</code>.</p>

  <div class="sec"><span class="n">01</span><h2>User App — 14 screens</h2></div>
  <div class="grid">
      <a class="pcard" href="index.php"><span class="pid">U1</span><span class="ptitle">Home / Landing</span></a>
      <a class="pcard" href="register.php"><span class="pid">U2</span><span class="ptitle">Register</span></a>
      <a class="pcard" href="verify_email.php"><span class="pid">U3</span><span class="ptitle">Verify Email</span></a>
      <a class="pcard" href="login.php"><span class="pid">U4</span><span class="ptitle">Login</span></a>
      <a class="pcard" href="forgot_password.php"><span class="pid">U5</span><span class="ptitle">Forgot / Reset Password</span></a>
      <a class="pcard" href="pick_plan.php"><span class="pid">U6</span><span class="ptitle">Pick Plan + PayPal</span></a>
      <a class="pcard" href="dashboard.php"><span class="pid">U7</span><span class="ptitle">Dashboard — My Channels</span></a>
      <a class="pcard" href="create_channel.php"><span class="pid">U8</span><span class="ptitle">Create / Edit Channel</span></a>
      <a class="pcard" href="channel_detail.php"><span class="pid">U9</span><span class="ptitle">Channel Detail</span></a>
      <a class="pcard" href="player.php"><span class="pid">U10</span><span class="ptitle">Player Bar — Continuous Radio Play</span></a>
      <a class="pcard" href="add_track.php"><span class="pid">U11</span><span class="ptitle">Add Track</span></a>
      <a class="pcard" href="share_channel.php"><span class="pid">U12</span><span class="ptitle">Share Channel</span></a>
      <a class="pcard" href="public_channel.php"><span class="pid">U13</span><span class="ptitle">Public Channel — Shared Link View</span></a>
      <a class="pcard" href="delete_channel.php"><span class="pid">U14</span><span class="ptitle">Delete Channel?</span></a>
  </div>

  <div class="sec admin"><span class="n">02</span><h2>Admin Panel — 10 screens</h2></div>
  <div class="grid admin-grid">
      <a class="pcard" href="admin/login.php"><span class="pid">A1</span><span class="ptitle">Admin Login Gate</span></a>
      <a class="pcard" href="admin/dashboard.php"><span class="pid">A2</span><span class="ptitle">Dashboard — Overview</span></a>
      <a class="pcard" href="admin/users.php"><span class="pid">A3</span><span class="ptitle">Users — Registrations, Price Paid, Dates &amp; Last Login</span></a>
      <a class="pcard" href="admin/user_detail.php"><span class="pid">A4</span><span class="ptitle">User Detail</span></a>
      <a class="pcard" href="admin/channels.php"><span class="pid">A5</span><span class="ptitle">All Channels</span></a>
      <a class="pcard" href="admin/subscriptions.php"><span class="pid">A6</span><span class="ptitle">Subscriptions</span></a>
      <a class="pcard" href="admin/activity_log.php"><span class="pid">A7</span><span class="ptitle">Activity Log</span></a>
      <a class="pcard" href="admin/suspended_users.php"><span class="pid">A8</span><span class="ptitle">Suspended Users</span></a>
      <a class="pcard" href="admin/settings.php"><span class="pid">A9</span><span class="ptitle">Admin Settings</span></a>
      <a class="pcard" href="admin/confirm_modal.php"><span class="pid">A10</span><span class="ptitle">Confirm Modals</span></a>
  </div>
</div>
</body>
</html>
